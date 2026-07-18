<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KARSA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex">

    {{-- Kolom Kiri: Foto DP3AKB --}}
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">

        {{-- Foto background --}}
        <img
            src="{{ asset('images/dp3B.jpg') }}"
            alt="DP3AKB Kalimantan Selatan"
            class="absolute inset-0 w-full h-full object-cover"
        >

        {{-- Overlay gelap gradient --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/10"></div>

        {{-- Konten di atas foto --}}
        <div class="relative z-10 flex flex-col justify-between p-10 w-full">

            {{-- Logo & nama sistem di atas --}}
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white font-bold text-lg">
                    K
                </div>
                <div>
                    <p class="font-bold text-white text-lg leading-tight">KARSA</p>
                    <p class="text-blue-200 text-xs">Sistem Pengambilan Keputusan</p>
                </div>
            </div>

            {{-- Teks di bagian bawah foto --}}
            <div>
                <div class="mb-4">
                    <span class="inline-block rounded-full bg-blue-600/80 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                        DP3AKB Provinsi Kalimantan Selatan
                    </span>
                </div>
                <h2 class="text-3xl font-bold text-white leading-snug">
                    Sistem Pemetaan<br>Risiko Perceraian
                </h2>
                <p class="mt-3 text-slate-300 text-sm leading-relaxed max-w-sm">
                    Analisis dan visualisasi tingkat risiko perceraian berbasis
                    K-Means Clustering untuk mendukung kebijakan
                    Dinas Pemberdayaan Perempuan, Perlindungan Anak,
                    dan Keluarga Berencana Provinsi Kalimantan Selatan.
                </p>

                {{-- Statistik kecil --}}
                <div class="mt-6 flex gap-6">
                    <div>
                        <p class="text-2xl font-bold text-white">13</p>
                        <p class="text-xs text-slate-400">Kabupaten/Kota</p>
                    </div>
                    <div class="w-px bg-white/20"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">4</p>
                        <p class="text-xs text-slate-400">Cluster Risiko</p>
                    </div>
                    <div class="w-px bg-white/20"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">K-Means</p>
                        <p class="text-xs text-slate-400">Algoritma</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Kolom Kanan: Form Login --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-slate-950 px-6 py-12">

        <div class="w-full max-w-md">

            {{-- Header form --}}
            <div class="mb-8 text-center lg:text-left">
                {{-- Logo mobile (hanya muncul di layar kecil) --}}
                <div class="lg:hidden mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600">
                    <span class="text-2xl font-bold text-white">K</span>
                </div>
                <h1 class="text-2xl font-bold text-white">Selamat Datang</h1>
                <p class="mt-1 text-slate-400 text-sm">Masuk ke sistem KARSA DP3AKB</p>
            </div>

            {{-- Error --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-500/50 bg-red-900/30 px-4 py-3 text-sm text-red-400">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        {{ $errors->first() }}
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Masukkan email"
                        required
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2.5 text-white placeholder-slate-500 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Masukkan password"
                            required
                            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2.5 text-white placeholder-slate-500 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition pr-10"
                        >
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                            <svg id="eye-icon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Ingat saya --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember"
                            class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-blue-600 focus:ring-blue-500">
                        Ingat saya
                    </label>
                </div>

                {{-- Tombol Login --}}
                <button
                    type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition"
                >
                    Masuk ke Sistem
                </button>

            </form>

            {{-- Demo credentials --}}
            <!-- <div class="mt-8 rounded-lg border border-slate-800 bg-slate-900 p-4">
                <p class="text-xs font-semibold text-slate-400 mb-3">Akun Demo</p>
                <div class="space-y-2">
                    <button type="button"
                        onclick="fillDemo('admin@dp3akb.kalselprov.go.id')"
                        class="w-full text-left rounded-lg border border-slate-700 px-3 py-2 hover:border-blue-500 hover:bg-slate-800 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-white">Administrator</p>
                                <p class="text-xs text-slate-500">admin@dp3akb.kalselprov.go.id</p>
                            </div>
                            <span class="rounded-full bg-red-900/50 px-2 py-0.5 text-xs text-red-400">Admin</span>
                        </div>
                    </button>
                    <button type="button"
                        onclick="fillDemo('operator@dp3akb.kalselprov.go.id')"
                        class="w-full text-left rounded-lg border border-slate-700 px-3 py-2 hover:border-blue-500 hover:bg-slate-800 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-white">Operator</p>
                                <p class="text-xs text-slate-500">operator@dp3akb.kalselprov.go.id</p>
                            </div>
                            <span class="rounded-full bg-blue-900/50 px-2 py-0.5 text-xs text-blue-400">Operator</span>
                        </div>
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-600">Password: <span class="text-slate-500">password</span></p>
            </div> -->

            {{-- Footer --}}
            <p class="mt-8 text-center text-xs text-slate-600">
                © {{ date('Y') }} DP3AKB Kalimantan Selatan. All rights reserved.
            </p>

        </div>
    </div>

</body>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function fillDemo(email) {
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="password"]').value = 'password';
}
</script>

</html>