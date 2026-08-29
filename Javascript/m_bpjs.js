import { useRouter, useRoute, RouterLink } from 'vue-router'
import { ref, reactive, inject, onBeforeMount, onActivated, watchEffect } from 'vue'

const router = useRouter()
const route = useRoute()
const store = inject('store')
const swal = inject('swal')

const isRead = route.params.id && route.params.id !== 'create'
const actionText = ref(route.params.id === 'create' ? 'Tambah' : route.query.action)
const isBadForm = ref(false)
const isRequesting = ref(false)
const modulPath = route.params.modul
const currentMenu = store.currentMenu
const apiTable = ref(null)
const formErrors = ref({})
const endpointApi = '/m_bpjs'

const filters = reactive({
  tahun: new Date().getFullYear(),
  kota_id: null,
  jenis: '',
  is_active: true,
})

const defaultValues = {
  kota_id: null,
  jenis: 'UMK',
  tahun: new Date().getFullYear(),
  nominal: 0,
  effective_from: '',
  effective_to: '',
  is_default: true,
  is_active: true,
  desc: '',
}
const initialValues = { ...defaultValues }
const values = reactive({ ...defaultValues })

function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount || 0))
}

function escapeCsv(value) {
  const text = value === null || value === undefined ? '' : String(value)
  return `"${text.replace(/"/g, '""')}"`
}

function buildWhere() {
  const where = []

  if (filters.is_active) where.push(`this.is_active = true`)
  if (filters.tahun) where.push(`this.tahun = ${Number(filters.tahun)}`)
  if (filters.kota_id) where.push(`this.kota_id = ${Number(filters.kota_id)}`)
  if (filters.jenis) where.push(`upper(this.jenis) = '${String(filters.jenis).replace(/'/g, "''").toUpperCase()}'`)

  return where.join(' AND ')
}

function syncLandingFilter() {
  landing.api.params.where = buildWhere()
}

function applyFilter() {
  syncLandingFilter()
  apiTable.value?.reload?.()
}

function resetFilter() {
  filters.tahun = new Date().getFullYear()
  filters.kota_id = null
  filters.jenis = ''
  filters.is_active = true
  syncLandingFilter()
  apiTable.value?.reload?.()
}

async function exportCsv() {
  try {
    swal.fire({
      title: 'Please Wait',
      text: 'Mempersiapkan export...',
      allowOutsideClick: false,
      didOpen: () => swal.showLoading(),
    })

    const params = new URLSearchParams({
      simplest: true,
      paginate: '100000',
      where: buildWhere(),
    })

    const res = await fetch(`${store.server.url_backend}/operation${endpointApi}?${params.toString()}`, {
      headers: {
        'Content-Type': 'Application/json',
        Authorization: `${store.user.token_type} ${store.user.token}`,
      },
    })
    if (!res.ok) throw new Error('Gagal mengambil data export')

    const json = await res.json()
    const rows = Array.isArray(json?.data?.data)
      ? json.data.data
      : Array.isArray(json?.data)
        ? json.data
        : Array.isArray(json?.items)
          ? json.items
          : []

    const headers = ['Kota', 'Jenis', 'Tahun', 'Nominal', 'Default', 'Aktif', 'Mulai', 'Sampai', 'Deskripsi']
    const csv = [
      headers.map(escapeCsv).join(','),
      ...rows.map((row) => [
        row.kota_nama || row['m_general.value'] || row['kota.value'] || '',
        row.jenis || '',
        row.tahun || '',
        row.nominal || '',
        row.is_default ? 'YA' : 'TIDAK',
        row.is_active ? 'YA' : 'TIDAK',
        row.effective_from || '',
        row.effective_to || '',
        row.desc || '',
      ].map(escapeCsv).join(',')),
    ].join('\n')

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `m_bpjs_${Date.now()}.csv`
    a.click()
    URL.revokeObjectURL(url)
    swal.close()
  } catch (err) {
    swal.close()
    swal.fire({
      icon: 'error',
      text: err?.message || err,
    })
  }
}

function onBack() {
  router.replace('/' + modulPath)
}

function onReset() {
  swal.fire({
    icon: 'warning',
    text: 'Reset this form data?',
    showDenyButton: true,
  }).then((res) => {
    if (res.isConfirmed) {
      Object.keys(values).forEach((key) => {
        values[key] = initialValues[key] ?? values[key]
      })
    }
  })
}

async function onSave() {
  try {
    isRequesting.value = true
    formErrors.value = {}

    const payload = { ...values }
    payload.kota_id = payload.kota_id ? Number(payload.kota_id) : null
    payload.nominal = Number(payload.nominal || 0)
    payload.tahun = Number(payload.tahun || new Date().getFullYear())
    payload.is_default = payload.is_default ? 1 : 0
    payload.is_active = payload.is_active ? 1 : 0

    const isEdit = !!(route.params.id && route.params.id !== 'create')
    const dataURL = isEdit
      ? `${store.server.url_backend}/operation${endpointApi}/${route.params.id}`
      : `${store.server.url_backend}/operation${endpointApi}`

    const res = await fetch(dataURL, {
      method: isEdit ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'Application/json',
        Authorization: `${store.user.token_type} ${store.user.token}`,
      },
      body: JSON.stringify(payload),
    })

    if (!res.ok) {
      const responseJson = await res.json()
      formErrors.value = responseJson.errors || {}
      throw (responseJson.errors?.length ? responseJson.errors[0] : responseJson.message || 'Gagal menyimpan data')
    }

    router.replace('/' + modulPath + '?reload=' + Date.now())
  } catch (err) {
    isBadForm.value = true
    swal.fire({
      icon: 'error',
      text: err?.message || err,
    })
  }
  isRequesting.value = false
}

const landing = reactive({
  actions: [
    {
      icon: 'pen-to-square',
      class: 'bg-amber-600 text-light-100',
      title: 'Edit',
      click(row) {
        router.push('/' + modulPath + '/' + row.id + '?action=edit')
      },
    },
    {
      icon: 'trash',
      class: 'bg-red-600 text-light-100',
      title: 'Hapus',
      click(row) {
        swal.fire({
          icon: 'warning',
          text: 'Hapus data terpilih?',
          confirmButtonText: 'Yes',
          showDenyButton: true,
        }).then(async (result) => {
          if (!result.isConfirmed) return

          try {
            isRequesting.value = true
            const dataURL = `${store.server.url_backend}/operation${endpointApi}/${row.id}`
            const res = await fetch(dataURL, {
              method: 'DELETE',
              headers: {
                'Content-Type': 'Application/json',
                Authorization: `${store.user.token_type} ${store.user.token}`,
              },
            })

            if (!res.ok) {
              const resultJson = await res.json()
              throw (resultJson.message || 'Gagal menghapus data')
            }

            apiTable.value?.reload?.()
          } catch (err) {
            swal.fire({
              icon: 'error',
              text: err?.message || err,
            })
          }
          isRequesting.value = false
        })
      },
    },
  ],
  api: {
    url: `${store.server.url_backend}/operation${endpointApi}`,
    headers: {
      'Content-Type': 'Application/json',
      authorization: `${store.user.token_type} ${store.user.token}`,
    },
    params: {
      simplest: true,
      searchfield: 'this.id, this.jenis, this.tahun, this.nominal, this.desc, this.kota_id',
      where: buildWhere(),
    },
    onsuccess(response) {
      response.page = response.current_page
      response.hasNext = response.has_next
      return response
    },
  },
  columns: [
    {
      headerName: 'No',
      valueGetter: (params) => params.node.rowIndex + 1,
      width: 60,
      sortable: true,
      resizable: true,
      filter: true,
      cellClass: ['justify-center', 'bg-gray-50', 'border-r', '!border-gray-200'],
    },
    {
      field: 'kota_nama',
      headerName: 'Kota',
      filter: true,
      wrapText: true,
      sortable: true,
      flex: 1,
      filter: 'ColFilter',
      resizable: true,
      cellClass: ['border-r', '!border-gray-200', 'justify-start'],
      cellRenderer: ({ value, data }) => value || data?.['kota.value'] || data?.['m_general.value'] || '-',
    },
    {
      field: 'jenis',
      headerName: 'Jenis',
      filter: true,
      wrapText: true,
      sortable: true,
      width: 100,
      filter: 'ColFilter',
      resizable: true,
      cellClass: ['border-r', '!border-gray-200', 'justify-center'],
    },
    {
      field: 'tahun',
      headerName: 'Tahun',
      filter: true,
      wrapText: true,
      sortable: true,
      width: 90,
      filter: 'ColFilter',
      resizable: true,
      cellClass: ['border-r', '!border-gray-200', 'justify-center'],
    },
    {
      field: 'nominal_fmt',
      headerName: 'Nominal',
      filter: true,
      wrapText: true,
      sortable: true,
      flex: 1,
      filter: 'ColFilter',
      resizable: true,
      cellClass: ['border-r', '!border-gray-200', 'justify-end'],
      cellRenderer: ({ value, data }) => formatRupiah(data?.nominal || value || 0),
    },
    {
      field: 'is_default',
      headerName: 'Default',
      filter: true,
      sortable: true,
      width: 110,
      cellClass: ['border-r', '!border-gray-200', 'justify-center'],
      cellRenderer: ({ value }) => value ? 'YA' : 'TIDAK',
    },
    {
      field: 'is_active',
      headerName: 'Aktif',
      filter: true,
      sortable: true,
      width: 90,
      cellClass: ['border-r', '!border-gray-200', 'justify-center'],
      cellRenderer: ({ value }) => value ? 'YA' : 'TIDAK',
    },
    {
      field: 'effective_from',
      headerName: 'Mulai',
      filter: true,
      sortable: true,
      width: 120,
      cellClass: ['border-r', '!border-gray-200', 'justify-center'],
    },
    {
      field: 'effective_to',
      headerName: 'Sampai',
      filter: true,
      sortable: true,
      width: 120,
      cellClass: ['border-r', '!border-gray-200', 'justify-center'],
    },
    {
      field: 'desc',
      headerName: 'Deskripsi',
      filter: true,
      wrapText: true,
      sortable: true,
      flex: 1,
      filter: 'ColFilter',
      resizable: true,
      cellClass: ['border-r', '!border-gray-200', 'justify-start'],
    },
  ],
})

onBeforeMount(async () => {
  document.title = 'Master BPJS'

  if (isRead) {
    try {
      const editedId = route.params.id
      const dataURL = `${store.server.url_backend}/operation${endpointApi}/${editedId}`
      isRequesting.value = true

      const params = { join: false, transform: false }
      const fixedParams = new URLSearchParams(params)
      const res = await fetch(dataURL + '?' + fixedParams, {
        headers: {
          'Content-Type': 'Application/json',
          Authorization: `${store.user.token_type} ${store.user.token}`,
        },
      })
      if (!res.ok) throw new Error('Gagal memuat data')
      const resultJson = await res.json()
      const data = resultJson.data || {}

      Object.keys(defaultValues).forEach((key) => {
        values[key] = data[key] ?? defaultValues[key]
        initialValues[key] = data[key] ?? defaultValues[key]
      })

      values.is_default = !!values.is_default
      values.is_active = !!values.is_active
      values.nominal = Number(values.nominal || 0)
      values.tahun = Number(values.tahun || new Date().getFullYear())
    } catch (err) {
      isBadForm.value = true
      swal.fire({
        icon: 'error',
        text: err?.message || err,
        allowOutsideClick: false,
        confirmButtonText: 'Kembali',
      }).then(() => {
        router.back()
      })
    }
  }
})

onActivated(() => {
  if (apiTable.value && route.query.reload) {
    apiTable.value.reload()
  }
})

watchEffect(() => store.commit('set', ['isRequesting', isRequesting.value]))
