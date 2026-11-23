<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login CBT Madrasah</title>
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
</head>
<body class="bg-contentBg text-textPrimary flex items-center justify-center min-h-screen p-4 sm:p-6">
    <div class="max-w-md w-full bg-white border border-cardBorder rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">

        <!-- Branding Header -->
        <div class="text-center space-y-2">
            <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-3xl mx-auto shadow-lg shadow-emerald-600/30">
                C
            </div>
            <h1 class="text-2xl font-bold text-navy-900 tracking-tight">CBT MADRASAH</h1>
            <p class="text-xs font-semibold text-textSecondary uppercase tracking-wider">Portal Ujian & Administrasi Madrasah</p>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span> Platform Asesmen Digital Modern
            </div>
        </div>

        <!-- Status Message Alert -->
        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="font-medium">{{ session('status') }}</div>
            </div>
        @endif

        <!-- Error Messages Alert -->
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl p-4 space-y-1.5">
                <div class="flex items-center gap-2 font-bold text-red-900">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Autentikasi Gagal</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-red-700 font-medium pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form action="/login" method="POST" id="loginForm" class="space-y-4">
            @csrf

            <!-- Identitas pengguna -->
            <div>
                <label for="identifier" class="block text-xs font-bold text-navy-900 uppercase tracking-wider mb-1.5">
                    Username / NIP / NIK / NISN <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}"
                        placeholder="Masukkan username, NIP, NIK, atau NISN" required autofocus autocomplete="username"
                        class="w-full px-4 py-3 bg-contentBg border border-cardBorder rounded-xl text-sm text-navy-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition">
                </div>
                <p class="text-[11px] text-textSecondary mt-1">Admin: username · Guru: NIP, NIK, atau username · Siswa: NISN.</p>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-navy-900 uppercase tracking-wider mb-1.5">
                    Kata Sandi (Password) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                        placeholder="••••••••"
                        class="w-full pl-4 pr-12 py-3 bg-contentBg border border-cardBorder rounded-xl text-sm text-navy-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition">
                    <button type="button" onclick="togglePasswordVisibility()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-textSecondary hover:text-navy-900 p-1 transition"
                        title="Tampilkan / Sembunyikan Password">
                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a9.957 9.957 0 014.122-.963c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m-6.165-4.516a3 3 0 003.829-3.829m-3.829 3.829L3 3l18 18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 text-emerald-600 rounded border-cardBorder focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-textSecondary">Ingat Saya di Perangkat Ini</span>
                </label>
            </div>

            <!-- Submit Button with Loading State -->
            <button type="submit" id="submitBtn"
                class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2">
                <svg id="spinner" class="w-4 h-4 text-white animate-spin hidden" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="btnText">Masuk ke CBT Madrasah</span>
            </button>
        </form>

        <!-- Footer Links -->
        <div class="pt-4 border-t border-cardBorder text-center space-y-2">
            <a href="/" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Lihat Landing Page & Status API Sistem
            </a>
            <p class="text-[11px] text-textSecondary">© {{ date('Y') }} CBT Madrasah — Platform Asesmen Digital Multi-tenant</p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');

            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            spinner.classList.remove('hidden');
            btnText.textContent = 'Memproses Login...';
        });
    </script>
</body>
</html>
