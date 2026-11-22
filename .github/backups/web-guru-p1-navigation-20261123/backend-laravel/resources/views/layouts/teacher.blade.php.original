<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ config('app.name', 'CBT Madrasah') }} — Portal Guru</title>
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
    <style>
        :root {
            color-scheme: light dark;
            --page: #f4f7f6;
            --surface: #ffffff;
            --surface-soft: #edf4f1;
            --text: #172b25;
            --muted: #62736d;
            --border: #dce7e1;
            --nav: #103b30;
            --nav-soft: #194d3e;
            --accent: #0b8063;
            --accent-strong: #08664f;
            --accent-text: #ffffff;
            --warning-bg: #fff8e6;
            --warning-text: #805b16;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --page: #101a17;
                --surface: #182622;
                --surface-soft: #20332d;
                --text: #e6f1ec;
                --muted: #a7bbb1;
                --border: #31483e;
                --nav: #0b211b;
                --nav-soft: #17372c;
                --accent: #38b58d;
                --accent-strong: #55c9a0;
                --accent-text: #08251c;
                --warning-bg: #3a301d;
                --warning-text: #f1d18a;
            }
        }
        html, body { min-height: 100%; }
        body { margin: 0; background: var(--page); color: var(--text); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .portal-card { background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; }
        .portal-soft { background: var(--surface-soft); }
        .portal-muted { color: var(--muted); }
        .portal-border { border-color: var(--border); }
        .portal-nav { background: var(--nav); color: #edf8f3; }
        .portal-nav-link { display:flex; align-items:center; gap:.75rem; border-radius:.75rem; padding:.7rem .85rem; color:#d3e6dd; transition:background .15s ease; }
        .portal-nav-link:hover, .portal-nav-link.active { background:var(--nav-soft); color:#fff; }
        .portal-nav-link.active { box-shadow:inset 3px 0 0 var(--accent); font-weight:700; }
        .portal-disabled { display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.7rem .85rem; border-radius:.75rem; color:#93aaa0; cursor:not-allowed; }
        .portal-input { width:100%; border:1px solid var(--border); border-radius:.7rem; padding:.7rem .85rem; background:var(--surface); color:var(--text); }
        .portal-button { display:inline-flex; align-items:center; justify-content:center; border-radius:.7rem; padding:.7rem 1rem; background:var(--accent); color:var(--accent-text); font-weight:700; }
        .portal-button:hover { background:var(--accent-strong); }
        .portal-badge { display:inline-flex; align-items:center; border-radius:999px; padding:.2rem .6rem; background:var(--surface-soft); color:var(--text); font-size:.75rem; font-weight:700; }
        .portal-warning { border:1px solid var(--border); background:var(--warning-bg); color:var(--warning-text); border-radius:.85rem; padding:.85rem 1rem; }
        .portal-table th { color:var(--muted); font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .portal-table th, .portal-table td { border-bottom:1px solid var(--border); padding:.85rem .75rem; text-align:left; vertical-align:top; }
        summary { list-style:none; }
        summary::-webkit-details-marker { display:none; }
    </style>
    @livewireStyles
</head>
<body>
    @php($currentUser = auth()->user())
    <div class="min-h-screen md:flex">
        <aside class="portal-nav hidden w-72 shrink-0 flex-col md:fixed md:inset-y-0 md:flex">
            <div class="border-b border-white/10 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 text-lg font-black text-white">C</div>
                    <div>
                        <p class="text-sm font-extrabold tracking-wide">CBT MADRASAH</p>
                        <p class="mt-1 text-xs text-emerald-100/70">Portal Guru</p>
                    </div>
                </div>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5 text-sm">
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-emerald-100/50">Ruang Kerja Guru</p>
                <a href="{{ route('teacher.dashboard') }}" class="portal-nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}"><span>▦</span> Dashboard</a>
                <a href="{{ route('teacher.assignments') }}" class="portal-nav-link {{ request()->routeIs('teacher.assignments') ? 'active' : '' }}"><span>▤</span> Penugasan Saya</a>
                <a href="{{ route('teacher.learning') }}" class="portal-nav-link {{ request()->routeIs('teacher.learning') ? 'active' : '' }}"><span>▤</span> Pembelajaran</a>
                <a href="{{ route('teacher.curriculum') }}#kurikulum" class="portal-nav-link {{ request()->routeIs('teacher.curriculum') ? 'active' : '' }}"><span>◈</span> Kurikulum & Akademik</a>
                <div class="my-3 border-t border-white/10"></div>
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-emerald-100/50">Perangkat Asesmen</p>
                <a href="{{ route('teacher.questions.index') }}" class="portal-nav-link {{ request()->routeIs('teacher.questions.index') ? 'active' : '' }}"><span>▧</span> Bank Soal</a>
                <a href="{{ route('teacher.questions.template') }}" class="portal-nav-link pl-6 text-xs"><span>⇩</span> Unduh Template Import Soal</a>
                <a href="{{ route('teacher.exams.index') }}" class="portal-nav-link {{ request()->routeIs('teacher.exams.index') ? 'active' : '' }}"><span>▣</span> Asesmen & Ujian</a>
                <div class="my-3 border-t border-white/10"></div>
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-emerald-100/50">Akses per ujian / tahap berikutnya</p>
                <div class="portal-disabled" aria-disabled="true" title="Tautan monitoring tersedia dari ujian yang diizinkan"><span>Monitoring Ujian</span><span class="text-[10px] uppercase tracking-wide">Per ujian</span></div>
                <div class="portal-disabled" aria-disabled="true" title="Penilaian dan hasil dibuka dari ujian setelah pemeriksaan policy"><span>Penilaian & Hasil</span><span class="text-[10px] uppercase tracking-wide">Per ujian</span></div>
                <div class="portal-disabled" aria-disabled="true" title="Analitik dan ekspor dibuka dari ujian setelah pemeriksaan policy"><span>Analytics & Ekspor</span><span class="text-[10px] uppercase tracking-wide">Per ujian</span></div>
                <p class="px-3 py-2 text-[11px] leading-relaxed text-emerald-100/60">Pengawasan asesmen mandiri mengikuti lingkup Guru pembuat. Pengawasan ujian terpusat hanya tersedia melalui penugasan Proktor yang sah.</p>
                <div class="my-3 border-t border-white/10"></div>
                <a href="{{ route('teacher.profile') }}" class="portal-nav-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}"><span>♙</span> Profil Guru</a>
                <div class="portal-disabled" aria-disabled="true" title="Belum diimplementasikan pada fondasi Web Guru"><span>Pengaturan</span><span class="text-[10px] uppercase tracking-wide">Tahap berikutnya</span></div>
            </nav>
            <div class="border-t border-white/10 p-4">
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="truncate text-sm font-bold">{{ $currentUser->name ?? 'Guru' }}</p>
                    <p class="mt-1 truncate text-xs text-emerald-100/70">{{ $currentUser->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-white/20 px-3 py-2 text-left text-sm font-semibold text-emerald-50 hover:bg-white/10">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1 md:ml-72">
            <header class="sticky top-0 z-20 border-b portal-border" style="background:var(--surface)">
                <div class="flex items-center justify-between gap-3 px-4 py-3 md:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider portal-muted">CBT Madrasah</p>
                        <p class="text-sm font-bold">Ruang Kerja Guru</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <details class="relative md:hidden">
                            <summary class="cursor-pointer rounded-lg border portal-border px-3 py-2 text-sm font-semibold">☰ Menu</summary>
                            <div class="portal-card absolute right-0 mt-2 w-64 p-2 shadow-xl">
                                <a href="{{ route('teacher.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm">Dashboard</a>
                                <a href="{{ route('teacher.assignments') }}" class="block rounded-lg px-3 py-2 text-sm">Penugasan Saya</a>
                                <a href="{{ route('teacher.learning') }}" class="block rounded-lg px-3 py-2 text-sm">Pembelajaran</a>
                                <a href="{{ route('teacher.curriculum') }}#kurikulum" class="block rounded-lg px-3 py-2 text-sm">Kurikulum & Akademik</a>
                                <div class="my-2 border-t portal-border"></div>
                                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider portal-muted">Modul existing — menunggu gate akses</p>
                                <a href="{{ route('teacher.questions.index') }}" class="block rounded-lg px-3 py-2 text-sm">Bank Soal</a>
                                <a href="{{ route('teacher.questions.template') }}" class="block rounded-lg px-3 py-2 text-sm">Unduh Template Import Soal</a>
                                <a href="{{ route('teacher.exams.index') }}" class="block rounded-lg px-3 py-2 text-sm">Asesmen & Ujian</a>
                                <div class="my-2 border-t portal-border"></div>
                                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider portal-muted">Akses per ujian / tahap berikutnya</p>
                                <span class="block rounded-lg px-3 py-2 text-sm portal-muted" aria-disabled="true">Monitoring Ujian · Per ujian</span>
                                <span class="block rounded-lg px-3 py-2 text-sm portal-muted" aria-disabled="true">Penilaian & Hasil · Per ujian</span>
                                <span class="block rounded-lg px-3 py-2 text-sm portal-muted" aria-disabled="true">Analytics & Ekspor · Per ujian</span>
                                <p class="px-3 py-2 text-[11px] leading-relaxed portal-muted">Pengawasan ujian terpusat memerlukan penugasan Proktor yang sah.</p>
                                <a href="{{ route('teacher.profile') }}" class="block rounded-lg px-3 py-2 text-sm">Profil Guru</a>
                                <span class="block rounded-lg px-3 py-2 text-sm portal-muted" aria-disabled="true">Pengaturan · Tahap berikutnya</span>
                                <div class="my-2 border-t portal-border"></div>
                                <form method="POST" action="{{ route('logout') }}" class="p-2">
                                    @csrf
                                    <button type="submit" class="portal-button w-full">Keluar</button>
                                </form>
                            </div>
                        </details>
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-bold">{{ $currentUser->name ?? 'Guru' }}</p>
                            <p class="text-xs portal-muted">Akun Guru</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full font-bold" style="background:var(--surface-soft);color:var(--accent)">
                            {{ strtoupper(substr($currentUser->name ?? 'G', 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>
            <main class="mx-auto w-full max-w-7xl px-4 py-6 md:px-8 md:py-8">
                {{ $slot }}
            </main>
            <footer class="px-4 pb-6 text-center text-xs portal-muted md:px-8">CBT Madrasah · Portal Guru</footer>
        </div>
    </div>
    @livewireScripts
</body>
</html>
