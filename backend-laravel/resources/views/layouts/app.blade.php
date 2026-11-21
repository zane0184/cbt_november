<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CBT Madrasah') }} - Modern EdTech Admin</title>
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
    </style>
    @livewireStyles
</head>
<body class="bg-contentBg text-textPrimary antialiased flex min-h-screen">
    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-navy-900 text-white flex flex-col justify-between hidden md:flex shrink-0">
        <div>
            <!-- Brand Header -->
            <div class="px-6 py-5 border-b border-navy-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl shadow-md">
                    C
                </div>
                <div>
                    <h1 class="text-base font-bold leading-tight">CBT MADRASAH</h1>
                    <p class="text-xs text-gray-400">Panel Admin & Guru</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1.5 text-sm font-medium">
                @php
                    $u = auth()->user();
                @endphp

                <!-- Dashboards Per Role -->
                @if($u && ($u->hasRole('ADMIN_MADRASAH') && !$u->hasRole('ADMIN') && !$u->hasRole('OPERATOR')))
                    <a href="/operator/dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('operator/dashboard') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span>Dashboard Operator</span>
                    </a>
                @endif

                @if($u && $u->hasRole('PROKTOR'))
                    <a href="/proktor/assignments" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('proktor/assignments') ? 'bg-navy-800 text-white font-bold border-l-4 border-amber-500' : '' }}">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>Penugasan Proktor</span>
                    </a>
                @endif

                <div class="pt-3 pb-1 px-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Modul Operasional</div>

                @if($u && $u->hasRole('ADMIN_MADRASAH') && $u->hasPermission('exam.proctor.assign'))
                    <a href="/admin/proctor-assignments" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/proctor-assignments*') ? 'bg-navy-800 text-white font-bold border-l-4 border-amber-500' : '' }}">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Penugasan Proktor</span>
                    </a>
                @endif

                @if($u && $u->hasRole('GURU') && !$u->hasRole('ADMIN_MADRASAH'))
                    <a href="/admin/exams" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/exams*') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Manajemen Ujian</span>
                    </a>
                @endif

                @if($u && $u->hasRole('GURU') && !$u->hasRole('ADMIN_MADRASAH') && $u->hasPermission('question.view'))
                    <a href="/admin/questions" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/questions*') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 20H3V15M21 4h-5V9M16 4l5 5M8 20l-5-5M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/></svg>
                        <span>Bank Soal</span>
                    </a>
                @endif

                @if($u && ($u->hasRole('ADMIN_MADRASAH') && !$u->hasRole('ADMIN') && !$u->hasRole('OPERATOR')) || ($u->hasRole('GURU') && !$u->hasRole('ADMIN_MADRASAH')) && $u->hasPermission('curriculum.view'))
                    <a href="/admin/curriculum" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/curriculum*') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span>Kurikulum (CP/TP)</span>
                    </a>
                @endif

                @if($u && $u->hasRole('ADMIN_MADRASAH') && $u->hasPermission('academic.manage'))
                    <a href="/admin/academic" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/academic*') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Data Akademik & Rombel</span>
                    </a>
                @endif

                @if($u && $u->hasRole('ADMIN_MADRASAH') && $u->hasPermission('academic.manage'))
                    <a href="/admin/rombel-students" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/rombel-students*') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.4-1.8M17 20H7m10 0v-2c0-.7-.14-1.36-.4-1.95M7 20H2v-2a3 3 0 015.4-1.8M7 20v-2c0-.7.14-1.36.4-1.95m0 0a5 5 0 019.2 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Keanggotaan Rombel</span>
                    </a>
                @endif

                @if($u && $u->hasRole('ADMIN_MADRASAH') && $u->hasPermission('user.manage'))
                    <a href="/admin/users" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-navy-800 hover:text-white transition {{ request()->is('admin/users*') ? 'bg-navy-800 text-white font-bold border-l-4 border-emerald-500' : '' }}">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Pengguna & Akun</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-navy-800">
            <div class="bg-navy-800 rounded-xl p-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-600/20 text-emerald-500 flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="truncate text-xs">
                    <p class="font-bold text-white truncate">{{ auth()->user()->name ?? 'Pengguna' }}</p>
                    <p class="text-gray-400 truncate">{{ auth()->user()->email ?? 'user@madrasah.id' }}</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <header class="bg-white border-b border-cardBorder px-6 py-4 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-navy-900">Asesmen Digital Madrasah</h2>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm font-semibold text-textSecondary bg-gray-100 px-3 py-1.5 rounded-full border border-cardBorder">
                    {{ auth()->user()->name ?? 'Pengguna' }}
                </span>
                <form action="/auth/logout" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg border border-red-200 transition">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-6 md:p-8 overflow-y-auto">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    <script>
        window.addEventListener('notify', event => {
            alert(event.detail[0].message);
        });
    </script>
</body>
</html>
