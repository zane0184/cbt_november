import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve, join } from 'node:path';
import { Script } from 'node:vm';

const here = dirname(fileURLToPath(import.meta.url));
const backendRoot = resolve(here, '..');
const bladePath = join(backendRoot, 'resources/views/student/exams/show.blade.php');
const html = readFileSync(bladePath, 'utf8');

const portalViewPaths = [
  join(backendRoot, 'resources/views/auth/login.blade.php'),
  join(backendRoot, 'resources/views/student/exams/index.blade.php'),
  bladePath,
  join(backendRoot, 'resources/views/student/results/show.blade.php')
];
const localPortalCssPath = join(backendRoot, 'public/css/student-portal.css');
const localPortalCss = readFileSync(localPortalCssPath, 'utf8');
assert.ok(localPortalCss.length > 5000, 'Local Web Siswa stylesheet must contain the checked-in utility subset');
for (const viewPath of portalViewPaths) {
  const view = readFileSync(viewPath, 'utf8');
  assert.ok(!view.includes('cdn.tailwindcss.com'), 'Web Siswa view must not load Tailwind CDN: ' + viewPath);
  assert.ok(!view.includes('fonts.googleapis.com'), 'Web Siswa view must not load remote Google Fonts: ' + viewPath);
  assert.ok(view.includes("asset('css/student-portal.css')"), 'Web Siswa view must load the local stylesheet: ' + viewPath);
}
assert.ok(localPortalCss.includes('.bg-slate-100') && localPortalCss.includes('.p-4'), 'Local stylesheet must include the shared portal utility styles');
console.log('PASS: login, exam list, exam runner, and results use local CSS without CDN');

const inlineScripts = [...html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)]
  .filter((match) => !/\bsrc\s*=/.test(match[1]));
assert.equal(inlineScripts.length, 1, 'Runner must contain exactly one inline application script');

const applicationScript = inlineScripts[0][2];
assert.ok(applicationScript.trim().length > 1000, 'Inline runner script must not be empty');
new Script(applicationScript, { filename: bladePath });
console.log('PASS: inline runner JavaScript parses');

const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map((match) => match[1]);
const duplicates = ids.filter((id, index) => ids.indexOf(id) !== index);
assert.deepEqual([...new Set(duplicates)], [], 'HTML IDs must be unique');
console.log('PASS: ' + ids.length + ' static HTML IDs are unique');

const requiredStores = ['attempt_context', 'answers', 'sync_queue', 'server_receipts', 'conflicts', 'local_meta'];
for (const store of requiredStores) {
  assert.ok(applicationScript.includes(store), 'IndexedDB store reference missing: ' + store);
}
assert.match(applicationScript, /indexedDB\.open\(LOCAL_DB_NAME,\s*2\)/, 'IndexedDB schema version 2 must be opened');
console.log('PASS: IndexedDB v2 stores and open version are present');

assert.ok(applicationScript.includes('client_request_id'), 'Answer queue must retain a stable client request ID');
assert.ok(applicationScript.includes('function flushPendingSaves()'), 'Submit must flush pending answer writes');
assert.ok(!html.includes('cdn.tailwindcss.com'), 'Runner must not depend on Tailwind CDN');
assert.match(html, /id="question-announcement"[^>]*aria-live="polite"[^>]*aria-atomic="true"/, 'Question changes must be announced to assistive technology');
assert.ok(applicationScript.includes("button.setAttribute('aria-current', 'step')"), 'Current question navigation item must expose aria-current');
assert.ok(applicationScript.includes("textarea.setAttribute('aria-label'"), 'Essay answer editor must have an accessible name');
assert.ok(applicationScript.includes("question.metadata?.question_type || 'MC') === 'SHORT_ANSWER'"), 'Disabled SHORT_ANSWER snapshot types must be detected');
assert.ok(applicationScript.includes('unsupportedQuestionIds.size'), 'Disabled SHORT_ANSWER must block submit');
assert.ok(applicationScript.includes("} else if (type === 'ESSAY') {"), 'Only ESSAY may render the free-text answer editor');
assert.ok(!applicationScript.includes("type === 'SHORT_ANSWER' || type === 'ESSAY'"), 'SHORT_ANSWER must not share the enabled free-text renderer');
assert.ok(applicationScript.includes('Token ujian wajib diisi sebelum memulai.'), 'Required token must be validated before start POST');
assert.ok(applicationScript.includes("select.setAttribute('aria-label'"), 'Matching answer selector must have an accessible name');
console.log('PASS: question navigation and answer controls expose accessible semantics');
assert.ok(applicationScript.includes('state.pending.size'), 'Submit barrier must inspect pending answers');
assert.ok(applicationScript.includes('state.conflicts.size'), 'Submit barrier must block unresolved conflicts');
assert.ok(applicationScript.includes('state.undurable.size'), 'Submit barrier must block undurable local drafts');
assert.ok(applicationScript.includes('state.submitOutcomeUnknown'), 'Unknown submit outcome must be persisted in runner state');
assert.ok(applicationScript.includes('recoverSubmitStatus'), 'Unknown submit recovery path must exist');
console.log('PASS: answer durability, conflict, and submit barriers are present');

assert.ok(applicationScript.includes('X-CBT-Fence-Generation'), 'Attempt-scoped requests must carry the server fencing generation');
assert.ok(applicationScript.includes('fenceGeneration: state.fenceGeneration'), 'Fencing generation must be persisted in IndexedDB Attempt context');
assert.ok(applicationScript.includes('function startHeartbeat()') && applicationScript.includes('60000'), 'Web Attempt must heartbeat at the agreed 60-second cadence');
assert.ok(applicationScript.includes('function stopHeartbeat()'), 'Heartbeat must stop on terminal state and submit');
assert.ok(applicationScript.includes('async function renderSnapshotMedia'), 'Snapshot media must use authenticated Fetch/Blob loading');
assert.ok(applicationScript.includes('URL.createObjectURL(blob)'), 'Media must be rendered from authenticated Blob Object URLs');
assert.ok(applicationScript.includes('URL.revokeObjectURL(url)'), 'Media Object URLs must be revoked when no longer used');
assert.ok(applicationScript.includes('10 * 1024 * 1024'), 'Media client must enforce the backend 10 MB upload limit');
console.log('PASS: auth-session fencing, heartbeat, and authenticated media lifecycle are present');

assert.ok(applicationScript.includes('async function verifyAnswerReceipt'), 'Server answer receipt must be verified before acknowledging a local queue item');
assert.ok(applicationScript.includes('client_request_id'), 'Receipt must bind the immutable client request ID');
assert.ok(applicationScript.includes('expectedAnswerPayloadHash'), 'Receipt payload hash must be compared with the locally canonicalized payload');
assert.ok(applicationScript.includes('state.pending.delete(question.id)'), 'Pending queue item is removed only after the verified response path');
console.log('PASS: immutable answer receipt binding and payload-hash verification are present');

assert.ok(applicationScript.includes('clearInterval(state.timerIntervalId)'), 'Timer reload must clear the previous interval');
console.log('PASS: timer interval deduplication guard is present');

assert.match(html, /\.runner-layout\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)/s, 'Mobile-first runner must start with one content column');
assert.match(html, /@media\s*\(min-width:\s*1024px\)[\s\S]*?\.runner-layout\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)\s+220px/s, 'Desktop two-column layout must start at 1024px');
assert.match(html, /\.question-nav-content\s*\{/, 'Mobile question navigation content styles must exist');
assert.match(html, /#question-nav\s*button\s*\{[^}]*min-height:\s*44px/s, 'Question navigation touch targets must be at least 44px');
assert.match(html, /#previous-button,\s*#next-button,\s*#submit-button\s*\{\s*min-height:\s*48px/s, 'Primary navigation and submit touch targets must be at least 48px');
console.log('PASS: mobile-first layout, navigation selector, and touch targets are present');

assert.ok(applicationScript.includes("['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart']"), 'Question-area copy deterrents must remain installed');
assert.ok(applicationScript.includes('isEditableQuestionTarget'), 'Copy deterrents must preserve editable/media/link controls');
console.log('PASS: scoped copy deterrents preserve interactive controls');

console.log('Agent 4 Web Siswa runner static audit: ALL CHECKS PASSED');
