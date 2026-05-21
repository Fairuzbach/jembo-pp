<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $pendingRequests = [];
    public $selectedRequest = null;
    public $approvalData = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = auth()->user();
        $isManager = $user->job_level === 'MANAGER';
        $userDeptCode = $user->dept->code ?? '';

        $query = PurchaseRequest::with(['requester.dept', 'items.masterItem.department']);

        /**
         * LOGIKA FILTERING DASHBOARD (SMART QUEUE & MY REQUESTS)
         * Tujuan: Menampilkan dokumen milik sendiri (Tracking) DAN dokumen yang butuh tindakan (Inbox).
         */
        $query->where(function ($q) use ($user, $isManager, $userDeptCode) {
            //Selalu tampilkan dokumen yang dibuat oleh user itu sendiri (Tracking/History)
            $q->where('requester_id', $user->id);

            // ----------------------------------------------------------------------
            // LOGIKA DI BAWAH INI ADALAH TUGAS APPROVAL
            // ----------------------------------------------------------------------
            if ($isManager) {
                // TUGAS 1: Sebagai Manager dari Requester (Step 1)
                $q->orWhere(function ($subQ) use ($userDeptCode) {
                    $subQ->where('status', 'pending_manager_dept_requester')->where('requester_dept_code', $userDeptCode);
                });

                // TUGAS 2: Sebagai Manager Dept Pooling (Step 3 / Final)
                $q->orWhere(function ($subQ) use ($userDeptCode) {
                    $subQ->where('status', 'pending_dept_pooling')->whereHas('items', function ($itemQ) use ($userDeptCode) {
                        $itemQ->where('internal_status', 'pending_manager')->whereHas('masterItem.department', function ($deptQ) use ($userDeptCode) {
                            $deptQ->where('code', $userDeptCode);
                        });
                    });
                });
            } else {
                // TUGAS 3: Sebagai Admin Dept Pooling (Step 2 / Verifikator)
                $q->orWhere(function ($subQ) use ($userDeptCode) {
                    $subQ->where('status', 'menunggu_verifikasi_admin_dept_pooling')->whereHas('items', function ($itemQ) use ($userDeptCode) {
                        $itemQ->where('internal_status', 'pending')->whereHas('masterItem.department', function ($deptQ) use ($userDeptCode) {
                            $deptQ->where('code', $userDeptCode);
                        });
                    });
                });
            }
        });

        $this->pendingRequests = $query->orderBy('created_at', 'desc')->get();
    }

    private function getActivePoolingDepts($pr)
    {
        $deps = [];
        if ($pr->related_it) {
            $deps[] = 'IT';
        }
        if ($pr->related_ga) {
            $deps[] = 'GA';
        }
        if ($pr->related_maintenance) {
            $deps[] = 'MTC';
        }
        if ($pr->related_hse) {
            $deps[] = 'HSE';
        }
        if ($pr->related_pe) {
            $deps[] = 'PE';
        }
        if ($pr->related_qc) {
            $deps[] = 'QC';
        }
        if ($pr->related_sales_support) {
            $deps[] = 'SLS';
        }
        if ($pr->related_energy) {
            $deps[] = 'Energy';
        }
        return $deps;
    }

    private function joinLabels($array)
    {
        if (empty($array)) {
            return '...';
        }
        if (count($array) === 1) {
            return $array[0];
        }
        $last = array_pop($array);
        return implode(', ', $array) . ' dan ' . $last;
    }

    public function getStatusLabel($pr)
    {
        if ($pr->status === 'pending_manager_dept_requester') {
            $deptCode = $pr->requester_dept_code ?? 'User';
            return "Menunggu Mgr. Dept. {$deptCode}";
        }
        if ($pr->status === 'menunggu_verifikasi_admin_dept_pooling') {
            return 'Menunggu Verifikasi Admin Dept. ' . $this->joinLabels($this->getActivePoolingDepts($pr));
        }
        if ($pr->status === 'pending_dept_pooling') {
            return 'Menunggu Manager Dept. ' . $this->joinLabels($this->getActivePoolingDepts($pr));
        }
        if ($pr->status === 'approved_final_system') {
            return '✅ SELESAI (Final Approved)';
        }
        if ($pr->status === 'rejected') {
            return '❌ DITOLAK';
        }

        return strtoupper(str_replace('_', ' ', $pr->status));
    }

    public function openDetail($id)
    {
        $this->selectedRequest = PurchaseRequest::with(['requester', 'items.masterItem.department'])->find($id);
        $this->approvalData = [];

        $userDeptCode = auth()->user()->dept->code ?? '';
        $isManager = auth()->user()->job_level === 'MANAGER';

        foreach ($this->selectedRequest->items as $item) {
            $itemDeptCode = $item->masterItem->department->code ?? '';

            /**
             * LOGIKA KEAMANAN INTERAKSI (RBAC per Item)
             * Tujuan: Memastikan tombol radio Setuju/Tolak hanya muncul pada item yang sesuai
             * dengan wewenang Departemen dan Job Level user.
             */
            $canInteract = false;
            // Kondisi A: User adalah Manager Requester dan dokumen di tahap awal
            if ($this->selectedRequest->status === 'pending_manager_dept_requester' && $isManager && $this->selectedRequest->requester_dept_code === $userDeptCode) {
                $canInteract = true;
            }
            // Kondisi B: User adalah Admin dan sedang tahap verifikasi pooling
            elseif ($this->selectedRequest->status === 'menunggu_verifikasi_admin_dept_pooling' && !$isManager && $itemDeptCode === $userDeptCode && $item->internal_status === 'pending') {
                $canInteract = true;
            }
            // Kondisi C: User adalah Manager Pooling dan sedang tahap approval akhir
            elseif ($this->selectedRequest->status === 'pending_dept_pooling' && $isManager && $itemDeptCode === $userDeptCode && $item->internal_status === 'pending_manager') {
                $canInteract = true;
            }
            // Jika boleh berinteraksi, siapkan slot data di array approvalData
            if ($canInteract) {
                $this->approvalData[$item->id] = [
                    'status' => 'approved', // Default state saat dibuka
                    'reason' => '',
                ];
            }
        }
    }

    public function closeDetail()
    {
        $this->selectedRequest = null;
        $this->approvalData = [];
    }

    public function submitApproval()
    {
        // 1. Validasi Alasan Penolakan
        foreach ($this->approvalData as $itemId => $data) {
            if ($data['status'] === 'rejected' && trim($data['reason']) === '') {
                $this->dispatch('swal', [
                    'title' => 'Tunggu Sebentar!',
                    'text' => 'Mohon isi Alasan Penolakan pada baris yang Anda tolak.',
                    'icon' => 'warning',
                    'confirmButtonColor' => '#e30613',
                ]);
                return;
            }
        }

        DB::transaction(function () {
            $docStatus = $this->selectedRequest->status;

            /**
             * UPDATE STATUS PER-ITEM (Micro Status)
             * Tujuan: Menggerakkan setiap barang di jalur masing-masing.
             */
            foreach ($this->approvalData as $itemId => $data) {
                $item = PurchaseRequestItem::find($itemId);
                if ($item) {
                    $newInternalStatus = $data['status']; // 'approved' atau 'rejected'

                    // Alur khusus: Jika Admin setuju, status internal barang naik ke 'pending_manager' (menunggu bosnya)
                    if ($docStatus === 'menunggu_verifikasi_admin_dept_pooling' && $data['status'] === 'approved') {
                        $newInternalStatus = 'pending_manager';
                    }
                    // Alur khusus: Jika Manager Requester setuju, status barang siap diverifikasi Admin Pooling
                    elseif ($docStatus === 'pending_manager_dept_requester' && $data['status'] === 'approved') {
                        $newInternalStatus = 'pending';
                    }

                    $item->update([
                        'internal_status' => $newInternalStatus,
                        'rejection_reason' => $data['status'] === 'rejected' ? $data['reason'] : null,
                    ]);
                }
            }

            /**
             * LOGIKA TRANSISI DOKUMEN INDUK (Macro Status)
             * Tujuan: Menentukan apakah dokumen induk boleh lanjut ke tahap berikutnya
             * berdasarkan status terkecil dari item-item di dalamnya.
             */
            $allItems = $this->selectedRequest->items()->get();

            if ($docStatus === 'pending_manager_dept_requester') {
                // Maju ke pooling setelah manager pembuat setuju
                $this->selectedRequest->update(['status' => 'menunggu_verifikasi_admin_dept_pooling']);
            } elseif ($docStatus === 'menunggu_verifikasi_admin_dept_pooling') {
                // Cek apakah semua item sudah diproses Admin (tidak ada lagi yang 'pending')
                $masihAdaTugasAdmin = $allItems->contains('internal_status', 'pending');
                if (!$masihAdaTugasAdmin) {
                    $this->selectedRequest->update(['status' => 'pending_dept_pooling']);
                }
            } elseif ($docStatus === 'pending_dept_pooling') {
                // Cek apakah semua item sudah diproses Manager Pooling (tidak ada lagi yang 'pending_manager')
                $masihAdaTugasManager = $allItems->contains('internal_status', 'pending_manager');
                if (!$masihAdaTugasManager) {
                    $this->selectedRequest->update(['status' => 'approved_final_system']);
                }
            }
        });

        $this->closeDetail();
        $this->loadData();

        $this->dispatch('swal', [
            'title' => 'Berhasil!',
            'text' => 'Keputusan Anda telah direkam sistem.',
            'icon' => 'success',
            'confirmButtonColor' => '#003882',
        ]);
    }
};
?>

<div class="min-h-screen bg-[#f0f3f6] p-4 md:p-8 font-sans text-gray-700">
    <div class="max-w-7xl mx-auto">

        <div
            class="mb-6 p-6 rounded-[30px] bg-[#f0f3f6] shadow-[10px_10px_20px_#d1d9e6,-10px_-10px_20px_#ffffff] border border-white/50 flex flex-wrap gap-4 justify-between items-center">
            <div class="flex items-center gap-5">
                <div
                    class="w-14 h-14 rounded-full bg-[#f0f3f6] shadow-[inset_6px_6px_12px_#d1d9e6,inset_-6px_-6px_12px_#ffffff] flex items-center justify-center border border-white/40">
                    <span class="text-xl font-black text-[#003882]">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </span>
                </div>
                <div>
                    <h2 class="text-[#003882] font-black text-sm tracking-wide uppercase">
                        {{ auth()->user()->name }}
                    </h2>
                    <div class="flex items-center mt-1 gap-2">
                        <span
                            class="bg-blue-100/50 text-[#003882] px-2 py-0.5 rounded-md text-[9px] font-black tracking-widest border border-blue-200/50">
                            {{ auth()->user()->dept->name }}
                        </span>
                        <span
                            class="bg-blue-100/50 text-[#003882] px-2 py-0.5 rounded-md text-[9px] font-black tracking-widest border border-blue-200/50">
                            {{ auth()->user()->job_level }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('purchase-request.create') }}" wire:navigate
                    class="px-6 py-3.5 rounded-full bg-[#003882] text-white font-black text-[10px] tracking-[0.2em] shadow-[6px_6px_12px_#d1d9e6] hover:bg-[#002b63] transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                    </svg>
                    BUAT REQUEST BARU
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit"
                        class="px-6 py-3.5 rounded-full bg-[#f0f3f6] text-[#e30613] font-black text-[10px] tracking-[0.2em] shadow-[6px_6px_12px_#d1d9e6,-6px_-6px_12px_#ffffff] hover:shadow-inner border border-white/50 transition-all flex items-center gap-2">
                        LOGOUT
                    </button>
                </form>
            </div>
        </div>

        @if (!$selectedRequest)
            <div class="flex justify-between items-center mb-10 px-2 mt-4">
                <div>
                    <h1 class="text-4xl font-extrabold text-[#003882] tracking-tight drop-shadow-sm">Dashboard Approval
                        Permintaan Pembelian
                    </h1>
                    <p class="text-sm text-gray-500 mt-1 font-medium italic">Sistem Monitoring Alur Persetujuan
                        Terintegrasi</p>
                </div>
                <div
                    class="bg-[#f0f3f6] px-5 py-3 rounded-2xl shadow-[6px_6px_12px_#d1d9e6,-6px_-6px_12px_#ffffff] flex items-center border border-white/40">
                    <span class="w-3 h-3 bg-[#fde026] rounded-full animate-pulse mr-3 shadow-inner"></span>
                    <span class="text-sm font-extrabold text-[#003882] uppercase tracking-tighter">
                        Antrean: {{ count($pendingRequests) }} Dokumen
                    </span>
                </div>
            </div>

            <div
                class="bg-[#f0f3f6] rounded-[2.5rem] shadow-[15px_15px_30px_#d1d9e6,-15px_-15px_30px_#ffffff] overflow-hidden border border-white/60 p-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-[10px] uppercase tracking-widest border-b border-gray-200/60">
                            <th class="p-5 font-black">No. PP</th>
                            <th class="p-5 font-black">Requester</th>
                            <th class="p-5 font-black">Status Saat Ini</th>
                            <th class="p-5 font-black text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/50">
                        @forelse ($pendingRequests as $pr)
                            <tr class="hover:bg-white/30 transition-all group">
                                <td class="p-5 font-black text-[#e30613] text-sm tracking-tight">{{ $pr->pp_number }}
                                </td>
                                <td class="p-5">
                                    <div class="text-sm font-extrabold text-[#003882]">{{ $pr->requester->name }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">
                                        {{ $pr->requester_dept_code }} • {{ $pr->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td class="p-5">
                                    <div
                                        class="inline-flex items-center px-4 py-2 bg-[#f0f3f6] shadow-[inset_3px_3px_6px_#d1d9e6,inset_-3px_-3px_6px_#ffffff] rounded-2xl border border-white/50">
                                        <div
                                            class="w-2 h-2 rounded-full mr-3 {{ $pr->status == 'approved_final_system' ? 'bg-blue-600' : 'bg-amber-400 animate-pulse' }}">
                                        </div>
                                        <span
                                            class="text-[10px] font-black uppercase text-gray-600 tracking-tight">{{ $this->getStatusLabel($pr) }}</span>
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <button wire:click="openDetail({{ $pr->id }})"
                                        class="bg-[#f0f3f6] text-[#003882] text-[10px] font-black py-2.5 px-6 rounded-xl shadow-[5px_5px_10px_#d1d9e6,-5px_-5px_10px_#ffffff] hover:shadow-inner border border-white/50 uppercase transition-all active:scale-95">Detail</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"
                                    class="p-16 text-center text-gray-400 font-bold italic tracking-widest">Tidak ada
                                    antrean dokumen untuk Anda saat ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="mb-10 flex justify-between items-center px-2">
                <button wire:click="closeDetail"
                    class="bg-[#f0f3f6] text-gray-600 font-black py-2.5 px-6 rounded-2xl shadow-[6px_6px_12px_#d1d9e6,-6px_-6px_12px_#ffffff] hover:shadow-inner transition-all flex items-center border border-white/40 uppercase text-xs">
                    &larr; Kembali
                </button>
                <div
                    class="text-sm font-black text-[#003882] bg-white shadow-inner px-6 py-2 rounded-2xl border border-gray-100 uppercase tracking-tight">
                    {{ $this->getStatusLabel($selectedRequest) }}
                </div>
            </div>

            <div
                class="bg-white p-2 shadow-2xl mx-auto border-[3px] border-black max-w-[1100px] mb-12 transform hover:scale-[1.01] transition-transform">
                <div class="border border-black">
                    <div class="flex border-b border-black">
                        <div class="w-1/4 p-4 border-r border-black flex justify-center items-center bg-white">
                            <img src="{{ asset('images/logo jembo.webp') }}" class="max-h-16">
                        </div>
                        <div class="w-2/4 p-2 border-r border-black text-center flex flex-col justify-center">
                            <div class="font-bold uppercase text-xs italic tracking-widest">Sistem Manajemen
                                Terintegrasi</div>
                            <div
                                class="font-black uppercase text-lg border-t-2 border-black mt-2 pt-2 tracking-tighter">
                                Form Permintaan Pembelian Barang Dan Jasa</div>
                        </div>
                        <div class="w-1/4 text-[9px] font-black leading-tight">
                            <div class="p-1.5 border-b border-black">NO. DOC: JCC-SC-PS-002-F001</div>
                            <div class="p-1.5 border-b border-black">EFFECTIVE: 20-MAY-24</div>
                            <div class="p-1.5 border-b border-black">REVISION: 06</div>
                            <div class="p-1.5">PAGE: 01</div>
                        </div>
                    </div>

                    <div class="flex border-b border-black bg-gray-50 text-[11px] font-bold">
                        <div class="w-1/3 p-3 border-r border-black">
                            <div class="mb-1 text-gray-500">KEPADA: <span
                                    class="text-[#003882] uppercase underline decoration-2">{{ $this->getStatusLabel($selectedRequest) }}</span>
                            </div>
                            <div>DARI: <span
                                    class="uppercase underline decoration-2 ml-4">{{ $selectedRequest->requester_dept_code }}</span>
                            </div>
                        </div>
                        <div class="w-1/3 p-3 border-r border-black text-center flex flex-col justify-center bg-white">
                            <div class="text-base font-black">NO. PP: <span
                                    class="text-[#e30613]">{{ $selectedRequest->pp_number }}</span></div>
                            <div class="uppercase text-[9px] font-bold tracking-[0.3em] mt-1 text-gray-400">
                                {{ $selectedRequest->expense_type }}</div>
                        </div>
                        <div class="w-1/3 p-3 flex flex-col justify-center items-end">
                            <div class="text-gray-400 uppercase text-[10px]">Tanggal Cetak Sistem:</div>
                            <div class="font-black text-xs uppercase">
                                {{ $selectedRequest->created_at->format('d-M-Y') }}</div>
                        </div>
                    </div>

                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-100 border-b border-black font-black uppercase text-center tracking-tighter">
                                <th class="border-r border-black p-3 w-10">NO</th>
                                <th class="border-r border-black p-3 text-left">DESKRIPSI BARANG & TUJUAN</th>
                                <th class="border-r border-black p-3 w-24">QUANTITY</th>
                                <th class="p-3 bg-blue-50/50 border-l-2 border-black w-1/3">PANEL KEPUTUSAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedRequest->items as $index => $item)
                                @php
                                    $itemDeptCode = $item->masterItem->department->code ?? 'N/A';
                                @endphp
                                <tr class="border-b border-black last:border-b-0 hover:bg-gray-50 transition-colors">
                                    <td class="border-r border-black p-4 text-center font-black text-gray-400">
                                        {{ $index + 1 }}</td>
                                    <td class="border-r border-black p-4">
                                        <div class="font-black text-sm uppercase text-gray-800 tracking-tight">
                                            {{ $item->masterItem->name }}</div>
                                        <div
                                            class="text-[10px] italic text-blue-800 bg-blue-50 inline-block px-2 py-0.5 rounded-md mt-2 border border-blue-100">
                                            TUJUAN: {{ $item->usage_purpose }}
                                        </div>
                                    </td>
                                    <td class="border-r border-black p-4 text-center font-black">
                                        <div class="text-base">{{ $item->quantity }}</div>
                                        <div class="text-[9px] uppercase text-gray-400 tracking-widest">
                                            {{ $item->masterItem->unit }}</div>
                                    </td>

                                    <td class="p-4 bg-blue-50/30 border-l-2 border-black">
                                        @if (isset($approvalData[$item->id]))
                                            <div class="flex space-x-6 mb-3">
                                                <label
                                                    class="flex items-center text-[11px] font-black cursor-pointer group">
                                                    <input type="radio"
                                                        wire:model.live="approvalData.{{ $item->id }}.status"
                                                        value="approved" class="w-4 h-4 mr-2 accent-green-600">
                                                    <span class="group-hover:text-green-700">SETUJU</span>
                                                </label>
                                                <label
                                                    class="flex items-center text-[11px] font-black cursor-pointer group">
                                                    <input type="radio"
                                                        wire:model.live="approvalData.{{ $item->id }}.status"
                                                        value="rejected" class="w-4 h-4 mr-2 accent-red-600">
                                                    <span class="group-hover:text-red-700">TOLAK</span>
                                                </label>
                                            </div>
                                            @if ($approvalData[$item->id]['status'] === 'rejected')
                                                <textarea wire:model="approvalData.{{ $item->id }}.reason"
                                                    class="w-full border-2 border-dashed border-red-200 bg-red-50 p-3 text-[10px] font-bold outline-none rounded-lg focus:border-red-400 transition-all"
                                                    rows="2" placeholder="TULIS ALASAN PENOLAKAN..."></textarea>
                                            @endif
                                        @else
                                            <div
                                                class="text-[10px] font-black uppercase text-center py-2 bg-white rounded border border-gray-200">
                                                @if ($item->internal_status === 'rejected')
                                                    <span class="text-red-600 tracking-widest">❌ Ditolak
                                                        ({{ $itemDeptCode }})
                                                    </span>
                                                @elseif($item->internal_status === 'approved')
                                                    <span class="text-green-600 tracking-widest">✅ Disetujui
                                                        Final</span>
                                                @elseif($item->internal_status === 'pending_manager')
                                                    <span class="text-amber-500 tracking-widest">⏳ Menunggu Mgr.
                                                        {{ $itemDeptCode }}</span>
                                                @else
                                                    <span class="text-gray-500 tracking-widest">⏳ Menunggu Admin
                                                        {{ $itemDeptCode }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if (!empty($approvalData))
                <div class="flex justify-center mb-20">
                    <button wire:click="submitApproval"
                        class="bg-[#f0f3f6] text-[#003882] font-black py-5 px-16 rounded-[2rem] shadow-[10px_10px_20px_#d1d9e6,-10px_-10px_20px_#ffffff] border-2 border-white/50 hover:shadow-inner uppercase tracking-[0.2em] transition-all active:scale-95 flex items-center group">
                        <span class="mr-3 text-xl group-hover:rotate-12 transition-transform">⚡</span> SIMPAN KEPUTUSAN
                        DOKUMEN
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
