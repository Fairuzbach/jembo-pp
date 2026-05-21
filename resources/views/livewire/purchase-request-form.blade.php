    <?php
    
    use Livewire\Component;
    use App\Models\MasterItem;
    use App\Models\PurchaseRequest;
    use App\Services\WhatsappService;
    
    new class extends Component {
        public $expense_type = 'CAPEX';
        public $remarks = '';
    
        // Checkbox Pembelian Terkait
        public $related_sales_support = false;
        public $related_it = false;
        public $related_pe = false;
        public $related_ga = false;
        public $related_maintenance = false;
        public $related_fh = false;
        public $related_qc = false;
        public $related_energy = false;
    
        public $requestItems = [];
        protected $masterItemsList = [];
    
        public $requester_dept_code;
        public $requester_name;
    
        public function mount()
        {
            $user = auth()->user();
    
            $this->requester_name = $user->name;
            $this->requester_dept_code = $user->dept->code ?? 'N/A';
            $this->masterItemsList = MasterItem::with('department')->get();
            $this->addItem();
        }
        public function hydrate()
        {
            $this->masterItemsList = MasterItem::with('department')->get();
        }
        public function render()
        {
            return view('livewire.purchase-request-form', [
                'masterItemsList' => $this->masterItemsList,
                'masterItemsJson' => $this->masterItemsList
                    ->map(
                        fn($i) => [
                            'name' => $i->name,
                            'account_no' => $i->account_no,
                            'unit' => $i->unit,
                        ],
                    )
                    ->toJson(),
            ]);
        }
    
        /**
         * Hook Otomatis: Dijalankan setiap kali properti $requestItems berubah.
         * Tujuan: Menyalakan checkbox related_* secara otomatis & Auto-fill Unit/COA.
         */
        public function updatedRequestItems()
        {
            foreach ($this->requestItems as $index => $item) {
                // Jika user mengetikkan sesuatu di input nama barang
                if (!empty($item['item_name'])) {
                    // Cari barang berdasarkan namanya
                    $masterItem = $this->masterItemsList->firstWhere('name', $item['item_name']);
    
                    if ($masterItem) {
                        // Jika ketemu, isi ID, Unit, dan Account No secara diam-diam di belakang layar
                        $this->requestItems[$index]['master_item_id'] = $masterItem->id;
                        $this->requestItems[$index]['unit'] = $masterItem->unit;
                        $this->requestItems[$index]['account_no'] = $masterItem->account_no;
                    } else {
                        // Jika tidak ketemu (user ngetik sembarangan), kosongkan ID-nya
                        $this->requestItems[$index]['master_item_id'] = null;
                        $this->requestItems[$index]['unit'] = '';
                        $this->requestItems[$index]['account_no'] = '';
                    }
                }
            }
        }
    
        private function resetCheckboxes()
        {
            $this->related_it = false;
            $this->related_ga = false;
            $this->related_maintenance = false;
            $this->related_fh = false;
            $this->related_pe = false;
            $this->related_qc = false;
            $this->related_sales_support = false;
            $this->related_energy = false;
        }
    
        public function addItem()
        {
            $this->requestItems[] = [
                'item_name' => '',
                'master_item_id' => '',
                'unit' => '',
                'account_no' => '',
                'department_name' => '',
                'quantity' => 1,
                'usage_purpose' => '',
                'requirement_date' => '',
            ];
        }
    
        public function removeItem($index)
        {
            unset($this->requestItems[$index]);
            $this->requestItems = array_values($this->requestItems);
        }
    
        public function applyAISuggestion()
        {
            // 1. Reset semua centang
            $this->related_sales_support = false;
            $this->related_it = false;
            $this->related_pe = false;
            $this->related_ga = false;
            $this->related_maintenance = false;
            $this->related_fh = false;
            $this->related_qc = false;
            $this->related_energy = false;
    
            foreach ($this->requestItems as $row) {
                if (!empty($row['master_item_id'])) {
                    $item = $this->masterItemsList->firstWhere('id', $row['master_item_id']);
    
                    if ($item) {
                        // Pengecekan Energi
                        if ($item->requires_energy) {
                            $this->related_energy = true;
                        }
    
                        // Fallback jika casting gagal: Decode manual string JSON ke Array
                        $rawTags = is_string($item->ai_tags) ? json_decode($item->ai_tags, true) : $item->ai_tags;
    
                        if (is_array($rawTags)) {
                            // Ubah semua tag menjadi huruf kecil agar pencarian (Maintenance vs maintenance) tidak error
                            $tags = array_map('strtolower', $rawTags);
    
                            // ==========================================
                            // KAMUS KECERDASAN BUATAN (AI DICTIONARY)
                            // array_intersect mengecek apakah ada kata kunci di dalam tag
                            // ==========================================
    
                            // Cek Sales Support
                            if (array_intersect(['sales support', 'sales', 'marketing', 'brosur'], $tags)) {
                                $this->related_sales_support = true;
                            }
    
                            // Cek IT
                            if (array_intersect(['information technology', 'it', 'komputer', 'laptop', 'software', 'kabel lan'], $tags)) {
                                $this->related_it = true;
                            }
    
                            // Cek Process Engineering (Engineering)
                            if (array_intersect(['process engineering', 'engineering', 'pe', 'mesin', 'desain'], $tags)) {
                                $this->related_pe = true;
                            }
    
                            // Cek GA
                            if (array_intersect(['general affair', 'ga', 'atk', 'kertas', 'tinta', 'kebersihan'], $tags)) {
                                $this->related_ga = true;
                            }
    
                            // Cek Maintenance (Contoh: Bearing, Tembaga, Sparepart)
                            if (array_intersect(['maintenance', 'sparepart', 'bearing', 'tembaga', 'pelumas', 'baut'], $tags)) {
                                $this->related_maintenance = true;
                            }
    
                            // Cek Facility & HSE
                            if (array_intersect(['facility & hse', 'hse', 'k3', 'safety', 'apar', 'helm', 'sepatu'], $tags)) {
                                $this->related_fh = true;
                            }
    
                            // Cek QC
                            if (array_intersect(['quality control', 'qc', 'inspeksi', 'alat ukur', 'kalibrasi'], $tags)) {
                                $this->related_qc = true;
                            }
                        }
                    }
                }
            }
    
            $this->dispatch('swal-toast', [
                'title' => '✨ Rekomendasi diterapkan!',
                'icon' => 'success',
            ]);
        }
    
        public function getKepadaText()
        {
            $deps = [];
            if ($this->related_it) {
                $deps[] = 'INFORMATION TECHNOLOGY';
            }
            if ($this->related_ga) {
                $deps[] = 'GENERAL AFFAIR';
            }
            if ($this->related_maintenance) {
                $deps[] = 'MAINTENANCE';
            }
            if ($this->related_fh) {
                $deps[] = 'FACILITY & HSE';
            }
            if ($this->related_pe) {
                $deps[] = 'PROCESS ENGINEERING';
            }
            if ($this->related_qc) {
                $deps[] = 'QUALITY CONTROL';
            }
            if ($this->related_sales_support) {
                $deps[] = 'SALES SUPPORT';
            }
    
            if (empty($deps)) {
                return 'DEPARTEMEN ...';
            }
    
            return implode(', ', $deps);
        }
    
        public function getPoolingManagersText()
        {
            $deps = [];
            // Format: [Nama Manager] (Mgr. KodeDept)
            if ($this->related_it) {
                $deps[] = '[Nama Manager] (Mgr. IT)';
            }
            if ($this->related_ga) {
                $deps[] = '[Nama Manager] (Mgr. GA)';
            }
            if ($this->related_maintenance) {
                $deps[] = '[Nama Manager] (Mgr. MTC)';
            }
            if ($this->related_fh) {
                $deps[] = '[Nama Manager] (Mgr. HSE)';
            }
            if ($this->related_pe) {
                $deps[] = '[Nama Manager] (Mgr. PE)';
            }
            if ($this->related_qc) {
                $deps[] = '[Nama Manager] (Mgr. QC)';
            }
            if ($this->related_sales_support) {
                $deps[] = '[Nama Manager] (Mgr. SLS)';
            }
    
            if (empty($deps)) {
                return '...';
            }
    
            return implode(' / ', $deps);
        }
    
        public function getNoPPText()
        {
            $romanMonths = [
                '1' => 'I',
                '2' => 'II',
                '3' => 'III',
                '4' => 'IV',
                '5' => 'V',
                '6' => 'VI',
                '7' => 'VII',
                '8' => 'VIII',
                '9' => 'IX',
                '10' => 'X',
                '11' => 'XI',
                '12' => 'XII',
            ];
    
            $month = date('n');
            $romanMonth = $romanMonths[$month];
            $year = date('Y');
    
            return "... / {$this->requester_dept_code} / {$romanMonth} / {$year} / {$this->expense_type}";
        }
    
        public function submitRequest()
        {
            /**
             * TAHAP 1: VALIDASI DATA
             * Tujuan: Memastikan field wajib diisi dan minimal ada 1 barang yang dipesan.
             */
            if (empty($this->requestItems) || empty($this->requestItems[0]['master_item_id'])) {
                $this->dispatch('swal', [
                    'icon' => 'warning',
                    'title' => 'Tidak Ada Barang Dipilih',
                    'text' => 'Anda perlu memilih minimal satu barang sebelum mengirim permintaan.',
                    'hint' => 'Centang checkbox pada baris barang di tabel, lalu klik Kirim kembali.',
                ]);
                return;
            }
    
            $noPPFinal = '';
    
            \DB::transaction(function () use (&$noPPFinal) {
                /**
                 * TAHAP 2: SIMPAN HEADER DOKUMEN (Level Induk)
                 * Tujuan: Membuat nomor PP unik dan menetapkan status awal ke Manager Dept Requester.
                 */
                $currentYear = date('Y');
                $lastSequence = PurchaseRequest::whereYear('created_at', $currentYear)->max('user_sequence') ?? 0;
                $newSequence = $lastSequence + 1;
    
                $romanMonths = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI', '7' => 'VII', '8' => 'VIII', '9' => 'IX', '10' => 'X', '11' => 'XI', '12' => 'XII'];
                $noPPFinal = "{$newSequence}/{$this->requester_dept_code}/{$romanMonths[date('n')]}/{$currentYear}/{$this->expense_type}";
    
                $request = PurchaseRequest::create([
                    // 1.Gunakan ID user yang sedang login saat ini
                    'requester_id' => auth()->user()->id,
    
                    // 2.Simpan kode departemen ke database
                    'requester_dept_code' => $this->requester_dept_code,
    
                    'user_sequence' => $newSequence,
                    'pp_number' => $noPPFinal,
                    'expense_type' => $this->expense_type,
                    'remarks' => $this->remarks,
                    'related_it' => $this->related_it,
                    'related_ga' => $this->related_ga,
                    'related_maintenance' => $this->related_maintenance,
                    'related_fh' => $this->related_fh,
                    'related_pe' => $this->related_pe,
                    'related_qc' => $this->related_qc,
                    'related_sales_support' => $this->related_sales_support,
                    'related_energy' => $this->related_energy,
                    'status' => 'pending_manager_dept_requester',
                ]);
    
                /**
                 * TAHAP 3: SIMPAN DETAIL BARANG (Level Item)
                 * Tujuan: Memecah daftar belanjaan menjadi baris individual
                 * dengan status 'pending' agar bisa dideteksi oleh Admin Pooling nanti.
                 */
                foreach ($this->requestItems as $item) {
                    if (!empty($item['master_item_id'])) {
                        $request->items()->create([
                            'master_item_id' => $item['master_item_id'],
                            'quantity' => $item['quantity'],
                            'usage_purpose' => $item['usage_purpose'],
                            'requirement_date' => $item['requirement_date'] ?: null,
                            'internal_status' => 'pending',
                        ]);
                    }
                }
            });
    
            try {
                $this->sendWhatsappNotification($noPPFinal);
            } catch (\Exception $e) {
                \Log::error('Gagal mengirim notif Whatsapp: ' . $e->getMessage());
            }
    
            // 3. Notifikasi Sukses & Reset Form
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Permintaan Terkirim',
                'text' => 'Notifikasi WhatsApp telah diteruskan ke Manager Anda.',
                'meta' => [['icon' => '📋', 'label' => 'No. PP', 'value' => $noPPFinal], ['icon' => '📱', 'label' => 'WA Status', 'value' => 'Terkirim ke Manager'], ['icon' => '🕐', 'label' => 'Waktu', 'value' => now()->format('H:i, d M Y')]],
            ]);
    
            // Reset variabel agar form kosong kembali
            $this->remarks = '';
            $this->requestItems = [];
            $this->addItem(); // Tambah 1 baris kosong lagi
            $this->related_it = $this->related_ga = $this->related_maintenance = $this->related_fh = $this->related_pe = $this->related_qc = $this->related_sales_support = $this->related_energy = false;
        }
    
        private function sendWhatsAppNotification($noPP)
        {
            // Ganti dengan nomor Anda untuk testing (nanti akan ditarik otomatis dari database)
            $managerPhone = '085156469296';
    
            $message = "🔔 *Notifikasi Jembocable PP*\n\n" . "Halo Bapak/Ibu Manager,\n" . "Ada pengajuan Purchase Request (PP) baru yang membutuhkan *Approval* Anda.\n\n" . "📄 *No. PP:* {$noPP}\n" . "👤 *Dari:* {$this->requester_name}\n" . "🏢 *Dept:* {$this->requester_dept_code}\n" . "🏷️ *Tipe:* {$this->expense_type}\n\n" . "Silakan login ke aplikasi untuk meninjau detail barang dan melakukan persetujuan.\n\n" . 'Terima kasih.';
    
            // Memanggil service milik Anda!
            WhatsappService::send($managerPhone, $message);
        }
    };
    ?>
    <div class="min-h-screen"
        style="
        background-color: #0d1b2a;
        background-image:
            linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px),
            linear-gradient(rgba(56,139,253,0.07) 1px, transparent 1px),
            linear-gradient(90deg, rgba(56,139,253,0.07) 1px, transparent 1px);
        background-size: 80px 80px, 80px 80px, 16px 16px, 16px 16px;
        padding: 2rem 1.5rem 4rem;
    ">
        {{-- Corner accents dekoratif --}}
        <div style="position:fixed; top:0; left:0; width:180px; height:180px; pointer-events:none; z-index:0;">
            <svg viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg"
                style="width:100%;height:100%;opacity:.18;">
                <path d="M0 0 L80 0 L0 80 Z" fill="#2563eb" />
                <path d="M0 0 L40 0 L0 40 Z" fill="#60a5fa" />
            </svg>
        </div>
        <div style="position:fixed; bottom:0; right:0; width:180px; height:180px; pointer-events:none; z-index:0;">
            <svg viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg"
                style="width:100%;height:100%;opacity:.12;">
                <path d="M180 180 L100 180 L180 100 Z" fill="#2563eb" />
                <path d="M180 180 L140 180 L180 140 Z" fill="#60a5fa" />
            </svg>
        </div>

        {{-- TOP BAR --}}
        <div class="max-w-[1200px] mx-auto mb-5 flex items-center justify-between"
            style="position:relative; z-index:1;">

            {{-- Tombol Back --}}
            <a href="{{ url()->previous() }}"
                class="group flex items-center gap-2 px-4 py-2 rounded-lg font-bold text-sm transition-all"
                style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#93c5fd; text-decoration:none; backdrop-filter:blur(4px);"
                onmouseover="this.style.background='rgba(37,99,235,0.25)'; this.style.borderColor='rgba(96,165,250,0.4)'; this.style.color='#bfdbfe'"
                onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='rgba(255,255,255,0.12)'; this.style.color='#93c5fd'">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>

            {{-- Label dokumen --}}
            <div class="flex items-center gap-3">
                <div
                    style="width:6px;height:6px;border-radius:50%;background:#22c55e;box-shadow:0 0 8px #22c55e;animation:pulse 2s infinite;">
                </div>
                <span
                    style="font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,0.35);">
                    Purchase Request — {{ auth()->user()->dept->code ?? 'DEPT' }}
                </span>
            </div>
        </div>
        <div class="max-w-[1200px] mx-auto bg-white text-black font-sans text-sm mb-20 shadow-xl">
            <div class="border-2 border-black">

                <div class="flex border-b-2 border-black">
                    <div class="w-1/4 p-4 flex justify-center items-center border-r border-black bg-white">
                        <img src="{{ asset('images/logo jembo.webp') }}" alt="Jembo Cable Logo"
                            class="max-h-20 object-contain">
                    </div>

                    <div
                        class="w-2/4 flex flex-col justify-center items-center p-2 text-center border-r border-black font-bold">
                        <div class="text-base uppercase">Sistem Manajemen Terintegrasi</div>
                        <div class="text-xs uppercase">(Mutu, K3, Lingkungan dan Energi)</div>
                        <div class="text-blue-600 text-xs italic uppercase mb-2">Integrated Management
                            System<br>(Quality,
                            OHS,
                            Environment and Energy)</div>
                        <div class="text-base uppercase border-t border-black w-full pt-1 mt-1">Form Permintaan
                            Pembelian
                            Barang
                            Dan Jasa</div>
                        <div class="text-blue-600 text-xs italic uppercase">Goods and Services Purchase Request Form
                        </div>
                        @if (session()->has('success'))
                            <div class="mb-4 p-3 bg-green-500 text-white font-bold rounded shadow flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7">
                                    </path>
                                </svg>
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session()->has('error'))
                            <div class="mb-4 p-3 bg-red-500 text-white font-bold rounded shadow flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                    <div class="w-1/4">
                        <table class="w-full text-xs h-full">
                            <tr class="border-b border-black">
                                <td class="p-1 border-r border-black w-1/2">No. Document</td>
                                <td class="p-1 font-bold">JCC-SC-PS-002-F001</td>
                            </tr>
                            <tr class="border-b border-black">
                                <td class="p-1 border-r border-black">Effective Date</td>
                                <td class="p-1 font-bold">20-May-24</td>
                            </tr>
                            <tr class="border-b border-black">
                                <td class="p-1 border-r border-black">Revision</td>
                                <td class="p-1 font-bold">06</td>
                            </tr>
                            <tr>
                                <td class="p-1 border-r border-black">Page</td>
                                <td class="p-1 font-bold">01</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="flex border-b-2 border-black min-h-[100px]">
                    <div class="w-1/3 p-2 border-r border-black flex flex-col justify-center">
                        <table class="w-full text-xs font-bold border border-black text-left">
                            <tr class="border-b border-black">
                                <td class="p-1 border-r border-black w-1/4 bg-gray-50 text-[10px]">Kepada<br><span
                                        class="text-blue-600 italic font-normal text-[9px]">To</span></td>
                                <td class="p-1 font-bold text-[10px] leading-tight text-blue-900 uppercase">
                                    {{ $this->getKepadaText() }}</td>
                            </tr>
                            <tr>
                                <td class="p-1 border-r border-black bg-gray-50 text-[10px]">Dari<br><span
                                        class="text-blue-600 italic font-normal text-[9px]">From</span></td>
                                <td class="p-1 uppercase text-[10px]">{{ auth()->user()->dept->name }}</td>
                            </tr>
                        </table>
                    </div>

                    <div
                        class="w-1/3 p-2 border-r border-black flex flex-col justify-center items-center relative text-center">
                        <div class="font-bold text-base mb-2">No. PP : <span
                                class="text-red-600">{{ $this->getNoPPText() }}</span></div>
                        <div class="flex items-center space-x-2 text-xs">
                            <label class="font-bold">Tipe Pengeluaran:</label>
                            <select wire:model.live="expense_type"
                                class="border border-black px-1 outline-none font-bold bg-yellow-50 cursor-pointer">
                                <option value="CAPEX">CAPEX</option>
                                <option value="OPEX">OPEX</option>
                            </select>
                        </div>
                    </div>

                    <div class="w-1/3 p-2 text-xs flex flex-col relative">
                        <div class="flex justify-between items-start mb-1">
                            <span class="italic bg-gray-50 px-1 font-semibold border border-gray-300">Pembelian Terkait
                                :</span>
                            <button wire:click="applyAISuggestion" type="button"
                                class="bg-blue-100 border border-blue-500 text-blue-700 hover:bg-blue-200 px-2 py-0.5 rounded text-[10px] font-bold flex items-center absolute top-2 right-2 transition-transform active:scale-95">
                                ✨ AI Suggest
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-x-2 gap-y-1 mt-2">
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_fh"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span
                                    class="text-[10px]">Facility & HSE</span></label>
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_it"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span
                                    class="text-[10px]">IT</span></label>
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_pe"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span class="text-[10px]">Process
                                    Eng.</span></label>
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_ga"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span class="text-[10px]">General
                                    Affair</span></label>
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_maintenance"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span
                                    class="text-[10px]">Maintenance</span></label>
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_qc"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span class="text-[10px]">Quality
                                    Control</span></label>
                            <label class="flex items-center cursor-pointer hover:bg-gray-50"><input type="checkbox"
                                    wire:model.live="related_sales_support"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span class="text-[10px]">Sales
                                    Support</span></label>
                            <label
                                class="flex items-center cursor-pointer bg-yellow-50 px-1 border border-yellow-200"><input
                                    type="checkbox" wire:model.live="related_energy"
                                    class="mr-1 accent-black cursor-pointer text-xs"><span
                                    class="text-[10px] font-bold text-amber-600 uppercase">ENERGY</span></label>
                        </div>
                    </div>
                </div>

                <div class="p-2 border-b-2 border-black text-xs font-bold bg-gray-50 uppercase leading-tight">
                    Mohon dapat disediakan permintaan pembelian barang atau jasa yang tercantum dibawah ini:<br>
                    <span class="text-blue-600 italic font-normal text-[10px]">Please provide a purchase request for
                        the
                        goods
                        or services listed below :</span>
                </div>
                @once
                    <script>
                        window.__masterItems = {!! $masterItemsJson !!};
                    </script>
                @endonce
                <table class="w-full text-xs text-center border-collapse">
                    <thead>
                        <tr class="border-b border-black bg-gray-50">
                            <th class="border-r border-black p-1 w-8">No</th>
                            <th class="border-r border-black p-1 w-1/3">Nama Barang / Jasa<br><span
                                    class="text-blue-600 font-normal italic">Name of goods or services</span></th>
                            <th class="border-r border-black p-1 w-16">Unit</th>
                            <th class="border-r border-black p-1 w-20 text-[10px]">Jumlah<br>Permintaan<br><span
                                    class="text-blue-600 font-normal italic">Order Qty</span></th>
                            <th class="border-r border-black p-1">Dibutuhkan Untuk<br><span
                                    class="text-blue-600 font-normal italic">Using For</span></th>
                            <th class="border-r border-black p-1 w-28">Tgl<br>Dibutuhkan<br><span
                                    class="text-blue-600 font-normal italic">Req. Date</span></th>
                            <th class="p-1 w-24">No. Akun<br><span
                                    class="text-blue-600 font-normal italic text-[10px]">Account No.</span></th>
                            <th class="border-l border-black p-1 w-10 text-red-600">X</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requestItems as $index => $item)
                            <tr class="border-b border-black hover:bg-yellow-50 transition-colors">
                                <td class="border-r border-black p-1 font-bold">{{ $index + 1 }}</td>
                                <td class="p-2">
                                    <div class="relative" x-data="{
                                        open: false,
                                        search: '{{ $requestItems[$index]['item_name'] ?? '' }}',
                                        items: window.__masterItems,
                                        get filtered() {
                                            if (!this.search) return this.items;
                                            return this.items.filter(i =>
                                                i.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                                i.account_no.toLowerCase().includes(this.search.toLowerCase())
                                            );
                                        }
                                    }" x-init="search = $wire.requestItems[{{ $index }}]?.item_name ?? ''"
                                        @click.outside="open = false">

                                        <input type="text" x-model="search"
                                            @input="open = true; $wire.set('requestItems.{{ $index }}.item_name', search)"
                                            @focus="open = true" @keydown.escape="open = false"
                                            class="w-full bg-transparent border-b-2 border-gray-100 focus:border-[#003882] outline-none text-xs font-bold uppercase py-1"
                                            placeholder="Ketik nama barang...">

                                        <div x-show="open && filtered.length > 0"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto"
                                            style="min-width: 220px;">

                                            <template x-for="(item, i) in filtered" :key="i">
                                                <div @click="
                            search = item.name;
                            $wire.set('requestItems.{{ $index }}.item_name', item.name);
                            open = false;
                        "
                                                    class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-[#003882]/5 group transition-colors">

                                                    <div
                                                        class="flex-shrink-0 w-6 h-6 rounded bg-[#003882]/10 flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-[#003882]" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M16 3H8v4h8V3z" />
                                                        </svg>
                                                    </div>

                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs font-bold uppercase text-gray-800 truncate group-hover:text-[#003882]"
                                                            x-text="item.name"></p>
                                                        <p class="text-[10px] text-gray-400 truncate"
                                                            x-text="item.account_no + ' · ' + item.unit"></p>
                                                    </div>
                                                </div>
                                            </template>

                                            <div x-show="filtered.length === 0 && search.length > 0"
                                                class="px-3 py-4 text-center text-xs text-gray-400">
                                                Barang tidak ditemukan
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="border-r border-black p-1">{{ $item['unit'] }}</td>
                                <td class="border-r border-black p-0">
                                    <input type="number" wire:model="requestItems.{{ $index }}.quantity"
                                        class="w-full h-full text-center bg-transparent outline-none p-1"
                                        min="1">
                                </td>
                                <td class="border-r border-black p-0">
                                    <input type="text" wire:model="requestItems.{{ $index }}.usage_purpose"
                                        class="w-full h-full bg-transparent outline-none px-2 py-1">
                                </td>
                                <td class="border-r border-black p-0">
                                    <input type="date"
                                        wire:model="requestItems.{{ $index }}.requirement_date"
                                        class="w-full h-full bg-transparent outline-none px-1 py-1 text-[11px] text-center cursor-pointer">
                                </td>
                                <td class="p-1 font-bold uppercase">{{ $item['account_no'] }}</td>
                                <td class="border-l border-black p-1">
                                    <button wire:click="removeItem({{ $index }})" type="button"
                                        class="text-red-400 hover:text-red-700 font-bold px-1 rounded transition-colors uppercase text-[10px]">X</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-b-2 border-black p-1 bg-gray-100 flex justify-center">
                    <button wire:click="addItem" type="button"
                        class="text-[10px] text-blue-600 font-bold hover:underline px-4 py-1 uppercase">
                        + Tambah Baris Barang
                    </button>
                </div>

                <div class="border-b-2 border-black flex flex-col h-20 bg-white">
                    <div class="px-2 pt-1 text-xs font-bold uppercase">Catatan/Remarks:</div>
                    <textarea wire:model="remarks" class="w-full h-full bg-transparent outline-none px-2 py-1 resize-none text-[11px]"
                        placeholder="..."></textarea>
                </div>

                <div class="border-b-2 border-black p-1 px-2 text-[10px] font-bold italic bg-gray-50 leading-tight">
                    Note: Untuk pengadaan barang / Jasa baru yang berkaitan dengan HSE, IT System dan Energy harus
                    diperiksa
                    oleh department terkait.
                </div>

                <div class="flex text-center text-[9px] min-h-[90px] bg-white">

                    <div class="w-1/3 border-r border-black flex flex-col justify-between pt-2 pb-1 uppercase">
                        <div>Diperiksa oleh/<br><span class="text-blue-600 font-normal italic">Checked by</span></div>
                        <div class="border-t border-black mx-12 mt-10 pt-1 font-bold leading-tight">
                            {{ $this->getPoolingManagersText() }}
                        </div>
                    </div>

                    <div class="w-1/3 border-r border-black flex flex-col justify-between pt-2 pb-1 uppercase">
                        <div>Disetujui oleh/<br><span class="text-blue-600 font-normal italic">Approved by</span></div>
                        <div class="border-t border-black mx-12 mt-10 pt-1 font-bold">
                            [Nama Manager] (Mgr. {{ $this->requester_dept_code }})
                        </div>
                    </div>

                    <div class="w-1/3 flex flex-col justify-between pt-2 pb-1 uppercase relative">
                        <div class="text-left px-2 font-bold absolute top-1 left-1 italic uppercase">Tanggal/<span
                                class="text-blue-600 font-normal italic">Date</span> : <span
                                class="font-normal">{{ date('d-M-Y') }}</span></div>
                        <div class="mt-4">Diminta oleh/<br><span
                                class="text-blue-600 font-normal italic text-[8px]">Requested by</span></div>
                        <div class="border-t border-black mx-12 mt-6 pt-1 font-bold text-blue-800">
                            {{ $this->requester_name }}
                        </div>
                    </div>

                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button wire:click="submitRequest" type="button"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded shadow shadow-blue-200 transition-all active:scale-95 uppercase text-sm flex items-center">
                    Kirim Request Pembelian
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
        <style>
            @keyframes pulse {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: .4;
                }
            }
        </style>
    </div>
