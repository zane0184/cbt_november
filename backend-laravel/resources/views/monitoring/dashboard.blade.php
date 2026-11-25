<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Ujian Realtime - CBT Madrasah</title>
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
<body class="bg-contentBg text-textPrimary antialiased p-6 md:p-8">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <header class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-bold border border-emerald-100 mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span> Monitoring Realtime
                </div>
                <h1 class="text-2xl font-bold text-navy-900" id="exam-title">Memuat Informasi Ujian...</h1>
                <p class="text-sm text-textSecondary" id="exam-status">Status: -</p>
            </div>
            <div class="bg-navy-900 text-white px-5 py-3 rounded-xl shadow-md text-right">
                <p class="text-xs font-medium text-gray-300 uppercase tracking-wider">Waktu Server</p>
                <p class="text-xl font-bold text-emerald-400 font-mono" id="server-time">00:00:00</p>
            </div>
        </header>

        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4" id="summary-cards">
            <!-- Summary cards injected via JavaScript -->
        </div>

        <!-- Participants Table Card -->
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-cardBorder flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/50">
                <h2 class="text-lg font-bold text-navy-900">Daftar Peserta Ujian</h2>
                <div class="flex items-center gap-3">
                    <select id="filter-status" class="border border-cardBorder rounded-xl px-4 py-2 text-sm font-medium focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="IN_PROGRESS">Sedang Mengerjakan</option>
                        <option value="SUBMITTED">Telah Selesai</option>
                        <option value="EXPIRED">Waktu Habis</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cardBorder">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Nama Siswa</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Sisa Waktu</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Aktivitas Terakhir</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-cardBorder" id="participant-table-body">
                        <!-- Table rows injected via JS -->
                    </tbody>
                </table>
            </div>

            <div id="pagination" class="p-4 bg-gray-50 border-t border-cardBorder flex justify-center gap-2">
                <!-- Pagination injected via JS -->
            </div>
        </div>
    </div>

    <script>
        const examId = "{{ $examId }}";
        const apiBase = "/api/v1/exams/" + examId + "/monitoring";
        let currentPage = 1;

        async function fetchSummary() {
            try {
                const response = await fetch(apiBase);
                const result = await response.json();
                if (result.success) {
                    updateSummaryUI(result.data);
                }
            } catch (e) { console.error("Summary fetch error", e); }
        }

        async function fetchParticipants(page = 1) {
            try {
                const status = document.getElementById('filter-status').value;
                const url = `${apiBase}/participants?page=${page}&status=${status}`;
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    updateParticipantsUI(result.data);
                    updatePaginationUI(result.meta);
                }
            } catch (e) { console.error("Participants fetch error", e); }
        }

        function updateSummaryUI(data) {
            document.getElementById('exam-title').textContent = data.title;
            document.getElementById('exam-status').textContent = "Status Ujian: " + data.status;
            document.getElementById('server-time').textContent = new Date(data.server_now).toLocaleTimeString();

            const cards = [
                { label: 'Total', value: data.summary.total_participants, border: 'border-navy-900' },
                { label: 'Belum Mulai', value: data.summary.not_started, border: 'border-blue-500' },
                { label: 'Sedang Berjalan', value: data.summary.in_progress, border: 'border-emerald-600' },
                { label: 'Selesai', value: data.summary.submitted, border: 'border-indigo-600' },
                { label: 'Waktu Habis', value: data.summary.expired, border: 'border-red-500' },
                { label: 'Perhatian', value: data.summary.requires_attention, border: 'border-amber-500' }
            ];

            const summaryHtml = cards.map(c => `
                <div class="bg-white p-4 rounded-xl border-l-4 ${c.border} border-y border-r border-cardBorder shadow-sm">
                    <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">${c.label}</p>
                    <p class="text-2xl font-bold text-navy-900 mt-1">${c.value}</p>
                </div>
            `).join('');
            document.getElementById('summary-cards').innerHTML = summaryHtml;
        }

        function updateParticipantsUI(participants) {
            const tbody = document.getElementById('participant-table-body');
            tbody.innerHTML = participants.map(p => {
                const attempt = p.attempt;
                const statusClass = getStatusClass(attempt?.status || 'NOT_STARTED');
                const lastActivity = attempt?.last_activity_at ? new Date(attempt.last_activity_at).toLocaleTimeString() : '-';
                const remaining = attempt?.remaining_seconds ? formatSeconds(attempt.remaining_seconds) : '-';

                return `
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-navy-900">${p.student_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full ${statusClass}">
                                ${attempt?.status || 'NOT_STARTED'}
                            </span>
                            ${p.requires_attention ? '<span class="ml-2 text-red-500 font-bold" title="Memerlukan Perhatian">⚠️</span>' : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-textSecondary">${remaining}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-textSecondary">${lastActivity}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                            <a href="/exams/${examId}/results" class="text-emerald-600 hover:text-emerald-700">Detail Hasil</a>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function getStatusClass(status) {
            switch(status) {
                case 'IN_PROGRESS': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                case 'SUBMITTED': return 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                case 'EXPIRED': return 'bg-red-50 text-red-700 border border-red-200';
                default: return 'bg-gray-100 text-gray-700 border border-gray-200';
            }
        }

        function formatSeconds(sec) {
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
        }

        function updatePaginationUI(meta) {
            const container = document.getElementById('pagination');
            if (meta.last_page <= 1) { container.innerHTML = ''; return; }

            let html = '';
            for (let i = 1; i <= meta.last_page; i++) {
                const activeClass = i === meta.current_page ? 'bg-emerald-600 text-white font-bold' : 'bg-white text-navy-900 border border-cardBorder hover:bg-gray-50';
                html += `<button onclick="fetchParticipants(${i})" class="px-3.5 py-1.5 rounded-lg text-sm transition ${activeClass}">${i}</button>`;
            }
            container.innerHTML = html;
        }

        document.getElementById('filter-status').onchange = () => fetchParticipants(1);

        fetchSummary();
        fetchParticipants();
        setInterval(fetchSummary, 5000);
        setInterval(() => fetchParticipants(currentPage), 10000);
    </script>
</body>
</html>
