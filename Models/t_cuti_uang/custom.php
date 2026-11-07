<?php

namespace App\Models\CustomModels;

use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;

class t_cuti_uang extends \App\Models\BasicModels\t_cuti_uang
{    
    private $helper;
    public function __construct()
    {
        parent::__construct();
        $this->helper = getCore("Helper");
    }

     public function createBefore($model, $arrayData, $metaData, $id = null)
    {
        $newArrayData = array_merge($arrayData, [
            "kode" => $this->helper->generateNomor("KODE CUTI DIUANGKAN"),
        ]);

        return [
            "model" => $model,
            "data" => $newArrayData,
        ];
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    // public function custom_save($req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "m_kary_id" => "required",
    //         "jumlah_cuti_diuangkan" => "required",
    //         "date" => "required",
    //         "status" => "required",
    //         "nominal" => "required",
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->helper->responseValidate($validator);
    //     }

    //     DB::beginTransaction();
    //     try{
    //         $m_kary = m_kary::find($req->m_kary_id);

    //         if($m_kary){
    //             $t_cuti_adjustment = t_cuti_adjustment::create([
    //                 "m_kary_id" => $m_kary->id,
    //                 "m_comp_id" => $m_kary->m_comp_id,
    //                 "m_dir_id" => $m_kary->m_dir_id,
    //                 "value" => (float)$req->jumlah_cuti_diuangkan,
    //                 "creator_id" => auth()->user()->id,
    //                 "date" => $req->date,
    //                 "keterangan" => 'Cuti Diuangkan',
    //             ]);

    //             // $kary_salary = t_kary_salary::whereHas('t_kary_salary_det', function($q){
    //             //     $q->where('keterangan', 'Gaji Pokok');
    //             // })
    //             // ->where('m_kary_id', $m_kary->id)
    //             // ->first();
                
    //             // // dd($kary_salary->tipe_perhitungan, $kary_salary->t_kary_salary_det[0]->nominal);
    //             // $kompensasi = 0;
    //             // if($kary_salary){
    //             //     if($kary_salary->tipe_perhitungan != 'HARIAN')
    //             //     {
    //             //         $kompensasi = 480 * $kary_salary->t_kary_salary_det[0]->nominal;
    //             //     }else{
    //             //         $kompensasi = $kary_salary->t_kary_salary_det[0]->nominal;
    //             //     }
    //             // }

    //             // dd($kompensasi);

    //             if($t_cuti_adjustment)
    //             {
    //                 $date = Carbon::parse($req->date);
    //                 $formatted = $date->format('dmy');
    //                 $t_cuti_uang = t_cuti_uang::create([
    //                     "t_cuti_adjustment_id" => $t_cuti_adjustment->id,
    //                     "m_kary_id" => $m_kary->id,
    //                     "jumlah_cuti_diuangkan" => $req->jumlah_cuti_diuangkan,
    //                     "nominal" => $req->nominal,
    //                     "kode" => 'CU'. $formatted . $m_kary->kode ,
    //                     "status" => $req->status,
    //                     "tanggal" => $req->date,
    //                 ]);
    //             }

    //             // dd($t_cuti_uang, 'tes');
    //         }
    //         DB::commit();

    //         return $this->helper->customResponse(
    //                 "Pengajuan Berhasil",
    //                 200
    //             );
    //     }catch (\Exception $e) {
    //         DB::rollback();
    //         return $this->helper->customResponse(
    //                     "Pengajuan Gagal - " . $e->getMessage(),
    //                     500
    //                 );
    //     }
    // }

    public function custom_posted($req)
    {
        $validator = Validator::make($req->all(), [
            "id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->helper->responseValidate($validator);
        }

        DB::beginTransaction();
        try{
            $t_cuti_uang = t_cuti_uang::find($req->id);
            
            // dd($t_cuti_uang);
            if($t_cuti_uang)
            {
                $m_kary = m_kary::find($t_cuti_uang->m_kary_id);
                $tipe_cuti = m_general::where('value', 'Cuti Satu Hari')->first();

                $t_cuti_adjustment = t_cuti_adjustment::create([
                    "m_kary_id" => $m_kary->id,
                    "m_comp_id" => $m_kary->m_comp_id,
                    "m_dir_id" => $m_kary->m_dir_id,
                    "value" => -($t_cuti_uang->jumlah_cuti_diuangkan),
                    "date" => Carbon::now()->format('Y-m-d'),
                    "keterangan" => 'Cuti Diuangkan',
                    "tipe_cuti_id" => $tipe_cuti->id,
                ]);

                $t_cuti_uang->update([
                    "t_cuti_adjustment_id" => $t_cuti_adjustment->id,
                    "status" => "POSTED"
                ]);

                DB::commit();
                return $this->helper->customResponse(
                    "Pengajuan Berhasil",
                    200
                );
            }else{
                return $this->helper->customResponse(
                    "Pengajuan gagal, data tidak ditemukan",
                    422
                );
            }

        }catch (\Exception $e) {
            DB::rollback();
            return $this->helper->customResponse(
                        "Pengajuan Gagal - " . $e->getMessage(),
                        500
                    );
        }
    }

    public function custom_getWage($req)
    {
        $m_kary = m_kary::find($req->m_kary_id);
        $kary_salary = t_kary_salary::whereHas('t_kary_salary_det', function($q){
                $q->where('keterangan', 'Gaji Pokok');
            })
            ->where('m_kary_id', $m_kary->id)
            ->first();
                
        if($kary_salary){
            if($kary_salary->tipe_perhitungan !== 'HARIAN'){
                $data = [
                    "salary" => $kary_salary->t_kary_salary_det[0]->nominal,
                    "type" => $kary_salary->tipe_perhitungan,
                    "minute" => 480,
                    //data sisa cuti ambil dari transformrow m_kary https://server.qqltech.com:7035/operation/m_kary/953?join=true&transform=false&detail=true
                ];
            }else{
                $data = [
                    "salary" => $kary_salary->t_kary_salary_det[0]->nominal,
                    "type" => $kary_salary->tipe_perhitungan,
                    //data sisa cuti ambil dari transformrow m_kary https://server.qqltech.com:7035/operation/m_kary/953?join=true&transform=false&detail=true
                ];
            }
            return $data;
        }else{
            return [];
        }

    }


    
}