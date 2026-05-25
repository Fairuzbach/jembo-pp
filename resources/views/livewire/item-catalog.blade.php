<?php

use function Livewire\Volt\{state, computed, with};
use App\Models\MasterItem;
use App\Models\ItemRequest;
use App\Models\ItemGroup;
use Livewire\Volt\Component;

// State Navigasi Tab
state(['activeTab' => 'master_items']);

// Variabel untuk Pencarian
state(['search' => '']);

// Variabel untuk Form Pengajuan (Tanpa Item Group sesuai permintaan)
state([
    'showModal' => false,
    'newName' => '',
    'newDescription' => '',
    'newPurpose' => '',
    'newUnit' => '',
    'newPrice' => '',
    'item_code' => '',

    'selectedCategory' => '',
    'selectedSubcategory' => '',
    'selectedType' => '',
    'departmentCode' => strtoupper(auth()->user()->dept->code ?? 'XX'),

    // Variabel Khusus Item Group
    'isNewGroup' => false,
    'selectedGroupId' => '',
    'groupSearch' => '', // Untuk kotak pencarian group
    'newGroupCode' => '',
    'newGroupDesc' => '',
]);

with(
    fn() => [
        'categories' => [
            'SV' => 'SV - SERVICE/JASA',
            'IT' => 'IT - IT',
            'SA' => 'SA - SAFETY & PPE',
            'PK' => 'PK - PRINTING/PACKAGING/STATIONERY',
            'CH' => 'CH - CHEMICAL/LUBRICANT/PAINT',
            'IN' => 'IN - INSTRUMENT/TEST/CALIBRATION',
            'TL' => 'TL - TOOLS/PERKAKAS',
            'HD' => 'HD - HARDWARE/FASTENER',
            'CM' => 'CM - CABLE & PROCESS TOOLING',
            'OT' => 'OT - OTHER',
            'KB' => 'KB - KEBERSIHAN',
            'AK' => 'AK - ATK',
        ],

        'subcategories' => [
            'SV' => ['CALB' => 'CALIBRATION SERVICE', 'FABR' => 'FABRICATION SERVICE', 'INST' => 'INSTALLATION SERVICE', 'REPR' => 'REPAIR', 'SERV' => 'GENERAL SERVICE', 'RENT' => 'RENTAL SERVICE'],
            'IT' => ['COMP' => 'COMPUTER HARDWARE', 'ITGN' => 'GENERAL IT', 'NETW' => 'NETWORKING', 'PRNT' => 'PRINTER & IMAGING', 'STOR' => 'STORAGE DEVICE'],
            'SA' => ['PPEX' => 'PPE', 'FIRE' => 'FIRE SAFETY', 'SAFE' => 'GENERAL SAFETY'],
            'PK' => ['PACK' => 'PACKAGING GENERAL', 'LABL' => 'LABEL & STICKER'],
            'CH' => ['LUBE' => 'LUBRICANT', 'CHEM' => 'CHEMICAL GENERAL', 'PAIN' => 'PAINT & THINNER', 'ADHV' => 'ADHESIVE/SEALANT'],
            'IN' => ['GAUG' => 'GAUGE', 'INST' => 'GENERAL INSTRUMENT', 'MEAS' => 'MEASURING INSTRUMENT', 'TEST' => 'TESTING INSTRUMENT', 'WEIG' => 'WEIGHING INSTRUMENT'],
            'TL' => ['HAND' => 'HAND TOOLS', 'PWRT' => 'POWER TOOLS', 'TOOL' => 'GENERAL TOOL', 'CUTT' => 'CUTTING TOOLS'],
            'HD' => ['FAST' => 'FASTENER / BAUT', 'BOLT' => 'BOLT', 'NUTS' => 'NUT', 'WASH' => 'WASHER'],
            'CM' => ['CABL' => 'CABLE / CABLE PRODUCT', 'DIES' => 'DIE', 'EMBS' => 'EMBOSS ITEM', 'MOUL' => 'MOULD', 'PROC' => 'PROCESS TOOLING'],
            'KB' => ['CLEA' => 'CLEANING CHEMICAL', 'TOOL' => 'PERALATAN KEBERSIHAN'],
            'AK' => ['STAT' => 'STATIONERY', 'INKS' => 'INK/TONER/RIBBON', 'PRNT' => 'PRINTING', 'PAPR' => 'PAPER'],
            'OT' => ['OTHR' => 'OTHER / LAIN-LAIN'],
        ],

        'types' => [
            'SRV' => 'SRV - SERVICE/REPAIR/JASA',
            'CAL' => 'CAL - CALIBRATION/TESTING',
            'FBR' => 'FBR - FABRICATION/CUSTOM MADE',
            'INS' => 'INS - INSTALLATION/SETUP',
            'RNT' => 'RNT - RENTAL/SEWA',
            'ASM' => 'ASM - ASSEMBLY/KIT/SET/MODULE',
            'DOC' => 'DOC - DOCUMENT/STANDARD/SPECIFICATION',
            'TOL' => 'TOL - TOOL/PERKAKAS',
            'EQU' => 'EQU - EQUIPMENT/MESIN/DEVICE',
            'CON' => 'CON - CONSUMABLE',
            'MAT' => 'MAT - RAW MATERIAL',
            'PRT' => 'PRT - PART/COMPONENT',
        ],
    ],
);

$generateItemCode = function () {
    if ($this->departmentCode && $this->selectedCategory && $this->selectedSubcategory && $this->selectedType) {
        $prefix = strtoupper($this->departmentCode . $this->selectedCategory . $this->selectedSubcategory . $this->selectedType);

        // Cek nilai tertinggi di Master Item dan Request (hindari bentrok)
        $lastMaster = MasterItem::where('item_code', 'like', $prefix . '%')->max('item_code');
        $lastRequest = ItemRequest::where('item_code', 'like', $prefix . '%')
            ->where('status', '!=', 'rejected')
            ->max('item_code');

        $highestCode = max($lastMaster, $lastRequest);
        $nextNumber = $highestCode ? ((int) substr($highestCode, -5)) + 1 : 1;

        $this->item_code = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    } else {
        $this->dispatch('swal', ['icon' => 'warning', 'title' => 'Gagal', 'text' => 'Pilih Kategori, Sub-Kategori, dan Tipe Barang terlebih dahulu!']);
    }
};

// Computed: Daftar Item Group untuk Tab Referensi
$itemGroups = computed(function () {
    return ItemGroup::orderBy('code', 'asc')->get();
});

$filteredGroups = computed(function () {
    return ItemGroup::where('code', 'like', '%' . $this->groupSearch . '%')
        ->orWhere('description', 'like', '%' . $this->groupSearch . '%')
        ->orderBy('code', 'asc')
        ->get();
});

// Computed: Pencarian Master Item
$items = computed(function () {
    return MasterItem::with(['department', 'group'])
        ->when($this->search, function ($query) {
            $query
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('item_code', 'like', '%' . $this->search . '%')
                ->orWhereHas('group', function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
                });
        })
        ->orderBy('name', 'asc')
        ->get();
});

// Computed: Data Belum Sinkron (is_synced = false)
$unsyncedItems = computed(function () {
    return MasterItem::with(['department', 'group'])
        ->where('is_synced', false)
        ->when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')->orWhere('item_code', 'like', '%' . $this->search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->get();
});

$isPoolingTeam = computed(function () {
    $deptCode = strtoupper(auth()->user()->dept->code ?? '');

    // Daftar kode departemen yang diizinkan melakukan pengajuan item baru
    $allowedPoolingDepts = ['FH', 'GA', 'IT', 'SC', 'SS', 'QA', 'MT', 'PE'];

    return in_array($deptCode, $allowedPoolingDepts);
});

$isProcurementTeam = computed(function () {
    $deptCode = strtoupper(auth()->user()->dept->code ?? '');

    return $deptCode === 'SC';
});

// Fungsi Simpan Pengajuan
$submitRequest = function () {
    // SECURITY GUARD: Blokir jika kode departemen user tidak ada di dalam daftar whitelist
    if (!$this->isPoolingTeam) {
        $this->dispatch('swal', [
            'icon' => 'error',
            'title' => 'Akses Ditolak',
            'text' => 'Departemen Anda tidak memiliki hak akses untuk membuat pengajuan item baru!',
        ]);
        return;
    }

    // 1. Validasi Input Dasar
    if (empty($this->newName) || empty($this->newUnit)) {
        $this->dispatch('swal', ['icon' => 'warning', 'title' => 'Gagal', 'text' => 'Nama Item dan Satuan wajib diisi.']);
        return;
    }

    if (empty($this->item_code)) {
        $this->dispatch('swal', ['icon' => 'warning', 'title' => 'Gagal', 'text' => 'Silakan generate Item Code terlebih dahulu.']);
    }

    // 2. Validasi Item Group
    if ($this->isNewGroup) {
        if (empty($this->newGroupCode) || empty($this->newGroupDesc)) {
            $this->dispatch('swal', ['icon' => 'warning', 'title' => 'Gagal', 'text' => 'Kode dan Deskripsi Item Group baru wajib diisi.']);
            return;
        }
    } else {
        if (empty($this->selectedGroupId)) {
            $this->dispatch('swal', ['icon' => 'warning', 'title' => 'Gagal', 'text' => 'Silakan pilih Item Group atau centang opsi buat baru.']);
            return;
        }
    }

    // 3. Simpan ke Item Request (Sebagai PENDING)
    ItemRequest::create([
        'requester_id' => auth()->user()->id,
        'item_group_id' => $this->isNewGroup ? null : $this->selectedGroupId,
        'new_group_code' => $this->isNewGroup ? strtoupper($this->newGroupCode) : null,
        'new_group_desc' => $this->isNewGroup ? $this->newGroupDesc : null,
        'name' => strtoupper($this->newName),
        'description' => strtoupper($this->newDescription),
        'purpose' => $this->newPurpose,
        'unit' => $this->newUnit,
        'estimated_price' => $this->newPrice ? $this->newPrice : null,
        'status' => 'pending',
        'department_code' => $this->departmentCode,
        'category_code' => $this->selectedCategory,
        'subcategory_code' => $this->selectedSubcategory,
        'type_code' => $this->selectedType,
        'item_code' => $this->item_code,
    ]);

    // 4. Reset Form & Tutup Modal
    $this->showModal = false;
    $this->reset(['newName', 'newDescription', 'newPurpose', 'newUnit', 'newPrice', 'isNewGroup', 'selectedGroupId', 'groupSearch', 'newGroupCode', 'newGroupDesc', 'selectedCategory', 'selectedSubcategory', 'selectedType', 'departmentCode']);
    $this->dispatch('swal', ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Pengajuan berhasil dikirim dan menunggu persetujuan Procurement.']);
};

?>

<div x-data="{ modalTerbuka: $wire.entangle('showModal') }" class="max-w-7xl mx-auto p-6">
    <div class="neo-card bg-[#f0f4f8] rounded-3xl p-8"
        style="box-shadow: 12px 12px 24px #d1d9e6, -12px -12px 24px #ffffff;">

        <div class="flex gap-8 mb-8 border-b-2 border-gray-200 overflow-x-auto">
            <button wire:click="$set('activeTab', 'master_items')"
                class="pb-4 text-sm font-black uppercase tracking-widest whitespace-nowrap transition-all {{ $activeTab === 'master_items' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                📦 Semua Item
            </button>

            @if ($this->isProcurementTeam)

                <button wire:click="$set('activeTab', 'unsynced')"
                    class="pb-4 text-sm font-black uppercase tracking-widest whitespace-nowrap transition-all flex items-center gap-2 {{ $activeTab === 'unsynced' ? 'text-orange-600 border-b-4 border-orange-600' : 'text-gray-400 hover:text-gray-600' }}">
                    ⏳ Belum Sync
                    @if (count($this->unsyncedItems) > 0)
                        <span class="bg-orange-500 text-white text-[10px] px-2 py-0.5 rounded-full">
                            {{ count($this->unsyncedItems) }}
                        </span>
                    @endif
                </button>
            @endif

            <button wire:click="$set('activeTab', 'item_groups')"
                class="pb-4 text-sm font-black uppercase tracking-widest whitespace-nowrap transition-all {{ $activeTab === 'item_groups' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                📑 Item Group
            </button>
        </div>

        <div class="animate-fade-in">
            @if ($activeTab !== 'item_groups')
                <div class="flex justify-between items-center mb-6">
                    <div class="relative w-1/3">
                        <input type="text" wire:model.live="search" placeholder="Cari data..."
                            class="neo-inset w-full pl-12 pr-4 py-3 rounded-xl text-sm font-bold text-gray-700 focus:outline-none"
                            style="border:none;">
                        <span class="absolute left-4 top-3.5 text-gray-400 text-lg">🔍</span>
                    </div>

                    <div class="flex gap-3">
                        @if ($activeTab === 'unsynced' && count($this->unsyncedItems) > 0)
                            <a href="{{ route('export.erp.bundle') }}"
                                class="neo-btn bg-green-600 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition-all flex items-center gap-2">
                                🚀 Export ke ERP (ZIP)
                            </a>
                        @endif
                        @if ($this->isPoolingTeam)
                            <button @click="modalTerbuka = true"
                                class="neo-btn bg-[#2563eb] text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700">
                                + Ajukan Item
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            @if ($activeTab === 'master_items')
                <div class="overflow-hidden rounded-2xl border-2 border-white shadow-sm bg-white/50">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Item Code</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Item Group</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    Nama Barang</th>
                                <th
                                    class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">
                                    Sync Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($this->items as $item)
                                <tr class="hover:bg-white transition-all">
                                    <td class="py-4 px-6 text-xs font-black text-blue-600">
                                        {{ $item->item_code ?? 'TBD' }}
                                    </td>

                                    <td class="py-4 px-6">
                                        @if ($item->group)
                                            <div class="text-xs font-black text-blue-600">{{ $item->group->code }}</div>
                                            <div
                                                class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                                                {{ $item->group->description }}</div>
                                        @else
                                            <span class="text-xs text-gray-400 italic font-bold">Belum diset</span>
                                        @endif
                                    </td>

                                    <td class="py-4 px-6 text-sm font-bold text-gray-800">{{ $item->name }}</td>
                                    <td class="py-4 px-6 text-center">
                                        @if ($item->is_synced)
                                            <span
                                                class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">Synced</span>
                                        @else
                                            <span
                                                class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-20 text-center text-gray-400 italic font-bold">Data
                                        kosong.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($activeTab === 'unsynced')
                <div class="overflow-hidden rounded-2xl border-2 border-white shadow-sm bg-white/50">
                    <table class="w-full text-left">
                        <thead class="bg-orange-50 border-b border-orange-100">
                            <tr>
                                <th class="py-4 px-6 text-[10px] font-black text-orange-400 uppercase tracking-widest">
                                    Nama Barang</th>
                                <th
                                    class="py-4 px-6 text-[10px] font-black text-orange-400 uppercase tracking-widest text-center">
                                    Tgl Input</th>
                                <th
                                    class="py-4 px-6 text-[10px] font-black text-orange-400 uppercase tracking-widest text-right">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-50">
                            @forelse($this->unsyncedItems as $item)
                                <tr class="hover:bg-white transition-all">
                                    <td class="py-4 px-6">
                                        <div class="text-sm font-bold text-gray-800">{{ $item->name }}</div>
                                        <div
                                            class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                                            {{ $item->group ? $item->group->code : 'Group Belum Diset' }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center text-xs font-bold text-gray-500">
                                        {{ $item->created_at->format('d M Y') }}
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button
                                            class="text-blue-600 hover:underline text-xs font-black uppercase tracking-widest">Detail</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-20 text-center text-gray-400 italic font-bold">
                                        Semua data sudah sinkron dengan ERP. ✨
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($activeTab === 'item_groups')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @forelse ($this->itemGroups as $group)
                        <div
                            class="bg-white p-5 rounded-2xl border-2 border-white shadow-sm hover:border-blue-200 transition-all group">
                            <div class="text-xs font-black text-blue-600 mb-1 tracking-tighter">{{ $group->code }}
                            </div>
                            <div class="text-sm font-bold text-gray-800">{{ $group->description }}</div>
                        </div>
                    @empty
                        <div class="col-span-full py-20 text-center text-gray-400 italic">Belum ada Item Group
                            terdaftar.</div>
                    @endforelse
                </div>
            @endif

        </div>
    </div>

    <div x-show="modalTerbuka" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="modalTerbuka = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity">
            </div>

            <div class="relative bg-[#f0f4f8] rounded-3xl shadow-2xl w-full max-w-2xl p-8 transform transition-all"
                style="box-shadow: 20px 20px 60px #bebebe, -20px -20px 60px #ffffff;">
                <div class="flex justify-between items-center mb-8 border-b pb-4">
                    <h3 class="text-lg font-black text-gray-800 uppercase tracking-tighter">📝 Form Pengajuan Item Baru
                    </h3>
                    <button @click="modalTerbuka = false"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-5 rounded-2xl border-2 border-gray-100 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <label class="text-[11px] font-black text-gray-500 uppercase tracking-widest ml-1">
                                Item Group (ERP) <span class="text-red-500">*</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" wire:model.live="isNewGroup"
                                    class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                                <span
                                    class="text-xs font-bold text-gray-500 group-hover:text-blue-600 transition-colors">Group
                                    belum terdaftar? (Buat Baru)</span>
                            </label>
                        </div>

                        @if (!$isNewGroup)
                            <div>
                                <input type="text" wire:model.live="groupSearch"
                                    placeholder="🔍 Ketik kode atau deskripsi untuk mencari grup..."
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3 transition-all">

                                <div class="max-h-48 overflow-y-auto pr-2 space-y-2">
                                    @forelse($this->filteredGroups as $group)
                                        <label
                                            class="flex items-center gap-4 p-3 bg-white rounded-xl border-2 cursor-pointer transition-all {{ $selectedGroupId == $group->id ? 'border-blue-500 bg-blue-50/50' : 'border-gray-100 hover:border-blue-200' }}">
                                            <input type="radio" wire:model="selectedGroupId"
                                                value="{{ $group->id }}"
                                                class="w-4 h-4 text-blue-600 border-gray-300 ml-1">
                                            <div>
                                                <div class="text-xs font-black text-blue-600 tracking-tighter">
                                                    {{ $group->code }}</div>
                                                <div
                                                    class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                                                    {{ $group->description }}</div>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="py-6 text-center">
                                            <span class="text-xs font-bold text-gray-400">Pencarian tidak ditemukan.
                                                Silakan centang "Buat Baru" di atas.</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        @if ($isNewGroup)
                            <div class="grid grid-cols-2 gap-4 animate-fade-in">
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1 ml-1">Group
                                        Code Baru</label>
                                    <input type="text" wire:model="newGroupCode" placeholder="Contoh: RM-001"
                                        class="w-full px-4 py-3 bg-blue-50/30 border-2 border-blue-100 rounded-xl text-sm font-black text-gray-800 uppercase focus:outline-none focus:border-blue-500 transition-all">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1 ml-1">Deskripsi
                                        Group Baru</label>
                                    <input type="text" wire:model="newGroupDesc"
                                        placeholder="Contoh: Raw Material"
                                        class="w-full px-4 py-3 bg-blue-50/30 border-2 border-blue-100 rounded-xl text-sm font-bold text-gray-800 focus:outline-none focus:border-blue-500 transition-all">
                                </div>
                            </div>
                        @endif
                    </div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">Item
                        Code</label>
                    <div
                        class="p-6 rounded-2xl bg-[#e0e5ec] shadow-inner border border-gray-300/50 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                        <div>
                            <label
                                class="text-[10px] font-black tracking-widest uppercase ml-2 mb-1 block text-gray-400">Dept
                                (DD)</label>
                            <input type="text" wire:model="departmentCode" readonly
                                class="w-full p-3 rounded-xl bg-[#d1d9e6] border-none outline-none text-gray-600 font-black text-center uppercase cursor-not-allowed shadow-sm">
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black tracking-widest uppercase ml-2 mb-1 block text-blue-600">Category
                                (CC)</label>
                            <select wire:model.live="selectedCategory"
                                class="w-full p-3 rounded-xl bg-[#e0e5ec] shadow-[5px_5px_10px_#b8b9be,-5px_-5px_10px_#ffffff] border-none outline-none text-gray-700 font-bold text-xs">
                                <option value="">-- PILIH CATEGORY --</option>
                                @foreach ($categories as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black tracking-widest uppercase ml-2 mb-1 block text-blue-600">Sub-Category
                                (SSSS)</label>
                            <select wire:model.live="selectedSubcategory"
                                {{ empty($selectedCategory) ? 'disabled' : '' }}
                                class="w-full p-3 rounded-xl bg-[#e0e5ec] shadow-[5px_5px_10px_#b8b9be,-5px_-5px_10px_#ffffff] border-none outline-none text-gray-700 font-bold text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="">-- PILIH SUB-CATEGORY --</option>
                                @if (!empty($selectedCategory) && isset($subcategories[$selectedCategory]))
                                    @foreach ($subcategories[$selectedCategory] as $code => $label)
                                        <option value="{{ $code }}">{{ $code }} -
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black tracking-widest uppercase ml-2 mb-1 block text-blue-600">Type
                                (TTT)</label>
                            <select wire:model.live="selectedType"
                                class="w-full p-3 rounded-xl bg-[#e0e5ec] shadow-[5px_5px_10px_#b8b9be,-5px_-5px_10px_#ffffff] border-none outline-none text-gray-700 font-bold text-xs">
                                <option value="">-- PILIH TYPE --</option>
                                @foreach ($types as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-6 bg-white/40 p-4 rounded-2xl border border-white flex gap-4 items-center">
                        <div class="flex-1">
                            <label
                                class="text-[11px] font-black tracking-widest uppercase ml-2 mb-1 block text-gray-500">Generated
                                Item Code (ERP ID)</label>
                            <input type="text" wire:model="item_code"
                                placeholder="Kombinasikan opsi di atas lalu klik Generate..." readonly
                                class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner border-none outline-none text-lg font-black text-blue-700 uppercase tracking-widest text-center cursor-not-allowed">
                        </div>
                        <button type="button" wire:click="generateItemCode"
                            class="px-8 py-4 mt-5 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-black uppercase tracking-widest text-xs shadow-[0_10px_20px_rgba(59,130,246,0.3)] hover:scale-105 transition-transform whitespace-nowrap">
                            ✨ Generate Code
                        </button>
                    </div>
                    <div>
                        <label
                            class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">Nama
                            Item *</label>
                        <input type="text" wire:model="newName"
                            class="neo-inset w-full px-5 py-3 rounded-xl text-sm font-bold text-gray-800 focus:outline-none uppercase"
                            style="border:none;">
                    </div>

                    <div>
                        <label
                            class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">Deskripsi
                            / Spesifikasi</label>
                        <textarea wire:model="newDescription" rows="3"
                            class="neo-inset w-full px-5 py-3 rounded-xl text-sm font-bold text-gray-800 focus:outline-none"
                            style="border:none;"></textarea>
                    </div>
                    <div class="mt-6">
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">
                            Tujuan Pengajuan <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="newPurpose" rows="2"
                            class="neo-inset w-full p-5 rounded-2xl text-sm font-bold text-gray-800 focus:outline-none placeholder-gray-400"
                            style="border:none;" placeholder="Misal: Untuk kebutuhan project instalasi gedung X..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">Satuan
                                *</label>
                            <input type="text" wire:model="newUnit" placeholder="pcs / kg / m"
                                class="neo-inset w-full px-5 py-3 rounded-xl text-sm font-bold text-gray-800 focus:outline-none"
                                style="border:none;">
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">Estimasi
                                Harga (Rp)</label>
                            <input type="number" wire:model="newPrice"
                                class="neo-inset w-full px-5 py-3 rounded-xl text-sm font-bold text-gray-800 focus:outline-none"
                                style="border:none;">
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 mt-10">
                    <button @click="modalTerbuka = false"
                        class="flex-1 py-4 text-sm font-black text-gray-500 uppercase tracking-widest hover:text-gray-700 transition-all">
                        Batal
                    </button>
                    <button wire:click="submitRequest"
                        class="flex-1 py-4 bg-[#2563eb] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg hover:bg-blue-700 transition-all">
                        Kirim Pengajuan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
