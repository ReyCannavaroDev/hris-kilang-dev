@if (!$req->has('id'))
<div class="bg-white p-6 rounded-xl h-[570px]">
  <TableApi ref='apiTable' :api="landing.api" :columns="landing.columns" :actions="landing.actions">
    <template #header>
      <RouterLink v-if="currentMenu?.can_create||true||store.user.data.username==='developer'"
        :to="$route.path + '/create?' + (Date.parse(new Date()))"
        class="bg-blue-500 text-white hover:bg-blue-600 rounded-[6px] py-2 px-[12.5px]">
        <icon fa="bolt" />Generate Gaji
      </RouterLink>
    </template>
  </TableApi>
</div>
@else
@verbatim
<div class="flex flex-col gap-y-3 px-2">
  <div class="flex gap-x-4 w-full">
    <div class="flex flex-col border rounded shadow-sm px-6 py-6 w-full bg-white overflow-x-auto">

      <div class="mb-4">
        <h1 class="text-[24px] mb-4 font-bold">
          Perhitungan Gaji
        </h1>
        <hr>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div>
          <label class="font-semibold">Periode Awal<span class="text-red-500">*</span></label>
          <FieldX :bind="{ readonly: !actionText, required: true }" class="w-full py-2 !mt-0"
            :value="values.periode_awal_form" :errorText="formErrors.periode_awal_form ? 'failed' : ''"
            :hints="formErrors.periode_awal_form" :check="false" type="date" @input="v => {
              checkTglStDate(v);
              values.periode_awal_form = v;
              detailArr = [];
            }" label="" placeholder="YYYY-MM-DD" />
        </div>
        <div>
          <label class="font-semibold">Periode Akhir<span class="text-red-500">*</span></label>
          <FieldX :bind="{ readonly: !actionText, required: true }" class="w-full py-2 !mt-0"
            :value="values.periode_akhir_form" :errorText="formErrors.periode_akhir_form ? 'failed' : ''"
            :hints="formErrors.periode_akhir_form" :check="false" type="date" @input="v => {
              checkTglEdDate(v);
              values.periode_akhir_form = v;
              detailArr = [];
            }" label="" placeholder="YYYY-MM-DD" />
        </div>
        <div>
          <label class="font-semibold">Divisi<span class="text-red-500">*</span></label>
          <FieldSelect class="w-full py-2 !mt-0" :bind="{ disabled: !actionText, clearable: true }"
            :value="values.divisi" :check="false" @input="v => {
              values.divisi = v;
              values.m_divisi_id = v;
              values.m_dept_id = '';
              detailArr = [];
            }" :errorText="formErrors.divisi ? 'failed' : ''" :hints="formErrors.divisi" displayField="nama"
            valueField="id" :api="{
              url: `${store.server.url_backend}/operation/m_divisi`,
              headers: {
                Authorization: `${store.user.token_type} ${store.user.token}`
              },
              params: {
                simplest: true,
                single: true,
                where: `this.is_active='true'`,
                transform: false
              }
            }" fa-icon="search" :check="true" />
        </div>
        <div>
          <label class="font-semibold">Departemen<span class="text-red-500">*</span></label>
          <FieldSelect class="w-full py-2 !mt-0" :bind="{ disabled: !actionText, clearable: true }"
            :value="values.m_dept_id" :check="false" @input="v => {
              values.m_dept_id = v;
              detailArr = [];
            }" :errorText="formErrors.m_dept_id ? 'failed' : ''" :hints="formErrors.m_dept_id" displayField="nama"
            valueField="id" :api="{
              url: `${store.server.url_backend}/operation/m_dept`,
              headers: {
                Authorization: `${store.user.token_type} ${store.user.token}`
              },
              params: {
                simplest: true,
                single: true,
                scopes: 'filterDivisi',
                divisi_id: values.m_divisi_id ?? null,
                transform: false
              }
            }" fa-icon="search" :check="true" />
        </div>

        <div>
          <label class="font-semibold">Karyawan<span class="text-red-500">*</span></label>
          <FieldSelect class="w-full py-2 !mt-0" :bind="{ disabled: !actionText, clearable: true }"
            :value="values.m_kary_id" :check="false" @input="v => {
              values.m_kary_id = v;
              detailArr = [];
            }" :errorText="formErrors.m_kary_id ? 'failed' : ''" :hints="formErrors.m_kary_id" displayField="nama_lengkap"
            valueField="id" :api="{
              url: `${store.server.url_backend}/operation/m_kary`,
              headers: {
                Authorization: `${store.user.token_type} ${store.user.token}`
              },
              params: {
                where: `this.is_active = true ${values.m_dept_id ? ` AND this.m_dept_id = ${values.m_dept_id}` : ''} ${values.m_divisi_id ? ` AND this.m_divisi_id = ${values.m_divisi_id}` : ''}`
              }
            }" fa-icon="search" :check="true" />
        </div>
      </div>

      <div class="flex flex-row justify-center space-x-[10px] mt-[1em]">
        <button
          @click="generatePerhitungan"
          class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-[18px] py-[8px] rounded-[4px]"
        >
          <Icon fa="bolt" /> Generate
        </button>
        <button
          @click="detailArr = []"
          class="bg-[#EF4444] hover:bg-[#ed3232] text-white text-sm px-[18px] py-[8px] rounded-[4px]"
        >
          Hapus Detail
        </button>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[700px] table-auto border border-[#CACACA]" style="zoom: 80%">
          <thead>
            <tr class="border">
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 py-[14.5px] text-center w-[5%] border bg-[#f8f8f8] border-[#CACACA]">
                No.
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[10%] border bg-[#f8f8f8] border-[#CACACA]">
                Periode
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[12%] border bg-[#f8f8f8] border-[#CACACA]">
                NIK
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[20%] border bg-[#f8f8f8] border-[#CACACA]">
                Karyawan
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[12%] border bg-[#f8f8f8] border-[#CACACA]">
                Divisi
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[12%] border bg-[#f8f8f8] border-[#CACACA]">
                Departemen
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[15%] border bg-[#f8f8f8] border-[#CACACA]">
                Gaji Bersih
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[15%] border bg-[#f8f8f8] border-[#CACACA]">
                Deskripsi
              </td>
              <td
                class="text-[#8F8F8F] font-semibold text-[14px] text-capitalize px-2 text-center w-[5%] border bg-[#f8f8f8] border-[#CACACA]">
                Aksi
              </td>
            </tr>
          </thead>
          <tbody>
            <tr v-if="detailArr.length" v-for="(item, i) in detailArr" :key="item.id"
              class="border-t hover:bg-yellow-200 cursor-pointer" @click="openDetail(i)">
              <td class="text-center border border-[#CACACA] px-2">
                {{ i + 1 }}.
              </td>
              <td class="text-left border border-[#CACACA] px-2">
                {{ item.periode }}
              </td>
              <td class="text-left border border-[#CACACA] px-2">
                {{ item['m_kary.nik'] }}
              </td>
              <td class="text-left border border-[#CACACA] px-2">
                {{ item.karyawan }}
              </td>
              <td class="text-left border border-[#CACACA] px-2">
                {{ item['m_kary_divisi.nama'] }}
              </td>
              <td class="text-left border border-[#CACACA] px-2">
                {{ item['m_kary_dept.nama'] }}
              </td>
              <td class="text-right border border-[#CACACA] px-2">
                {{ formatRupiah(item.netto) }}
              </td>
              <td class="border border-[#CACACA]">
                <FieldX :bind="{ readonly: !actionText }" class="!mt-0" :value="item.deskripsi"
                  @input="v => item.deskripsi = v" type="textarea" label="" :check="false" />
              </td>
              <td class="border border-[#CACACA]">
                <div class="flex justify-center">
                  <button
                    v-show="actionText"
                    @click.stop="openDetail(i)"
                    class="rounded-lg flex items-center justify-center"
                  >
                    <icon fa="circle-info" size="lg" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-else class="text-center">
              <td colspan="9" class="py-[20px]">
                No data to show
              </td>
            </tr>
          </tbody>
        </table>
      </div>
         <div class="flex flex-row justify-end space-x-[20px] mt-[5em]">
        <!-- <button @click="onPost" class="bg-orange-500 hover:bg-orange-600 text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Mengajukan Persetujuan
          </button> -->
        <button @click="onBack" class="bg-gray-400 hover:bg-gray-500 text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Kembali
          </button>
        <button v-show="actionText" @click="onSave" class="bg-[#10B981] hover:bg-[#0ea774] text-white px-[36.5px] py-[12px] rounded-[6px] ">
            Simpan
          </button>
      </div>

        @endverbatim
        @endif

        @verbatim
        <!-- Modal Content -->
        <div v-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black opacity-50" @click.self="closeModal"></div>
          <div class="relative bg-white w-[70%] mx-auto rounded shadow-lg overflow-y-auto max-h-[90vh] z-10">
            <div class="modal-content py-4 text-left px-6">
              <div class="modal-header flex items-center justify-between flex-wrap">
                <div class="flex items-center">
                  <h3 class="text-xl font-semibold ml-2">Rincian Gaji {{ titleOpen }}</h3>
                </div>
              </div>
              <div class="modal-body">
                <div class="overflow-x-auto mt-2 border border-[#CACACA]">
                  <table class="w-full table-fixed">
                    <thead class="bg-dark-500 text-white">
                      <tr>
                        <th class="font-bold text-capitalize p-2 text-left w-[20%] border border-[#CACACA]">Komponen
                        </th>
                        <th class="font-bold text-capitalize p-2 text-right w-[15%] border border-[#CACACA]">Besaran
                        </th>
                      </tr>
                    </thead>
                  </table>
                  <div class="max-h-60 overflow-y-auto">
                    <table class="w-full table-fixed">
                      <tbody>
                        <tr class="border" v-for="(a, i) in detailArrOpen.items" :key="i">
                          <td class="text-left border border-gray-300 p-1 w-[20%]"
                            :class="a.factor == '=' ? 'font-bold bg-gray-200' : ''">
                            {{ a.label }}
                          </td>
                          <td class="text-right border border-gray-300 p-1 w-[15%]"
                            :class="a.factor == '=' ? 'font-bold bg-gray-200' : ''">
                            {{ (a.factor == '-' ? '(-) ' : '') + formatRupiah(a.value) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="modal-footer flex justify-end mt-2">
                <button @click="closeModal"
          class="modal-button bg-red-500 hover:bg-red-600 text-white font-semibold ml-2 px-2 py-1 rounded-sm">
          Tutup
        </button>
              </div>
            </div>
          </div>
        </div>
        @endverbatim