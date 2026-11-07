//   javascript

import { useRouter, useRoute, RouterLink } from 'vue-router'
import { ref, readonly, reactive, inject, onMounted, computed,onBeforeMount, watchEffect, onActivated } from 'vue'

const router = useRouter()
const route = useRoute()
const store = inject('store')
console.log('ini store', store.user.data.m_kary_id)
const swal = inject('swal')
const isRead = route.params.id && route.params.id !== 'create'
const read = ref(false)
const actionText = ref(route.params.id === 'create' ? 'Tambah' : (route.query.action?.toLowerCase() === 'verifikasi' ? null : route.query.action))
const isBadForm = ref(false)
const isRequesting = ref(false)
const modulPath = route.params.modul
const currentMenu = store.currentMenu
const access = reactive({
  create : false,
  read : false,
  update : false,
  delete : false,
  verify : false,
})

console.log('test',access.create)

const getAccess = async () => {
  try {
    const res = await fetch(`${store.server.url_backend}/operation/m_role/getAccess?endpoint=${endpointApi}`, {
      headers: {
        'Content-Type': 'application/json',
        Authorization: `${store.user.token_type} ${store.user.token}`,
      },
    })

    if (!res.ok) throw new Error('Gagal mengambil akses')

    const result = await res.json()
    console.log('result get',result)
    return result
  } catch (err) {
    console.error('Fetch akses gagal:', err)
    return null
  }
}


watchEffect(() => {
  console.log('watch',access)
  if (access.read || access.create || access.update) {
    landing.api = {
      url: `${store.server.url_backend}/operation${endpointApi}`,
      headers: {
        'Content-Type': 'Application/json',
        authorization: `${store.user.token_type} ${store.user.token}`
      },
      params: {
        simplest: true,
        searchfield: `this.id, this.nomor, m_kary.nama_depan, this.keterangan, this.nilai`,
      },
      onsuccess(response) {
        response.page = response.current_page
        response.hasNext = response.has_next
        return response
      }
    }
  } else {
    landing.api = null
  }
})

const apiTable = ref(null)
const formErrors = ref({})
const tsId = `ts=` + (Date.parse(new Date()))


// ------------------------------ PERSIAPAN
const endpointApi = '/t_bonus'
onBeforeMount(async () => {
  document.title = 'Transaksi Bonus'
  const result = await getAccess()
  if (result) {
    access.create = result.create
    access.read = result.read
    access.update = result.update
    access.delete = result.delete
    access.verify = result.verify
  }
  console.log('result call',access.create)
})

//  @if( $id )------------------- VALUES FORM ! PENTING JANGAN DIHAPUS
let initialValues = {}
const changedValues = []

const values = reactive({
  status: 'DRAFT',
  is_all_kary: false,
  is_lunas: false
})


onBeforeMount(async () => {
  // tampilkan default direktorat dengan store user comp.nama
  values.direktorat = store.user.data?.direktorat

  if (isRead) {
    //  READ DATA
    try {
      const editedId = route.params.id
      const dataURL = `${store.server.url_backend}/operation${endpointApi}/${editedId}`
      isRequesting.value = true

      const params = { join: false, transform: false }
      const fixedParams = new URLSearchParams(params)
      const res = await fetch(dataURL + '?' + fixedParams, {
        headers: {
          'Content-Type': 'Application/json',
          Authorization: `${store.user.token_type} ${store.user.token}`
        },
      })
      if (!res.ok) throw new Error("Failed when trying to read data")
      const resultJson = await res.json()
      initialValues = resultJson.data
    } catch (err) {
      isBadForm.value = true
      swal.fire({
        icon: 'error',
        text: err,
        allowOutsideClick: false,
        confirmButtonText: 'Kembali',
      }).then(() => {
        router.back()
      })
    }
    isRequesting.value = false
  }

  for (const key in initialValues) {
    values[key] = initialValues[key]
  }
})

async function posted() {
  const payload = {
    id: route.params.id
  }
  try {
    const dataURL = `${store.server.url_backend}/operation${endpointApi}/postData`
    isRequesting.value = true
    const res = await fetch(dataURL, {
      method: 'POST',
      headers: {
        'Content-Type': 'Application/json',
        Authorization: `${store.user.token_type} ${store.user.token}`
      },
      body: JSON.stringify(payload)
    })
    if (!res.ok) {
      if ([400, 422].includes(res.status)) {
        const responseJson = await res.json()
        formErrors.value = responseJson.errors || {}
        throw (responseJson.message || "Failed when trying to post data")
      } else {
        throw ("Failed when trying to post data")
      }
    }
    router.replace('/' + modulPath + '?reload=' + (Date.parse(new Date())))
  } catch (err) {
    isBadForm.value = true
    swal.fire({
      icon: 'error',
      text: err
    })
  }
  isRequesting.value = false

}


function onBack() {
  let isChanged = false
  for (const key in initialValues) {
    if (values[key] !== initialValues[key]) {
      isChanged = true
      break;
    }
  }

  if (!isChanged) {
    router.replace('/' + modulPath)
    return
  }

  router.replace('/' + modulPath)

}

function onReset() {
  swal.fire({
    icon: 'warning',
    text: 'Reset this form data?',
    showDenyButton: true
  }).then((res) => {
    if (res.isConfirmed) {
      for (const key in initialValues) {
        values[key] = initialValues[key]
      }
    }
  })
}

async function onSave() {
  console.log(values)
  //values.tags = JSON.stringify(values.tags)
  try {
    const isCreating = ['Create', 'Copy', 'Tambah'].includes(actionText.value)
    const dataURL = `${store.server.url_backend}/operation${endpointApi}${isCreating ? '' : ('/' + route.params.id)}`
    isRequesting.value = true
    values.status = values.status === 'DRAFT' ? 'DRAFT' : 'POSTED'
    values.is_all_kary = values.is_all_kary === true ? 1 : 0
    values.is_lunas = values.is_lunas === true ? 1 : 0
    values.last_editor_id = store.user.data.id
    values.creator_id = store.user.data.id
    const res = await fetch(dataURL, {
      method: isCreating ? 'POST' : 'PUT',
      headers: {
        'Content-Type': 'Application/json',
        Authorization: `${store.user.token_type} ${store.user.token}`
      },
      body: JSON.stringify(values)
    })
    if (!res.ok) {
      if ([400, 422].includes(res.status)) {
        const responseJson = await res.json()
        formErrors.value = responseJson.errors || {}
        throw new Error(responseJson.message || "Failed when trying to post data")
      } else {
        throw new Error("Failed when trying to post data")
      }
    }
    router.replace('/' + modulPath + '?reload=' + (Date.parse(new Date())))
  } catch (err) {
    console.log(err)
    isBadForm.value = true
    swal.fire({
      icon: 'error',
      text: err.message
    })
  }
  isRequesting.value = false
}


//  @else----------------------- LANDING

let datas = reactive({})
const tgl_awal = ref('');
const tgl_akhir = ref('');
let initialDatas = {}

// FUNCTION BUAT EDIT READ DSB ADA DI SINI COY//



function parseTanggalToYMD(tanggal) {
  const [dd, mm, yyyy] = tanggal.split('/');
  return `${yyyy}-${mm}-${dd}`;
}

function updateTanggal(tipe, value) {
  if (tipe === 'awal') {
    tgl_awal.value = value;
  } else if (tipe === 'akhir') {
    tgl_akhir.value = value;
  }

  let data = [];

  if (tgl_awal.value) {
    const val = parseTanggalToYMD(tgl_awal.value);
    data.push(`this.date_from = '${val}'`);
  }

  if (tgl_akhir.value) {
    const val = parseTanggalToYMD(tgl_akhir.value);
    data.push(`this.date_to = '${val}'`);
  }

  landing.api.params.where = data.length > 0 ? data.join(' AND ') : null;

  apiTable.value.reload();
}

async function handleHapus(row) {
  const confirm = await swal.fire({
    icon: 'warning',
    text: 'Hapus Data Terpilih?',
    confirmButtonText: 'Yes',
    showDenyButton: true,
  })

  if (confirm.isConfirmed) {
    try {
      isRequesting.value = true
      const url = `${store.server.url_backend}/operation${endpointApi}/${row.id}`
      const res = await fetch(url, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'Application/json',
          Authorization: `${store.user.token_type} ${store.user.token}`
        }
      })
      if (!res.ok) throw new Error('Gagal menghapus data')
      apiTable.value.reload()
    } catch (err) {
      isBadForm.value = true
      swal.fire({ icon: 'error', text: err.message || err })
    } finally {
      isRequesting.value = false
    }
  }
}

function handleRead(row) {
  read.value = true
  router.push(`${route.path}/${row.id}?${tsId}`)
}

function handleEdit(row) {
  read.value = false
  router.push(`${route.path}/${row.id}?action=Edit&${tsId}`)
}

function handleCopy(row) {
  router.push(`${route.path}/${row.id}?action=Copy&${tsId}`)
}

async function handlePost(row) {
  const confirm = await swal.fire({
    icon: 'warning',
    text: 'Post Data?',
    iconColor: '#1469AE',
    confirmButtonColor: '#1469AE',
    showDenyButton: true,
  })

  if (confirm.isConfirmed) {
    try {
      isRequesting.value = true
      const url = `${store.server.url_backend}/operation/t_bonus/${row.id}`
      const res = await fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'Application/json',
          Authorization: `${store.user.token_type} ${store.user.token}`
        },
        body: JSON.stringify({ id: row.id, status: 'POSTED' })
      })

      const json = await res.json()
      if (!res.ok) {
        formErrors.value = json.errors || {}
        throw (json.message + " " + json.data?.errorText || "Gagal post data")
      }

      swal.fire({ icon: 'success', text: json.message })
      apiTable.value.reload()
    } catch (err) {
      isBadForm.value = true
      swal.fire({
        icon: 'error',
        iconColor: '#1469AE',
        confirmButtonColor: '#1469AE',
        text: err.message || err
      })
    } finally {
      isRequesting.value = false
    }
  }
}
// END OF FUNCTION EDIT READ DSB LAH //

//Table Atur Di Sini //

async function syncData() {
  swal.fire({
    icon: 'warning',
    text: 'Sync Upah Bonus ERP?',
    iconColor: '#1469AE',
    confirmButtonColor: '#1469AE',
    showDenyButton: true
  }).then(async (res) => {
    if (res.isConfirmed) {
      try {
        const dataURL = `${store.server.url_backend}/operation${endpointApi}/importupaherp`
        isRequesting.value = true
        const response = await fetch(dataURL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `${store.user.token_type} ${store.user.token}`
          }
        })
        if (!response.ok) {
          const responseJson = await response.json().catch(() => ({}))
          const msg = responseJson.message || 'Gagal melakukan sync'
          throw msg
        }
        const result = await response.json()
        swal.fire({
          icon: 'success',
          text: result.message || 'Sync berhasil'
        })
      } catch (err) {
        isBadForm.value = true
        swal.fire({
          icon: 'error',
          iconColor: '#1469AE',
          confirmButtonColor: '#1469AE',
          text: err.toString()
        })
      } finally {
        isRequesting.value = false
        apiTable.value.reload()
      }
    }
  })
}

const api = computed(() => ({
  url: access.read
    ? `${store.server.url_backend}/operation${endpointApi}`
    : null,
  headers: {
    'Content-Type': 'Application/json',
    authorization: `${store.user.token_type} ${store.user.token}`
  },
  params: {
    simplest: true,
    searchfield: `this.id, this.nomor, m_kary.nama_depan, this.keterangan, this.nilai`,
  },
  onsuccess(response) {
    response.page = response.current_page
    response.hasNext = response.has_next
    return response
  }
}))

const landing = reactive({
  actions: [
    {
      icon: 'trash',
      title: 'Hapus',
      class: 'bg-red-600 text-light-100',
      show : () => access.create === true,
      click: handleHapus
    },
    {
      icon: 'eye',
      title: 'Read',
      class: 'bg-green-600 text-light-100',
      show : () => access.read === true,
      click: handleRead
    },
    {
      icon: 'edit',
      title: 'Edit',
      class: 'bg-blue-600 text-light-100',
      show : () => access.update === true,
      click: handleEdit
    },
    {
      icon: 'location-arrow',
      title: 'Post Data',
      class: 'bg-rose-700 text-white rounded-sm',
      show: row => row.status?.toUpperCase() === 'DRAFT',
      click: handlePost
    },
    {
      icon: 'copy',
      title: 'Copy',
      class: 'bg-gray-600 text-light-100',
      show: row => row.status?.toUpperCase() === 'DRAFT',
      click: handleCopy
    } 
  ],

  columns: [{
    headerName: 'No',
    valueGetter: (params) => params.node.rowIndex + 1,
    width: 60,
    sortable: true,
    resizable: true,
    filter: true,
    cellClass: ['justify-left', 'bg-gray-50', 'border-r', '!border-gray-200']
  },
  {
    field: 'nomor',
    headerName: 'Nomor',
    filter: true,
    sortable: true,
    flex: 1,
    filter: 'ColFilter',
    resizable: true,
    cellClass: ['border-r', '!border-gray-200', 'justify-start']
  },
  {
    field: 'm_kary.nama_depan',
    headerName: 'Karyawan',
    filter: true,
    sortable: true,
    flex: 1,
    filter: 'ColFilter',
    resizable: true,
    cellClass: ['border-r', '!border-gray-200', 'justify-left']
  },
  {
    field: 'keterangan',
    headerName: 'Keterangan',
    filter: true,
    sortable: true,
    flex: 1,
    filter: 'ColFilter',
    resizable: true,
    cellClass: ['border-r', '!border-gray-200', 'justify-left']
  },
   {
    field: 'date_from',
    headerName: 'Tgl Awal',
    filter: true,
    sortable: true,
    flex: 1,
    filter: 'ColFilter',
    resizable: true,
    cellClass: ['border-r', '!border-gray-200', 'justify-left']
  },
    {
    field: 'date_to',
    headerName: 'Tgl Akhir',
    filter: true,
    sortable: true,
    flex: 1,
    filter: 'ColFilter',
    resizable: true,
    cellClass: ['border-r', '!border-gray-200', 'justify-left']
  },
  {
    field: 'nilai',
    headerName: 'Nominal',
    filter: 'ColFilter',
    sortable: true,
    resizable: true,
    flex: 1,
    cellClass: ['border-r', '!border-gray-200', 'justify-end'],
    cellRenderer: ({ value }) => {
      const nominal = parseFloat(value || 0);
      const formatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(nominal);
      return `<span style="color:green">${formatted}</span>`;
    }
  },
  {
    field: 'status',
    headerName: 'Status',
    filter: true,
    sortable: true,
    filter: 'ColFilter',
    resizable: true,
    flex: 1,
    cellClass: ['border-r', '!border-gray-200', 'justify-center'],
    cellRenderer: ({ value }) => {
      return value
        === "POSTED"
        ? `<span class="text-green-500 rounded-md text-xs font-medium px-4 py-1 inline-block capitalize">${value}</span>`
        : `<span class="text-red-500 rounded-md text-xs font-medium px-4 py-1 inline-block capitalize">${value}</span>`
    }
  }]
})



onBeforeMount(async () => {
  const res = await getAccess()
  console.log('iki res',res)

  if (res.read === true) {
    landing.api.url = `${store.server.url_backend}/operation${endpointApi}`

  }
})

// END //


onActivated(() => {
  //  reload table api landing
  if (apiTable.value) {
    if (route.query.reload) {
      apiTable.value.reload()
    }
  }
})

//  @endif -------------------------------------------------END
watchEffect(() => store.commit('set', ['isRequesting', isRequesting.value]))