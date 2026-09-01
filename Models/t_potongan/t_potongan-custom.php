<?php

namespace App\Models\CustomModels;

class t_potongan extends \App\Models\BasicModels\t_potongan
{
    private $helper;
    public function __construct()
    {
        parent::__construct();
        $this->helper = getCore("Helper");
    }

    public $fileColumns = ["doc"];

    public $createAdditionalData = ["creator_id" => "auth:id"];
    public $updateAdditionalData = ["last_editor_id" => "auth:id"];

    public function t_final_gaji_det_rincian() :\HasMany
    {
        return $this->hasMany('App\Models\BasicModels\t_final_gaji_det_rincian', 't_potongan_id', 'id');
    }

    public function createBefore($model, $arrayData, $metaData, $id = null)
    {
        $newArrayData = array_merge($arrayData, [
            "nomor" => $this->helper->generateNomor("KODE POTONGAN"),
        ]);

        return [
            "model" => $model,
            "data" => $newArrayData,
            // "errors" => ['error1']
        ];
    }

    public function custom_postData($request)
    {
        $data = t_potongan::find($request->id);

        if (!$data) {
            return response()->json(["error" => "Data tidak ditemukan."], 404);
        }

        try {
            $update = $data->update([
                "status" => "POSTED",
            ]);

            if ($update) {
                return response()->json([
                    "message" => "Data berhasil diposting.",
                ]);
            } else {
                return response()->json(
                    ["error" => "Gagal memperbarui status."],
                    500
                );
            }
        } catch (\Exception $e) {
            // Handle exception, log error messages, etc.
            return response()->json(
                ["error" => "Terjadi kesalahan: " . $e->getMessage()],
                500
            );
        }
    }

    public function transformRowData(array $row)
    {
        $data = [];
        if (app()->request->view_hutang) {
            $getHutang = m_hutang_kary::where("m_kary_id", $row["m_kary_id"])
                ->where("jenis_potongan_id", $row["jenis_potongan_id"])
                ->sum("total_hutang");

            $getPotongan = t_potongan::where(
                "jenis_potongan_id",
                $row["jenis_potongan_id"]
            )
                ->where("m_kary_id", $row["m_kary_id"])
                ->sum("nilai");

            $remainingDebt = $getHutang - $getPotongan;
            $data = [
                "total_debt" => $getHutang,
                "total_debt_payed" => $getPotongan,
                "remaining_debt" => $remainingDebt < 0 ? 0 : $remainingDebt,
            ];
        }
        return array_merge($row, $data);
    }

    public function scopeDateRange($model)
    {
        $dateFrom = request("date_from");
        $dateTo = request("date_to");

        if ($dateFrom && $dateTo) {
            $dateFrom = \Carbon::parse($dateFrom)->format("Y-m-d");
            $dateTo = \Carbon::parse($dateTo)->format("Y-m-d");

            return $model
                ->whereDate("date_from", "<=", $dateTo)
                ->whereDate("date_to", ">=", $dateFrom);
        }else{
            return $model;
        }
    }

    public function custom_getDebt($request)
    {
        $user = m_kary::find($request->id);
        
        if (!$user) {
            return response()->json(['error' => 'Data tidak ditemukan.'], 404);
        }

        try {
            $getHutang = m_hutang_kary::where('m_kary_id', $user->id)->where('jenis_potongan_id', $request->jenis_potongan_id)->where('is_active', true)->sum('total_hutang');
            // $getPotongan = t_potongan::where("jenis_potongan_id", $request->jenis_potongan_id)->where('m_kary_id', $user->id)->where('status', 'POSTED')->sum('nilai');
            $getPotongan = t_final_gaji_det_rincian::
                where('factor', '-')
                ->where('label', 'ILIKE', '%Potongan%')
                ->whereHas('t_potongan', function($q) use ($request){
                    $q->where('jenis_potongan_id', $request->jenis_potongan_id);
                })->whereHas('t_final_gaji_det', function($q) use ($user){
                    $q->where('m_kary_id', $user->id);
                })
                ->sum('value');
            $remainingDebt = $getHutang - $getPotongan;
            $data = [
                'total_dibayar' => (int)($getPotongan ?? 0),
                'total_hutang_jenis' => (int)($getHutang ?? 0),
                'sisa_hutang' => $getHutang <= 0 ? 0 : $remainingDebt,
            ];

            return $this->helper->customResponse("Data hutang karyawan", 200, $data);

        }catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
