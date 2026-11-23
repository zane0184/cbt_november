import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const here = dirname(fileURLToPath(import.meta.url));
const backendRoot = resolve(here, '..');
const bladePath = join(backendRoot, 'resources/views/student/exams/show.blade.php');
const blade = readFileSync(bladePath, 'utf8');
const scripts = [...blade.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)]
  .filter((match) => !/\bsrc\s*=/.test(match[1]));
assert.equal(scripts.length, 1, 'Expected exactly one inline runner script');
const runnerScript = scripts[0][2];
assert.match(blade, /id="question-stem" dir="auto"/, 'Question stem must declare automatic bidirectional text direction');
assert.ok(runnerScript.includes("label.dir = 'auto'") && runnerScript.includes("text.dir = 'auto'"), 'Answer options must use automatic text direction');
const runnerStyles = [...blade.matchAll(/<style\b[^>]*>([\s\S]*?)<\/style>/gi)].map(match => match[1]).join('\n');
assert.ok(runnerStyles.includes('.runner-layout') && runnerStyles.includes('.question-nav-panel'), 'Viewport fixture must use the actual Blade runner responsive styles');

const fixture = [
  '<!doctype html><html lang="id"><head><meta charset="utf-8">',
  '<meta name="csrf-token" content="test-csrf"><style>.hidden{display:none!important}button{min-height:44px}' + runnerStyles + '</style></head><body>',
  '<main id="exam-app" data-exam-id="exam-1" data-student-id="student-1" data-device-binding="11111111-1111-4111-8111-111111111111" data-require-token="0">',
  '<section id="instruction-panel">{{TOKEN_INPUT}}<button id="start-button" type="button">Mulai Ujian</button><span id="start-status"></span></section>',
  '<section id="runner-panel" class="hidden"><div id="question-announcement" class="sr-only" aria-live="polite" aria-atomic="true"></div><div class="runner-layout"><div class="runner-main"><div id="exam-timer"></div><div id="save-status"></div><div id="question-progress"></div>',
  '<div id="question-number"></div><div id="question-points"></div><div id="question-media"></div><div id="question-stem" dir="auto"></div><div id="question-input"></div>',
  '<button id="previous-button" type="button">Sebelumnya</button><button id="next-button" type="button">Berikutnya</button></div>',
  '<details id="question-nav-panel" class="question-nav-panel"><summary>Nomor soal</summary><div id="question-nav"></div><button id="submit-button" type="button">Kirim Ujian</button></details></div></section>',
  '<section id="completion-panel" class="hidden"><h2 id="completion-title"></h2><p id="completion-message"></p></section>',
  '<div id="fatal-error" class="hidden"></div><button id="check-submit-status" class="hidden" type="button">Periksa status</button>',
  '</main><script>window.__intervalIds = new Set(); window.__revokedObjectUrls = []; const nativeSetInterval = window.setInterval.bind(window); const nativeClearInterval = window.clearInterval.bind(window); const nativeRevokeObjectURL = URL.revokeObjectURL.bind(URL); URL.revokeObjectURL = url => { window.__revokedObjectUrls.push(url); return nativeRevokeObjectURL(url); }; window.setInterval = (...args) => { const id = nativeSetInterval(...args); window.__intervalIds.add(id); return id; }; window.clearInterval = id => { window.__intervalIds.delete(id); return nativeClearInterval(id); };</script>',
  '<script>', runnerScript, '</script></body></html>'
].join('');

const server = createServer((request, response) => {
  response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
  if (new URL(request.url, 'http://127.0.0.1').pathname === '/seed') {
    response.end('<!doctype html><script>window.__legacySeedDone = false; const request = indexedDB.open("cbt-madrasah-student-v1", 1); request.onupgradeneeded = () => request.result.createObjectStore("pending-answers", { keyPath: "key" }); request.onsuccess = () => { const db = request.result; const tx = db.transaction("pending-answers", "readwrite"); tx.objectStore("pending-answers").put({ key: "attempt-1:snapshot-1", attemptId: "attempt-1", snapshotId: "snapshot-1", payload: { selected_option_id: "opt-b" }, clientRequestId: "legacy-request-1", updatedAt: Date.now() }); tx.oncomplete = () => { db.close(); window.__legacySeedDone = true; }; };</script>');
    return;
  }
  const requireToken = new URL(request.url, 'http://127.0.0.1').searchParams.get('require-token') === '1';
  const tokenInput = requireToken ? '<label for="exam-token">Token Ujian</label><input id="exam-token">' : '';
  response.end(fixture.replace('{{TOKEN_INPUT}}', tokenInput).replace('data-require-token="0"', `data-require-token="${requireToken ? '1' : '0'}"`));
});
await new Promise((resolveListen, reject) => {
  server.once('error', reject);
  server.listen(0, '127.0.0.1', resolveListen);
});
const address = server.address();
const origin = 'http://127.0.0.1:' + address.port;

function canonicalizeJson(value) {
  if (Array.isArray(value)) return value.map(canonicalizeJson);
  if (value && typeof value === 'object') {
    const normalized = {};
    for (const key of Object.keys(value).sort()) normalized[key] = canonicalizeJson(value[key]);
    return normalized;
  }
  return value;
}
function answerPayloadHash(snapshotId, payload) {
  const canonical = canonicalizeJson({ snapshot_id: String(snapshotId), answer_state: 'ANSWERED', answer_payload: payload });
  return createHash('sha256').update(JSON.stringify(canonical), 'utf8').digest('hex');
}

function makeScenario(mode = 'normal') {
  return {
    mode, online: true, startCalls: 0, submitCalls: 0, answerWrites: 0,
    requireToken: false, startTokens: [], media: [], legacyPending: false, disableIndexedDb: false,
    status: 'IN_PROGRESS', serverAnswers: [], pageErrors: [], statusChecks: 0,
    generation: 1, heartbeatCalls: 0, attemptHeaders: [], mediaHeaders: [], executionCalls: 0, omitStartFence: false, mediaStatus: 200, mediaResponseMime: null, mediaBodySize: 0, answerRequestLedger: new Map(), answerRequestIds: [], answerCommits: 0, leaseExpiresAt: new Date(Date.now() + 5 * 60 * 1000).toISOString(),
    expiresInMs: 30 * 60 * 1000, submitStatus: 'SUBMITTED',
    questionType: 'MULTIPLE_CHOICE',
    questionStem: 'Berapakah hasil 2 + 2?',
    questionOptions: [
      { id: 'opt-a', option_key: 'A', content: 'Empat' },
      { id: 'opt-b', option_key: 'B', content: 'Lima' }
    ]
  };
}

function json(route, status, body) {
  return route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(body) });
}

async function attachApiMock(page, scenario) {
  await page.route('**/api/v1/**', async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    const path = url.pathname.replace('/api/v1/exams/exam-1', '');
    const method = request.method();

    if (method === 'GET' && url.pathname.startsWith('/api/v1/media/')) {
      scenario.mediaHeaders.push(request.headers());
      if (request.headers()['x-cbt-fence-generation'] !== String(scenario.generation) || !request.headers()['x-device-binding-id']) return json(route, 409, { success: false, code: 'ATTEMPT_FENCE_MISMATCH' });
      if (scenario.mediaStatus !== 200) return route.fulfill({ status: scenario.mediaStatus, contentType: 'application/json', body: JSON.stringify({ message: 'Media access denied' }) });
      const mediaId = url.pathname.split('/').pop();
      const mediaItem = scenario.media.find(item => String(item.id) === mediaId);
      const mime = scenario.mediaResponseMime || mediaItem?.mime_type || 'image/png';
      let body;
      if (mime === 'image/png') body = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j5ZkAAAAASUVORK5CYII=', 'base64');
      else if (mime === 'audio/mpeg') body = Buffer.from('ID3\\u0004\\u0000\\u0000\\u0000\\u0000\\u0000\\u0000', 'binary');
      else if (mime === 'video/mp4') body = Buffer.from([0, 0, 0, 24, 102, 116, 121, 112, 105, 115, 111, 109, 0, 0, 2, 0, 105, 115, 111, 109, 105, 115, 111, 50]);
      else if (mime === 'application/pdf') body = Buffer.from('%PDF-1.4\\n%%EOF\\n');
      else body = Buffer.from('<html>not an allowed media type</html>');
      if (scenario.mediaBodySize > 0) body = Buffer.alloc(scenario.mediaBodySize, 65);
      return route.fulfill({ status: 200, contentType: mime, body });
    }
    if ((path === '/execution' || path.startsWith('/attempts/')) && !(method === 'POST' && path === '/attempts')) {
      const headers = request.headers();
      scenario.attemptHeaders.push({ method, path, fence: headers['x-cbt-fence-generation'] || null, device: headers['x-device-binding-id'] || null });
      if (headers['x-cbt-fence-generation'] !== String(scenario.generation) || !headers['x-device-binding-id']) return json(route, 409, { success: false, code: 'ATTEMPT_FENCE_MISMATCH' });
    }
    if (method === 'POST' && path === '/attempts') {
      scenario.startCalls++;
      scenario.startTokens.push(request.postDataJSON().token ?? null);
      const startData = { id: 'attempt-1' };
      if (!scenario.omitStartFence) { startData.fence_generation = scenario.generation; startData.lease_expires_at = scenario.leaseExpiresAt; }
      return json(route, 200, { success: true, data: startData });
    }
    if (method === 'GET' && path === '/execution') {
      scenario.executionCalls++;
      const now = new Date();
      return json(route, 200, { success: true, data: {
        attempt: { id: 'attempt-1', expires_at: new Date(now.getTime() + scenario.expiresInMs).toISOString(), fence_generation: scenario.generation, lease_expires_at: scenario.leaseExpiresAt },
        exam: { server_now: now.toISOString() },
        questions: [{
          id: 'snapshot-1', snapshot_stem: scenario.questionStem, points: 1,
          metadata: { question_type: scenario.questionType }, snapshot_media: scenario.media,
          snapshot_options: scenario.questionOptions
        }]
      }});
    }
    if (method === 'GET' && path === '/attempts/attempt-1/answers') {
      return json(route, 200, { success: true, data: scenario.serverAnswers, fence_generation: scenario.generation, lease_expires_at: scenario.leaseExpiresAt });
    }
    if (method === 'PUT' && path === '/attempts/attempt-1/answers/snapshot-1') {
      scenario.answerWrites++;
      const payload = request.postDataJSON();
      scenario.answerRequestIds.push(payload.client_request_id);
      if (!scenario.online) return route.abort('failed');
      if (scenario.mode === 'fence-mismatch') return json(route, 409, { success: false, code: 'ATTEMPT_FENCE_MISMATCH', message: 'Fence mismatch' });
      if (scenario.mode === 'conflict') {
        return json(route, 409, {
          success: false, message: 'Konflik revisi',
          current_revision: 2, current_answer: { selected_option_id: 'opt-b' }
        });
      }
      const payloadHash = answerPayloadHash('snapshot-1', payload.answer_payload);
      const existing = scenario.answerRequestLedger.get(payload.client_request_id);
      if (existing) {
        if (existing.payloadHash !== payloadHash) return json(route, 409, { success: false, code: 'IDEMPOTENCY_KEY_REUSED' });
        return json(route, 200, { ...existing.response, data: { ...existing.response.data, fence_generation: scenario.generation }, fence_generation: scenario.generation, lease_expires_at: scenario.leaseExpiresAt, idempotent: true, message: 'Request yang sama telah diterima sebelumnya.' });
      }
      scenario.answerCommits++;
      scenario.serverAnswers = [{
        exam_question_snapshot_id: 'snapshot-1',
        answer_payload: payload.answer_payload,
        answer_state: 'ANSWERED',
        revision_number: 1
      }];
      const receipt = {
        id: 'receipt-' + payload.client_request_id,
        client_request_id: scenario.mode === 'bad-receipt' ? 'wrong-request-id' : payload.client_request_id,
        attempt_id: 'attempt-1',
        snapshot_id: 'snapshot-1',
        revision_number: 1,
        payload_hash: payloadHash,
        committed_at: new Date().toISOString()
      };
      const response = {
        success: true,
        data: { attempt_id: 'attempt-1', snapshot_id: 'snapshot-1', revision_number: 1, answer_state: 'ANSWERED', fence_generation: scenario.generation },
        receipt,
        fence_generation: scenario.generation,
        lease_expires_at: scenario.leaseExpiresAt
      };
      scenario.answerRequestLedger.set(payload.client_request_id, { payloadHash, response });
      if (scenario.mode === 'answer-unknown' && scenario.answerCommits === 1) return route.abort('failed');
      return json(route, 200, response);
    }
    if (method === 'POST' && path === '/attempts/attempt-1/heartbeat') {
      scenario.heartbeatCalls++;
      if (scenario.mode === 'heartbeat-terminal') return json(route, 409, { success: false, code: 'ATTEMPT_NOT_ACTIVE' });
      scenario.generation++;
      scenario.leaseExpiresAt = new Date(Date.now() + 5 * 60 * 1000).toISOString();
      return json(route, 200, { success: true, server_now: new Date().toISOString(), status: 'IN_PROGRESS', fence_generation: scenario.generation, lease_expires_at: scenario.leaseExpiresAt });
    }
    if (method === 'POST' && path === '/attempts/attempt-1/submit') {
      scenario.submitCalls++;
      if (scenario.mode === 'unknown-submit' && scenario.submitCalls === 1) {
        return route.abort('failed');
      }
      scenario.status = scenario.submitStatus;
      return json(route, 200, { success: true, data: { status: scenario.submitStatus } });
    }
    if (method === 'GET' && path === '/attempts/attempt-1') {
      scenario.statusChecks++;
      return json(route, 200, { success: true, data: { status: scenario.status, fence_generation: scenario.generation, lease_expires_at: scenario.leaseExpiresAt } });
    }
    return json(route, 404, { success: false, message: 'Unexpected API ' + method + ' ' + path });
  });
}

async function newPage(browser, scenario) {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const page = await context.newPage();
  page.on('pageerror', error => scenario.pageErrors.push(error.message));
  await attachApiMock(page, scenario);
  if (scenario.disableIndexedDb) {
    await page.addInitScript(() => Object.defineProperty(window, 'indexedDB', { configurable: true, value: undefined }));
  }
  if (scenario.legacyPending) {
    await page.goto(origin + '/seed');
    await page.waitForFunction(() => window.__legacySeedDone === true);
  }
  await page.goto(scenario.requireToken ? origin + '/?require-token=1' : origin);
  return { context, page };
}

async function start(page) {
  await page.locator('#start-button').click();
  await page.locator('#runner-panel:not(.hidden)').waitFor({ state: 'visible' });
  await page.waitForFunction(() => document.querySelector('#question-stem').textContent.trim().length > 0);
}

async function chooseFirstAnswer(page) {
  await page.locator('#question-input input[value="opt-a"]').check();
}

async function openQuestionNavigation(page) {
  const panel = page.locator('#question-nav-panel');
  if (!(await panel.evaluate(element => element.open))) {
    await panel.locator('summary').click();
  }
}

const browser = await chromium.launch({ headless: true });
try {
  // Mobile and desktop layouts use the intended column structure, touch sizes, and preserve drawer state on resize.
  {
    const scenario = makeScenario();
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    const mobileColumns = await page.locator('.runner-layout').evaluate(element => getComputedStyle(element).gridTemplateColumns.trim().split(/\s+/).length);
    await openQuestionNavigation(page);
    assert.equal(await page.locator('#question-nav-panel').evaluate(element => element.open), true);
    const submitHeight = await page.locator('#submit-button').evaluate(element => element.getBoundingClientRect().height);
    assert.equal(mobileColumns, 1, 'Mobile layout must use a single content column');
    assert.ok(submitHeight >= 48, 'Submit touch target must be at least 48 CSS pixels');
    await page.setViewportSize({ width: 1440, height: 900 });
    const desktopColumns = await page.locator('.runner-layout').evaluate(element => getComputedStyle(element).gridTemplateColumns.trim().split(/\s+/).length);
    assert.equal(desktopColumns, 2, 'Desktop layout must show the question navigation column');
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.locator('#question-nav-panel').evaluate(element => element.open), true, 'Manual mobile drawer state must survive resize');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: mobile/desktop responsive columns, touch target, and drawer resize state');
  }

  // 1. Normal path: answer is saved to the server before submit; submit is terminal.
  {
    const scenario = makeScenario();
    const { context, page } = await newPage(browser, scenario);
    page.on('dialog', dialog => dialog.accept());
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    await openQuestionNavigation(page);
    await page.locator('#submit-button').click();
    await page.locator('#completion-panel:not(.hidden)').waitFor({ state: 'visible' });
    assert.equal(scenario.startCalls, 1);
    assert.equal(await page.locator('#exam-token').count(), 0, 'Optional-token exams must not render a token field');
    assert.equal(scenario.startTokens[0], null, 'Optional-token exams must start without a token value');
    assert.equal(scenario.answerWrites, 1);
    assert.equal(scenario.submitCalls, 1);
    assert.equal(scenario.serverAnswers[0].answer_payload.selected_option_id, 'opt-a');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: browser answer save and terminal submit');
  }

  // A committed answer whose response is lost is retried with the exact same request ID and payload.
  {
    const scenario = makeScenario('answer-unknown');
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('Gagal menyimpan'));
    await page.reload();
    await start(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    assert.equal(scenario.answerWrites, 2);
    assert.equal(scenario.answerCommits, 1, 'Exact retry must replay the immutable server receipt without a second commit');
    assert.equal(scenario.answerRequestLedger.size, 1);
    assert.equal(scenario.answerRequestIds[0], scenario.answerRequestIds[1]);
    assert.equal(scenario.serverAnswers[0].answer_payload.selected_option_id, 'opt-a');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: response-loss answer retry reuses exact request ID/payload and verifies original receipt');
  }

  // A mismatched client request ID in a receipt is never acknowledged or removed from the local queue.
  {
    const scenario = makeScenario('bad-receipt');
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('Receipt server tidak mengikat client_request_id'));
    const queue = await page.evaluate(() => new Promise(resolve => {
      const request = indexedDB.open('cbt-madrasah-student-v1', 2);
      request.onsuccess = () => {
        const db = request.result; const tx = db.transaction('sync_queue', 'readonly');
        const get = tx.objectStore('sync_queue').getAll();
        get.onsuccess = () => { const rows = get.result; db.close(); resolve(rows); };
        get.onerror = () => { db.close(); resolve([]); };
      };
      request.onerror = () => resolve([]);
    }));
    assert.equal(queue.length, 1);
    assert.equal(scenario.answerCommits, 1);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: mismatched receipt never clears the durable local answer queue');
  }

  // Missing IndexedDB fails closed before the server creates an Attempt.
  {
    const scenario = makeScenario();
    scenario.disableIndexedDb = true;
    const { context, page } = await newPage(browser, scenario);
    await page.locator('#start-button').click();
    await page.waitForFunction(() => document.querySelector('#start-status').textContent.includes('Browser tidak mendukung IndexedDB'));
    assert.equal(scenario.startCalls, 0, 'Attempt must not start without durable local storage');
    assert.equal(await page.locator('#runner-panel').isVisible(), false);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: runner fails closed when IndexedDB is unavailable');
  }

  // Start response without a server-issued fence generation must fail closed.
  {
    const scenario = makeScenario();
    scenario.omitStartFence = true;
    const { context, page } = await newPage(browser, scenario);
    await page.locator('#start-button').click();
    await page.waitForFunction(() => document.querySelector('#start-status').textContent.includes('fencing generation'));
    assert.equal(scenario.startCalls, 1);
    assert.equal(scenario.executionCalls, 0, 'Execution must not be requested without a server-issued fence');
    assert.equal(await page.locator('#runner-panel').isVisible(), false);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: runner fails closed when start response omits fencing generation');
  }

  // 3. Required exam token is trimmed and normalized to uppercase before the start request.
  {
    const scenario = makeScenario();
    scenario.requireToken = true;
    const { context, page } = await newPage(browser, scenario);
    assert.equal(await page.locator('#exam-token').count(), 1, 'Required-token exams must render a token field');
    await page.locator('#start-button').click();
    await page.waitForFunction(() => document.querySelector('#start-status').textContent.includes('Token ujian wajib diisi'));
    assert.equal(scenario.startCalls, 0, 'Empty required token must be rejected before the API request');
    await page.locator('#exam-token').fill('  ab12cd  ');
    await page.locator('#start-button').click();
    await page.locator('#runner-panel:not(.hidden)').waitFor({ state: 'visible' });
    assert.equal(scenario.startTokens[0], 'AB12CD');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: required token is trimmed and normalized before Attempt start');
  }

  // Supported student question types render and serialize correctly; SHORT_ANSWER is deliberately excluded.
  {
    const cases = [
      {
        type: 'MULTIPLE_RESPONSE',
        options: [
          { id: 'mr-a', option_key: 'A', content: 'Pernyataan A' },
          { id: 'mr-b', option_key: 'B', content: 'Pernyataan B' }
        ],
        answer: async page => {
          await page.locator('#question-input input[type="checkbox"]').nth(0).check();
          await page.locator('#question-input input[type="checkbox"]').nth(1).check();
        },
        expected: { selected_option_ids: ['mr-a', 'mr-b'] }
      },
      {
        type: 'TRUE_FALSE',
        options: [
          { id: 'tf-a', option_key: 'A', content: 'BENAR' },
          { id: 'tf-b', option_key: 'B', content: 'SALAH' }
        ],
        answer: async page => page.locator('#question-input input[value="tf-b"]').check(),
        expected: { selected_option_id: 'tf-b' }
      },
      {
        type: 'MATCHING',
        options: [
          { id: 'left-1', option_key: 'LEFT', matching_key: null, content: '2 + 2' },
          { id: 'left-2', option_key: 'LEFT', matching_key: null, content: '3 + 3' },
          { id: 'right-1', option_key: null, matching_key: 'RIGHT', content: '4' },
          { id: 'right-2', option_key: null, matching_key: 'RIGHT', content: '6' }
        ],
        answer: async page => {
          const selects = page.locator('#question-input select');
          assert.equal(await selects.count(), 2, 'Matching must render one select for each left item');
          await selects.nth(0).selectOption('right-2');
          await selects.nth(1).selectOption('right-1');
        },
        expected: { matches: [
          { left_id: 'left-1', right_id: 'right-2' },
          { left_id: 'left-2', right_id: 'right-1' }
        ]}
      },
      {
        type: 'ESSAY',
        options: [],
        answer: async page => page.locator('#question-input textarea').fill('Jawaban uraian panjang'),
        expected: { text: 'Jawaban uraian panjang' }
      }
    ];

    for (const item of cases) {
      const scenario = makeScenario();
      scenario.questionType = item.type;
      scenario.questionOptions = item.options;
      const { context, page } = await newPage(browser, scenario);
      await start(page);
      await item.answer(page);
      await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
      assert.deepEqual(scenario.serverAnswers[0].answer_payload, item.expected, item.type + ' payload must match the API contract');
      assert.equal(scenario.answerWrites, 1, item.type + ' answer must be persisted');
      assert.deepEqual(scenario.pageErrors, []);
      await context.close();
      console.log('PASS: question type ' + item.type + ' renders and saves');
    }
  }

  // SHORT_ANSWER remains disabled until the accepted-answer/scoring contract is complete.
  {
    const scenario = makeScenario();
    scenario.questionType = 'SHORT_ANSWER';
    scenario.questionOptions = [];
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    assert.equal(await page.locator('#question-input textarea').count(), 0, 'SHORT_ANSWER must not render an editable answer field');
    assert.match(await page.locator('#question-input').textContent(), /Jawaban Singkat sementara dinonaktifkan/);
    assert.equal(await page.locator('#submit-button').isDisabled(), true, 'An exam containing SHORT_ANSWER must block submission');
    assert.equal(scenario.answerWrites, 0, 'Disabled SHORT_ANSWER must not persist an answer');
    assert.equal(scenario.submitCalls, 0, 'Disabled SHORT_ANSWER must not submit the Attempt');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: SHORT_ANSWER is disabled and blocks unsafe submission');
  }

  // Arabic text and harakat use automatic direction in the stem, options, and essay editor.
  {
    const scenario = makeScenario();
    scenario.questionStem = 'ٱلسَّلَامُ عَلَيْكُمْ — ٢ + ٢؟';
    scenario.questionType = 'ESSAY';
    scenario.questionOptions = [];
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    assert.equal(await page.locator('#question-stem').getAttribute('dir'), 'auto');
    assert.equal(await page.locator('#question-input textarea').getAttribute('dir'), 'auto');
    assert.equal(await page.locator('#question-input textarea').getAttribute('aria-label'), 'Jawaban uraian untuk soal 1');
    await page.locator('#question-input textarea').fill('أَرْبَعَة');
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    assert.equal(scenario.serverAnswers[0].answer_payload.text, 'أَرْبَعَة');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: Arabic question and answer retain harakat with automatic RTL direction');
  }

  // Heartbeat returns a new monotonic generation; the client persists it before later writes.
  {
    const scenario = makeScenario();
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    assert.equal(await page.locator('#question-announcement').textContent(), 'Soal 1 dari 1');
    assert.equal(await page.locator('#question-nav button').first().getAttribute('aria-current'), 'step');
    await page.evaluate(() => window.dispatchEvent(new Event('online')));
    await page.waitForFunction(async () => {
      return await new Promise(resolve => {
        const request = indexedDB.open('cbt-madrasah-student-v1', 2);
        request.onsuccess = () => {
          const db = request.result; const tx = db.transaction('attempt_context', 'readonly');
          const get = tx.objectStore('attempt_context').get('attempt-1');
          get.onsuccess = () => { const value = get.result; db.close(); resolve(value?.fenceGeneration === 2); };
          get.onerror = () => { db.close(); resolve(false); };
        };
        request.onerror = () => resolve(false);
      });
    });
    assert.equal(scenario.heartbeatCalls, 1);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    assert.ok(scenario.attemptHeaders.some(item => item.path.endsWith('/answers/snapshot-1') && item.fence === '2'));
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: heartbeat generation is persisted before subsequent answer writes');
  }

  // A terminal heartbeat response is reconciled with read-only Attempt status instead of leaving a false active runner.
  {
    const scenario = makeScenario('heartbeat-terminal');
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    scenario.status = 'EXPIRED';
    await page.evaluate(() => window.dispatchEvent(new Event('online')));
    await page.locator('#completion-panel:not(.hidden)').waitFor({ state: 'visible' });
    assert.equal(scenario.heartbeatCalls, 1);
    assert.equal(scenario.statusChecks, 1);
    assert.equal(await page.locator('#completion-title').textContent(), 'Ujian ditutup oleh server');
    assert.equal(await page.locator('#runner-panel').isVisible(), false);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: terminal heartbeat is reconciled from server Attempt status');
  }

  // Every backend-supported snapshot media type uses authenticated Fetch/Blob and revokes Object URLs on terminal state.
  {
    const scenario = makeScenario();
    scenario.media = [
      { id: 'media-image', role: 'STEM', mime_type: 'image/png' },
      { id: 'media-audio', role: 'STEM', mime_type: 'audio/mpeg' },
      { id: 'media-video', role: 'STEM', mime_type: 'video/mp4' },
      { id: 'media-pdf', role: 'STEM', mime_type: 'application/pdf' }
    ];
    const { context, page } = await newPage(browser, scenario);
    page.on('dialog', dialog => dialog.accept());
    await start(page);
    await page.waitForFunction(() => document.querySelectorAll('#question-media img, #question-media audio, #question-media video, #question-media a').length === 4);
    await page.waitForFunction(() => document.querySelector('#question-media img')?.naturalWidth === 1);
    for (const selector of ['img', 'audio', 'video', 'a']) assert.ok((await page.locator('#question-media ' + selector).getAttribute(selector === 'a' ? 'href' : 'src')).startsWith('blob:'));
    assert.equal(scenario.mediaHeaders.length, 4);
    assert.ok(scenario.mediaHeaders.every(headers => headers['x-cbt-fence-generation'] === '1' && headers['x-device-binding-id']));
    await openQuestionNavigation(page);
    await page.locator('#submit-button').click();
    await page.locator('#completion-panel:not(.hidden)').waitFor({ state: 'visible' });
    assert.equal(await page.evaluate(() => window.__revokedObjectUrls.length), 4);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: image/audio/video/PDF media uses bound-session Fetch/Blob and Object URLs are revoked');
  }

  // MIME mismatch is rejected before any untrusted Blob is attached to the question.
  {
    const scenario = makeScenario();
    scenario.media = [{ id: 'media-bad-mime', role: 'STEM', mime_type: 'image/png' }];
    scenario.mediaResponseMime = 'text/html';
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await page.waitForFunction(() => document.querySelector('#question-media').textContent.includes('Jenis file media dari server tidak sesuai'));
    assert.equal(await page.locator('#question-media img').count(), 0);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: media MIME mismatch is rejected');
  }

  // Oversized media is rejected before creating a Blob URL.
  {
    const scenario = makeScenario();
    scenario.media = [{ id: 'media-too-large', role: 'STEM', mime_type: 'image/png' }];
    scenario.mediaBodySize = 10 * 1024 * 1024 + 1;
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await page.waitForFunction(() => /Ukuran media melebihi batas 10 MB|Ukuran atau MIME media tidak valid/.test(document.querySelector('#question-media').textContent));
    assert.equal(await page.locator('#question-media img').count(), 0);
    assert.equal(await page.evaluate(() => window.__revokedObjectUrls.length), 0);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: media above 10 MB is rejected');
  }

  // Unauthorized media response locks the runner and leaves the question runner fail-closed.
  {
    const scenario = makeScenario();
    scenario.media = [{ id: 'media-denied', role: 'STEM', mime_type: 'image/png' }];
    scenario.mediaStatus = 403;
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await page.waitForFunction(() => document.querySelector('#runner-panel').getAttribute('aria-busy') === 'true');
    assert.equal(await page.locator('#question-media img').count(), 0);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: unauthorized media response locks runner');
  }

  // In-place network reconnect waits for heartbeat generation before retrying the durable answer queue.
  {
    const scenario = makeScenario();
    scenario.online = false;
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('Gagal menyimpan'));
    scenario.online = true;
    await page.evaluate(() => window.dispatchEvent(new Event('online')));
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    assert.equal(scenario.heartbeatCalls, 1);
    assert.equal(scenario.answerWrites, 2);
    assert.equal(scenario.answerCommits, 1);
    assert.equal(scenario.answerRequestIds[0], scenario.answerRequestIds[1]);
    assert.ok(scenario.attemptHeaders.some(item => item.path.endsWith('/answers/snapshot-1') && item.fence === '2'));
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: reconnect heartbeat advances fence before exact answer queue retry');
  }

  // 5. Offline write: local draft survives reload and is synchronized on resume.
  {
    const scenario = makeScenario('offline-recovery');
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    scenario.online = false;
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('Gagal menyimpan'));
    await page.reload();
    scenario.online = true;
    await start(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    assert.equal(await page.locator('#question-input input[value="opt-a"]').isChecked(), true);
    assert.equal(scenario.startCalls, 1, 'Normal reload must resume the saved Attempt without posting a second start');
    assert.equal(scenario.heartbeatCalls, 1, 'Normal reload must recover/renew the bound lease through heartbeat');
    assert.ok(scenario.attemptHeaders.some(item => item.path === '/attempts/attempt-1' && item.fence === '2'), 'Status lookup must use the generation returned by heartbeat');
    assert.ok(scenario.answerWrites >= 2, 'The persisted local answer must be retried after reload');
    assert.equal(scenario.serverAnswers[0].answer_payload.selected_option_id, 'opt-a');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: IndexedDB draft survives reload and synchronizes after reconnect');
  }

  // IndexedDB v1 pending-answer records migrate into v2 and are synchronized.
  {
    const scenario = makeScenario();
    scenario.legacyPending = true;
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt server terverifikasi'));
    assert.equal(await page.locator('#question-input input[value="opt-b"]').isChecked(), true);
    assert.equal(scenario.answerWrites, 1);
    assert.equal(scenario.serverAnswers[0].answer_payload.selected_option_id, 'opt-b');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: IndexedDB v1 pending answer migrates and synchronizes');
  }

  // A fence/session rejection locks the runner but keeps the exact durable local draft.
  {
    const scenario = makeScenario('fence-mismatch');
    const { context, page } = await newPage(browser, scenario);
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('Draft lokal dipertahankan'));
    assert.equal(await page.locator('#runner-panel').getAttribute('aria-busy'), 'true');
    const queue = await page.evaluate(() => new Promise(resolve => {
      const request = indexedDB.open('cbt-madrasah-student-v1', 2);
      request.onsuccess = () => {
        const db = request.result; const tx = db.transaction('sync_queue', 'readonly');
        const get = tx.objectStore('sync_queue').getAll();
        get.onsuccess = () => { const rows = get.result; db.close(); resolve(rows); };
        get.onerror = () => { db.close(); resolve([]); };
      };
      request.onerror = () => resolve([]);
    }));
    assert.equal(queue.length, 1);
    assert.ok(queue[0].clientRequestId);
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: fence mismatch locks runner and preserves durable answer queue');
  }

  // 8. Conflict: local draft is restored, server copy is retained, submit stays blocked.
  {
    const scenario = makeScenario('conflict');
    const { context, page } = await newPage(browser, scenario);
    page.on('dialog', dialog => dialog.accept());
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('KONFLIK'));
    await page.reload();
    await start(page);
    assert.equal(await page.locator('#question-input input[value="opt-a"]').isChecked(), true);
    await openQuestionNavigation(page);
    await page.getByRole('button', { name: 'Soal 1, konflik jawaban' }).waitFor();
    await page.locator('#submit-button').click();
    await page.waitForFunction(() => document.querySelector('#fatal-error').textContent.toLowerCase().includes('konflik'));
    assert.equal(scenario.submitCalls, 0, 'Conflict must block submit before POST');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: conflict draft restores after reload and submit remains blocked');
  }

  // 9. Server expiry triggers one automatic submit and clears the timer interval.
  {
    const scenario = makeScenario();
    scenario.expiresInMs = -1000;
    scenario.submitStatus = 'EXPIRED';
    const { context, page } = await newPage(browser, scenario);
    await page.locator('#start-button').click();
    await page.locator('#completion-panel:not(.hidden)').waitFor({ state: 'visible' });
    assert.equal(scenario.submitCalls, 1, 'Expired Attempt must trigger one automatic submit');
    assert.equal(await page.locator('#completion-title').textContent(), 'Ujian ditutup oleh server');
    assert.equal(await page.evaluate(() => window.__intervalIds.size), 0, 'Terminal completion must clear the timer interval');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: server expiry auto-submits once and clears timer');
  }

  // 10. Lost submit response: status is checked; no second start/submit is posted.
  {
    const scenario = makeScenario('unknown-submit');
    const { context, page } = await newPage(browser, scenario);
    page.on('dialog', dialog => dialog.accept());
    await start(page);
    await openQuestionNavigation(page);
    await page.locator('#submit-button').click();
    await page.locator('#runner-panel:not(.hidden)').waitFor({ state: 'visible' });
    for (let attempt = 0; attempt < 100 && scenario.statusChecks === 0; attempt++) {
      await new Promise(resolveWait => setTimeout(resolveWait, 50));
    }
    assert.equal(scenario.statusChecks, 1, 'Unknown submit recovery must perform a read-only status check');
    await page.waitForFunction(() => document.querySelector('#check-submit-status').classList.contains('hidden'));
    assert.equal(scenario.startCalls, 1, 'Unknown submit recovery must not POST a second Attempt start');
    assert.equal(scenario.submitCalls, 1, 'Unknown submit recovery must not POST submit a second time');
    assert.equal(await page.locator('#submit-button').isEnabled(), true, 'Same IN_PROGRESS Attempt should unlock after verification');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: unknown submit recovers same Attempt without duplicate POST');
  }
} finally {
  await browser.close();
  await new Promise(resolveClose => server.close(resolveClose));
}
console.log('Agent 4 Web Siswa browser E2E: ALL SCENARIOS PASSED');
