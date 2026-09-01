@php
    use App\Models\CustomModels\m_kary;
    use App\Models\CustomModels\m_general;
    use App\Models\CustomModels\t_final_gaji;
    use App\Models\CustomModels\t_final_gaji_det;
    use App\Models\CustomModels\t_final_gaji_det_rincian;
    use App\Models\CustomModels\presensi_absensi;
    use App\Models\CustomModels\m_hutang_kary;
    use App\Models\CustomModels\t_potongan;
    use App\Models\CustomModels\t_sgp;
    use App\Models\CustomModels\t_libur;
    function formatRupiah($number)
       {
           return 'Rp ' . number_format($number, 0, ',', '.');
       }
    $req = app()->request;
    $kary_list = [];

    if (!$req->m_kary_id) {
        $t_final_gaji = t_final_gaji::find($req->f_id);

        $gaji = t_final_gaji_det::where('t_final_gaji_id', $req->f_id)->with('m_kary')->get();

        foreach ($gaji as $det) {
            if ($det->m_kary) {
                $kary_list[] = $det->m_kary->id;
            }
        }
    }else{
        $kary_list[] = $req->m_kary_id;
    }
@endphp
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Out Slip Gaji</title>
    <style>
        @page {
            size: A4 portrait;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    @foreach ($kary_list as $index => $m_kary_id )
    <!-- Title -->
    <table style="width: 100%; border-bottom: 1px solid black;">
        <tr>
            <td style="width: 86%;"></td>
            <td style="font-size: 12px; width:14%; letter-spacing: 1px; font-weight: bold;">Slip Gaji</td>
        </tr>
    </table>
    <!-- end -->

    @php
        // $req = app()->request;
        $details = (new m_kary())->details;
        $joins = (new m_kary())->joins;
        $karyawan = m_kary::with($details, ['m_divisi', 'm_dept', 'm_posisi'])->find($m_kary_id);
        $bank = $karyawan->m_kary_det_pemb->isNotEmpty()
            ? m_general::where('group', 'BANK')
                    ->where('id', $karyawan->m_kary_det_pemb->first()->bank_id)
                    ->pluck('value')
                    ->first() ?? 'N/A'
            : 'N/A';

        $t_final_gaji = t_final_gaji::find($req->f_id);
        $periode_from = $t_final_gaji->periode_awal ?? '2023-01-01';
        $periode_to = $t_final_gaji->periode_akhir ?? '2023-12-30';

        $gaji = t_final_gaji_det::where('m_kary_id', $m_kary_id)->where('t_final_gaji_id', $req->f_id)->first();
        $det_penerimaan = t_final_gaji_det_rincian::where('t_final_gaji_det_id', $gaji->id)
            ->where('factor', '+')
            ->get();
        $det_potongan = t_final_gaji_det_rincian::where('t_final_gaji_det_id', $gaji->id)->where('factor', '-')->get();
        // dd($det_potongan);

        $denda_items = $det_potongan->filter(function ($item) {
            return str_contains($item->label, 'Denda Istirahat Lebih Dari Waktu Istirahat');
        });

        $total_denda = $denda_items->sum(function ($item) {
            return floatval($item->value);
        });

        $new_denda = $denda_items->first();

        if ($new_denda) {
            $new_denda = clone $new_denda;
            $new_denda->label = 'Denda Istirahat Lebih Dari Waktu Istirahat';
            $new_denda->value = $total_denda;
        } else {
            $new_denda = (object) [
                'label' => 'Denda Istirahat Lebih Dari Waktu Istirahat',
                'value' => $total_denda,
            ];
        }

        $det_potongan = $det_potongan->reject(function ($item) {
            return str_contains($item->label, 'Denda Istirahat Lebih Dari Waktu Istirahat');
        });

        $det_potongan->push($new_denda);
        // dd($det_potongan);

        $date_start = date('Y-m-d', strtotime($periode_from));
        $date_end = date('Y-m-d', strtotime($periode_to));

        if ($req->periode) {
            $periode = $req->periode . '-' . date('d');
        } elseif ($date_start) {
            $periode = $date_start;
        } else {
            $periode = $dateNow;
        }

        $data = \DB::table(\DB::raw('employee_attendance_detail_range_new(?,?,?) as detail'))
            ->select('*')
            ->whereBetween('all_days_of_month', [$date_start, $date_end])
            ->where(function ($query) {
                $query
                    ->whereRaw("(absensi::json->>'checkin_time') is not null")
                    ->orWhereRaw("(absensi::json->>'checkout_time') is not null")
                    ->orWhereRaw("(absensi::json->>'status') = 'CUTI'")
                    ->orWhereRaw("(absensi::json->>'status') = 'NOT ATTEND'");
            })
            ->addBinding($date_start, 'select')
            ->addBinding($date_end, 'select')
            ->addBinding($karyawan->id, 'select')
            ->get();
          
          $hadir = $data->filter(function($absensi){
            return @json_decode($absensi->absensi)->status === 'ATTEND';
          })->count();

        $jadwal_off_kary = t_libur::with('t_libur_d')
            ->select('tanggal_mulai', 'tanggal_akhir', 'desc as keterangan')
            ->whereHas('t_libur_d', function ($q) use ($karyawan) {
                $q->where('m_kary_id', $karyawan->id);
            })
            ->where('is_active', true)
            ->whereDate('tanggal_mulai', '<=', $date_end)
            ->whereDate('tanggal_akhir', '>=', $date_start)
            ->get();

        $jadwal_off_dates = collect($jadwal_off_kary)->pluck('tanggal')->toArray();

        $kary = m_kary::with('tipe_jam_kerja')->where('id', $karyawan->id)->first();
        $creator = m_kary::find($t_final_gaji->creator_id);

        $check_kary_jam_kerja_tipe = optional($kary->tipeJamKerja)->code;
        $check_kary_jam_kerja_tipe_id = $kary->tipe_jam_kerja_id ?? null;

        $user = \DB::table('default_users')->where('m_kary_id', $karyawan->id)->first();

        $checkin_avg = \DB::table('presensi_absensi')
            ->where('default_user_id', $user->id ?? 0)
            ->whereNotNull('checkin_time')
            ->whereBetween('tanggal', [$date_start, $date_end])
            ->selectRaw(
                "TO_CHAR(INTERVAL '1 second' * AVG(EXTRACT(EPOCH FROM checkin_time::TIME)), 'HH24:MI:SS') as checkin_avg",
            )
            ->value('checkin_avg');

        $checkout_avg = \DB::table('presensi_absensi')
            ->where('default_user_id', $user->id ?? 0)
            ->whereNotNull('checkout_time')
            ->whereBetween('tanggal', [$date_start, $date_end])
            ->selectRaw(
                "TO_CHAR(INTERVAL '1 second' * AVG(EXTRACT(EPOCH FROM checkout_time::TIME)), 'HH24:MI:SS') as checkout_avg",
            )
            ->value('checkout_avg');

        $rekap = [
            [
                'checkin_avg' => $checkin_avg,
                'checkout_avg' => $checkout_avg,
                'id' => $karyawan->id ?? null,
                'kode' => $karyawan->kode ?? null,
                'nama_lengkap' => $karyawan->nama_lengkap ?? null,
                'dept' => $karyawan->dept ?? null,
            ],
        ];

        $total_checkin_telat = 0;
        $total_checkout_lebih_awal = 0;
        $total_checkin_lebih_awal = 0;
        $total_checkout_telat = 0;
        $total_hari_kerja = 0;
        $total_hadir = 0;
        $total_cuti = 0;
        $total_alpha = 0;
        $total_lembur_hari_biasa = 0;
        $total_lembur_hari_merah = 0;

        $sisa_cuti = DB::selectOne(
            "
            SELECT (employee_attendance(?,?) ->> 'sisa_cuti_satu_hari')::int AS sisa_cuti_satu_hari
        ",
            [$date_start, $karyawan->id],
        );

        // $start_jam_kerja = null;

        // if ($karyawan->m_jam_kerja?->waktu_mulai) {
        //     $start_jam_kerja = date('H:i:s', strtotime($karyawan->m_jam_kerja->waktu_mulai));
        //     $end_jam_kerja = date('H:i:s', strtotime($karyawan->m_jam_kerja->waktu_akhir));
        //     $start_jam_lembur = date('H:i:s', strtotime($karyawan->m_jam_kerja->waktu_akhir . ' +15 minutes'));
        // }
        if (strtolower($check_kary_jam_kerja_tipe) == 'office') {
            $jadwal = \DB::table('m_jam_kerja as t')
                ->where('t.is_active', 'true')
                ->where('t.tipe_jam_kerja_id', $check_kary_jam_kerja_tipe_id)
                ->first();
        } else {
            $jadwal = \DB::table('m_jam_kerja as t')
                ->where('t.is_active', 'true')
                ->where('t.tipe_jam_kerja_id', $check_kary_jam_kerja_tipe_id)
                ->first();
        }

        if ($jadwal) {
            $start_jam_kerja = date('H:i:s', strtotime($jadwal->waktu_mulai));
            $end_jam_kerja = date('H:i:s', strtotime($jadwal->waktu_akhir));
            $start_jam_lembur = date('H:i:s', strtotime($jadwal->waktu_akhir . ' +15 minutes'));
        }

        $absensi = presensi_absensi::where('default_user_id', $user->id ?? 0)
            ->whereBetween('tanggal', [$date_start, $date_end])
            ->whereNotNull('checkin_time')
            ->whereRaw('checkin_time > ?', [$start_jam_kerja]);

        //$telat_kali = 0;
        //$total_telat_menit = 0;

        // foreach ($absensi->get() as $row) {
        //     $checkin_time = date('H:i', strtotime($row->checkin_time));
        //     $start_time = date('H:i', strtotime($start_jam_kerja));
        //     if ($checkin_time > $start_time) {
        //         $telat_kali++;
        //         // Hitung selisih menit
        //         $jam_telat = (strtotime($row->checkin_time) - strtotime($start_jam_kerja)) / 60;
        //         $total_telat_menit += floor($jam_telat);
        //     }
        // }
        // Inisialisasi total
        $telat_kali = 0;
        $total_telat_menit = 0;

        foreach ($data as $dt) {
            $absensi_json = json_decode($dt->absensi);

            $waktu_mulai = strtotime(@$jadwal->waktu_mulai);
            $waktu_checkin = strtotime(@$absensi_json->checkin_time);

            if (
                !empty($absensi_json->checkin_time) &&
                @$jadwal->waktu_mulai &&
                strtolower($dt->type) === 'hari kerja'
            ) {
                $selisih_detik = $waktu_mulai + (5 * 60) - $waktu_checkin; // (+) kalau checkin lebih lambat
                $late = floor(abs($selisih_detik) / 60);

                if ($waktu_checkin > $waktu_mulai + (5 * 60)) {
                    // TELAT
                    $telat_kali++;
                    $total_telat_menit += $late;
                }

                // Kalau mau simpan ke $dt juga:
                // $dt->checkin_result = $checkin_result;
                // $dt->telat_menit = $late;
            }
        }

        // foreach ($data as $dt) {
        //     $waktu_mulai = strtotime(@$jadwal->waktu_mulai);
        //     $waktu_checkin = strtotime(@json_decode($dt->absensi)->checkin_time);
        //     $checkin_result = @json_decode($dt->absensi)->checkin_time;
        //     if (@json_decode($dt->absensi)->checkin_time != null && @$jadwal->waktu_mulai) {
        //         $selisih_detik = $waktu_mulai - $waktu_checkin;
        //         $late = floor(abs($selisih_detik / 60)); // Dibulatkan ke bawah
        //         if ($waktu_mulai < $waktu_checkin) {
        //             $checkin_result =
        //                 @json_decode($dt->absensi)->checkin_time .
        //                 ' <span style="color: red">(' .
        //                 $late .
        //                 ' Menit )</span>';
        //             if (strtolower($dt->type) === 'hari kerja') {
        //                 $total_checkin_telat += $late;
        //                 $telat_menit = $late;
        //             }
        //         } else {
        //             $checkin_result = @json_decode($dt->absensi)->checkin_time;
        //             if (strtolower($dt->type) === 'hari kerja') {
        //                 $total_checkin_lebih_awal += $late;
        //             }
        //         }
        //     }
        // }
        // dd($telat_kali, $start_jam_kerja);

        // dd($jumlah_telat, $total_telat_menit, $absensi->get());

        // dd($start_jam_kerja);

        $total_lembur_kali = 0;
        $total_lembur_menit = 0;

        // Add query to get cuti data
        $cuti_data = \DB::select(
            "
            SELECT tc.*, mg.value as alasan_cuti
            FROM t_cuti tc
            LEFT JOIN m_general mg ON mg.id = tc.alasan_id
            WHERE tc.m_kary_id = ?
            AND tc.status = 'APPROVED'
            AND (
            (? BETWEEN tc.date_from AND tc.date_to)
OR (? BETWEEN tc.date_from AND tc.date_to)
            OR (tc.date_from BETWEEN ? AND ?)
            )
            ",
            [$karyawan->id, $date_start, $date_end, $date_start, $date_end],
        );

        $cuti_dates = [];
        foreach ($cuti_data as $cuti) {
            $start = strtotime($cuti->date_from);
            $end = strtotime($cuti->date_to);
            while ($start <= $end) {
                $cuti_dates[date('Y-m-d', $start)] = $cuti->alasan_cuti;
                $start = strtotime('+1 day', $start);
            }
        }
        // dd(count($cuti_dates));
        $total_cuti = count($cuti_dates);

        foreach ($data as $dt) {
            $type = strtolower(trim($dt->type));
            $absensi = @json_decode($dt->absensi);
            $status = isset($absensi->status) ? strtoupper(trim($absensi->status)) : null;
            $checkin_time = @$absensi->checkin_time;
            $checkout_time = @$absensi->checkout_time;
            $current_date = $dt->all_days_of_month;

            $dt->lembur_menit = 0; // Default lembur harian

            if (isset($cuti_dates[$current_date]) && $type === 'hari kerja') {
                $type = 'CUTI';
                $dt->type = 'CUTI';
                $dt->type = $cuti_dates[$current_date];
            }

            if ($type === 'hari kerja') {
                $total_hari_kerja++;

                if ($status === 'CUTI' || $status === 'IJIN') {
                    // $total_cuti++;
                } elseif ($checkin_time && $checkout_time) {
                    $total_hadir++;

                    // === HITUNG TELAT CHECKOUT & LEMBUR ===
                    $waktu_checkout = strtotime($checkout_time);

                    if ($waktu_checkout) {
                        $checkout_date = date('Y-m-d', $waktu_checkout);
                        $batas_jam_kerja = strtotime($checkout_date . ' 17:00:00');
                        $batas_lembur = strtotime($checkout_date . ' 17:15:00');

                        // Tangani jika checkout lewat tengah malam
                        if ($waktu_checkout < strtotime($checkout_date . ' 06:00:00')) {
                            $checkout_date = date('Y-m-d', strtotime('-1 day', $waktu_checkout));
                            $batas_jam_kerja = strtotime($checkout_date . ' 17:00:00');
                            $batas_lembur = strtotime($checkout_date . ' 17:15:00');
                        }

                        if ($waktu_checkout > $batas_lembur) {
                            $lembur_menit = floor(($waktu_checkout - $batas_jam_kerja) / 60);
                            $dt->lembur_menit = $lembur_menit;

                            $total_lembur_kali++;
                            $total_lembur_menit += $lembur_menit;

                            $total_checkout_telat += $lembur_menit;

                            if (strtolower($dt->type) === 'hari kerja') {
                                $total_lembur_hari_biasa += $lembur_menit;
                            } else {
                                $total_lembur_hari_merah += $lembur_menit;
                            }
                        } else {
                            $dt->lembur_menit = 0;
                        }
                    }
                } else {
                    $total_alpha++;
                }
            } elseif ($type === 'hari libur') {
                if($status === 'ATTEND'){
                  $total_hari_kerja++;
                }
            } else {
                $waktu_checkout = strtotime($checkout_time);
                $checkout_date = date('Y-m-d', $waktu_checkout);
                $batas_jam_kerja = strtotime($checkout_date . ' 17:00:00');
                $lembur_menit = floor(($waktu_checkout - $batas_jam_kerja) / 60);
                $dt->lembur_menit = $lembur_menit;
            }

            // === HITUNG DURASI ISTIRAHAT ===
            $breakout_time = @$absensi->checkout_istirahat_time;
            $breakin_time = @$absensi->checkin_kerja_time;

            if ($breakout_time && $breakin_time) {
                $waktu_breakout = strtotime($breakout_time);
                $waktu_breakin = strtotime($breakin_time);
                $break_menit = floor(($waktu_breakin - $waktu_breakout) / 60);
                $dt->break_menit = $break_menit;
            } else {
                $dt->break_menit = 0;
            }
        }

        //$getHutang = m_hutang_kary::where('m_kary_id', $karyawan->id)->where('is_active', true)->sum('total_hutang');
        //$getPotongan = t_potongan::where('m_kary_id', $karyawan->id)->where('status', 'POSTED')->sum('nilai');

        //$getHutang = m_hutang_kary::where('m_kary_id', $karyawan->id)->where('is_active', true)->get();
        //$getPotongan = t_potongan::where('m_kary_id', $karyawan->id)->where('status', 'POSTED')->get();
        //$sisa_hutang = 0;
        //dd($getHutang->getSisaDebt());
        //foreach ($getHutang as $hutang){
        //  $sisa_hutang += $hutang->getSisaDebt();
        //}
        $sisa_hutang = 0;
        $getHutang = m_hutang_kary::where('m_kary_id', $karyawan->id)->where('is_active', true)->get();
        if($getHutang->count() > 0){
          foreach($getHutang as $hutang){
            $sisa_hutang += $hutang->getSisaDebt();
          }
        }

        
        //$sisa_hutang = $getHutang <= 0 ? 0 : $getHutang - $getPotongan;
        // $sisa_hutang = $getHutang < 0 ? 0 : $remainingDebt;

        $t_sgp = t_sgp::where('m_kary_id', $karyawan->id)
            ->whereBetween('tgl', [$date_start, $date_end])
            ->count();
    @endphp

    <table>
        <tr>
            <td></td>
        </tr>
    </table>
    <table style="width: 100%; font-size: 8px;">
        <tr>
            <td style="width: 48% ;"></td>
            <td style="width: 8%;">Periode</td>
            <td style="width: 3%; text-align: center;">:</td>
            <td style="width: 18%; text-align: center;"> {{ Carbon::parse($periode_from)->isoFormat('D MMMM YYYY') }}
            </td>
            <td style="width: 5%;">s/d</td>
            <td style="width: 20%; text-align: center;">{{ Carbon::parse($periode_to)->isoFormat('D MMMM YYYY') }}</td>
        </tr>
    </table>
    <table>
        <tr>
            <td></td>
        </tr>
    </table>

    <table style="width: 100%; font-size: 8px;">
        <tr>
            <td style="width: 18%;">NIK / NAMA</td>
            <td style="width: 2%; text-align: center;">:</td>
            <td style="width: 16%;">{{ $karyawan->nik }}</td>
            <td style="width: 2%;">/</td>
            <td style="width: 12%;">{{ $karyawan->nama_lengkap }}</td>
            <td style="width: 14%;"></td>
            <td style="width: 15%;">No. Rek</td>
            <td style="width: 2%;">:</td>
            <td style="width: 7%;"></td>
            <td style="width: 7%;">
                {{ $karyawan->m_kary_det_pemb->first()?->no_rek ?? 'N/A' }}</td>
            <td style="width: 10%;"></td>
        </tr>
    </table>
    <table style="width: 100%; font-size: 8px;">
        <tr>
            <td style="width: 18%;">Direktorat / Divisi</td>
            <td style="width: 2%; text-align: center;">:</td>
            <td style="width: 16%;">STAFF</td>
            <td style="width: 2%;">/</td>
            <td style="width: 12%;">{{ $karyawan->m_dept->nama }}</td>
            <td style="width: 14%;"></td>
            <td style="width: 15%;">Nama bank</td>
            <td style="width: 2%;">:</td>
            <td style="width: 8%;"></td>
            <td style="width: 4%;">{{ $bank }}</td>
            <td style="width: 10%;"></td>
        </tr>
    </table>
    <table style="width: 100%; font-size: 8px;">
        <tr>
            <td style="width: 18%;">Departemen / posisi</td>
            <td style="width: 2%; text-align: center;">:</td>
            <td style="width: 16%;">{{ $karyawan->m_divisi->nama }}</td>
            <td style="width: 2%;">/</td>
            <td style="width: 12%;">Sumatra Utara</td>
            <td style="width: 14%;"></td>
            <td style="width: 15%;">Grade / Score</td>
            <td style="width: 2%;">:</td>
            <td style="width: 7%;"></td>
            <td style="width: 2%;">/</td>
            <td style="width: 10%;"></td>
        </tr>
    </table>

    <table>
        <tr>
            <td></td>
        </tr>
    </table>

    <table style="border-bottom: 1px solid black; border-top: 1px solid black; width: 100%; font-size:8px;">
        <tr>
            <td style="text-align: center; width: 30%;">PENERIMAAN</td>
            <td style="text-align: center; width:35%;">POTONGAN</td>
            <td style="text-align: center;width:35%;">RINCIAN</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
        <tr>
            <td style="width: 34%; vertical-align: top;">
              <table style="width: 100%; border-collapse: collapse;">
                  @php
                      // Pisahkan Bonus dan Non-Bonus
                      $bonuses = $det_penerimaan->filter(fn($item) => str_contains(strtolower($item->label), 'bonus'));
                      $others = $det_penerimaan->filter(fn($item) => !str_contains(strtolower($item->label), 'bonus'));
                      $totalBonus = $bonuses->sum('value');
                  @endphp

                  {{-- Tampilkan Baris Bonus Jika Ada --}}
                  @if($totalBonus > 0)
                  <tr>
                      <td style="width: 30%; font-size: 7px">Total Bonus</td>
                      <td style="width: 30%; font-size: 7px"></td>
                      <td style="text-align: left; font-size: 7px">{{ formatRupiah($totalBonus) }}</td>
                  </tr>
                  @endif

                  {{-- Tampilkan Sisanya (Gaji Pokok, dll) --}}
                  @foreach ($others as $terima)
                      @if($terima->value > 0)
                      <tr>
                          <td style="width: 30%; font-size: 7px">
                              {{ str_contains(strtolower($terima->label), 'gaji pokok hari biasa') ? 'Gaji Pokok' : $terima->label }}
                          </td>
                          <td style="width: 30%; font-size: 7px"></td>
                          <td style="text-align: left; font-size: 7px">{{ formatRupiah($terima->value) }}</td>
                      </tr>
                      @endif
                  @endforeach
              </table>
          </td>

          <td style="width: 33%; vertical-align: top;">
            <table style="width: 100%; border-collapse: collapse;">
                @php
                    // Kelompokkan potongan berdasarkan kata pertama (Contoh: BPJS, Potongan, Denda)
                    $groupedPotongan = $det_potongan->filter(fn($p) => $p->value > 0)->groupBy(function($item) {
                        if (str_contains(strtolower($item->label), 'bpjs')) return 'Total BPJS';
                        if (str_contains(strtolower($item->label), 'potongan')) return 'Total Potongan PT';
                        return $item->label;
                    });
                @endphp

                @forelse ($groupedPotongan as $label => $items)
                    <tr>
                        <td style="width: 30%; font-size: 7px">{{ $label }}</td>
                        <td style="width: 30%; font-size: 7px"></td>
                        <td style="text-align: left; font-size: 7px">{{ formatRupiah($items->sum('value')) }}</td>
                    </tr>
                @empty
                    <tr style="width: 100; text-align: center;">
                        <td>-</td>
                    </tr>
                @endforelse
            </table>
        </td>

            <td style="width: 33%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td>Hari Kerja</td>
                        <td style="text-align: center;">{{ $hadir }}</td>
                        <td>Hari</td>
                    </tr>
                    <tr>
                        <td>Izin / Cuti</td>
                        <td style="text-align: center;">{{ $total_cuti }}</td>
                        <td>Hari</td>
                    </tr>
                    <tr>
                        <td>Lembur</td>
                        <td style="text-align: center;">{{ $total_lembur_menit }}</td>
                        <td>Menit</td>
                    </tr>
                    <tr>
                        <td>Jumlah Lembur</td>
                        <td style="text-align: center;">{{ $total_lembur_kali }}</td>
                        <td>Kali</td>
                    </tr>
                    <tr>
                        <td>Telat</td>
                        <td style="text-align: center;">{{ $total_telat_menit }}</td>
                        <td>Menit</td>
                    </tr>
                    <tr>
                        <td>Jumlah Telat</td>
                        <td style="text-align: center;">{{ $telat_kali }}</td>
                        <td>Kali</td>
                    </tr>
                    <tr>
                        <td>SP</td>
                        <td style="text-align: center;">{{ $t_sgp }}</td>
                        <td>Kali</td>
                    </tr>
                    <tr>
                        <td>Sisa Cuti</td>
                        <td style="text-align: center;">{{ $sisa_cuti->sisa_cuti_satu_hari }}</td>
                        <td>Hari</td>
                    </tr>
                    <tr>
                        <td>Sisa Pinjam</td>
                        <td style="text-align: center;">{{ formatRupiah($sisa_hutang) }}</td>
                        <td></td>
                    </tr>

                </table>
            </td>

        </tr>
    </table>

    <table>
        <tr>
            <td></td>
        </tr>
    </table>


    <table style="border-bottom: 1px solid black; border-top: 1px solid black; width: 100%; font-size:8px;">
        <tr>
            <td style="text-align: center; width: 30%;">Total Penerimaan :
                {{ formatRupiah($det_penerimaan->sum('value')) }}</td>
            <td style="text-align: center; width:35%;">Total Pemotongan :
                -{{ formatRupiah($det_potongan->sum('value') ?? 0) }}</td>
            <td style="text-align: center;width:35%;"></td>
        </tr>
    </table>

    <table style="width: 100%; font-size:8px;">
        <tr>
            <td></td>
        </tr>
        <tr>
            <td style="width: 3%;"></td>
            <td style="width: 15%; font-weight: bold;">Take Home Pay</td>
            <td style="width: 2%;">:</td>
            <td style="width: 15%;">{{ formatRupiah($gaji->total_gaji) }}</td>
            <td style="width: 38%;"></td>
            <td style="width: 28%;">KISARAN, {{ $t_final_gaji?->created_at->isoFormat('D MMMM YYYY') }}</td>
            <td style="width: 3%;"></td>
        </tr>
    </table>

    <table style="width: 100%; font-size:8px;">
        <tr>
            <td style="width: 3%;"></td>
            <td style="width: 15%;">Keterangan</td>
            <td style="width: 2%;">:</td>
            <td style="width: 15%;"></td>
            <td style="width: 34%;"></td>
            <td style="width: 14%">Dibuat Oleh</td>
            <td style="width: 14%; text-align: left;">Diterima Oleh</td>
            <td style="width: 3%;"></td>
        </tr>
        <tr>
            <td style="width: 3%;"></td>
            <td style="width: 15%;">System</td>
            <td style="width: 2%;">:</td>
            <td style="width: 15%;"></td>
            <td style="width: 34%;"></td>
            <td style="width: 14%"></td>
            <td style="width: 14%; text-align: right;"></td>
            <td style="width: 3%;"></td>
        </tr>
        <tr>
            <td style="width: 3%;"></td>
            <td style="width: 15%;">Rincian</td>
            <td style="width: 2%;">:</td>
            <td style="width: 15%;"></td>
            <td style="width: 34%;"></td>
            <td style="width: 14%;">{{ $creator ? $creator->nama_lengkap : 'Admin' }}</td>
            <td style="width: 14%; text-align: left;">{{ $karyawan->nama_lengkap }}</td>
            <td style="width: 3%;"></td>
        </tr>
    </table>

    <table>
        <tr>
            <td></td>
        </tr>
    </table>

    <table style="font-size:8px; width: 100%; border-collapse: collapse; font-family: monospace;">
        <!-- Header baris -->
        <tr>
            <td style="width: 3%; border: none;"></td>
            <th style="width: 11%; border-bottom: 1px dashed black; border-top: 1px dashed black;">Tanggal</th>
            <th style="width: 8%; border-bottom: 1px dashed black; border-top: 1px dashed black;">C.In</th>
            <th style="width: 7%; border-bottom: 1px dashed black; border-top: 1px dashed black;">C.Out</th>
            <th style="width: 7%; border-bottom: 1px dashed black; border-top: 1px dashed black;">B.Out</th>
            <th style="width: 7%; border-bottom: 1px dashed black; border-top: 1px dashed black;">B.In</th>
            <th style="width: 7%; border-bottom: 1px dashed black; border-top: 1px dashed black;">Telat</th>
            <th style="width: 7%; border-bottom: 1px dashed black; border-top: 1px dashed black;">Lembur</th>
            <th style="width: 7%; border-bottom: 1px dashed black; border-top: 1px dashed black;">Break</th>
            <th style="width: 18%; border-bottom: 1px dashed black; border-top: 1px dashed black;">Keterangan System
            </th>
            <th style="width: 15%; border-bottom: 1px dashed black; border-top: 1px dashed black; text-align:center;">
                Status</th>
            <td style="width: 3%; border: none;"></td>
        </tr>

        @foreach ($data as $dt)
            @php
                //tidak perlu tampilkan jika dia hari libur dan tidak masuk
                $absensi_status = json_decode($dt->absensi)->status ?? null;
                if($dt->type === 'Hari Libur' && $absensi_status != 'ATTEND'){
                    continue;
                }
                $waktu_mulai = strtotime(@$jadwal->waktu_mulai);
                $waktu_checkin = strtotime(@json_decode($dt->absensi)->checkin_time);

                $telat_menit = 0;
                $break_menit = 0;

                $checkin_result = null;

                if (@json_decode($dt->absensi)->checkin_time != null) {
                    $waktu_checkin = strtotime(@json_decode($dt->absensi)->checkin_time);
                    $checkin_result = date('H:i', $waktu_checkin);

                    $selisih_detik = $waktu_mulai - $waktu_checkin;
                    $late = floor(abs($selisih_detik / 60));

                    if ($waktu_mulai < $waktu_checkin) {
                        if (strtolower($dt->type) === 'hari kerja') {
                            $total_checkin_telat += $late;
                            $telat_menit = $late;
                        }
                        // $total_checkin_telat += $late;
                        // $telat_menit = $late;
                    } else {
                        if (strtolower($dt->type) === 'hari kerja') {
                            $total_checkin_lebih_awal += $late;
                        }
                    }
                }

                $checkout_time = @json_decode($dt->absensi)->checkout_time;
                $checkout_result = $checkout_time ? date('H:i', strtotime($checkout_time)) : '-';
                $waktu_checkout = strtotime(@json_decode($dt->absensi)->checkout_time);

                $breakout_time = @json_decode($dt->absensi)->checkout_istirahat_time;
                $breakin_time = @json_decode($dt->absensi)->checkin_kerja_time;

                $breakout_result = '-';
                $breakin_result = '-';

                if ($breakout_time) {
                    $waktu_breakout = strtotime($breakout_time);
                    $breakout_result = date('H:i', $waktu_breakout);
                }

                if ($breakin_time) {
                    $waktu_breakin = strtotime($breakin_time);
                    $breakin_result = date('H:i', $waktu_breakin);
                }

                $break_menit = '-';
                if ($breakout_time && $breakin_time) {
                    $break_menit = floor(($waktu_breakin - $waktu_breakout) / 60);
                }

                $is_off = false;
                $off_desc = '';
                foreach ($jadwal_off_kary as $off) {
                    $current_date = $dt->all_days_of_month;
                    if ($current_date >= $off->tanggal_mulai && $current_date <= $off->tanggal_akhir) {
                        $is_off = true;
                        $off_desc = $off->keterangan;
                        break;
                    }
                }
            @endphp

            <tr style="font-size: 7px;">
                <td style="width: 3%; border: none;"></td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">{{ $dt->date_to_idn }}
                </td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">
                    {{ $is_off ? '-' : $checkin_result }}</td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">{{ $checkout_result }}
                </td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">{{ $breakout_result }}
                </td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">{{ $breakin_result }}
                </td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">
                    {{ $telat_menit > 0 ? $telat_menit : '-' }} </td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">
                    {{ $dt->lembur_menit > 0 ? $dt->lembur_menit : '-' }} </td>
                <td style="text-align: center; border-right: 1px dashed black; padding: 2px;">
                    {{ $break_menit > 0 ? $break_menit : '-' }} </td>
                <td style="width: 18%; border-right: 1px dashed black; padding: 2px;">
                    @if ($dt->type == 'CUTI')
                        CUTI
                        @if ($dt->type)
                            ({{ $dt->type }})
                        @endif
                    @else
                        {{ $dt->type }}
                        @if ($dt->type != 'Hari Kerja')
                            ({{ $dt->type }})
                        @endif
                    @endif
                </td>
                <td style="width: 15%; padding: 2px;">
                    @if ($is_off)
                        OFF
                        {{-- @if ($off_desc)
                            <br><small>({{ $off_desc }})</small>
                        @endif --}}
                    @else
                        @php
                            $absensi_status = json_decode($dt->absensi)->status ?? null;
                        @endphp
                        @if ($absensi_status === 'ATTEND')
                            @if ($dt->type === 'Hari Kerja')
                                HADIR
                            @else
                                HADIR
                            @endif
                        @else
                            TIDAK HADIR
                        @endif
                    @endif
                </td>
                <td style="width: 3%; border: none;"></td>
            </tr>
        @endforeach

        <!-- Footer -->
        <tr>
            <td style="width: 3%; border: none;"></td>
            <td colspan="10" style="padding: 2px; font-size: 8px;">Jumlah Hari Kerja: {{ $total_hari_kerja }}</td>
            <td style="width: 3%; border: none;"></td>
        </tr>
    </table>

    {{-- @else
    @foreach ($kary_list as $m_kary_id)
    
    @endforeach --}}
   @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach
</body>

</html>

<!-- <script>
    window.print();
</script> -->
