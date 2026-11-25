@extends('installer.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-extrabold text-navy-900">Langkah 2: Konfigurasi Database MySQL</h2>
        <p class="text-sm text-textSecondary mt-1">Masukkan kredensial database MySQL server yang telah Anda buat di hosting/aaPanel/cPanel Anda.</p>
    </div>

    <form action="{{ route('installer.database.save') }}" method="POST" id="dbForm" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-navy-900 mb-1">Host Database</label>
                <input type="text" name="host" id="host" value="{{ old('host', $defaultConfig['host']) }}" required
                    class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-navy-900 mb-1">Port</label>
                <input type="text" name="port" id="port" value="{{ old('port', $defaultConfig['port']) }}" required
                    class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-navy-900 mb-1">Nama Database MySQL</label>
            <input type="text" name="database" id="database" value="{{ old('database', $defaultConfig['database']) }}" placeholder="misal: cbt_madrasah" required
                class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-navy-900 mb-1">Username Database</label>
                <input type="text" name="username" id="username" value="{{ old('username', $defaultConfig['username']) }}" placeholder="misal: root atau user_cbt" required
                    class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-navy-900 mb-1">Password Database</label>
                <input type="password" name="password" id="password" placeholder="Password MySQL"
                    class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
            </div>
        </div>

        <!-- Connection Test Result Badge -->
        <div id="testResult" class="hidden p-3 rounded-xl text-xs font-bold"></div>

        <div class="pt-4 border-t border-cardBorder flex justify-between items-center">
            <button type="button" id="btnTest" class="px-5 py-2.5 bg-navy-800 hover:bg-navy-900 text-white font-bold text-sm rounded-xl transition shadow-xs">
                Uji Koneksi Database
            </button>

            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-md flex items-center gap-2">
                <span>Simpan & Lanjut Migrasi</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('btnTest').addEventListener('click', function() {
        const btn = this;
        const resultDiv = document.getElementById('testResult');
        btn.disabled = true;
        btn.textContent = 'Memeriksa...';
        resultDiv.className = 'hidden';

        const payload = {
            _token: '{{ csrf_token() }}',
            host: document.getElementById('host').value,
            port: document.getElementById('port').value,
            database: document.getElementById('database').value,
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
        };

        fetch('{{ route("installer.database.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Uji Koneksi Database';
            resultDiv.classList.remove('hidden');

            if (data.success) {
                resultDiv.className = 'p-3 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
                resultDiv.textContent = '✓ ' + data.message + ' (MySQL ' + (data.version || '') + ')';
            } else {
                resultDiv.className = 'p-3 rounded-xl text-xs font-bold bg-red-100 text-red-800 border border-red-300';
                resultDiv.textContent = '✕ ' + data.message;
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.textContent = 'Uji Koneksi Database';
            resultDiv.classList.remove('hidden');
            resultDiv.className = 'p-3 rounded-xl text-xs font-bold bg-red-100 text-red-800 border border-red-300';
            resultDiv.textContent = '✕ Terjadi kesalahan saat menguji koneksi.';
        });
    });
</script>
@endsection
