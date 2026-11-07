@verbatim
<body>
  @php
  $req = app()->request;
  $data = \DB::select("select * from default_users du where du.id = ?",[$req->id]);
  @endphp
  <div class="frame">
    <img src="https://public-cdn.is3.cloudhost.id/kilang-padi-logo3.png" class="h-20 inline-block mb-2" />
    <div class="top">
      <p>Juru Sortir</p>
    </div>
    <img id="qrImage" alt="QR Code Karyawan" />
    <div class="details">
      <p>Nama : {{$data[0]->name}}</p>
      <p>Nik : -</p>
    </div>
  </div>

  <button id="printButton" class="print-button">Print</button>
</body>
@endverbatim