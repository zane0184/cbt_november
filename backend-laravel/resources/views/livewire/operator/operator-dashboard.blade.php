<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Role: Admin Madrasah
            </div>
            <h1 class="text-2xl font-bold text-navy-900">Dashboard Operasional Madrasah</h1>
            <p class="text-sm text-textSecondary">Pengelolaan administrasi madrasah, data induk, akademik, pengguna, dan kurikulum.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/users" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-sm">
                + Tambah Pengguna
            </a>
            <a href="/admin/academic" class="px-4 py-2.5 bg-navy-900 hover:bg-navy-800 text-white font-bold text-sm rounded-xl transition shadow-sm">
                Kelola Akademik
            </a>
        </div>
    </div>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Total User</p>
            <p class="text-2xl font-bold text-navy-900 mt-2">{{ $totalUsers }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Akun Terdaftar</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Siswa</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2">{{ $totalStudents }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Peserta Ujian</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Guru</p>
            <p class="text-2xl font-bold text-indigo-600 mt-2">{{ $totalTeachers }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Pengajar & Pembuat Soal</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Rombel</p>
            <p class="text-2xl font-bold text-navy-900 mt-2">{{ $totalRombels }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Kelas Aktif</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Mata Pelajaran</p>
            <p class="text-2xl font-bold text-amber-600 mt-2">{{ $totalSubjects }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Mapel Terdaftar</p>
        </div>
    </div>

    <!-- Quick Operational Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="/admin/users" class="bg-white p-5 rounded-2xl border border-cardBorder hover:border-emerald-500 transition shadow-sm group">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl mb-3 group-hover:scale-105 transition">
                👥
            </div>
            <h3 class="font-bold text-navy-900 text-base">Manajemen Pengguna</h3>
            <p class="text-xs text-textSecondary mt-1">Tambah, edit, dan kelola akun Guru dan Siswa.</p>
        </a>
        <a href="/admin/academic" class="bg-white p-5 rounded-2xl border border-cardBorder hover:border-emerald-500 transition shadow-sm group">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl mb-3 group-hover:scale-105 transition">
                🏫
            </div>
            <h3 class="font-bold text-navy-900 text-base">Akademik & Rombel</h3>
            <p class="text-xs text-textSecondary mt-1">Atur Rombongan Belajar, Mata Pelajaran, dan Tahun Ajaran.</p>
        </a>
        <a href="/admin/curriculum" class="bg-white p-5 rounded-2xl border border-cardBorder hover:border-emerald-500 transition shadow-sm group">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-xl mb-3 group-hover:scale-105 transition">
                📖
            </div>
            <h3 class="font-bold text-navy-900 text-base">Kurikulum Merdeka</h3>
            <p class="text-xs text-textSecondary mt-1">Master data Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP).</p>
        </a>
    </div>

    <!-- Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Users Table -->
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-cardBorder flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-navy-900">Pengguna Terbaru</h3>
                <a href="/admin/users" class="text-xs font-bold text-emerald-600 hover:underline">Lihat Semua →</a>
            </div>
            <div class="divide-y divide-cardBorder">
                @forelse($recentUsers as $user)
                    <div class="p-4 flex justify-between items-center hover:bg-gray-50/50">
                        <div>
                            <p class="text-sm font-bold text-navy-900">{{ $user->name }}</p>
                            <p class="text-xs text-textSecondary">{{ $user->email }}</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ $user->roles->pluck('label')->join(', ') ?: 'Tanpa Role' }}
                        </span>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-gray-500">Belum ada data pengguna.</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Rombels -->
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-cardBorder flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-navy-900">Rombongan Belajar (Rombel)</h3>
                <a href="/admin/academic" class="text-xs font-bold text-emerald-600 hover:underline">Lihat Semua →</a>
            </div>
            <div class="divide-y divide-cardBorder">
                @forelse($recentRombels as $rombel)
                    <div class="p-4 flex justify-between items-center hover:bg-gray-50/50">
                        <div>
                            <p class="text-sm font-bold text-navy-900">{{ $rombel->name }}</p>
                            <p class="text-xs text-textSecondary">Kode: {{ $rombel->code ?: '-' }}</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
                            Aktif
                        </span>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-gray-500">Belum ada rombel.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
