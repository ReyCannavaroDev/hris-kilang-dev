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
          Form Cuti Di Uangkan
        </h1>
        <hr>
      </div>
      <div class="grid <md:grid-cols-1 grid-cols-2 gap-2">
        <!-- START COLUMN -->

        <div>
          <label class="font-semibold">Kode<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldX :bind="{ readonly: true, disabled: true }" class="w-full py-2 !mt-0" placeholder="Auto Generate By System"
            :value="values.kode" :errorText="formErrors.kode?'failed':''" @input="v=>values.kode=v"
            :hints="formErrors.kode" :check="false" label="" />
        </div>

        <div>
          <label class="font-semibold">Tanggal<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldX type="date" :bind="{ readonly: true, disabled: true}" class="w-full py-2 !mt-0"
            placeholder="Pilih Tanggal" :value="values.tanggal" :errorText="formErrors.tanggal?'failed':''"
            @input="v=>values.tanggal=v" :hints="formErrors.tanggal" :check="false" label="" />
        </div>

        <div>
          <label class="font-semibold">Nama Karyawan<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: !actionText, clearable:false, disabled: !actionText }"
            class="w-full py-2 !mt-0" label="" :value="values.m_kary_id" @input="v=>values.m_kary_id=v"
            :errorText="formErrors.m_kary_id?'failed':''" :hints="formErrors.m_kary_id" valueField="id"
            @update:valueFull="(response)=>{
                  values.gaji = response.salary
                  values.sisa_cuti = response.sisa_cuti_satu_hari
                  values.m_dept_id = null
                }" displayField="nama_lengkap" :api="{
                url: `${store.server.url_backend}/operation/m_kary`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:true,
                  cuti_uang: true,
                  //where: `this.sisa_cuti_satu_hari = '0'`
                }
            }" placeholder="Pilih Karyawan" :check="false" />
        </div>
        
        <div>
          <label class="font-semibold">Jumlah Cuti Diuangkan<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldX :bind="{ readonly: !actionText, disabled: !actionText }" class="w-full py-2 !mt-0"
            placeholder="Jumlah Pengajuan" :value="values.jumlah_cuti_diuangkan"
            :errorText="formErrors.jumlah_cuti_diuangkan?'failed':''" @input="v=>values.jumlah_cuti_diuangkan=v"
            :hints="formErrors.jumlah_cuti_diuangkan" :check="false" label="" />
        </div>

        <div>
          <label class="font-semibold">Sisa Cuti<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldNumber :bind="{ readonly: true, disabled: true }" class="w-full py-2 !mt-0"
            placeholder="Sisa Cuti" :value="values.sisa_cuti_all" :errorText="formErrors.sisa_cuti_all?'failed':''"
            @input="v=>values.sisa_cuti_all=v" :hints="formErrors.sisa_cuti_all" :check="false" label="" />
        </div>

        <div>
          <label class="font-semibold">Nominal<label class="text-red-500 space-x-0 pl-0"></label></label>
          <FieldNumber :bind="{ readonly: true, disabled: true }" class="w-full py-2 !mt-0"
            placeholder="Nominal" :value="values.nominal" :errorText="formErrors.nominal?'failed':''"
            @input="v=>values.nominal=v" :hints="formErrors.nominal" :check="false" label="" />
        </div>

        <div>
          <label class="inline-block pl-[0.15rem] hover:cursor-pointer font-semibold">Status</label>
          <FieldX :bind="{ readonly: true }"
            class="w-full py-2 !mt-0" 
            :value="values.status" :errorText="formErrors.status?'failed':''"
            @input="v=>values.status=v" :hints="formErrors.status" 
            placeholder="" label="" fa-icon="" :check="false"
          />
          
        </div>
<!-- 
        <div>
          <label class="font-semibold">Adjustment Cuti<label class="text-red-500 space-x-0 pl-0">*</label></label>
          <FieldSelect :bind="{ disabled: !actionText, disabled: true, clearable:true }" class="w-full py-2 !mt-0"
            label="" :value="values.t_cuti_adjustment_id" @input="v=>values.t_cuti_adjustment_id=v"
            :errorText="formErrors.t_cuti_adjustment_id?'failed':''" :hints="formErrors.t_cuti_adjustment_id"
            valueField="id" displayField="value" :api="{
                url: `${store.server.url_backend}/operation/t_cuti_adjustment`,
                headers: { 'Content-Type': 'Application/json', Authorization: `${store.user.token_type} ${store.user.token}`},
                params: {
                  simplest:true,
                  transform:false,
                  join:false,
                  where: `this.is_active = true`
                }
            }" placeholder="Auto Field By System" :check="false" />
        </div> -->
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