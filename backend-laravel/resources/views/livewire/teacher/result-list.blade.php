<div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden space-y-6">
    <!-- Top Filter Header -->
    <div class="p-6 border-b border-cardBorder bg-gray-50/50">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-navy-900">Hasil Ujian: {{ $exam->title }}</h2>
                    @if($exam->show_result)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ✓ Hasil Dipublikasikan
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            🔒 Belum Dipublikasikan
                        </span>
                    @endif
                </div>
                <p class="text-xs text-textSecondary">Kelola dan lihat rincian skor pengerjaan seluruh peserta ujian</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('publishResults', $exam)
                <button wire:click="startPublishModal" class="text-xs font-bold py-2.5 px-4 rounded-xl transition shadow-sm {{ $exam->show_result ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                    {{ $exam->show_result ? 'Tarik Publikasi Nilai' : 'Publikasikan Nilai ke Siswa' }}
                </button>
                @endcan
                <a href="{{ route('teacher.exams.analytics', ['exam' => $exam->id]) }}" class="bg-navy-900 hover:bg-navy-800 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition shadow-sm">
                    Dashboard Analisis
                </a>
                <a href="{{ route('teacher.exams.export.excel', ['exam' => $exam->id]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition shadow-sm">
                    Export Excel
                </a>
                <a href="{{ route('teacher.exams.export.pdf', ['exam' => $exam->id]) }}" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition shadow-sm">
                    Export PDF
                </a>
                @if(auth()->user()?->hasPermission('exam.grade') && (auth()->user()->hasRole('ADMIN_MADRASAH') || auth()->user()->hasRole('GURU')))
                <button wire:click="startFinalizeAll" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition shadow-sm">
                    Finalisasi Masal
                </button>
                @endif
            </div>
        </div>

        <div class="mt-4 flex flex-col md:flex-row gap-3 pt-4 border-t border-cardBorder">
            <input wire:model.live="search" type="text" placeholder="Cari nama siswa..." class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none flex-1">

            <select wire:model.live="status" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                <option value="">Semua Status Attempt</option>
                <option value="IN_PROGRESS">Sedang Berjalan</option>
                <option value="SUBMITTED">Submitted</option>
                <option value="EXPIRED">Expired</option>
            </select>

            <select wire:model.live="rombelId" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                <option value="">Semua Rombel</option>
                @foreach($rombels as $rombel)
                    <option value="{{ $rombel->id }}">{{ $rombel->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="gradingStatus" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                <option value="">Semua Status Penilaian</option>
                <option value="NOT_SCORED">Belum Dinilai</option>
                <option value="AUTO_SCORED">Auto Scored</option>
                <option value="MANUAL_REVIEW">Perlu Review Manual</option>
                <option value="GRADED">Telah Dinilai</option>
                <option value="FINALIZED">Difinalisasi</option>
            </select>
        </div>
    </div>

    <!-- Results Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-cardBorder">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Siswa</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Status Attempt</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Skor Akhir</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Status Penilaian</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-cardBorder">
                @foreach($attempts as $attempt)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-navy-900">{{ $attempt->participant->user->name }}</div>
                            <div class="text-xs text-textSecondary">Attempt #{{ $attempt->attempt_number }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full
                                {{ $attempt->status === 'SUBMITTED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $attempt->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-textPrimary font-mono">
                            @if($attempt->score)
                                <span class="font-bold text-navy-900 text-base">{{ $attempt->score->raw_score }}</span> / {{ $attempt->score->max_score }}
                                <span class="text-xs font-sans text-textSecondary">({{ $attempt->score->percentage }}%)</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($attempt->score)
                                <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full
                                    {{ $attempt->score->grading_status === 'FINALIZED' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                                       ($attempt->score->grading_status === 'MANUAL_REVIEW' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-gray-100 text-gray-700 border border-gray-200') }}">
                                    {{ $attempt->score->grading_status }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Belum Mulai</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                            <a href="{{ route('teacher.exams.results.detail', ['exam' => $exam->id, 'attempt' => $attempt->id]) }}" class="text-emerald-600 hover:text-emerald-700">Lihat Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-cardBorder">
        {{ $attempts->links() }}
    </div>

    <!-- Finalize All Modal -->
    @if($showFinalizeAllModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
                <h3 class="text-lg font-bold text-navy-900">Finalisasi Masal Seluruh Attempt</h3>
                <div class="space-y-2.5 text-sm text-textSecondary bg-contentBg p-4 rounded-xl border border-cardBorder">
                    <div class="flex justify-between">
                        <span>Total Peserta:</span>
                        <span class="font-bold text-navy-900">{{ $preflight['total'] }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-medium">
                        <span>Siap Difinalisasi:</span>
                        <span class="font-bold">{{ $preflight['eligible'] }}</span>
                    </div>
                    <div class="flex justify-between text-red-600 font-medium">
                        <span>Perlu Review Manual:</span>
                        <span class="font-bold">{{ $preflight['pending_review'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Sedang Mengerjakan:</span>
                        <span class="font-bold text-navy-900">{{ $preflight['in_progress'] }}</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Sudah Difinalisasi:</span>
                        <span class="font-bold">{{ $preflight['already_finalized'] }}</span>
                    </div>
                </div>

                @if($preflight['pending_review'] > 0)
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-3 rounded-lg text-xs text-amber-800">
                        Peringatan: Terdapat {{ $preflight['pending_review'] }} attempt yang membutuhkan koreksi manual sebelum dapat difinalisasi.
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('showFinalizeAllModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary hover:text-navy-900 rounded-xl bg-gray-100">Batal</button>
                    <button wire:click="confirmFinalizeAll" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow"
                        @if($preflight['eligible'] == 0) disabled @endif>
                        Finalisasi {{ $preflight['eligible'] }} Attempt
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Publish Results Modal -->
    @if($showPublishModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
                <h3 class="text-lg font-bold text-navy-900">
                    {{ $publishPreflight['is_published'] ? 'Tarik Publikasi Hasil Ujian' : 'Publikasikan Hasil Ujian ke Siswa' }}
                </h3>

                <p class="text-xs text-textSecondary">
                    @if($publishPreflight['is_published'])
                        Menarik publikasi akan menyembunyikan skor dan rincian hasil dari seluruh aplikasi Android siswa.
                    @else
                        Memublikasikan hasil ujian akan mengizinkan siswa yang attempt-nya telah difinalisasi untuk melihat nilai dan rincian evaluasi di aplikasi Android.
                    @endif
                </p>

                <div class="space-y-2.5 text-sm text-textSecondary bg-contentBg p-4 rounded-xl border border-cardBorder">
                    <div class="flex justify-between">
                        <span>Status Publikasi Saat Ini:</span>
                        <span class="font-bold {{ $publishPreflight['is_published'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $publishPreflight['is_published'] ? 'DIPUBLIKASIKAN' : 'DILINDUNGI (Private)' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Attempt Peserta:</span>
                        <span class="font-bold text-navy-900">{{ $publishPreflight['total'] }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-medium">
                        <span>Pengerjaan Selesai (Submitted):</span>
                        <span class="font-bold">{{ $publishPreflight['submitted'] }}</span>
                    </div>
                    <div class="flex justify-between text-blue-700 font-medium">
                        <span>Telah Difinalisasi:</span>
                        <span class="font-bold">{{ $publishPreflight['finalized'] }}</span>
                    </div>
                    @if($publishPreflight['pending_review'] > 0)
                        <div class="flex justify-between text-red-600 font-medium">
                            <span>Perlu Review Manual:</span>
                            <span class="font-bold">{{ $publishPreflight['pending_review'] }}</span>
                        </div>
                    @endif
                </div>

                @if(!$publishPreflight['is_published'] && $publishPreflight['unfinalized_submitted'] > 0)
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-3 rounded-lg text-xs text-amber-800">
                        Catatan: Terdapat {{ $publishPreflight['unfinalized_submitted'] }} attempt yang belum difinalisasi. Siswa hanya dapat melihat hasil nilai setelah attempt miliknya difinalisasi oleh Guru/Admin.
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('showPublishModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary hover:text-navy-900 rounded-xl bg-gray-100">Batal</button>
                    <button wire:click="toggleResultPublication" class="px-4 py-2 text-xs font-bold text-white rounded-xl shadow {{ $publishPreflight['is_published'] ? 'bg-amber-600 hover:bg-amber-700' : 'bg-indigo-600 hover:bg-indigo-700' }}">
                        {{ $publishPreflight['is_published'] ? 'Ya, Tarik Publikasi' : 'Ya, Publikasikan Hasil' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
