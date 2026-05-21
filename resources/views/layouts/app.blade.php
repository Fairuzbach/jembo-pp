<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'e-Procurement System' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- GLOBAL NEOMORPHISM STYLE -->
    <style>
        @keyframes neo-in {
            from {
                opacity: 0;
                transform: scale(.92) translateY(16px)
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0)
            }
        }

        @keyframes neo-out {
            from {
                opacity: 1;
                transform: scale(1)
            }

            to {
                opacity: 0;
                transform: scale(.95) translateY(8px)
            }
        }

        @keyframes toast-in {
            from {
                opacity: 0;
                transform: translateX(110%)
            }

            to {
                opacity: 1;
                transform: translateX(0)
            }
        }

        @keyframes toast-out {
            from {
                opacity: 1;
                transform: translateX(0)
            }

            to {
                opacity: 0;
                transform: translateX(110%)
            }
        }

        @keyframes check-draw {
            from {
                stroke-dashoffset: 80
            }

            to {
                stroke-dashoffset: 0
            }
        }

        @keyframes icon-float {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-4px)
            }
        }

        @keyframes shake-x {

            0%,
            100% {
                transform: translateX(0)
            }

            20%,
            60% {
                transform: translateX(-4px)
            }

            40%,
            80% {
                transform: translateX(4px)
            }
        }

        @keyframes ripple-out {
            from {
                transform: scale(0);
                opacity: .4
            }

            to {
                transform: scale(2.8);
                opacity: 0
            }
        }

        .neo-in {
            animation: neo-in .42s cubic-bezier(.34, 1.32, .64, 1) both
        }

        .neo-out {
            animation: neo-out .22s ease-in both
        }

        .tin {
            animation: toast-in .38s cubic-bezier(.34, 1.32, .64, 1) both
        }

        .tout {
            animation: toast-out .25s ease-in both
        }

        .swal2-popup {
            padding: 0 !important;
            border-radius: 28px !important
        }

        .swal2-html-container {
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important
        }

        .swal2-timer-progress-bar {
            height: 3px !important;
            border-radius: 0 !important
        }

        .swal2-backdrop-show {
            backdrop-filter: blur(8px) !important;
            background: rgba(209, 213, 219, .55) !important;
        }

        .sw-wrap .swal2-popup {
            width: 400px !important
        }

        .st-wrap .swal2-popup {
            width: 340px !important
        }

        body {
            background: #e8ecf0;
        }

        /* Set base background to match neo */

        /* ── Neo base surface ── */
        .neo-card {
            background: #e8ecf0;
            border-radius: 28px;
            overflow: hidden;
        }

        /* ── Inset box (recessed) ── */
        .neo-inset {
            background: #e8ecf0;
            border-radius: 14px;
            box-shadow: inset 3px 3px 7px #c8ccd0, inset -3px -3px 7px #ffffff;
        }

        /* ── Raised pill button ── */
        .neo-btn {
            border: none;
            cursor: pointer;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .02em;
            transition: all .18s cubic-bezier(.4, 0, .2, 1);
            position: relative;
            overflow: hidden;
        }

        .neo-btn:active {
            transform: scale(.97) !important
        }

        .neo-btn-primary {
            background: #e8ecf0;
            box-shadow: 5px 5px 12px #c2c6ca, -5px -5px 12px #ffffff;
        }

        .neo-btn-primary:hover {
            box-shadow: 3px 3px 8px #c2c6ca, -3px -3px 8px #ffffff;
        }

        .neo-btn-ghost {
            background: #e8ecf0;
            box-shadow: 3px 3px 8px #c2c6ca, -3px -3px 8px #ffffff;
        }

        .neo-btn-ghost:hover {
            box-shadow: 2px 2px 5px #c2c6ca, -2px -2px 5px #ffffff;
        }

        /* ── Icon orb ── */
        .neo-orb {
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .neo-orb-raised {
            box-shadow: 8px 8px 18px #c2c6ca, -8px -8px 18px #ffffff;
        }

        /* ── Divider ── */
        .neo-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #c8ccd0 30%, #c8ccd0 70%, transparent);
            margin: 0 4px;
        }

        /* ── Toast card ── */
        .neo-toast {
            background: #e8ecf0;
            border-radius: 18px;
            box-shadow: 8px 8px 20px #c2c6ca, -8px -8px 20px #ffffff;
            overflow: hidden;
        }
    </style>
</head>

<body class="font-sans antialiased text-gray-900">

    <!-- TOP NAVBAR (Disesuaikan agar menyatu dengan background Neomorphism) -->
    <nav class="bg-[#e8ecf0] shadow-[0_4px_10px_rgba(0,0,0,0.05)] border-b border-[#c8ccd0] mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Menu Kiri -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center font-black text-blue-700 text-xl tracking-wider">
                        PT JEMBO CABLE
                    </div>

                    <div class="hidden sm:-my-px sm:ml-8 sm:flex sm:space-x-8">
                        <a href="{{ url('/dashboard') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-bold transition-colors
              {{ request()->is('/dashboard') || request()->routeIs('dashboard')
                  ? 'border-blue-600 text-blue-700'
                  : 'border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600' }}">
                            Home
                        </a>
                        <a href="{{ route('katalog.index') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-bold transition-colors
                                  {{ request()->routeIs('katalog.index')
                                      ? 'border-blue-600 text-blue-700'
                                      : 'border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600' }}">
                            Master Data
                        </a>
                        <a href="{{ route('item.requests') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-bold transition-colors
          {{ request()->routeIs('item.requests')
              ? 'border-blue-600 text-blue-700'
              : 'border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600' }}">
                            Status Pengajuan Item Baru
                        </a>
                    </div>
                </div>

                <!-- Info User Kanan -->
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    @auth
                        <div class="flex items-center space-x-3">
                            <div class="text-right">
                                <div class="text-sm font-bold text-gray-900">{{ auth()->user()->name }}</div>
                                <div class="text-xs font-bold text-gray-500">
                                    {{ auth()->user()->dept->name ?? 'Departemen' }}</div>
                            </div>
                            <div
                                class="h-9 w-9 rounded-full bg-gradient-to-r from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- KONTEN UTAMA HALAMAN -->
    <main class="py-6 px-4">
        {{ $slot }}
    </main>

    <!-- LISTENER UNTUK SWEETALERT (Basic fallback) -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('swal', (event) => {
                const data = event[0];
                Swal.fire({
                    icon: data.icon,
                    title: data.title,
                    text: data.text,
                    background: '#e8ecf0',
                    confirmButtonColor: '#2563eb'
                });
            });
        });
    </script>
</body>

</html>
