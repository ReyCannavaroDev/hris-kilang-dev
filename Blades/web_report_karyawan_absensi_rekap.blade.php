@php
$req = app()->request;
$tipe = $req->tipe_report;
use App\Models\CustomModels\t_libur;
use App\Models\CustomModels\m_libur_nasional;
use App\Models\CustomModels\t_cuti;



$date_start = $req->date_start;
$date_end = $req->date_end;
$dateNow = date('Y-m-d');

$startDate = Carbon::parse($req->date_start);
$endDate = Carbon::parse($req->date_end);

$hari_kerja = 0;
$currentDate = $startDate->copy();


$karyawan = \App\Models\CustomModels\m_kary::query()
    ->where('is_active', true)
    ->with(['m_divisi', 'm_dept'])
    ->when($req->filled('m_divisi_id'), fn($q) => $q->where('m_divisi_id', $req->m_divisi_id))
    ->when($req->filled('m_dept_id'), fn($q) => $q->where('m_dept_id', $req->m_dept_id))
    ->when($req->filled('kary_id'), fn($q) => $q->where('id', $req->kary_id))
    ->get();

while ($currentDate->lte($endDate)) {
    if ($currentDate->format('w') != 0) {
        $hari_kerja++;
    }
    $currentDate->addDay();
}

@endphp
<table style="font-size: 10pt; font-weight: bold;">
    <tr>
        <td style="font-size: 10pt;">{{$tipe}}</td>
    </tr>
    <tr>
        <td style="font-size: 9pt;">Periode {{ $date_start }} s/d {{ $date_end }}</td>
    </tr>
</table>

<!-- <table style="font-size: 8pt; font-weight: bold;">
    <tr>
        <td style="font-style: italic;">HK</td>
        <td>: Hari Kerja</td>
    </tr>
    <tr>
        <td style="font-style: italic;">H</td>
        <td>: Masuk Kerja</td>
    </tr>
    <tr>
        <td style="font-style: italic;">I</td>
        <td>: Ijin / Cuti</td>
    </tr>
    <tr>
        <td style="font-style: italic;">A</td>
        <td>: Tidak Masuk Kerja / Tanpa Keterangan</td>
    </tr>
    <tr>
        <td style="font-style: italic;">CI AVG</td>
        <td>: Rata-rata Jam Checkin</td>
    </tr>
    <tr>
        <td style="font-style: italic;">CO AVG</td>
        <td>: Rata-rata Jam Checkout</td>
    </tr>
</table> -->

<table>
  <tr>
    <td></td>
  </tr>
</table>

<table v-else class="table-auto w-full" cellpadding="3">
    <thead class="bg-[#c6c6c6]">
        <tr>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6;">NIK</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 25%;">Karyawan</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 27%;">Departemen</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">Hari Kerja</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">Hadir</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">Ijin / Cuti</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">Absen</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">Off</th>
            <th style="border:0.5px solid black; text-align: center; padding: 2px; font-size: 9pt; border-collapse: collapse; background-color: #c6c6c6; width: 6%;">Libur Nasional</th>

        </tr>
    </thead>
    <tbody>
        @foreach( $karyawan as $kary)
          @php

          $kary->hari_kerja = $hari_kerja;

           $offAll = t_libur::with(['t_libur_d' => function ($q) {
                $q->select('id', 't_libur_id');
             }])
            ->whereHas('t_libur_d', function($q) use ($kary){
                $q->where('m_kary_id', $kary->id);
            })
            ->whereDate('tanggal_mulai', '<=', $req->date_end)
            ->whereDate('tanggal_akhir', '>=', $req->date_start)
            ->get();

            $libur = m_libur_nasional::whereBetween('tanggal', [$date_start, $date_end])
            ->where('is_active', true)
            ->count();


            $cutiAll = t_cuti::where('m_kary_id', $kary->id)
            ->whereDate('date_from', '<=', $req->date_end)
            ->whereDate('date_to', '>=', $req->date_start)
            ->get();

        $data = \DB::table(\DB::raw('employee_attendance_detail_range_new(?, ?, ?) as detail'))
        ->select('*')
        ->whereBetween('all_days_of_month', [$date_start, $date_end])
        ->where(function ($query) {
            $query->whereRaw("(absensi::json->>'checkin_time') is not null")
                ->orWhereRaw("(absensi::json->>'checkout_time') is not null")
                ->orWhereRaw("(absensi::json->>'status') = 'CUTI'")
                ->orWhereRaw("(absensi::json->>'status') = 'NOT ATTEND'");
        })
        ->addBinding($date_start, 'select')
        ->addBinding($date_end, 'select')
        ->addBinding($kary->id, 'select')
        ->get();

        $off_dates = [];
        foreach ($offAll as $off) {
            $mulai = \Carbon\Carbon::parse($off->tanggal_mulai);
            $akhir = \Carbon\Carbon::parse($off->tanggal_akhir);
            while ($mulai <= $akhir) {
                $off_dates[$mulai->format('Y-m-d')] = true;
                $mulai->addDay();
            }
        }   

        $cuti_dates = [];
        foreach ($cutiAll as $cuti) {
            $mulai = \Carbon\Carbon::parse($cuti->date_from);
            $akhir = \Carbon\Carbon::parse($cuti->date_to);
            while ($mulai <= $akhir) {
                $cuti_dates[$mulai->format('Y-m-d')] = true;
                $mulai->addDay();
            }
        }      

          $total_hadir = 0;
          $total_cuti_ijin = 0;
          $total_alpha = 0;
          $total_off = 0;
          foreach ($data as $dt) {
            $type = strtolower(trim($dt->type));
            $absensi = @json_decode($dt->absensi);
            $status = isset($absensi->status) ? strtoupper(trim($absensi->status)) : null;
            $checkin_time = @$absensi->checkin_time;
            $checkout_time = @$absensi->checkout_time;
            $current_date = $dt->all_days_of_month;
            if ($type === 'hari kerja') {
                if ($checkin_time && $checkout_time) {
                    $total_hadir++;
                } else {
                  if (isset($cuti_dates[$current_date])) {
                    $total_cuti_ijin++;
                  }elseif (!isset($off_dates[$current_date])) {
                    $total_alpha++;
                  }
                }
            }  elseif($status === 'ATTEND'){
                $total_hadir++;
                if($type === 'hari libur'){
                  $kary->hari_kerja++;
                }
            }
          }
          @endphp
          <tr>
            <td style="border:0.5px solid black; padding: 2px; font-size: 9pt; border-collapse: collapse;">{{ $kary->kode }}</td>
            <td style="border:0.5px solid black; padding: 2px; font-size: 9pt; border-collapse: collapse; width: 25%;">{{ $kary->nama_lengkap }}</td>
            <td style="border:0.5px solid black; padding: 2px; font-size: 9pt; border-collapse: collapse; width: 27%;">{{$kary->m_dept->nama}}</td>
            <td style="border:0.5px solid black; padding: 2px; text-align: center; font-size: 9pt; border-collapse: collapse; text-align: center; width: 6%">{{ $kary->hari_kerja }}</td>
            <td style="border:0.5px solid black; padding: 2px; text-align: center; font-size: 9pt; border-collapse: collapse; text-align: center; width: 6%;">{{$total_hadir }}</td>
            <td style="border:0.5px solid black; padding: 2px; text-align: center; font-size: 9pt; border-collapse: collapse; text-align: center; width: 6%;">{{ $total_cuti_ijin }}</td>
            <td style="border:0.5px solid black; padding: 2px; text-align: center; font-size: 9pt; border-collapse: collapse; text-align: center; width: 6%;">{{ $total_alpha }}</td>
            <td style="border:0.5px solid black; padding: 2px; text-align: center; font-size: 9pt; border-collapse: collapse; text-align: center; width: 6%;">{{ $offAll->count() }}</td>
            <td style="border:0.5px solid black; padding: 2px; text-align: center; font-size: 9pt; border-collapse: collapse; text-align: center; width: 6%;">{{ $libur }}</td>
        </tr>
        @endforeach
    </tbody>
</table>