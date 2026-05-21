<?php

use function Livewire\Volt\{state, computed};
use App\Models\MasterItem;
use App\Models\ItemRequest;
use App\Models\ItemGroup;

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

    // Variabel Khusus Item Group
    'isNewGroup' => false,
    'selectedGroupId' => '',
    'groupSearch' => '', // Untuk kotak pencarian group
    'newGroupCode' => '',
    'newGroupDesc' => '',
]);

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
        'estimated_price' => $this->newPrice,
        'status' => 'pending',
    ]);

    // 4. Reset Form & Tutup Modal
    $this->showModal = false;
    $this->reset(['newName', 'newDescription', 'newPurpose', 'newUnit', 'newPrice', 'isNewGroup', 'selectedGroupId', 'groupSearch', 'newGroupCode', 'newGroupDesc']);
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
