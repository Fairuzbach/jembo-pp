<?php

use Livewire\Volt\Component;
use App\Models\MasterItem;
use App\Models\Department;
use App\Models\ItemRequest;
use App\Models\ItemGroup;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $currentStep = 1;
    public $itemRequestId = null;
    public $is_serialized = false;

    // --- STEP 1: General Info & Code Generator ---
    public $item_code = '';
    public $item_name = '';
    public $unit = 'pcs';
    public $unit_child = 'Pieces buah';

    // Variabel untuk Rules Generate Code
    public $departmentCode = ''; // DD
    public $selectedCategory = ''; // CC
    public $selectedSubcategory = ''; // SSSS
    public $selectedType = ''; // TTT

    // Variabel Item Group
    public $item_group_id = '';
    public $item_group_child = '';

    // State untuk Searchable Dropdown Item Group
    public $groupSearch = '';
    public $isDropdownOpen = false;
    public $isSearching = false;

    // Master Data Pilihan (Sesuai Rules Anda)
    public $categories = [
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
    ];

    public $types = [
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
    ];

    // Struktur Data Sub-Kategori (Silakan sesuaikan isi kode 4-digitnya dengan gambar Anda)
    public $subcategories = [
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
    ];

    // --- STEP 2 s/d STEP 3 Variables ---
    public $site_code = 'JCC1';
    public $warehouse_code = '';
    public $serial_number = '';

    // Dimensi diset default 0
    public $weight = 0;
    public $length = 0;
    public $width = 0;
    public $height = 0;

    public $hazardous_material = false;
    public $class_of_risk = '';

    // Pembelian & Harga diset default
    public $buy_from_bp = '';
    public $standard_cost = 0;
    public $currency = 'IDR'; // Default IDR
    public $tax_code = '';

    // Aturan Stok diset default
    public $safety_stock = 0;
    public $reorder_point = 0;
    public $min_order_qty = 0;
    public $max_order_qty = 99999999; // Default Max Order

    public function mount($itemRequestId = null)
    {
        if ($itemRequestId) {
            $this->itemRequestId = $itemRequestId;

            // Eager load relasi requester dan dept-nya
            $req = ItemRequest::with('requester.dept')->find($itemRequestId);

            if ($req) {
                // 1. Tarik Data Default dari Pengajuan
                $this->item_name = $req->name;
                $this->unit = $req->unit;

                // 2. KUNCI DD: Ambil otomatis kode departemen dari requester pengaju
                if ($req->requester && $req->requester->dept) {
                    $this->departmentCode = strtoupper($req->requester->dept->code);
                } else {
                    $this->departmentCode = 'OT'; // Default 'OTHER' jika data dept tidak ditemukan
                }

                // 3. Set Item Group jika requester sudah memilih grup yang ada sebelumnya
                if ($req->item_group_id) {
                    $this->item_group_id = $req->item_group_id;
                    $group = ItemGroup::find($req->item_group_id);
                    if ($group) {
                        $this->item_group_child = $group->description;
                        $this->groupSearch = $group->code . ' - ' . $group->description;
                    }
                }
            }
        }
    }

    // Mengosongkan sub-kategori otomatis jika kategori utama diganti oleh procurement
    public function updatedSelectedCategory()
    {
        $this->selectedSubcategory = '';
    }

    // Logic Pemasukan Berdasarkan Pilihan Dropdown Item Group
    public function getFilteredGroupsProperty()
    {
        if (!$this->isSearching || empty($this->groupSearch)) {
            return ItemGroup::orderBy('code', 'asc')->limit(15)->get();
        }
        return ItemGroup::where('code', 'like', '%' . $this->groupSearch . '%')
            ->orWhere('description', 'like', '%' . $this->groupSearch . '%')
            ->limit(15)
            ->get();
    }

    public function selectGroup($id, $code, $desc)
    {
        $this->item_group_id = $id;
        $this->item_group_child = $desc;
        $this->groupSearch = $code . ' - ' . $desc;
        $this->isSearching = false;
        $this->isDropdownOpen = false;
    }

    // --- LOGIKA GENERATE ITEM CODE (DDCCSSSSTTTXXXXX) ---
    public function generateItemCode()
    {
        // 1. Validasi Kelengkapan Prefix
        if (empty($this->departmentCode) || empty($this->selectedCategory) || empty($this->selectedSubcategory) || empty($this->selectedType)) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Gagal Generate',
                'text' => 'Harap tentukan Kategori, Sub-Kategori, dan Tipe terlebih dahulu!',
            ]);
            return;
        }

        // 2. Gabungkan Komponen Menjadi Prefix Utama (11 Karakter)
        $prefix = strtoupper($this->departmentCode . $this->selectedCategory . $this->selectedSubcategory . $this->selectedType);

        // 3. Cari Running Number Terakhir di Database MasterItem Berdasarkan Prefix Ini
        $lastItem = MasterItem::where('item_code', 'like', $prefix . '%')
            ->orderBy('item_code', 'desc')
            ->first();

        if ($lastItem) {
            // Ambil 5 digit terakhir kodenya, konversi ke int, lalu tambah 1
            $lastRunningNumber = (int) substr($lastItem->item_code, -5);
            $nextNumber = $lastRunningNumber + 1;
        } else {
            // Jika belum pernah ada kombinasi prefix ini, mulai dari 1
            $nextNumber = 1;
        }

        // 4. Set Hasil Akhir ke Variabel $item_code (Pad dengan angka 0 di depan hingga total panjang 5 digit)
        $this->item_code = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Kode Berhasil Dibuat',
            'text' => "Item Code: {$this->item_code}",
        ]);
    }

    public function nextStep()
    {
        if ($this->currentStep == 1) {
            $this->validate(
                [
                    'item_code' => 'required|string|size:16|unique:master_items,item_code',
                    'item_name' => 'required|string',
                    'unit' => 'required|string',
                ],
                [
                    'item_code.required' => 'Item Code wajib diisi.',
                    'item_code.size' => 'Item Code harus terdiri dari tepat 16 karakter/digit!',
                    'item_code.unique' => 'Item Code ini sudah pernah didaftarkan.',
                ],
            );
        }
        if ($this->currentStep < 3) {
            $this->currentStep++;
        }
    }

    public function prevStep()
    {
        $this->currentStep--;
    }
    public function submit()
    {
        // 1. Set Default Unit (TYPO DIPERBAIKI)
        $this->unit = 'pcs';
        $this->unit_child = 'Pieces buah';

        // 2. Siapkan Aturan Validasi Dasar
        $rules = [
            'item_code' => 'required|string|size:16|unique:master_items,item_code',
            'item_name' => 'required|string',
        ];

        // 3. Validasi Dinamis: Jika Serialized ON, SN wajib diisi!
        if ($this->is_serialized) {
            // Membuat kode unik dummy, misal: SN-64A1B2C3-999
            $this->serial_number = 'SN-' . strtoupper(substr(uniqid(), -6)) . '-' . rand(100, 999);
        } else {
            $this->serial_number = '';
        }

        // 4. Eksekusi Validasi beserta Custom Message
        $this->validate($rules, [
            'item_code.required' => 'Item Code wajib diisi.',
            'item_code.size' => 'Item Code harus terdiri dari tepat 16 karakter/digit!',
            'item_code.unique' => 'Item Code ini sudah pernah didaftarkan di sistem.',
        ]);

        DB::beginTransaction();
        try {
            // 5. Simpan ke tabel master_items
            $master = MasterItem::create([
                'item_request_id' => $this->itemRequestId,
                'item_code' => strtoupper($this->item_code),
                // PENGAMAN ANTI-NULL: Kirim string kosong ('') jika bukan barang serialized
                'serial_number' => $this->is_serialized ? strtoupper($this->serial_number) : '',
                'name' => $this->item_name,
                'unit' => $this->unit,
                'unit_child' => $this->unit_child,
                // Menggunakan item_group_id sesuai deklarasi variabel Anda sebelumnya
                'item_group_id' => strtoupper($this->item_group_id ?? ''),
                'is_synced' => false,
            ]);

            // 6. Simpan ke tabel item_warehouses
            $master->warehouse()->create([
                'warehouse_code' => $this->warehouse_code,
                'weight' => $this->weight ?: 0,
                'length' => $this->length ?: 0,
                'width' => $this->width ?: 0,
                'height' => $this->height ?: 0,
                'hazardous_material' => $this->hazardous_material,
                'class_of_risk' => $this->hazardous_material ? $this->class_of_risk : '',
            ]);

            // 7. Simpan ke tabel item_procurements
            $master->procurement()->create([
                'buy_from_bp' => $this->buy_from_bp,
                'standard_cost' => $this->standard_cost ?: 0,
                'currency' => strtoupper($this->currency ?? 'IDR'),
                'tax_code' => strtoupper($this->tax_code ?? ''),
            ]);

            // 8. Simpan ke tabel item_order_rules
            $master->orderRule()->create([
                'safety_stock' => $this->safety_stock ?: 0,
                'reorder_point' => $this->reorder_point ?: 0,
                'min_order_qty' => $this->min_order_qty ?: 0,
                'max_order_qty' => $this->max_order_qty ?: 0,
            ]);

            // 9. Simpan ke tabel item_serializations
            $master->serialization()->create([
                'is_serialized' => $this->is_serialized,
                // Karena inputan warranty sudah kita hapus, langsung set statis 0
                'warranty_period_months' => 0,
            ]);

            // 10. Update status pengajuan (jika ada)
            if ($this->itemRequestId) {
                ItemRequest::where('id', $this->itemRequestId)->update(['status' => 'completed']);
            }

            DB::commit();

            session()->flash('success', 'Master Item ' . $this->item_code . ' berhasil dibuat dan siap di-export ke ERP!');
            return redirect()->route('dashboard'); // Pastikan route 'dashboard' sudah benar
        } catch (\Exception $e) {
            DB::rollBack();
            // Menangkap dan menampilkan error secara elegan
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
};
?>

<div class="max-w-6xl mx-auto p-8" x-data="{ dropdownOpen: @entangle('isDropdownOpen') }">
    @if ($errors->any())
        <div class="p-4 mb-4 bg-red-100 text-red-700 rounded-xl font-bold">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>⚠️ {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- TAMPILAN ERROR DARI SYSTEM/DATABASE --}}
    @if (session()->has('error'))
        <div
            class="p-4 mb-4 bg-red-100 text-red-700 rounded-xl font-bold border border-red-400 shadow-md animate-bounce">
            ⚠️ {{ session('error') }}
        </div>
    @endif
    <div class="mb-12 text-center">
        <h2 class="text-4xl font-black text-gray-700 tracking-tighter uppercase drop-shadow-md">Item Master Wizard</h2>
        <p class="text-sm font-bold text-gray-500 mt-2">Lengkapi data untuk pendaftaran item ke ERP</p>
    </div>

    <div class="p-10 rounded-3xl bg-[#e0e5ec] shadow-[20px_20px_60px_#bebebe,-20px_-20px_60px_#ffffff]">

        <form wire:submit.prevent="submit">

            @if ($currentStep === 1)
                <div class="animate-fade-in space-y-6">
                    <h3 class="text-xl font-black text-gray-700 mb-4 border-b-2 border-gray-300 pb-2 uppercase">1.
                        General Information & Klasifikasi ERP</h3>

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
                                        <option value="{{ $code }}">{{ $code }} - {{ $label }}
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label
                                class="text-[11px] font-black tracking-widest uppercase ml-2 mb-1 block text-gray-500">Nama
                                Item</label>
                            <input type="text" wire:model="item_name"
                                class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner border-none outline-none text-gray-700 font-bold uppercase">
                        </div>
                        <div>
                            <label
                                class="text-[11px] font-black tracking-widest uppercase ml-2 mb-1 block text-gray-500">Satuan
                                (Unit)</label>
                            <input type="text" wire:model="unit"
                                class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner border-none outline-none text-gray-700 font-bold uppercase">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="relative" x-data="{ dropdownOpen: @entangle('isDropdownOpen') }">
                            <label
                                class="text-[11px] font-black tracking-widest uppercase ml-2 mb-1 block text-gray-600">Item
                                Group</label>

                            <div @click.away="dropdownOpen = false">
                                <input type="text" wire:model.live="groupSearch"
                                    @focus="dropdownOpen = true; $wire.set('isSearching', true)"
                                    placeholder="Cari Kode atau Deskripsi Grup..."
                                    class="w-full p-4 rounded-xl bg-white shadow-[5px_5px_15px_#d1d9e6,-5px_-5px_15px_#ffffff] border-none outline-none text-gray-700 font-bold focus:ring-2 focus:ring-blue-400">

                                <div x-show="dropdownOpen" x-cloak x-transition
                                    class="absolute z-50 w-full mt-2 bg-[#f0f4f8] rounded-xl shadow-2xl max-h-56 overflow-y-auto border border-gray-200">
                                    @forelse($this->filteredGroups as $group)
                                        <div wire:click="selectGroup({{ $group->id }}, '{{ $group->code }}', '{{ $group->description }}')"
                                            class="p-4 hover:bg-blue-100 cursor-pointer border-b border-gray-200 last:border-0 transition-colors">
                                            <div class="text-xs font-black text-blue-600">{{ $group->code }}</div>
                                            <div class="text-[10px] font-bold text-gray-500 uppercase">
                                                {{ $group->description }}</div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-xs font-bold text-gray-400 text-center">Grup tidak
                                            ditemukan</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div>
                            <label
                                class="text-[11px] font-black tracking-widest uppercase ml-2 mb-1 block text-gray-500">Item
                                Group Child</label>
                            <input type="text" wire:model="item_group_child" readonly
                                class="w-full p-4 rounded-xl bg-[#e0e5ec] shadow-inner border-none outline-none text-gray-500 font-bold cursor-not-allowed">
                        </div>
                    </div>
                </div>
            @endif

            @if ($currentStep == 2)
                <div class="space-y-6 animate-fadeIn">
                    <h2 class="text-2xl font-black text-gray-600 mb-6 border-l-4 border-orange-500 pl-4">Logistik &
                        Gudang</h2>

                    <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-orange-50 rounded-2xl border border-orange-100">
                        <div>
                            <label class="text-xs font-bold ml-2 mb-1 block text-gray-500">Site (Lokasi Pabrik)</label>
                            <select wire:model="site_code"
                                class="w-full p-4 rounded-xl bg-white shadow-sm border-none outline-none focus:ring-2 focus:ring-orange-400 font-semibold text-gray-700">
                                <option value="JCC1">JCC1</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold ml-2 mb-1 block text-gray-500">Kode Gudang
                                (Warehouse)</label>
                            <select wire:model="warehouse_code"
                                class="w-full p-4 rounded-xl bg-white shadow-sm border-none outline-none focus:ring-2 focus:ring-orange-400 font-semibold text-gray-700">
                                <option value="">-- Pilih Gudang --</option>
                                {{-- <option value="FG0">FG0 - Wh. Finished Goods 0</option>
                                <option value="FGS">FGS - Wh. Finished Goods Scrapt</option>
                                <option value="FOSF">FOSF - Fiber Optic Shopfloor</option>
                                <option value="LVSF">LVSF - Low Voltage Shopfloor</option>
                                <option value="MVSF">MVSF - Medium Voltage Shopfloor</option>
                                <option value="WHQ1">WHQ1 - Wh. Quarantine Jembo</option>
                                <option value="WHRM">WHRM - WH Raw Material</option>
                                <option value="WHRM1">WHRM1 - WH Raw Material 1</option>
                                <option value="WHRM2">WHRM2 Transit (Peminjaman)</option>
                                <option disabled value="WHRM3">WHRM3</option>
                                <option disabled value="WHRM4">WHRM4</option> --}}
                                <option value="WHSPT">WHSPT - Warehouse Sparepart</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex flex-col justify-center mt-2">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" wire:model.live="hazardous_material" class="sr-only">
                                    <div
                                        class="block w-10 h-6 rounded-full shadow-inner transition-colors duration-300 {{ $hazardous_material ? 'bg-red-500' : 'bg-gray-300' }}">
                                    </div>
                                    <div
                                        class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 {{ $hazardous_material ? 'translate-x-4' : 'translate-x-0' }}">
                                    </div>
                                </div>
                                <span class="text-sm font-black text-gray-700">Barang Berbahaya (Hazardous)</span>
                            </label>

                            @if ($hazardous_material)
                                <div class="mt-3 animate-fadeIn">
                                    <input type="text" wire:model="class_of_risk"
                                        placeholder="Class of Risk (Cth: Flammable)"
                                        class="w-full p-3 text-sm rounded-xl bg-[#e0e5ec] shadow-inner border border-red-200 outline-none">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- STEP 3: Service, Serialization & Finalisasi --}}
            @if ($currentStep == 3)
                <div class="space-y-6 animate-fadeIn">
                    <h2 class="text-2xl font-black text-gray-600 mb-6 border-l-4 border-blue-500 pl-4">Service & Serial
                        Number</h2>

                    <div class="grid grid-cols-1 gap-6">
                        <div class="flex flex-col justify-center">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" wire:model.live="is_serialized" class="sr-only">
                                    <div
                                        class="block w-10 h-6 rounded-full shadow-inner transition-colors duration-300 {{ $is_serialized ? 'bg-teal-500' : 'bg-gray-300' }}">
                                    </div>
                                    <div
                                        class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 {{ $is_serialized ? 'translate-x-4' : 'translate-x-0' }}">
                                    </div>
                                </div>
                                <span class="text-sm font-black text-gray-700">Dilengkapi Nomor Seri
                                    (Serialized)</span>
                            </label>

                            @if ($this->is_serialized)
                                <div class="mt-4 p-4 bg-teal-50 rounded-xl border border-teal-200">
                                    <p class="text-xs font-bold text-teal-700">✅ Serial Number Dummy Unik akan dibuat
                                        secara otomatis oleh sistem saat disimpan.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif



            <div class="flex justify-between mt-12 pt-6 border-t-2 border-gray-300">
                @if ($currentStep > 1)
                    <button type="button" wire:click="prevStep"
                        class="px-8 py-3 rounded-xl bg-[#e0e5ec] shadow-[5px_5px_10px_#b8b9be,-5px_-5px_10px_#ffffff] hover:shadow-inner font-bold text-gray-500 uppercase tracking-widest text-xs">
                        Kembali
                    </button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < 3)
                    <button type="button" wire:click="nextStep"
                        class="px-8 py-3 rounded-xl bg-blue-500 text-white shadow-[0_10px_20px_rgba(59,130,246,0.3)] font-black hover:scale-105 transition-transform uppercase tracking-widest text-xs">
                        Lanjutkan
                    </button>
                @else
                    <button type="submit"
                        class="px-8 py-3 rounded-xl bg-teal-500 text-white shadow-[0_10px_20px_rgba(20,184,166,0.4)] font-black hover:scale-105 transition-transform uppercase tracking-widest text-xs">
                        Simpan Master Data
                    </button>
                @endif
            </div>

        </form>
    </div>
</div>
