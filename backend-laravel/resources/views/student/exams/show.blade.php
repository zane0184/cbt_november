<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $exam->title }} - CBT Madrasah</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <header class="bg-slate-900 text-white shadow">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <div><div class="text-xs uppercase tracking-widest text-slate-300">CBT Madrasah</div><h1 class="text-lg font-bold">{{ $exam->title }}</h1></div>
            <a href="{{ route('student.exams.index') }}" class="rounded-lg border border-slate-500 px-3 py-2 text-sm">Daftar Ujian</a>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-8" id="exam-app"
        data-exam-id="{{ $exam->id }}"
        data-device-binding="{{ $deviceBindingId }}"
        data-require-token="{{ $exam->require_token ? '1' : '0' }}"
        data-has-active-attempt="{{ $activeAttempt ? '1' : '0' }}"
        data-can-start="{{ $canStart ? '1' : '0' }}">
        <section id="instruction-panel" class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Peserta: {{ $student->name }}</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ $exam->title }}</h2>
                </div>
                <div class="rounded-xl bg-blue-50 px-4 py-3 text-blue-900"><div class="text-xs">Durasi</div><div class="text-xl font-bold">{{ ceil($exam->duration_seconds / 60) }} menit</div></div>
            </div>
            <div class="prose mt-6 max-w-none whitespace-pre-line text-slate-700">{{ $exam->instructions ?: 'Bacalah setiap soal dengan teliti dan periksa kembali jawaban sebelum mengirim ujian.' }}</div>
            <dl class="mt-6 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Jadwal mulai</dt><dd class="font-semibold">{{ optional($exam->start_at)->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-slate-500">Jadwal selesai</dt><dd class="font-semibold">{{ optional($exam->end_at)->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-slate-500">Percobaan</dt><dd class="font-semibold">{{ $attemptCount }} / {{ $attemptLimit }}</dd></div>
                <div><dt class="text-slate-500">Token</dt><dd class="font-semibold">{{ $exam->require_token ? 'Diperlukan' : 'Tidak diperlukan' }}</dd></div>
            </dl>
            @if (!$eligible)
                <p class="mt-5 rounded-xl bg-amber-50 p-4 text-amber-800">Waktu akses peserta belum dimulai atau sudah berakhir. Hubungi Guru jika ada ketidaksesuaian.</p>
            @elseif (!$activeAttempt && !$canStart)
                <p class="mt-5 rounded-xl bg-amber-50 p-4 text-amber-800">Ujian belum dapat dimulai. Pastikan jadwal telah dimulai dan batas percobaan masih tersedia.</p>
            @endif
            @if ($exam->require_token)
                <label class="mt-6 block text-sm font-semibold" for="exam-token">Token Ujian</label>
                <input id="exam-token" type="text" maxlength="64" autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 uppercase tracking-widest" placeholder="Masukkan token dari Guru">
            @endif
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button id="start-button" type="button" {{ (!$activeAttempt && !$canStart) ? 'disabled' : '' }} class="rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300">{{ $activeAttempt ? 'Lanjutkan Ujian' : 'Mulai Ujian' }}</button>
                <span id="start-status" class="text-sm text-slate-500" aria-live="polite">{{ $activeAttempt ? 'Attempt aktif ditemukan. Jawaban akan dipulihkan dari server.' : 'Ujian hanya dapat dimulai sesuai jadwal server.' }}</span>
            </div>
        </section>

        <section id="runner-panel" class="hidden">
            <div class="sticky top-0 z-10 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow">
                <div><div class="text-xs text-slate-500">Sisa waktu</div><div id="exam-timer" class="text-2xl font-black tabular-nums">--:--</div></div>
                <div class="text-right"><div id="save-status" class="text-sm font-semibold" aria-live="polite">Memuat jawaban…</div><div id="question-progress" class="text-xs text-slate-500"></div></div>
            </div>
            <div class="grid gap-4 lg:grid-cols-[1fr_220px]">
                <div class="rounded-2xl bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between"><span id="question-number" class="text-sm font-semibold text-blue-700"></span><span id="question-points" class="text-xs text-slate-500"></span></div>
                    <div id="question-media" class="mb-5 space-y-3"></div>
                    <div id="question-stem" class="mb-6 whitespace-pre-wrap text-lg leading-relaxed"></div>
                    <div id="question-input" class="space-y-3"></div>
                    <div class="mt-8 flex justify-between gap-3"><button id="previous-button" class="rounded-xl border border-slate-300 px-4 py-3 font-semibold">Sebelumnya</button><button id="next-button" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white">Berikutnya</button></div>
                </div>
                <aside class="rounded-2xl bg-white p-4 shadow-sm"><h3 class="font-bold">Navigasi Soal</h3><div id="question-nav" class="mt-3 grid grid-cols-5 gap-2"></div><button id="submit-button" class="mt-6 w-full rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white">Kirim Ujian</button></aside>
            </div>
        </section>

        <section id="completion-panel" class="hidden rounded-2xl bg-white p-10 text-center shadow-sm">
            <div class="text-4xl">✓</div><h2 class="mt-3 text-2xl font-bold">Ujian berhasil dikirim</h2><p id="completion-message" class="mt-2 text-slate-600">Jawaban Anda telah diterima server. Silakan kembali ke daftar ujian.</p><a href="{{ route('student.exams.index') }}" class="mt-6 inline-flex rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white">Kembali ke Daftar Ujian</a>
        </section>
        <div id="fatal-error" class="mt-4 hidden rounded-xl bg-red-50 p-4 text-red-800" role="alert"></div>
    </main>

<script>
(() => {
    const app = document.getElementById('exam-app');
    const examId = app.dataset.examId;
    const deviceBinding = app.dataset.deviceBinding;
    const requireToken = app.dataset.requireToken === '1';
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const apiBase = `/api/v1/exams/${encodeURIComponent(examId)}`;
    const state = { attemptId: null, questions: [], answers: new Map(), revisions: new Map(), current: 0, serverEpoch: 0, monotonicAtSync: 0, saving: new Map(), timers: new Map(), pending: new Map(), conflicts: new Map(), localDb: null, submitting: false, expired: false };
    const el = id => document.getElementById(id);

    async function api(path, options = {}) {
        const response = await fetch(`${apiBase}${path}`, {
            credentials: 'same-origin',
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'X-Device-Binding-ID': deviceBinding,
                ...(options.headers || {})
            }
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok || body.success === false) {
            const error = new Error(body.message || body.error || `Permintaan gagal (${response.status})`);
            error.status = response.status; error.body = body; throw error;
        }
        return body;
    }

    function showError(message) {
        el('fatal-error').textContent = message;
        el('fatal-error').classList.remove('hidden');
    }
    function setSaveStatus(message, bad = false) {
        el('save-status').textContent = message;
        el('save-status').className = `text-sm font-semibold ${bad ? 'text-red-700' : 'text-slate-700'}`;
    }
    function requestId() {
        return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }
    function optionLabel(option) { return option.content ?? option.text ?? option.label ?? option.title ?? ''; }

    const LOCAL_DB_NAME = 'cbt-madrasah-student-v1';
    const LOCAL_STORES = ['attempt_context', 'answers', 'sync_queue', 'server_receipts', 'conflicts', 'local_meta'];

    function openLocalDatabase() {
        return new Promise((resolve, reject) => {
            if (!('indexedDB' in window)) return reject(new Error('Browser tidak mendukung IndexedDB. Gunakan browser terbaru; ujian belum dimulai.'));
            const request = indexedDB.open(LOCAL_DB_NAME, 2);
            request.onupgradeneeded = event => {
                const db = request.result;
                const tx = request.transaction;
                LOCAL_STORES.forEach(name => {
                    if (!db.objectStoreNames.contains(name)) db.createObjectStore(name, { keyPath: 'key' });
                });
                if (!db.objectStoreNames.contains('pending-answers')) {
                    // Fresh install; no legacy queue to migrate.
                } else {
                    const legacy = tx.objectStore('pending-answers');
                    const cursorRequest = legacy.openCursor();
                    cursorRequest.onsuccess = () => {
                        const cursor = cursorRequest.result;
                        if (!cursor) return;
                        const row = cursor.value;
                        tx.objectStore('answers').put({
                            key: row.key, attemptId: row.attemptId, snapshotId: row.snapshotId,
                            payload: row.payload, localStatus: 'PENDING', updatedAt: row.updatedAt || Date.now()
                        });
                        tx.objectStore('sync_queue').put({
                            key: row.key, attemptId: row.attemptId, snapshotId: row.snapshotId,
                            payload: row.payload, clientRequestId: row.clientRequestId,
                            baseRevision: null, updatedAt: row.updatedAt || Date.now(), status: 'PENDING'
                        });
                        cursor.continue();
                    };
                }
                tx.objectStore('local_meta').put({ key: 'schema_version', value: 2, updatedAt: Date.now() });
            };
            request.onsuccess = () => {
                request.result.onversionchange = () => request.result.close();
                resolve(request.result);
            };
            request.onerror = () => reject(request.error || new Error('Penyimpanan lokal tidak dapat dibuka.'));
            request.onblocked = () => reject(new Error('Penyimpanan lokal sedang dipakai tab lain. Tutup tab CBT lain lalu muat ulang.'));
        });
    }

    function localTransaction(storeNames, mode, operation) {
        return new Promise((resolve, reject) => {
            if (!state.localDb) return reject(new Error('Penyimpanan lokal belum siap.'));
            const tx = state.localDb.transaction(storeNames, mode);
            let result;
            try { result = operation(tx); } catch (error) { reject(error); return; }
            tx.oncomplete = () => resolve(result);
            tx.onerror = () => reject(tx.error || new Error('Transaksi penyimpanan lokal gagal.'));
            tx.onabort = () => reject(tx.error || new Error('Penyimpanan lokal dibatalkan.'));
        });
    }

    function localKey(attemptId, snapshotId) { return String(attemptId) + ':' + String(snapshotId); }
    async function persistAttemptContext() {
        const key = String(state.attemptId);
        await localTransaction(['attempt_context'], 'readwrite', tx => tx.objectStore('attempt_context').put({
            key, attemptId: key, examId: String(app.dataset.examId), deviceBinding,
            updatedAt: Date.now()
        }));
    }
    async function persistPending(snapshotId, item) {
        const key = localKey(state.attemptId, snapshotId);
        await localTransaction(['answers', 'sync_queue'], 'readwrite', tx => {
            tx.objectStore('answers').put({
                key, attemptId: String(state.attemptId), snapshotId: String(snapshotId),
                payload: item.payload, localStatus: 'PENDING', updatedAt: Date.now()
            });
            tx.objectStore('sync_queue').put({
                key, attemptId: String(state.attemptId), snapshotId: String(snapshotId),
                payload: item.payload, clientRequestId: item.clientRequestId,
                baseRevision: item.baseRevision ?? null, updatedAt: Date.now(), status: 'PENDING'
            });
        });
    }
    async function acknowledgePersistedPending(snapshotId, item, serverRevision) {
        const key = localKey(state.attemptId, snapshotId);
        await localTransaction(['answers', 'sync_queue'], 'readwrite', tx => {
            tx.objectStore('answers').put({
                key, attemptId: String(state.attemptId), snapshotId: String(snapshotId),
                payload: item.payload, serverRevision: Number(serverRevision),
                localStatus: 'SERVER_ACKNOWLEDGED_NO_VERIFIED_RECEIPT', updatedAt: Date.now()
            });
            tx.objectStore('sync_queue').delete(key);
        });
    }
    async function persistConflict(snapshotId, item, serverAnswer, serverRevision) {
        const key = localKey(state.attemptId, snapshotId);
        await localTransaction(['conflicts'], 'readwrite', tx => tx.objectStore('conflicts').put({
            key, attemptId: String(state.attemptId), snapshotId: String(snapshotId),
            localPayload: item.payload, clientRequestId: item.clientRequestId,
            baseRevision: item.baseRevision ?? null, serverAnswer,
            serverRevision: Number(serverRevision), updatedAt: Date.now(), status: 'OPEN'
        }));
    }
    async function readPersistedPending() {
        const rows = await new Promise((resolve, reject) => {
            const tx = state.localDb.transaction(['sync_queue'], 'readonly');
            const request = tx.objectStore('sync_queue').getAll();
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error || new Error('Draft lokal gagal dibaca.'));
        });
        return rows.filter(row => row.attemptId === String(state.attemptId));
    }
    async function readPersistedConflicts() {
        const rows = await new Promise((resolve, reject) => {
            const tx = state.localDb.transaction(['conflicts'], 'readonly');
            const request = tx.objectStore('conflicts').getAll();
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error || new Error('Arsip konflik lokal gagal dibaca.'));
        });
        return rows.filter(row => row.attemptId === String(state.attemptId));
    }

    async function startOrResume() {
        el('start-button').disabled = true;
        el('start-status').textContent = 'Memeriksa penyimpanan perangkat…';
        try {
            state.localDb = await openLocalDatabase();
            el('start-status').textContent = 'Menghubungkan ke server…';
            const payload = {};
            if (requireToken) payload.token = (el('exam-token')?.value || '').trim().toUpperCase();
            const started = await api('/attempts', { method: 'POST', body: JSON.stringify(payload) });
            state.attemptId = started.data.id;
            await loadExecution();
        } catch (error) {
            el('start-button').disabled = false;
            el('start-status').textContent = error.message;
            if (error.status === 409) showError('Attempt sedang aktif pada browser/perangkat lain. Jangan membuka ujian pada perangkat berbeda. Hubungi Guru atau Admin jika perangkat sebelumnya rusak.');
        }
    }

    async function loadExecution() {
        const execution = await api('/execution');
        const data = execution.data;
        state.attemptId = data.attempt.id;
        state.questions = data.questions || [];
        state.serverEpoch = Date.parse(data.exam.server_now);
        state.monotonicAtSync = performance.now();
        const answersResponse = await api(`/attempts/${encodeURIComponent(state.attemptId)}/answers`, { method: 'GET' });
        (answersResponse.data || []).forEach(answer => {
            state.answers.set(answer.exam_question_snapshot_id, { payload: answer.answer_payload, state: answer.answer_state });
            state.revisions.set(answer.exam_question_snapshot_id, Number(answer.revision_number || 0));
        });
        await persistAttemptContext();
        const localPending = await readPersistedPending();
        localPending.forEach(row => {
            if (!state.questions.some(question => String(question.id) === row.snapshotId)) return;
            const item = { payload: row.payload, clientRequestId: row.clientRequestId, baseRevision: row.baseRevision ?? null };
            state.pending.set(row.snapshotId, item);
            state.answers.set(row.snapshotId, { payload: row.payload, state: 'ANSWERED' });
        });
        const localConflicts = await readPersistedConflicts();
        localConflicts.forEach(row => {
            state.conflicts.set(row.snapshotId, {
                local: row.localPayload, server: row.serverAnswer, revision: row.serverRevision
            });
        });
        if (localConflicts.length) {
            showError(`${localConflicts.length} soal memiliki konflik jawaban. Draft lokal dan versi server tersimpan terpisah; pengiriman ditahan sampai konflik ditangani oleh Guru/Admin.`);
        }
        el('instruction-panel').classList.add('hidden');
        el('runner-panel').classList.remove('hidden');
        renderNavigation(); renderQuestion(); updateTimer(data.attempt.expires_at);
        window.setInterval(() => updateTimer(data.attempt.expires_at), 1000);
        setSaveStatus(localPending.length ? `${localPending.length} draft lokal menunggu sinkronisasi` : 'Jawaban dipulihkan dari server', localPending.length > 0);
        if (localPending.length) flushPendingSaves().then(() => setSaveStatus('Draft lokal telah diterima server')).catch(error => setSaveStatus(`Draft lokal belum tersinkron: ${error.message}`, true));
    }

    function updateTimer(expiresAt) {
        const estimatedServerNow = state.serverEpoch + (performance.now() - state.monotonicAtSync);
        const remaining = Math.max(0, Date.parse(expiresAt) - estimatedServerNow);
        const seconds = Math.ceil(remaining / 1000);
        el('exam-timer').textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        if (remaining <= 0 && !state.expired) {
            state.expired = true;
            setSaveStatus('Waktu habis. Mengirim jawaban…');
            submitExam(true);
        }
    }

    function renderNavigation() {
        const nav = el('question-nav'); nav.replaceChildren();
        state.questions.forEach((question, index) => {
            const button = document.createElement('button');
            button.type = 'button'; button.textContent = String(index + 1);
            const conflicted = state.conflicts.has(question.id);
            button.className = `rounded-lg border px-2 py-2 text-sm font-semibold ${conflicted ? 'border-red-500 bg-red-50 text-red-800' : (state.answers.has(question.id) && state.answers.get(question.id).state === 'ANSWERED' ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-slate-300')} ${index === state.current ? 'ring-2 ring-blue-600' : ''}`;
            button.setAttribute('aria-label', conflicted ? `Soal ${index + 1}, konflik jawaban` : `Soal ${index + 1}, ${state.answers.has(question.id) ? 'ada draft jawaban' : 'belum dijawab'}`);
            button.addEventListener('click', () => { state.current = index; renderQuestion(); }); nav.appendChild(button);
        });
        el('question-progress').textContent = `${state.questions.length} soal`;
    }

    function currentPayload(question) {
        const saved = state.answers.get(question.id);
        return saved && saved.payload ? JSON.parse(JSON.stringify(saved.payload)) : null;
    }
    function createOptionControl(question, option, type, selected) {
        const label = document.createElement('label');
        label.className = 'flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50';
        const input = document.createElement('input'); input.type = type; input.name = `question-${question.id}`; input.value = option.id; input.className = 'mt-1';
        input.checked = Array.isArray(selected) ? selected.includes(option.id) : selected === option.id;
        input.addEventListener('change', () => {
            let payload;
            if (type === 'checkbox') {
                const ids = [...el('question-input').querySelectorAll('input[type="checkbox"]:checked')].map(item => item.value);
                payload = { selected_option_ids: ids };
            } else payload = { selected_option_id: option.id };
            queueSave(question, payload);
        });
        const text = document.createElement('span'); text.textContent = optionLabel(option); label.append(input, text); return label;
    }

    function renderQuestion() {
        const question = state.questions[state.current]; if (!question) return;
        const metadata = question.metadata || {}; const type = metadata.question_type || 'MC';
        const saved = state.answers.get(question.id); const payload = currentPayload(question) || {};
        el('question-number').textContent = `Soal ${state.current + 1} dari ${state.questions.length}`;
        el('question-points').textContent = `${question.points ?? 0} poin`;
        el('question-stem').textContent = question.snapshot_stem || '';
        const mediaArea = el('question-media'); mediaArea.replaceChildren();
        (question.snapshot_media || []).forEach(media => {
            if (!media || !media.id) return;
            const url = `/api/v1/media/${encodeURIComponent(media.id)}`;
            const mime = String(media.mime_type || '').toLowerCase();
            if (mime.startsWith('image/')) {
                const image = document.createElement('img'); image.src = url; image.alt = 'Media soal'; image.loading = 'lazy'; image.referrerPolicy = 'no-referrer'; image.className = 'max-h-96 max-w-full rounded-xl border border-slate-200 object-contain'; mediaArea.appendChild(image);
            } else if (mime.startsWith('audio/')) {
                const audio = document.createElement('audio'); audio.controls = true; audio.preload = 'none'; audio.src = url; audio.className = 'w-full'; mediaArea.appendChild(audio);
            } else if (mime.startsWith('video/')) {
                const video = document.createElement('video'); video.controls = true; video.preload = 'metadata'; video.src = url; video.className = 'max-h-96 w-full rounded-xl bg-black'; mediaArea.appendChild(video);
            } else if (mime === 'application/pdf') {
                const link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = 'Buka lampiran PDF soal'; link.className = 'inline-flex rounded-lg border border-blue-300 px-3 py-2 text-blue-700'; mediaArea.appendChild(link);
            }
        });
        const inputArea = el('question-input'); inputArea.replaceChildren();
        const options = question.snapshot_options || [];
        if (['MC', 'MULTIPLE_CHOICE', 'TRUE_FALSE'].includes(type)) {
            options.forEach(option => inputArea.appendChild(createOptionControl(question, option, 'radio', payload.selected_option_id)));
        } else if (type === 'MULTIPLE_RESPONSE') {
            options.forEach(option => inputArea.appendChild(createOptionControl(question, option, 'checkbox', payload.selected_option_ids || [])));
        } else if (type === 'SHORT_ANSWER' || type === 'ESSAY') {
            const textarea = document.createElement('textarea'); textarea.rows = type === 'ESSAY' ? 8 : 3; textarea.maxLength = 20000;
            textarea.className = 'w-full rounded-xl border border-slate-300 p-4'; textarea.placeholder = 'Ketik jawaban Anda…'; textarea.value = payload.text || '';
            textarea.addEventListener('input', () => queueSave(question, { text: textarea.value })); inputArea.appendChild(textarea);
        } else if (type === 'MATCHING') {
            const left = options.filter(option => option.option_key === 'LEFT');
            const right = options.filter(option => option.matching_key === 'RIGHT');
            if (!left.length || !right.length) {
                const note = document.createElement('p'); note.className = 'rounded-lg bg-amber-50 p-3 text-amber-800'; note.textContent = 'Format pasangan soal belum lengkap. Hubungi Guru sebelum mengirim ujian.'; inputArea.appendChild(note);
            } else {
                const existing = Array.isArray(payload.matches) ? payload.matches : [];
                left.forEach(leftOption => {
                    const wrap = document.createElement('label'); wrap.className = 'block rounded-xl border border-slate-200 p-3';
                    const title = document.createElement('span'); title.className = 'mb-2 block font-semibold'; title.textContent = optionLabel(leftOption);
                    const select = document.createElement('select'); select.className = 'w-full rounded-lg border border-slate-300 p-3';
                    const empty = document.createElement('option'); empty.value = ''; empty.textContent = 'Pilih pasangan…'; select.appendChild(empty);
                    right.forEach(rightOption => { const option = document.createElement('option'); option.value = rightOption.id; option.textContent = optionLabel(rightOption); select.appendChild(option); });
                    const prior = existing.find(item => item.left_id === leftOption.id); if (prior) select.value = prior.right_id;
                    select.addEventListener('change', () => {
                        const matches = [...inputArea.querySelectorAll('select')].map((node, index) => ({ left_id: left[index].id, right_id: node.value })).filter(item => item.right_id);
                        queueSave(question, { matches });
                    });
                    wrap.append(title, select); inputArea.appendChild(wrap);
                });
            }
        } else {
            const note = document.createElement('p'); note.className = 'text-amber-700'; note.textContent = `Tipe soal ${type} belum didukung oleh tampilan ujian ini.`; inputArea.appendChild(note);
        }
        el('previous-button').disabled = state.current === 0;
        el('next-button').textContent = state.current === state.questions.length - 1 ? 'Soal Terakhir' : 'Berikutnya';
        renderNavigation();
    }

    async function queueSave(question, payload) {
        state.answers.set(question.id, { payload, state: 'ANSWERED' });
        const pendingItem = { payload, clientRequestId: requestId(), baseRevision: null };
        state.pending.set(question.id, pendingItem);
        setSaveStatus('Menyimpan draft pada perangkat…');
        try {
            await persistPending(question.id, pendingItem);
            if (state.pending.get(question.id) !== pendingItem) return;
            setSaveStatus('Draft tersimpan di perangkat; menunggu server');
            if (state.timers.has(question.id)) clearTimeout(state.timers.get(question.id));
            state.timers.set(question.id, setTimeout(() => saveAnswer(question, pendingItem).catch(() => {}), 500));
        } catch (error) {
            setSaveStatus(`Draft tidak dapat disimpan: ${error.message}`, true);
            showError('Penyimpanan lokal gagal. Jangan tutup atau muat ulang halaman; hubungi Guru/Admin sebelum melanjutkan.');
        }
        renderNavigation();
    }

    async function saveAnswer(question, pendingItem) {
        const payload = pendingItem.payload;
        const previous = state.saving.get(question.id) || Promise.resolve();
        const task = previous.catch(() => {}).then(async () => {
            // A newer local edit supersedes this not-yet-sent item. Never let an
            // obsolete request overwrite the durable queue record for the latest edit.
            if (state.pending.get(question.id) !== pendingItem) return;
            setSaveStatus('Menyimpan jawaban…');
            if (pendingItem.baseRevision === null || pendingItem.baseRevision === undefined) {
                pendingItem.baseRevision = state.revisions.get(question.id) || 0;
                await persistPending(question.id, pendingItem);
            }
            try {
                const result = await api(`/attempts/${encodeURIComponent(state.attemptId)}/answers/${encodeURIComponent(question.id)}`, {
                    method: 'PUT', body: JSON.stringify({ client_request_id: pendingItem.clientRequestId, base_revision: pendingItem.baseRevision, answer_payload: payload, answer_state: 'ANSWERED' })
                });
                state.revisions.set(question.id, Number(result.data.revision_number));
                if (state.pending.get(question.id) === pendingItem) {
                    state.pending.delete(question.id);
                    await acknowledgePersistedPending(question.id, pendingItem, result.data.revision_number);
                }
                setSaveStatus(state.pending.size ? `${state.pending.size} jawaban menunggu sinkronisasi` : 'Respons server berhasil; receipt terverifikasi belum tersedia');
            } catch (error) {
                if (error.status === 409 && error.body?.current_revision !== undefined) {
                    state.revisions.set(question.id, Number(error.body.current_revision));
                    // A newer edit may already be queued while this older request was in flight.
                    // Rebase only that not-yet-sent newer item; do not archive the stale item as a
                    // user-visible conflict because the latest local answer is still available.
                    if (state.pending.get(question.id) !== pendingItem) throw error;
                    const conflict = { local: pendingItem.payload, server: error.body.current_answer || null, revision: Number(error.body.current_revision) };
                    state.conflicts.set(question.id, conflict);
                    await persistConflict(question.id, pendingItem, conflict.server, conflict.revision);
                    setSaveStatus('KONFLIK: draft lokal dipertahankan. Pengiriman ditahan; jangan muat ulang atau tutup halaman.', true);
                    showError('Terjadi konflik revisi. Draft lokal dan versi server diarsipkan terpisah. Pengiriman ditahan; minta Guru/Admin membantu pemulihan sebelum mengirim ujian.');
                    renderNavigation();
                } else setSaveStatus(`Gagal menyimpan: ${error.message}. Periksa koneksi dan coba lagi.`, true);
                throw error;
            }
        });
        state.saving.set(question.id, task);
        try {
            await task;
        } catch (error) {
            throw error;
        } finally {
            if (state.saving.get(question.id) === task) state.saving.delete(question.id);
        }
    }

    async function flushPendingSaves() {
        await Promise.all([...state.saving.values()].map(promise => promise.catch(() => {})));
        const pendingSaves = [...state.pending.entries()].filter(([snapshotId]) => !state.conflicts.has(snapshotId)).map(([snapshotId, pendingItem]) => {
            const question = state.questions.find(item => item.id === snapshotId);
            return question ? saveAnswer(question, pendingItem) : Promise.resolve();
        });
        await Promise.all(pendingSaves);
        if (state.conflicts.size) throw new Error('Ada konflik jawaban yang belum diselesaikan.');
        if (state.pending.size) throw new Error('Masih ada jawaban yang belum mendapat konfirmasi server.');
    }

    async function submitExam(auto = false) {
        if (state.submitting || !state.attemptId) return;
        if (!auto && !window.confirm('Yakin ingin mengirim ujian? Pastikan semua jawaban sudah tersimpan.')) return;
        state.submitting = true; el('submit-button').disabled = true;
        try {
            for (const timer of state.timers.values()) clearTimeout(timer);
            state.timers.clear();
            await flushPendingSaves();
            await api(`/attempts/${encodeURIComponent(state.attemptId)}/submit`, { method: 'POST', body: JSON.stringify({}) });
            el('runner-panel').classList.add('hidden'); el('completion-panel').classList.remove('hidden');
        } catch (error) {
            state.submitting = false; el('submit-button').disabled = false;
            if (auto) setSaveStatus('Waktu habis, tetapi ada jawaban yang belum terkonfirmasi. Attempt mengikuti expiry server; jangan tutup halaman.', true);
            showError(`Ujian belum dapat dikirim: ${error.message}. Draft lokal dipertahankan. Jangan tutup halaman; periksa koneksi lalu coba lagi.`);
        }
    }

    window.addEventListener('beforeunload', event => {
        if (state.pending.size || state.conflicts.size || state.saving.size) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    el('start-button').addEventListener('click', startOrResume);
    el('previous-button').addEventListener('click', () => { if (state.current > 0) { state.current--; renderQuestion(); } });
    el('next-button').addEventListener('click', () => { if (state.current < state.questions.length - 1) { state.current++; renderQuestion(); } });
    el('submit-button').addEventListener('click', () => submitExam(false));
    window.addEventListener('online', () => {
        if (!state.attemptId || state.submitting || state.pending.size === 0) return;
        flushPendingSaves().then(() => setSaveStatus('Koneksi pulih; jawaban tertunda sudah tersimpan'))
            .catch(error => setSaveStatus(`Sinkronisasi ulang gagal: ${error.message}`, true));
    });
})();
</script>
</body>
</html>
