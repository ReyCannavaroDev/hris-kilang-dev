@php
$req = app()->request;



$raw = \DB::select("
select f.periode_awal, k.kode, f.nomor nomor_gaji, f.periode_akhir, f.desc,k.nama_lengkap, k.nik,kd.nama dir, kdi.nama
divisi, kde.nama dept, r.* from t_final_gaji_det_rincian r
join t_final_gaji_det d on d.id = r.t_final_gaji_det_id
join m_kary k on k.id = d.m_kary_id
join t_final_gaji f on f.id = d.t_final_gaji_id
left join m_dir kd on kd.id = k.m_dir_id
left join m_divisi kdi on kdi.id = k.m_divisi_id
left join m_dept kde on kde.id = k.m_dept_id
where d.m_kary_id = coalesce(?,k.id) and f.id = coalesce(?,f.id)
order by r.seq ;
", [ $req->m_kary_id, $req->f_id ]);


$periode_from = $raw[0]->periode_awal ?? '2023-01-01';
$periode_to = $raw[0]->periode_akhir ?? '2023-12-30';
function formatRupiah($number) {
return 'Rp ' . number_format($number, 0, ',', '.');
}


$collect = collect($raw)->groupBy('kode');
@endphp
@if(count($raw))
@foreach($collect as $single)
<span style="width:700px;text-align:right;font-weight:bold; margin-bottom:20px;">Slip Gaji</span>
<hr>
<div></div>
<table style="border-collapse: collapse; width: 100%; ">
  <tr>
    <td style="width: 70%; float: left;">
      <table style="border-collapse: collapse; width: 100%; font-size: 10px;">
        <tr>
          <td style="width: 25%; font-size: 10pt">NIK</td>
          <td style="font-size: 10pt">: {{ $single[0]->kode }} </td>
        </tr>
        <tr>
          <td style="font-size: 10pt">Karyawan</td>
          <td style="font-size: 10pt">: {{ $single[0]->nama_lengkap }}</td>
        </tr>
        <tr>
          <td style="font-size: 10pt">Direktorat</td>
          <td style="font-size: 10pt">: {{ $single[0]->dir }}</td>
        </tr>
        <tr>
          <td style="font-size: 10pt">Divisi</td>
          <td style="font-size: 10pt">: {{ $single[0]->divisi }}</td>
        </tr>
        <tr>
          <td style="font-size: 10pt">Departemen</td>
          <td style="font-size: 10pt">: {{ $single[0]->dept }}</td>
        </tr>
      </table>
    </td>

    <td style="width: 30%; float: left;">
      <table style="border-collapse: collapse; width: 100%; font-size: 10px;">
        <tr>
          <td style="width: 30%; font-size: 10pt">Nomor Gaji</td>
          <td>: {{ $single[0]->nomor_gaji }}</td>
        </tr>
        <tr>
          <td style="font-size: 10pt">Catatan</td>
          <td>: {{ $single[0]->desc }}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<div></div>
<table style="font-size:10px; width:100%; border: none;">
  <thead style="font-weight:semibold;">
    <tr style="border: none;">
      <td style="font-weight: bold; line-height: 20px;text-align:center; background-color: #c8daf8;">Komponen</td>
      <td style="font-weight: bold; line-height: 20px;text-align:center; background-color: #c8daf8;">Nilai</td>
      <td style="font-weight: bold; line-height: 20px;text-align:center; background-color: #c8daf8;">Catatan</td>
    </tr>
  </thead>
  <tbody>
    @foreach($single as $i => $d)
    @php
    $backgroundColor = $i % 2 === 1 ? '#f8f8f8' : '';
    @endphp
    <tr style="background-color: {{$backgroundColor}}">
      <td style="line-height: 20px;text-align:left;  {{ $d->factor == '=' ? 'font-weight:bold' : '' }}">{{ $d->label }}
      </td>
      <td style="line-height: 20px;text-align:right; {{ $d->factor == '=' ? 'font-weight:bold' : '' }}">{{ ($d->factor
        == '-' ? '(-)' : '').formatRupiah($d->value) }}</td>
      <td style="line-height: 20px;text-align:left;  {{ $d->factor == '=' ? 'font-weight:bold' : '' }}">{{ $d->deskripsi
        }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
<div></div>
<!-- detail -->

@php
$req = app()->request;
$tipe = $req->tipe_report;

$rekap = [];
$dateNow = date('Y-m-d');

// Get dates from the salary period
$date_start = $single[0]->periode_awal; 
$date_end = $single[0]->periode_akhir;

// Format date_start dan date_end ke Y-m-d
$date_start = date('Y-m-d', strtotime($date_start));
$date_end = date('Y-m-d', strtotime($date_end));

// Periode hanya dibentuk jika ada, dan fallback ke date_start jika tidak ada
if ($req->periode) {
$periode = $req->periode.'-'.date('d');
} elseif ($date_start) {
$periode = $date_start;
} else {
$periode = $dateNow;
}


// Get employee ID from kode
$employee_id = \DB::table('m_kary')->where('kode', $single[0]->kode)->value('id');

$data = \DB::select("
select * from employee_attendance_detail(?, ?) detail
where all_days_of_month between ? and ?
and (
(absensi::json->>'checkin_time' is not null) OR
(absensi::json->>'checkout_time' is not null) OR
(absensi::json->>'status' = 'CUTI') OR
(absensi::json->>'status' = 'NOT ATTEND')
)
", [ $date_start, $employee_id, $date_start, $date_end ]);



$kary_id = $employee_id;

$jadwal_off_kary = \DB::select("
SELECT
l.tanggal_mulai,
l.tanggal_akhir,
l.\"desc\" as keterangan
FROM t_libur l
JOIN t_libur_d tld ON tld.t_libur_id = l.id
WHERE
is_active = true AND
tld.m_kary_id = ? AND
l.tanggal_mulai <= ? AND
    l.tanggal_akhir>= ?
    ", [$kary_id, $date_end, $date_start]);


    $jadwal_off_dates = collect($jadwal_off_kary)->pluck('tanggal')->toArray();

    $check_kary_jam_kerja_tipe = \DB::table('m_kary as k')->join('m_general as g','g.id','k.tipe_jam_kerja_id')
    ->where('k.id', $kary_id)->pluck('g.code')->first();

    $check_kary_jam_kerja_tipe_id = \DB::table('m_kary as k')->join('m_general as g','g.id','k.tipe_jam_kerja_id')
    ->where('k.id', $kary_id)->pluck('k.tipe_jam_kerja_id')->first();

    $rekap = \DB::select("
    select
    employee_attendancew(?, k.id, ?, ?, ?) absen,
    (select
    TO_CHAR(INTERVAL '1 second' * AVG(EXTRACT(EPOCH FROM pa.checkin_time::TIME)), 'HH24:MI:SS')
    from presensi_absensi pa
    where pa.default_user_id = u.id
    and pa.checkin_time is not null
    and pa.tanggal BETWEEN ? AND ?) checkin_avg,
    (select
    TO_CHAR(INTERVAL '1 second' * AVG(EXTRACT(EPOCH FROM pa.checkout_time::TIME)), 'HH24:MI:SS')
    from presensi_absensi pa
    where pa.default_user_id = u.id
    and pa.checkout_time is not null
    and pa.tanggal BETWEEN ? AND ?) checkout_avg,
    k.id, kode, nama_lengkap, d.nama dept
    from m_kary k
    join default_users u on u.m_kary_id = k.id
    join m_dept d on d.id = k.m_dept_id
    where k.is_active = true
    and k.m_dept_id IS NOT NULL and k.m_dept_id != 0
    and k.id = COALESCE(?, k.id)
    ",[
    $periode, date('Y-m-d'), $date_start, $date_end, // Parameters for employee_attendance
    $date_start, $date_end, // Parameters for checkin_avg
    $date_start, $date_end, // Parameters for checkout_avg
    $kary_id
    ]);

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

    // Add query to get cuti data
    $cuti_data = \DB::select("
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
    ", [$kary_id, $date_start, $date_end, $date_start, $date_end]);

    $cuti_dates = [];
    foreach($cuti_data as $cuti) {
    $start = strtotime($cuti->date_from);
    $end = strtotime($cuti->date_to);
    while($start <= $end) {
        $cuti_dates[date('Y-m-d', $start)]=$cuti->alasan_cuti;
        $start = strtotime('+1 day', $start);
        }
        }

        // Count hari kerja
        foreach ($data as $dt) {
        $type = strtolower(trim($dt->type));
        $absensi = @json_decode($dt->absensi);
        $status = isset($absensi->status) ? strtoupper(trim($absensi->status)) : null;
        $checkin_time = @$absensi->checkin_time;
        $checkout_time = @$absensi->checkout_time;
        $current_date = $dt->all_days_of_month;

        // Check if the date is in cuti_dates
        if (isset($cuti_dates[$current_date]) && $type === 'hari kerja') {
        $type = 'CUTI';
        $dt->type = 'CUTI';
        $dt->desc_type = $cuti_dates[$current_date];
        }

        // Count hari kerja
        if ($type === 'hari kerja') {
        $total_hari_kerja++;

        // Check attendance status
        if ($status === 'CUTI' || $status === 'IJIN') {
        $total_cuti++;
        } else if ($checkin_time && $checkout_time) {
        $total_hadir++;
        } else {
        $total_alpha++;
        }
        } elseif ($type === 'cuti') {
        $total_cuti++;
        }
        }


        @endphp

        <table style="width: 100%; border-collapse: collapse;">
            <!-- <pre>@json($jadwal_off_kary, JSON_PRETTY_PRINT)</pre> -->
            <tr>
                <td style="font-weight: bold; font-size: 10pt;">Absensi Karyawan Detail</td>
            </tr>
            <tr>
                <td style="font-weight: bold; font-size: 7pt;">
                    {{ @json_decode(@$data[0]->kary)->nik }} - {{ @json_decode(@$data[0]->kary)->nama_lengkap }}
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold; font-size: 7pt;">
                    Periode: {{ date('d/m/Y', strtotime($date_start)) }} s/d {{ date('d/m/Y', strtotime($date_end)) }}
                </td>
            </tr>
        </table>

        <table style="width: 100%; font-size: 7pt" cellpadding="2">
            <thead class="bg-[#c6c6c6]">
                <tr>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 10%;">
                        Tanggal</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">
                        Tipe Hari</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 8%; text-align: center;">
                        Status</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 15%; text-align: center;">
                        Checkin Time</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 15%; text-align: center;">
                        Breakout</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 10%; text-align: center;">
                        Breakin</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 10%; text-align: center;">
                        Checkout Time</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 8%; text-align: center;">
                        Telat (Menit)</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 8%; text-align: center;">
                        Lembur (Menit)</th>
                    <th
                        style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; background-color: #c6c6c6; width: 8%; text-align: center;">
                        Break (Menit)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $dt)
                @php
                if (strtolower($check_kary_jam_kerja_tipe) == 'office') {
                $jadwal = \DB::table('m_jam_kerja as t')
                ->where('t.is_active', 'true')
                ->where('t.tipe_jam_kerja_id', $check_kary_jam_kerja_tipe_id) // contoh filter berdasarkan tipe jam kerja
                ->first();
                } else {
                $jadwal = \DB::table('m_jam_kerja as t')
                ->where('t.is_active', 'true')
                ->where('t.tipe_jam_kerja_id', $check_kary_jam_kerja_tipe_id)
                ->first();
                }


                $waktu_mulai = strtotime(@$jadwal->waktu_mulai);
                $waktu_checkin = strtotime(@json_decode($dt->absensi)->checkin_time);


                // Initialize variables
                $telat_menit = 0;
                $lembur_menit = 0;
                $break_menit = 0;

                // HITUNG TELAT CHECKIN
                $checkin_result = @json_decode($dt->absensi)->checkin_time;
                if(@json_decode($dt->absensi)->checkin_time != null && @$jadwal->waktu_mulai){
                $selisih_detik = $waktu_mulai - $waktu_checkin;
                $late = floor(abs($selisih_detik / 60)); // Dibulatkan ke bawah
                if($waktu_mulai < $waktu_checkin) {
                    $checkin_result=@json_decode($dt->absensi)->checkin_time . ' <span style="color: red">('.$late.' Menit )</span>';
                    if (strtolower($dt->type) === 'hari kerja') {
                    $total_checkin_telat += $late;
                    $telat_menit = $late;
                    }
                    } else {
                    $checkin_result = @json_decode($dt->absensi)->checkin_time;
                    if (strtolower($dt->type) === 'hari kerja') {
                    $total_checkin_lebih_awal += $late;
                    }
                    }
                    }

                    // HITUNG TELAT CHECKOUT dan LEMBUR
                    $checkout_result = @json_decode($dt->absensi)->checkout_time;
                    $waktu_checkout = strtotime(@json_decode($dt->absensi)->checkout_time);

                    if(@json_decode($dt->absensi)->checkout_time != null) {
                    $checkout_date = date('Y-m-d', $waktu_checkout);
                    $batas_jam_kerja = strtotime($checkout_date . ' 17:00:00');
                    $batas_lembur = strtotime($checkout_date . ' 17:15:00');

                    if ($waktu_checkout < $waktu_mulai) {
                        $checkout_date=date('Y-m-d', strtotime('-1 day', $waktu_checkout));
                        $batas_jam_kerja=strtotime($checkout_date . ' 17:00:00' );
                        $batas_lembur=strtotime($checkout_date . ' 17:15:00' );
                        }

                        if($waktu_checkout> $batas_lembur) {
                            $lembur_menit = floor(($waktu_checkout - $batas_jam_kerja) / 60); // Dibulatkan ke bawah
                            $checkout_result = @json_decode($dt->absensi)->checkout_time .
                            ' <span style="color: blue">('.$lembur_menit.' Menit Lembur)</span>';
                            if (strtolower($dt->type) === 'hari kerja') {
                                $total_lembur_hari_biasa += $lembur_menit;
                            } else {
                                $total_lembur_hari_merah += $lembur_menit;
                            }
                            $total_checkout_telat += $lembur_menit;
                        } else {
                            $checkout_result = @json_decode($dt->absensi)->checkout_time;
                            $lembur_menit = 0;
                        }
                        }

                        // HITUNG DURASI ISTIRAHAT
                        $breakout_time = @json_decode($dt->absensi)->checkout_istirahat_time;
                        $breakin_time = @json_decode($dt->absensi)->checkin_kerja_time;

                        if ($breakout_time && $breakin_time) {
                        $waktu_breakout = strtotime($breakout_time);
                        $waktu_breakin = strtotime($breakin_time);
                        $break_menit = floor(($waktu_breakin - $waktu_breakout) / 60); // Dibulatkan ke bawah
                        }
                        @endphp 
                        @php
                        $is_off = false;
                        $off_desc = '';
                        foreach ($jadwal_off_kary as $off) {
                        $current_date = $dt->all_days_of_month;
                        // Check if current date falls between tanggal_mulai and tanggal_akhir
                        if ($current_date >= $off->tanggal_mulai && $current_date <= $off->tanggal_akhir) {
                            $is_off = true;
                            $off_desc = $off->keterangan;
                            break;
                            }
                            }
                            $row_style = $is_off ? 'background-color: #ffebee;' : '';
                            @endphp
                            <tr style="<?php echo $row_style ? $row_style : ''; ?>">
                                <td style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 10%;">{{
            $dt->day_name_idn }}, {{$dt->date_to_idn}}</td>
                                <td style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 6%;">
                                    @if($dt->type == 'CUTI')
                                    <span style="color: #4CAF50;">CUTI</span>
                                    @if($dt->desc_type)
                                    <br><small>({{ $dt->desc_type }})</small>
                                    @endif
                                    @else
                                    {{ $dt->type }}
                                    @if($dt->type != 'Hari Kerja')
                                    <span style="color: #FF6600;">({{ $dt->desc_type }})</span>
                                    @endif
                                    @endif
                                </td>

                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 8%; text-align: center;">
                                    @if($is_off)
                                    <span style="color: red;">OFF</span>
                                    @if($off_desc)
                                    <br><small>({{ $off_desc }})</small>
                                    @endif
                                    @else
                                    {{ json_decode($dt->absensi)->status === 'ATTEND' ? 'HADIR' : 'TIDAK HADIR' }}
                                    @endif
                                </td>

                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 15%; text-align: center;">
                                    @if($is_off)
                                    -
                                    @else
                                    {!! $checkin_result !!}
                                    @endif
                                </td>
                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 15%; text-align: center;">
                                    {{ @json_decode($dt->absensi)->checkout_istirahat_time ?? '-' }}
                                </td>
                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 10%; text-align: center;">
                                    {{ @json_decode($dt->absensi)->checkin_kerja_time ?? '-' }}
                                </td>
                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 10%; text-align: center;">
                                    {!! $checkout_result !!}</td>
                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 8%; text-align: center; color: {{ $telat_menit > 0 ? 'red' : 'inherit' }}">
                                    {{ $telat_menit > 0 ? $telat_menit : '-' }}
                                </td>
                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 8%; text-align: center;">
                                    {{ $lembur_menit > 0 ? $lembur_menit : '-' }}
                                </td>
                                <td
                                    style="border:0.5px solid black; padding: 2px; font-size: 7pt; border-collapse: collapse; width: 8%; text-align: center;">
                                    {{ $break_menit > 0 ? $break_menit : '-' }}
                                </td>
                            </tr>
                            @endforeach
                            <tr style="font-weight: bold; background: #f0f0f0;">
                                <td colspan="7" style="border:0.5px solid black; text-align: center;">Total</td>
                                <td style="border:0.5px solid black; text-align: center; color: red;">{{ $total_checkin_telat }}</td>
                                <td style="border:0.5px solid black; text-align: center;">
                           {{ $total_lembur_hari_biasa }}/<span style="color: red;">{{ $total_lembur_hari_merah }}</span>

                                </td>
                                <td style="border:0.5px solid black; text-align: center;">
                                    @php
                                    $total_break_menit = 0;
                                    foreach ($data as $dt) {
                                        $breakout_time = @json_decode($dt->absensi)->checkout_istirahat_time;
                                        $breakin_time = @json_decode($dt->absensi)->checkin_kerja_time;
                                        if ($breakout_time && $breakin_time) {
                                            $waktu_breakout = strtotime($breakout_time);
                                            $waktu_breakin = strtotime($breakin_time);
                                            $total_break_menit += round(($waktu_breakin - $waktu_breakout) / 60);
                                        }
                                    }
                                    echo $total_break_menit;
                                    @endphp
                                </td>
                            </tr>
            </tbody>
        </table>

        <table style="width: 100%; font-size: 7pt">
            <tbody>
                <tr>
                    <td style="color: red; width: 7%">Total Checkin Telat</td>
                    <td style="width: 1%">:</td>
                    <td style="width: 20%">{{ round($total_checkin_telat/60).' Jam ('.$total_checkin_telat.' Menit)' }}</td>
                    <td style="width: 7%">Hari Kerja</td>
                    <td style="width: 1%">:</td>
                    <td style="width: 20%">{{ $total_hari_kerja }}</td>
                </tr>
                <tr>
                    <td style="color: red">Total Checkout Lebih Awal</td>
                    <td style="width: 1%">:</td>
                    <td style="width: 20%">{{ round($total_checkout_lebih_awal/60).' Jam ('.$total_checkout_lebih_awal.' Menit)' }}
                    </td>
                    <td>Hadir</td>
                    <td style="width: 1%">:</td>
                    <td>{{ $total_hadir }}</td>
                </tr>
                <tr>
                    <td>Total Checkin Lebih Awal</td>
                    <td style="width: 1%">:</td>
                    <td style="width: 20%">{{ round($total_checkin_lebih_awal/60).' Jam ('.$total_checkin_lebih_awal.' Menit)' }}</td>
                    <td>Ijin / Cuti</td>
                    <td style="width: 1%">:</td>
                    <td>{{ $total_cuti }}</td>
                </tr>
                <tr>
                    <td>Total Checkout Telat</td>
                    <td style="width: 1%">:</td>
                    <td style="width: 20%">{{ round($total_checkout_telat/60).' Jam ('.$total_checkout_telat.' Menit)' }}</td>
                    <td>Alpha</td>
                    <td style="width: 1%">:</td>
                    <td>{{ $total_alpha }}</td>
                </tr>
                <tr>
                    <td>Rata-rata Jam Checkin</td>
                    <td style="width: 1%">:</td>
                    <td>{{ @$rekap[0]->checkin_avg ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Rata-rata Jam Checkout</td>
                    <td style="width: 1%">:</td>
                    <td>{{ @$rekap[0]->checkout_avg ?? '-' }}</td>
                </tr>
            </tbody>
        </table>


@endforeach
@endif