@if(!$req->has('id'))
<div class="bg-white p-6 rounded-xl h-[570px]">
  <TableApi ref='apiTable' :api="landing.api" :columns="landing.columns" :actions="landing.actions">
    <template #header>
      <div class="flex gap-x-2 -mt-5">
        <FieldX type="date" :value="tgl_awal" :errorText="formErrors.tgl_awal ? 'failed' : ''"
          @input="v => updateTanggal('awal', v)" :hints="formErrors.tgl_awal" placeholder="Tanggal Awal" label="Dari"
          :check="false" />

        <FieldX type="date" :value="tgl_akhir" :errorText="formErrors.tgl_akhir ? 'failed' : ''"
          @input="v => updateTanggal('akhir', v)" :hints="formErrors.tgl_akhir" placeholder="Tanggal Akhir"
          label="Sampai" :check="false" />
      </div>
      <RouterLink v-if="currentMenu?.can_create||true||store.user.data.username==='developer'"
        :to="$route.path+'/create?'+(Date.parse(new Date()))"
        class="bg-green-500 text-white hover:bg-green-600 rounded-[6px] py-2 px-[12.5px]">
        Tambah
        <icon fa="plus" />
      </RouterLink>
    </template>
  </TableApi>
</div>
@else

@verbatim
<div class="flex flex-col gap-y-3">
  <div class="flex gap-x-4 px-2">
    <div class="flex flex-col border rounded shadow-sm px-6 py-6 <md:w-full w-full bg-white">
      <div class="mb-4">
        <h1 class="text-[24px] mb-4 font-bold">
          Form Transaksi Perjalanan Dinas
        </h1>
        <hr>
      </div>
      <div class="grid <md:grid-cols-1 grid-cols-2 gap-y-2 gap-x-20">
        <!-- START COLUMN -->
        <div>
          <label class="font-semibold">Kode</label>
          <FieldX :bind="{ readonly: true }" label="" class="w-full py-2 !mt-0" :value="values.kode"
            :errorText="formErrors.kode?'failed':''" @input="v=>values.kode=v" :hints="formErrors.kode" :check="false"
            label="" placeholder="kode" />
        </div>

        <div class="flex flex-col gap-2">
          <label
            class="inline-block pl-[0.15rem] hover:cursor-pointer font-semibold"
            for="is_active_for_click">
            Status <span class="text-red-500 space-x-0 pl-0">*</span>
          </label>
          <FieldX class="w-full py-0 !mt-0" :bind="{ readonly: true }" :value="values.status"
            @input="v=>values.status=v" placeholder="Status" label="" fa-icon="" :check="false" />
        </div>

        <div>
          <label class="font-semibold">Tanggal<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldX :bind="{ readonly: !actionText , required: true}" class="w-full py-2 !mt-0" :value="values.date"
            :errorText="formErrors.date?'failed':''" :hints="formErrors.date" :check="false" type="date" label=""
            placeholder="Pilih Tanggal" @input="(v)=>{values.date=v}" />
        </div>

        <div>
          <label class="font-semibold">keterangan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldX :bind="{ readonly: !actionText , required: true}" class="w-full py-2 !mt-0" :value="values.keterangan"
            :errorText="formErrors.keterangan?'failed':''" :hints="formErrors.keterangan" :check="false" type="textarea"
            label="" placeholder="Masukkan keterangan" @input="(v)=>{values.keterangan=v}" />
        </div>

<!-- 
        <div class="py-2">
          <label class="col-span-12 font-semibold">Karyawan<label class="text-red-500 space-x-0 pl-0 mb-2">*</label></label>
          <div class="flex flex-col gap-y-2 mt-2">
            <ButtonMultiSelect title="Tambahkan Ke Table" :api="{
              url: `${store.server.url_backend}/operation/m_kary`,
              headers: {
                'Content-Type': 'Application/json',
                Authorization: `${store.user.token_type} ${store.user.token}`
              },
              params: {
                join: true,

                searchfield: 'this.code, this.long_name, uom.value1, uom_conv.value1, this.conv',
                //notin: arrDivisi.value.length > 0 ? `id:${arrDivisi.value.map(dt => dt.id).join(',')}`: undefined
                //where: `this.tipe_id=${values.m_tipe_id ?? 0} AND this.is_active=true`
              },
              onsuccess(response) {
                response.page = response.current_page
                response.hasNext = response.has_next
                return response
              }
            }" :columns="[
              {
                checkboxSelection: true,
                headerCheckboxSelection: true,
                headerName: 'No',
                valueGetter: () => '',
                width: 60,
                sortable: false,
                resizable: true,
                filter: false,
                cellClass: ['justify-center', 'bg-gray-50', '!border-gray-200']
              },
              {
                headerName: 'Kode',
                field: 'code',
                filter: true,
                sortable: true,
                flex: 2,
                filter: 'ColFilter',
                resizable: true,
                wrapText: true,
                cellClass: ['border-r', '!border-gray-200', 'justify-start']
              },
              {
                headerName: 'Nama Karyawan',
                field: 'nama_lengkap',
                filter: true,
                sortable: true,
                flex: 2,
                filter: 'ColFilter',
                resizable: true,
                wrapText: true,
                cellClass: ['border-r', '!border-gray-200', 'justify-start']
              }
            ]" @add="onDetailAdd">
              <button
            type="button"
            :disabled="!actionText"
            class="bg-[#2d7f28] hover:bg-[#256c21] disabled:bg-[#2d7f2880] disabled:cursor-not-allowed text-white py-2 px-4 text-xs flex items-center justify-center space-x-2 rounded"
          >
            <icon fa="plus" />
            <span class="font-semibold">Tambahkan Ke Tabel</span>
          </button>
            </ButtonMultiSelect>

            <FieldSelect :bind="{ disabled: !actionText, multiple: true, clearable:false }" class="w-full py-2 !mt-0"
              :value="values.tampilKary" @input="v=>values.tampilKary=v" :errorText="formErrors.tampilKary?'failed':''"
              :hints="formErrors.tampilKary" valueField="id" displayField="nama_lengkap" :api="{
              url: `${store.server.url_backend}/operation/m_kary`,
              headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
              params: {
                simplest:false,
                transform:true,
                join:true
              }
          }" placeholder="" label="" fa-icon="" :check="false" />
          </div>
        </div> -->


        <!-- <div>
          <label class="font-semibold">Last Editor<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect
            :bind="{ disabled: !actionText, clearable:false }" class="w-full py-2 !mt-0" label=""
            :value="values.last_editor_id" @input="v=>values.last_editor_id=v"
            :errorText="formErrors.last_editor_id?'failed':''" 
            :hints="formErrors.last_editor_id"
            valueField="id" displayField="name"
            :api="{
                url: `${store.server.url_backend}/operation/default_users`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true`,
                  selectfield: 'id, name'
                }
            }"
            placeholder="Pilih Jenis Potongan" :check="false"
          />
        </div> -->

        <!-- UPDATE NAME -->
        <!-- <br class="col-span-2">

        <hr class="col-span-2 !mb-5"> -->

        <!-- <div v-if="isRead">
          <label class="font-semibold">Creator<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: true, readonly: true, clearable:false }" class="w-full py-2 !mt-0" label=""
            :value="values.creator_id" @input="v=>values.creator_id=v" :errorText="formErrors.creator_id?'failed':''"
            :hints="formErrors.creator_id" valueField="id" displayField="name" :api="{
                url: `${store.server.url_backend}/operation/default_users`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true`,
                  selectfield: 'id, name'
                }
            }" placeholder="" :check="false" />
        </div>

        <div v-if="isRead">
          <label class="font-semibold">Last Editor<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: true, readonly: true, clearable:false }" class="w-full py-2 !mt-0" label=""
            :value="values.last_editor_id" @input="v=>values.last_editor_id=v"
            :errorText="formErrors.last_editor_id?'failed':''" :hints="formErrors.last_editor_id" valueField="id"
            displayField="name" :api="{
                url: `${store.server.url_backend}/operation/default_users`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true`,
                  selectfield: 'id, name'
                }
            }" placeholder="" :check="false" />
        </div> -->

        <!-- UPDATE TIME -->

        <!-- <div v-if="isRead">
          <label class="font-semibold">Created At<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: true, readonly: true, clearable:false }" class="w-full py-2 !mt-0" label=""
            :value="values.created_at" @input="v=>values.created_at=v" :errorText="formErrors.created_at?'failed':''"
            :hints="formErrors.created_at" valueField="id" displayField="name" :api="{
                url: `${store.server.url_backend}/operation/default_users`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true`,
                  selectfield: 'id, name'
                }
            }" placeholder="" :check="false" />
        </div>

        <div v-if="isRead">
          <label class="font-semibold">Updated At<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: true, readonly: true, clearable:false }" class="w-full py-2 !mt-0" label=""
            :value="values.updated_at" @input="v=>values.updated_at=v" :errorText="formErrors.updated_at?'failed':''"
            :hints="formErrors.updated_at" valueField="id" displayField="name" :api="{
                url: `${store.server.url_backend}/operation/default_users`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true`,
                  selectfield: 'id, name'
                }
            }" placeholder="" :check="false" />
        </div>  -->

        <!-- END COLUMN -->
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
              class="bg-green-600 text-white font-semibold hover:bg-green-500 transition-transform duration-300 transform hover:-translate-y-0.5 rounded p-1.5 mt-3">
              <icon fa="plus" size="sm mr-0.5" /> Tambah Ke Tabel
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
                    class="text-[#8f8f8f] font-semibold text-[12px] text-capitalize p-2 text-center w-[5%] border bg-[#f8f8f8] border-[#CACACA]">
                  </td>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, i) in detailArr" :key="item.id" class="border-t" v-if="detailArr.length > 0">
                  <td class="text-[12px] text-center border border-[#CACACA]">{{ i + 1 }}.</td>
                  <td class="text-[12px] text-center border border-[#CACACA]">{{ item.nama }}.</td>
                  <td>
                    <div class="text-[12px] text-center border-[#CACACA]">
                      <button type="button" @click="removeDetail(i)" :disabled="!actionText">
                        <svg width="14" height="14" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path id="Vector" d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#F24E1E"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <!-- <tr v-for="(item, i) in " :key="item.id" class="border-t" v-if=".length > 0">
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
                </tr> -->
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


      <hr class="mt-5">
      <!-- ACTION BUTTON START -->
      <div class="flex flex-row justify-end space-x-[20px]">
        <!-- <button @click="onPost" class="bg-orange-500 hover:bg-orange-600 text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Mengajukan Persetujuan
          </button> -->
        <button @click="onBack" class="mt-5 bg-[#EF4444] hover:bg-[#ed3232] text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Batal
          </button>
        <button v-show="route.query.action?.toLowerCase() === 'verifikasi'" @click="posted" class="bg-orange-500 mt-5 hover:bg-orange-600 text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Posted
          </button>
        <button v-show="actionText" @click="onSave" class="bg-[#10B981] hover:bg-[#0ea774] text-white px-[36.5px] mt-5 py-[12px] rounded-[6px] ">
            Simpan
          </button>
      </div>
    </div>
  </div>
</div>
@endverbatim
@endif