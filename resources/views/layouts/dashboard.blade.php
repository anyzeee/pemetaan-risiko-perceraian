<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'KARSA')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
@php
    use Illuminate\Support\Facades\Auth;
    $user    = Auth::user();
    $isAdmin = $user?->role === 'admin';
@endphp

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="w-64 flex-shrink-0 border-r border-slate-200 bg-white">
            <div class="flex h-16 items-center gap-2 border-b border-slate-200 px-6">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white font-bold text-sm">K</div>
                <span class="font-bold text-slate-900">KARSA</span>
            </div>
            <nav class="space-y-1 p-4">

                {{-- Menu tersedia untuk SEMUA role --}}
                <a href="{{ route('dashboard') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('data-management.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('data-management.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                    Manajemen Data
                </a>
                <a href="{{ route('risk-mapping.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('risk-mapping.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                    Pemetaan Risiko
                </a>
                <a href="{{ route('clustering-analysis.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('clustering-analysis.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                    Clustering
                </a>
                <a href="{{ route('reports.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('reports.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                    Laporan
                </a>
                <a href="{{ route('profile.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('profile.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                    Profil
                </a>

                {{-- Menu KHUSUS Admin --}}
                @if ($isAdmin)
                    <div class="pt-2">
                        <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Admin</p>
                        <a href="{{ route('clustering-analysis.results') }}"
                           class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('clustering-analysis.results') ? 'bg-blue-50 text-blue-700' : '' }}">
                            Hasil Clustering
                        </a>
                        <a href="{{ route('recommendation.index') }}"
                           class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('recommendation.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                            Strategi Media
                        </a>
                    </div>
                @endif

            </nav>
        </aside>

        {{-- Right side: navbar + content --}}
        <div class="flex flex-1 flex-col">

            {{-- Top Navbar --}}
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6">
                <p class="text-sm font-medium text-slate-500">@yield('title', 'KARSA')</p>
                <div class="flex items-center gap-4">

                    {{-- Role Badge --}}
                    @if ($isAdmin)
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                            Administrator
                        </span>
                    @else
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            Operator
                        </span>
                    @endif

                    {{-- User Info --}}
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full text-white font-bold text-sm {{ $isAdmin ? 'bg-red-500' : 'bg-blue-500' }}">
                            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="text-sm">
                            <p class="font-semibold text-slate-900">{{ $user?->name ?? 'User' }}</p>
                            <p class="text-xs text-slate-500">{{ $user?->email ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Keluar
                        </button>
                    </form>

                </div>
            </header>

            {{-- Main content --}}
            <main class="flex-1 p-6">
                @yield('content')
            </main>

        </div>

    </div>

</body>
</html>