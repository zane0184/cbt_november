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
    @php
        $u = auth()->user();
        $roles = $u ? $u->roles()->pluck('name')->all() : [];
        $isAdmin = count($roles) === 1 && $roles[0] === 'ADMIN_MADRASAH';
        $isGuru = count($roles) === 1 && $roles[0] === 'GURU';
        $hasTemporaryProctorDuty = $isGuru && \App\Models\ExamProctorAssignment::query()
            ->activeNow()->where('tenant_id', $u->tenant_id)->where('proctor_user_id', $u->id)->exists();
    @endphp

    <!-- Desktop portal sidebar -->
    <aside class="hidden h-screen w-64 shrink-0 flex-col justify-between overflow-y-auto border-r border-navy-800 bg-navy-900 text-white md:sticky md:top-0 md:flex">
        <div>
            <div class="flex items-center gap-3 border-b border-navy-800 px-6 py-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-xl font-bold text-white">C</div>
                <div>
                    <h1 class="text-base font-bold leading-tight">CBT MADRASAH</h1>
                    <p class="text-xs text-gray-400">{{ $isAdmin ? 'Panel Admin Madrasah' : ($isGuru ? 'Portal Guru' : 'Portal CBT') }}</p>
                </div>
            </div>

            <nav aria-label="Navigasi portal" class="space-y-1.5 p-4 text-sm font-medium">
                @if($isAdmin)
                    <p class="px-4 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Administrasi</p>
                    <a href="/admin/dashboard" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/dashboard') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">▦</span><span>Dashboard Admin</span>
                    </a>
                    @if($u->hasPermission('academic.manage'))
                        <a href="/admin/academic" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/academic*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span aria-hidden="true">▤</span><span>Data Akademik & Rombel</span>
                        </a>
                        <a href="/admin/teacher-assignments" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/teacher-assignments*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span aria-hidden="true">♧</span><span>Penugasan Guru</span>
                        </a>
                        <a href="/admin/rombel-students" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/rombel-students*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span aria-hidden="true">♧</span><span>Keanggotaan Rombel</span>
                        </a>
                    @endif
                    @if($u->hasPermission('user.manage'))
                        <a href="/admin/users" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/users*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span aria-hidden="true">♙</span><span>Pengguna & Akun</span>
                        </a>
                    @endif
                    @if($u->hasPermission('curriculum.registry.manage'))
                        <a href="/admin/curriculum/registry" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/curriculum/registry*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span aria-hidden="true">▧</span><span>Registry Kurikulum</span>
                        </a>
                    @endif
                    @if($u->hasPermission('exam.proctor.assign'))
                        <a href="/admin/proctor-assignments" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('admin/proctor-assignments*') ? 'border-l-4 border-amber-500 bg-navy-800 font-bold text-white' : '' }}">
                            <span aria-hidden="true">◷</span><span>Penugasan Guru Pengawas</span>
                        </a>
                    @endif
                @elseif($isGuru)
                    <p class="px-4 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Pembelajaran & Asesmen</p>
                    <a href="/guru/dashboard" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('guru/dashboard') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">▦</span><span>Dashboard Guru</span>
                    </a>
                    <a href="/guru/penugasan" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('guru/penugasan*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">▤</span><span>Penugasan Mengajar</span>
                    </a>
                    <a href="/guru/pembelajaran" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('guru/pembelajaran*') || request()->is('guru/kurikulum*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">◫</span><span>Pembelajaran & Kurikulum</span>
                    </a>
                    <a href="/guru/bank-soal" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('guru/bank-soal*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">▧</span><span>Bank Soal</span>
                    </a>
                    <a href="/guru/asesmen" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('guru/asesmen*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">◷</span><span>Asesmen & Ujian</span>
                    </a>
                    @if($hasTemporaryProctorDuty)
                        <div class="mt-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-xs text-amber-100" role="status">
                            <p class="font-bold">Tugas pengawas sementara aktif</p>
                            <p class="mt-1 text-amber-100/80">Tugas operasional berada di dalam penugasan Guru. Tidak ada portal Proktor permanen.</p>
                        </div>
                    @endif
                    <a href="/guru/profil" class="mt-3 flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-300 transition hover:bg-navy-800 hover:text-white {{ request()->is('guru/profil*') ? 'border-l-4 border-emerald-500 bg-navy-800 font-bold text-white' : '' }}">
                        <span aria-hidden="true">♙</span><span>Profil Guru</span>
                    </a>
                @endif
            </nav>
        </div>

        <div class="border-t border-navy-800 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-navy-800 p-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600/20 text-sm font-bold text-emerald-400">{{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}</div>
                <div class="min-w-0 truncate text-xs">
                    <p class="truncate font-bold text-white">{{ $u->name ?? 'Pengguna' }}</p>
                    <p class="truncate text-gray-400">{{ $u->email ?? 'user@madrasah.id' }}</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile portal navigation -->
    <div class="fixed left-0 right-0 top-0 z-40 border-b border-cardBorder bg-white/95 px-4 py-2 shadow-sm backdrop-blur md:hidden">
        <details class="group">
            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-2 py-2 text-sm font-bold text-navy-900 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <span>{{ $isAdmin ? 'Menu Admin Madrasah' : ($isGuru ? 'Menu Guru' : 'Menu CBT Madrasah') }}</span>
                <span aria-hidden="true" class="transition group-open:rotate-180">⌄</span>
            </summary>
            <nav aria-label="Navigasi mobile" class="max-h-[70vh] space-y-1 overflow-y-auto border-t border-cardBorder py-3 text-sm">
                @if($isAdmin)
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/dashboard">Dashboard Admin</a>
                    @if($u->hasPermission('academic.manage'))
                        <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/academic">Data Akademik & Rombel</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/teacher-assignments">Penugasan Guru</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/rombel-students">Keanggotaan Rombel</a>
                    @endif
                    @if($u->hasPermission('user.manage'))
                        <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/users">Pengguna & Akun</a>
                    @endif
                    @if($u->hasPermission('curriculum.registry.manage'))
                        <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/curriculum/registry">Registry Kurikulum</a>
                    @endif
                    @if($u->hasPermission('exam.proctor.assign'))
                        <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/admin/proctor-assignments">Penugasan Guru Pengawas</a>
                    @endif
                @elseif($isGuru)
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/guru/dashboard">Dashboard Guru</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/guru/penugasan">Penugasan Mengajar</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/guru/pembelajaran">Pembelajaran & Kurikulum</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/guru/bank-soal">Bank Soal</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/guru/asesmen">Asesmen & Ujian</a>
                    <a class="block rounded-lg px-3 py-2 hover:bg-contentBg" href="/guru/profil">Profil Guru</a>
                @endif
            </nav>
        </details>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <header class="mt-14 flex items-center justify-between border-b border-cardBorder bg-white px-6 py-4 shadow-sm md:mt-0">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-navy-900">{{ $isAdmin ? 'Panel Admin Madrasah' : ($isGuru ? 'Portal Guru' : 'CBT Madrasah') }}</h2>
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

    <div id="app-toast" role="status" aria-live="polite" hidden class="fixed bottom-5 right-5 z-50 max-w-sm rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-semibold text-navy-900 shadow-xl transition duration-200"></div>
    @livewireScripts
    <script>
        window.addEventListener('notify', event => {
            const payload = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            const toast = document.getElementById('app-toast');
            if (!toast || !payload?.message) return;
            toast.textContent = payload.message;
            toast.hidden = false;
            toast.classList.remove('opacity-0', 'translate-y-2');
            window.clearTimeout(window.__cbtToastTimer);
            window.__cbtToastTimer = window.setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                window.setTimeout(() => { toast.hidden = true; }, 250);
            }, 3200);
        });
    </script>
</body>
</html>
