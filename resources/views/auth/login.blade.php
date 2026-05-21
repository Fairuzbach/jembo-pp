<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div class="min-h-screen flex items-center justify-center bg-[#f0f3f6] px-4 font-sans">

        <div
            class="w-full max-w-md p-8 rounded-[40px] bg-[#f0f3f6] shadow-[15px_15px_30px_#d1d9e6,-15px_-15px_30px_#ffffff] border border-white/50">

            <div class="flex justify-center mb-8">
                <div
                    class="w-24 h-24 rounded-full bg-[#f0f3f6] shadow-[inset_8px_8px_16px_#d1d9e6,inset_-8px_-8px_16px_#ffffff] flex items-center justify-center p-4 border border-white/40">
                    <img src="{{ asset('images/logo jembo.webp') }}" alt="Jembo Logo" class="w-full">
                </div>
            </div>

            <div class="text-center mb-10">
                <h1 class="text-2xl font-extrabold text-[#003882] tracking-tight drop-shadow-sm">e-Procurement</h1>
                <p class="text-sm text-gray-500 mt-1 font-bold">Silakan login dengan NIK Anda</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-100 shadow-inner text-center">
                    <span class="text-xs font-bold text-[#e30613]">{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest ml-5 mb-2">Nomor
                        Induk Karyawan</label>
                    <input type="text" name="nik" value="{{ old('nik') }}" required autofocus
                        class="w-full px-6 py-4 rounded-full bg-[#f0f3f6] border-none shadow-[inset_6px_6px_12px_#d1d9e6,inset_-6px_-6px_12px_#ffffff] focus:shadow-[inset_8px_8px_16px_#d1d9e6,inset_-8px_-8px_16px_#ffffff] focus:ring-0 outline-none text-[#003882] font-black placeholder-gray-400 transition-all"
                        placeholder="Masukkan NIK">
                    @error('nik')
                        <div class="text-[#e30613] text-[10px] font-bold mt-2 ml-5">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label
                        class="block text-[11px] font-black text-gray-400 uppercase tracking-widest ml-5 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-6 py-4 rounded-full bg-[#f0f3f6] border-none shadow-[inset_6px_6px_12px_#d1d9e6,inset_-6px_-6px_12px_#ffffff] focus:shadow-[inset_8px_8px_16px_#d1d9e6,inset_-8px_-8px_16px_#ffffff] focus:ring-0 outline-none text-[#003882] font-black placeholder-gray-400 transition-all"
                        placeholder="••••••••">
                    @error('password')
                        <div class="text-[#e30613] text-[10px] font-bold mt-2 ml-5">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center justify-between ml-5 mr-2">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                        <input id="remember_me" type="checkbox" name="remember" class="peer sr-only">
                        <div
                            class="w-5 h-5 rounded bg-[#f0f3f6] shadow-[inset_2px_2px_4px_#d1d9e6,inset_-2px_-2px_4px_#ffffff] border border-white/50 peer-checked:bg-[#003882] peer-checked:shadow-[inset_2px_2px_4px_rgba(0,0,0,0.4)] flex items-center justify-center transition-all">
                            <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span
                            class="ml-3 text-sm font-bold text-gray-500 group-hover:text-[#003882] transition-colors">Ingat
                            Saya</span>
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full py-4 rounded-full bg-[#f0f3f6] text-[#003882] font-black tracking-widest shadow-[8px_8px_16px_#d1d9e6,-8px_-8px_16px_#ffffff] hover:shadow-[inset_6px_6px_12px_#d1d9e6,inset_-6px_-6px_12px_#ffffff] border border-white/50 transition-all active:scale-95 flex justify-center items-center">
                        LOGIN SEKARANG
                    </button>
                </div>
            </form>

            <div class="mt-10 text-center">
                <p class="text-[10px] text-gray-400 font-bold tracking-[0.2em] uppercase">PT Jembo Cable Company Tbk</p>
            </div>
        </div>
    </div>
</body>

</html>
