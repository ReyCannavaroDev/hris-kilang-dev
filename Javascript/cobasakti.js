//   javascript

import { useRouter, useRoute, RouterLink } from 'vue-router'
import { ref, readonly, reactive, inject, onMounted, onBeforeMount, watchEffect, onActivated } from 'vue'

const router = useRouter()
const route = useRoute()
const store = inject('store')
console.log('ini store', store.user)
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

console.log('test',access)

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

console.log('values',values)

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


onBeforeMount(async () => {
  // tampilkan default direktorat dengan store user comp.nama
  datas.direktorat = store.user.data?.direktorat

  {
    //  READ DATA
    try {
      const dataURL = `${store.server.url_backend}/operation/m_role/getAccess?endpoint=${endpointApi}`
      isRequesting.value = true

      const params = { join: true, transform: true }
      const fixedParams = new URLSearchParams(params)
      const res = await fetch(dataURL + '?' + fixedParams, {
        headers: {
          'Content-Type': 'Application/json',
          Authorization: `${store.user.token_type} ${store.user.token}`
        },
      })

      if (!res.ok) throw new Error("Failed when trying to read data")
      const resultJson = await res.json()
      console.log('test', resultJson)
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

  for (const key in initialDatas) {
    datas[key] = initialDatas[key]
  }
})

function parseTanggalToYMD(tanggal) {
  const [dd, mm, yyyy] = tanggal.split('/');
  return `${yyyy}-${mm}-${dd}`;
}

function getApiConfig() {
  if (!access.read) return null

  return {
    url: `${store.server.url_backend}/operation${endpointApi}`,
    headers: {
      'Content-Type': 'Application/json',
      authorization: `${store.user.token_type} ${store.user.token}`
    },
    params: {
      simplest: true,
      searchfield: `this.id, this.nomor, m_kary.nama_depan, this.keterangan, this.nilai`
    },
    onsuccess(response) {
      response.page = response.current_page
      response.hasNext = response.has_next
      return response
    }
  }
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

const landing = reactive({
  actions: [
    {
      icon: 'trash',
      class: 'bg-red-600 text-light-100',
      title: "Hapus",
      // show: (row) => row.status?.toUpperCase() === 'DRAFT',
      // show: () => store.user.data.username==='developer',
      click(row) {
        swal.fire({
          icon: 'warning',
          text: 'Hapus Data Terpilih?',
          confirmButtonText: 'Yes',
          showDenyButton: true,
        }).then(async (result) => {
          if (result.isConfirmed) {
            try {
              const dataURL = `${store.server.url_backend}/operation${endpointApi}/${row.id}`
              isRequesting.value = true
              const res = await fetch(dataURL, {
                method: 'DELETE',
                headers: {
                  'Content-Type': 'Application/json',
                  Authorization: `${store.user.token_type} ${store.user.token}`
                }
              })
              if (!res.ok) throw new Error("Failed when trying to remove data")
              apiTable.value.reload()
              // const resultJson = await res.json()
            } catch (err) {
              isBadForm.value = true
              swal.fire({
                icon: 'error',
                text: err
              })
            }
            isRequesting.value = false
          }
        })
      }
    },
    {
      icon: 'eye',
      title: "Read",
      class: 'bg-green-600 text-light-100',
      // show: (row) => (currentMenu?.can_read)||store.user.data.username==='developer',
      click(row) {
        read.value = true
        console.log('read', read.value)
        router.push(`${route.path}/${row.id}?` + tsId)
      }
    },
    {
      icon: 'edit',
      title: "Edit",
      class: 'bg-blue-600 text-light-100',
      //  show: (row) => row.access.update === true,
      // show: (row) => (currentMenu?.can_update)||store.user.data.username==='developer',
      click(row) {
        read.value = false
        router.push(`${route.path}/${row.id}?action=Edit&` + tsId)
      }
    },
    {
      icon: 'location-arrow',
      title: "Post Data",
      class: 'bg-rose-700 rounded-sm text-white',
      show: (row) => row.status?.toUpperCase() === 'DRAFT',
      async click(row) {
        console.log('row', `${row.id}`)
        swal.fire({
          icon: 'warning',
          text: 'Post Data?',
          iconColor: '#1469AE',
          confirmButtonColor: '#1469AE',
          showDenyButton: true
        }).then(async (res) => {
          if (res.isConfirmed) {
            try {
              const dataURL = `${store.server.url_backend}/operation/t_bonus/${row.id}`
              isRequesting.value = true

              const datas = { status: "POSTED" }

              const res = await fetch(dataURL, {
                method: 'PUT',
                headers: {
                  'Content-Type': 'Application/json',
                  Authorization: `${store.user.token_type} ${store.user.token}`
                },
                body: JSON.stringify({
                  id: row.id,
                  ...datas
                })
              })

              if (!res.ok) {
                if ([400, 422, 500].includes(res.status)) {
                  const responseJson = await res.json()
                  formErrors.value = responseJson.errors || {}
                  throw (responseJson.message + " " + responseJson.data?.errorText || "Failed when trying to post data")
                } else {
                  throw ("Failed when trying to post data")
                }
              }

              const responseJson = await res.json()
              swal.fire({
                icon: 'success',
                text: responseJson.message
              })
            } catch (err) {
              isBadForm.value = true
              swal.fire({
                icon: 'error',
                iconColor: '#1469AE',
                confirmButtonColor: '#1469AE',
                text: err
              })
            }

            isRequesting.value = false
            apiTable.value.reload()
          }
        })
      }
    },
    {
      icon: 'copy',
      title: "Copy",
      class: 'bg-gray-600 text-light-100',
      show: (row) => row.status?.toUpperCase() === 'DRAFT ',
      click(row) {
        // read.value = false
        router.push(`${route.path}/${row.id}?action=Copy&` + tsId)
      }
    }
  ],
  api: {
    // url: access.read === true 
    // ? `${store.server.url_backend}/operation${endpointApi}` 
    // : null,
    url: `${store.server.url_backend}/operation${endpointApi}`,
    headers: {
      'Content-Type': 'Application/json',
      authorization: `${store.user.token_type} ${store.user.token}`
    },
    params: {
      simplest: true,
      searchfield: `this.id, this.nomor, m_kary.nama_depan, this.keterangan, this.nilai`
    },
    onsuccess(response) {
      response.page = response.current_page
      response.hasNext = response.has_next
      return response
    }
  },
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
  // {
  //   field: 'date_from',
  //   headerName: 'Tanggal Awal',
  //   filter: true,
  //   sortable: true,
  //   flex: 1,
  //   filter: 'ColFilter',
  //   resizable: true,
  //   cellClass: ['border-r', '!border-gray-200', 'justify-start']
  // },
  // {
  //   field: 'date_to',
  //   headerName: 'Tanggal Akhir',
  //   filter: true,
  //   sortable: true,
  //   flex: 1,
  //   filter: 'ColFilter',
  //   resizable: true,
  //   cellClass: ['border-r', '!border-gray-200', 'justify-start']
  // },
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
  },
  ]
})
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