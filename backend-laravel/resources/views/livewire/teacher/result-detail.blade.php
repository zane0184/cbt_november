<div class="space-y-6">
    <!-- Header Card Banner -->
    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">{{ $attempt->participant->user->name }}</h1>
                <p class="text-sm text-textSecondary">{{ $exam->title }} — Percobaan ke-{{ $attempt->attempt_number }}</p>
            </div>
            <div class="text-right bg-contentBg border border-cardBorder px-5 py-3 rounded-2xl">
                <div class="text-3xl font-bold text-emerald-600 font-mono">
                    {{ $attempt->score ? $attempt->score->raw_score : '-' }} <span class="text-textSecondary text-lg font-normal">/ {{ $attempt->score ? $attempt->score->max_score : '-' }}</span>
                </div>
                <div class="mt-1">
                    <span class="px-3 py-1 text-xs font-bold rounded-full
                        {{ $attempt->score && $attempt->score->grading_status === 'FINALIZED' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                        {{ $attempt->score ? $attempt->score->grading_status : 'BELUM DINILAI' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3 pt-4 border-t border-cardBorder">
            @can('finalize', $attempt)
                @if($attempt->score && $attempt->score->grading_status !== 'FINALIZED')
                <button wire:click="finalize" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-5 rounded-xl transition shadow-sm text-xs">
                    Finalisasi Nilai
                </button>
                @endif
            @endcan
            @can('reopen', $attempt)
                @if($attempt->score && $attempt->score->grading_status === 'FINALIZED')
                    <button wire:click="openReopenModal" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-5 rounded-xl transition shadow-sm text-xs">
                        Buka Kembali Hasil (Reopen)
                    </button>
                @endif
            @endcan
            @can('viewStudentResults', $attempt->participant->user)
            <a href="/students/{{ $attempt->participant->user_id }}/export/pdf" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-xl transition shadow-sm text-xs">
                Cetak PDF Result
            </a>
            @endcan
            <a href="/exams/{{ $exam->id }}/results" class="bg-navy-900 hover:bg-navy-800 text-white font-bold py-2.5 px-5 rounded-xl transition shadow-sm text-xs">
                Kembali ke Daftar
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl shadow-sm text-sm" role="alert">
            <p>{{ session('message') }}</p>
        </div>
    @endif

    <!-- Question Scores List -->
    <div class="space-y-4">
        @foreach($questionScores as $qs)
            <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden border-l-4
                {{ $qs->scoring_status === 'MANUAL_REVIEW' ? 'border-l-amber-500' : ($qs->awarded_score > 0 ? 'border-l-emerald-600' : 'border-l-gray-300') }}">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-2">
                            <span class="bg-navy-900 text-white font-bold text-xs px-3 py-1 rounded-lg">#{{ $qs->snapshot->sequence + 1 }}</span>
                            <span class="text-xs font-bold text-textSecondary uppercase bg-gray-100 px-2.5 py-1 rounded-md border border-cardBorder">{{ $qs->question_type }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-navy-900 font-mono">
                                {{ $qs->awarded_score }} / {{ $qs->max_score }} Poin
                            </div>
                            <div class="text-xs text-textSecondary uppercase font-medium">{{ $qs->scoring_status }}</div>
                        </div>
                    </div>

                    <div class="prose prose-sm max-w-none text-textPrimary mb-6 bg-contentBg p-4 rounded-xl border border-cardBorder">
                        {!! $qs->snapshot->snapshot_stem !!}
                    </div>

                    <div class="bg-gray-50/80 p-4 rounded-xl border border-cardBorder mb-4">
                        <h4 class="text-xs font-bold text-textSecondary uppercase mb-2">Jawaban Siswa:</h4>
                        <div class="text-sm font-medium text-navy-900">
                            @php
                                $payload = $qs->evaluated_answer_snapshot;
                            @endphp
                            @if($qs->question_type === 'MC' || $qs->question_type === 'MULTIPLE_CHOICE' || $qs->question_type === 'TRUE_FALSE' || $qs->question_type === 'TF')
                                {{ $payload['selected_option_id'] ?? 'Tidak Dijawab' }}
                            @elseif($qs->question_type === 'MULTIPLE_RESPONSE')
                                {{ implode(', ', $payload['selected_option_ids'] ?? []) }}
                            @elseif($qs->question_type === 'SHORT_ANSWER' || $qs->question_type === 'ESSAY')
                                <p class="whitespace-pre-wrap">{{ $payload['text'] ?? 'Tidak Dijawab' }}</p>
                            @elseif($qs->question_type === 'MATCHING')
                                <ul class="list-disc ml-4 space-y-1">
                                    @foreach($payload['matches'] ?? [] as $match)
                                        <li>{{ $match['left_id'] }} &rarr; {{ $match['right_id'] }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @if($qs->feedback)
                        <div class="bg-blue-50/80 p-4 rounded-xl mb-4 border-l-4 border-blue-500 border-y border-r border-blue-100">
                            <h4 class="text-xs font-bold text-blue-700 uppercase mb-1">Catatan Guru:</h4>
                            <p class="text-sm italic text-blue-900">{{ $qs->feedback }}</p>
                        </div>
                    @endif

                    @can('grade', $attempt)
                    @if($attempt->score && $attempt->score->grading_status !== 'FINALIZED')
                        <div class="mt-4 flex justify-end">
                            @if($editingScoreId === $qs->id)
                                <div class="w-full space-y-3 bg-contentBg p-4 rounded-xl border border-cardBorder">
                                    <div>
                                        <label class="block text-xs font-bold text-textSecondary uppercase mb-1">Nilai Manual</label>
                                        <input wire:model="manualScore" type="number" step="0.01" class="w-36 border border-cardBorder rounded-lg px-3 py-1.5 text-sm font-bold focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                        @error('manualScore') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-textSecondary uppercase mb-1">Catatan / Feedback Guru</label>
                                        <textarea wire:model="feedbackText" rows="2" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Berikan saran atau evaluasi..."></textarea>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="cancelEdit" class="text-xs text-textSecondary hover:text-navy-900 px-3 py-1.5 font-bold">Batal</button>
                                        <button wire:click="saveScore" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-1.5 px-4 rounded-lg shadow-sm">Simpan Nilai</button>
                                    </div>
                                </div>
                            @else
                                <button wire:click="editScore('{{ $qs->id }}')" class="text-emerald-600 hover:text-emerald-700 text-sm font-bold flex items-center gap-1">
                                    {{ $qs->question_type === 'ESSAY' || $qs->scoring_status === 'MANUAL_REVIEW' ? '✏️ Koreksi Soal Ini' : '✏️ Penyesuaian Nilai' }}
                                </button>
                            @endif
                        </div>
                    @endif
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    <!-- Reopen Modal -->
    @if($showReopenModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="relative p-6 border border-cardBorder w-full max-w-md shadow-2xl rounded-2xl bg-white space-y-4">
                <h3 class="text-lg font-bold text-navy-900">Konfirmasi Buka Kembali (Reopen)</h3>
                <p class="text-xs text-textSecondary">Membuka kembali hasil yang telah difinalisasi akan membuat versi penilaian baru. Riwayat versi sebelumnya tetap tersimpan secara aman.</p>
                <div>
                    <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Alasan Membuka Hasil <span class="text-red-500">*</span></label>
                    <textarea wire:model="reopenReason" rows="3" class="w-full border border-cardBorder rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none" placeholder="Tuliskan alasan perbaikan atau ralat nilai..."></textarea>
                    @error('reopenReason') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="closeReopenModal" class="px-4 py-2 text-xs font-bold text-textSecondary hover:text-navy-900 rounded-xl bg-gray-100">Batal</button>
                    <button wire:click="reopen" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm">Buka Kembali</button>
                </div>
            </div>
        </div>
    @endif
</div>
