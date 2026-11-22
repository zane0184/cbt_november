import assert from 'node:assert/strict';
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

const fixture = [
  '<!doctype html><html lang="id"><head><meta charset="utf-8">',
  '<meta name="csrf-token" content="test-csrf"><style>.hidden{display:none!important}button{min-height:44px}</style></head><body>',
  '<main id="exam-app" data-exam-id="exam-1" data-student-id="student-1" data-device-binding="11111111-1111-4111-8111-111111111111" data-require-token="0">',
  '<section id="instruction-panel"><input id="exam-token"><button id="start-button" type="button">Mulai Ujian</button><span id="start-status"></span></section>',
  '<section id="runner-panel" class="hidden"><div id="exam-timer"></div><div id="save-status"></div><div id="question-progress"></div>',
  '<div id="question-number"></div><div id="question-points"></div><div id="question-media"></div><div id="question-stem"></div><div id="question-input"></div>',
  '<button id="previous-button" type="button">Sebelumnya</button><button id="next-button" type="button">Berikutnya</button>',
  '<details id="question-nav-panel"><summary>Nomor soal</summary><div id="question-nav"></div><button id="submit-button" type="button">Kirim Ujian</button></details></section>',
  '<section id="completion-panel" class="hidden"><h2 id="completion-title"></h2><p id="completion-message"></p></section>',
  '<div id="fatal-error" class="hidden"></div><button id="check-submit-status" class="hidden" type="button">Periksa status</button>',
  '</main><script>', runnerScript, '</script></body></html>'
].join('');

const server = createServer((_request, response) => {
  response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
  response.end(fixture);
});
await new Promise((resolveListen, reject) => {
  server.once('error', reject);
  server.listen(0, '127.0.0.1', resolveListen);
});
const address = server.address();
const origin = 'http://127.0.0.1:' + address.port;

function makeScenario(mode = 'normal') {
  return {
    mode, online: true, startCalls: 0, submitCalls: 0, answerWrites: 0,
    status: 'IN_PROGRESS', serverAnswers: [], pageErrors: [], statusChecks: 0,
    questionType: 'MULTIPLE_CHOICE',
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

    if (method === 'POST' && path === '/attempts') {
      scenario.startCalls++;
      return json(route, 200, { success: true, data: { id: 'attempt-1' } });
    }
    if (method === 'GET' && path === '/execution') {
      const now = new Date();
      return json(route, 200, { success: true, data: {
        attempt: { id: 'attempt-1', expires_at: new Date(now.getTime() + 30 * 60 * 1000).toISOString() },
        exam: { server_now: now.toISOString() },
        questions: [{
          id: 'snapshot-1', snapshot_stem: 'Berapakah hasil 2 + 2?', points: 1,
          metadata: { question_type: scenario.questionType }, snapshot_media: [],
          snapshot_options: scenario.questionOptions
        }]
      }});
    }
    if (method === 'GET' && path === '/attempts/attempt-1/answers') {
      return json(route, 200, { success: true, data: scenario.serverAnswers });
    }
    if (method === 'PUT' && path === '/attempts/attempt-1/answers/snapshot-1') {
      scenario.answerWrites++;
      if (!scenario.online) return route.abort('failed');
      if (scenario.mode === 'conflict') {
        return json(route, 409, {
          success: false, message: 'Konflik revisi',
          current_revision: 2, current_answer: { selected_option_id: 'opt-b' }
        });
      }
      const payload = request.postDataJSON();
      scenario.serverAnswers = [{
        exam_question_snapshot_id: 'snapshot-1',
        answer_payload: payload.answer_payload,
        answer_state: 'ANSWERED',
        revision_number: 1
      }];
      return json(route, 200, { success: true, data: { revision_number: 1 } });
    }
    if (method === 'POST' && path === '/attempts/attempt-1/submit') {
      scenario.submitCalls++;
      if (scenario.mode === 'unknown-submit' && scenario.submitCalls === 1) {
        return route.abort('failed');
      }
      scenario.status = 'SUBMITTED';
      return json(route, 200, { success: true, data: { status: 'SUBMITTED' } });
    }
    if (method === 'GET' && path === '/attempts/attempt-1') {
      scenario.statusChecks++;
      return json(route, 200, { success: true, data: { status: scenario.status } });
    }
    return json(route, 404, { success: false, message: 'Unexpected API ' + method + ' ' + path });
  });
}

async function newPage(browser, scenario) {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const page = await context.newPage();
  page.on('pageerror', error => scenario.pageErrors.push(error.message));
  await attachApiMock(page, scenario);
  await page.goto(origin);
  return { context, page };
}

async function start(page) {
  await page.locator('#start-button').click();
  await page.locator('#runner-panel:not(.hidden)').waitFor({ state: 'visible' });
  await page.waitForFunction(() => document.querySelector('#question-stem').textContent.includes('Berapakah hasil 2 + 2?'));
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
  // 1. Normal path: answer is saved to the server before submit; submit is terminal.
  {
    const scenario = makeScenario();
    const { context, page } = await newPage(browser, scenario);
    page.on('dialog', dialog => dialog.accept());
    await start(page);
    await chooseFirstAnswer(page);
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt terverifikasi belum tersedia'));
    await openQuestionNavigation(page);
    await page.locator('#submit-button').click();
    await page.locator('#completion-panel:not(.hidden)').waitFor({ state: 'visible' });
    assert.equal(scenario.startCalls, 1);
    assert.equal(scenario.answerWrites, 1);
    assert.equal(scenario.submitCalls, 1);
    assert.equal(scenario.serverAnswers[0].answer_payload.selected_option_id, 'opt-a');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: browser answer save and terminal submit');
  }

  // 2. All six question types published by the question bank render and serialize correctly.
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
        type: 'SHORT_ANSWER',
        options: [],
        answer: async page => page.locator('#question-input textarea').fill('Jawaban singkat'),
        expected: { text: 'Jawaban singkat' }
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
      await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt terverifikasi belum tersedia'));
      assert.deepEqual(scenario.serverAnswers[0].answer_payload, item.expected, item.type + ' payload must match the API contract');
      assert.equal(scenario.answerWrites, 1, item.type + ' answer must be persisted');
      assert.deepEqual(scenario.pageErrors, []);
      await context.close();
      console.log('PASS: question type ' + item.type + ' renders and saves');
    }
  }

  // 3. Offline write: local draft survives reload and is synchronized on resume.
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
    await page.waitForFunction(() => document.querySelector('#save-status').textContent.includes('receipt terverifikasi belum tersedia'));
    assert.equal(await page.locator('#question-input input[value="opt-a"]').isChecked(), true);
    assert.equal(scenario.startCalls, 2, 'Resume request must resolve to the same active Attempt in this mock');
    assert.ok(scenario.answerWrites >= 2, 'The persisted local answer must be retried after reload');
    assert.equal(scenario.serverAnswers[0].answer_payload.selected_option_id, 'opt-a');
    assert.deepEqual(scenario.pageErrors, []);
    await context.close();
    console.log('PASS: IndexedDB draft survives reload and synchronizes after reconnect');
  }

  // 4. Conflict: local draft is restored, server copy is retained, submit stays blocked.
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

  // 5. Lost submit response: status is checked; no second start/submit is posted.
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
