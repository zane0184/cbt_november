<div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden space-y-6">
    <div class="p-6 border-b border-cardBorder bg-gray-50/50 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-navy-900">Analisis Butir Soal: {{ $exam->title }}</h2>
            <p class="text-xs text-textSecondary">Evaluasi tingkat kesulitan (P) dan daya pembeda (D) butir soal</p>
        </div>
        <a href="{{ route('teacher.exams.analytics', ['exam' => $exam->id]) }}" class="text-emerald-600 hover:text-emerald-700 text-xs font-bold bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-100">
            &larr; Kembali ke Dashboard
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-cardBorder">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">No</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Tipe Soal</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Total Respon</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Tingkat Kesulitan (P)</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Daya Pembeda (D)</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Status Evaluasi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-cardBorder">
                @foreach($items as $item)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-navy-900">#{{ $item['sequence'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-textSecondary uppercase font-medium">{{ $item['question_type'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-textPrimary font-mono">{{ $item['response_count'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-navy-900 font-mono">{{ $item['difficulty_index'] ?? '-' }}</div>
                            <div class="text-xs text-textSecondary">{{ $item['difficulty_status'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($item['discrimination_status'] === 'insufficient_sample')
                                <span class="text-xs text-amber-700 font-medium bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200">Sampel Minimal < 20</span>
                            @else
                                <div class="text-sm font-bold font-mono {{ $item['discrimination_index'] < 0.2 ? 'text-red-600' : 'text-navy-900' }}">
                                    {{ $item['discrimination_index'] ?? '-' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                             @if($item['difficulty_index'] !== null)
                                <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full
                                    {{ $item['difficulty_status'] === 'Sulit' ? 'bg-red-50 text-red-700 border border-red-200' :
                                       ($item['difficulty_status'] === 'Sedang' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                                    {{ $item['difficulty_status'] }}
                                </span>
                             @else
                                <span class="text-gray-400">-</span>
                             @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-6 bg-contentBg border-t border-cardBorder">
        <h4 class="text-xs font-bold text-navy-900 uppercase tracking-wider mb-2">Panduan Interpretasi Parameter Soal</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-textSecondary">
            <div class="bg-white p-4 rounded-xl border border-cardBorder">
                <p class="font-bold text-navy-900 mb-1">Tingkat Kesulitan (Indeks P):</p>
                <ul class="list-disc ml-4 space-y-0.5">
                    <li>P &lt; 0.30: Kategori Sulit</li>
                    <li>0.30 &le; P &le; 0.70: Kategori Sedang</li>
                    <li>P &gt; 0.70: Kategori Mudah</li>
                </ul>
            </div>
            <div class="bg-white p-4 rounded-xl border border-cardBorder">
                <p class="font-bold text-navy-900 mb-1">Daya Pembeda (Indeks D):</p>
                <p>Membandingkan performa 27% kelompok atas dan 27% kelompok bawah.</p>
                <p class="mt-1 font-medium text-navy-900">Diaktifkan otomatis bila terdapat minimal 20 peserta yang difinalisasi.</p>
            </div>
        </div>
    </div>
</div>
