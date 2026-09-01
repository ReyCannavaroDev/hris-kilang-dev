@if(!$req->has('id'))
<div class="bg-white p-6 rounded-xl h-[570px]">
  <TableApi ref='apiTable' :api="landing.api" :columns="landing.columns" :actions="landing.actions">
    <template #header>
     
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
          Form Transaksi Pinjaman Karyawan
        </h1>
        <hr>
      </div>
      <div class="grid <md:grid-cols-1 grid-cols-2 gap-2">
        <!-- START COLUMN -->
        <div>
          <label class="font-semibold">Nama Karyawan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: !actionText, clearable:false, disabled: !actionText }" class="w-full py-2 !mt-0" label=""
            :value="values.m_kary_id" @input="v=>values.m_kary_id=v" :errorText="formErrors.m_kary_id?'failed':''"
            :hints="formErrors.m_kary_id" valueField="id" displayField="nama_lengkap" :api="{
                url: `${store.server.url_backend}/operation/m_kary`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  selectfield: 'id, nama_lengkap, nik'
                  //where: `this.is_active = true AND this.group = 'JENIS POTONGAN'`
                }
            }" placeholder="Pilih Karyawan" :check="false" />
        </div>

        <div>
          <label class="font-semibold">Jenis Potongan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: !actionText, disabled: !actionText, clearable:false }" class="w-full py-2 !mt-0" label=""
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

        <div>
          <label class="font-semibold">Pilih Potongan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: !actionText, disabled: !actionText, clearable:true }" class="w-full py-2 !mt-0" label=""
            :value="values.t_potongan_id" @input="v=>values.t_potongan_id=v"
            :errorText="formErrors.t_potongan_id?'failed':''" :hints="formErrors.t_potongan_id" valueField="id"
            displayField="keterangan" :api="{
                url: `${store.server.url_backend}/operation/t_potongan`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.status = 'POSTED' AND this.m_kary_id = ${values.m_kary_id} AND this.jenis_potongan_id = ${values.jenis_potongan_id}`
                }
            }" placeholder="Pilih Jenis Potongan" :check="false" />
        </div>

        <div>
          <label class="font-semibold">Tanggal<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldX type="date" :bind="{ readonly: !actionText, disabled: !actionText}" class="w-full py-2 !mt-0" placeholder="Pilih Tanggal"
            :value="values.tanggal" :errorText="formErrors.tanggal?'failed':''"
            @input="v=>values.tanggal=v" :hints="formErrors.tanggal" :check="false" label="" />
        </div>

        <div>
          <label class="font-semibold">Total Hutang<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldX :bind="{ readonly: !actionText, disabled: !actionText }" class="w-full py-2 !mt-0" placeholder="Nominal Hutang"
            :value="values.total_hutang" :errorText="formErrors.total_hutang?'failed':''"
            @input="v=>values.total_hutang=v" :hints="formErrors.total_hutang" :check="false" label="" />
        </div>

        <div>
          <label
            class="inline-block pl-[0.15rem] hover:cursor-pointer font-semibold"
            for="is_active_for_click">
            Status <span class="text-red-500 space-x-0 pl-0">*</span>
          </label>
          <FieldSelect :bind="{ disabled: true, clearable: false }" class="w-full py-2 !mt-0" :value="values.is_active"
            @input="v=>{values.is_active=v}" :errorText="formErrors.is_active?'failed':''" :hints="formErrors.is_active"
            valueField="id" displayField="key" :options="[{'id' : true , 'key' : 'Aktif'},{'id': false, 'key' : 'Non Aktif'}]"
            placeholder="Pilih Status" label="" :check="false" />
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