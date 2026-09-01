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
          Form Transaksi Potongan
        </h1>
        <hr>
      </div>
      <div class="grid <md:grid-cols-1 grid-cols-2 gap-2">
        <!-- START COLUMN -->
        <div>
          <label class="font-semibold">Nomor</label>
          <FieldX :bind="{ readonly: true }" label="" class="w-full py-2 !mt-0" :value="values.nomor"
            :errorText="formErrors.nomor?'failed':''" @input="v=>values.nomor=v" :hints="formErrors.nomor"
            :check="false" label="" placeholder="nomor" />
        </div>
        <div>
          <label class="font-semibold">Karyawan<span class="text-red-500 space-x-0 pl-0">*</span></label>
          <FieldPopup :bind="{ readonly: !actionText }" class="w-full py-2 !mt-0" :value="values.m_kary_id"
            @input="(v)=>values.m_kary_id=v" :errorText="formErrors.m_kary_id?'failed':''" :hints="formErrors.m_kary_id"
            valueField="id" displayField="nama_depan" @update:valueFull="(objVal)=>{
          values.m_divisi_lama_id = objVal['m_divisi.id']
          values.m_dept_lama_id = objVal['m_dept.id']
          values.m_posisi_lama_id = objVal['m_posisi.id']
          values.m_standart_posisi_id = objVal['m_standart_gaji.gaji_pokok']
        }" :api="{
          url: `${store.server.url_backend}/operation/m_kary`,
          headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
          params: {
            simplest:true,
            paginate : 100,
            searchfield:'nik, nama_depan, m_dir.nama, m_divisi.nama, id'
          }
        }" placeholder="Pilih Karyawan" label="" :check="false" :columns="[
          {
            headerName: 'No',
            valueGetter: (p) => p.node.rowIndex + 1,
            width: 60,
            sortable: false, 
            resizable: false, 
            filter: false,
            cellClass: ['justify-center', 'bg-gray-50']
          },
          {
            flex: 1,
            field: 'nik',
            headerName: 'NIK',
            sortable: false, 
            resizable: true, 
            filter: 'ColFilter',
            cellClass: ['border-r', '!border-gray-200', 'justify-center']
          },
          {
            flex: 1,
            field: 'nama_depan',
            headerName: 'Nama',
            sortable: false, 
            resizable: true, 
            filter: 'ColFilter',
            cellClass: ['border-r', '!border-gray-200', 'justify-center']
          },
          {
            flex: 1,
            field: 'm_zona.nama',
            headerName: 'Zona',
            sortable: false, 
            resizable: true, 
            filter: 'ColFilter',
            cellClass: ['border-r', '!border-gray-200', 'justify-center']
          },              
          {
            flex: 1,
            field: 'm_dir.nama',
            headerName: 'Direktorat',
            sortable: false, 
            resizable: true, 
            filter: 'ColFilter',
            cellClass: ['border-r', '!border-gray-200', 'justify-center']
          },
          {
            flex: 1,
            field: 'm_divisi.nama',
            headerName: 'Divisi',
            sortable: false, 
            resizable: true, 
            filter: 'ColFilter',
            cellClass: ['border-r', '!border-gray-200', 'justify-center']
          },      
          {
            flex: 1,
            field: 'm_dept.nama',
            headerName: 'Departemen',
            sortable: false, 
            resizable: true, 
            filter: 'ColFilter',
            cellClass: ['border-r', '!border-gray-200', 'justify-center']
          },
        
          // Add columns for m_posisi data if needed
          ]" />

        </div>

        <div>
          <label class="font-semibold">Jenis Potongan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: !actionText, clearable:false }" class="w-full py-2 !mt-0" label=""
            :value="values.jenis_potongan_id" @input="v=>values.jenis_potongan_id=v"
            :errorText="formErrors.jenis_potongan_id?'failed':''" :hints="formErrors.jenis_potongan_id" valueField="id"
            displayField="value" :api="{
                url: `${store.server.url_backend}/operation/m_general`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true AND this.group = 'JENIS POTONGAN'`
                }
            }" placeholder="Pilih Jenis Potongan" :check="false" />
        </div>

        <div class="flex gap-4" v-show="values.jenis_potongan_id && values.m_kary_id">
          <div class="w-1/3">
            <label class="font-semibold">Total Bayar<label class="text-red-500 pl-1">*</label>
            </label>
            <FieldNumber class="w-full py-2 !mt-0" :bind="{ readonly: true, disabled: true }" :value="values.ttl_byr"
              @input="(v)=>values.ttl_byr=v" :errorText="formErrors.ttl_byr?'failed':''" :hints="formErrors.ttl_byr"
              placeholder="" label="" fa-icon="" :check="false" />
          </div>

          <div class="w-1/3">
            <label class="font-semibold">Total Pinjaman<label class="text-red-500 pl-1">*</label>
            </label>
            <FieldNumber class="w-full py-2 !mt-0" :bind="{ readonly: true, disabled: true }" :value="values.jmlh_pinj"
              @input="(v)=>values.jmlh_pinj=v" :errorText="formErrors.jmlh_pinj?'failed':''"
              :hints="formErrors.jmlh_pinj" placeholder="" label="" fa-icon="" :check="false" />
          </div>

          <div class="w-1/3">
            <label class="font-semibold">Sisa Pinjaman<label class="text-red-500 pl-1">*</label>
            </label>
            <FieldNumber class="w-full py-2 !mt-0" :bind="{ readonly: true, disabled: true }" :value="values.sisa_pinj"
              @input="(v)=>values.sisa_pinj=v" :errorText="formErrors.sisa_pinj?'failed':''"
              :hints="formErrors.sisa_pinj" placeholder="" label="" fa-icon="" :check="false" />
          </div>
        </div>


        <div>
          <label class="font-semibold">Tanggal Awal<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldX :bind="{ readonly: !actionText , required: true}" class="w-full py-2 !mt-0" :value="values.date_from"
            :errorText="formErrors.date_from?'failed':''" :hints="formErrors.date_from" :check="false" type="date"
            label="" placeholder="Pilih Tanggal Awal" @input="(v)=>{values.date_from=v}" />
        </div>
        <div>
          <label class="font-semibold">Tanggal Akhir<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldX :bind="{ readonly: !actionText , required: true}" class="w-full py-2 !mt-0" :value="values.date_to"
            :errorText="formErrors.date_to?'failed':''" :hints="formErrors.date_to" :check="false" type="date" label=""
            placeholder="Pilih Tanggal Akhir" @input="(v)=>{values.date_to=v}" />
        </div>
        <div>
          <label class="font-semibold">No.Dokumen<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldX :bind="{ readonly: !actionText }" class="w-full py-2 !mt-0" placeholder="Tuliskan Nomor Dokumen"
            :value="values.no_doc" :errorText="formErrors.no_doc?'failed':''" @input="v=>values.no_doc=v"
            :hints="formErrors.no_doc" :check="false" label="" />
        </div>

        <div v-if="values.doc === ''">
          <label class="font-semibold">Upload Dokumen</label>
          <FieldUpload class="w-full py-2 !mt-0" :bind="{ readonly: !actionText }" :value="values.doc"
            @input="(v)=>values.doc=v" :maxSize="10"
            :reducerDisplay="val => !val ? null : val.split(':::')[val.split(':::').length - 1]" :api="{
        url: `${store.server.url_backend}/operation/t_potongan/upload`,
        headers: { Authorization: `${store.user.token_type} ${store.user.token}`},
        params: { field: 'doc' },
        onsuccess: response => response,
        onerror: (error) => {},
      }" :hints="formErrors.doc" label="" placeholder="Upload Berkas" fa-icon="upload" accept="application/pdf"
            :check="false" />

        </div>

        <div v-if="values.doc !== ''">
          <label class="font-semibold">Dokumen</label>
          <div class="w-full">
            <button @click="showPdfModal = true"
          class="bg-blue-500 !mt-2 hover:bg-blue-400 text-white px-[36.5px] py-[6px] rounded-[6px]">
          Lihat Doc
        </button>

            <button
            @click="removeDoc()"class="text-red-500 ml-4 hover:text-red-400">
            <icon fa="trash" />
          </button>
          </div>
        </div>

        <!-- Modal PDF -->
        <div v-if="showPdfModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
          <div class="bg-white w-[90%] md:w-[70%] h-[90%] rounded-lg overflow-hidden relative">
            <button @click="showPdfModal = false"
          class="absolute bottom-2 right-2 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-400 z-10">
          Tutup
        </button>
            <iframe v-if="pdfUrl" :src="pdfUrl" class="w-full h-full border-none"></iframe>
            <div v-else class="flex justify-center items-center h-full">
              <p class="text-gray-500">File PDF tidak tersedia.</p>
            </div>
          </div>
        </div>


        <div>
          <label class="font-semibold">Nilai<span class="text-red-500 space-x-0 pl-0">*</span><label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldNumber :bind="{ readonly: !actionText }" class="w-full py-2 !mt-0" placeholder="0.00"
            :value="values.nilai" :errorText="formErrors.nilai?'failed':''" @input="v=>values.nilai=v"
            :hints="formErrors.nilai" :check="false" label="" />
        </div>
        <div>
          <label class="font-semibold">Persentase Angsuran<span class="text-red-500 space-x-0 pl-0">*</span><label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldNumber :bind="{ readonly: !actionText }" class="w-full py-2 !mt-0" placeholder="1%"
            :value="values.percentage" :errorText="formErrors.percentage?'failed':''" @input="v=>values.percentage=v"
            :hints="formErrors.percentage" :check="false" label="" />
        </div>
        <div>
          <label class="font-semibold">Keterangan</label>
          <FieldX class="w-full py-2 !mt-0" :bind="{ readonly: !actionText  }" type="textarea"
            :value="values.keterangan" @input="v=>values.keterangan=v" placeholder="Tulis Keterangan" label=""
            fa-icon="" :check="false" />
        </div>
        <!-- <div class="grid grid-cols-12">
          <label class="col-span-12">Potongan Seluruh Karyawan <label class="text-red-500 space-x-0 pl-0">*</label></label>
            <div class="col-span-12">
              <div class="grid grid-cols-12">
                <div class="flex items-center col-span-6">
                  <input type="radio" :value="true" v-model="values.is_all_kary" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"/>
                  <label class="ml-2  font-medium text-gray-900 dark:text-gray-300">Iya</label>
                </div>
                <div class="flex items-center col-span-6">
                  <input type="radio" :value="false" v-model="values.is_all_kary" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"/>
                  <label class="ml-2  font-medium text-gray-900 dark:text-gray-300">Tidak</label>
                </div>
              </div>
            </div>
        </div> -->
        <div class="flex flex-col gap-2">
          <label
            class="inline-block pl-[0.15rem] hover:cursor-pointer font-semibold"
            for="is_active_for_click">
            Status <span class="text-red-500 space-x-0 pl-0">*</span>
          </label>
          <FieldX class="w-full py-0 !mt-0" :bind="{ readonly: true }" :value="values.status"
            @input="v=>values.status=v" placeholder="Status" label="" fa-icon="" :check="false" />
        </div>

        <div></div>

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
        <br class="col-span-2">

        <hr class="col-span-2 !mb-5">

        <div v-if="isRead">
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
        </div>

        <!-- UPDATE TIME -->

        <div v-if="isRead">
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
        </div>

        <!-- END COLUMN -->
      </div>
      <!-- ACTION BUTTON START -->
      <div class="flex flex-row justify-end space-x-[20px] mt-[5em]">
        <!-- <button @click="onPost" class="bg-orange-500 hover:bg-orange-600 text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Mengajukan Persetujuan
          </button> -->
        <button @click="onBack" class="bg-[#EF4444] hover:bg-[#ed3232] text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Batal
          </button>
        <button v-show="route.query.action?.toLowerCase() === 'verifikasi'" @click="posted" class="bg-orange-500 hover:bg-orange-600 text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Posted
          </button>
        <button v-show="actionText" @click="onSave" class="bg-[#10B981] hover:bg-[#0ea774] text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Simpan
          </button>
      </div>
    </div>
  </div>
</div>
@endverbatim
@endif