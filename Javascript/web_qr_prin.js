import { useRouter, useRoute, RouterLink } from 'vue-router'
import { ref, readonly, reactive, inject, onMounted, onBeforeMount, onBeforeUnmount, watchEffect, onActivated } from 'vue'

const router = useRouter()
const route = useRoute()
const store = inject('store')
const swal = inject('swal')
const params = new URLSearchParams(window.location.search);
const id = params.get('id');
const endpointApi = '/default_users'

if (id) {
  const img = document.getElementById('qrImage');
  const tokenType = 'Bearer';
  const token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiYzkyYjNmM2ViMjJkMGEzMDdhNDlmNzgwNTc1ODU2YTY3MmFkNDRjYTI2YTBhNTE0Y2MyZmZjMGEzYjg5ZDBiZmJkYTQ0YjkyZTgxOTA2YzkiLCJpYXQiOjE3NDY4NTkxODkuNTAzNjEyLCJuYmYiOjE3NDY4NTkxODkuNTAzNjE1LCJleHAiOjE3NzgzOTUxODkuNDk3MTcyLCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.oebbcnWUoTQ8XALKpAytgfCFB_0BeqTiRpiQ7roY1dh83eFeYccBikcuyJ676WqcPD56i6LKEc1rmXSSYZbN6Zp7BYeW7D-AiZko90-3PzLvjDpB9goioNTY27rFvztlJbomD5ADR1kns_YholWgilHpt08IHL0jQtP01YWthFzOgIIjlHoewhEXY4sjAIYR6ORaxDRg10dzrVMMlJAMXN4341ArL8aaS-wLXTd7UQ9A8WoCoIN90GpEFQ16huvgGDqVii8SbHZMy14CoygOID07JBo7UwJbiOMt_GXXqz2fD0qkjOY7sddHEgYNFq8tVu2Fa1KYN2IdAhfVucaJ_gkpHS4lN63ECGagjBiX-Eh0ygtjpV-ZHO8zZ54FZAG5_mizn_M_RQnxV30O9KVl51hZWt-QjR5MkWg1JkjvneF2-5Elmg41z-zqx104Fisa5z-7Ey1h23U_0pJpciXVcWE_H-rMFlI1e8rd4k2Wq6_o6ibbrPUbb80zalnVagCBaoom8lj0TC5rAAIJXOvymy2bz8Kjymp-etvxtmZeM0Q-hQZsvuC3GAl2_6FAeIJkJguNWvVAdpSNY7yNGVWpH8ubiWNRXeBzg_DdHgA_BdxAIWiS_BmQgWqyU4-VNXaucfGVVhUWjc5xMN7t_TT0iWa5P-7Cie_C8gciTQOv8tI';

  const namaElem = document.querySelector('.details p:nth-child(1)');
  const nikElem = document.querySelector('.details p:nth-child(2)');

  if (!tokenType || !token) {
    console.error("Token tidak ditemukan!");
  } else {
    const url = `${store.server.url_backend}/operation${endpointApi}/generateQRCode?id=${id}`;

    console.log('Token Type:', tokenType);
    console.log('Token:', token);
    console.log('Request URL:', url);

    fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `${tokenType} ${token}`
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