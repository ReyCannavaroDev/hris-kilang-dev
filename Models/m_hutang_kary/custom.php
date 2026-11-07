<?php

namespace App\Models\CustomModels;
use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;

class m_hutang_kary extends \App\Models\BasicModels\m_hutang_kary
{
    private $helper;
    public function __construct()
    {
        parent::__construct();
        $this->helper = getCore("Helper");
    }

    public $fileColumns = [ /*file_column*/];

    //public $createAdditionalData = ["creator_id"=>"auth:id"];
    //public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    public function transformRowData(array $row)
    {
        $data = [];
        if (app()->request->view_debt) {
            if($row['t_potongan_id'])
            {
                $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
                        ->where('m_kary_id', $row['m_kary_id'] ?? 0)
                        ->where("jenis_potongan_id", $row['jenis_potongan_id'])
                        ->where('id', $row['t_potongan_id'])
                        ->where('status', 'POSTED')
                        ->first();

                    $paid = 0;
                    $sisa = 0;
                    $hutang = $this->where('jenis_potongan_id', $row['jenis_potongan_id'])
                    ->where('m_kary_id', $row['m_kary_id'])
                    ->where('is_active', true)
                    ->sum('total_hutang') ?? 0;
                    
                    if ($t_potongan) {
                        //$paid = $t_potongan->t_final_gaji_det_rincian?->sum('value') ?? 0;
                        // $paid = t_final_gaji_det_rincian::
                        // where('factor', '-')
                        // // ->where('label', 'ILIKE', '%Potongan%')
                        // ->whereHas('t_potongan', function($q) use ($row){
                        //     $q->where('jenis_potongan_id', $row['jenis_potongan_id']);
                        // })->whereHas('t_final_gaji_det', function($q) use ($row){
                        //     $q->where('m_kary_id', $row['m_kary_id']);
                        // })
                        // ->sum('value');
                         $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $row['m_kary_id'])
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $row['t_potongan_id'])
                        ->sum('t_final_gaji_det_rincian.value');
                        
                        // dd($row['id'], $paid);

                        $sisa = $row['total_hutang'] - $paid;
                        //$sisa = $hutang - $paid;

                    }
        
                    $data = [
                        'total_dibayar' => (int)$paid ?? 0,
                        'total_hutang_jenis' => (int)$hutang ?? 0,
                        'sisa_hutang' => $sisa <= 0 ? 0 : $sisa,
                    ];   
            }else{
                $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
                    ->where('m_kary_id', $row['m_kary_id'] ?? 0)
                    ->where("jenis_potongan_id", $row['jenis_potongan_id'])
                    ->where('status', 'POSTED')
                    ->pluck('id');

                $paid = 0;
                $sisa = 0;
                $hutang = $this->where('jenis_potongan_id', $row['jenis_potongan_id'])
                ->where('m_kary_id', $row['m_kary_id'])
                ->where('is_active', true)
                ->sum('total_hutang') ?? 0;
                
                if ($t_potongan) {
                    //$paid = $t_potongan->t_final_gaji_det_rincian?->sum('value') ?? 0;
                    $paid = t_final_gaji_det_rincian::
                    where('factor', '-')
                    //->where('label', 'ILIKE', '%Potongan%')
                    ->whereIn('t_potongan_id', $t_potongan)
                    // ->whereHas('t_potongan', function($q) use ($row){
                    //     $q->where('jenis_potongan_id', $row['jenis_potongan_id']);
                    // })
                    ->whereHas('t_final_gaji_det', function($q) use ($row){
                        $q->where('m_kary_id', $row['m_kary_id']);
                    })
                    ->sum('value');
                    
                    $sisa = $hutang - $paid;
                }
    
                $data = [
                    'total_dibayar' => (int)$paid ?? 0,
                    'total_hutang_jenis' => (int)$hutang ?? 0,
                    'sisa_hutang' => $sisa <= 0 ? 0 : $sisa,
                ];
            }
        }
        return array_merge($row, $data);
    }
// public function transformRowData(array $row)
// {
//     $data = [];
//     if (app()->request->view_debt) {
//         // 1. Cari Potongan yang aktif (POSTED) untuk karyawan dan jenis ini
//         $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
//             ->where('m_kary_id', $row['m_kary_id'] ?? 0)
//             ->where("jenis_potongan_id", $row['jenis_potongan_id'])
//             ->where('status', 'POSTED')
//             ->first();

//         $paid = 0;
//         $hutang = 0;

//         // 2. Logika Alur Baru: Cek apakah hutang ini sudah memiliki t_potongan_id
//         // $this merujuk ke model m_hutang_kary (asumsi fungsi ini ada di dalam model/repository tersebut)
//         $queryHutang = $this->where('m_kary_id', $row['m_kary_id'])
//                             ->where('is_active', true);

//         // Cek fallback: jika ada t_potongan_id yang cocok, ambil yang spesifik
//         if ($t_potongan && (clone $queryHutang)->where('t_potongan_id', $t_potongan->id)->exists()) {
//             $hutang = $queryHutang->where('t_potongan_id', $t_potongan->id)->sum('total_hutang');
//         } else {
//             // Data lama: Ambil total hutang berdasarkan jenis potongan
//             $hutang = $queryHutang->where('jenis_potongan_id', $row['jenis_potongan_id'])->sum('total_hutang');
//         }

//         // 3. Hitung Total yang sudah dibayar
//         if ($t_potongan) {
//             // Kita hitung pembayaran KHUSUS untuk potongan ID ini agar datanya presisi
//             $paid = t_final_gaji_det_rincian::where('factor', '-')
//                 ->where('t_potongan_id', $t_potongan->id) // Gunakan ID spesifik jika tersedia
//                 ->whereHas('t_final_gaji_det', function($q) use ($row){
//                     $q->where('m_kary_id', $row['m_kary_id']);
//                 })
//                 ->sum('value');
            
//             // Fallback paid: Jika hasil sum 0, mungkin data lama yang belum mencatat t_potongan_id di rincian
//             if ($paid == 0) {
//                  $paid = t_final_gaji_det_rincian::where('factor', '-')
//                     ->where('label', 'ILIKE', '%Potongan%')
//                     ->whereHas('t_potongan', function($q) use ($row){
//                         $q->where('jenis_potongan_id', $row['jenis_potongan_id']);
//                     })
//                     ->whereHas('t_final_gaji_det', function($q) use ($row){
//                         $q->where('m_kary_id', $row['m_kary_id']);
//                     })
//                     ->sum('value');
//             }
//         }

//         $sisa = $hutang - $paid;

//         $data = [
//             'total_dibayar' => (int)$paid,
//             //'total_hutang_jenis' => (int)$hutang,
//             'total_hutang_jenis' => $row['total_hutang'],
//             'sisa_hutang' => $sisa <= 0 ? 0 : $sisa,
//         ];
//     }
//     return array_merge($row, $data);
// }

    public function getSisaDebt()
    {
        $paid_debt = t_final_gaji_det_rincian::
            where('factor', '-')
            ->where('label', 'ILIKE', '%Potongan%')
            ->whereHas('t_potongan', function($q){
                $q->where('jenis_potongan_id', $this->jenis_potongan_id)
                ->where('m_kary_id', $this->m_kary_id);
            })->whereHas('t_final_gaji_det', function($q){
                    $q->where('m_kary_id', $this->m_kary_id);
                })
            ->sum('value');

        $total_hutang = static::query()
        ->where('jenis_potongan_id', $this->jenis_potongan_id)
        ->where('m_kary_id', $this->m_kary_id)
        ->where('is_active', true)
        ->sum('total_hutang') ?? 0;
        
        return max(0, $total_hutang - $paid_debt);
    }

    public function custom_pinjamanMandiri($req)
    {
       $validator = Validator::make($req->all(), [
            "total_hutang" => "required",
            "jenis_potongan_id" => "required",
            // "tanggal" => "required",
        ]);

         if ($validator->fails()) {
            return $this->helper->responseValidate($validator);
        }

        DB::beginTransaction();
        try{
            // dd(auth()->user());
            $kary = m_kary::find(auth()->user()->m_kary_id);

            if($kary)
            {
                $pinjaman = m_hutang_kary::create([
                    "m_kary_id" => $kary->id,
                    "total_hutang" => $validator->total_hutang,
                    "jenis_potongan_id" => $validator->jenis_potongan_id,
                    "tanggal" => Carbon::now()->format("Y-m-d"),
                    "is_active" => false,
                    "creator_id" => auth()->user()->id,
                ]);

                return $this->helper->customResponse(
                        "Pengajuan pinjaman mandiri berhasil! Menunggu persetujuan manajemen",
                        200
                    );
            }
        }catch (\Exception $e) {
            DB::rollback();
            return $this->helper->customResponse(
                        "Pengajuan Pinjaman Gagal - " . $e->getMessage(),
                        500
                    );
        }
    }
}