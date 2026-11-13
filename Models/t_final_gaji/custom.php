<?php

namespace App\Models\CustomModels;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;

class t_final_gaji extends \App\Models\BasicModels\t_final_gaji
{    
    private $helper;
    public function __construct()
    {
        parent::__construct();
        $this->helper = getCore('Helper');
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    public $createAdditionalData = ["creator_id"=>"auth:id"];
    public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    public static function isRestrictedUser(): bool
    {
        $currentUser = auth()->user();
        if (!$currentUser) return false;
        $username = strtolower($currentUser->username ?? '');
        $name = strtolower($currentUser->name ?? '');
        return in_array($username, ['sisi', 'kristina']) || in_array($name, ['sisi', 'kristina']);
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('restrictUserKary', function ($builder) {
            if (self::isRestrictedUser()) {
                $builder->whereHas('t_final_gaji_det.m_kary', function($q) {
                    $q->where(function($sub) {
                        $sub->where('m_kary.is_proses_krani', true)
                            ->orWhereRaw("LOWER(m_kary.nama_lengkap) LIKE '%wagino%'");
                    });
                });
            }
        });
    }

    public function createBefore( $model, $arrayData, $metaData, $id=null )
    {
        $req = app()->request;
        $newArrayData  = array_merge( $arrayData,[
            'nomor' => $this->helper->generateNomor('KODE FINAL GAJI')
        ]);
       
        return [
            "model"  => $model,
            "data"   => $newArrayData,
            // "errors" => ['error1']
        ];
    }

    public function custom_tes()
    {
        $det = t_final_gaji_det::with(['t_final_gaji_det_rincian' => function ($query) {
        $query->where('t_potongan_id', '!=', null);
        }])
        ->where('t_final_gaji_id', 1)
        ->get();


        return response()->json($det);
    }
    
    public function custom_postData($request)
    {
        \DB::beginTransaction();
        $data = t_final_gaji::find($request->id);

        if (!$data) {
            return response()->json(['error' => 'Data tidak ditemukan.'], 404);
        }

        try {

            $update = $data->update([
                'status' => "POSTED"
            ]);
            $create = true;
            $det = t_final_gaji_det::with(['t_final_gaji_det_rincian' => function ($query) {
            $query->where('t_potongan_id', '!=', null);
            }])
            ->where('t_final_gaji_id', $request->id)
            ->get();
            foreach($det as $dt){
                foreach($dt['t_final_gaji_det_rincian'] as $dt1){
                    $potongan = t_potongan::where('id', $dt1['t_potongan_id'])->first();
                    $create = t_potongan_det_bayar::create([
                        'm_potongan_id' => $dt1['t_potongan_id'],
                        't_final_gaji_id' => $request->id,
                        'percentage' =>  $potongan['percentage'],
                        'nilai' => $dt1['value'],
                        'paid_at' => \Carbon::now()
                    ]) && $create;
                }
                // if($create){
                //     $cekAngsuran = t_potongan_det_bayar::where('t_potongan_id')
                // }
            }

            if ($update && $create) {
                 \DB::commit(); 
                return response()->json(['message' => 'Data berhasil diposting.']);
            } else {
                \DB::rollback();
                return response()->json(['error' => 'Gagal memperbarui status.'], 500);
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            // Handle exception, log error messages, etc.
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function custom_save($req) {
        \Validator::make($req->all(),[
            "periode_awal" => 'required',
            "periode_akhir" => 'required',
            "total_pengeluaran_gaji" => 'required',
            "desc" => 'required',
            "status" => 'required',
            "type_perhitungan" => 'required',
        ]);
        $data = $req->all();

        if (self::isRestrictedUser() && isset($data['t_final_gaji_det'])) {
            $waginoIds = m_kary::whereRaw("LOWER(nama_lengkap) LIKE '%wagino%'")->pluck('id')->toArray();
            $data['t_final_gaji_det'] = array_values(array_filter($data['t_final_gaji_det'], function($single) use ($waginoIds) {
                return in_array($single['m_kary_id'] ?? null, $waginoIds);
            }));
            $data['total_pengeluaran_gaji'] = array_sum(array_column($data['t_final_gaji_det'], 'netto'));
        }

        \DB::beginTransaction();
        try {
            $IdFinalGaji = t_final_gaji::create([
                'nomor' => $this->helper->generateNomor('KODE FINAL GAJI'),
                'periode_awal' => $data['periode_awal'],
                'periode_akhir' => $data['periode_akhir'],
                'total_pengeluaran_gaji' => $data['total_pengeluaran_gaji'] ?? array_sum(array_column($data['t_final_gaji_det'], 'netto')),
                'desc' => $data['desc'],
                'status' => $data['status'] ?? "DRAFT",
                'type_perhitungan' => $data['type_perhitungan']
            ]);

            $finalGajiDetData = [];
            $finalGajiDetRincianData = [];

            foreach ($data['t_final_gaji_det'] as $single) {
                $finalGajiDetData[] = [
                    't_final_gaji_id' => $IdFinalGaji->id,
                    't_perhitungan_gaji_id' => $single['t_perhitungan_gaji_id'],
                    'm_kary_id' => $single['m_kary_id'],
                    'm_kary_dir_id' => $single['m_kary_dir_id'],
                    'm_kary_divisi_id' => $single['m_kary_divisi_id'],
                    'm_kary_dept_id' => $single['m_kary_dept_id'],
                    'periode' => $single['periode'],
                    'periode_in_date' => \Carbon::createFromFormat('d/m/Y', $single['periode_in_date'])->format('Y-m-d'),
                    'total_gaji' => $single['total_gaji'],
                    'total_tax' => $single['total_tax'],
                    'netto' => $single['netto'],
                    'periode_id' => $single['periode_id'],
                    'deskripsi' => $single['deskripsi'],
                    'status' => $single['status'] ?? "DRAFT",
                ];
            }

            t_final_gaji_det::insert($finalGajiDetData);

            $finalGajiDetIds = t_final_gaji_det::where('t_final_gaji_id', $IdFinalGaji->id)
                ->pluck('id')
                ->toArray();

            foreach ($data['t_final_gaji_det'] as $index => $single) {
                foreach ($single['t_final_gaji_det_rincian'] as $subSingle) {
                    // if($subSingle['value'] != null){
                        $finalGajiDetRincianData[] = [
                            't_final_gaji_det_id' => $finalGajiDetIds[$index],
                            'seq' => $subSingle['seq'],
                            'name' => $subSingle['name'] ?? null,
                            'type' => $subSingle['type'],
                            'factor' => $subSingle['factor'],
                            'value_ref' => $subSingle['value_ref'] ?? null,
                            'value' => $subSingle['value'] ?? 0,
                            'can_adjust' => $subSingle['can_adjust'] ?? null,
                            'detail' => isset($subSingle['detail']) ? json_encode($subSingle['detail']) : null,
                            'status' => $subSingle['status'] ?? 'DRAFT',
                            't_potongan_id' => $subSingle['t_potongan_id'] ?? null,
                            't_cuti_id' => $subSingle['t_cuti_id'] ?? null,
                            'label' => $subSingle['label'],
                        ];
                    // }
                }
            }

            t_final_gaji_det_rincian::insert($finalGajiDetRincianData);

            \DB::commit();
            return response()->json(['message' => 'Data berhasil disimpan.'], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function custom_update($req){
        \Validator::make($req->all(),[
            "id" => 'required',
            "periode_awal" => 'required',
            "periode_akhir" => 'required',
            "total_pengeluaran_gaji" => 'required',
            "desc" => 'required',
            "status" => 'required',
            "type_perhitungan" => 'required',
        ]);
        $data = $req->all();

        if (self::isRestrictedUser() && isset($data['t_final_gaji_det'])) {
            $waginoIds = m_kary::whereRaw("LOWER(nama_lengkap) LIKE '%wagino%'")->pluck('id')->toArray();
            $data['t_final_gaji_det'] = array_values(array_filter($data['t_final_gaji_det'], function($single) use ($waginoIds) {
                return in_array($single['m_kary_id'] ?? null, $waginoIds);
            }));
            $data['total_pengeluaran_gaji'] = array_sum(array_column($data['t_final_gaji_det'], 'netto'));
        }

        \DB::beginTransaction();
        try {
            $id = $data['id'];
            $IdFinalGaji = t_final_gaji::findOrFail($id);
            $IdFinalGaji->update([
                'periode_awal' => $data['periode_awal'],
                'periode_akhir' => $data['periode_akhir'],
                'total_pengeluaran_gaji' => $data['total_pengeluaran_gaji'],
                'desc' => $data['desc'],
                'status' => $data['status'] ?? "DRAFT",
                'type_perhitungan' => $data['type_perhitungan']
            ]);

            $finalGajiDetData = [];
            $finalGajiDetRincianData = [];

            t_final_gaji_det::where('t_final_gaji_id', $id)->delete();

            foreach ($data['t_final_gaji_det'] as $single) {
                $finalGajiDetData[] = [
                    't_final_gaji_id' => $id,
                    't_perhitungan_gaji_id' => $single['t_perhitungan_gaji_id'],
                    'm_kary_id' => $single['m_kary_id'],
                    'm_kary_dir_id' => $single['m_kary_dir_id'],
                    'm_kary_divisi_id' => $single['m_kary_divisi_id'],
                    'm_kary_dept_id' => $single['m_kary_dept_id'],
                    'periode' => $single['periode'],
                    'periode_in_date' => \Carbon::createFromFormat('d/m/Y', $single['periode_in_date'])->format('Y-m-d'),
                    'total_gaji' => $single['total_gaji'],
                    'total_tax' => $single['total_tax'],
                    'netto' => $single['netto'],
                    'periode_id' => $single['periode_id'],
                    'deskripsi' => $single['deskripsi'],
                    'status' => $single['status'] ?? "DRAFT",
                ];
            }

            t_final_gaji_det::insert($finalGajiDetData);

            $finalGajiDetIds = t_final_gaji_det::where('t_final_gaji_id', $IdFinalGaji->id)
                ->pluck('id')
                ->toArray();
                         
                         
            t_final_gaji_det_rincian::where('t_final_gaji_det_id', $finalGajiDetIds)->delete();

            foreach ($data['t_final_gaji_det'] as $index => $single) {
                foreach ($single['t_final_gaji_det_rincian'] as $subSingle) {
                    $finalGajiDetRincianData[] = [
                        't_final_gaji_det_id' => $finalGajiDetIds[$index],
                        'seq' => $subSingle['seq'],
                        'name' => $subSingle['name'] ?? null,
                        'type' => $subSingle['type'],
                        'factor' => $subSingle['factor'],
                        'value_ref' => $subSingle['value_ref'] ?? null,
                        'value' => $subSingle['value'],
                        'can_adjust' => $subSingle['can_adjust'] ?? null,
                        'detail' => isset($subSingle['detail']) ? json_encode($subSingle['detail']) : null,
                        'status' => $subSingle['status'] ?? 'DRAFT',
                        't_potongan_id' => $subSingle['t_potongan_id'] ?? null,
                        't_cuti_id' => $subSingle['t_cuti_id'] ?? null,
                        'label' => $subSingle['label'],
                    ];
                }
            }

            t_final_gaji_det_rincian::insert($finalGajiDetRincianData);


            \DB::commit();
            return response()->json(['message' => 'Data berhasil disimpan.'], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage() .' '. $e->getLine()], 500);
        }
    }

    public function custom_slip_gaji($req){
        if(app()->request->header('Source') !== 'mobile'){
           return response()->json(["message" => 'Unauthorized'],401);
        }
        $date = $req->date;
        $idFinalGaji = $req->id_final_gaji;
        $startPeriod = \Carbon::parse($date)->startOfMonth();
        $endPeriod = \Carbon::parse($date)->endOfMonth();
        $karyID = $req->kary_id;
        $query = \DB::select("select f.periode_awal, k.kode, f.nomor nomor_gaji, f.periode_akhir, f.desc,k.nama_lengkap, k.nik,kd.nama dir, kdi.nama divisi, kde.nama dept, r.*, r.label as name from t_final_gaji_det_rincian r
        join t_final_gaji_det d on d.id = r.t_final_gaji_det_id 
        join m_kary k on k.id = d.m_kary_id 
        join t_final_gaji f on f.id = d.t_final_gaji_id 
        left join m_dir kd on kd.id = k.m_dir_id 
        left join m_divisi kdi on kdi.id = k.m_divisi_id 
        left join m_dept kde on kde.id = k.m_dept_id
        where d.m_kary_id = coalesce(?,k.id)
        AND (
            (f.periode_awal BETWEEN COALESCE(?, CURRENT_DATE) AND COALESCE(?, CURRENT_DATE)) 
            OR (f.periode_akhir BETWEEN COALESCE(?, CURRENT_DATE) AND COALESCE(?, CURRENT_DATE)) 
            OR (f.periode_awal <= COALESCE(?, CURRENT_DATE) AND f.periode_akhir >= COALESCE(?, CURRENT_DATE))
        )
        order by r.seq",[$karyID, $startPeriod, $endPeriod, $startPeriod, $endPeriod, $startPeriod, $endPeriod]);
        return response()->json(['message' => 'success', 'data' => $query],200);
    }

    public function scopeFinalGajiKaryawan($model){
        $karyId = request('kary_id',0);
        return $model->whereHas('t_final_gaji_det', function($query) use($karyId){
            $query->where('m_kary_id', $karyId);
        });
    }

    public function custom_generateTaxReport($req)
    {
        try {
            $final = t_final_gaji::with('t_final_gaji_det.t_final_gaji_det_rincian')->find($req->f_id);
            if (!$final || !$final->t_final_gaji_det) {
                return response()->json(['error' => 'Data tidak ditemukan.'], 404);
            }

            $umsk = (float) (m_general::where('key', 'UMSK')->first()?->value ?? 3265908);
            $bpjskes_total = (float) (m_general::where('key', 'BPJSKES')->first()?->value ?? 0.05);
            $bpjstk_total = (float) (m_general::where('key', 'BPJSTK1')->first()?->value ?? 0.0988999996325677);
            $bpjstk55_total = (float) (m_general::where('key', 'BPJSTK2')->first()?->value ?? 0.0688999996325677);

            $periode = \Carbon\Carbon::parse($final->periode_akhir);

            // Buat data untuk Excel
            $rows = collect();
            $rows->push(['NPWP Pemotong', '0138749643115000']);
            $rows->push([
                'Masa Pajak', 'Tahun Pajak', 'Status Pegawai', 'NPWP/NIK/TIN',
                'Nomor Passport', 'Status', 'Posisi', 'Sertifikat/Fasilitas',
                'Kode Objek Pajak', 'Penghasilan Kotor', 'Tarif', 'ID TKU',
                'Tgl Pemotongan', 'PPH21 Dipotong', 'TER'
            ]);

            foreach ($final->t_final_gaji_det as $data) {
                $m_kary = m_kary::with('tanggungan')->find($data->m_kary_id);
                if (!$m_kary) continue;

                $age = $m_kary->tgl_lahir 
                    ? Carbon::parse($m_kary->tgl_lahir)->age 
                    : 0;
                

                // dd($data->t_final_gaji_det_rincian);
                $potongan = optional($data->t_final_gaji_det_rincian)
                    ->filter(fn($item) => str_contains(strtolower($item->label ?? ''), 'potongan'))
                    ->sum('value') ?? 0;
                
                $bpjs = optional($data->t_final_gaji_det_rincian)
                    ->filter(fn($item) =>
                        str_contains(strtolower($item->label ?? ''), 'bpjs') &&
                        ($item->factor ?? '') === '-'
                    )
                    ->sum('value') ?? 0;
                
                $bpjskes_perusahaan = (float)($umsk * $bpjskes_total);

                if($age < 55){
                    $bpjstk_perusahaan = (float)($umsk * $bpjstk_total);
                }else{
                    $bpjstk_perusahaan = (float)($umsk * $bpjstk55_total);                    
                }

                $tarif_perusahaan = $bpjskes_perusahaan + $bpjstk_perusahaan - $bpjs;
                
                $management_fee = 0;
                if(str_contains(strtolower($m_kary->grading?->value ?? ''), 'outsourcing')){
                    $management_fee = optional($data->t_final_gaji_det_rincian)
                    ->filter(fn($item) => str_contains(strtolower($item->label ?? ''), 'management fee'))
                    ->sum('value') ?? 0;
                }

                // $penerimaan = optional($data->t_final_gaji_det_rincian)
                //     ->filter(fn($item) =>
                //         ($item->factor ?? '') === '+'
                //     )
                //     ->sum('value') ?? 0;
                $penerimaan = $data->total_gaji;

                $tanggungan = $m_kary->tanggungan?->value ?? '-';
                $gaji = $penerimaan + $potongan + $bpjs - $management_fee + $tarif_perusahaan ?? 0;

                $category = $this->decideCategory($tanggungan);
                $tarif = $this->getTarifTER($category);
                $persen = 0;

                foreach ($tarif as $range) {
                    [$min, $max] = $range['range'];
                    if ($gaji >= $min && ($max === null || $gaji <= $max)) {
                        $persen = $range['ter'];
                        break;
                    }
                }

                $tax = round($gaji * ($persen / 100));

                $rows->push([
                    (int) $periode->format('m'),
                        (int) $periode->format('Y'),
                        'Resident',
                        "'" . $m_kary->nik ?? '-',
                        $this->formatNamaStatus($m_kary->nama_lengkap, $tanggungan) ?? '-',
                        $tanggungan,
                        'IRT',
                        'N/A',
                        '21-100-01',
                        number_format((int) $gaji ?? 0, 0, ',', ','),
                        "$persen",
                        "'0138749643115000000000",
                        $periode->copy()->endOfMonth()->format('d/m/Y'),
                        number_format((int) $tax ?? 0, 0, ',', ','),
                        'TER ' . $category,
                ]);
            }

            $fileName = 'tax-report-' . Carbon::now()->format('YmdHis') . '.xlsx';

            // Export Excel langsung ke browser
           return Excel::download(new class($rows) implements \Maatwebsite\Excel\Concerns\FromCollection {
                public function __construct(public \Illuminate\Support\Collection $rows) {}
                public function collection() { return $this->rows; }
            }, $fileName, \Maatwebsite\Excel\Excel::XLSX);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function custom_generateRekapGajiBpjsReport($req)
    {
        try {
            $final = t_final_gaji::with([
                't_final_gaji_det' => function ($q) {
                    $q->orderBy('id', 'asc');
                },
                't_final_gaji_det.t_final_gaji_det_rincian'
            ])->find($req->f_id);

            if (!$final || !$final->t_final_gaji_det) {
                return response()->json(['error' => 'Data final gaji tidak ditemukan.'], 404);
            }

            $periodeAwal = \Carbon\Carbon::parse($final->periode_awal);
            $periodeAkhir = \Carbon\Carbon::parse($final->periode_akhir);
            $bulanTahun = $periodeAkhir->locale('id')->translatedFormat('F Y');
            $rentangTgl = $periodeAwal->format("dM'y") . '-' . $periodeAkhir->format("dM'y");

            $rows = collect();

            // Baris 1: Header Utama
            $rows->push([
                '', '', '', '', 'Periode', 'Pot BPJS', '', 'Gaji yang diterima', '', '', '', 'T O T A L',
                '', '', 'NO', '', '', 'GAJI', 'BPJS Kesehatan', '', '',
                '', 'NO', '', '', 'GAJI', 'BPJS Ketenagakerjaan', '', '', '', ''
            ]);

            // Baris 2: Sub-header Kolom
            $rows->push([
                'N', 'KODE', 'N A M A', 'N I K', '', '', '', '', 'Management', 'PPn', 'Potongan', '',
                '', '', '', 'KODE', 'NAMA', '', '', '', 'TOTAL IURAN',
                '', '', 'KODE', 'NAMA', '', '', '', 'TOTAL IURAN', '', 'Gaji sebelum potongan'
            ]);

            // Baris 3: Kategori & Keterangan Pengali
            $rows->push([
                'O', 'KARYAWAN', '', '', $bulanTahun, 'Kesehatan', 'Ketenagakerjaan', '', 'fee', 'management fee', 'PPh', '',
                '', '', '', 'KARYAWAN', '', $bulanTahun, 'Perusahaan', 'Tenaga Kerja', '',
                '', '', 'KARYAWAN', '', $bulanTahun, 'Perusahaan', 'Tenaga Kerja', '', '', '+'
            ]);

            // Baris 4: Rentang Tanggal & Keterangan Rumus
            $rows->push([
                '', '', '', '', $rentangTgl, '', '', '', '', '', '', '',
                '', '', '', '', '', '', '', '', '',
                '', '', '', '', '', '', '', '', '', 'BPJS yg dibayar perusahaan'
            ]);

            // Baris 5: Indeks Kolom 1 - 30
            $rows->push([
                '', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11',
                '12', '13', '14', '15', '16', '17', '18', '19', '20',
                '21', '22', '23', '24', '25', '26', '27', '28', '29', '30'
            ]);

            $no = 1;
            $sum_gaji_periode = 0;
            $sum_pot_kes = 0;
            $sum_pot_tk = 0;
            $sum_gaji_diterima = 0;
            $sum_fee = 0;
            $sum_ppn = 0;
            $sum_pph = 0;
            $sum_total = 0;

            $sum_dasar_kes = 0;
            $sum_kes_perusahaan = 0;
            $sum_kes_tk = 0;
            $sum_kes_total = 0;

            $sum_dasar_tk = 0;
            $sum_tk_perusahaan = 0;
            $sum_tk_tk = 0;
            $sum_tk_total = 0;

            $sum_bruto = 0;

            foreach ($final->t_final_gaji_det as $data) {
                $m_kary = m_kary::with('tanggungan')->find($data->m_kary_id);
                if (!$m_kary) continue;

                $kodeKary = $m_kary->kode ?? $m_kary->nik ?? '';
                $tanggungan = $m_kary->tanggungan?->value ?? '';
                $namaKary = $this->formatNamaStatus($m_kary->nama_lengkap, $tanggungan);
                $nik = (string) ($m_kary->nik ?? '');

                // Kolom E: Gaji Periode sebelum potongan
                $gaji_periode = (float) $data->total_gaji;

                // Kolom F: Potongan BPJS Kesehatan (pekerja)
                $pot_bpjs_kes = (float) optional($data->t_final_gaji_det_rincian)
                    ->filter(fn($item) => str_contains(strtolower($item->label ?? ''), 'kes') &&
                                          str_contains(strtolower($item->label ?? ''), 'bpjs') &&
                                          ($item->factor ?? '') === '-')
                    ->sum('value');

                // Kolom G: Potongan BPJS Ketenagakerjaan (pekerja)
                $pot_bpjs_tk = (float) optional($data->t_final_gaji_det_rincian)
                    ->filter(fn($item) => (str_contains(strtolower($item->label ?? ''), 'tk') || str_contains(strtolower($item->label ?? ''), 'ketenagakerjaan')) &&
                                          str_contains(strtolower($item->label ?? ''), 'bpjs') &&
                                          ($item->factor ?? '') === '-')
                    ->sum('value');

                // Kolom H: Gaji yang diterima (Col E - F - G)
                $gaji_diterima = round($gaji_periode - $pot_bpjs_kes - $pot_bpjs_tk, 2);

                // Kolom I, J, K: Management fee, PPn 11%, Potongan PPh 23 (-2%)
                $management_fee = (float) optional($data->t_final_gaji_det_rincian)
                    ->filter(fn($item) => str_contains(strtolower($item->label ?? ''), 'management fee'))
                    ->sum('value');

                $ppn_fee = $management_fee > 0 ? round($management_fee * 0.11, 2) : 0;
                $pph_fee = $management_fee > 0 ? round(-0.02 * $management_fee, 2) : 0;

                // Kolom L: Total Gaji diterima + fee + ppn + pph
                $total_l = round($gaji_diterima + $management_fee + $ppn_fee + $pph_fee, 2);

                // BPJS Kesehatan (Kolom R, S, T, U)
                if ($pot_bpjs_kes > 0 || ($gaji_periode == 0 && str_starts_with($m_kary->kode ?? '', 'EMP') && !empty($m_kary->tgl_masuk))) {
                    if ($pot_bpjs_kes == 110000) {
                        $dasar_kes = 5500000;
                    } elseif ($pot_bpjs_kes > 0) {
                        $dasar_kes = round($pot_bpjs_kes / 0.01, 2);
                    } else {
                        $dasar_kes = 3531361;
                    }
                    $kes_perusahaan = round($dasar_kes * 0.04, 2);
                    $kes_tk = round($dasar_kes * 0.01, 2);
                    $kes_total = round($kes_perusahaan + $kes_tk, 2);
                } else {
                    $dasar_kes = 0;
                    $kes_perusahaan = 0;
                    $kes_tk = 0;
                    $kes_total = 0;
                }

                // BPJS Ketenagakerjaan (Kolom Z, AA, AB, AC)
                if ($pot_bpjs_tk > 0) {
                    if ($pot_bpjs_tk == 165000) {
                        $dasar_tk = 5500000;
                        $tk_perusahaan = 378950;
                        $tk_tk = 165000;
                    } elseif ($pot_bpjs_tk == 135000) {
                        $dasar_tk = 4500000;
                        $tk_perusahaan = 310050;
                        $tk_tk = 135000;
                    } else {
                        $dasar_tk = 3531361;
                        $ratio = $pot_bpjs_tk / $dasar_tk;
                        if ($ratio > 0.025) {
                            $tk_perusahaan = round($dasar_tk * 0.0688999996, 2);
                            $tk_tk = $pot_bpjs_tk;
                        } else {
                            $tk_perusahaan = round($dasar_tk * 0.0488999996, 2);
                            $tk_tk = $pot_bpjs_tk;
                        }
                    }
                    $tk_total = round($tk_perusahaan + $tk_tk, 2);
                } else {
                    $dasar_tk = 0;
                    $tk_perusahaan = 0;
                    $tk_tk = 0;
                    $tk_total = 0;
                }

                // Kolom Terakhir: Penghasilan Bruto PPh 21 TER (Kolom E + Kolom S + Kolom AA)
                $gaji_bruto = round($gaji_periode + $kes_perusahaan + $tk_perusahaan, 2);

                $rows->push([
                    $no,
                    $kodeKary,
                    $namaKary,
                    $nik,
                    (float) $gaji_periode,
                    (float) $pot_bpjs_kes,
                    (float) $pot_bpjs_tk,
                    (float) $gaji_diterima,
                    (float) $management_fee,
                    (float) $ppn_fee,
                    (float) $pph_fee,
                    (float) $total_l,
                    '',
                    '',
                    $no,
                    $kodeKary,
                    $namaKary,
                    (float) $dasar_kes,
                    (float) $kes_perusahaan,
                    (float) $kes_tk,
                    (float) $kes_total,
                    '',
                    $no,
                    $kodeKary,
                    $namaKary,
                    (float) $dasar_tk,
                    (float) $tk_perusahaan,
                    (float) $tk_tk,
                    (float) $tk_total,
                    '',
                    (float) $gaji_bruto
                ]);

                // Akumulasi Total
                $sum_gaji_periode += $gaji_periode;
                $sum_pot_kes += $pot_bpjs_kes;
                $sum_pot_tk += $pot_bpjs_tk;
                $sum_gaji_diterima += $gaji_diterima;
                $sum_fee += $management_fee;
                $sum_ppn += $ppn_fee;
                $sum_pph += $pph_fee;
                $sum_total += $total_l;

                $sum_dasar_kes += $dasar_kes;
                $sum_kes_perusahaan += $kes_perusahaan;
                $sum_kes_tk += $kes_tk;
                $sum_kes_total += $kes_total;

                $sum_dasar_tk += $dasar_tk;
                $sum_tk_perusahaan += $tk_perusahaan;
                $sum_tk_tk += $tk_tk;
                $sum_tk_total += $tk_total;

                $sum_bruto += $gaji_bruto;
                $no++;
            }

            // Baris Total Penjumlahan di paling bawah
            $rows->push([
                '', '', '', '',
                (float) $sum_gaji_periode,
                $sum_pot_kes > 0 ? (float) $sum_pot_kes : 0.0,
                $sum_pot_tk > 0 ? (float) $sum_pot_tk : 0.0,
                (float) $sum_gaji_diterima,
                $sum_fee > 0 ? (float) $sum_fee : '',
                $sum_ppn > 0 ? (float) $sum_ppn : '',
                $sum_pph != 0 ? (float) $sum_pph : '',
                (float) $sum_total,
                '', '', '', '', '',
                $sum_dasar_kes > 0 ? (float) $sum_dasar_kes : 0.0,
                $sum_kes_perusahaan > 0 ? (float) $sum_kes_perusahaan : 0.0,
                $sum_kes_tk > 0 ? (float) $sum_kes_tk : 0.0,
                $sum_kes_total > 0 ? (float) $sum_kes_total : 0.0,
                '', '', '', '',
                $sum_dasar_tk > 0 ? (float) $sum_dasar_tk : 0.0,
                $sum_tk_perusahaan > 0 ? (float) $sum_tk_perusahaan : 0.0,
                $sum_tk_tk > 0 ? (float) $sum_tk_tk : 0.0,
                $sum_tk_total > 0 ? (float) $sum_tk_total : 0.0,
                '',
                (float) $sum_bruto
            ]);

            $fileName = 'rekap-gaji-bpjs-' . \Carbon\Carbon::now()->format('YmdHis') . '.xlsx';

            $export = new class($rows) implements
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithEvents
            {
                public function __construct(public \Illuminate\Support\Collection $rows) {}

                public function collection()
                {
                    return $this->rows;
                }

                public function registerEvents(): array
                {
                    return [
                        \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                            $sheet = $event->sheet->getDelegate();
                            $totalRow = $this->rows->count();

                            // 1. Merge Header Sesuai Template Acuan Pak Adi
                            $merges = [
                                'E1:E2', 'F1:G2', 'H1:H4', 'L1:L4', 'O1:O4', 'R1:R2', 'S1:T2', 'W1:W4', 'Z1:Z2', 'AA1:AB2',
                                'C2:C3', 'D2:D3', 'Q2:Q3', 'U2:U3', 'Y2:Y3', 'AC2:AC3',
                                'F3:F4', 'G3:G4', 'R3:R4', 'S3:S4', 'T3:T4', 'Z3:Z4', 'AA3:AA4', 'AB3:AB4'
                            ];
                            foreach ($merges as $range) {
                                $sheet->mergeCells($range);
                            }

                            // 2. Set Lebar Kolom Proporsional (Sesuai Template Pak Adi)
                            $widths = [
                                'A' => 6, 'B' => 14, 'C' => 38, 'D' => 20, 'E' => 18, 'F' => 16, 'G' => 16, 'H' => 18,
                                'I' => 16, 'J' => 16, 'K' => 16, 'L' => 18, 'M' => 10, 'N' => 10, 'O' => 6, 'P' => 14,
                                'Q' => 38, 'R' => 18, 'S' => 16, 'T' => 16, 'U' => 18, 'V' => 10, 'W' => 6, 'X' => 14,
                                'Y' => 38, 'Z' => 18, 'AA' => 16, 'AB' => 16, 'AC' => 18, 'AD' => 10, 'AE' => 22
                            ];
                            foreach ($widths as $col => $w) {
                                $sheet->getColumnDimension($col)->setWidth($w);
                            }

                            // 3. Styling Font, Alignment & Borders Header (Baris 1 - 5)
                            $sheet->getStyle('A1:AE5')->getAlignment()
                                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                            $sheet->getStyle('A1:AE5')->getFont()->setName('Calibri')->setSize(10);
                            $sheet->getStyle('A1:AE5')->getBorders()->getAllBorders()->setBorderStyle(
                                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                            );

                            // Header Tebal Spesifik
                            foreach (['S1', 'AA1', 'E3', 'R3', 'Z3', 'H1', 'L1', 'AE2', 'AE4'] as $cell) {
                                $sheet->getStyle($cell)->getFont()->setBold(true);
                            }

                            // 4. Alignment Data Karyawan (Baris 6 s/d Baris Total)
                            $sheet->getStyle("A6:B{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("C6:C{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                            $sheet->getStyle("D6:D{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                            $sheet->getStyle("O6:P{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("Q6:Q{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                            $sheet->getStyle("W6:X{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("Y6:Y{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                            // 5. Penyelarasan NIK (Text) & Nilai 0 Eksplisit pada Kolom Finansial
                            $numFormat = '_(* #,##0.00_);_(* (#,##0.00);_(* "-"??_);_(@_)';
                            $numCols = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'R', 'S', 'T', 'U', 'Z', 'AA', 'AB', 'AC', 'AE'];

                            for ($r = 6; $r <= $totalRow; $r++) {
                                if ($r < $totalRow) {
                                    // Pastikan NIK disimpan sebagai text string murni (tanpa scientific notation & tanpa apostrof)
                                    $sheet->getCell("D{$r}")->setValueExplicit(
                                        (string) $sheet->getCell("D{$r}")->getValue(),
                                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                                    );
                                }

                                // Pastikan kolom numerik yang kosong terisi 0 (mencegah loose comparison bug PhpSpreadsheet)
                                foreach ($numCols as $col) {
                                    if ($r === $totalRow && in_array($col, ['I', 'J', 'K'])) {
                                        continue;
                                    }
                                    $cell = $sheet->getCell("{$col}{$r}");
                                    $val = $cell->getValue();
                                    if ($val === null || $val === '' || $val === '0' || $val === 0) {
                                        $cell->setValueExplicit(0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                                    }
                                }
                            }

                            // Format Angka & Rata Kanan Kolom Finansial
                            foreach ($numCols as $col) {
                                $sheet->getStyle("{$col}6:{$col}{$totalRow}")
                                    ->getNumberFormat()
                                    ->setFormatCode($numFormat);
                                $sheet->getStyle("{$col}6:{$col}{$totalRow}")
                                    ->getAlignment()
                                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                            }

                            // 6. Styling Baris Total (Sesuai Template Pak Adi: I, J, K dikosongkan)
                            $sheet->setCellValue("I{$totalRow}", null);
                            $sheet->setCellValue("J{$totalRow}", null);
                            $sheet->setCellValue("K{$totalRow}", null);

                            $sheet->getStyle("A{$totalRow}:AE{$totalRow}")->getFont()->setBold(true);
                            $doubleBottom = [
                                'borders' => [
                                    'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                                    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE],
                                ]
                            ];
                            foreach (['E', 'F', 'G', 'H', 'L', 'R', 'S', 'T', 'U', 'Z', 'AA', 'AB', 'AC', 'AE'] as $col) {
                                $sheet->getStyle("{$col}{$totalRow}")->applyFromArray($doubleBottom);
                            }

                            // Tampilkan Gridlines
                            $sheet->setShowGridlines(true);
                        }
                    ];
                }
            };

            return Excel::download($export, $fileName, \Maatwebsite\Excel\Excel::XLSX);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function custom_generateTaxReportOld($req)
    {
        try {
            $fileName = 'tax-report-' . Carbon::now()->format('YmdHis') . '.csv';

            $final = t_final_gaji::with('t_final_gaji_det')->find($req->f_id);
            if (!$final || !$final->t_final_gaji_det) {
                return response()->json(['error' => 'Data tidak ditemukan.'], 404);
            }

            $periode = Carbon::parse($final->periode_akhir);
            $report = [];

            return response()->streamDownload(function () use ($final, $periode) {
                $file = fopen('php://output', 'w');

                fputcsv($file, ['NPWP Pemotong', "'0138749643115000"]);

                // Header kolom
                fputcsv($file, [
                    'Masa Pajak',
                    'Tahun Pajak',
                    'Status Pegawai',
                    'NPWP/NIK/TIN',
                    'Nomor Passport',
                    'Status',
                    'Posisi',
                    'Sertifikat/Fasilitas',
                    'Kode Objek Pajak',
                    'Penghasilan Kotor',
                    'Tarif',
                    'ID TKU',
                    'Tgl Pemotongan',
                    'PPH21 Dipotong',
                    'TER'
                ]);

                foreach ($final->t_final_gaji_det as $data) {
                    $m_kary = m_kary::with('tanggungan')->find($data->m_kary_id);
                    if (!$m_kary) continue;

                    $tanggungan = $m_kary->tanggungan?->value ?? '-';
                    $gaji = $data->total_gaji ?? 0;

                    $category = $this->decideCategory($tanggungan);
                    $tarif = $this->getTarifTER($category);
                    $persen = 0;

                    foreach ($tarif as $range) {
                        [$min, $max] = $range['range'];
                        if ($gaji >= $min && ($max === null || $gaji <= $max)) {
                            $persen = $range['ter'];
                            break;
                        }
                    }

                    $tax = round($gaji * ($persen / 100));
                    fputcsv($file, [
                        (int) $periode->format('m'),
                        (int) $periode->format('Y'),
                        'Resident',
                        "'" . $m_kary->nik ?? '-',
                        $this->formatNamaStatus($m_kary->nama_lengkap, $tanggungan) ?? '-',
                        $tanggungan,
                        'IRT',
                        'N/A',
                        '21-100-01',
                        number_format((int) $data->total_gaji ?? 0, 0, ',', ','),
                        $persen,
                        "'0138749643115000000000",
                        $periode->format('n') . '/31/' . $periode->format('Y'),
                        number_format((int) $tax ?? 0, 0, ',', ','),
                        'TER ' . $category
                    ]);
                }

                fclose($file);
            }, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }


    // public static function hitungTER($gaji, $status)
    // {
    //     $kategori = $this->decideCategory($status);
    //     $tarif = $this->getTarifTER($kategori);

    //     foreach ($tarif as $range) {
    //         [$min, $max] = $range['range'];
    //         if ($gaji >= $min && ($max === null || $gaji <= $max)) {
    //             $persen = $range['ter'];
    //             return round($gaji * ($persen / 100));
    //         }
    //     }

    //     return 0;
    // }

    private function formatNamaStatus($nama, $tanggungan)
    {
        $cleanNama = preg_replace('/\s+/', ' ', trim($nama ?? ''));
        $namaFormatted = ucwords(strtolower($cleanNama));
        $tanggungan = strtoupper(trim(str_replace('/', '', $tanggungan ?? '')));
        if (empty($tanggungan) || $tanggungan === 'TK0') {
            $tanggungan = 'TK';
        }
        return "{$namaFormatted} ({$tanggungan})";
    }

    public function decideCategory($status)
    {
        $status = strtoupper(trim($status));

        return match (true) {
            in_array($status, ['TK/0', 'TK/1', 'K/0']) => 'A',
            in_array($status, ['TK/2', 'K/1', 'TK/3', 'K/2']) => 'B',
            default => 'C',
        };
    }

    public function getTarifTER($kategori)
    {
        return match ($kategori) {
            'A' => $this->tarifKategoriA(),
            'B' => $this->tarifKategoriB(),
            'C' => $this->tarifKategoriC(),
            default => [],
        };
    }

   public function tarifKategoriA()
    {
        return [
            ['range' => [0, 5400000], 'ter' => 0],
            ['range' => [5400000, 5650000], 'ter' => 0.25],
            ['range' => [5650000, 5950000], 'ter' => 0.5],
            ['range' => [5950000, 6300000], 'ter' => 0.75],
            ['range' => [6300000, 6750000], 'ter' => 1],
            ['range' => [6750000, 7500000], 'ter' => 1.25],
            ['range' => [7500000, 8550000], 'ter' => 1.5],
            ['range' => [8550000, 9650000], 'ter' => 1.75],
            ['range' => [9650000, 10050000], 'ter' => 2],
            ['range' => [10050000, 10350000], 'ter' => 2.25],
            ['range' => [10350000, 10700000], 'ter' => 2.5],
            ['range' => [10700000, 11050000], 'ter' => 3],
            ['range' => [11050000, 11600000], 'ter' => 3.5],
            ['range' => [11600000, 12500000], 'ter' => 4],
            ['range' => [12500000, 13750000], 'ter' => 5],
            ['range' => [13750000, 15100000], 'ter' => 6],
            ['range' => [15100000, 16950000], 'ter' => 7],
            ['range' => [16950000, 19750000], 'ter' => 8],
            ['range' => [19750000, 24150000], 'ter' => 9],
            ['range' => [24150000, 26450000], 'ter' => 10],
            ['range' => [26450000, 28000000], 'ter' => 11],
            ['range' => [28000000, 30050000], 'ter' => 12],
            ['range' => [30050000, 32400000], 'ter' => 13],
            ['range' => [32400000, 35400000], 'ter' => 14],
            ['range' => [35400000, 39100000], 'ter' => 15],
            ['range' => [39100000, 43850000], 'ter' => 16],
            ['range' => [43850000, 47800000], 'ter' => 17],
            ['range' => [47800000, 51400000], 'ter' => 18],
            ['range' => [51400000, 56300000], 'ter' => 19],
            ['range' => [56300000, 62200000], 'ter' => 20],
            ['range' => [62200000, 68600000], 'ter' => 21],
            ['range' => [68600000, 77500000], 'ter' => 22],
            ['range' => [77500000, 89000000], 'ter' => 23],
            ['range' => [89000000, 103000000], 'ter' => 24],
            ['range' => [103000000, 125000000], 'ter' => 25],
            ['range' => [125000000, 157000000], 'ter' => 26],
            ['range' => [157000000, 206000000], 'ter' => 27],
            ['range' => [206000000, 337000000], 'ter' => 28],
            ['range' => [337000000, 454000000], 'ter' => 29],
            ['range' => [454000000, 550000000], 'ter' => 30],
            ['range' => [550000000, 695000000], 'ter' => 31],
            ['range' => [695000000, 910000000], 'ter' => 32],
            ['range' => [910000000, 1400000000], 'ter' => 33],
            ['range' => [1400000000, null], 'ter' => 34],
        ];
    }

    public function tarifKategoriB()
    {
        return [
            ['range' => [0, 6200000], 'ter' => 0],
            ['range' => [6200000, 6500000], 'ter' => 0.25],
            ['range' => [6500000, 6850000], 'ter' => 0.5],
            ['range' => [6850000, 7300000], 'ter' => 0.75],
            ['range' => [7300000, 9200000], 'ter' => 1],
            ['range' => [9200000, 10750000], 'ter' => 1.5],
            ['range' => [10750000, 11250000], 'ter' => 2],
            ['range' => [11250000, 11600000], 'ter' => 2.5],
            ['range' => [11600000, 12600000], 'ter' => 3],
            ['range' => [12600000, 13600000], 'ter' => 4],
            ['range' => [13600000, 14950000], 'ter' => 5],
            ['range' => [14950000, 16400000], 'ter' => 6],
            ['range' => [16400000, 18450000], 'ter' => 7],
            ['range' => [18450000, 21850000], 'ter' => 8],
            ['range' => [21850000, 26000000], 'ter' => 9],
            ['range' => [26000000, 27700000], 'ter' => 10],
            ['range' => [27700000, 29350000], 'ter' => 11],
            ['range' => [29350000, 31450000], 'ter' => 12],
            ['range' => [31450000, 33950000], 'ter' => 13],
            ['range' => [33950000, 37100000], 'ter' => 14],
            ['range' => [37100000, 41100000], 'ter' => 15],
            ['range' => [41100000, 45800000], 'ter' => 16],
            ['range' => [45800000, 49500000], 'ter' => 17],
            ['range' => [49500000, 53800000], 'ter' => 18],
            ['range' => [53800000, 58500000], 'ter' => 19],
            ['range' => [58500000, 64000000], 'ter' => 20],
            ['range' => [64000000, 71000000], 'ter' => 21],
            ['range' => [71000000, 80000000], 'ter' => 22],
            ['range' => [80000000, 93000000], 'ter' => 23],
            ['range' => [93000000, 109000000], 'ter' => 24],
            ['range' => [109000000, 129000000], 'ter' => 25],
            ['range' => [129000000, 163000000], 'ter' => 26],
            ['range' => [163000000, 211000000], 'ter' => 27],
            ['range' => [211000000, 374000000], 'ter' => 28],
            ['range' => [374000000, 459000000], 'ter' => 29],
            ['range' => [459000000, 555000000], 'ter' => 30],
            ['range' => [555000000, 704000000], 'ter' => 31],
            ['range' => [704000000, 957000000], 'ter' => 32],
            ['range' => [957000000, 1405000000], 'ter' => 33],
            ['range' => [1405000000, null], 'ter' => 34],
        ];
    }

    public function tarifKategoriC()
    {
        return [
            ['range' => [0, 6600000], 'ter' => 0],
            ['range' => [6600000, 6950000], 'ter' => 0.25],
            ['range' => [6950000, 7350000], 'ter' => 0.5],
            ['range' => [7350000, 7800000], 'ter' => 0.75],
            ['range' => [7800000, 8850000], 'ter' => 1],
            ['range' => [8850000, 9800000], 'ter' => 1.25],
            ['range' => [9800000, 10950000], 'ter' => 1.5],
            ['range' => [10950000, 11200000], 'ter' => 1.75],
            ['range' => [11200000, 12050000], 'ter' => 2],
            ['range' => [12050000, 12950000], 'ter' => 3],
            ['range' => [12950000, 14150000], 'ter' => 4],
            ['range' => [14150000, 15550000], 'ter' => 5],
            ['range' => [15550000, 17050000], 'ter' => 6],
            ['range' => [17050000, 19500000], 'ter' => 7],
            ['range' => [19500000, 22700000], 'ter' => 8],
            ['range' => [22700000, 26600000], 'ter' => 9],
            ['range' => [26600000, 28100000], 'ter' => 10],
            ['range' => [28100000, 30100000], 'ter' => 11],
            ['range' => [30100000, 32600000], 'ter' => 12],
            ['range' => [32600000, 35400000], 'ter' => 13],
            ['range' => [35400000, 38900000], 'ter' => 14],
            ['range' => [38900000, 43000000], 'ter' => 15],
            ['range' => [43000000, 47400000], 'ter' => 16],
            ['range' => [47400000, 51200000], 'ter' => 17],
            ['range' => [51200000, 55800000], 'ter' => 18],
            ['range' => [55800000, 60400000], 'ter' => 19],
            ['range' => [60400000, 66700000], 'ter' => 20],
            ['range' => [66700000, 74500000], 'ter' => 21],
            ['range' => [74500000, 83200000], 'ter' => 22],
            ['range' => [83200000, 95000000], 'ter' => 23],
            ['range' => [95000000, 110000000], 'ter' => 24],
            ['range' => [110000000, 134000000], 'ter' => 25],
            ['range' => [134000000, 169000000], 'ter' => 26],
            ['range' => [169000000, 221000000], 'ter' => 27],
            ['range' => [221000000, 390000000], 'ter' => 28],
            ['range' => [390000000, 463000000], 'ter' => 29],
            ['range' => [463000000, 561000000], 'ter' => 30],
            ['range' => [561000000, 709000000], 'ter' => 31],
            ['range' => [709000000, 965000000], 'ter' => 32],
            ['range' => [965000000, 1419000000], 'ter' => 33],
            ['range' => [1419000000, null], 'ter' => 34],
        ];
    }

}