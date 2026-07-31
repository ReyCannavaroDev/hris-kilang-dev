@if (!$req->has('id'))
<div class="bg-white p-6 rounded-xl">
  @verbatim
  <div class="flex flex-wrap gap-3 mb-4 items-end">
    <div class="min-w-[180px]">
      <label class="block text-sm font-semibold mb-1">Tahun</label>
      <FieldX :bind="{ clearable: true }" class="w-full !mt-0" :value="filters.tahun" :check="false"
        type="number" label="" placeholder="2026" @input="v => filters.tahun = v" />
    </div>

    <div class="min-w-[260px]">
      <label class="block text-sm font-semibold mb-1">Kota</label>
      <FieldSelect class="w-full !mt-0" :bind="{ clearable: true }" :value="filters.kota_id" :check="false"
        @input="v => filters.kota_id = v" displayField="value" valueField="id" :api="{
          url: `${store.server.url_backend}/operation/m_general`,
          headers: {
            Authorization: `${store.user.token_type} ${store.user.token}`
          },
          params: {
            simplest: true,
            single: true,
            where: `this.group = 'KOTA' and this.is_active = true`,
            transform: false
          }
        }" fa-icon="search" />
    </div>

    <div class="min-w-[160px]">
      <label class="block text-sm font-semibold mb-1">Jenis</label>
      <select v-model="filters.jenis"
        class="w-full border border-gray-300 rounded-[6px] px-3 py-[10px] bg-white text-sm">
        <option value="">Semua</option>
        <option value="UMK">UMK</option>
        <option value="UMSK">UMSK</option>
      </select>
    </div>

    <label class="flex items-center gap-2 text-sm font-medium mb-2">
      <input type="checkbox" v-model="filters.is_active" class="h-4 w-4" />
      Aktif saja
    </label>

    <button @click="applyFilter"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-[6px] text-sm">
      <icon fa="magnifying-glass" /> Cari
    </button>
    <button @click="resetFilter"
      class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-[6px] text-sm">
      <icon fa="rotate-right" /> Reset
    </button>
  </div>

  <TableApi ref="apiTable" :api="landing.api" :columns="landing.columns" :actions="landing.actions">
    <template #header>
      <div class="flex flex-wrap gap-2 justify-end w-full">
        <button @click="exportCsv"
          class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-[6px] py-2 px-4 text-sm">
          <icon fa="file-arrow-down" /> Export CSV
        </button>
        <RouterLink v-if="currentMenu?.can_create || true || store.user.data.username==='developer'"
          :to="$route.path + '/create?' + (Date.parse(new Date()))"
          class="bg-blue-500 text-white hover:bg-blue-600 rounded-[6px] py-2 px-4 text-sm">
          <icon fa="plus" /> Tambah BPJS
        </RouterLink>
      </div>
    </template>
  </TableApi>
  @endverbatim
</div>
@else
@verbatim
<div class="flex flex-col gap-y-3 px-2">
  <div class="flex gap-x-4 w-full">
    <div class="flex flex-col border rounded shadow-sm px-6 py-6 w-full bg-white">
      <div class="mb-4">
        <h1 class="text-[24px] mb-2 font-bold">
          Master BPJS
        </h1>
        <p class="text-sm text-gray-500">
          Simpan nominal UMK/UMSK per kota dan tahun untuk basis perhitungan BPJS.
        </p>
        <hr class="mt-4">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="font-semibold">Kota<span class="text-red-500">*</span></label>
          <FieldSelect class="w-full py-2 !mt-0" :bind="{ disabled: !actionText, clearable: true }"
            :value="values.kota_id" :check="false" @input="v => values.kota_id = v"
            :errorText="formErrors.kota_id ? 'failed' : ''" :hints="formErrors.kota_id" displayField="value"
            valueField="id" :api="{
              url: `${store.server.url_backend}/operation/m_general`,
              headers: {
                Authorization: `${store.user.token_type} ${store.user.token}`
              },
              params: {
                simplest: true,
                single: true,
                where: `this.group = 'KOTA' and this.is_active = true`,
                transform: false
              }
            }" fa-icon="search" :check="true" />
        </div>

        <div>
          <label class="font-semibold">Jenis<span class="text-red-500">*</span></label>
          <select :disabled="!actionText" v-model="values.jenis"
            class="w-full border border-gray-300 rounded-[6px] px-3 py-[10px] bg-white">
            <option value="UMK">UMK</option>
            <option value="UMSK">UMSK</option>
          </select>
        </div>

        <div>
          <label class="font-semibold">Tahun<span class="text-red-500">*</span></label>
          <FieldX :bind="{ readonly: !actionText, required: true }" class="w-full py-2 !mt-0"
            :value="values.tahun" :errorText="formErrors.tahun ? 'failed' : ''" :hints="formErrors.tahun"
            :check="false" type="number" label="" placeholder="2026" @input="v => values.tahun = v" />
        </div>

        <div>
          <label class="font-semibold">Nominal<span class="text-red-500">*</span></label>
          <FieldX :bind="{ readonly: !actionText, required: true }" class="w-full py-2 !mt-0"
            :value="values.nominal" :errorText="formErrors.nominal ? 'failed' : ''" :hints="formErrors.nominal"
            :check="false" type="number" label="" placeholder="3265908" @input="v => values.nominal = v" />
        </div>

        <div>
          <label class="font-semibold">Berlaku Mulai</label>
          <FieldX :bind="{ readonly: !actionText }" class="w-full py-2 !mt-0"
            :value="values.effective_from" :errorText="formErrors.effective_from ? 'failed' : ''"
            :hints="formErrors.effective_from" :check="false" type="date" label=""
            @input="v => values.effective_from = v" />
        </div>

        <div>
          <label class="font-semibold">Berlaku Sampai</label>
          <FieldX :bind="{ readonly: !actionText }" class="w-full py-2 !mt-0"
            :value="values.effective_to" :errorText="formErrors.effective_to ? 'failed' : ''"
            :hints="formErrors.effective_to" :check="false" type="date" label=""
            @input="v => values.effective_to = v" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <label class="flex items-center gap-2">
          <input type="checkbox" :disabled="!actionText" v-model="values.is_default" class="h-4 w-4" />
          Default
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" :disabled="!actionText" v-model="values.is_active" class="h-4 w-4" />
          Aktif
        </label>
      </div>

      <div class="mt-4">
        <label class="font-semibold">Deskripsi</label>
        <FieldX :bind="{ readonly: !actionText }" class="w-full !mt-0"
          :value="values.desc" :errorText="formErrors.desc ? 'failed' : ''"
          :hints="formErrors.desc" :check="false" type="textarea" label=""
          @input="v => values.desc = v" />
      </div>

      <div class="flex flex-row justify-end space-x-[12px] mt-[2em]">
        <button @click="onBack" class="bg-gray-400 hover:bg-gray-500 text-white px-[28px] py-[10px] rounded-[6px]">
          Kembali
        </button>
        <button v-show="actionText" @click="onReset"
          class="bg-orange-500 hover:bg-orange-600 text-white px-[28px] py-[10px] rounded-[6px]">
          Reset
        </button>
        <button v-show="actionText" @click="onSave"
          class="bg-[#10B981] hover:bg-[#0ea774] text-white px-[28px] py-[10px] rounded-[6px]">
          Simpan
        </button>
      </div>
    </div>
  </div>
</div>
@endverbatim
@endif
