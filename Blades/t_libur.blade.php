@if(!$req->has('id'))
<div class="bg-white p-6 rounded-xl h-[570px]">
  <TableApi ref='apiTable' :api="landing.api" :columns="landing.columns" :actions="landing.actions">
    <template #header>
      <RouterLink :to="$route.path+'/create?'+(Date.parse(new Date()))"
        class="bg-green-500 text-white hover:bg-green-600 rounded-[6px] py-2 px-[12.5px]">
        <icon fa="plus" />
        Tambah Data
      </RouterLink>
    </template>
  </TableApi>
</div>
@else

@verbatim

<div class="flex flex-col gap-y-3">
  <div class="flex gap-x-4 px-2">
    <div class="flex flex-col border rounded-2xl shadow-sm px-6 py-6 <md:w-full w-full bg-white">

      <!-- HEADER START -->
      <div class="flex items-center justify-between mb-2 pb-4">
        <h2 class="font-sans text-xl flex justify-left font-bold">
          Form Jadwal Off
        </h2>

      </div>
      <!-- HEADER END -->

      <!-- FORM START -->
      <div class="grid <md:grid-cols-1 grid-cols-2 grid-flow-row gap-x-4 gap-y-4 mb-5">
        <div class="grid grid-cols-12 items-center gap-y-2 hidden">
          <label class="col-span-12">company</label>
          <FieldSelect class="col-span-12 !mt-0" :bind="{ disabled: !actionText, clearable:false }"
            :value="values.m_comp_id" @input="v=>values.m_comp_id=v" :errorText="formErrors.m_comp_id?'failed':''"
            :hints="formErrors.m_comp_id" valueField="id" displayField="key" :api="{
                url: `${store.server.url_backend}/operation/t_ins_sort`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false
                }
            }" placeholder="" label="" fa-icon="" :check="false" />
        </div>

        <div class="grid grid-cols-12 items-center gap-y-2">
          <label class="col-span-12">Direktorat</label>
          <FieldSelect class="col-span-12 !mt-0" :bind="{ disabled: !actionText, clearable:false }"
            :value="values.m_dir_id" @input="v=>values.m_dir_id=v" :errorText="formErrors.m_dir_id?'failed':''"
            :hints="formErrors.m_dir_id" valueField="id" displayField="nama" :api="{
                url: `${store.server.url_backend}/operation/m_dir`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false
                }
            }" placeholder="" label="" fa-icon="" :check="false" />
        </div>

        <div class="grid grid-cols-12 items-center gap-y-2">
          <label class="col-span-12">Code<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldX class="col-span-12 !mt-0" :bind="{ readonly: true, disabled: true }" :value="values.kode"
            :errorText="formErrors.kode?'failed':''" @input="v=>values.kode=v" :hints="formErrors.kode" label=""
            placeholder="Auto Generate By System" :check="false" />
        </div>

        <div>
          <label class="font-semibold">Tanggal Awal<span class="text-red-500 space-x-0 pl-0">*</span></label>
          <FieldX :bind="{ readonly: !actionText , required: true}" class="w-full py-2 !mt-0"
            :value="values.tanggal_mulai" :errorText="formErrors.tanggal_mulai?'failed':''"
            @input="v=>values.tanggal_mulai=v" :hints="formErrors.tanggal_mulai" :check="false" type="date" label=""
            placeholder="DD/MM/YYYY" />
        </div>

        <div>
          <label class="font-semibold">Tanggal Akhir<span class="text-red-500 space-x-0 pl-0">*</span></label>
          <FieldX :bind="{ readonly: !actionText , required: true}" class="w-full py-2 !mt-0"
            :value="values.tanggal_akhir" :errorText="formErrors.tanggal_akhir?'failed':''"
            @input="v=>values.tanggal_akhir=v" :hints="formErrors.tanggal_akhir" :check="false" type="date" label=""
            placeholder="DD/MM/YYYY" />
        </div>

        <div class="grid grid-cols-12 items-center gap-y-2">
          <label class="col-span-12">Keterangan <label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldX class="col-span-12 !mt-0" :bind="{ readonly: !actionText }" :value="values.desc"
            :errorText="formErrors.desc?'failed':''" @input="v=>values.desc=v" type="textarea" label=""
            placeholder="Tuliskan Keterangan" :hints="formErrors.desc" :check="false" />
        </div>

        <div class="grid grid-cols-12 items-start gap-y-2">
          <label class="col-span-12">Status <label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect class="col-span-12 !mt-0" :bind="{ disabled: true, readonly:true, clearable:false }"
            :value="values.is_active" @input="v=>values.is_active=v" :errorText="formErrors.is_active?'failed':''"
            :hints="formErrors.is_active" valueField="id" displayField="key"
            :options="[{'id' : 1 , 'key' : 'Aktif'},{'id': 0, 'key' : 'Non Aktif'}]" placeholder="" label="" fa-icon=""
            :check="false" />

        </div>

        <div class="grid grid-cols-12 items-start gap-y-2">
          <label class="col-span-12">Semua Karyawan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <div class="flex-auto">
            <i class="text-red-500">Tidak</i>
          </div>
          <input
         bg-neutral-300 relative transition-colors duration-200
         class="mr-2 mt-[0.3rem] h-3.5 w-8 appearance-none rounded-[0.4375rem]
                before:pointer-events-none before:absolute before:h-3.5 before:w-3.5
                before:rounded-full before:bg-transparent before:content-['']
                after:absolute after:z-[2] after:-mt-[0.1875rem] after:h-5 after:w-5
                after:rounded-full after:border-none after:shadow-[0_0px_3px_0_rgb(0_0_0_/7%),_0_2px_2px_0_rgb(0_0_0/_4%)]
                after:transition-[background-color_0.2s,transform_0.2s] after:content-['']
                checked:after:ml-[1.0625rem]
                focus:outline-none focus:ring-0"
         type="checkbox"
         role="switch"
         id="is_all_kary_for_click"
         :disabled="!actionText"
         v-model="values.is_all_kary"
         :class="values.is_all_kary
           ? 'bg-green-400 after:bg-green-600'
           : 'bg-red-400 after:bg-red-600'"
          / >
          <div class="flex-auto">
            <i class="text-green-500">Ya</i>
          </div>

        </div>
      </div>
      <!-- FORM END -->

      <div v-if="values.is_all_kary != 1" class="flex items-stretch mt-8 lg:w-[40%] text-sm  <md:col-span-1 col-span-2">
        <h1 class="text-17px font-semibold">Detail Hari Libur</h1>
      </div>

      <div v-if="values.is_all_kary != 1"
        class="<md:col-span-1 col-span-2 pb-4 grid <md:grid-cols-1 grid-cols-3 gap-2 ">
        <div class="<md:col-span-1 col-span-3">
          <ButtonMultiSelect v-if="actionText" title="Add to list" @add="onDetailAdd" :api="{
            url: `${store.server.url_backend}/operation/m_kary`,
            headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
            params: { simplest: true},
            onsuccess:(response)=>{
              response.data = [...response.data].map((dt)=>{
                dt['m_kary_id'] = dt.id
                dt['nama'] = dt.nama_lengkap
                dt['jabatan'] = dt['m_dept.id']
                return dt
              })
              response.page = response.current_page
              response.hasNext = response.has_next
              return response
            }
          }" :columns="[{
            checkboxSelection: true,
            headerCheckboxSelection: true,
            headerName: 'No',
            valueGetter:p=>'',
            width:60,
            searchField:'this.nama_lengkap, m_dept.nama',
            sortable: false, resizable: true, filter: false,
            cellClass: ['justify-center', 'bg-gray-50', '!border-gray-200']
          },
          {
            flex: 1,
            headerName:'Nama',
            sortable: false, resizable: true, filter: 'ColFilter',
            field: 'nama_lengkap',
            cellClass: ['justify-center','!border-gray-200']
          },
          {
            flex: 1,
            headerName:'Jabatan',
            sortable: false, resizable: true, filter: 'ColFilter',
            field: 'm_dept.nama',
            cellClass: ['justify-center','!border-gray-200']
          }]">
            <div
              class="bg-blue-600 text-white font-semibold hover:bg-blue-500 transition-transform duration-300 transform hover:-translate-y-0.5 rounded p-1.5 mt-3">
              <icon fa="plus" size="sm mr-0.5" /> Add to list
            </div>
          </ButtonMultiSelect>
          <div class="overflow-scroll lg:overflow-visible <md:col-span-1 col-span-3 mt-4">
            <table class="w-[100%] overflow-x-auto table-auto border border-[#CACACA] pt-4">
              <thead>
                <tr class="border">
                  <td
                    class="text-[#8f8f8f] font-semibold text-[12px] text-capitalize p-2 text-center w-[5%] border bg-[#f8f8f8] border-[#CACACA]">
                    No</td>
                  <td
                    class="text-[#8f8f8f] font-semibold text-[12px] text-capitalize p-2 text-center border bg-[#f8f8f8] border-[#CACACA]">
                    Nama</td>
                  <td
                    class="text-[#8f8f8f] font-semibold text-[12px] text-capitalize p-2 text-center border bg-[#f8f8f8] border-[#CACACA]">
                    Jabatan</td>
                  <td
                    class="text-[#8f8f8f] font-semibold text-[12px] text-capitalize p-2 text-center w-[5%] border bg-[#f8f8f8] border-[#CACACA]">
                  </td>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, i) in detailArr" :key="item.id" class="border-t" v-if="detailArr.length > 0">
                  <td class="text-[12px] text-center border border-[#CACACA]">
                    {{ i + 1 }}.
                  </td>
                  <td class="text-[12px] text-left border border-[#CACACA]">
                    <FieldX :bind="{ readonly: !actionText }" :value="item.nama"
                      :errorText="formErrors.nama?'failed':''" @input="v=>item.nama=v" :hints="formErrors.nama"
                      :check="false" placeholder="" label="" />
                  </td>
                  <td class="text-[12px] text-right border border-[#CACACA]">
                    <FieldSelect class="col-span-12 !mt-0" :bind="{ disabled: !actionText, clearable:false }"
                      :value="item.jabatan" @input="v=>item.jabatan=v" :errorText="formErrors.jabatan?'failed':''"
                      :hints="formErrors.jabatan" valueField="id" displayField="nama" :api="{
                        url: `${store.server.url_backend}/operation/m_dept`,
                        headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                        params: {
                          simplest:true,
                          transform:false,
                          join:false
                        }
                    }" placeholder="" label="" fa-icon="" :check="false" />
                  </td>
                  <td>
                    <div class="flex justify-center">
                      <button type="button" @click="removeDetail(i)" :disabled="!actionText">
                      <svg width="14" height="14" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path id="Vector" d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#F24E1E"/>
                      </svg>
                </button>
                    </div>
                  </td>
                </tr>
                <tr v-else class="text-center">
                  <td colspan="7" class="py-[20px]">
                    No data to show
                  </td>
                </tr>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- END TABLE DETAIL -->
        </div>
      </div>

      <div class="flex justify-end mb-4 gap-4">
        <button @click="onBack" class="bg-[#EF4444] hover:bg-[#ed3232] text-white px-[36.5px] py-[12px] rounded-[6px] w-32">
            Batal
        </button>
        <button v-show="actionText" @click="onSave" class="bg-[#10B981] hover:bg-[#0ea774] text-white px-[36.5px] py-[12px] rounded-[6px] w-32">
            Simpan
        </button>
      </div>
    </div>
  </div>


</div>

@endverbatim
@endif