@extends('layouts.dashboard')

@section('content')

@php
    $user = Auth::user();
    $role = strtolower($user->role ?? 'operator');

    $badgeColor = $role == 'admin'
        ? 'bg-red-100 text-red-700'
        : 'bg-blue-100 text-blue-700';

    $roleName = $role == 'admin'
        ? 'Administrator'
        : 'Operator';
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-slate-900">
            Profil Pengguna
        </h1>

        <p class="mt-2 text-slate-600">
            Kelola informasi akun dan pengaturan keamanan
        </p>
    </div>



<div class="grid gap-6 lg:grid-cols-3">




    {{-- Profile Card --}}
    <div class="rounded-lg border border-slate-200 bg-white p-6 lg:col-span-1">

        <div class="space-y-6">

            <div class="flex flex-col items-center">

                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-blue-100">

                    <span class="text-3xl font-bold text-blue-600">

                        {{ strtoupper(substr(Auth::user()->name,0,1)) }}

                    </span>

                </div>

                <h2 class="mt-4 text-center text-xl font-bold text-slate-900">

                    {{ Auth::user()->name }}

                </h2>

                <p class="text-center text-sm text-slate-500">

                    {{ ucfirst(Auth::user()->role) }}

                </p>

            </div>



            <div class="border-t pt-4 space-y-3">

                <div>

                    <p class="text-xs font-semibold text-slate-500">

                        AKSES SISTEM

                    </p>

                    <p class="mt-1 text-sm font-medium">

                        {{ ucfirst(Auth::user()->role) }}

                    </p>

                </div>



                <div>

                    <p class="text-xs font-semibold text-slate-500">

                        LOGIN TERAKHIR

                    </p>

                    <p class="mt-1 text-sm">

                        {{ now()->translatedFormat('l, d F Y') }}

                    </p>

                </div>

            </div>

        </div>

    </div>







    <div class="space-y-6 lg:col-span-2">



        {{-- Informasi Akun --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">

            <div class="mb-6 flex items-center justify-between">

                <h3 class="text-lg font-semibold">

                    Informasi Akun

                </h3>

            </div>



<form action="{{ route('profile.update') }}" method="POST">

@csrf
@method('PUT')



<div class="space-y-4">



<div>

<label class="block text-sm font-medium">

Username

</label>

<input

type="text"

class="mt-1 block w-full rounded-md border border-slate-300 bg-slate-100 px-3 py-2"

value="{{ Auth::user()->username }}"

readonly>

</div>





<div>

<label class="block text-sm font-medium">

Email

</label>

<input

type="email"

name="email"

class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"

value="{{ Auth::user()->email }}">

</div>





<div>

<label class="block text-sm font-medium">

Nama Lengkap

</label>

<input

type="text"

name="name"

class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"

value="{{ Auth::user()->name }}">

</div>





<div>

<label class="block text-sm font-medium">

Departemen

</label>

<input

type="text"

name="department"

class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"

value="{{ Auth::user()->department }}">

</div>





<div class="border-t pt-4">

<button

class="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700">

Simpan Perubahan

</button>

</div>

</div>

</form>

</div>

        {{-- Keamanan --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">

                     <h3 class="mb-6 text-lg font-semibold text-slate-900">
                Keamanan
            </h3>

            <form action="{{ route('profile.password') }}" method="POST">

                @csrf

                <div class="space-y-4">

                    <div>

                        <label class="block text-sm font-medium text-slate-700">
                            Password Baru
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"
                            placeholder="Minimal 8 karakter">

                    </div>



                    <div>

                        <label class="block text-sm font-medium text-slate-700">
                            Konfirmasi Password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"
                            placeholder="Konfirmasi Password">

                    </div>



                    <div class="border-t border-slate-200 pt-4">

                        <button
                            class="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700">

                            Simpan Password

                        </button>

                    </div>

                </div>

            </form>

        </div>





        {{-- Activity Log --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6">

            <h3 class="mb-6 text-lg font-semibold text-slate-900">

                Log Aktivitas Terakhir

            </h3>



            <div class="space-y-3">

                <div class="flex items-center justify-between border-b pb-3">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">

                            ✓

                        </div>

                        <span>

                            Login ke Sistem

                        </span>

                    </div>

                    <span class="text-xs text-slate-500">

                        Hari Ini

                    </span>

                </div>





                <div class="flex items-center justify-between border-b pb-3">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">

                            ✓

                        </div>

                        <span>

                            Membuka Dashboard

                        </span>

                    </div>

                    <span class="text-xs text-slate-500">

                        Hari Ini

                    </span>

                </div>





                <div class="flex items-center justify-between border-b pb-3">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">

                            ✓

                        </div>

                        <span>

                            Mengakses Data Management

                        </span>

                    </div>

                    <span class="text-xs text-slate-500">

                        Kemarin

                    </span>

                </div>





                <div class="flex items-center justify-between border-b pb-3">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">

                            ✓

                        </div>

                        <span>

                            Membuat Laporan

                        </span>

                    </div>

                    <span class="text-xs text-slate-500">

                        2 Hari Yang Lalu

                    </span>

                </div>





                <div class="flex items-center justify-between">

                    <div class="flex items-center gap-3">

                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">

                            ✓

                        </div>

                        <span>

                            Login ke Sistem

                        </span>

                    </div>

                    <span class="text-xs text-slate-500">

                        3 Hari Yang Lalu

                    </span>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

@endsection  