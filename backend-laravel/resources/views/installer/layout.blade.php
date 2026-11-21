<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installer Mandiri - CBT Madrasah</title>
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
                        contentBg: '#F4F7FA',
                        cardBorder: '#E4E9EF',
                        textPrimary: '#172536',
                        textSecondary: '#68778A',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-contentBg text-textPrimary antialiased flex flex-col min-h-screen">
    <!-- Top Header -->
    <header class="bg-navy-900 text-white shadow-md">
        <div class="max-w-4xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl shadow-md">
                    C
                </div>
                <div>
                    <h1 class="text-lg font-extrabold tracking-wide">CBT MADRASAH</h1>
                    <p class="text-xs text-emerald-100/80">Wisad Instalasi Mandiri Server</p>
                </div>
            </div>
            <span class="text-xs font-semibold bg-navy-800 text-emerald-400 px-3 py-1 rounded-full border border-navy-800">
                v1.0.0 Release
            </span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto p-4 md:p-8">
        <!-- Step Indicators -->
        @php
            $currentRoute = request()->route() ? request()->route()->getName() : '';
            $steps = [
                'installer.requirements' => ['num' => 1, 'label' => 'Syarat Sistem'],
                'installer.database' => ['num' => 2, 'label' => 'Database'],
                'installer.migrations' => ['num' => 3, 'label' => 'Migrasi Skema'],
                'installer.admin' => ['num' => 4, 'label' => 'Akun Admin'],
                'installer.license' => ['num' => 5, 'label' => 'Lisensi'],
                'installer.complete' => ['num' => 6, 'label' => 'Finalisasi'],
            ];
        @endphp

        <div class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-cardBorder mb-6">
            <div class="grid grid-cols-6 gap-2 text-center text-xs md:text-sm font-semibold">
                @foreach ($steps as $routeName => $step)
                    @php
                        $isActive = ($currentRoute === $routeName);
                        $isPast = false; // Simple step display
                    @endphp
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs md:text-sm transition-all shadow-sm
                            {{ $isActive ? 'bg-emerald-600 text-white ring-4 ring-emerald-100' : 'bg-gray-100 text-gray-500 border border-cardBorder' }}">
                            {{ $step['num'] }}
                        </div>
                        <span class="{{ $isActive ? 'text-emerald-700 font-extrabold' : 'text-textSecondary font-medium' }} hidden sm:inline">
                            {{ $step['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Flash Alert Messages -->
        @if (session('error'))
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-xl shadow-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold">Perhatian Error:</p>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-xl shadow-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <div>
                    <p class="font-bold">Sukses:</p>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Dynamic Step Content -->
        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-cardBorder">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-cardBorder py-4 text-center text-xs text-textSecondary mt-auto">
        <p>&copy; {{ date('Y') }} CBT Madrasah. Hak Cipta Dilindungi Undang-Undang.</p>
    </footer>
</body>
</html>
