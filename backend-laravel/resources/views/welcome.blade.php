<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Madrasah - Platform Asesmen Digital Modern</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#E6F4F1',
                            100: '#D0EAE4',
                            600: '#0F8B72',
                            700: '#0B6B58',
                        },
                        navy: {
                            800: '#1E3452',
                            900: '#14243A',
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
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-contentBg text-textPrimary flex items-center justify-center min-h-screen p-6">
    <div class="max-w-lg w-full bg-white border border-cardBorder rounded-3xl p-8 shadow-xl text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-3xl mx-auto shadow-lg shadow-emerald-600/30">
            C
        </div>
        <div>
            <h1 class="text-2xl font-bold text-navy-900">CBT MADRASAH</h1>
            <p class="text-sm font-medium text-textSecondary mt-1">Platform Asesmen Digital Madrasah Ibtidaiyah & Secondary</p>
        </div>

        <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> System Ready: Modern EdTech Premium
        </div>

        <div class="bg-contentBg border border-cardBorder rounded-2xl p-4 text-xs font-mono text-textSecondary text-left space-y-1">
            <p><strong>API Endpoint:</strong> <code>/api/v1/health</code></p>
            <p><strong>Status API:</strong> Operational</p>
            <p><strong>Tenant Isolation:</strong> Active</p>
        </div>

        @auth
            <a href="/exams" class="block w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-md shadow-emerald-600/20">
                Buka Panel Guru & Admin
            </a>
        @else
            <div class="text-xs text-textSecondary">
                Silakan login melalui aplikasi Android siswa atau portal admin madrasah.
            </div>
        @endauth
    </div>
</body>
</html>
