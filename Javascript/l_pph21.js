import { useRouter, useRoute } from 'vue-router';
import { ref, reactive, inject, onBeforeMount, watchEffect } from 'vue';

const router = useRouter();
const route = useRoute();
const store = inject('store');
const swal = inject('swal');

const isRequesting = ref(false);
const modulPath = route.params.modul;
const currentMenu = store.currentMenu;
const exportHtml = ref(false);
const formErrors = ref({});

onBeforeMount(() => {
  document.title = 'Laporan PPH 21';
});

const values = reactive({
  tipe: 'Excel',
});

const onGenerate = async () => {
  if (values.tipe === null) {
    swal.fire({
      icon: 'error',
      text: 'Harap Memilih Tipe Export Dahulu!',
    });
    return;
  }

  const tempGet = [];
  isRequesting.value = true;

  if (values.f_id) {
    tempGet.push(`f_id=${values.f_id}`);
  }

  const paramsGet = tempGet.join('&');
  const url = `${store.server.url_backend}/operation/t_final_gaji/generateTaxReport?${paramsGet}`;

  try {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        Authorization: `${store.user.token_type} ${store.user.token}`,
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const disposition = response.headers.get('Content-Disposition');
    let filename = 'report';

    if (disposition && disposition.includes('filename=')) {
      const match = disposition.match(/filename="?([^"]+)"?/);
      if (match && match[1]) {
        filename = match[1];
      }
    }

    const blob = await response.blob();

    const downloadUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = downloadUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(downloadUrl);

    exportHtml.value = false;
  } catch (error) {
    swal.fire({
      icon: 'error',
      text: error.message || error,
    });
  } finally {
    isRequesting.value = false;
  }
};

watchEffect(() => store.commit('set', ['isRequesting', isRequesting.value]));
