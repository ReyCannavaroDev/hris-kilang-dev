<style>
  body {
    margin: 0;
    padding: 0;
    background-color: #f0f0f0;
    overflow: hidden;
  }

  .frame {
    width: 6cm;
    height: 8cm;
    position: absolute;
    top: 10;
    left: 10;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    border: 1px solid #000;
    padding: 10px;
    background-color: white;
    box-sizing: border-box;
    overflow: hidden;
  }

  h1 {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
  }

  img {
    width: 55%;
    height: auto;
    margin-bottom: 10px;
  }

  .details {
    align-self: flex-start;
    text-align: left;
    margin-top: 10px;
    font-size: 14px;
  }

  .top {
    align-items: center;
    margin-bottom: 20px;
  }

  .print-button {
    padding: 10px 20px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    position: absolute;
    top: 10px;
    right: 10px;
  }

  .print-button:hover {
    background-color: #45a049;
  }

  p {
    margin: 0;
  }

  @media print {
    body {
      margin: 0;
      padding: 0;
      background-color: white;
      overflow: hidden;
    }

    .print-button {
      display: none;
    }

    .frame {
      box-shadow: none;
      page-break-inside: avoid;
      position: absolute;
      top: 10px;
      left: 10px;
    }

    @page {
      margin: 0;
      size: landscape;
    }
  }
</style>


<body>
  @php
  $req = app()->request;
  $data = \DB::select("select du.username, md.nama as nama_dept, mkdk.ktp_no from default_users du 
  join m_kary mk on du.m_kary_id = mk.id
  join m_dept md on mk.m_dept_id = md.id
  join m_kary_det_kartu mkdk on mkdk.m_kary_id = mk.id
   where du.id = ?",[$req->id]);
  @endphp
  <div class="frame">
    <img src="https://public-cdn.is3.cloudhost.id/kilang-padi-logo3.png" class="h-20 inline-block mb-2" />
    <div class="top">
      <p>{{$data[0]->nama_dept}}</p>
    </div>
    <img id="qrImage" alt="QR Code Karyawan" />
    <div class="details">
      <p>Nama : {{$data[0]->username}}</p>
      <p>Nik : {{$data[0]->ktp_no ?? "-"}} </p>
    </div>
  </div>
  <button id="printButton" class="print-button">Print</button>
</body>



<script>
const params = new URLSearchParams(window.location.search);
const id = params.get('id');

if (id) {
  const img = document.getElementById('qrImage');
  const tokenType = '1';
  const token = '1';
  
    const namaElem = document.querySelector('.details p:nth-child(1)');
    const nikElem = document.querySelector('.details p:nth-child(2)');

  if (!tokenType || !token) {
    console.error("Token tidak ditemukan!");
  }else {
      const url = `https://server.qqltech.com:7027/public/default_users/generateQRCode?id=${id}`;

      //console.log('Token Type:', tokenType);
      //console.log('Token:', token);
      //console.log('Request URL:', url);
      const token = localStorage.getItem('token');
      console.log('token');

      fetch(url, {
        method: 'GET',
        headers: {
          //'Authorization': `${tokenType} ${token}`
        }
      })
      .then(res => {
        if (!res.ok) {
          throw new Error(`Gagal mengambil data QR, status: ${res.status}`);
        }
        return res.blob();
      })
      .then(blob => {
        const objectURL = URL.createObjectURL(blob);
        img.src = objectURL;
      })
      .catch(error => console.error('Error:', error));
    }
  }

  document.getElementById('printButton').addEventListener('click', function () {
    window.print();
    });
</script>