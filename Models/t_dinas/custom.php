<?php

namespace App\Models\CustomModels;
use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;

class t_dinas extends \App\Models\BasicModels\t_dinas
{    
    private $helper;
    public function __construct()
    {
        parent::__construct();
       $this->helper = getCore("Helper");
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    public function t_dinas_d() :\HasMany
    {
        return $this->HasMany('App\Models\BasicModels\t_dinas_d', 't_dinas_id', 'id');
    }

     public function createBefore($model, $arrayData, $metaData, $id = null)
    {
        $newArrayData = array_merge($arrayData, [
            "kode" => $this->helper->generateNomor("KODE RENCANA PERJALANAN DINAS"),
        ]);

        return [
            "model" => $model,
            "data" => $newArrayData,
            // "errors" => ['error1']
        ];
    }

    public function transformRowData(array $row)
    {
        $karyawan = t_dinas_d::where('t_dinas_id', $row['id'])
            ->with('m_kary:id,nama_depan')
            ->get()
            ->pluck('m_kary.nama_depan') // ambil nama saja
            ->implode(', '); // gabung jadi string

        $row['karyawan'] = $karyawan;

        return $row;
    }
    

    public function custom_setPresensi($req)
    {   
        $validator = Validator::make($req->all(), [
            "id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->helper->responseValidate($validator);
        }

        try{
            $dinas = t_dinas::findOrFail($req->id);
            $posted = $dinas->update([
                "status" => 'POSTED'
            ]);
        
            if($dinas && $posted)
            {
                $m_kary_ids = $dinas->t_dinas_d->pluck('m_kary_id');
                // dd($m_kary_ids);
                $req = new \Illuminate\Http\Request();
                $req->merge([
                    'm_kary_ids' => $m_kary_ids,
                    'date' => $dinas->date
                ]);
                // dd($req);

                $dinas = $this->custom_checkin_dinas($req);
                return $this->helper->customResponse(
                        "Checkin dinas berhasil",
                        200
                    );
            }else{
                // return "Checkin dinas gagal, status masih draft";
                return $this->helper->customResponse(
                        "Checkin dinas gagal, status masih draft",
                        422
                    );
            }
        }catch (\Exception $e) {
            // DB::rollback();
            // return "Checkin dinas gagal, - " . $e->getMessage();
            return $this->helper->customResponse(
                        "Checkin Dinas Gagal - " . $e->getMessage(),
                        500
                    );
        }
    }

    public function custom_checkin_dinas($req)
    {
        $validator = Validator::make($req->all(), [
            "m_kary_ids" => "required",
            "date" => "required"
        ]);

        if ($validator->fails()) {
            return $this->helper->responseValidate($validator);
        }

        DB::beginTransaction();
        try {
            // dd($req->m_kary_ids);
            foreach($req->m_kary_ids as $m_kary_id){
                $user = default_users::where('m_kary_id', $m_kary_id)->first();
                if (!$user) {
                    return $this->helper->customResponse(
                            "User Tidak Ditemukan!",
                            500
                        );
                }

                $check_exists_absen = presensi_absensi::where('tanggal', $req->date)
                    ->where("default_user_id", $user->id)
                    ->first();
                // dd($check_exists_absen);
                if($check_exists_absen){
                    // $status = $check_exists_absen->status;
                    // if(strtolower($status) === 'break' ){
                        $check_exists_absen->update([
                            // "status" => "RESUME WORKING",
                            "checkout_istirahat_time" => "12:00:00",
                            "checkin_kerja_time" => "13:00:00"
                        ]);
                    
                        DB::commit();

                        // return $this->helper->customResponse(
                        //     "Checkin Berhasil!",
                        //     200,
                        //     $check_exists_absen
                        // );
                    // }
                }
                // else{

                //     $presensi = presensi_absensi::create([
                //         "tanggal" => $req->date,
                //         "checkin_time" => Carbon::now()->format('H:i:s'),
                //         "checkin_foto" => "-",
                //         "checkin_lat" => "-",
                //         "checkin_long" => "-",
                //         "checkin_address" => "-",
                //         "checkin_region" => "-",
                //         "checkin_on_scope" => false,
                //         "catatan_in" => "DINAS",
                //         "default_user_id" => $user->id,
                //         "creator_id" => auth()->id(),
                //         "status" => "WORKING"
                //     ]);
                //     DB::commit();
                //     // return "Checkin berhasil" . $presensi;
                //     return $this->helper->customResponse(
                //             "Checkin Berhasil",
                //             200,
                //             $presensi
                //         );
                // }
            }
        } catch (\Exception $e) {
            DB::rollback();
            // return "Checkin gagal, coba kembali nanti - " . $e->getMessage();
            return $this->helper->customResponse(
                        "Checkin gagal",
                        422
                    );
        }
    }
}
