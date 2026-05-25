<?php

use function Livewire\Volt\{state, computed};
use App\Models\ItemRequest;
use App\Models\ItemGroup;

state([
    'showDetailModal' => false,
    'selectedRequest' => null,
]);

$showDetail = function ($id) {
    $this->selectedRequest = \App\Models\ItemRequest::with(['requester.dept', 'itemGroup'])->find($id);
    if ($this->selectedRequest) {
        $this->showDetailModal = true;
    }
};

$closeDetail = function () {
    $this->showDetailModal = false;
    $this->selectedRequest = null;
};

$declineRequest = function ($id) {
    $req = \App\Models\ItemRequest::find($id);
    if ($req) {
        $req->update([
            'status' => 'rejected',
        ]);
    }
};

// Mengecek apakah user saat ini adalah tim SC/Procurement
$isScTeam = computed(function () {
    $deptCode = strtoupper(auth()->user()->dept->code ?? '');
    return in_array($deptCode, ['SC', 'PROCUREMENT']);
});

// Menghitung jumlah pengajuan yang memiliki Grup Baru dan sudah di-Approve tapi belum dikonfirmasi upload
$pendingGroupExports = computed(function () {
    if (!$this->isScTeam) {
        return 0;
    }
    return ItemRequest::where('status', 'approved')->whereNotNull('new_group_code')->where('is_group_synced', false)->count();
});

// Ambil data pengajuan
$requests = computed(function () {
    $query = ItemRequest::with(['requester.dept', 'itemGroup']);

    if ($this->isScTeam) {
        return $query->orderByRaw("FIELD(status, 'pending', 'approved', 'processed', 'rejected')")->orderBy('created_at', 'desc')->get();
    }

    return $query
        ->where('requester_id', auth()->user()->id)
        ->orderBy('created_at', 'desc')
        ->get();
});

// Fungsi Approve
$approveRequest = function ($id) {
    $req = ItemRequest::find($id);

    if ($req->new_group_code) {
        $exists = ItemGroup::where('code', $req->new_group_code)->exists();
        if ($exists) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Grup Sudah Ada',
                'text' => "Item Group {$req->new_group_code} sudah terdaftar. Silakan gunakan grup yang ada.",
            ]);
            return;
        }
    }

    $req->update(['status' => 'approved']);
    $this->dispatch('swal', ['icon' => 'success', 'title' => 'Disetujui', 'text' => 'Silakan lakukan Export dan Konfirmasi Upload ERP.']);
};

// Fungsi Konfirmasi Upload (Proses pemindahan ke database ItemGroup)
$confirmGroupSync = function ($id) {
    $req = ItemRequest::findOrFail($id);

    if ($req->new_group_code) {
        // PROSES SIMPAN KE DATABASE MASTER DATA (item_groups)
        $group = ItemGroup::firstOrCreate(['code' => strtoupper($req->new_group_code)], ['description' => $req->new_group_desc]);

        // Update Request: Hubungkan ke ID Group yang baru dan tandai Synced
        $req->update([
            'item_group_id' => $group->id,
            'is_group_synced' => true,
        ]);

        $msg = "Item Group {$group->code} resmi masuk ke Master Data.";
    } else {
        $req->update(['is_group_synced' => true]);
        $msg = 'Status sinkronisasi berhasil dicatat.';
    }

    $this->dispatch('swal', ['icon' => 'success', 'title' => 'Berhasil!', 'text' => $msg]);
};

$declineRequest = function ($id) {
    ItemRequest::find($id)->update(['status' => 'rejected']);
};

$processItem = function ($id) {
    return redirect()->route('master-item.create', ['itemRequestId' => $id]);
};
?>

<div class="p-6">
    <div class="neo-card bg-[#f0f4f8] rounded-3xl p-8"
        style="box-shadow: 12px 12px 24px #d1d9e6, -12px -12px 24px #ffffff;">
        <h2 class="text-2xl font-black text-gray-800 mb-6 uppercase tracking-tighter">📋 Dashboard Status Pengajuan</h2>

        @if ($this->pendingGroupExports > 0)
            <div
                class="mb-6 p-5 bg-orange-50 border-l-4 border-orange-500 rounded-r-2xl flex items-start gap-4 shadow-sm animate-pulse">
                <div class="text-orange-500 text-2xl mt-1">⚠️</div>
                <div>
                    <h3 class="text-sm font-black text-orange-800 uppercase tracking-widest">Grup Baru Terdeteksi</h3>
                    <p class="text-xs font-bold text-orange-600 mt-1">Ada {{ $this->pendingGroupExports }} grup baru yang
                        perlu di-upload ke ERP. Data tidak akan masuk ke Master Data sampai tombol <b>Konfirmasi
                            Upload</b> ditekan.</p>
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border-2 border-white shadow-sm bg-white/50">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="py-4 px-6">Requester</th>
                        <th class="py-4 px-6">Item Name</th>
                        <th class="py-4 px-6 text-center">Status</th>
                        <th class="py-4 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->requests as $req)
                        <tr class="hover:bg-white transition-all">
                            <td class="py-4 px-6 text-sm font-bold text-gray-800">
                                {{ $req->requester->name }} <br>
                                <span
                                    class="text-[10px] text-blue-500 uppercase">{{ $req->requester->dept->name ?? '-' }}</span>
                            </td>
                            <td class="py-4 px-6 text-sm font-bold text-gray-700">{{ $req->name }}</td>
                            <td class="py-4 px-6 text-center">
                                <span
                                    class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex gap-2 justify-end items-center">
                                    {{-- Tombol Detail (Dapat diakses di semua status) --}}
                                    <button wire:click="showDetail({{ $req->id }})"
                                        class="px-3 py-1.5 rounded-lg bg-[#e0e5ec] text-blue-600 font-black text-xs uppercase tracking-widest shadow-[3px_3px_6px_#b8b9be,-3px_-3px_6px_#ffffff] hover:shadow-inner transition-all">
                                        Detail
                                    </button>

                                    @if ($this->isScTeam && $req->status === 'pending')
                                        {{-- Tombol Approve --}}
                                        <button wire:click="approveRequest({{ $req->id }})"
                                            class="bg-green-500 text-white px-3 py-1.5 rounded-lg text-xs font-black uppercase shadow-md hover:scale-105 transition-transform">
                                            Approve
                                        </button>

                                        {{-- Tombol Tolak (Decline) --}}
                                        <button wire:click="declineRequest({{ $req->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menolak pengajuan item ini?"
                                            class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-xs font-black uppercase shadow-md hover:scale-105 transition-transform">
                                            Tolak
                                        </button>
                                    @endif

                                    @if ($this->isScTeam && $req->status === 'approved')
                                        @if ($req->new_group_code && !$req->is_group_synced)
                                            <div class="flex gap-2 justify-end border-l-2 border-gray-200 pl-2 ml-1">
                                                <a href="{{ route('export.group', ['id' => $req->id]) }}"
                                                    class="bg-orange-500 text-white px-3 py-1.5 rounded-lg text-xs font-black uppercase shadow-md">1.
                                                    Export</a>
                                                <button wire:click="confirmGroupSync({{ $req->id }})"
                                                    class="bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-black uppercase shadow-md">2.
                                                    Konfirmasi Upload</button>
                                            </div>
                                        @else
                                            <button wire:click="processItem({{ $req->id }})"
                                                class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-black uppercase shadow-md hover:scale-105 transition-transform border-l-2 border-gray-200 pl-3 ml-1">
                                                Proses Pembuatan Excel ➡️
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                class="py-10 text-center text-gray-400 font-bold italic uppercase tracking-widest text-[10px]">
                                Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- MODAL DETAIL OVERLAY --}}
    @if ($showDetailModal && $selectedRequest)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fadeIn">
            <div
                class="bg-[#e0e5ec] w-full max-w-2xl p-8 rounded-3xl shadow-[20px_20px_60px_#bebebe,-20px_-20px_60px_#ffffff] relative max-h-[90vh] overflow-y-auto">

                {{-- Header Modal --}}
                <div class="flex justify-between items-center mb-6 border-b-2 border-gray-300 pb-4">
                    <h3 class="text-lg font-black text-gray-700 uppercase tracking-wider">
                        Detail Pengajuan Item Baru
                    </h3>
                    <span
                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider 
                        {{ $selectedRequest->status === 'pending' ? 'bg-amber-100 text-amber-700 border border-amber-300' : '' }}
                        {{ $selectedRequest->status === 'approved' ? 'bg-emerald-100 text-emerald-700 border border-emerald-300' : '' }}
                        {{ $selectedRequest->status === 'rejected' ? 'bg-red-100 text-red-700 border border-red-300' : '' }}
                        {{ $selectedRequest->status === 'processed' ? 'bg-blue-100 text-blue-700 border border-blue-300' : '' }}">
                        {{ $selectedRequest->status }}
                    </span>
                </div>

                {{-- Konten Detail Data --}}
                <div class="space-y-5">
                    <div class="md:col-span-2 mb-4">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">
                            Item Code
                        </label>
                        <div class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner font-bold text-gray-800 text-sm">
                            {{ $selectedRequest->item_code ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Nama
                            Item</label>
                        <div class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner font-bold text-gray-800 text-sm">
                            {{ $selectedRequest->name }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Unit
                                / Satuan</label>
                            <div
                                class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner font-bold text-gray-800 text-sm uppercase">
                                {{ $selectedRequest->unit }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Estimasi
                                Harga</label>
                            <div
                                class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner font-bold text-gray-800 text-sm">
                                Rp {{ number_format($selectedRequest->estimated_price, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Deskripsi
                            / Spesifikasi</label>
                        <div
                            class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner font-bold text-gray-700 text-sm whitespace-pre-line">
                            {{ $selectedRequest->description ?: 'Tidak ada deskripsi spesifikasi.' }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Tujuan
                            Pengajuan</label>
                        <div
                            class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner font-bold text-gray-700 text-sm whitespace-pre-line">
                            {{ $selectedRequest->purpose ?: 'Tidak ada data tujuan pengajuan.' }}
                        </div>
                    </div>

                    {{-- Metadata Pengirim --}}
                    <div class="grid grid-cols-2 gap-4 border-t border-gray-300 pt-4 mt-2">
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Diajukan
                                Oleh</label>
                            <div class="text-sm font-black text-gray-700">
                                {{ $selectedRequest->requester->name ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500 font-bold uppercase tracking-wider">
                                Dept: {{ $selectedRequest->requester->dept->name ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Waktu
                                Masuk</label>
                            <div class="text-sm font-bold text-gray-700">
                                {{ $selectedRequest->created_at ? $selectedRequest->created_at->format('d M Y, H:i') : '-' }}
                                WIB
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer Modal Button --}}
                <div class="mt-8 flex justify-end border-t border-gray-300 pt-4">
                    <button wire:click="closeDetail"
                        class="px-6 py-2.5 rounded-xl bg-gray-600 text-white font-black text-xs uppercase tracking-widest shadow-md hover:bg-gray-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
