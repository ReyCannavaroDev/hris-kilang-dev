<?php

namespace App\Cores;

use App\Models\BasicModels\t_kary_salary;
use App\Models\BasicModels\m_standart_gaji;
use App\Models\BasicModels\m_standart_gaji_det;
use App\Models\BasicModels\m_general;
use App\Models\BasicModels\t_cuti;
use App\Models\BasicModels\t_libur;
use App\Models\CustomModels\t_potongan;
use App\Models\BasicModels\m_kary;
use App\Models\BasicModels\default_users;
use App\Models\CustomModels\m_hutang_kary;
use App\Models\BasicModels\presensi_absensi;
use App\Models\BasicModels\t_lembur;
use App\Models\BasicModels\t_bonus;
use App\Models\CustomModels\m_general as grade;
use App\Models\BasicModels\m_libur_nasional;
use App\Models\CustomModels\m_jam_kerja;
use App\Models\CustomModels\t_final_gaji_det_rincian;
use App\Models\CustomModels\t_final_gaji_det;
use App\Models\CustomModels\t_final_gaji;
use Carbon\Carbon;
use Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerhitunganGaji
{
    private function getBpjsBasis($kary = null, $date_from = null)
    {
        $fallback = (float) (m_general::where('group', 'UMSK')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('value') ?? 3531361);

        try {
            if (!Schema::hasTable('m_bpjs')) {
                return $fallback;
            }

            $payrollDate = $date_from ? Carbon::parse($date_from)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $tahun = Carbon::parse($payrollDate)->format('Y');

            $query = DB::table('m_bpjs')
                ->where('is_active', true)
                ->where('tahun', $tahun)
                ->whereIn('jenis', ['UMSK', 'UMK'])
                ->where(function ($q) use ($payrollDate) {
                    $q->whereNull('effective_from')->orWhere('effective_from', '<=', $payrollDate);
                })
                ->where(function ($q) use ($payrollDate) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $payrollDate);
                });

            $nominal = $query
                ->orderByDesc('is_default')
                ->orderByRaw("CASE WHEN jenis = 'UMSK' THEN 0 WHEN jenis = 'UMK' THEN 1 ELSE 2 END")
                ->orderByDesc('id')
                ->value('nominal');

            return $nominal ? (float) $nominal : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    // public function factorSalary($standart_gaji, $kary = null, $periode = null)
    // {
    //     $firstDayOfMonth = "$periode-01";
    //     $date = new \DateTime($firstDayOfMonth);

    //     // Set the date to the last day of the month
    //     $date->modify('last day of this month');

    //     // Get the last day as a string in 'Y-m-d' format
    //     $lastDayOfMonth = $date->format('Y-m-d');

    //     $defaultColumns = [
    //         [
    //             'name' => 'gaji_pokok',
    //             'type' => 'gaji_pokok_periode'
    //         ],
    //         [
    //             'name' => 'uang_saku',
    //             'type' => 'uang_saku_periode'
    //         ],
    //         [
    //             'name' => 'tunjangan_posisi',
    //             'type' => 'tunjangan_posisi_periode'
    //         ],
    //         [
    //             'name' => 'tunjangan_kemahalan_id',
    //             'table' => 'm_tunj_kemahalan',
    //             'type' => 'tunjangan_kemahalan_periode'
    //         ],
    //         [
    //             'name' => 'uang_makan',
    //             'type' => 'uang_makan'
    //         ],
    //         [
    //             'name' => 'tunjangan_tetap',
    //             'type' => 'tunjangan_tetap'
    //         ]
    //     ];

    //     foreach ($defaultColumns as $idx => $key) {
    //         $defaultColumns[$idx]['label'] = getCore('Helper')->snakeCaseToCapitalize($key['name']);
    //         $defaultColumns[$idx]['factor'] = '+'; // pasti tambah karena default kolom tunjangan

    //         // dynamic 
    //         if (@!$key['table']) {
    //             $defaultColumns[$idx]['value'] = (float) $standart_gaji[$key['name']] ?? 0;
    //             $defaultColumns[$idx]['type'] = $standart_gaji[$key['type']];

    //         } else {
    //             if ($key['table'] == 'm_tunj_kemahalan') {
    //                 $defaultColumns[$idx]['label'] = "Tunjangan Kemahalan";
    //                 $defaultColumns[$idx]['value'] = (float) \DB::table($key['table'])->where('id', $standart_gaji[$key['name']])->pluck('besaran')->first() ?? 0;
    //                 $defaultColumns[$idx]['type'] = $standart_gaji[$key['type']];
    //             }
    //         }
    //         $defaultColumns[$idx]['can_adjust'] = 1;
    //         if ($defaultColumns[$idx]['value'] == 0) {
    //             unset($defaultColumns[$idx]);
    //         }
    //     }

    //     // faktor lain dari table m_standart_gaji_det
    //     $standart_gaji_det = m_standart_gaji_det::where('m_standart_gaji_id', $standart_gaji->id ?? 0)->get();
    //     foreach ($standart_gaji_det as $d) {
    //         $defaultColumns[] = [
    //             'label' => $d->komponen,
    //             'factor' => $d->faktor,
    //             'value' => $d->nilai,
    //             'type' => $d->periode,
    //             'can_adjust' => 1

    //         ];
    //     }

    //     if (!$kary)
    //         return $defaultColumns;

    //     // tunjangan masa kerja
    //     $general_masa_kerja = m_general::where('group', 'TUNJANGAN MASA KERJA')->where('key', '01')->pluck('value')->first();
    //     if ($general_masa_kerja && $kary->tgl_masuk) {
    //         $general_masa_kerja = (float) $general_masa_kerja;
    //         $date_from = \DateTime::createFromFormat('Y-m-d', $kary->tgl_masuk);
    //         $date_to = \DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
    //         $interval = @$date_from->diff($date_to) ?? 0;
    //         $jumlah_tahun = floor($interval->days / 365);

    //         $total_tunjangan = $general_masa_kerja * pow(2, $jumlah_tahun);
    //         if ($total_tunjangan > 0) {
    //             $defaultColumns[] = [
    //                 'label' => "Tunjangan Masa Kerja ($jumlah_tahun)",
    //                 'factor' => '+',
    //                 'value' => $total_tunjangan,
    //                 'type' => 'Bulanan',
    //                 'can_adjust' => 1
    //             ];
    //         }
    //     }

    //     // faktor lain :Potongan
    //     $t_potongan = t_potongan::where('m_kary_id', @$kary->id ?? 0)->orWhere('is_all_kary', true)->whereRaw("date_from >= ? and date_to <= ?", [$firstDayOfMonth, $lastDayOfMonth])->get();
    //     if (count($t_potongan)) {
    //         foreach ($t_potongan as $d) {
    //             $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
    //             $defaultColumns[] = [
    //                 'label' => "Potongan - $d->nomor",
    //                 'factor' => '-',
    //                 'value' => $nilai_netto,
    //                 'type' => 'Bulanan',
    //                 'can_adjust' => 1,
    //                 't_potongan_id' => $d->id
    //             ];
    //         }
    //     }

    //     // check kehadiran karyawan
    //     $attendance = \DB::select("select public.employee_attendance(?,?)", [$firstDayOfMonth, @$kary->id ?? 0]);
    //     if (count($attendance)) {
    //         $att = $attendance[0]->employee_attendance;
    //         $att = json_decode($att);

    //         $jml_hari_sebulan = $att->work_days_in_month;
    //         $tidak_masuk_kerja = $att->work_not_present;
    //         $cuti_reguler = @$att->cuti_reguler;
    //         $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
    //         $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
    //         $potongan_cuti = @$att->potongan_cuti;
    //         $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;

    //         // gaji perhari
    //         $gaji_per_hari = 0;
    //         $makan_per_hari = 0;
    //         $gaji_pokok = @$standart_gaji['gaji_pokok'] ?? 0;
    //         if ($gaji_pokok) {
    //             $gaji_per_hari = $gaji_pokok / $jml_hari_sebulan;
    //         }
    //         $standart_gaji = @$standart_gaji['uang_makan'];
    //         if ($standart_gaji) {
    //             $makan_per_hari = $standart_gaji / $jml_hari_sebulan;
    //         }

    //         // potongan tidak hadir dan jatah semua cuti sudah habis
    //         if (($sisa_cuti - $tidak_masuk_kerja) < 0) {
    //             $sisa_cuti -= $tidak_masuk_kerja;
    //             $value = $gaji_per_hari * $tidak_masuk_kerja;
    //             $defaultColumns[] = [
    //                 'label' => "Potongan Tidak Masuk Kerja ($tidak_masuk_kerja)",
    //                 'factor' => '-',
    //                 'value' => $value,
    //                 'type' => 'Bulanan',
    //                 'can_adjust' => 1
    //             ];
    //         }


    //         // ketika jatah cuti reguler masih ada -> potong uang makan
    //         if ($sisa_cuti > 0 && $potongan_cuti > 0) {
    //             $value = $makan_per_hari * $potongan_cuti;
    //             $defaultColumns[] = [
    //                 'label' => "Potongan Uang Makan Cuti ($potongan_cuti)",
    //                 'factor' => '-',
    //                 'value' => $value,
    //                 'type' => 'Bulanan',
    //                 'can_adjust' => 1
    //             ];
    //         }

    //         // ketika jatah cuti reguler tidak ada -> potong gaji 
    //         if ($sisa_cuti <= 0 && $potongan_cuti > 0) {
    //             $value = $gaji_per_hari * $potongan_cuti;
    //             $defaultColumns[] = [
    //                 'label' => "Potongan Cuti ($potongan_cuti)",
    //                 'factor' => '-',
    //                 'value' => $value,
    //                 'type' => 'Bulanan',
    //                 'can_adjust' => 1
    //             ];
    //         }


    //     }


    //     // faktor lain :Cuti
    //     $t_cuti = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$firstDayOfMonth, $lastDayOfMonth])->get();
    //     if (count($t_cuti)) {
    //         $sisa_cuti = m_kary::where('id', @$kary->id ?? 0)->pluck('cuti_sisa_reguler')->first() ?? 0;
    //         $count = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("attachment is not null and status = 'APPROVED' and date_from >= ? and date_to <= ?", [$firstDayOfMonth, $lastDayOfMonth])->count();
    //         foreach ($t_cuti as $d) {
    //             $date_from = \DateTime::createFromFormat('Y-m-d', $d->date_from);
    //             $date_to = \DateTime::createFromFormat('Y-m-d', $d->date_to);
    //             $interval = @$date_from->diff($date_to) ?? 0;
    //             $jumlah_hari = $interval->days;
    //             if ($sisa_cuti > 0) {
    //                 $jumlah_hari = $jumlah_hari - $sisa_cuti;
    //             }

    //             $gaji_per_hari = 0;
    //             $makan_per_hari = 0;
    //             $gaji_pokok = @$standart_gaji['gaji_pokok'] ?? 0;
    //             if ($gaji_pokok) {
    //                 $gaji_per_hari = $gaji_pokok / (int) date('t');
    //             }
    //             $makan_per_hari = @$standart_gaji['uang_makan'] / (int) date('t');
    //             $potongan_cuti = $gaji_per_hari * $jumlah_hari;
    //             $potongan_makan = $makan_per_hari * $jumlah_hari;

    //             if ($count > 7) {
    //                 $defaultColumns[] = [
    //                     'label' => "Potongan Cuti ($jumlah_hari)",
    //                     'factor' => '-',
    //                     'value' => $potongan_cuti,
    //                     'type' => 'Bulanan',
    //                     'can_adjust' => 1,
    //                     't_cuti_id' => $d->id
    //                 ];

    //             } else {
    //                 $defaultColumns[] = [
    //                     'label' => "Potongan Cuti (Uang Makan) ($jumlah_hari)",
    //                     'factor' => '-',
    //                     'value' => $potongan_cuti,
    //                     'type' => 'Bulanan',
    //                     'can_adjust' => 1,
    //                     't_cuti_id' => $d->id
    //                 ];
    //             }

    //         }
    //     }

    //     return $defaultColumns;
    // }

    // public function factorSalaryManual($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    // {
    //     //logic kuontol
    //     $grade = grade::where('id', $kary_grade)->with('treatments')->first();
    //     $treatments = collect($grade['treatments']);

    //     // $notCheckIn = $treatments->where('not_checkin', true);
    //     // $potonganGrade = $treatments->where('keterangan', 'like', '%potongan%');
    //     // $needOvertime = $treatments->where('need_overtime', true);
    //     // $fullweek75 = $treatments->where('is_7_5', true)->where('full_week', true);
    //     // $sundayCheckIn = $treatments->Where('day', "0")->where('full_week', false)->first();
    //     // $sundayCheckInFullWeek = $treatments->Where('day', "0")->where('full_week', true)->first();

    //     // $pluckKeterangan = $needOvertime->pluck('keterangan')->toArray();

    //     $faktor = $treatments->where('is_month', true)
    //         ->pluck('factor', 'keterangan')
    //         ->mapWithKeys(function ($faktor, $keterangan) {
    //             return [strtolower($keterangan) => $faktor];
    //         })
    //         ->toArray();

    //     $bulanan = array_keys($faktor);

    //     $defaultColumns = [];

    //     if (!$kary)
    //         return $defaultColumns;
    //     // $setQuery =  m_general::where('group', 'SET-TUNJANGAN')->get();  
    //     // $setName = $setQuery->firstWhere('key', 'NAMA')->value;

    //     // $name = strtolower(@$setName) ?? "uang bulanan";

    //     $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
    //         ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //         ->where('m_kary_id', @$kary->id ?? 0)
    //         ->where('t_kary_salary.is_active', true)
    //         ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
    //         // ->whereRaw("d.keterangan NOT ILIKE '%lembur%'")
    //         ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");
    //     // ->get();               
    //     // if ($needOvertime->isNotEmpty()) {
    //     //     $t_kary_salary = $t_kary_salary->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $pluckKeterangan);
    //     // }

    //     // Tanggal awal dan akhir
    //     // $dateFrom = new \DateTime($date_from);
    //     // $dateTo = new \DateTime($date_to);

    //     // // Hitung selisihnya
    //     // $interval = $dateFrom->diff($dateTo);

    //     // // Ambil total hari
    //     // $totalDays = $interval->days;

    //     //tunjangan
    //     if (true) {

    //         $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
    //             ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //             ->where('m_kary_id', @$kary->id ?? 0)
    //             ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
    //             ->where('t_kary_salary.is_active', true)
    //             ->get();

    //         foreach ($getTunjangan as $single) {

    //             $keteranganLower = strtolower($single['keterangan']);
    //             $factor = $faktor[$keteranganLower] ?? '+';

    //             $defaultColumns[] = [
    //                 'label' => $single['keterangan'] . ' (' . $factor . ')',
    //                 'factor' => $factor,
    //                 'value' => (float) $single['nominal'],
    //                 'type' => 'BULANAN',
    //                 'can_adjust' => 1,
    //             ];
    //         }
    //     }

    //     // $dataResults = $t_kary_salary->get();
    //     $t_kary_salary = $t_kary_salary->get();

    //     $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
    //     // $gaji_karyawan = @$t_kary_salary[0]->total;

    //     $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

    //     // faktor lain :Potongan
    //     $t_potongan = t_potongan::where('m_kary_id', @$kary->id ?? 0)->orWhere('is_all_kary', true)->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])->get();
    //     if (count($t_potongan)) {
    //         foreach ($t_potongan as $d) {
    //             if ($d->percentage) {
    //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
    //             } else {
    //                 $nilai_netto = (float) $d->nilai;

    //             }
    //             $defaultColumns[] = [
    //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
    //                 'factor' => '-',
    //                 'value' => $nilai_netto,
    //                 'type' => 'BULANAN',
    //                 'can_adjust' => 1,
    //                 't_potongan_id' => $d->id
    //             ];
    //         }
    //     }

    //     $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
    //         ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
    //         ->get();

    //     if (count($t_bonus)) {
    //         foreach ($t_bonus as $d) {
    //             $defaultColumns[] = [
    //                 'label' => "Bonus - $d->nomor ($d->keterangan)",
    //                 'factor' => '+',
    //                 'value' => (float) $d->nilai,
    //                 'type' => 'BULANAN',
    //                 'can_adjust' => 1,
    //             ];
    //         }
    //     }

    //     //check data presensi
    //     $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

    //     $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);


    //     // if ($presensi['sunday_not_checkin'] > 0) {
    //     //     if ($notCheckIn) {
    //     //         foreach ($notCheckIn as $single) {
    //     //             $presensi['count'] += $single['value'];
    //     //         }
    //     //     }
    //     // }

    //     //cek telat kerja
    //     if ($presensiMinute['late_count'] > 0) {
    //         $lateCount = $presensiMinute['late_count'];
    //         $lateValue = 10000;
    //         $defaultColumns[] = [
    //             'label' => "Denda Telat Kerja " . $lateCount . " Kali",
    //             'factor' => '-',
    //             'value' => $lateCount * $lateValue,
    //             'type' => 'BULANAN',
    //             'can_adjust' => 1
    //         ];
    //     }

    //     //cek lebih dari jadwal menit istirahat
    //     if ($presensiMinute['over_break_penalty_per_day'] > 0) {
    //         foreach ($presensiMinute['over_break_penalty_per_day'] as $key => $value) {
    //             if ($value > 0) {
    //                 $defaultColumns[] = [
    //                     'label' => "Denda Istirahat Lebih Dari Waktu Istirahat - Hari Ke-$key",
    //                     'factor' => '-',
    //                     'value' => $value,
    //                     'type' => 'BULANAN',
    //                     'can_adjust' => 1
    //                 ];
    //             }
    //         }
    //     }

    //     //cek data lembur
    //     // $getOvertime = t_lembur::where('m_kary_id', @$kary->id)
    //     //     ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
    //     //     ->join('m_kary as mk', 'm_kary_id', 'mk.id')
    //     //     // ->join('m_posisi as mp','mk.m_posisi_id','mp.id')
    //     //     ->select('t_lembur.*')
    //     //     ->where('status', 'APPROVED')->get();

    //     // $diff = 0;
    //     // $needOvertimeCount = 0;
    //     // $needOvertimeHour = $needOvertime->pluck('value_overtime')->first();
    //     // if ($getOvertime->isNotEmpty()) {
    //     //     foreach ($getOvertime as $single) {
    //     //         $startOvertime = \Carbon::parse($single['tanggal'] . '' . $single['jam_mulai']);
    //     //         $endOvertime = \Carbon::parse($single['tanggal'] . '' . $single['jam_selesai']);
    //     //         if ($single['jam_mulai'] >= $single['jam_selesai'])
    //     //             $endOvertime->addDay();
    //     //         $diff += $startOvertime->diffInHours($endOvertime);
    //     //         $overtimeTunjangan = $startOvertime->diffInHours($endOvertime);
    //     //         if ($overtimeTunjangan >= $needOvertimeHour) {
    //     //             $needOvertimeCount = $needOvertimeCount + 1;
    //     //         }
    //     //     }
    //     // }


    //     // if ($needOvertimeCount > 0) {
    //     //     if ($needOvertime) {
    //     //         $getNeedOvertime = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
    //     //             ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //     //             ->where('m_kary_id', @$kary->id ?? 0)
    //     //             ->whereIn(\DB::raw('LOWER(d.keterangan)'), $pluckKeterangan)
    //     //             ->where('t_kary_salary.is_active', true)
    //     //             ->get();

    //     //         if ($getNeedOvertime) {
    //     //             foreach ($getNeedOvertime as $single) {
    //     //                 $defaultColumns[] = [
    //     //                     'label' => $single['keterangan'],
    //     //                     'factor' => '+',
    //     //                     'value' => $needOvertimeCount * $single['nominal'],
    //     //                     'type' => 'BULANAN',
    //     //                     'can_adjust' => 1
    //     //                 ];
    //     //             }
    //     //         }
    //     //     }
    //     // }

    //     // check kehadiran karyawan
    //     $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
    //     if (count($attendance)) {
    //         $att = $attendance[0]->employee_attendance_harian;
    //         $att = json_decode($att);
    //         $jml_hari_sebulan = $att->work_days_in_month;
    //         $jml_hari_terpilih = $att->work_day_in_week;
    //         $tidak_masuk_kerja = $att->work_not_present;
    //         $cuti_reguler = @$att->cuti_reguler;
    //         $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
    //         $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
    //         $potongan_cuti = @$att->potongan_cuti;
    //         $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
    //         $libur_nasional = @$att->libur_nasional;
    //         $cuti_satu_hari = @$att->cuti_satu_hari;

    //         $total_gaji_libur_nasional = 0;
    //         $total_7_5_fullweek = 0;
    //         $totalSundayCheckin = 0;
    //         $totalSundayFullweek = 0;

    //         // if ((float) $libur_nasional > 0) {
    //         //     $getLiburNasional = $treatments->where('big_event', true)->first();
    //         //     if (isset($presensi['libur_nasional']) && $presensi['libur_nasional'] > 0) {
    //         //         $total_gaji_libur_nasional = $presensi['libur_nasional'] * $getLiburNasional['value'];
    //         //     }
    //         // }

    //         // if ($presensi['saturday_7_5_fullweek'] > 0) {
    //         //     if ($fullweek75 && !$fullweek75->isEmpty()) {
    //         //         foreach ($fullweek75 as $single) {
    //         //             $total_7_5_fullweek += $single['value'] * $presensi['saturday_7_5_fullweek'];
    //         //         }
    //         //     }
    //         // }

    //         // if ($presensi['sunday'] > 0) {
    //         //     if ($sundayCheckIn) {
    //         //         $totalSundayCheckin += $sundayCheckIn['value'] * $presensi['sunday'];
    //         //     }
    //         // }

    //         // if ($presensi['countFullWeek'] > 0) {
    //         //     if ($sundayCheckInFullWeek) {
    //         //         $totalSundayFullweek += $sundayCheckInFullWeek['value'] * $presensi['sunday'];
    //         //     }
    //         // }

    //         $gaji_pokok_hari = 0;
    //         $countOfHariLiburCheckin = 0;


    //         foreach ($t_kary_salary as $d) {
    //             if (@$d->nominal != 0) {

    //                 $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

    //                 $keterangan = null;
    //                 if (isset($d->keterangan) && $d->keterangan !== null) {
    //                     $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
    //                 }

    //                 if ($keterangan === "gaji pokok") {
    //                     $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->count();
    //                     if ($t_cuti_approved != 0) {
    //                         $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
    //                         if ($sisa_cuti_satu_hari > 0)
    //                             $gaji_pokok_hari += $t_cuti_approved;
    //                     }

    //                     $countOfHariLiburCheckin = $presensi['sunday'] + $presensi['kerja_di_hari_libur_count'] ?? 0;
    //                     $value += $countOfHariLiburCheckin;
    //                     if ($value == 0 && $t_cuti_approved > 0) {
    //                         $value = $t_cuti_approved;
    //                     }
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $value . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
    //                         'factor' => '+',
    //                         'value' => $value * (float) @$d->nominal ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === "uang makan siang") {
    //                     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
    //                         $presensi['day'] = $t_cuti_approved;
    //                     }
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
    //                         'factor' => '+',
    //                         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === "uang makan malam") {
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
    //                         'factor' => '+',
    //                         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }

    //                 if ($keterangan === "uang kerajinan") {
    //                     $maxKerajinan = 5;
    //                     $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
    //                     $hadir = max(0, min($hadir, $maxKerajinan));
    //                     $percentage = $hadir / $maxKerajinan;
    //                     $nominalKerajinan = $percentage * (float) @$d->nominal;
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan,
    //                         'factor' => '+',
    //                         'value' => $nominalKerajinan,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === 'uang lembur') {
    //                     $overtimeMinute = $presensiMinute['overtime_minute'];
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
    //                         'factor' => '+',
    //                         'value' => @$d->nominal * $overtimeMinute ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === 'uang lembur hr merah') {

    //                     if ($presensi['kerja_di_hari_libur_count'] > 0) {
    //                         $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
    //                     } else {
    //                         $overtimeOnHoliday = 0;
    //                     }

    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
    //                         'factor' => '+',
    //                         'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }

    //                 $defaultColumns[] = [
    //                     'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
    //                     'factor' => '+',
    //                     'value' => $value * (float) @$d->nominal ?? 0,
    //                     'type' => 'BULANAN',
    //                     'can_adjust' => 1
    //                 ];

    //             }
    //         }

    //         // gaji perhari
    //         $gaji_per_hari = $gaji_karyawan;
    //         $makan_per_hari = 0;


    //         // potongan tidak hadir dan jatah semua cuti sudah habis
    //         // if(($sisa_cuti-$tidak_masuk_kerja) < 0){
    //         //     $sisa_cuti -= $tidak_masuk_kerja;
    //         //     $value = $gaji_per_hari*$tidak_masuk_kerja;
    //         //     $defaultColumns[] = [
    //         //         'label'    => "Potongan Tidak Masuk Kerja ($tidak_masuk_kerja)",
    //         //         'factor'   => '-',
    //         //         'value'    => $value,
    //         //         'type'     => 'HARIAN',
    //         //         'can_adjust' => 1
    //         //     ];
    //         // }

    //         // ketika jatah cuti reguler tidak ada -> potong gaji 
    //         // if ($sisa_cuti <= 0 && $potongan_cuti > 0) {
    //         //     $value = $gaji_per_hari * $potongan_cuti;
    //         //     $defaultColumns[] = [
    //         //         'label' => "Potongan Cuti ($potongan_cuti)",
    //         //         'factor' => '-',
    //         //         'value' => $value,
    //         //         'type' => 'Bulanan',
    //         //         'can_adjust' => 1
    //         //     ];
    //         // }
    //     }

    //     if ($presensiMinute['missing_check'] != 0) {
    //         $missingCheck = $presensiMinute['missing_check'] ?? 0;
    //         $defaultColumns[] = [
    //             'label' => "Denda Absensi",
    //             'factor' => '-',
    //             'value' => $missingCheck * 5000,
    //             'type' => 'Bulanan',
    //             'can_adjust' => 1
    //         ];
    //     }

    //     // if ($diff != 0) {
    //     //     $getLembur = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
    //     //         ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //     //         ->where('m_kary_id', @$kary->id ?? 0)
    //     //         ->whereRaw('LOWER(d.keterangan) = LOWER(?)', ['Uang Lembur'])
    //     //         ->where('t_kary_salary.is_active', true)
    //     //         ->first();
    //     //     $totalOvertime = $diff * @$getLembur['nominal'] ?? 0;
    //     //     $defaultColumns[] = [
    //     //         'label' => 'Uang Lembur - ' . (float) $diff . ' Jam',
    //     //         'factor' => '+',
    //     //         'value' => (float) $totalOvertime ?? 0,
    //     //         'type' => 'BULANAN',
    //     //         'can_adjust' => 1,
    //     //     ];
    //     // }




    //     // faktor lain :Cuti
    //     // $t_cuti = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->get();
    //     // if (count($t_cuti)) {
    //     //     $sisa_cuti = m_kary::where('id', @$kary->id ?? 0)->pluck('cuti_sisa_reguler')->first() ?? 0;
    //     //     $count = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("attachment is not null and status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->count();
    //     //     foreach ($t_cuti as $d) {
    //     //         $date_from = \DateTime::createFromFormat('Y-m-d', $d->date_from);
    //     //         $date_to = \DateTime::createFromFormat('Y-m-d', $d->date_to);
    //     //         $interval = @$date_from->diff($date_to) ?? 0;
    //     //         $jumlah_hari = $interval->days;
    //     //         if ($sisa_cuti > 0) {
    //     //             $jumlah_hari = $jumlah_hari - $sisa_cuti;
    //     //         }

    //     //         $gaji_per_hari = $gaji_karyawan;
    //     //         $makan_per_hari = 0;
    //     //         $potongan_cuti = $gaji_per_hari * $jumlah_hari;
    //     //         $potongan_makan = $makan_per_hari * $jumlah_hari;

    //     //         // if($count > 7){
    //     //         $defaultColumns[] = [
    //     //             'label' => "Potongan Cuti ($jumlah_hari)",
    //     //             'factor' => '-',
    //     //             'value' => $potongan_cuti,
    //     //             'type' => 'Bulanan',
    //     //             'can_adjust' => 1,
    //     //             't_cuti_id' => $d->id
    //     //         ];

    //     //         // }else{
    //     //         //     $defaultColumns[] = [
    //     //         //         'label'    => "Potongan Cuti (Uang Makan) ($jumlah_hari)",
    //     //         //         'factor'   => '-',
    //     //         //         'value'    => $potongan_cuti,
    //     //         //         'type'     => 'Bulanan',
    //     //         //         'can_adjust' => 1,
    //     //         //         't_cuti_id' => $d->id
    //     //         //     ];
    //     //         // }

    //     //     }
    //     // }


    //     return $defaultColumns;
    // }

    public function factorSalary($standart_gaji, $kary = null, $periode = null)
    {
        $firstDayOfMonth = "$periode-01";
        $date = new \DateTime($firstDayOfMonth);

        // Set the date to the last day of the month
        $date->modify('last day of this month');

        // Get the last day as a string in 'Y-m-d' format
        $lastDayOfMonth = $date->format('Y-m-d');
        $defaultColumns = [
            [
                'name' => 'gaji_pokok_hari_biasa',
                'type' => 'gaji_pokok_hari_biasa_periode',
                'label' => 'Gaji Pokok Hari Biasa'
            ],
            [
                'name' => 'gaji_pokok_hari_merah',
                'type' => 'gaji_pokok_hari_merah_periode',
                'label' => 'Gaji Pokok Hari Merah'
            ],
            [
                'name' => 'uang_saku',
                'type' => 'uang_saku_periode'
            ],
            [
                'name' => 'tunjangan_posisi',
                'type' => 'tunjangan_posisi_periode'
            ],
            [
                'name' => 'tunjangan_kemahalan_id',
                'table' => 'm_tunj_kemahalan',
                'type' => 'tunjangan_kemahalan_periode'
            ],
            [
                'name' => 'uang_makan',
                'type' => 'uang_makan'
            ],
            [
                'name' => 'tunjangan_tetap',
                'type' => 'tunjangan_tetap'
            ]
        ];

        foreach ($defaultColumns as $idx => $key) {
            $defaultColumns[$idx]['label'] = getCore('Helper')->snakeCaseToCapitalize($key['name']);
            $defaultColumns[$idx]['factor'] = '+'; // pasti tambah karena default kolom tunjangan

            // dynamic 
            if (@!$key['table']) {
                $defaultColumns[$idx]['value'] = (float) $standart_gaji[$key['name']] ?? 0;
                $defaultColumns[$idx]['type'] = $standart_gaji[$key['type']];
            } else {
                if ($key['table'] == 'm_tunj_kemahalan') {
                    $defaultColumns[$idx]['label'] = "Tunjangan Kemahalan";
                    $defaultColumns[$idx]['value'] = (float) \DB::table($key['table'])->where('id', $standart_gaji[$key['name']])->pluck('besaran')->first() ?? 0;
                    $defaultColumns[$idx]['type'] = $standart_gaji[$key['type']];
                }
            }
            $defaultColumns[$idx]['can_adjust'] = 1;
            if ($defaultColumns[$idx]['value'] == 0) {
                unset($defaultColumns[$idx]);
            }
        }

        // faktor lain dari table m_standart_gaji_det
        $standart_gaji_det = m_standart_gaji_det::where('m_standart_gaji_id', $standart_gaji->id ?? 0)->get();
        foreach ($standart_gaji_det as $d) {
            $defaultColumns[] = [
                'label' => $d->komponen,
                'factor' => $d->faktor,
                'value' => $d->nilai,
                'type' => $d->periode,
                'can_adjust' => 1

            ];
        }

        if (!$kary)
            return $defaultColumns;

        // tunjangan masa kerja
        $general_masa_kerja = m_general::where('group', 'TUNJANGAN MASA KERJA')->where('key', '01')->pluck('value')->first();
        if ($general_masa_kerja && $kary->tgl_masuk) {
            $general_masa_kerja = (float) $general_masa_kerja;
            $date_from = \DateTime::createFromFormat('Y-m-d', $kary->tgl_masuk);
            $date_to = \DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
            $interval = @$date_from->diff($date_to) ?? 0;
            $jumlah_tahun = floor($interval->days / 365);

            $total_tunjangan = $general_masa_kerja * pow(2, $jumlah_tahun);
            if ($total_tunjangan > 0) {
                $defaultColumns[] = [
                    'label' => "Tunjangan Masa Kerja ($jumlah_tahun)",
                    'factor' => '+',
                    'value' => $total_tunjangan,
                    'type' => 'Bulanan',
                    'can_adjust' => 1
                ];
            }
        }

        // faktor lain :Potongan
        $t_potongan = t_potongan::where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where(function ($q2) use ($date_from, $date_to) {
                    $q2->where('date_from', '<=', $date_to)
                        ->where('date_to', '>=', $date_from);
                });
            })
            ->where('status', 'POSTED')
            ->get();
        if (count($t_potongan)) {
            foreach ($t_potongan as $d) {
                $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                $defaultColumns[] = [
                    'label' => "Potongan - $d->nomor",
                    'factor' => '-',
                    'value' => $nilai_netto,
                    'type' => 'Bulanan',
                    'can_adjust' => 1,
                    't_potongan_id' => $d->id
                ];
            }
        }

        // check kehadiran karyawan
        $attendance = \DB::select("select public.employee_attendance(?,?)", [$firstDayOfMonth, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance;
            $att = json_decode($att);

            $jml_hari_sebulan = $att->work_days_in_month;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;

            // gaji perhari
            $gaji_per_hari = 0;
            $makan_per_hari = 0;
            $gaji_pokok = @$standart_gaji['gaji_pokok'] ?? 0;
            if ($gaji_pokok) {
                $gaji_per_hari = $gaji_pokok / $jml_hari_sebulan;
            }
            $standart_gaji = @$standart_gaji['uang_makan'];
            if ($standart_gaji) {
                $makan_per_hari = $standart_gaji / $jml_hari_sebulan;
            }

            // potongan tidak hadir dan jatah semua cuti sudah habis
            if (($sisa_cuti - $tidak_masuk_kerja) < 0) {
                $sisa_cuti -= $tidak_masuk_kerja;
                $value = $gaji_per_hari * $tidak_masuk_kerja;
                $defaultColumns[] = [
                    'label' => "Potongan Tidak Masuk Kerja ($tidak_masuk_kerja)",
                    'factor' => '-',
                    'value' => $value,
                    'type' => 'Bulanan',
                    'can_adjust' => 1
                ];
            }


            // ketika jatah cuti reguler masih ada -> potong uang makan
            if ($sisa_cuti > 0 && $potongan_cuti > 0) {
                $value = $makan_per_hari * $potongan_cuti;
                $defaultColumns[] = [
                    'label' => "Potongan Uang Makan Cuti ($potongan_cuti)",
                    'factor' => '-',
                    'value' => $value,
                    'type' => 'Bulanan',
                    'can_adjust' => 1
                ];
            }

            // ketika jatah cuti reguler tidak ada -> potong gaji 
            if ($sisa_cuti <= 0 && $potongan_cuti > 0) {
                $value = $gaji_per_hari * $potongan_cuti;
                $defaultColumns[] = [
                    'label' => "Potongan Cuti ($potongan_cuti)",
                    'factor' => '-',
                    'value' => $value,
                    'type' => 'Bulanan',
                    'can_adjust' => 1
                ];
            }
        }


        // faktor lain :Cuti
        $t_cuti = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$firstDayOfMonth, $lastDayOfMonth])->get();
        if (count($t_cuti)) {
            $sisa_cuti = m_kary::where('id', @$kary->id ?? 0)->pluck('cuti_sisa_reguler')->first() ?? 0;
            $count = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("attachment is not null and status = 'APPROVED' and date_from >= ? and date_to <= ?", [$firstDayOfMonth, $lastDayOfMonth])->count();
            foreach ($t_cuti as $d) {
                $date_from = \DateTime::createFromFormat('Y-m-d', $d->date_from);
                $date_to = \DateTime::createFromFormat('Y-m-d', $d->date_to);
                $interval = @$date_from->diff($date_to) ?? 0;
                $jumlah_hari = $interval->days;
                if ($sisa_cuti > 0) {
                    $jumlah_hari = $jumlah_hari - $sisa_cuti;
                }

                $gaji_per_hari = 0;
                $makan_per_hari = 0;
                $gaji_pokok = @$standart_gaji['gaji_pokok'] ?? 0;
                if ($gaji_pokok) {
                    $gaji_per_hari = $gaji_pokok / (int) date('t');
                }
                $makan_per_hari = @$standart_gaji['uang_makan'] / (int) date('t');
                $potongan_cuti = $gaji_per_hari * $jumlah_hari;
                $potongan_makan = $makan_per_hari * $jumlah_hari;

                if ($count > 7) {
                    $defaultColumns[] = [
                        'label' => "Potongan Cuti ($jumlah_hari)",
                        'factor' => '-',
                        'value' => $potongan_cuti,
                        'type' => 'Bulanan',
                        'can_adjust' => 1,
                        't_cuti_id' => $d->id
                    ];
                } else {
                    $defaultColumns[] = [
                        'label' => "Potongan Cuti (Uang Makan) ($jumlah_hari)",
                        'factor' => '-',
                        'value' => $potongan_cuti,
                        'type' => 'Bulanan',
                        'can_adjust' => 1,
                        't_cuti_id' => $d->id
                    ];
                }
            }
        }

        return $defaultColumns;
    }

    // public function factorSalaryManual($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    // {
    //     //logic kuontol
    //     $grade = grade::where('id', $kary_grade)->with('treatments')->first();
    //     $treatments = collect($grade['treatments']);

    //     // $notCheckIn = $treatments->where('not_checkin', true);
    //     // $potonganGrade = $treatments->where('keterangan', 'like', '%potongan%');
    //     // $needOvertime = $treatments->where('need_overtime', true);
    //     // $fullweek75 = $treatments->where('is_7_5', true)->where('full_week', true);
    //     // $sundayCheckIn = $treatments->Where('day', "0")->where('full_week', false)->first();
    //     // $sundayCheckInFullWeek = $treatments->Where('day', "0")->where('full_week', true)->first();

    //     // $pluckKeterangan = $needOvertime->pluck('keterangan')->toArray();

    //     $faktor = $treatments->where('is_month', true)
    //         ->pluck('factor', 'keterangan')
    //         ->mapWithKeys(function ($faktor, $keterangan) {
    //             return [strtolower($keterangan) => $faktor];
    //         })
    //         ->toArray();

    //     $bulanan = array_keys($faktor);

    //     $defaultColumns = [];

    //     if (!$kary)
    //         return $defaultColumns;
    //     // $setQuery =  m_general::where('group', 'SET-TUNJANGAN')->get();  
    //     // $setName = $setQuery->firstWhere('key', 'NAMA')->value;

    //     // $name = strtolower(@$setName) ?? "uang bulanan";

    //     $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
    //         ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //         ->where('m_kary_id', @$kary->id ?? 0)
    //         ->where('t_kary_salary.is_active', true)
    //         ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
    //         // ->whereRaw("d.keterangan NOT ILIKE '%lembur%'")
    //         ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");
    //     // ->get();               
    //     // if ($needOvertime->isNotEmpty()) {
    //     //     $t_kary_salary = $t_kary_salary->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $pluckKeterangan);
    //     // }

    //     // Tanggal awal dan akhir
    //     // $dateFrom = new \DateTime($date_from);
    //     // $dateTo = new \DateTime($date_to);

    //     // // Hitung selisihnya
    //     // $interval = $dateFrom->diff($dateTo);

    //     // // Ambil total hari
    //     // $totalDays = $interval->days;

    //     //tunjangan
    //     if (true) {

    //         $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
    //             ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //             ->where('m_kary_id', @$kary->id ?? 0)
    //             ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
    //             ->where('t_kary_salary.is_active', true)
    //             ->get();

    //         foreach ($getTunjangan as $single) {

    //             $keteranganLower = strtolower($single['keterangan']);
    //             $factor = $faktor[$keteranganLower] ?? '+';

    //             $defaultColumns[] = [
    //                 'label' => $single['keterangan'] . ' (' . $factor . ')',
    //                 'factor' => $factor,
    //                 'value' => (float) $single['nominal'],
    //                 'type' => 'BULANAN',
    //                 'can_adjust' => 1,
    //             ];
    //         }
    //     }

    //     // $dataResults = $t_kary_salary->get();
    //     $t_kary_salary = $t_kary_salary->get();

    //     $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
    //     // $gaji_karyawan = @$t_kary_salary[0]->total;

    //     $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

    //     // faktor lain :Potongan
    //     $t_potongan = t_potongan::where('m_kary_id', @$kary->id ?? 0)->orWhere('is_all_kary', true)->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])->get();
    //     if (count($t_potongan)) {
    //         foreach ($t_potongan as $d) {
    //             if ($d->percentage) {
    //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
    //             } else {
    //                 $nilai_netto = (float) $d->nilai;
    //             }
    //             $defaultColumns[] = [
    //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
    //                 'factor' => '-',
    //                 'value' => $nilai_netto,
    //                 'type' => 'BULANAN',
    //                 'can_adjust' => 1,
    //                 't_potongan_id' => $d->id
    //             ];
    //         }
    //     }

    //     $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
    //         ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
    //         ->get();

    //     if (count($t_bonus)) {
    //         foreach ($t_bonus as $d) {
    //             $defaultColumns[] = [
    //                 'label' => "Bonus - $d->nomor ($d->keterangan)",
    //                 'factor' => '+',
    //                 'value' => (float) $d->nilai,
    //                 'type' => 'BULANAN',
    //                 'can_adjust' => 1,
    //             ];
    //         }
    //     }

    //     //check data presensi
    //     $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

    //     $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);


    //     // if ($presensi['sunday_not_checkin'] > 0) {
    //     //     if ($notCheckIn) {
    //     //         foreach ($notCheckIn as $single) {
    //     //             $presensi['count'] += $single['value'];
    //     //         }
    //     //     }
    //     // }

    //     //cek telat kerja
    //     if ($presensiMinute['late_count'] > 0) {
    //         $lateCount = $presensiMinute['late_count'];
    //         $lateValue = 15000;
    //         $defaultColumns[] = [
    //             'label' => "Denda Telat Kerja " . $lateCount . " Kali",
    //             'factor' => '-',
    //             'value' => $lateCount * $lateValue,
    //             'type' => 'BULANAN',
    //             'can_adjust' => 1
    //         ];
    //     }

    //     //cek lebih dari jadwal menit istirahat
    //     if ($presensiMinute['over_break_penalty_per_day'] > 0) {
    //         foreach ($presensiMinute['over_break_penalty_per_day'] as $key => $value) {
    //             if ($value > 0) {
    //                 $defaultColumns[] = [
    //                     'label' => "Denda Istirahat Lebih Dari Waktu Istirahat - Hari Ke-$key",
    //                     'factor' => '-',
    //                     'value' => $value,
    //                     'type' => 'BULANAN',
    //                     'can_adjust' => 1
    //                 ];
    //             }
    //         }
    //     }

    //     //cek data lembur
    //     // $getOvertime = t_lembur::where('m_kary_id', @$kary->id)
    //     //     ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
    //     //     ->join('m_kary as mk', 'm_kary_id', 'mk.id')
    //     //     // ->join('m_posisi as mp','mk.m_posisi_id','mp.id')
    //     //     ->select('t_lembur.*')
    //     //     ->where('status', 'APPROVED')->get();

    //     // $diff = 0;
    //     // $needOvertimeCount = 0;
    //     // $needOvertimeHour = $needOvertime->pluck('value_overtime')->first();
    //     // if ($getOvertime->isNotEmpty()) {
    //     //     foreach ($getOvertime as $single) {
    //     //         $startOvertime = \Carbon::parse($single['tanggal'] . '' . $single['jam_mulai']);
    //     //         $endOvertime = \Carbon::parse($single['tanggal'] . '' . $single['jam_selesai']);
    //     //         if ($single['jam_mulai'] >= $single['jam_selesai'])
    //     //             $endOvertime->addDay();
    //     //         $diff += $startOvertime->diffInHours($endOvertime);
    //     //         $overtimeTunjangan = $startOvertime->diffInHours($endOvertime);
    //     //         if ($overtimeTunjangan >= $needOvertimeHour) {
    //     //             $needOvertimeCount = $needOvertimeCount + 1;
    //     //         }
    //     //     }
    //     // }


    //     // if ($needOvertimeCount > 0) {
    //     //     if ($needOvertime) {
    //     //         $getNeedOvertime = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
    //     //             ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //     //             ->where('m_kary_id', @$kary->id ?? 0)
    //     //             ->whereIn(\DB::raw('LOWER(d.keterangan)'), $pluckKeterangan)
    //     //             ->where('t_kary_salary.is_active', true)
    //     //             ->get();

    //     //         if ($getNeedOvertime) {
    //     //             foreach ($getNeedOvertime as $single) {
    //     //                 $defaultColumns[] = [
    //     //                     'label' => $single['keterangan'],
    //     //                     'factor' => '+',
    //     //                     'value' => $needOvertimeCount * $single['nominal'],
    //     //                     'type' => 'BULANAN',
    //     //                     'can_adjust' => 1
    //     //                 ];
    //     //             }
    //     //         }
    //     //     }
    //     // }

    //     // check kehadiran karyawan
    //     $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
    //     if (count($attendance)) {
    //         $att = $attendance[0]->employee_attendance_harian;
    //         $att = json_decode($att);
    //         $jml_hari_sebulan = $att->work_days_in_month;
    //         $jml_hari_terpilih = $att->work_day_in_week;
    //         $tidak_masuk_kerja = $att->work_not_present;
    //         $cuti_reguler = @$att->cuti_reguler;
    //         $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
    //         $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
    //         $potongan_cuti = @$att->potongan_cuti;
    //         $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
    //         $libur_nasional = @$att->libur_nasional;
    //         $cuti_satu_hari = @$att->cuti_satu_hari;

    //         $total_gaji_libur_nasional = 0;
    //         $total_7_5_fullweek = 0;
    //         $totalSundayCheckin = 0;
    //         $totalSundayFullweek = 0;

    //         // if ((float) $libur_nasional > 0) {
    //         //     $getLiburNasional = $treatments->where('big_event', true)->first();
    //         //     if (isset($presensi['libur_nasional']) && $presensi['libur_nasional'] > 0) {
    //         //         $total_gaji_libur_nasional = $presensi['libur_nasional'] * $getLiburNasional['value'];
    //         //     }
    //         // }

    //         // if ($presensi['saturday_7_5_fullweek'] > 0) {
    //         //     if ($fullweek75 && !$fullweek75->isEmpty()) {
    //         //         foreach ($fullweek75 as $single) {
    //         //             $total_7_5_fullweek += $single['value'] * $presensi['saturday_7_5_fullweek'];
    //         //         }
    //         //     }
    //         // }

    //         // if ($presensi['sunday'] > 0) {
    //         //     if ($sundayCheckIn) {
    //         //         $totalSundayCheckin += $sundayCheckIn['value'] * $presensi['sunday'];
    //         //     }
    //         // }

    //         // if ($presensi['countFullWeek'] > 0) {
    //         //     if ($sundayCheckInFullWeek) {
    //         //         $totalSundayFullweek += $sundayCheckInFullWeek['value'] * $presensi['sunday'];
    //         //     }
    //         // }

    //         $gaji_pokok_hari = 0;
    //         $countOfHariLiburCheckin = 0;


    //         foreach ($t_kary_salary as $d) {
    //             if (@$d->nominal != 0) {

    //                 $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

    //                 $keterangan = null;
    //                 if (isset($d->keterangan) && $d->keterangan !== null) {
    //                     $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
    //                 }
    //                 if ($keterangan === "gaji pokok") {
    //                     // Hitung hari kerja biasa dan merah
    //                     $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
    //                         (float) $presensiMinute['work_minute'] :
    //                         (float) $presensi['count'];

    //                     // Hitung hari kerja merah (minggu + hari libur nasional)
    //                     $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);

    //                     // Hitung hari kerja biasa (total - hari merah)
    //                     $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);

    //                     // Tambahkan cuti yang disetujui ke hari kerja biasa
    //                     $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
    //                         ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
    //                         ->count();
    //                     if ($t_cuti_approved > 0) {
    //                         $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
    //                         if ($sisa_cuti_satu_hari > 0) {
    //                             $hari_kerja_biasa += $t_cuti_approved;
    //                         }
    //                     }

    //                     // Hitung dan tambahkan gaji untuk hari kerja biasa
    //                     if ($hari_kerja_biasa > 0) {
    //                         $nominal_hari_biasa = (float) @$d->nominal ?? 0;
    //                         $defaultColumns[] = [
    //                             'label' => 'Gaji Pokok Hari Biasa - ' . $hari_kerja_biasa .
    //                                 ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
    //                             'factor' => '+',
    //                             'value' => $hari_kerja_biasa * $nominal_hari_biasa,
    //                             'type' => 'BULANAN',
    //                             'can_adjust' => 1
    //                         ];
    //                     }

    //                     // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
    //                     if ($hari_kerja_merah > 0) {
    //                         $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
    //                         $defaultColumns[] = [
    //                             'label' => 'Gaji Pokok Hari Merah - ' . $hari_kerja_merah .
    //                                 ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
    //                             'factor' => '+',
    //                             'value' => $hari_kerja_merah * $nominal_hari_merah,
    //                             'type' => 'BULANAN',
    //                             'can_adjust' => 1
    //                         ];
    //                     }

    //                     continue;
    //                 }
    //                 if ($keterangan === "uang makan siang") {
    //                     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
    //                         $presensi['day'] = $t_cuti_approved;
    //                     }
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
    //                         'factor' => '+',
    //                         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === "uang makan malam") {
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
    //                         'factor' => '+',
    //                         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }

    //                 if ($keterangan === "uang kerajinan") {
    //                     $maxKerajinan = 5;
    //                     $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
    //                     $hadir = max(0, min($hadir, $maxKerajinan));
    //                     $percentage = $hadir / $maxKerajinan;
    //                     $nominalKerajinan = $percentage * (float) @$d->nominal;
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan,
    //                         'factor' => '+',
    //                         'value' => $nominalKerajinan,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === 'uang lembur') {
    //                     $overtimeMinute = $presensiMinute['overtime_minute'];
    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
    //                         'factor' => '+',
    //                         'value' => @$d->nominal * $overtimeMinute ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }
    //                 if ($keterangan === 'uang lembur hr merah') {

    //                     if ($presensi['kerja_di_hari_libur_count'] > 0) {
    //                         $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
    //                     } else {
    //                         $overtimeOnHoliday = 0;
    //                     }

    //                     $defaultColumns[] = [
    //                         'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
    //                         'factor' => '+',
    //                         'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
    //                         'type' => 'BULANAN',
    //                         'can_adjust' => 1
    //                     ];
    //                     continue;
    //                 }

    //                 $defaultColumns[] = [
    //                     'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
    //                     'factor' => '+',
    //                     'value' => $value * (float) @$d->nominal ?? 0,
    //                     'type' => 'BULANAN',
    //                     'can_adjust' => 1
    //                 ];
    //             }
    //         }

    //         // gaji perhari
    //         $gaji_per_hari = $gaji_karyawan;
    //         $makan_per_hari = 0;


    //         // potongan tidak hadir dan jatah semua cuti sudah habis
    //         // if(($sisa_cuti-$tidak_masuk_kerja) < 0){
    //         //     $sisa_cuti -= $tidak_masuk_kerja;
    //         //     $value = $gaji_per_hari*$tidak_masuk_kerja;
    //         //     $defaultColumns[] = [
    //         //         'label'    => "Potongan Tidak Masuk Kerja ($tidak_masuk_kerja)",
    //         //         'factor'   => '-',
    //         //         'value'    => $value,
    //         //         'type'     => 'HARIAN',
    //         //         'can_adjust' => 1
    //         //     ];
    //         // }

    //         // ketika jatah cuti reguler tidak ada -> potong gaji 
    //         // if ($sisa_cuti <= 0 && $potongan_cuti > 0) {
    //         //     $value = $gaji_per_hari * $potongan_cuti;
    //         //     $defaultColumns[] = [
    //         //         'label' => "Potongan Cuti ($potongan_cuti)",
    //         //         'factor' => '-',
    //         //         'value' => $value,
    //         //         'type' => 'Bulanan',
    //         //         'can_adjust' => 1
    //         //     ];
    //         // }
    //     }

    //     if ($presensiMinute['missing_check'] != 0) {
    //         $missingCheck = $presensiMinute['missing_check'] ?? 0;
    //         $defaultColumns[] = [
    //             'label' => "Denda Absensi",
    //             'factor' => '-',
    //             'value' => $missingCheck * 5000,
    //             'type' => 'Bulanan',
    //             'can_adjust' => 1
    //         ];
    //     }

    //     // if ($diff != 0) {
    //     //     $getLembur = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
    //     //         ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
    //     //         ->where('m_kary_id', @$kary->id ?? 0)
    //     //         ->whereRaw('LOWER(d.keterangan) = LOWER(?)', ['Uang Lembur'])
    //     //         ->where('t_kary_salary.is_active', true)
    //     //         ->first();
    //     //     $totalOvertime = $diff * @$getLembur['nominal'] ?? 0;
    //     //     $defaultColumns[] = [
    //     //         'label' => 'Uang Lembur - ' . (float) $diff . ' Jam',
    //     //         'factor' => '+',
    //     //         'value' => (float) $totalOvertime ?? 0,
    //     //         'type' => 'BULANAN',
    //     //         'can_adjust' => 1,
    //     //     ];
    //     // }




    //     // faktor lain :Cuti
    //     // $t_cuti = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->get();
    //     // if (count($t_cuti)) {
    //     //     $sisa_cuti = m_kary::where('id', @$kary->id ?? 0)->pluck('cuti_sisa_reguler')->first() ?? 0;
    //     //     $count = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("attachment is not null and status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->count();
    //     //     foreach ($t_cuti as $d) {
    //     //         $date_from = \DateTime::createFromFormat('Y-m-d', $d->date_from);
    //     //         $date_to = \DateTime::createFromFormat('Y-m-d', $d->date_to);
    //     //         $interval = @$date_from->diff($date_to) ?? 0;
    //     //         $jumlah_hari = $interval->days;
    //     //         if ($sisa_cuti > 0) {
    //     //             $jumlah_hari = $jumlah_hari - $sisa_cuti;
    //     //         }

    //     //         $gaji_per_hari = $gaji_karyawan;
    //     //         $makan_per_hari = 0;
    //     //         $potongan_cuti = $gaji_per_hari * $jumlah_hari;
    //     //         $potongan_makan = $makan_per_hari * $jumlah_hari;

    //     //         // if($count > 7){
    //     //         $defaultColumns[] = [
    //     //             'label' => "Potongan Cuti ($jumlah_hari)",
    //     //             'factor' => '-',
    //     //             'value' => $potongan_cuti,
    //     //             'type' => 'Bulanan',
    //     //             'can_adjust' => 1,
    //     //             't_cuti_id' => $d->id
    //     //         ];

    //     //         // }else{
    //     //         //     $defaultColumns[] = [
    //     //         //         'label'    => "Potongan Cuti (Uang Makan) ($jumlah_hari)",
    //     //         //         'factor'   => '-',
    //     //         //         'value'    => $potongan_cuti,
    //     //         //         'type'     => 'Bulanan',
    //     //         //         'can_adjust' => 1,
    //     //         //         't_cuti_id' => $d->id
    //     //         //     ];
    //     //         // }

    //     //     }
    //     // }


    //     return $defaultColumns;
    // }


    public function factorSalaryManual($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        //logic kuontol
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);

        // $notCheckIn = $treatments->where('not_checkin', true);
        // $potonganGrade = $treatments->where('keterangan', 'like', '%potongan%');
        // $needOvertime = $treatments->where('need_overtime', true);
        // $fullweek75 = $treatments->where('is_7_5', true)->where('full_week', true);
        // $sundayCheckIn = $treatments->Where('day', "0")->where('full_week', false)->first();
        // $sundayCheckInFullWeek = $treatments->Where('day', "0")->where('full_week', true)->first();

        // $pluckKeterangan = $needOvertime->pluck('keterangan')->toArray();

        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;
        // $setQuery =  m_general::where('group', 'SET-TUNJANGAN')->get();  
        // $setName = $setQuery->firstWhere('key', 'NAMA')->value;

        // $name = strtolower(@$setName) ?? "uang bulanan";

        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            // ->whereRaw("d.keterangan NOT ILIKE '%lembur%'")
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");
        // ->get();               
        // if ($needOvertime->isNotEmpty()) {
        //     $t_kary_salary = $t_kary_salary->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $pluckKeterangan);
        // }

        // Tanggal awal dan akhir
        // $dateFrom = new \DateTime($date_from);
        // $dateTo = new \DateTime($date_to);

        // // Hitung selisihnya
        // $interval = $dateFrom->diff($dateTo);

        // // Ambil total hari
        // $totalDays = $interval->days;

        //tunjangan
        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {

                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        // $dataResults = $t_kary_salary->get();
        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // $gaji_karyawan = @$t_kary_salary[0]->total;

        $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

        // faktor lain :Potongan

        $sisa = 0;

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();

        // if($t_potongan->count())
        // {
        //     foreach ($t_potongan as $d)
        //     {
        //          $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];
        //     }
        // }

        // if ($t_potongan->count()) {
        //     foreach ($t_potongan as $key => $d) {
        //         $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             ->where('is_active', true)
        //             // ->sum('total_hutang') ?? 0;
        //              ->sum('total_hutang') ?? 0;
                
        //         if($hutang > 0){
        //             // $paid = $d->t_final_gaji_det_rincian?->sum('value') ?? 0;
        //             $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
        //             ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
        //             ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
        //             ->sum('t_final_gaji_det_rincian.value');

                    
        //             $sisa = max($hutang - $paid, 0);

        //             if ($d->percentage) {
        //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
        //             } else {
        //                 $nilai_netto = (float) $d->nilai;
        //             }

        //             if ($sisa > 0) {
        //                 if ($nilai_netto > $sisa) {
        //                     $nilai_netto = $sisa;
        //                 }
        //             } else {
        //                 $nilai_netto = 0;
        //             }

        //             // dd($hutang, $paid, $nilai_netto);


        //             if($nilai_netto > 0){
        //                 $defaultColumns[] = [
        //                     'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                     'factor' => '-',
        //                     'value' => $nilai_netto,
        //                     'type' => 'BULANAN',
        //                     'can_adjust' => 1,
        //                     't_potongan_id' => $d->id,
        //                 ];
        //             }
        //             //dd($hutang,$t_potongan->count(), $d, $defaultColumns, $paid);

        //             // if ($key == 1) {
        //             //     //  $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             //     //     ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             //     //     ->where('is_active', true)
        //             //     //     ->get();
        //             //         // ->sum('total_hutang') ?? 0;
        //             //         //->sum('total_hutang') ?? 0;
        //             //     dd([
        //             //         'index' => $key,
        //             //         'jenis_potongan' => $d->jenis_potongan_id,
        //             //         'hutang' => $hutang,
        //             //         'paid' => $paid ,
        //             //         'nilai_netto' => $nilai_netto,
        //             //         'data_potongan' => $d
        //             //     ]);
        //             // }


        //         }else{
        //             if($d->nilai > 0){
        //             $defaultColumns[] = [
        //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                 'factor' => '-',
        //                 'value' => $d->nilai,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1,
        //                 't_potongan_id' => $d->id,
        //             ];
        //             }
                
        //         }

        //     }
        // }

        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }

        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        //check data presensi
        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);


        // if ($presensi['sunday_not_checkin'] > 0) {
        //     if ($notCheckIn) {
        //         foreach ($notCheckIn as $single) {
        //             $presensi['count'] += $single['value'];
        //         }
        //     }
        // }

        //cek telat kerja
        if ($presensiMinute['late_count'] > 0) {
            $lateCount = $presensiMinute['late_count'];
            $lateValue = 15000;
            $defaultColumns[] = [
                'label' => "Denda Telat Kerja " . $lateCount . " Kali",
                'factor' => '-',
                'value' => $lateCount * $lateValue,
                'type' => 'BULANAN',
                'can_adjust' => 1
            ];
        }

        //cek lebih dari jadwal menit istirahat
        if ($presensiMinute['over_break_penalty_per_day'] > 0) {
            foreach ($presensiMinute['over_break_penalty_per_day'] as $key => $value) {
                if ($value > 0) {
                    $defaultColumns[] = [
                        'label' => "Denda Istirahat Lebih Dari Waktu Istirahat - Hari Ke-$key",
                        'factor' => '-',
                        'value' => $value,
                        'type' => 'BULANAN',
                        'can_adjust' => 1
                    ];
                }
            }
        }

        //cek data lembur
        // $getOvertime = t_lembur::where('m_kary_id', @$kary->id)
        //     ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
        //     ->join('m_kary as mk', 'm_kary_id', 'mk.id')
        //     // ->join('m_posisi as mp','mk.m_posisi_id','mp.id')
        //     ->select('t_lembur.*')
        //     ->where('status', 'APPROVED')->get();

        // $diff = 0;
        // $needOvertimeCount = 0;
        // $needOvertimeHour = $needOvertime->pluck('value_overtime')->first();
        // if ($getOvertime->isNotEmpty()) {
        //     foreach ($getOvertime as $single) {
        //         $startOvertime = \Carbon::parse($single['tanggal'] . '' . $single['jam_mulai']);
        //         $endOvertime = \Carbon::parse($single['tanggal'] . '' . $single['jam_selesai']);
        //         if ($single['jam_mulai'] >= $single['jam_selesai'])
        //             $endOvertime->addDay();
        //         $diff += $startOvertime->diffInHours($endOvertime);
        //         $overtimeTunjangan = $startOvertime->diffInHours($endOvertime);
        //         if ($overtimeTunjangan >= $needOvertimeHour) {
        //             $needOvertimeCount = $needOvertimeCount + 1;
        //         }
        //     }
        // }


        // if ($needOvertimeCount > 0) {
        //     if ($needOvertime) {
        //         $getNeedOvertime = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
        //             ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
        //             ->where('m_kary_id', @$kary->id ?? 0)
        //             ->whereIn(\DB::raw('LOWER(d.keterangan)'), $pluckKeterangan)
        //             ->where('t_kary_salary.is_active', true)
        //             ->get();

        //         if ($getNeedOvertime) {
        //             foreach ($getNeedOvertime as $single) {
        //                 $defaultColumns[] = [
        //                     'label' => $single['keterangan'],
        //                     'factor' => '+',
        //                     'value' => $needOvertimeCount * $single['nominal'],
        //                     'type' => 'BULANAN',
        //                     'can_adjust' => 1
        //                 ];
        //             }
        //         }
        //     }
        // }

        // check kehadiran karyawan
        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $jml_hari_sebulan = $att->work_days_in_month;
            $jml_hari_terpilih = $att->work_day_in_week;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
            $libur_nasional = @$att->libur_nasional;
            $cuti_satu_hari = @$att->cuti_satu_hari;

            $total_gaji_libur_nasional = 0;
            $total_7_5_fullweek = 0;
            $totalSundayCheckin = 0;
            $totalSundayFullweek = 0;

            // if ((float) $libur_nasional > 0) {
            //     $getLiburNasional = $treatments->where('big_event', true)->first();
            //     if (isset($presensi['libur_nasional']) && $presensi['libur_nasional'] > 0) {
            //         $total_gaji_libur_nasional = $presensi['libur_nasional'] * $getLiburNasional['value'];
            //     }
            // }

            // if ($presensi['saturday_7_5_fullweek'] > 0) {
            //     if ($fullweek75 && !$fullweek75->isEmpty()) {
            //         foreach ($fullweek75 as $single) {
            //             $total_7_5_fullweek += $single['value'] * $presensi['saturday_7_5_fullweek'];
            //         }
            //     }
            // }

            // if ($presensi['sunday'] > 0) {
            //     if ($sundayCheckIn) {
            //         $totalSundayCheckin += $sundayCheckIn['value'] * $presensi['sunday'];
            //     }
            // }

            // if ($presensi['countFullWeek'] > 0) {
            //     if ($sundayCheckInFullWeek) {
            //         $totalSundayFullweek += $sundayCheckInFullWeek['value'] * $presensi['sunday'];
            //     }
            // }

            $gaji_pokok_hari = 0;
            $countOfHariLiburCheckin = 0;


            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // Hitung hari kerja biasa dan merah
                        $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                            (float) $presensiMinute['work_minute'] :
                            (float) $presensi['count'];

                        $gaji_menit = $d->tipe_perhitungan == "MENIT" ?
                            (float) $d->nominal :
                            (float) $d->nominal / 8 / 60;

                        //denda pulang cepat
                        if ($presensiMinute['leave_early'] > 0) {
                            $defaultColumns[] = [
                                'label' => "Denda Pulang Cepat - " . $presensiMinute['leave_early'] . " Menit",
                                'factor' => '-',
                                'value' => $presensiMinute['leave_early'] * $gaji_menit,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung hari kerja merah (minggu + hari libur nasional)
                        // $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);
                        $hari_kerja_merah = ($presensi['kerja_di_hari_libur_count'] ?? 0);

                        // Hitung hari kerja biasa (total - hari merah)
                        $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);
                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        // if (@$kary->id == 915) {
                        //     dump([
                        //         't_cuti_approved' => $t_cuti_approved,
                        //         'total_hari_kerja' => $total_hari_kerja,
                        //         'hari_kerja_merah' => $hari_kerja_merah,
                        //         'hari_kerja_biasa' => $hari_kerja_biasa,
                        //         'presensi_count' => $presensi['count'],
                        //         'kerja_di_hari_libur_count' => $presensi['kerja_di_hari_libur_count'] ?? 0,
                        //         'sunday' => $presensi['sunday'] ?? 0
                        //     ]);
                        // }
                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }

                        // Hitung dan tambahkan gaji untuk hari kerja biasa
                        if ($hari_kerja_biasa > 0) {
                            $nominal_hari_biasa = (float) @$d->nominal ?? 0;

                            $total_hari_gapok = $presensi['count'] + $t_cuti_approved;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Biasa - ' . $total_hari_gapok . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $total_hari_gapok * $nominal_hari_biasa,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
                        if ($hari_kerja_merah > 0) {
                            $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Merah - ' . $presensi['kerja_di_hari_libur_count'] .
                                    ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $hari_kerja_merah * $nominal_hari_merah,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        continue;
                    }
                    if ($keterangan === "uang makan siang") {
                        // if (@$kary->id == 915) {
                        //     dump([
                        //         'wang makan siang' => $presensi['day'] ?? 0
                        //     ]);
                        // }
                        if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
                            $presensi['day'] = $t_cuti_approved;
                        }
                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
                            'factor' => '+',
                            'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    if ($keterangan === "uang makan malam") {
                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
                            'factor' => '+',
                            'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    if ($keterangan === "uang kerajinan") {
                        $maxKerajinan = 5;
                        $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
                        $hadir = max(0, min($hadir, $maxKerajinan));
                        $percentage = $hadir / $maxKerajinan;
                        $nominalKerajinan = $percentage * (float) @$d->nominal;
                        $defaultColumns[] = [
                            'label' => $d->keterangan,
                            'factor' => '+',
                            'value' => $nominalKerajinan,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    if ($keterangan === 'uang lembur') {
                        //$overtimeMinute = $presensiMinute['overtime_minute'];
                        $overtimeMinute = $presensi['lembur_kerja_all'] - $presensi['lembur_kerja_di_hari_libur'];

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeMinute ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    if ($keterangan === 'uang lembur hr merah') {

                        if ($presensi['kerja_di_hari_libur_count'] > 0) {
                            $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                        } else {
                            $overtimeOnHoliday = 0;
                        }

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    if ($keterangan === 'uang transport') {
                        $totalUangTransport = $presensi['count'] + $presensi['kerja_di_hari_libur_count'] ?? 0;
                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $totalUangTransport . ' Hari Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $totalUangTransport ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    $defaultColumns[] = [
                        'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                        'factor' => '+',
                        'value' => $value * (float) @$d->nominal ?? 0,
                        'type' => 'BULANAN',
                        'can_adjust' => 1
                    ];
                }
            }

            // gaji perhari
            $gaji_per_hari = $gaji_karyawan;
            $makan_per_hari = 0;


            // potongan tidak hadir dan jatah semua cuti sudah habis
            // if(($sisa_cuti-$tidak_masuk_kerja) < 0){
            //     $sisa_cuti -= $tidak_masuk_kerja;
            //     $value = $gaji_per_hari*$tidak_masuk_kerja;
            //     $defaultColumns[] = [
            //         'label'    => "Potongan Tidak Masuk Kerja ($tidak_masuk_kerja)",
            //         'factor'   => '-',
            //         'value'    => $value,
            //         'type'     => 'HARIAN',
            //         'can_adjust' => 1
            //     ];
            // }

            // ketika jatah cuti reguler tidak ada -> potong gaji 
            // if ($sisa_cuti <= 0 && $potongan_cuti > 0) {
            //     $value = $gaji_per_hari * $potongan_cuti;
            //     $defaultColumns[] = [
            //         'label' => "Potongan Cuti ($potongan_cuti)",
            //         'factor' => '-',
            //         'value' => $value,
            //         'type' => 'Bulanan',
            //         'can_adjust' => 1
            //     ];
            // }
        }

        if ($presensiMinute['missing_check'] != 0) {
            $missingCheck = $presensiMinute['missing_check'] ?? 0;
            $defaultColumns[] = [
                'label' => "Denda Absensi",
                'factor' => '-',
                'value' => $missingCheck * 5000,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        // dd($presensiMinute);

        // if ($diff != 0) {
        //     $getLembur = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
        //         ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
        //         ->where('m_kary_id', @$kary->id ?? 0)
        //         ->whereRaw('LOWER(d.keterangan) = LOWER(?)', ['Uang Lembur'])
        //         ->where('t_kary_salary.is_active', true)
        //         ->first();
        //     $totalOvertime = $diff * @$getLembur['nominal'] ?? 0;
        //     $defaultColumns[] = [
        //         'label' => 'Uang Lembur - ' . (float) $diff . ' Jam',
        //         'factor' => '+',
        //         'value' => (float) $totalOvertime ?? 0,
        //         'type' => 'BULANAN',
        //         'can_adjust' => 1,
        //     ];
        // }




        // faktor lain :Cuti
        // $t_cuti = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->get();
        // if (count($t_cuti)) {
        //     $sisa_cuti = m_kary::where('id', @$kary->id ?? 0)->pluck('cuti_sisa_reguler')->first() ?? 0;
        //     $count = t_cuti::where('m_kary_id', @$kary->id ?? 0)->whereRaw("attachment is not null and status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])->count();
        //     foreach ($t_cuti as $d) {
        //         $date_from = \DateTime::createFromFormat('Y-m-d', $d->date_from);
        //         $date_to = \DateTime::createFromFormat('Y-m-d', $d->date_to);
        //         $interval = @$date_from->diff($date_to) ?? 0;
        //         $jumlah_hari = $interval->days;
        //         if ($sisa_cuti > 0) {
        //             $jumlah_hari = $jumlah_hari - $sisa_cuti;
        //         }

        //         $gaji_per_hari = $gaji_karyawan;
        //         $makan_per_hari = 0;
        //         $potongan_cuti = $gaji_per_hari * $jumlah_hari;
        //         $potongan_makan = $makan_per_hari * $jumlah_hari;

        //         // if($count > 7){
        //         $defaultColumns[] = [
        //             'label' => "Potongan Cuti ($jumlah_hari)",
        //             'factor' => '-',
        //             'value' => $potongan_cuti,
        //             'type' => 'Bulanan',
        //             'can_adjust' => 1,
        //             't_cuti_id' => $d->id
        //         ];

        //         // }else{
        //         //     $defaultColumns[] = [
        //         //         'label'    => "Potongan Cuti (Uang Makan) ($jumlah_hari)",
        //         //         'factor'   => '-',
        //         //         'value'    => $potongan_cuti,
        //         //         'type'     => 'Bulanan',
        //         //         'can_adjust' => 1,
        //         //         't_cuti_id' => $d->id
        //         //     ];
        //         // }
        //     }
        // }


        return $defaultColumns;
    }

    public function summarySubSalary($arrConfig)
    {
        return array_reduce($arrConfig, function ($carry, $item) {
            if (is_numeric($item['value'])) {
                $value = (float) $item['value'];
                if ($value != 0) {
                    if ($item['factor'] == '+') {
                        $carry = $carry + $item['value'];
                    } elseif ($item['factor'] == '-') {
                        $carry = $carry - $item['value'];
                    }
                }
            }
            return $carry;
        }, 0);
    }

    public function countPPH21($kary, $netto = 0)
    {
        $getBasicSalary = [];
        // pengurangan dari perhitungan pph21
        // ------------------------- contoh perhitungan ---------------------------
        // Penghasilan Neto dalam setahun Rp9.400.000 x 12	    = Rp112.800.000
        // PTKP Status Lajang	                                = Rp54.000.000 (-)
        // Pendapatan Kena Pajak (PKP):	
        // PKP setahun Rp112.800.000 – Rp54.000.000	            = Rp58.800.000

        $tanggungan = m_general::find($kary->tanggungan_id);
        if ($tanggungan) {

            // persentase pajak <= Rp50.000.000                 = 5%
            // persentase pajak > Rp50.000.000  – Rp250.000.000 = 15%
            // persentase pajak > Rp250.000.000 – Rp500.000.000 = 25%
            // persentase pajak > Rp250.000.000 – Rp500.000.000 = 30%

            $nilaiTanggungan = @$tanggungan->value_2 ?? 0;
            $nettoYear = $netto * 12;
            $nettoPTKP = $nettoYear - $nilaiTanggungan;

            // hentikan fungsi ketika gaji masih dibawah jumlah tanggungan 
            if ($nettoPTKP <= 0)
                return $getBasicSalary;

            $percent = 0;
            if ($nettoPTKP <= 50000000) {
                $before_value = 0;
                $before_percent = $percent;
                $percent = 5;
            } elseif (
                $nettoPTKP > 50000000
                && $nettoPTKP <= 250000000
            ) {
                $before_value = 50000000;
                $before_percent = $percent;
                $percent = 15;
            } elseif ($nettoPTKP > 250000000 && $nettoPTKP <= 500000000) {
                $before_value = 250000000;
                $before_percent = $percent;
                $percent = 25;
            } elseif ($nettoPTKP > 500000000) {
                $before_value = 500000000;
                $before_percent = $percent;
                $percent = 30;
            }
            $getBasicSalary = $this->countTaxDetail(
                $tanggungan,
                $nettoPTKP,
                $before_value,
                $before_percent,
                $percent,
                $getBasicSalary
            );
        }
        return $getBasicSalary;
    }

    private function countTaxDetail(
        $tanggungan,
        $nettoPTKP,
        $before_value,
        $before_percent,
        $percent,
        $mergingArr
    ) {
        $outstanding = $nettoPTKP - $before_value;
        $tax1 = $before_percent * $before_value / 100;
        $tax2 = $percent * $outstanding / 100;
        $total_tax = $tax1 + $tax2;
        // insert dari kondisi gaji sebelumnya sebelumnya
        // ex: 5% x 50.000.000
        // ex: 15% x 800.0000
        $detail = [];
        if ($before_percent != 0) {
            // jika netto / before value memiliki sisa
            $detail = [
                [
                    'label' => "$before_percent% x $before_value",
                    'factor' => '+',
                    'value' => $tax1,
                    'type' => 'Tahunan'
                ],
                [
                    'label' => "$percent% x $outstanding",
                    'factor' => '+',
                    'value' => $tax2,
                    'type' => 'Tahunan'
                ]
            ];
        } else {
            // jika netto / before value tidak memiliki sisa (konidisi pertama)
            $detail = [
                [
                    'label' => "$percent% x $nettoPTKP",
                    'factor' => '+',
                    'value' => $tax2,
                    'type' => 'Tahunan',
                ]
            ];
        }

        $mergingArr[] = [
            'label' => "PTKP $tanggungan->value (perbulan)",
            'factor' => '-',
            'value' => $total_tax / 12,
            'type' => 'Bulanan',
            'can_adjust' => 0,
            'detail' => $detail
        ];
        return $mergingArr;
    }

    public function salaryOfKary($id, $periode = null)
    {
        try {
            $m_kary_id = $id;
            $kary = m_kary::find($m_kary_id);

            // check standart gaji karyawan
            if (!@$kary->m_standart_gaji_id)
                return [
                    'm_kary_id' => $m_kary_id,
                    'total_gaji' => 0,
                    'total_tax' => 0,
                    'netto' => 0,
                    'detail' => []
                ];
            $m_standart_gaji = m_standart_gaji::find($kary->m_standart_gaji_id);

            // default summary salary
            $getBasicSalary = $this->factorSalary($m_standart_gaji, $kary, $periode);
            $netto = $this->summarySubSalary($getBasicSalary);
            $getBasicSalary = array_merge($getBasicSalary, [
                [
                    'label' => 'Total Gaji',
                    'factor' => '=',
                    'value' => $netto,
                    'type' => '-'
                ]
            ]);

            $nettoFinish = $this->summarySubSalary($getBasicSalary);

            // default summary tax
            $arrPPH = $this->countPPH21($kary, $netto);
            $totalTax = @$arrPPH[0]['value'];
            if (count($arrPPH)) {
                $getBasicSalary = array_merge($getBasicSalary, $arrPPH);
                $nettoFinish = $this->summarySubSalary($getBasicSalary);
                $getBasicSalary = array_merge($getBasicSalary, [
                    [
                        'label' => 'Total Gaji (Setelah PPH 21)',
                        'factor' => '=',
                        'value' => $nettoFinish,
                        'type' => '-'
                    ]
                ]);
            }

            return [
                'm_kary_id' => $m_kary_id,
                'total_gaji' => $netto,
                'total_tax' => $totalTax,
                'netto' => $nettoFinish,
                'detail' => $getBasicSalary
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // untuk perhitungan gaji manual tanpa standar gaji
    public function salaryOfKaryManual($id, $date_from, $date_to, $isTunjangan)
    {
        try {
            $m_kary_id = $id;
            $kary = m_kary::find($m_kary_id);
            //$upah_packing = $this->importUpahErp($date_from, $date_to, $kary);
            //dd($upah_packing);

            $grade = @$kary->grading_id ?? 20;
            $gradeKary = m_general::where('id', $grade)->first();
            $getBasicSalary = match ($gradeKary->code) {
                '1A' => $this->factorSalaryManual($kary, $date_from, $date_to, $isTunjangan, $grade),
                '1B' => $this->factorSalaryJagaMalam($kary, $date_from, $date_to, $isTunjangan, $grade),
                '1C' => $this->factorSalaryArt($kary, $date_from, $date_to, $isTunjangan, $grade),
                '1D' => $this->factorSalaryOutsourceAdminMandor($kary, $date_from, $date_to, $isTunjangan, $grade),
                '1E' => $this->factorSalaryOutsourceKary($kary, $date_from, $date_to, $isTunjangan, $grade),
                '1F' => $this->factorSalaryOutsourceDriver($kary, $date_from, $date_to, $isTunjangan, $grade),
                '1G' => $this->factorSalaryPersonalDriver($kary, $date_from, $date_to, $isTunjangan, $grade),
                default => $this->factorSalaryManual($kary, $date_from, $date_to, $isTunjangan, $grade)
            };

            // default summary salary
            // $getBasicSalary = $this->factorSalaryManual($kary, $date_from, $date_to, $isTunjangan, $grade);
            $netto = $this->summarySubSalary($getBasicSalary);
            $getBasicSalary = array_merge($getBasicSalary, [
                [
                    'label' => 'Total Gaji',
                    'factor' => '=',
                    'value' => $netto,
                    'type' => '-'
                ]
            ]);
            
            // $nettoFinish = $this->summarySubSalary($getBasicSalary);

            // // default summary tax
            // $arrPPH = $this->countPPH21($kary, $netto);
            // $totalTax = @$arrPPH[0]['value'];
            // if (count($arrPPH)) {
            //     $getBasicSalary = array_merge($getBasicSalary, $arrPPH);
            //     $nettoFinish = $this->summarySubSalary($getBasicSalary);
            //     $getBasicSalary = array_merge($getBasicSalary, [
            //         [
            //             'label' => 'Total Gaji (Setelah PPH 21)',
            //             'factor' => '=',
            //             'value' => $nettoFinish,
            //             'type' => '-'
            //         ]
            //     ]);
            // }

            return [
                'm_kary_id' => $m_kary_id,
                'total_gaji' => $netto,
                'total_tax' => 0,
                'netto' => $netto,
                'detail' => $getBasicSalary
            ];
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return $e->getMessage();
        }
    }

    //  // gaji manual -> tanpa master standar gaji
    //     $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, d.*")->join('t_kary_salary_det as d','d.t_kary_salary_id','t_kary_salary.id')
    //                         ->where('m_kary_id', @$kary->id ?? 0)
    //                         ->where('t_kary_salary.is_active', true)
    //                         ->get();

    //      foreach($standart_gaji_det as $d){
    //         $defaultColumns[] = [
    //             'label'    => $d->keterangan,
    //             'factor'   => '+',
    //             'value'    => $d->,
    //             'type'     => $d->periode,
    //             'can_adjust' => 1
    //         ];
    //     }

    // private function salaryPresensi($karyId, $date_from, $date_to)
    // {
    //     $getUserId = default_users::where('m_kary_id', $karyId)->first()->id ?? 0;
    //     $noRecordDate = $this->noRecordDate($date_from, $date_to, $getUserId);

    //     $getPresensi = presensi_absensi::where('default_user_id', $getUserId)
    //         ->where('presensi_absensi.status', 'ATTEND')
    //         ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to]);

    //     $getLiburKary = t_libur::where('tanggal_mulai', '>=', $date_from)
    //         ->where('tanggal_akhir', '<=', $date_to)->whereHas('t_libur_d', function ($query) use ($karyId) {
    //             $query->where('m_kary_id', $karyId);
    //         })->get();

    //     $liburDates = collect();
    //     foreach ($getLiburKary as $libur) {
    //         $start = \Carbon::parse($libur->tanggal_mulai);
    //         $end = \Carbon::parse($libur->tanggal_akhir);
    //         while ($start->lte($end)) {
    //             $liburDates->push($start->toDateString());
    //             $start->addDay();
    //         }
    //     }

    //     $sundayDates = collect();
    //     $start = \Carbon::parse($date_from);
    //     $end = \Carbon::parse($date_to);
    //     while ($start->lte($end)) {
    //         if ($start->dayOfWeek === 0) {
    //             $sundayDates->push($start->toDateString());
    //         }
    //         $start->addDay();
    //     }

    //     $getLiburNasional = m_libur_nasional::whereBetween('tanggal', [$date_from, $date_to])
    //         ->where('is_active', true)
    //         ->pluck('tanggal')
    //         ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString());

    //     $getLiburNasionalAndSunday = $getLiburNasional->merge($sundayDates)->unique()->values();

    //     $allLiburDates = $liburDates
    //         ->merge($getLiburNasional)
    //         ->merge($sundayDates)
    //         ->unique()
    //         ->values();

    //     $noRecordWithLiburDates = array_values(array_intersect($allLiburDates->toArray(), $noRecordDate));

    //     $countRecordWithLiburDates = count($noRecordWithLiburDates);
    //     $countNoRecordDate = count($noRecordDate);
    //     $countTotalNoRecord = $countNoRecordDate - $countRecordWithLiburDates;


    //     $liburNasional = clone $getPresensi;
    //     $collectLiburNasional = collect($getLiburNasional)->pluck('tanggal')->map(fn($date) => \Carbon::parse($date)->toDateString());
    //     $liburNasional = collect($liburNasional->get('tanggal'))->pluck('tanggal')->map(fn($date) => \Carbon::parse($date)->toDateString());
    //     $totalLiburNasional = $collectLiburNasional->intersect($liburNasional);
    //     $countLiburNasionalCheckIn = $totalLiburNasional->count();
    //     $liburNasionalCount = $collectLiburNasional->count();
    //     // $countLiburNasionalNotCheckin = $liburNasionalCount - $countLiburNasionalCheckIn;

    //     // $shift = clone $getPresensi;
    //     // $shift = $shift->whereRaw("shift ~* ?", ['shift [23]'])->count();

    //     $count = clone $getPresensi;
    //     $count = $count->whereRaw("EXTRACT(DOW FROM tanggal) != 0")->count();

    //     // Uang makan siang: checkin pagi (05:00–12:00)
    //     $makanSiangPresensi = clone $getPresensi;
    //     $makanSiangDates = $makanSiangPresensi
    //         ->where('checkin_time', '>=', '05:00:00')
    //         ->where('checkin_time', '<=', '12:00:00')
    //         ->pluck('tanggal')
    //         ->unique()
    //         ->toArray();

    //     // Uang makan malam: checkout >= 19:30
    //     $makanMalamPresensi = clone $getPresensi;
    //     $makanMalamDates = $makanMalamPresensi
    //         ->where('checkout_time', '>=', '19:30:00')
    //         ->pluck('tanggal')
    //         ->unique()
    //         ->toArray();

    //     // Hasil
    //     $day = count($makanSiangDates);
    //     $night = count($makanMalamDates);


    //     // $longShiftDates = clone $getPresensi;
    //     // $longShiftDates = $longShiftDates
    //     //     ->where('checkin_time', '>=', '05:00:00')
    //     //     ->where('checkin_time', '<=', '12:00:00')
    //     //     ->where(function ($query) {
    //     //         $query->whereRaw("checkout_time >= ?", ["19:30:00"])
    //     //             ->orWhereRaw("checkout_time <= ?", ["05:00:00"]);
    //     //     })
    //     //     ->pluck('tanggal')
    //     //     ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
    //     //     ->toArray();

    //     // $longShiftCount = count($longShiftDates);
    //     // $day = $longShiftCount + $day;

    //     // $shift1Sat = clone $getPresensi;
    //     // $shift1Sat = $shift1Sat->whereRaw("EXTRACT(DOW FROM tanggal) = 6")->whereRaw("shift ~* ?", ['shift [1]'])->count();

    //     $sunday = clone $getPresensi;
    //     $sunday = $sunday->whereRaw("EXTRACT(DOW FROM tanggal) = 0")->count();

    //     $checkInArray = clone $getPresensi;
    //     $checkInArray = collect($checkInArray->get('tanggal')->toArray())->pluck('tanggal')->map(fn($date) => \Carbon::parse($date));
    //     $getFullWeek = $this->isFullWeek($checkInArray);

    //     $saturday = clone $getPresensi;
    //     $saturday = $saturday->whereRaw("EXTRACT(DOW FROM tanggal) = 6")->count();

    //     $saturday75 = clone $getPresensi;
    //     $saturday75 = $saturday75->whereRaw("EXTRACT(DOW FROM tanggal) = 6")->get();
    //     $get75 = $this->is75($saturday75);

    //     $collectFullweek = collect($getFullWeek)->where('hasMondayToSaturday', true);
    //     $saturday_7_5_fullweek = $this->saturday_7_5_fullweek($collectFullweek, $get75, $saturday);

    //     // Cari tanggal presensi yang juga libur (nasional & cuti bersama)
    //     $tanggalPresensi = presensi_absensi::where('default_user_id', $getUserId)
    //         ->where('presensi_absensi.status', 'ATTEND')
    //         ->whereRaw('tanggal >= ? and tanggal <= ?', [$date_from, $date_to])
    //         ->pluck('tanggal')
    //         ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
    //         ->toArray();
    //     $kerjaDiHariLibur = array_values(array_intersect($tanggalPresensi, $getLiburNasionalAndSunday->toArray()));
    //     $countKerjaDiHariLibur = count($kerjaDiHariLibur);

    //     $checkOvertimeHariLibur = $this->checkOvertimeHariLibur($getUserId, $kerjaDiHariLibur);
    //     return [
    //         "user_id" => $getUserId,
    //         "count" => $count,
    //         "libur_nasional" => $liburNasionalCount,
    //         "night" => $night,
    //         "day" => $day,
    //         "shift" => 0,
    //         "shift1_sat" => 0,
    //         "sunday" => $sunday,
    //         "sunday_not_checkin" => 0,
    //         "saturday" => 0,
    //         "libur_nasional_checkin" => $countLiburNasionalCheckIn,
    //         "libur_nasional_not_checkin" => 0,
    //         "saturday_7_5" => [],
    //         "getFullWeek" => [],
    //         "saturday_7_5_fullweek" => [],
    //         "countFullWeek" => 0,
    //         "kerja_di_hari_libur_count" => $countKerjaDiHariLibur,
    //         "kerja_di_hari_libur_tanggal" => $kerjaDiHariLibur,
    //         "lembur_kerja_di_hari_libur" => $checkOvertimeHariLibur,
    //         'count_no_record_date' => $countTotalNoRecord,
    //     ];
    // }

    private function salaryPresensi($karyId, $date_from, $date_to)
    {
        $getUserId = default_users::where('m_kary_id', $karyId)->first()->id ?? 0;
        $noRecordDate = $this->noRecordDate($date_from, $date_to, $getUserId);

        $getPresensi = presensi_absensi::where('default_user_id', $getUserId)
            ->where('presensi_absensi.status', 'ATTEND')
            ->whereBetween('tanggal', [$date_from, $date_to])
            ->distinct('tanggal') ;
            // ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to]);

        $getLiburKary = t_libur::where('tanggal_mulai', '>=', $date_from)
            ->where('tanggal_akhir', '<=', $date_to)->whereHas('t_libur_d', function ($query) use ($karyId) {
                $query->where('m_kary_id', $karyId);
            })->get();

        $liburDates = collect();
        foreach ($getLiburKary as $libur) {
            $start = \Carbon::parse($libur->tanggal_mulai);
            $end = \Carbon::parse($libur->tanggal_akhir);
            while ($start->lte($end)) {
                $liburDates->push($start->toDateString());
                $start->addDay();
            }
        }

        $sundayDates = collect();
        $start = \Carbon::parse($date_from);
        $end = \Carbon::parse($date_to);
        while ($start->lte($end)) {
            if ($start->dayOfWeek === 0) {
                $sundayDates->push($start->toDateString());
            }
            $start->addDay();
        }

        $getLiburNasional = m_libur_nasional::whereBetween('tanggal', [$date_from, $date_to])
            ->where('is_active', true)
            ->pluck('tanggal')
            ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString());

        $getLiburNasionalAndSunday = $getLiburNasional->merge($sundayDates)->unique()->values();

        $allLiburDates = $liburDates
            ->merge($getLiburNasional)
            ->merge($sundayDates)
            ->unique()
            ->values();

        $noRecordWithLiburDates = array_values(array_intersect($allLiburDates->toArray(), $noRecordDate));

        $countRecordWithLiburDates = count($noRecordWithLiburDates);
        $countNoRecordDate = count($noRecordDate);
        $countTotalNoRecord = $countNoRecordDate - $countRecordWithLiburDates;


        $liburNasional = clone $getPresensi;
        $collectLiburNasional = collect($getLiburNasional)->pluck('tanggal')->map(fn($date) => \Carbon::parse($date)->toDateString());
        $liburNasional = collect($liburNasional->get('tanggal'))->pluck('tanggal')->map(fn($date) => \Carbon::parse($date)->toDateString());
        $totalLiburNasional = $collectLiburNasional->intersect($liburNasional);
        $countLiburNasionalCheckIn = $totalLiburNasional->count();
        $liburNasionalCount = $collectLiburNasional->count();
        // $countLiburNasionalNotCheckin = $liburNasionalCount - $countLiburNasionalCheckIn;

        // $shift = clone $getPresensi;
        // $shift = $shift->whereRaw("shift ~* ?", ['shift [23]'])->count();

        $count = clone $getPresensi;
        $count = $count->count();

        // Uang makan siang: checkin pagi (05:00–12:00)
        $makanSiangPresensi = clone $getPresensi;
        $makanSiangDates = $makanSiangPresensi
            // ->where('checkin_time', '>=', '05:00:00')
            ->where('checkin_time', '<=', '12:00:00')
            ->pluck('tanggal')
            ->unique()
            ->toArray();
        // if (@$karyId == 915) {
        //         dump([
        //             'makan siang bro' => $makanSiangDates,
        //         ]);
        //     }


        $cutiDates = [];
        $t_cuti = t_cuti::where('m_kary_id', $karyId)
            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->get();

        foreach ($t_cuti as $cuti) {
            $startDate = \Carbon::parse($cuti->date_from);
            $endDate = \Carbon::parse($cuti->date_to);

            while ($startDate->lte($endDate)) {
                if ($startDate->dayOfWeek !== 0) { // Skip Sundays
                    $cutiDates[] = $startDate->format('Y-m-d');
                }
                $startDate->addDay();
            }
        }

        // Merge attendance and leave dates, ensure uniqueness
        $makanSiangDates = array_unique(array_merge($makanSiangDates, $cutiDates));


        // Uang makan malam: checkout >= 19:30
        $makanMalamPresensi = clone $getPresensi;
        $makanMalamDates = $makanMalamPresensi
            ->where(function ($query) {
                $query->where('checkout_time', '>=', '19:30:00')
                    ->orWhere(function ($q) {
                        $q->where('checkout_time', '>=', '00:00:00')
                            ->where('checkout_time', '<=', '04:00:00');
                    });
            })
            ->pluck('tanggal')
            ->unique()
            ->toArray();

        // Hasil
        $day = count($makanSiangDates);
        $night = count($makanMalamDates);


        // $longShiftDates = clone $getPresensi;
        // $longShiftDates = $longShiftDates
        //     ->where('checkin_time', '>=', '05:00:00')
        //     ->where('checkin_time', '<=', '12:00:00')
        //     ->where(function ($query) {
        //         $query->whereRaw("checkout_time >= ?", ["19:30:00"])
        //             ->orWhereRaw("checkout_time <= ?", ["05:00:00"]);
        //     })
        //     ->pluck('tanggal')
        //     ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
        //     ->toArray();

        // $longShiftCount = count($longShiftDates);
        // $day = $longShiftCount + $day;

        // $shift1Sat = clone $getPresensi;
        // $shift1Sat = $shift1Sat->whereRaw("EXTRACT(DOW FROM tanggal) = 6")->whereRaw("shift ~* ?", ['shift [1]'])->count();

        $sunday = clone $getPresensi;
        $sunday = $sunday->whereRaw("EXTRACT(DOW FROM tanggal) = 0")->count();

        $checkInArray = clone $getPresensi;
        $checkInArray = collect($checkInArray->get('tanggal')->toArray())->pluck('tanggal')->map(fn($date) => \Carbon::parse($date));
        $getFullWeek = $this->isFullWeek($checkInArray);

        $saturday = clone $getPresensi;
        $saturday = $saturday->whereRaw("EXTRACT(DOW FROM tanggal) = 6")->count();

        $saturday75 = clone $getPresensi;
        $saturday75 = $saturday75->whereRaw("EXTRACT(DOW FROM tanggal) = 6")->get();
        $get75 = $this->is75($saturday75);

        $collectFullweek = collect($getFullWeek)->where('hasMondayToSaturday', true);
        $saturday_7_5_fullweek = $this->saturday_7_5_fullweek($collectFullweek, $get75, $saturday);

        // Cari tanggal presensi yang juga libur (nasional & cuti bersama)
        $tanggalPresensi = presensi_absensi::where('default_user_id', $getUserId)
            ->where('presensi_absensi.status', 'ATTEND')
            ->whereRaw('tanggal >= ? and tanggal <= ?', [$date_from, $date_to])
            ->pluck('tanggal')
            ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
            ->toArray();
            
        $kerjaDiHariLibur = array_values(array_intersect($tanggalPresensi, $getLiburNasionalAndSunday->toArray()));
        $countKerjaDiHariLibur = count($kerjaDiHariLibur);
        // if (@$karyId == 916) {
        //     dump([
        //         'hari biasa dia masuk' => $countKerjaDiHariLibur
        //     ]);
        // }   
        $semuaTanggalKerja = array_values($tanggalPresensi); 
        $countTotalHariKerja = count($semuaTanggalKerja);
        $checkOvertimeSemuaHari = $this->checkOvertimeHariLibur($getUserId, $semuaTanggalKerja);

        $checkOvertimeHariLibur = $this->checkOvertimeHariLibur($getUserId, $kerjaDiHariLibur);

        //$resOvertimeSemuaHari = $this->checkOvertimeDetail($getUserId, $semuaTanggalKerja);
        //$checkOvertimeSemuaHari = $resOvertimeSemuaHari['total_menit'];
        //$detailLemburAll = $resOvertimeSemuaHari['detail']; // Ini yang Anda butuhkan

        return [
            "user_id" => $getUserId,
            "count" => $count - $countKerjaDiHariLibur,
            "libur_nasional" => $liburNasionalCount,
            "night" => $night,
            "day" => $day,
            "shift" => 0,
            "shift1_sat" => 0,
            "sunday" => $sunday,
            "sunday_not_checkin" => 0,
            "saturday" => 0,
            "libur_nasional_checkin" => $countLiburNasionalCheckIn,
            "libur_nasional_not_checkin" => 0,
            "saturday_7_5" => [],
            "getFullWeek" => [],
            "saturday_7_5_fullweek" => [],
            "countFullWeek" => 0,
            "kerja_di_hari_libur_count" => $countKerjaDiHariLibur,
            "kerja_di_hari_libur_tanggal" => $kerjaDiHariLibur,
            "lembur_kerja_di_hari_libur" => $checkOvertimeHariLibur,
            "lembur_kerja_all" => $checkOvertimeSemuaHari,
            //"detail_lembur" => $detailLemburAll,
            'count_no_record_date' => $countTotalNoRecord,
        ];
    }

    // private function checkOvertimeHariLibur($userId, $kerjaDiHariLibur)
    // {
    //     $defaultUser = default_users::find($userId);
    //     $overtimeMinuteOnHoliday = 0;
    //     if (count($kerjaDiHariLibur) > 0) {
    //         $getPresensi = presensi_absensi::where('default_user_id', $userId)
    //             ->where('presensi_absensi.status', 'ATTEND')
    //             ->whereIn('tanggal', $kerjaDiHariLibur)
    //             ->get();

    //         foreach ($getPresensi as $single) {
    //             $checkin = \Carbon::parse($single['tanggal'] . ' ' . $single['checkin_time']);
    //             $checkout = \Carbon::parse($single['tanggal'] . ' ' . $single['checkout_time']);
    //             if ($checkin->greaterThanOrEqualTo($checkout)) {
    //                 $checkout->addDay();
    //             }

    //             if ($defaultUser->no_break_needed) {
    //                 $workHour = 8;
    //             } else {
    //                 $workHour = 9;
    //             }

    //             if ($checkout->diffInHours($checkin) >= $workHour) {
    //                 $overtimeMinuteOnHoliday += $checkout->diffInMinutes($checkin) - ($workHour * 60);
    //             }
    //         }
    //     }
    //     return $overtimeMinuteOnHoliday;
    // }


    // private function checkOvertimeHariLibur($userId, $kerjaDiHariLibur)
    // {
    //     $defaultUser = default_users::find($userId);
    //     $overtimeMinuteOnHoliday = 0;

    //     if (count($kerjaDiHariLibur) > 0) {
    //         $getPresensi = presensi_absensi::where('default_user_id', $userId)
    //             ->where('status', 'ATTEND')
    //             ->whereIn('tanggal', $kerjaDiHariLibur)
    //             ->get();

    //         foreach ($getPresensi as $single) {
    //             $checkin = \Carbon\Carbon::parse($single['tanggal'] . ' ' . $single['checkin_time']);
    //             $checkout = \Carbon\Carbon::parse($single['tanggal'] . ' ' . $single['checkout_time']);

    //             if ($checkin->greaterThanOrEqualTo($checkout)) {
    //                 $checkout->addDay();
    //             }

    //             $workHour = $defaultUser->no_break_needed ? 8 : 9;

    //             $timezone = $checkout->timezoneName;
    //             $lemburStart = \Carbon\Carbon::createFromTime(17, 0, $timezone)->setDate(
    //                 $checkout->year, $checkout->month, $checkout->day
    //             );
    //             $lemburThreshold = \Carbon\Carbon::createFromTime(17, 15, $timezone)->setDate(
    //                 $checkout->year, $checkout->month, $checkout->day
    //             );

    //             $workDurationMinutes = $checkout->diffInMinutes($checkin);

    //             if ($workDurationMinutes >= $workHour * 60) {
    //                 if ($checkout->greaterThan($lemburThreshold)) {
    //                     // Hitung durasi lembur dalam detik
    //                     $lemburSeconds = $checkout->diffInSeconds($lemburStart);
    //                     $lemburMinutes = intdiv($lemburSeconds, 60); // menit bulat ke bawah
    //                     $detikSisa = $lemburSeconds % 60;

    //                     // Jika detik lebih dari 40, genapkan ke atas
    //                     if ($detikSisa > 40) {
    //                         $lemburMinutes += 1;
    //                     }

    //                     $overtimeMinuteOnHoliday += $lemburMinutes;
    //                 }
    //             }
    //         }
    //     }

    //     return $overtimeMinuteOnHoliday;
    // }

    private function checkOvertimeDetail($userId, $kerjaDiHariLibur)
    {
        $overtimeMinuteOnHoliday = 0;
        $detailLembur = []; // Array untuk menampung detail tanggal => menit

        if (count($kerjaDiHariLibur) > 0) {
            $getPresensi = presensi_absensi::where('default_user_id', $userId)
                ->where('status', 'ATTEND')
                ->whereIn('tanggal', $kerjaDiHariLibur)
                ->get();

            foreach ($getPresensi as $single) {
                $checkin = \Carbon\Carbon::parse($single['tanggal'] . ' ' . $single['checkin_time']);
                $checkout = \Carbon\Carbon::parse($single['tanggal'] . ' ' . $single['checkout_time']);
                $tanggal = \Carbon\Carbon::parse($single['tanggal'])->toDateString();

                if ($checkin->greaterThanOrEqualTo($checkout)) {
                    $checkout->addDay();
                }

                $lemburStart = \Carbon\Carbon::parse($single['tanggal'] . ' 17:00');
                $lemburThreshold = \Carbon\Carbon::parse($single['tanggal'] . ' 17:15');

                if ($checkout->greaterThan($lemburThreshold)) {
                    $lemburMinutes = (int) $lemburStart->diffInMinutes($checkout);
                    $overtimeMinuteOnHoliday += $lemburMinutes;
                    
                    // Simpan detail per tanggal
                    $detailLembur[$tanggal] = $lemburMinutes;
                } else {
                    // Jika tidak lembur, set 0
                    $detailLembur[$tanggal] = 0;
                }
            }
        }

        return [
            'total_menit' => $overtimeMinuteOnHoliday,
            'detail' => $detailLembur
        ];
    }

    // private function checkOvertimeHariLibur($userId, $kerjaDiHariLibur)
    // {
    //     $defaultUser = default_users::find($userId);
    //     $overtimeMinuteOnHoliday = 0;

    //     if (count($kerjaDiHariLibur) > 0) {
    //         $getPresensi = presensi_absensi::where('default_user_id', $userId)
    //             ->where('status', 'ATTEND')
    //             ->whereIn('tanggal', $kerjaDiHariLibur)
    //             ->get();

    //         // $getPresensi = presensi_absensi::where('default_user_id', $userId)
    //         //     ->where('status', 'ATTEND')
    //         //     ->whereIn('tanggal', $kerjaDiHariLibur)
    //         //     ->orderBy('id', 'desc')
    //         //     ->get()
    //         //     ->unique('tanggal'); // Hanya ambil satu record (ID terbesar) per tanggal

    //         // dd($getPresensi->mapWithKeys(function ($item) {
    //         //     return [$item->tanggal => $item->id];
    //         // })->toArray());
    //         // if ($userId == 946) {
    //         //     dump("Nama: {$defaultUser->name}");         
    //         //     dump("Checkin: $getPresensi");
    //         // }

    //         foreach ($getPresensi as $single) {
                
    //             $checkin = \Carbon\Carbon::parse($single['tanggal'] . ' ' . $single['checkin_time']);
    //             $checkout = \Carbon\Carbon::parse($single['tanggal'] . ' ' . $single['checkout_time']);

    //             if ($checkin->greaterThanOrEqualTo($checkout)) {
    //                 $checkout->addDay();
    //             }

    //             $lemburStart = \Carbon\Carbon::parse($single['tanggal'] . ' 17:00');
    //             $lemburThreshold = \Carbon\Carbon::parse($single['tanggal'] . ' 17:15');

    //             if ($checkout->greaterThan($lemburThreshold)) {
    //                 $lemburMinutes = $lemburStart->diffInMinutes($checkout);
    //                 $overtimeMinuteOnHoliday += $lemburMinutes;
    //             }
    //             // if($single['tanggal'] === "2026-03-03" )
    //             // {
    //             //     dd($lemburMinutes, $single['id'], $checkout, $lemburThreshold, ($checkout->greaterThan($lemburThreshold)));
    //             // }
    //         }
    //     }

    //     return $overtimeMinuteOnHoliday;
    // }

    private function checkOvertimeHariLibur($userId, $kerjaDiHariLibur)
    {
        $overtimeMinuteOnHoliday = 0;

        if (count($kerjaDiHariLibur) > 0) {
            // Menggunakan Raw Query dengan subquery LIMIT 1 per tanggal
            // Agar sama persis dengan logic di fungsi employee_attendance_detail_range_new
            $getPresensi = collect();
            
            foreach (array_unique($kerjaDiHariLibur) as $tanggal) {
                $data = \DB::select("
                    SELECT p.tanggal, p.checkin_time, p.checkout_time 
                    FROM presensi_absensi p
                    JOIN default_users u ON u.id = p.default_user_id
                    WHERE u.id = ? 
                    AND p.tanggal = ?
                    AND p.status = 'ATTEND'
                    LIMIT 1
                ", [$userId, $tanggal]);

                if (!empty($data)) {
                    $getPresensi->push($data[0]);
                }
            }

            foreach ($getPresensi as $single) {
                // Reset lemburMinutes setiap awal loop untuk menghindari nilai warisan
                $lemburMinutes = 0; 

                $checkin = \Carbon\Carbon::parse($single->tanggal . ' ' . $single->checkin_time);
                $checkout = \Carbon\Carbon::parse($single->tanggal . ' ' . $single->checkout_time);

                // Penanganan checkout melewati tengah malam (seperti data Riki 27-02-2026) 
                if ($checkin->greaterThanOrEqualTo($checkout)) {
                    $checkout->addDay();
                }

                $lemburStart = \Carbon\Carbon::parse($single->tanggal . ' 17:00');
                $lemburThreshold = \Carbon\Carbon::parse($single->tanggal . ' 17:15');

                // Kalkulasi lembur jika melewati threshold
                if ($checkout->greaterThan($lemburThreshold)) {
                    $lemburMinutes = (int) $lemburStart->diffInMinutes($checkout);
                    $overtimeMinuteOnHoliday += $lemburMinutes;
                }
            }
        }

        return $overtimeMinuteOnHoliday;
    }




    private function is75($data)
    {
        $is75 = [];
        $requiredDiff = 10;
        foreach ($data as $single) {
            $checkIn = \Carbon::parse($single['checkin_time']);
            $checkOut = \Carbon::parse($single['checkout_time']);
            $durationInHours = $checkOut->diffInHours($checkIn);
            if ($durationInHours >= $requiredDiff) {
                $is75[] = [
                    'date' => $single['tanggal'],
                ];
            };
        }
        return $is75;
    }

    private function isFullWeek($dates)
    {
        $hasFullWeek = 0;

        $mondayIndices = [];
        foreach ($dates as $index => $date) {
            $carbonDate = \Carbon::parse($date);
            if ($carbonDate->isMonday()) {
                $mondayIndices[] = $index;
            }
        }


        $results = [];
        foreach ($mondayIndices as $mondayIndex) {
            $startDate = \Carbon::parse($dates[$mondayIndex]);
            $foundDays = [];

            for ($i = 0; $i < 6; $i++) {
                $dayToCheck = $startDate->copy()->addDays($i);
                foreach ($dates as $date) {
                    if ($dayToCheck->isSameDay(\Carbon::parse($date))) {
                        $foundDays[] = $dayToCheck->format('Y-m-d');
                        break;
                    }
                }
            }

            $isComplete = count($foundDays) === 6;
            $results[] = [
                'mondayIndex' => $mondayIndex,
                'hasMondayToSaturday' => $isComplete,
                'foundDays' => $foundDays
            ];
        }

        return $results;
    }

    private function saturday_7_5_fullweek($fullweek, $get75, $saturday)
    {
        $get75Day = array_column($get75, 'date');
        $fullweek = $fullweek->pluck('foundDays')->flatten()->toArray();
        $date = array_intersect($get75Day, $fullweek);
        if (!empty($date)) {
            return $saturday;
        }
        return 0;
    }

    // private function salaryPresensiByMinute($kary, $date_from, $date_to)
    // {
    //     $kary = collect(value: $kary);
    //     $workMinute = 0;
    //     $missingCheck = 0;
    //     $breakMinute = 0;
    //     $overtimeMinute = 0;
    //     $lateMinute = 0;
    //     $leaveEarly = 0;
    //     $lateCount = 0;
    //     $limitDay = 540;
    //     $limitDayWithoutBreak = 480;
    //     $overBreakMinute = 0;
    //     $overBreakMinutePerDay = [];
    //     $overBreakPenaltyPerDay = [];

    //     $getUserId = default_users::where('m_kary_id', $kary['id'])->first() ?? 0;
    //     if (!$getUserId) {
    //         return [
    //             'work_minute' => 0,
    //             'missing_check' => 0,
    //             'break_minute' => 0,
    //             'overtime_minute' => 0,
    //             'late_minute' => 0,
    //             'leave_early' => 0,
    //             'late_count' => 0,
    //             'over_break_minute' => 0,
    //             'over_break_minute_per_day' => 0,
    //             'over_break_penalty_per_day' => 0,
    //         ];
    //     }
    //     $jamKerja = m_jam_kerja::where('tipe_jam_kerja_id', $kary['tipe_jam_kerja_id'])->first();
    //     $liburNasional = m_libur_nasional::where('is_active', true)
    //         ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
    //         ->pluck('tanggal')
    //         ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
    //         ->toArray();

    //     $getPresensi = presensi_absensi::where('default_user_id', $getUserId->id)
    //         ->where('status', 'ATTEND')
    //         ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
    //         ->whereRaw("EXTRACT(DOW FROM tanggal) != 0")
    //         ->whereNotIn('tanggal', $liburNasional);

    //     $getMinute = clone $getPresensi;
    //     $getMinute = $getMinute->get();


    //     if ($getUserId['no_break_needed']) {
    //         foreach ($getMinute as $single) {
    //             $checkin = \Carbon::parse($single['tanggal'] . $single['checkin_time']);
    //             $checkout = \Carbon::parse($single['tanggal'] . $single['checkout_time']);

    //             // Overtime calculation (do not touch)
    //             $parseWorkHourStart = \Carbon::parse($single['tanggal'] . $jamKerja->waktu_mulai);
    //             $workStartLimit = $parseWorkHourStart->copy();
    //             $workEndLimit = $workStartLimit->copy()->addMinutes($limitDayWithoutBreak);
    //             $actualStart = $checkin->copy();
    //             $actualEnd = $checkout->copy();
    //             if ($actualEnd->lessThan($actualStart))
    //                 $actualEnd->addDay();
    //             $normalWorkEnd = $actualEnd->copy()->min($workEndLimit);
    //             $normalWorkMinutes = max(0, $actualStart->diffInMinutes($normalWorkEnd));
    //             $extraWorkMinutes = max(0, $actualEnd->diffInMinutes($workEndLimit, false) * -1);
    //             if ($actualEnd->lessThan($workEndLimit)) {
    //                 $leaveEarly += $this->leaveEarlyFunc($workStartLimit->copy(), $limitDayWithoutBreak, $actualEnd->copy());
    //             }
    //             $workMinute += $normalWorkMinutes;
    //             $overtimeMinute += $extraWorkMinutes;
    //             $missingCheck += $single['missing_check'];
    //             // Late minute calculation
    //             $late = $this->lateMinuteFunc($checkin->copy(), $parseWorkHourStart->copy());
    //             $lateMinute += $late;
    //             if ($late > 0) {
    //                 $lateCount++;
    //             }
    //         }
    //     } else {
    //         foreach ($getMinute as $single) {
    //             $checkin = \Carbon::parse($single['tanggal'] . $single['checkin_time']);
    //             $istirahat = \Carbon::parse($single['tanggal'] . $single['checkout_istirahat_time']);
    //             $kerja = \Carbon::parse($single['tanggal'] . $single['checkin_kerja_time']);
    //             $checkout = \Carbon::parse($single['tanggal'] . $single['checkout_time']);

    //             // Overtime calculation (do not touch)
    //             $parseWorkHourStart = \Carbon::parse($single['tanggal'] . $jamKerja->waktu_mulai);
    //             $workStartLimit = $parseWorkHourStart->copy();
    //             $workEndLimit = $workStartLimit->copy()->addMinutes($limitDay);
    //             $actualStart = $checkin->copy();
    //             $actualEnd = $checkout->copy();
    //             if ($actualEnd->lessThan($actualStart))
    //                 $actualEnd->addDay();
    //             $normalWorkEnd = $actualEnd->copy()->min($workEndLimit);
    //             $normalWorkMinutes = max(0, $actualStart->diffInMinutes($normalWorkEnd));
    //             $extraWorkMinutes = max(0, $actualEnd->diffInMinutes($workEndLimit, false) * -1);
    //             if ($actualEnd->lessThan($workEndLimit)) {
    //                 $leaveEarly += $this->leaveEarlyFunc($workStartLimit->copy(), $limitDay, $actualEnd->copy());
    //             }
    //             $workMinute += $normalWorkMinutes;
    //             $overtimeMinute += $extraWorkMinutes;
    //             $missingCheck += $single['missing_check'];

    //             // Over break logic (only for break penalty, not overtime)
    //             $breakTime = $istirahat->diffInMinutes($kerja);
    //             $dayOfWeek = $checkin->dayOfWeek;
    //             $breakLimit = ($dayOfWeek == 5) ? 90 : 60;
    //             $lebih = ($breakTime > $breakLimit) ? ($breakTime - $breakLimit) : 0;
    //             if ($lebih > 0) {
    //                 $overBreakMinute += $lebih;
    //                 $overBreakMinutePerDay[$single['tanggal']] = $lebih;
    //                 if ($lebih >= 45) {
    //                     $overBreakPenaltyPerDay[$single['tanggal']] = 15000;
    //                 } elseif ($lebih >= 30) {
    //                     $overBreakPenaltyPerDay[$single['tanggal']] = 10000;
    //                 } elseif ($lebih >= 15) {
    //                     $overBreakPenaltyPerDay[$single['tanggal']] = 6000;
    //                 } elseif ($lebih >= 5) {
    //                     $overBreakPenaltyPerDay[$single['tanggal']] = 4000;
    //                 } else {
    //                     $overBreakPenaltyPerDay[$single['tanggal']] = 0;
    //                 }
    //             }

    //             $late = $this->lateMinuteFunc($checkin->copy(), $parseWorkHourStart->copy());
    //             $lateMinute += $late;
    //             if ($late > 0) {
    //                 $lateCount++;
    //             }

    //         }
    //     }

    //     return [
    //         'work_minute' => $workMinute,
    //         'missing_check' => $missingCheck,
    //         'break_minute' => $breakMinute,
    //         'overtime_minute' => $overtimeMinute,
    //         'late_minute' => $lateMinute,
    //         'leave_early' => $leaveEarly,
    //         'late_count' => $lateCount,
    //         'over_break_minute' => $overBreakMinute,
    //         'over_break_minute_per_day' => $overBreakMinutePerDay,
    //         'over_break_penalty_per_day' => $overBreakPenaltyPerDay,
    //     ];
    // }
    private function salaryPresensiByMinute($kary, $date_from, $date_to)
    {
        $kary = collect(value: $kary);

        //antisipasi kalau dinamis
        // $tipe_jam_kerja = m_jam_kerja::where('tipe_jam_kerja_id', $kary['tipe_jam_kerja_id'])->where('is_active', true)->first();

        // if ($tipe_jam_kerja) {
        //     $mulai = Carbon::parse($tipe_jam_kerja->waktu_mulai);
        //     $akhir = Carbon::parse($tipe_jam_kerja->waktu_akhir);

        //     if ($tipe_jam_kerja->is_hari_berikutnya) {
        //         $akhir->addDay();
        //     } 

        //     elseif ($akhir->lessThan($mulai)) {
        //         $akhir->addDay();
        //     }

        //     $total_menit = $mulai->diffInMinutes($akhir);
        // } else {
        //     $total_menit = null;
        // }
        $total_menit = null;

        $workMinute = 0;
        $missingCheck = 0;
        $breakMinute = 0;
        $overtimeMinute = 0;
        $lateMinute = 0;
        $leaveEarly = 0;
        $leaveEarlyCount = 0;
        $lateCount = 0;
        $limitDay = $total_menit ? $total_menit : 540;
        $limitDayWithoutBreak = $total_menit ? ($total_menit-60) : 480;
        $overBreakMinute = 0;
        $overBreakMinutePerDay = [];
        $overBreakPenaltyPerDay = [];

        $getUserId = default_users::where('m_kary_id', $kary['id'])->first() ?? 0;
        if (!$getUserId) {
            return [
                'work_minute' => 0,
                'missing_check' => 0,
                'break_minute' => 0,
                'overtime_minute' => 0,
                'late_minute' => 0,
                'leave_early' => 0,
                'leave_early_count' => 0,
                'late_count' => 0,
                'over_break_minute' => 0,
                'over_break_minute_per_day' => 0,
                'over_break_penalty_per_day' => 0,
            ];
        }
        $jamKerja = m_jam_kerja::where('tipe_jam_kerja_id', $kary['tipe_jam_kerja_id'])->first();
        $liburNasional = m_libur_nasional::where('is_active', true)
            ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
            ->pluck('tanggal')
            ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
            ->toArray();

        $getPresensi = presensi_absensi::where('default_user_id', $getUserId->id)
            ->where('status', 'ATTEND')
            ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
            //->whereRaw("EXTRACT(DOW FROM tanggal) != 0")
            //->whereNotIn('tanggal', $liburNasional)
            ;

        //dd($jamKerja, $liburNasional, $getPresensi);

        $getMinute = clone $getPresensi;
        $getMinute = $getMinute->get();


        if ($getUserId['no_break_needed']) {
            foreach ($getMinute as $single) {
                $checkin = \Carbon::parse($single['tanggal'] . $single['checkin_time']);
                $checkout = \Carbon::parse($single['tanggal'] . $single['checkout_time']);

                // Overtime calculation (do not touch)
                $parseWorkHourStart = \Carbon::parse($single['tanggal'] . $jamKerja->waktu_mulai);
                $workStartLimit = $parseWorkHourStart->copy();
                $workEndLimit = $workStartLimit->copy()->addMinutes($limitDayWithoutBreak);
                $actualStart = $checkin->copy();
                $actualEnd = $checkout->copy();
                if ($actualEnd->lessThan($actualStart))
                    $actualEnd->addDay();
                $normalWorkEnd = $actualEnd->copy()->min($workEndLimit);
                $normalWorkMinutes = max(0, $actualStart->diffInMinutes($normalWorkEnd));

                // New overtime calculation
                $lemburStart = \Carbon::parse($single['tanggal'] . ' 17:00');
                $lemburThreshold = \Carbon::parse($single['tanggal'] . ' 17:15');
                $extraWorkMinutes = 0;

                if ($actualEnd->greaterThan($lemburThreshold)) {
                    // If checkout after 17:15, count overtime from 17:00
                    $extraWorkMinutes = $actualEnd->diffInMinutes($lemburStart);
                }

                if ($actualEnd->lessThan($workEndLimit)) {
                    $leaveEarly += $this->leaveEarlyFunc($workStartLimit->copy(), $limitDayWithoutBreak, $actualEnd->copy());
                    $leaveEarlyCount++;
                }
                $workMinute += $normalWorkMinutes;
                $overtimeMinute += $extraWorkMinutes;
                $missingCheck += $single['missing_check'];
                // Late minute calculation
                $late = $this->lateMinuteFunc($checkin->copy(), $parseWorkHourStart->copy());
                $lateMinute += $late;
                if ($late > 0) {
                    $lateCount++;
                }
            }
        } else {
            foreach ($getMinute as $single) {
                $checkin = \Carbon::parse($single['tanggal'] . $single['checkin_time']);
                $istirahat = \Carbon::parse($single['tanggal'] . $single['checkout_istirahat_time']);
                $kerja = \Carbon::parse($single['tanggal'] . $single['checkin_kerja_time']);
                $checkout = \Carbon::parse($single['tanggal'] . $single['checkout_time']);

                // Overtime calculation (do not touch)
                $parseWorkHourStart = \Carbon::parse($single['tanggal'] . $jamKerja->waktu_mulai);
                $workStartLimit = $parseWorkHourStart->copy();
                $workEndLimit = $workStartLimit->copy()->addMinutes($limitDay);
                $actualStart = $checkin->copy();
                $actualEnd = $checkout->copy();
                if ($actualEnd->lessThan($actualStart))
                    $actualEnd->addDay();
                $normalWorkEnd = $actualEnd->copy()->min($workEndLimit);
                $normalWorkMinutes = max(0, $actualStart->diffInMinutes($normalWorkEnd));

                // New overtime calculation
                $lemburStart = \Carbon::parse($single['tanggal'] . ' 17:00');
                $lemburThreshold = \Carbon::parse($single['tanggal'] . ' 17:15');

                $extraWorkMinutes = 0;
                if ($actualEnd->greaterThan($lemburThreshold)) {
                    // If checkout after 17:15, count overtime from 17:00
                    
                    $extraWorkMinutes = $actualEnd->diffInMinutes($lemburStart);
                }

                // dd($actualEnd, $workEndLimit);
                $breakTime = $actualEnd->copy()->setTime(12, 0, 0);
                $toleranceMinutes = 6;
                $breakWithTolerance = $breakTime->copy()->addMinutes($toleranceMinutes);

                if ($actualEnd->lessThan($workEndLimit)) {
                    // dd($single);
                    if($actualEnd->lessThan($breakWithTolerance)){
                        $leaveEarly += $this->leaveEarlyFunc($workStartLimit->copy(), $limitDayWithoutBreak, $actualEnd->copy());
                        $leaveEarlyCount++;

                    }else{
                        $leaveEarly += $this->leaveEarlyFunc($workStartLimit->copy(), $limitDay, $actualEnd->copy());
                        $leaveEarlyCount++;
                    }
                }
                $workMinute += $normalWorkMinutes;
                $overtimeMinute += $extraWorkMinutes;
                $missingCheck += $single['missing_check'];

                // Over break logic (only for break penalty, not overtime)
                $breakTime = $istirahat->diffInMinutes($kerja);
                $dayOfWeek = $checkin->dayOfWeek;
                $breakLimit = ($dayOfWeek == 5) ? 90 : 60;
                $lebih = ($breakTime > $breakLimit) ? ($breakTime - $breakLimit) : 0;
                if ($lebih > 0) {
                    $overBreakMinute += $lebih;
                    $overBreakMinutePerDay[$single['tanggal']] = $lebih;
                    if ($lebih >= 45) {
                        $overBreakPenaltyPerDay[$single['tanggal']] = 15000;
                    } elseif ($lebih >= 30) {
                        $overBreakPenaltyPerDay[$single['tanggal']] = 10000;
                    } elseif ($lebih >= 15) {
                        $overBreakPenaltyPerDay[$single['tanggal']] = 6000;
                    } elseif ($lebih >= 5) {
                        $overBreakPenaltyPerDay[$single['tanggal']] = 4000;
                    } else {
                        $overBreakPenaltyPerDay[$single['tanggal']] = 0;
                    }
                }

                $late = $this->lateMinuteFunc($checkin->copy(), $parseWorkHourStart->copy());
                $lateMinute += $late;
                if ($late > 0) {
                    $lateCount++;
                }
            }
        }

        return [
            'work_minute' => $workMinute,
            'missing_check' => $missingCheck,
            'break_minute' => $breakMinute,
            'overtime_minute' => $overtimeMinute,
            'late_minute' => $lateMinute,
            'leave_early' => $leaveEarly,
            'leave_early_count' => $leaveEarlyCount,
            'late_count' => $lateCount,
            'over_break_minute' => $overBreakMinute,
            'over_break_minute_per_day' => $overBreakMinutePerDay,
            'over_break_penalty_per_day' => $overBreakPenaltyPerDay,
        ];
    }

    private function lateMinuteFunc($checkin, $parseWorkHourStart)
    {
        $lateMinutePrivate = 0;
        if ($checkin->greaterThan($parseWorkHourStart->addMinutes(15))) {
            $lateMinuteAdd = $checkin->diffInMinutes($parseWorkHourStart);
            $lateMinutePrivate += $lateMinuteAdd;
        }
        return $lateMinutePrivate;
    }

    private function leaveEarlyFunc($parseWorkHourStart, $limitDay, $checkOut)
    {
        $leaveEarlyPrivate = 0;
        $checkoutDay = $parseWorkHourStart->addMinutes($limitDay);

        if ($checkOut->lt($checkoutDay)) {
            $leaveEarlyPrivateAdd = $checkOut->diffInMinutes($checkoutDay);
            $leaveEarlyPrivate += $leaveEarlyPrivateAdd;
        }

        return $leaveEarlyPrivate;
    }

    // private function leaveEarlyFunc($parseWorkHourStart, $limitDay, $checkOut, $breakStart = null, $breakEnd = null)
    // {
    //     $leaveEarlyPrivate = 0;
        
    //     if (!$breakStart) {
    //     $breakStart = Carbon::createFromTime(12, 0);
    //     }

    //     if (!$breakEnd) {
    //         $breakEnd = Carbon::createFromTime(13, 0);
    //     }

    //     $checkoutDay = $parseWorkHourStart->copy()->addMinutes($limitDay);

    //     if ($checkOut->lt($checkoutDay)) {
    //         $leaveEarlyPrivateAdd = $checkoutDay->diffInMinutes($checkOut);

    //         if ($breakStart && $breakEnd) {
    //             if ($checkOut->lt($breakStart)) {
    //                 $leaveEarlyPrivate += $leaveEarlyPrivateAdd - $breakEnd->diffInMinutes($breakStart);
    //             } elseif ($checkOut->between($breakStart, $breakEnd)) {
    //                 $leaveEarlyPrivate += $leaveEarlyPrivateAdd - $checkOut->diffInMinutes($breakStart);
    //             } else {
    //                 $leaveEarlyPrivate += $leaveEarlyPrivateAdd;
    //             }
    //         } else {
    //             $leaveEarlyPrivate += $leaveEarlyPrivateAdd;
    //         }
    //     }

    //     return $leaveEarlyPrivate;
    // }


    private function overtimeMinuteFunc($parseWorkHourStart, $limitDay, $checkOut)
    {
        $overtimeMinutePrivate = 0;
        $checkoutDay = $parseWorkHourStart->addMinutes($limitDay);

        if ($checkOut->gt($checkoutDay)) {
            $overWorkPrivateAdd = $checkOut->diffInMinutes($checkoutDay);
            $overtimeMinutePrivate += $overWorkPrivateAdd;
        }

        return $overtimeMinutePrivate;
    }

    private function noRecordDate($date_from, $date_to, $getUserId)
    {
        $allDates = [];
        $start = \Carbon::parse($date_from);
        $end = \Carbon::parse($date_to);
        while ($start->lte($end)) {
            if ($start->dayOfWeek !== 0) {
                $allDates[] = $start->toDateString();
            }
            $start->addDay();
        }

        $recordedDates = presensi_absensi::where('default_user_id', $getUserId)
            ->whereRaw("tanggal >= ? and tanggal <= ?", [$date_from, $date_to])
            ->where('status', 'ATTEND')
            ->pluck('tanggal')
            ->map(fn($tanggal) => \Carbon::parse($tanggal)->toDateString())
            ->toArray();

        $noRecordDates = array_values(array_diff($allDates, $recordedDates));
        return $noRecordDates;
    }

    private function factorSalaryArt($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);


        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;


        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");

        //tunjangan
        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {

                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        // $dataResults = $t_kary_salary->get();
        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // $gaji_karyawan = @$t_kary_salary[0]->total;

        $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();

        // if($t_potongan->count())
        // {
        //     foreach ($t_potongan as $d)
        //     {
        //          $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];
        //     }
        // }

        //  if ($t_potongan->count()) {
        //     foreach ($t_potongan as $d) {


        //         $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             ->where('is_active', true)
        //             ->first()?->total_hutang ?? 0;
                
        //         if($hutang > 0){
        //             $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
        //             ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
        //             ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
        //             ->sum('t_final_gaji_det_rincian.value');
                    
        //             $sisa = max($hutang - $paid, 0);

        //             if ($d->percentage) {
        //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
        //             } else {
        //                 $nilai_netto = (float) $d->nilai;
        //             }

        //             if ($sisa > 0) {
        //                 if ($nilai_netto > $sisa) {
        //                     $nilai_netto = $sisa;
        //                 }
        //             } else {
        //                 $nilai_netto = 0;
        //             }
                    
        //             if($nilai_netto > 0){
        //                 $defaultColumns[] = [
        //                     'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                     'factor' => '-',
        //                     'value' => $nilai_netto,
        //                     'type' => 'BULANAN',
        //                     'can_adjust' => 1,
        //                     't_potongan_id' => $d->id,
        //                 ];
        //             }
        //         }else{
        //             if($d->nilai > 0){
        //             $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //             ];  
        //             }
        //         }

        //     }
        // }

        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }

        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        //check data presensi
        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);



        // //cek telat kerja
        // if ($presensiMinute['late_count'] > 0) {
        //     $lateCount = $presensiMinute['late_count'];
        //     $lateValue = 15000;
        //     $defaultColumns[] = [
        //         'label' => "Denda Telat Kerja " . $lateCount . " Kali",
        //         'factor' => '-',
        //         'value' => $lateCount * $lateValue,
        //         'type' => 'BULANAN',
        //         'can_adjust' => 1
        //     ];
        // }

        // //cek lebih dari jadwal menit istirahat
        // if ($presensiMinute['over_break_penalty_per_day'] > 0) {
        //     foreach ($presensiMinute['over_break_penalty_per_day'] as $key => $value) {
        //         if ($value > 0) {
        //             $defaultColumns[] = [
        //                 'label' => "Denda Istirahat Lebih Dari Waktu Istirahat - Hari Ke-$key",
        //                 'factor' => '-',
        //                 'value' => $value,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1
        //             ];
        //         }
        //     }
        // }



        // check kehadiran karyawan
        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $jml_hari_sebulan = $att->work_days_in_month;
            $jml_hari_terpilih = $att->work_day_in_week;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
            $libur_nasional = @$att->libur_nasional;
            $cuti_satu_hari = @$att->cuti_satu_hari;

            $total_gaji_libur_nasional = 0;
            $total_7_5_fullweek = 0;
            $totalSundayCheckin = 0;
            $totalSundayFullweek = 0;



            $gaji_pokok_hari = 0;
            $countOfHariLiburCheckin = 0;


            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // // Hitung hari kerja biasa dan merah
                        // $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                        //     (float) $presensiMinute['work_minute'] :
                        //     (float) $presensi['count'];

                        // Hitung hari kerja merah (minggu + hari libur nasional)
                        // $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);
                        // $hari_kerja_merah = ($presensi['kerja_di_hari_libur_count'] ?? 0);

                        // Hitung hari kerja biasa (total - hari merah)
                        // $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);
                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        // $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                        //     ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                        //     ->count();

                        // if ($t_cuti_approved > 0) {
                        //     $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                        //     if ($sisa_cuti_satu_hari > 0) {
                        //         $hari_kerja_biasa += $t_cuti_approved;
                        //     }
                        // }

                         $gaji_menit = $d->tipe_perhitungan == "MENIT" ?
                            (float) $d->nominal :
                            (float) $d->nominal / 8 / 60;

                        //denda pulang cepat
                        // if ($presensiMinute['leave_early'] > 0) {
                        //     $defaultColumns[] = [
                        //         'label' => "Denda Pulang Cepat - " . $presensiMinute['leave_early'] . " Menit",
                        //         'factor' => '-',
                        //         'value' => $presensiMinute['leave_early'] * $gaji_menit,
                        //         'type' => 'BULANAN',
                        //         'can_adjust' => 1
                        //     ];
                        // }

                        // Hitung dan tambahkan gaji untuk hari kerja biasa

                        $defaultColumns[] = [
                            'label' => 'Gaji Pokok Hari Biasa - ' . $presensiMinute['work_minute'] . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                            'factor' => '+',
                            'value' => (float) $d->nominal * (float) $presensiMinute['work_minute'],
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];

                        // // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
                        // if ($hari_kerja_merah > 0) {
                        //     $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
                        //     $defaultColumns[] = [
                        //         'label' => 'Gaji Pokok Hari Merah - ' . $presensi['kerja_di_hari_libur_count'] .
                        //             ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                        //         'factor' => '+',
                        //         'value' => $hari_kerja_merah * $nominal_hari_merah,
                        //         'type' => 'BULANAN',
                        //         'can_adjust' => 1
                        //     ];
                        // }

                        continue;
                    }
                    // if ($keterangan === "uang makan siang") {
                    //     // if (@$kary->id == 915) {
                    //     //     dump([
                    //     //         'wang makan siang' => $presensi['day'] ?? 0
                    //     //     ]);
                    //     // }
                    //     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
                    //         $presensi['day'] = $t_cuti_approved;
                    //     }
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === "uang makan malam") {
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // if ($keterangan === "uang kerajinan") {
                    //     $maxKerajinan = 5;
                    //     $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
                    //     $hadir = max(0, min($hadir, $maxKerajinan));
                    //     $percentage = $hadir / $maxKerajinan;
                    //     $nominalKerajinan = $percentage * (float) @$d->nominal;
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan,
                    //         'factor' => '+',
                    //         'value' => $nominalKerajinan,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === 'uang lembur') {
                    //     $overtimeMinute = $presensiMinute['overtime_minute'];
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
                    //         'factor' => '+',
                    //         'value' => @$d->nominal * $overtimeMinute ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === 'uang lembur hr merah') {

                    //     if ($presensi['kerja_di_hari_libur_count'] > 0) {
                    //         $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                    //     } else {
                    //         $overtimeOnHoliday = 0;
                    //     }

                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
                    //         'factor' => '+',
                    //         'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // if ($keterangan === 'uang transport') {
                    //     $totalUangTransport = $presensi['count'] + $presensi['kerja_di_hari_libur_count'] ?? 0;
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $totalUangTransport . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => @$d->nominal * $totalUangTransport ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // $defaultColumns[] = [
                    //     'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                    //     'factor' => '+',
                    //     'value' => $value * (float) @$d->nominal ?? 0,
                    //     'type' => 'BULANAN',
                    //     'can_adjust' => 1
                    // ];
                }
            }
        }

        if ($presensiMinute['missing_check'] != 0) {
            $missingCheck = $presensiMinute['missing_check'] ?? 0;
            $defaultColumns[] = [
                'label' => "Denda Absensi",
                'factor' => '-',
                'value' => $missingCheck * 5000,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        return $defaultColumns;
    }

    private function factorSalaryJagaMalam($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);


        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;


        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");

        //tunjangan
        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {

                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        // $dataResults = $t_kary_salary->get();
        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // $gaji_karyawan = @$t_kary_salary[0]->total;

        $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();

        // if($t_potongan->count())
        // {
        //     foreach ($t_potongan as $d)
        //     {
        //          $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];
        //     }
        // }

        //  if ($t_potongan->count()) {
        //     foreach ($t_potongan as $d) {


        //         $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             ->where('is_active', true)
        //             ->sum('total_hutang') ?? 0;
                
        //         if($hutang > 0){
        //             $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
        //             ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
        //             ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
        //             ->sum('t_final_gaji_det_rincian.value');
                    
        //             $sisa = max($hutang - $paid, 0);

        //             if ($d->percentage) {
        //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
        //             } else {
        //                 $nilai_netto = (float) $d->nilai;
        //             }

        //             if ($sisa > 0) {
        //                 if ($nilai_netto > $sisa) {
        //                     $nilai_netto = $sisa;
        //                 }
        //             } else {
        //                 $nilai_netto = 0;
        //             }

        //             if($nilai_netto > 0){
        //                 $defaultColumns[] = [
        //                     'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                     'factor' => '-',
        //                     'value' => $nilai_netto,
        //                     'type' => 'BULANAN',
        //                     'can_adjust' => 1,
        //                     't_potongan_id' => $d->id,
        //                 ];
        //             }

        //         }else{
        //             if($d->nilai > 0){
        //                 $defaultColumns[] = [
        //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                 'factor' => '-',
        //                 'value' => $d->nilai,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1,
        //                 't_potongan_id' => $d->id,
        //                 ];
        //             }
        //         }

        //     }
        // }

        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }


        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        //check data presensi
        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);



        // //cek telat kerja
        // if ($presensiMinute['late_count'] > 0) {
        //     $lateCount = $presensiMinute['late_count'];
        //     $lateValue = 15000;
        //     $defaultColumns[] = [
        //         'label' => "Denda Telat Kerja " . $lateCount . " Kali",
        //         'factor' => '-',
        //         'value' => $lateCount * $lateValue,
        //         'type' => 'BULANAN',
        //         'can_adjust' => 1
        //     ];
        // }

        // //cek lebih dari jadwal menit istirahat
        // if ($presensiMinute['over_break_penalty_per_day'] > 0) {
        //     foreach ($presensiMinute['over_break_penalty_per_day'] as $key => $value) {
        //         if ($value > 0) {
        //             $defaultColumns[] = [
        //                 'label' => "Denda Istirahat Lebih Dari Waktu Istirahat - Hari Ke-$key",
        //                 'factor' => '-',
        //                 'value' => $value,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1
        //             ];
        //         }
        //     }
        // }



        // check kehadiran karyawan
        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $jml_hari_sebulan = $att->work_days_in_month;
            $jml_hari_terpilih = $att->work_day_in_week;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
            $libur_nasional = @$att->libur_nasional;
            $cuti_satu_hari = @$att->cuti_satu_hari;

            $total_gaji_libur_nasional = 0;
            $total_7_5_fullweek = 0;
            $totalSundayCheckin = 0;
            $totalSundayFullweek = 0;



            $gaji_pokok_hari = 0;
            $countOfHariLiburCheckin = 0;


            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // // Hitung hari kerja biasa dan merah
                        // $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                        //     (float) $presensiMinute['work_minute'] :
                        //     (float) $presensi['count'];

                        // Hitung hari kerja merah (minggu + hari libur nasional)
                        // $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);
                        // $hari_kerja_merah = ($presensi['kerja_di_hari_libur_count'] ?? 0);

                        // Hitung hari kerja biasa (total - hari merah)
                        // $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);
                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        // $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                        //     ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                        //     ->count();

                        // if ($t_cuti_approved > 0) {
                        //     $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                        //     if ($sisa_cuti_satu_hari > 0) {
                        //         $hari_kerja_biasa += $t_cuti_approved;
                        //     }
                        // }
                         $gaji_menit = $d->tipe_perhitungan == "MENIT" ?
                            (float) $d->nominal :
                            (float) $d->nominal / 8 / 60;

                        //denda pulang cepat
                        if ($presensiMinute['leave_early'] > 0) {
                            $defaultColumns[] = [
                                'label' => "Denda Pulang Cepat - " . $presensiMinute['leave_early'] . " Menit",
                                'factor' => '-',
                                'value' => $presensiMinute['leave_early'] * $gaji_menit,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung dan tambahkan gaji untuk hari kerja biasa
                        $hariKerja = $presensi['count'] + $presensi['kerja_di_hari_libur_count'] ?? 0;
                        $defaultColumns[] = [
                            'label' => 'Gaji Pokok Hari Biasa - ' . $hariKerja . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                            'factor' => '+',
                            'value' => (float) $d->nominal * (float) $hariKerja,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];

                        // // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
                        // if ($hari_kerja_merah > 0) {
                        //     $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
                        //     $defaultColumns[] = [
                        //         'label' => 'Gaji Pokok Hari Merah - ' . $presensi['kerja_di_hari_libur_count'] .
                        //             ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                        //         'factor' => '+',
                        //         'value' => $hari_kerja_merah * $nominal_hari_merah,
                        //         'type' => 'BULANAN',
                        //         'can_adjust' => 1
                        //     ];
                        // }

                        continue;
                    }
                    // if ($keterangan === "uang makan siang") {
                    //     // if (@$kary->id == 915) {
                    //     //     dump([
                    //     //         'wang makan siang' => $presensi['day'] ?? 0
                    //     //     ]);
                    //     // }
                    //     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
                    //         $presensi['day'] = $t_cuti_approved;
                    //     }
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === "uang makan malam") {
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    if ($keterangan === "uang kerajinan") {
                        $maxKerajinan = 1;
                        $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
                        $hadir = max(0, min($hadir, $maxKerajinan));
                        $percentage = $hadir / $maxKerajinan;
                        $nominalKerajinan = $percentage * (float) @$d->nominal;
                        $defaultColumns[] = [
                            'label' => $d->keterangan,
                            'factor' => '+',
                            'value' => $nominalKerajinan,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    // if ($keterangan === 'uang lembur') {
                    //     $overtimeMinute = $presensiMinute['overtime_minute'];
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
                    //         'factor' => '+',
                    //         'value' => @$d->nominal * $overtimeMinute ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === 'uang lembur hr merah') {

                    //     if ($presensi['kerja_di_hari_libur_count'] > 0) {
                    //         $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                    //     } else {
                    //         $overtimeOnHoliday = 0;
                    //     }

                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
                    //         'factor' => '+',
                    //         'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // if ($keterangan === 'uang transport') {
                    //     $totalUangTransport = $presensi['count'] + $presensi['kerja_di_hari_libur_count'] ?? 0;
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $totalUangTransport . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => @$d->nominal * $totalUangTransport ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // $defaultColumns[] = [
                    //     'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                    //     'factor' => '+',
                    //     'value' => $value * (float) @$d->nominal ?? 0,
                    //     'type' => 'BULANAN',
                    //     'can_adjust' => 1
                    // ];
                }
            }
        }

        if ($presensiMinute['missing_check'] != 0) {
            $missingCheck = $presensiMinute['missing_check'] ?? 0;
            $defaultColumns[] = [
                'label' => "Denda Absensi",
                'factor' => '-',
                'value' => $missingCheck * 5000,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        return $defaultColumns;
    }

    private function factorSalaryOutsourceAdminMandor($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);


        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;

        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");

        //tunjangan
        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {

                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        // $dataResults = $t_kary_salary->get();
        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // $gaji_karyawan = @$t_kary_salary[0]->total;

        $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();

       
        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }

        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);

        //cek telat
        if ($presensiMinute['late_count'] > 0) {
            $lateCount = $presensiMinute['late_count'];
            $lateValue = 15000;
            $defaultColumns[] = [
                'label' => "Denda Telat Kerja " . $lateCount . " Kali",
                'factor' => '-',
                'value' => $lateCount * $lateValue,
                'type' => 'BULANAN',
                'can_adjust' => 1
            ];
        }


        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $jml_hari_sebulan = $att->work_days_in_month;
            $jml_hari_terpilih = $att->work_day_in_week;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
            $libur_nasional = @$att->libur_nasional;
            $cuti_satu_hari = @$att->cuti_satu_hari;

            $total_gaji_libur_nasional = 0;
            $total_7_5_fullweek = 0;
            $totalSundayCheckin = 0;
            $totalSundayFullweek = 0;



            $gaji_pokok_hari = 0;
            $countOfHariLiburCheckin = 0;


            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // Hitung hari kerja biasa dan merah
                        $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                            (float) $presensiMinute['work_minute'] :
                            (float) $presensi['count'];

                         $gaji_menit = $d->tipe_perhitungan == "MENIT" ?
                            (float) $d->nominal :
                            (float) $d->nominal / 8 / 60;

                        //denda pulang cepat
                        if ($presensiMinute['leave_early'] > 0) {
                            $defaultColumns[] = [
                                'label' => "Denda Pulang Cepat - " . $presensiMinute['leave_early'] . " Menit",
                                'factor' => '-',
                                'value' => $presensiMinute['leave_early'] * $gaji_menit,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung hari kerja merah (minggu + hari libur nasional)
                        // $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);
                        $hari_kerja_merah = ($presensi['kerja_di_hari_libur_count'] ?? 0);

                        // Hitung hari kerja biasa (total - hari merah)
                        $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);
                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        // if (@$kary->id == 915) {
                        //     dump([
                        //         't_cuti_approved' => $t_cuti_approved,
                        //         'total_hari_kerja' => $total_hari_kerja,
                        //         'hari_kerja_merah' => $hari_kerja_merah,
                        //         'hari_kerja_biasa' => $hari_kerja_biasa,
                        //         'presensi_count' => $presensi['count'],
                        //         'kerja_di_hari_libur_count' => $presensi['kerja_di_hari_libur_count'] ?? 0,
                        //         'sunday' => $presensi['sunday'] ?? 0
                        //     ]);
                        // }
                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }

                        // Hitung dan tambahkan gaji untuk hari kerja biasa
                        if ($hari_kerja_biasa > 0) {
                            $nominal_hari_biasa = (float) @$d->nominal ?? 0;

                            $total_hari_gapok = $presensi['count'] + $t_cuti_approved;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Biasa - ' . $total_hari_gapok . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $total_hari_gapok * $nominal_hari_biasa,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
                        if ($hari_kerja_merah > 0) {
                            $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Merah - ' . $presensi['kerja_di_hari_libur_count'] .
                                    ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $hari_kerja_merah * $nominal_hari_merah,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        continue;
                    }
                    // if ($keterangan === "uang makan siang") {
                    //     // if (@$kary->id == 915) {
                    //     //     dump([
                    //     //         'wang makan siang' => $presensi['day'] ?? 0
                    //     //     ]);
                    //     // }
                    //     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
                    //         $presensi['day'] = $t_cuti_approved;
                    //     }
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === "uang makan malam") {
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // if ($keterangan === "uang kerajinan") {
                    //     $maxKerajinan = 5;
                    //     $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
                    //     $hadir = max(0, min($hadir, $maxKerajinan));
                    //     $percentage = $hadir / $maxKerajinan;
                    //     $nominalKerajinan = $percentage * (float) @$d->nominal;
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan,
                    //         'factor' => '+',
                    //         'value' => $nominalKerajinan,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // dd($presensiMinute, $presensi['lembur_kerja_di_hari_libur']);
                    if ($keterangan === 'uang lembur') {
                        //$overtimeMinute = $presensiMinute['overtime_minute'] - $presensi['lembur_kerja_di_hari_libur'];
                        $overtimeMinute = $presensi['lembur_kerja_all'] - $presensi['lembur_kerja_di_hari_libur'] ?? 0;

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeMinute ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    if ($keterangan === 'uang lembur hr merah') {

                        if ($presensi['kerja_di_hari_libur_count'] > 0) {
                            $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                        } else {
                            $overtimeOnHoliday = 0;
                        }

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    if ($keterangan === 'management fee') {
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);

                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }
                        //kalau ada leave early hitung 1/2 hari --update dari p adi 1 maret 2026 berlaku untuk semua outsource
                        $halfDay = $presensiMinute['leave_early_count'] / 2;

                        $hari = max(0, $total_hari_kerja + $hari_kerja_merah) - $halfDay;

                        $totalUangManagementFee = ($hari) * @$d->nominal;
                        $defaultColumns[] = [
                            'label' => $d->keterangan,
                            'factor' => '+',
                            'value' => ($totalUangManagementFee * 10) / 100 ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    $defaultColumns[] = [
                        'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                        'factor' => '+',
                        'value' => $value * (float) @$d->nominal ?? 0,
                        'type' => 'BULANAN',
                        'can_adjust' => 1
                    ];
                }
            }
        }

        if ($presensiMinute['missing_check'] != 0) {
            $missingCheck = $presensiMinute['missing_check'] ?? 0;
            $defaultColumns[] = [
                'label' => "Denda Absensi",
                'factor' => '-',
                'value' => $missingCheck * 5000,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        return $defaultColumns;
    }

    private function factorSalaryOutsourceKary($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);


        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;

        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");

        //tunjangan
        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {

                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        // $dataResults = $t_kary_salary->get();
        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // $gaji_karyawan = @$t_kary_salary[0]->total;

        $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();
        
        // if($t_potongan->count())
        // {
        //     foreach ($t_potongan as $d)
        //     {
        //          $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];
        //     }
        // }

        //  if ($t_potongan->count()) {
        //     foreach ($t_potongan as $d) {


        //         $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             ->where('is_active', true)
        //             ->sum('total_hutang') ?? 0;
                
        //         if($hutang > 0){
        //             $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
        //             ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
        //             ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
        //             ->sum('t_final_gaji_det_rincian.value');
                    
        //             $sisa = max($hutang - $paid, 0);

        //             if ($d->percentage) {
        //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
        //             } else {
        //                 $nilai_netto = (float) $d->nilai;
        //             }

        //             if ($sisa > 0) {
        //                 if ($nilai_netto > $sisa) {
        //                     $nilai_netto = $sisa;
        //                 }
        //             } else {
        //                 $nilai_netto = 0;
        //             }

        //             if($nilai_netto > 0){
        //             $defaultColumns[] = [
        //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                 'factor' => '-',
        //                 'value' => $nilai_netto,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1,
        //                 't_potongan_id' => $d->id,
        //             ];
        //             }
        //         }else{
        //             if($d->nilai > 0){
        //                 $defaultColumns[] = [
        //                     'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                     'factor' => '-',
        //                     'value' => $d->nilai,
        //                     'type' => 'BULANAN',
        //                     'can_adjust' => 1,
        //                     't_potongan_id' => $d->id,
        //                 ];  
        //             }
        //         }

        //     }
        // }

        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }

        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);

         //cek telat
        if ($presensiMinute['late_count'] > 0) {
            $lateCount = $presensiMinute['late_count'];
            $lateValue = 15000;
            $defaultColumns[] = [
                'label' => "Denda Telat Kerja " . $lateCount . " Kali",
                'factor' => '-',
                'value' => $lateCount * $lateValue,
                'type' => 'BULANAN',
                'can_adjust' => 1
            ];
        }

        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $jml_hari_sebulan = $att->work_days_in_month;
            $jml_hari_terpilih = $att->work_day_in_week;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
            $libur_nasional = @$att->libur_nasional;
            $cuti_satu_hari = @$att->cuti_satu_hari;

            $total_gaji_libur_nasional = 0;
            $total_7_5_fullweek = 0;
            $totalSundayCheckin = 0;
            $totalSundayFullweek = 0;



            $gaji_pokok_hari = 0;
            $countOfHariLiburCheckin = 0;


            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];
                    $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                            (float) $presensiMinute['work_minute'] :
                            (float) $presensi['count'];

                    $hari_kerja_merah = ($presensi['kerja_di_hari_libur_count'] ?? 0);

                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // Hitung hari kerja biasa dan merah                       
                        
                        $gaji_menit = $d->tipe_perhitungan == "MENIT" ?
                            (float) $d->nominal :
                            (float) $d->nominal / 8 / 60;

                        //denda pulang cepat
                        if ($presensiMinute['leave_early'] > 0) {
                            $defaultColumns[] = [
                                'label' => "Denda Pulang Cepat - " . $presensiMinute['leave_early'] . " Menit",
                                'factor' => '-',
                                'value' => $presensiMinute['leave_early'] * $gaji_menit,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung hari kerja merah (minggu + hari libur nasional)
                        // $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);
                        

                        // Hitung hari kerja biasa (total - hari merah)
                        $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);
                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        // if (@$kary->id == 915) {
                        //     dump([
                        //         't_cuti_approved' => $t_cuti_approved,
                        //         'total_hari_kerja' => $total_hari_kerja,
                        //         'hari_kerja_merah' => $hari_kerja_merah,
                        //         'hari_kerja_biasa' => $hari_kerja_biasa,
                        //         'presensi_count' => $presensi['count'],
                        //         'kerja_di_hari_libur_count' => $presensi['kerja_di_hari_libur_count'] ?? 0,
                        //         'sunday' => $presensi['sunday'] ?? 0
                        //     ]);
                        // }
                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }

                        // Hitung dan tambahkan gaji untuk hari kerja biasa
                        if ($hari_kerja_biasa > 0) {
                            $nominal_hari_biasa = (float) @$d->nominal ?? 0;

                            $total_hari_gapok = $presensi['count'] + $t_cuti_approved;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Biasa - ' . $total_hari_gapok . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $total_hari_gapok * $nominal_hari_biasa,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
                        if ($hari_kerja_merah > 0) {
                            $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Merah - ' . $presensi['kerja_di_hari_libur_count'] .
                                    ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $hari_kerja_merah * $nominal_hari_merah,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        continue;
                    }
                    // if ($keterangan === "uang makan siang") {
                    //     // if (@$kary->id == 915) {
                    //     //     dump([
                    //     //         'wang makan siang' => $presensi['day'] ?? 0
                    //     //     ]);
                    //     // }
                    //     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
                    //         $presensi['day'] = $t_cuti_approved;
                    //     }
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === "uang makan malam") {
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // if ($keterangan === "uang kerajinan") {
                    //     $maxKerajinan = 5;
                    //     $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
                    //     $hadir = max(0, min($hadir, $maxKerajinan));
                    //     $percentage = $hadir / $maxKerajinan;
                    //     $nominalKerajinan = $percentage * (float) @$d->nominal;
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan,
                    //         'factor' => '+',
                    //         'value' => $nominalKerajinan,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    if ($keterangan === 'uang lembur') {
                        //$overtimeMinute = $presensiMinute['overtime_minute'] - $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                        $overtimeMinute = $presensi['lembur_kerja_all'] - $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                        
                        //dd($presensi);
                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja ',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeMinute ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    if ($keterangan === 'uang lembur hr merah') {

                        if ($presensi['kerja_di_hari_libur_count'] > 0) {
                            $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                        } else {
                            $overtimeOnHoliday = 0;
                        }

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    if ($keterangan === 'management fee') {
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        //$hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);

                        //kalau ada leave early hitung 1/2 hari --update dari p adi 1 maret 2026 berlaku untuk semua outsource
                        $halfDay = $presensiMinute['leave_early_count'] / 2;                        
                        $hari_kerja_total = max(0, $total_hari_kerja + $hari_kerja_merah - $halfDay);
                        // dd($hari_kerja_total);
                        // dd($presensiMinute['leave_early_count']);

                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }
                        $totalUangManagementFee = ($hari_kerja_total) * @$d->nominal;
                        $defaultColumns[] = [
                            'label' => $d->keterangan,
                            'factor' => '+',
                            'value' => (float) $totalUangManagementFee  ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    $defaultColumns[] = [
                        'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                        'factor' => '+',
                        'value' => $value * (float) @$d->nominal ?? 0,
                        'type' => 'BULANAN',
                        'can_adjust' => 1
                    ];
                }
            }
        }

        if ($presensiMinute['missing_check'] != 0) {
            $missingCheck = $presensiMinute['missing_check'] ?? 0;
            $defaultColumns[] = [
                'label' => "Denda Absensi",
                'factor' => '-',
                'value' => $missingCheck * 5000,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        return $defaultColumns;
    }

    private function factorSalaryOutsourceDriver($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);


        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;

        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");

        //tunjangan
        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {

                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        // $dataResults = $t_kary_salary->get();
        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // $gaji_karyawan = @$t_kary_salary[0]->total;

        $gaji_hari = $t_kary_salary->firstWhere('keterangan', 'Gaji Pokok');

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();

        // if($t_potongan->count())
        // {
        //     foreach ($t_potongan as $d)
        //     {
        //          $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];
        //     }
        // }

        //  if ($t_potongan->count()) {
        //     foreach ($t_potongan as $d) {


        //         $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             ->where('is_active', true)
        //             ->sum('total_hutang') ?? 0;
                
        //         if($hutang > 0){
        //             $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
        //             ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
        //             ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
        //             ->sum('t_final_gaji_det_rincian.value');
                    
        //             $sisa = max($hutang - $paid, 0);

        //             if ($d->percentage) {
        //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
        //             } else {
        //                 $nilai_netto = (float) $d->nilai;
        //             }

        //             if ($sisa > 0) {
        //                 if ($nilai_netto > $sisa) {
        //                     $nilai_netto = $sisa;
        //                 }
        //             } else {
        //                 $nilai_netto = 0;
        //             }

        //             if($nilai_netto > 0){
        //             $defaultColumns[] = [
        //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                 'factor' => '-',
        //                 'value' => $nilai_netto,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1,
        //                 't_potongan_id' => $d->id,
        //             ];
        //             }
        //         }else{
        //             if($d->nilai > 0){
        //             $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //             ];  
        //             }
        //         }

        //     }
        // }

        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }

        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);
        // dd($presensiMinute);

        //cek telat
        if ($presensiMinute['late_count'] > 0) {
            $lateCount = $presensiMinute['late_count'];
            $lateValue = 15000;
            $defaultColumns[] = [
                'label' => "Denda Telat Kerja " . $lateCount . " Kali",
                'factor' => '-',
                'value' => $lateCount * $lateValue,
                'type' => 'BULANAN',
                'can_adjust' => 1
            ];
        }

        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $jml_hari_sebulan = $att->work_days_in_month;
            $jml_hari_terpilih = $att->work_day_in_week;
            $tidak_masuk_kerja = $att->work_not_present;
            $cuti_reguler = @$att->cuti_reguler;
            $sisa_cuti_reguler = @$att->sisa_cuti_reguler;
            $sisa_cuti_masa_kerja = @$att->sisa_cuti_masa_kerja;
            $potongan_cuti = @$att->potongan_cuti;
            $sisa_cuti = @$sisa_cuti_reguler + $sisa_cuti_masa_kerja;
            $libur_nasional = @$att->libur_nasional;
            $cuti_satu_hari = @$att->cuti_satu_hari;

            $total_gaji_libur_nasional = 0;
            $total_7_5_fullweek = 0;
            $totalSundayCheckin = 0;
            $totalSundayFullweek = 0;



            $gaji_pokok_hari = 0;
            $countOfHariLiburCheckin = 0;


            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];

                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // Hitung hari kerja biasa dan merah
                        $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                            (float) $presensiMinute['work_minute'] :
                            (float) $presensi['count'];
                        
                        $gaji_menit = $d->tipe_perhitungan == "MENIT" ?
                            (float) $d->nominal :
                            (float) $d->nominal / 8 / 60;

                        //denda pulang cepat
                        if ($presensiMinute['leave_early'] > 0) {
                            $defaultColumns[] = [
                                'label' => "Denda Pulang Cepat - " . $presensiMinute['leave_early'] . " Menit",
                                'factor' => '-',
                                'value' => $presensiMinute['leave_early'] * $gaji_menit,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung hari kerja merah (minggu + hari libur nasional)
                        // $hari_kerja_merah = ($presensi['sunday'] ?? 0) + ($presensi['kerja_di_hari_libur_count'] ?? 0);
                        $hari_kerja_merah = ($presensi['kerja_di_hari_libur_count'] ?? 0);

                        // Hitung hari kerja biasa (total - hari merah)
                        $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);
                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        // if (@$kary->id == 915) {
                        //     dump([
                        //         't_cuti_approved' => $t_cuti_approved,
                        //         'total_hari_kerja' => $total_hari_kerja,
                        //         'hari_kerja_merah' => $hari_kerja_merah,
                        //         'hari_kerja_biasa' => $hari_kerja_biasa,
                        //         'presensi_count' => $presensi['count'],
                        //         'kerja_di_hari_libur_count' => $presensi['kerja_di_hari_libur_count'] ?? 0,
                        //         'sunday' => $presensi['sunday'] ?? 0
                        //     ]);
                        // }
                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }

                        // Hitung dan tambahkan gaji untuk hari kerja biasa
                        if ($hari_kerja_biasa > 0) {
                            $nominal_hari_biasa = (float) @$d->nominal ?? 0;

                            $total_hari_gapok = $presensi['count'] + $t_cuti_approved;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Biasa - ' . $total_hari_gapok . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $total_hari_gapok * $nominal_hari_biasa,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        // Hitung dan tambahkan gaji untuk hari merah (rate 2x lipat)
                        if ($hari_kerja_merah > 0) {
                            $nominal_hari_merah = ((float) @$d->nominal ?? 0) * 2;
                            $defaultColumns[] = [
                                'label' => 'Gaji Pokok Hari Merah - ' . $presensi['kerja_di_hari_libur_count'] .
                                    ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $hari_kerja_merah * $nominal_hari_merah,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        continue;
                    }
                    // if ($keterangan === "uang makan siang") {
                    //     // if (@$kary->id == 915) {
                    //     //     dump([
                    //     //         'wang makan siang' => $presensi['day'] ?? 0
                    //     //     ]);
                    //     // }
                    //     if ($presensi['day'] == 0 && $t_cuti_approved > 0) {
                    //         $presensi['day'] = $t_cuti_approved;
                    //     }
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['day'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['day'] * @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    // if ($keterangan === "uang makan malam") {
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan . ' - ' . $presensi['night'] . ' Hari Kerja',
                    //         'factor' => '+',
                    //         'value' => (float) $presensi['night'] * (float) @$d->nominal ?? 0,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }

                    // if ($keterangan === "uang kerajinan") {
                    //     $maxKerajinan = 5;
                    //     $hadir = $maxKerajinan - ($presensi['count_no_record_date'] - ($t_cuti_approved ?? 0));
                    //     $hadir = max(0, min($hadir, $maxKerajinan));
                    //     $percentage = $hadir / $maxKerajinan;
                    //     $nominalKerajinan = $percentage * (float) @$d->nominal;
                    //     $defaultColumns[] = [
                    //         'label' => $d->keterangan,
                    //         'factor' => '+',
                    //         'value' => $nominalKerajinan,
                    //         'type' => 'BULANAN',
                    //         'can_adjust' => 1
                    //     ];
                    //     continue;
                    // }
                    if ($keterangan === 'uang lembur') {
                        //$overtimeMinute = $presensiMinute['overtime_minute'] - $presensi['lembur_kerja_di_hari_libur'];
                        $overtimeMinute = $presensi['lembur_kerja_all'] - $presensi['lembur_kerja_di_hari_libur'] ?? 0;

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeMinute . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeMinute ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }
                    if ($keterangan === 'uang lembur hr merah') {

                        if ($presensi['kerja_di_hari_libur_count'] > 0) {
                            $overtimeOnHoliday = $presensi['lembur_kerja_di_hari_libur'] ?? 0;
                        } else {
                            $overtimeOnHoliday = 0;
                        }

                        $defaultColumns[] = [
                            'label' => $d->keterangan . ' - ' . $overtimeOnHoliday . ' Menit Kerja',
                            'factor' => '+',
                            'value' => @$d->nominal * $overtimeOnHoliday ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    if ($keterangan === 'management fee') {
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();
                        
                        $hari_kerja_biasa = max(0, $total_hari_kerja - $hari_kerja_merah);

                        if ($t_cuti_approved > 0) {
                            $sisa_cuti_satu_hari = $cuti_satu_hari - $t_cuti_approved;
                            if ($sisa_cuti_satu_hari > 0) {
                                $hari_kerja_biasa += $t_cuti_approved;
                            }
                        }
                        // dd($d->nominal);
                        //kalau ada leave early hitung 1/2 hari --update dari p adi 1 maret 2026 berlaku untuk semua outsource
                        $halfDay = $presensiMinute['leave_early_count'] / 2;
                        $hari = max(0, $total_hari_kerja + $hari_kerja_merah) - $halfDay;
                        $totalUangManagementFee = ($hari) * @$d->nominal;
                        $defaultColumns[] = [
                            'label' => $d->keterangan,
                            'factor' => '+',
                            'value' => ($totalUangManagementFee * 9) / 100 ?? 0,
                            'type' => 'BULANAN',
                            'can_adjust' => 1
                        ];
                        continue;
                    }

                    $defaultColumns[] = [
                        'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                        'factor' => '+',
                        'value' => $value * (float) @$d->nominal ?? 0,
                        'type' => 'BULANAN',
                        'can_adjust' => 1
                    ];
                }
            }
        }

        if ($presensiMinute['missing_check'] != 0) {
            $missingCheck = $presensiMinute['missing_check'] ?? 0;
            $defaultColumns[] = [
                'label' => "Denda Absensi",
                'factor' => '-',
                'value' => $missingCheck * 5000,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        return $defaultColumns;
    }


    public function factorSalaryPersonalDriver($kary, $date_from, $date_to, $isTunjangan, $kary_grade)
    {
        //logic kuontol
        $grade = grade::where('id', $kary_grade)->with('treatments')->first();
        $treatments = collect($grade['treatments']);

        $faktor = $treatments->where('is_month', true)
            ->pluck('factor', 'keterangan')
            ->mapWithKeys(function ($faktor, $keterangan) {
                return [strtolower($keterangan) => $faktor];
            })
            ->toArray();

        $bulanan = array_keys($faktor);

        $defaultColumns = [];

        if (!$kary)
            return $defaultColumns;

        $add_1 = 0;
        $t_kary_salary = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*, t_kary_salary.tipe_perhitungan")
            ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where('t_kary_salary.is_active', true)
            ->whereNotIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
            ->whereRaw("LOWER(d.keterangan) NOT ILIKE '%potongan%'");

        if (true) {

            $getTunjangan = t_kary_salary::selectRaw("m_kary_id, t_kary_salary.id, t_kary_salary.total, d.*")
                ->join('t_kary_salary_det as d', 'd.t_kary_salary_id', 't_kary_salary.id')
                ->where('m_kary_id', @$kary->id ?? 0)
                ->whereIn(\DB::raw('LOWER(d.keterangan)'), $bulanan)
                ->where('t_kary_salary.is_active', true)
                ->get();

            foreach ($getTunjangan as $single) {
                $keteranganLower = strtolower($single['keterangan']);
                $factor = $faktor[$keteranganLower] ?? '+';

                $defaultColumns[] = [
                    'label' => $single['keterangan'] . ' (' . $factor . ')',
                    'factor' => $factor,
                    'value' => (float) $single['nominal'],
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
                $add_1 += (float) $single['nominal'];
            }
        }

        $t_kary_salary = $t_kary_salary->get();

        $gaji_karyawan = @$t_kary_salary[0]->total ?? 0;
        // faktor lain :Potongan

        $sisa = 0;

        $t_potongan = t_potongan::with(['t_final_gaji_det_rincian'])
            ->where('m_kary_id', @$kary->id ?? 0)
            ->where(function ($q) use ($date_from, $date_to) {
                $q->where('date_from', '<=', $date_to)
                    ->where('date_to', '>=', $date_from);
            })
            ->where('status', 'POSTED')
            ->get();

        // if($t_potongan->count())
        // {
        //     foreach ($t_potongan as $d)
        //     {
        //          $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];
        //     }
        // }

        //  if ($t_potongan->count()) {
        //     foreach ($t_potongan as $d) {


        //         $hutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
        //             ->where('jenis_potongan_id', $d->jenis_potongan_id)
        //             ->where('is_active', true)
        //             ->sum('total_hutang') ?? 0;
                
        //         if($hutang > 0){
        //             $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
        //             ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
        //             ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
        //             ->sum('t_final_gaji_det_rincian.value');
                    
        //             $sisa = max($hutang - $paid, 0);

        //             if ($d->percentage) {
        //                 $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
        //             } else {
        //                 $nilai_netto = (float) $d->nilai;
        //             }

        //             if ($sisa > 0) {
        //                 if ($nilai_netto > $sisa) {
        //                     $nilai_netto = $sisa;
        //                 }
        //             } else {
        //                 $nilai_netto = 0;
        //             }

        //             if($nilai_netto > 0){
        //             $defaultColumns[] = [
        //                 'label' => "Potongan - $d->nomor ($d->keterangan)",
        //                 'factor' => '-',
        //                 'value' => $nilai_netto,
        //                 'type' => 'BULANAN',
        //                 'can_adjust' => 1,
        //                 't_potongan_id' => $d->id,
        //             ];
        //             }
        //         }else{
        //             if($d->nilai > 0){
        //             $defaultColumns[] = [
        //             'label' => "Potongan - $d->nomor ($d->keterangan)",
        //             'factor' => '-',
        //             'value' => $d->nilai,
        //             'type' => 'BULANAN',
        //             'can_adjust' => 1,
        //             't_potongan_id' => $d->id,
        //         ];  
        //             }
        //         }

        //     }
        // }

        //latest new version hutang and potongan
        if ($t_potongan->count()) {
            foreach ($t_potongan as $key => $d) {
                
                // 1. Logika Fallback untuk Mencari Hutang
                $queryHutang = m_hutang_kary::where('m_kary_id', $d->m_kary_id)
                    ->where('is_active', true);

                // Cek apakah ada hutang yang spesifik ke t_potongan_id ini (Data Baru)
                $hasSpecificDebt = (clone $queryHutang)->where('t_potongan_id', $d->id)->exists();

                if ($hasSpecificDebt) {
                    $hutang = $queryHutang->where('t_potongan_id', $d->id)->sum('total_hutang');
                } else {
                    // Data Lama: Cari berdasarkan jenis_potongan_id seperti sebelumnya
                    $hutang = $queryHutang->where('jenis_potongan_id', $d->jenis_potongan_id)->sum('total_hutang');
                }

                if ($hutang > 0) {
                    // 2. Hitung yang sudah dibayar (Paid)
                    // Tetap filter berdasarkan t_potongan_id agar pembayaran tercatat rapi per periode potongan
                    $paid = t_final_gaji_det_rincian::join('t_final_gaji_det', 't_final_gaji_det.id', '=', 't_final_gaji_det_rincian.t_final_gaji_det_id')
                        ->where('t_final_gaji_det.m_kary_id', $d->m_kary_id)
                        ->where('t_final_gaji_det_rincian.t_potongan_id', $d->id)
                        ->sum('t_final_gaji_det_rincian.value');

                    $sisa = max($hutang - $paid, 0);

                    // 3. Hitung Nilai Netto
                    if ($d->percentage) {
                        $nilai_netto = ((float) $d->nilai * (float) $d->percentage) / 100;
                    } else {
                        $nilai_netto = (float) $d->nilai;
                    }

                    // 4. Validasi Sisa Hutang
                    if ($sisa > 0) {
                        if ($nilai_netto > $sisa) {
                            $nilai_netto = $sisa;
                        }
                    } else {
                        $nilai_netto = 0;
                    }

                    if ($nilai_netto > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $nilai_netto,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                } else {
                    // Kondisi jika bukan potongan hutang (Potongan Reguler)
                    if ($d->nilai > 0) {
                        $defaultColumns[] = [
                            'label' => "Potongan - $d->nomor ($d->keterangan)",
                            'factor' => '-',
                            'value' => $d->nilai,
                            'type' => 'BULANAN',
                            'can_adjust' => 1,
                            't_potongan_id' => $d->id,
                        ];
                    }
                }
            }
        }

        $t_bonus = t_bonus::where('m_kary_id', @$kary->id ?? 0)
            ->whereRaw("date_from >= ? and date_to <= ?", [$date_from, $date_to])
            ->where('status', 'POSTED')
            ->get();

        if (count($t_bonus)) {
            foreach ($t_bonus as $d) {
                $defaultColumns[] = [
                    'label' => "Bonus - $d->nomor ($d->keterangan)",
                    'factor' => '+',
                    'value' => (float) $d->nilai,
                    'type' => 'BULANAN',
                    'can_adjust' => 1,
                ];
            }
        }

        //check data presensi
        $presensi = $this->salaryPresensi(@$kary->id ?? 0, $date_from, $date_to);
        // dd($presensi);

        $presensiMinute = $this->salaryPresensiByMinute(@$kary ?? null, $date_from, $date_to);

        $data = \DB::table(\DB::raw('employee_attendance_detail(?, ?) as detail'))
            ->select('*')
            ->whereBetween('all_days_of_month', [$date_from, $date_to])
            ->where(function ($query) {
                $query
                    ->whereRaw("(absensi::json->>'checkin_time') is not null")
                    ->orWhereRaw("(absensi::json->>'checkout_time') is not null")
                    ->orWhereRaw("(absensi::json->>'status') = 'CUTI'")
                    ->orWhereRaw("(absensi::json->>'status') = 'NOT ATTEND'");
            })
            ->addBinding($date_from, 'select')
            ->addBinding($kary->id, 'select')
            ->get();

        // $hari_kerja = 1;    
        // $expected_salary = 0;
        // foreach ($data as $dt) {
        //     $type = strtolower(trim($dt->type));
        //     if (strpos($type, 'kerja')) {
        //         $hari_kerja++;
        //     }
        // }

        $startDate = Carbon::parse($date_from);
        $endDate = Carbon::parse($date_to);

        // $workDays = [];
        $hari_kerja = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if ($currentDate->format('w') != 0) {
                $hari_kerja++;
            }
            $currentDate->addDay();
        }

        // $hari_kerja = count($workDays);

        // check kehadiran karyawan
        $attendance = \DB::select("select public.employee_attendance_harian(?,?,?)", [$date_from, $date_to, @$kary->id ?? 0]);
        if (count($attendance)) {
            $att = $attendance[0]->employee_attendance_harian;
            $att = json_decode($att);
            $lemburTunjangan = [];
            foreach ($t_kary_salary as $d) {
                if (@$d->nominal != 0 && (strpos(strtolower($d->keterangan), 'lembur') === false)) {

                    $value = $d->tipe_perhitungan == "MENIT" ? (float) $presensiMinute['work_minute'] : (float) $presensi['count'];


                    $keterangan = null;
                    if (isset($d->keterangan) && $d->keterangan !== null) {
                        $keterangan = strtolower(trim(preg_replace('/\s+/', ' ', $d->keterangan)));
                    }
                    if ($keterangan === "gaji pokok") {
                        // Hitung hari kerja biasa dan merah
                        $total_hari_kerja = $d->tipe_perhitungan == "MENIT" ?
                            (float) $presensiMinute['work_minute'] :
                            (float) $presensi['count'];

                        $hari_kerja_biasa = max(0, $total_hari_kerja);

                        // Tambahkan cuti yang disetujui ke hari kerja biasa
                        $t_cuti_approved = t_cuti::where('m_kary_id', @$kary->id ?? 0)
                            ->whereRaw("status = 'APPROVED' and date_from >= ? and date_to <= ?", [$date_from, $date_to])
                            ->count();

                        if ($hari_kerja_biasa > 0) {
                            $nominal_hari_biasa = (float) @$d->nominal ?? 0;

                            //gaji pokok sudah sama ganti cuti
                            $total_hari_gapok = $presensi['count'];

                            // gaji pokok mengikuti jumlah hari kerja aktual pada presensi
                            $expected_salary = $total_hari_gapok * $nominal_hari_biasa;

                            $defaultColumns[] = [
                                'label' =>  'Gaji Pokok - ' . ($total_hari_gapok) . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $expected_salary,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];

                            $subtot_1 = $expected_salary;
                        }
                        if ($t_cuti_approved > 0) {
                            $defaultColumns[] = [
                                'label' =>  'Pengganti Cuti Tahunan - ' . $t_cuti_approved . ($d->tipe_perhitungan == "MENIT" ? ' Menit Kerja' : ' Hari Kerja'),
                                'factor' => '+',
                                'value' => $t_cuti_approved * $nominal_hari_biasa,
                                'type' => 'BULANAN',
                                'can_adjust' => 1
                            ];
                        }

                        continue;
                    }

                    $defaultColumns[] = [
                        'label' => $d->keterangan . ' - ' . $value . ' Hari Kerja',
                        'factor' => '+',
                        'value' => $value * (float) @$d->nominal ?? 0,
                        'type' => 'BULANAN',
                        'can_adjust' => 1
                    ];
                } else {
                    if (strpos(strtolower($d->keterangan), 'lembur') !== false) {
                        $lemburTunjangan[$d->keterangan] = $d->nominal;
                    }
                }
            }

            // gaji perhari
            $gaji_per_hari = $gaji_karyawan;
            $makan_per_hari = 0;
        }

        // $included = [];
        // $excluded = [];
        $subtot_2 = $subtot_1 + $add_1;

        //berdasar subtot 1 ( bpjs tk, bpjs kes )
        // foreach ($treatments as $treat) {
        //     if ($treat->type === 'percentage') {
        //         $is_excluded = in_array(strtolower($treat->keterangan), ['ppn', 'pph', 'management fee']);
        //         if (!$is_excluded) {
        //             $value = $treat->value / 100 * $expected_salary;
        //             $subtot_2 += $value;
        //             $included[] = [
        //                 // 'label' => $treat->keterangan . ' - ' . $treat->value . '%' . ' x Rp' . number_format($subtot_1, 2, ',', '.') . ' (' . $treat->factor . ')',
        //                 'label' => $treat->keterangan . ' (' . $treat->factor . ')',
        //                 'factor' => $treat->factor,
        //                 'value' => $value,
        //                 'type' => 'Bulanan',
        //                 'can_adjust' => 1
        //             ];
        //         }
        //     }
        // }

        //bpjs
        $bpjs = $treatments->filter(function ($treat) {
            return in_array(strtolower($treat->keterangan), ['bpjs tk', 'bpjs kes']);
        });

        if ($bpjs->isNotEmpty()) {
            foreach ($bpjs as $data) {
                $umsk = $this->getBpjsBasis($kary, $date_from);
                $value = $data->value / 100 * $umsk;
                $subtot_2 += $value;
                $defaultColumns[] = [
                    'label' => $data->keterangan . ' ' . $data->value . '% x ' . $umsk . ' (' . $data->factor . ')',
                    'factor' => $data->factor,
                    'value' => $value,
                    'type' => 'Bulanan',
                    'can_adjust' => 1
                ];
            }
        }



        //management fee
        $management_fee = $treatments->filter(function ($treat) {
            return strtolower($treat->keterangan) === 'management fee';
        })->first();

        if ($management_fee) {
            $management_fee_value = $management_fee->value / 100 * $subtot_2;
            $defaultColumns[] = [
                'label' => $management_fee->keterangan . ' (' . $management_fee->factor . ')',
                'factor' => $management_fee->factor,
                'value' => $management_fee_value,
                'type' => 'Bulanan',
                'can_adjust' => 1
            ];
        }

        //pajak
        $taxes = $treatments->filter(function ($treat) {
            return in_array(strtolower($treat->keterangan), ['ppn', 'pph']);
        });

        if ($taxes->isNotEmpty()) {
            foreach ($taxes as $tax) {
                $value = $tax->value / 100 * ($management_fee_value ?? ($subtot_2 * 10 / 100));
                $defaultColumns[] = [
                    // 'label' => $tax->keterangan . ' - ' . $tax->value . '%' . ' x Rp' . number_format($subtot_2, 2, ',', '.') . ' (' . $tax->factor . ')',
                    'label' => $tax->keterangan . ' (' . $tax->factor . ')',
                    'factor' => $tax->factor,
                    'value' => $value,
                    'type' => 'Bulanan',
                    'can_adjust' => 1
                ];
            }
        }
        //berdasar subtot 2 (ppn, pph)
        // foreach ($treatments as $treat) {
        //     if ($treat->type === 'percentage') {
        //         $is_excluded = in_array(strtolower($treat->keterangan), ['ppn', 'pph']);
        //         if ($is_excluded) {
        //             $value = $treat->value / 100 * ($management_fee_value ?? ($subtot_2 * 10 / 100));
        //             $excluded[] = [
        //                 // 'label' => $treat->keterangan . ' - ' . $treat->value . '%' . ' x Rp' . number_format($subtot_2, 2, ',', '.') . ' (' . $treat->factor . ')',
        //                 'label' => $treat->keterangan . ' (' . $treat->factor . ')',
        //                 'factor' => $treat->factor,
        //                 'value' => $value,
        //                 'type' => 'Bulanan',
        //                 'can_adjust' => 1
        //             ];
        //         }
        //     }
        // }

        // $defaultColumns = array_merge($defaultColumns, $included, $excluded);

        $data = \DB::table(\DB::raw('employee_attendance_detail(?, ?) as detail'))
            ->select('*')
            ->whereBetween('all_days_of_month', [$date_from, $date_to])
            ->where(function ($query) {
                $query
                    ->whereRaw("(absensi::json->>'checkin_time') is not null")
                    ->orWhereRaw("(absensi::json->>'checkout_time') is not null")
                    ->orWhereRaw("(absensi::json->>'status') = 'CUTI'")
                    ->orWhereRaw("(absensi::json->>'status') = 'NOT ATTEND'");
            })
            ->addBinding($date_from, 'select')
            ->addBinding($kary->id, 'select')
            ->get();

        $total_lembur_kali = 0;
        $total_lembur_menit = 0;
        $total_bayaran_lembur = 0;

        // Tarif lembur per menit
        $a = $lemburTunjangan['Lembur 60 Menit Pertama'];
        $b = $lemburTunjangan['Lembur 60 Menit Kedua'];
        $c = $lemburTunjangan['Lembur Selanjutnya'];

        foreach ($data as $dt) {
            $absensi = @json_decode($dt->absensi);
            $checkout_time = @$absensi->checkout_time;

            $dt->lembur_menit = 0;
            $dt->bayaran_lembur = 0;

            if ($checkout_time) {
                $waktu_checkout = strtotime($checkout_time);
                $checkout_date = date('Y-m-d', $waktu_checkout);

                $batas_jam_kerja = strtotime("$checkout_date 17:00:00");
                $batas_lembur = strtotime("$checkout_date 17:15:00");

                if ($waktu_checkout < strtotime("$checkout_date 06:00:00")) {
                    $checkout_date = date('Y-m-d', strtotime('-1 day', $waktu_checkout));
                    $batas_jam_kerja = strtotime("$checkout_date 17:00:00");
                    $batas_lembur = strtotime("$checkout_date 17:15:00");
                }

                if ($waktu_checkout > $batas_lembur) {
                    $lembur_menit = floor(($waktu_checkout - $batas_jam_kerja) / 60);
                    $dt->lembur_menit = $lembur_menit;

                    $menit_a = min($lembur_menit, 60);
                    $menit_b = min(max($lembur_menit - 60, 0), 60);
                    $menit_c = max($lembur_menit - 120, 0);

                    $bayaran_lembur = ($menit_a * $a) + ($menit_b * $b) + ($menit_c * $c);
                    $dt->bayaran_lembur = $bayaran_lembur;

                    $dt->bayaran_lembur = $bayaran_lembur;

                    $total_lembur_kali++;
                    $total_lembur_menit += $lembur_menit;
                    $total_bayaran_lembur += $bayaran_lembur;
                }
            }
        }

        $defaultColumns[] = [
            'label' => "Uang Lembur - " . $total_lembur_menit . " Menit",
            'factor' => '+',
            'value' => $total_bayaran_lembur,
            'type' => 'Bulanan',
            'can_adjust' => 1
        ];


        return $defaultColumns;
    }

    public function importUpahErp($startDate = null, $endDate = null, $mKaryId = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate   = $endDate ?? Carbon::now()->format('Y-m-d');

        $jenisBonus = DB::table('m_general')
            ->where('group', 'JENIS BONUS')
            ->where('value', 'UPAH PACKING')
            ->first();

        if (!$jenisBonus) {
            return response()->json(['error' => 'Master Jenis Bonus "UPAH PACKING" tidak ditemukan'], 404);
        }

        $karyawanTarget = null;
        $personId = null;

        if ($mKaryId) {
            $idCari = is_object($mKaryId) ? $mKaryId->id : $mKaryId;

            $karyawanTarget = DB::table('m_kary')->where('id', $idCari)->first();
            
            if (!$karyawanTarget) {
                return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
            }
            $personId = $karyawanTarget->id; 
        }
        

        $apiSources = [
            'PRODUKSI' => "https://app.qqltech.com:7023/public/t_pres_d_hasil/upahPackingHP",
            'SAMPINGAN' => "https://app.qqltech.com:7023/public/t_pres_d_sampingan/upahPackingHS",
            // 'SALES' => "https://app.qqltech.com:7023//public/t_faktur_penjualan/upahPenjualanSales"
        ];

        DB::beginTransaction();

        try {
            $count = 0;

            foreach ($apiSources as $kategori => $url) {
                $response = Http::withOptions(['verify' => false])->get($url, [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'person_id'  => $personId 
                ]);

                //dd($personId, $response->json());

                if ($response->successful()) {
                    $json = $response->json();
                    // dd($json);
                    $dataApi = $json['data'] ?? [];
                    //dd($dataApi);

                    foreach ($dataApi as $item) {
                        $totalUpah = (float) ($item['total_upah'] ?? 0);
                        if ($totalUpah <= 0) continue;

                        // --- PERBAIKAN LOGIKA CURRENT KARY ---
                        $apiPersonId = $item['personal_packing_id'] ?? null;
                        if (!$apiPersonId) continue;

                        $currentKary = null;
                        //dd($karyawanTarget);
                        
                        // Jika kita sudah punya target dan kodenya cocok, gunakan yang ada (hemat query)
                        if ($karyawanTarget && $karyawanTarget->kode == $apiPersonId) {
                            $currentKary = $karyawanTarget;
                        } else {
                            // Cari di DB, pastikan casting ke string untuk menghindari masalah tipe data
                            $currentKary = DB::table('m_kary')
                                ->where('id', $apiPersonId)
                                ->first();
                        }
                        //dd($currentKary);


                        // Jika tetap tidak ketemu, lewati
                        if (!$currentKary) continue;
                        // -------------------------------------

                        $uniqueRef = trim($item['no_penggilingan']) . '|' . 
                                    trim($item['nama_item']) . '|' . 
                                    trim($item['nama_lengkap']);

                        //dd($uniqueRef);

                        // Gunakan ID karyawan yang ditemukan
                        $bonus = DB::table('t_bonus')->updateOrInsert(
                            [
                                'm_kary_id' => $currentKary->id,
                                'no_doc'    => $uniqueRef,
                            ],
                            [
                                'nomor'          => getCore('Helper')->generateNomor('KODE BONUS'),
                                'jenis_bonus_id' => $jenisBonus->id,
                                'date_from'      => $item['date'],
                                'date_to'        => $item['date'],
                                //'no_doc'         => $item['no_penggilingan'],
                                'nilai'          => $totalUpah,
                                'keterangan'     => "UPAH PACKING: " . $item['nama_item'] . " ($kategori)",
                                'status'         => 'POSTED',
                                'is_lunas'       => false,
                                'm_comp_id'      => $currentKary->m_subcomp_id ?? $currentKary->m_comp_id,
                                'm_dir_id'       => $currentKary->m_dir_id,
                                //'creator_id'     => auth()->id() ?? 1,
                                'updated_at'     => Carbon::now(),
                                'created_at'     => Carbon::now(),
                            ]
                        );
                        //dd($bonus);
                        // $dataToUpsert = [];

                        // $dataToUpsert[] = [
                        //     'm_kary_id'      => $currentKary->id,
                        //     'nomor'         => $uniqueRef,
                        //     //'nomor'          => getCore('Helper')->generateNomor('KODE BONUS'),
                        //     'jenis_bonus_id' => $jenisBonus->id,
                        //     'date_from'      => $item['date'],
                        //     'date_to'        => $item['date'],
                        //     // 'no_doc'         => $item['no_penggilingan'],
                        //     'nilai'          => $totalUpah,
                        //     'keterangan'     => "UPAH PACKING: " . $item['nama_item'] . " ($kategori)",
                        //     'status'         => 'APPROVED',
                        //     'is_lunas'       => false,
                        //     //'m_comp_id'      => $currentKary->m_subcomp_id ?? $currentKary->m_comp_id,
                        //     'm_dir_id'       => $currentKary->m_dir_id,
                        //     // 'creator_id'     => auth()->id() ?? 1,
                        //     // 'created_at'     => Carbon::now(),
                        //     // 'updated_at'     => Carbon::now(),
                        // ];
                        
                        $count++;
                    }
                }
            }

            //dd($dataToUpsert);

            // if (!empty($dataToUpsert)) {
            //     t_bonus::upsert($dataToUpsert, ['m_kary_id', 'nomor'], [
            //         //'nomor', 
            //         'jenis_bonus_id', 
            //         'date_from', 
            //         'date_to', 
            //         //'no_doc', 
            //         'nilai', 
            //         'keterangan', 
            //         'status', 
            //         'is_lunas', 
            //         //'m_comp_id', 
            //         'm_dir_id', 
            //         // 'updated_at'
            //     ]);
            // }

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => "Selesai. $count data berhasil disinkronkan."
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }
}
