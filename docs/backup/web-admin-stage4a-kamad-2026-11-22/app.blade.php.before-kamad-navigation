<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CBT Madrasah') }} - Panel Admin Madrasah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#E6F4F1',
                            100: '#D0EAE4',
                            500: '#10B981',
                            600: '#0F8B72',
                            700: '#0B6B58',
                            900: '#053B30',
                        },
                        navy: {
                            800: '#1E3452',
                            900: '#14243A',
                            950: '#0D1827',
                        },
                        contentBg: '#F7F9FB',
                        cardBorder: '#E4E9EF',
                        textPrimary: '#172536',
                        textSecondary: '#68778A',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: #52647b #14243a; }
    </style>
    @livewireStyles
</head>
<body class="min-h-screen bg-contentBg text-textPrimary antialiased">
    @php
        $currentUser = auth()->user();
        $isAdminMadrasah = $currentUser && $currentUser->hasRole('ADMIN_MADRASAH');
        $isTeacher = $currentUser && $currentUser->hasRole('GURU') && !$isAdminMadrasah;
    @endphp

    <div class="flex min-h-screen">
        <aside class="sidebar-scroll sticky top-0 hidden h-screen w-64 shrink-0 flex-col overflow-y-auto overscroll-contain bg-navy-900 text-white md:flex">
            <div class="sticky top-0 z-10 border-b border-navy-800 bg-navy-900 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-xl font-bold text-white shadow-md">C</div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-bold leading-tight">CBT MADRASAH</h1>
                        <p class="mt-0.5 truncate text-xs text-gray-400">
                            {{ $isAdminMadrasah ? 'Panel Admin Madrasah' : ($isTeacher ? 'Portal Guru' : 'Madrasah') }}
                        </p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1.5 p-4 text-sm font-medium">
                @if($isAdminMadrasah)
                    <p class="px-4 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Administrasi Madrasah</p>
                    <a href="/admin/dashboard" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/dashboard') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span class="text-emerald-400">▦</span><span>Dashboard Eksekutif</span>
                    </a>
                    <a href="/admin/profile" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/profile*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span class="text-emerald-400">⌂</span><span>Profil Madrasah</span>
                    </a>
                    @if($currentUser->hasPermission('user.manage'))
                        <a href="/admin/users" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/users*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">♙</span><span>Guru & Siswa</span>
                        </a>
                        <a href="/admin/import" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/import*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">⇧</span><span>Import Data</span>
                        </a>
                    @endif
                    @if($currentUser->hasPermission('academic.manage'))
                        <a href="/admin/academic" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/academic*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">▤</span><span>Data Akademik</span>
                        </a>
                        <a href="/admin/rombel-students" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/rombel-students*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">♧</span><span>Keanggotaan Rombel</span>
                        </a>
                    @endif
                    @if($currentUser->hasPermission('curriculum.view'))
                        <a href="/admin/curriculum" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/curriculum*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">▧</span><span>Master Kurikulum</span>
                        </a>
                    @endif
                    @if($currentUser->hasPermission('exam.proctor.assign'))
                        <a href="/admin/proctor-assignments" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/proctor-assignments*') ? 'border-l-4 border-amber-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-amber-400">◷</span><span>Penugasan Pengawas</span>
                        </a>
                    @endif
                @elseif($isTeacher)
                    <p class="px-4 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Ruang Kerja Guru</p>
                    <a href="/admin/exams" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/exams*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span class="text-emerald-400">▤</span><span>Manajemen Ujian</span>
                    </a>
                    @if($currentUser->hasPermission('question.view'))
                        <a href="/admin/questions" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/questions*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">▧</span><span>Bank Soal</span>
                        </a>
                    @endif
                    @if($currentUser->hasPermission('curriculum.view'))
                        <a href="/admin/curriculum" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/curriculum*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span class="text-emerald-400">▧</span><span>Referensi Kurikulum</span>
                        </a>
                    @endif
                @endif
            </nav>

            <div class="border-t border-navy-800 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-navy-800 p-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600/20 text-sm font-bold text-emerald-400">
                        {{ strtoupper(substr($currentUser->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 text-xs">
                        <p class="truncate font-bold text-white">{{ $currentUser->name ?? 'Pengguna' }}</p>
                        <p class="truncate text-gray-400">{{ $currentUser->email ?? 'Akun Madrasah' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-cardBorder bg-white px-4 py-3 shadow-sm md:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <details class="relative md:hidden">
                        <summary class="cursor-pointer list-none rounded-lg border border-cardBorder px-3 py-2 text-sm font-semibold text-navy-900 focus:outline-none focus:ring-2 focus:ring-emerald-500">☰ Menu</summary>
                        <div class="sidebar-scroll absolute left-0 top-full z-30 mt-2 max-h-[70vh] w-72 overflow-y-auto rounded-xl border border-cardBorder bg-navy-900 p-3 text-sm text-white shadow-xl">
                            @if($isAdminMadrasah)
                                <a href="/admin/dashboard" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Dashboard Eksekutif</a>
                                <a href="/admin/profile" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Profil Madrasah</a>
                                @if($currentUser->hasPermission('user.manage'))<a href="/admin/users" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Guru & Siswa</a>@endif
                                @if($currentUser->hasPermission('academic.manage'))
                                    <a href="/admin/academic" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Data Akademik</a>
                                    <a href="/admin/rombel-students" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Keanggotaan Rombel</a>
                                @endif
                                @if($currentUser->hasPermission('curriculum.view'))<a href="/admin/curriculum" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Master Kurikulum</a>@endif
                                @if($currentUser->hasPermission('exam.proctor.assign'))<a href="/admin/proctor-assignments" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Penugasan Pengawas</a>@endif
                            @elseif($isTeacher)
                                <a href="/admin/exams" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Manajemen Ujian</a>
                                @if($currentUser->hasPermission('question.view'))<a href="/admin/questions" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Bank Soal</a>@endif
                                @if($currentUser->hasPermission('curriculum.view'))<a href="/admin/curriculum" class="block rounded-lg px-3 py-2.5 hover:bg-navy-800">Referensi Kurikulum</a>@endif
                            @endif
                        </div>
                    </details>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold text-navy-900 md:text-lg">{{ $isAdminMadrasah ? 'Panel Admin Madrasah' : ($isTeacher ? 'Portal Guru' : 'CBT Madrasah') }}</h2>
                        <p class="hidden text-xs text-textSecondary sm:block">Administrasi dan layanan akademik madrasah</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 md:gap-4">
                    <span class="hidden max-w-48 truncate rounded-full border border-cardBorder bg-gray-100 px-3 py-1.5 text-xs font-semibold text-textSecondary sm:inline-block">{{ $currentUser->name ?? 'Pengguna' }}</span>
                    <form action="/auth/logout" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100 md:text-sm">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="min-w-0 flex-1 overflow-y-auto p-4 md:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    <script>
        window.addEventListener('notify', event => {
            const message = event.detail?.[0]?.message ?? 'Perubahan berhasil diproses.';
            window.alert(message);
        });
    </script>
</body>
</html>
