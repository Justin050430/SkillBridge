import test from 'node:test';
import assert from 'node:assert/strict';
import assessmentTimer from '../../resources/js/assessment-timer.js';

function timer(t, seconds) {
    let elapsed = 0;
    let wallElapsed = 0;
    t.mock.method(performance, 'now', () => elapsed);
    t.mock.method(Date, 'now', () => wallElapsed);
    const instance = assessmentTimer({ attemptId: 7 });
    instance.$dispatch = t.mock.fn();
    instance.sync = t.mock.fn();
    instance.nextSync = Infinity;
    instance.setClock(0, seconds === null ? null : seconds * 1000);
    return { instance, advance: (ms, wall = ms) => { elapsed = ms; wallElapsed = wall; instance.tick(); } };
}

test('formats hours and seconds and enters warning at ten seconds', t => {
    const { instance, advance } = timer(t, 5400);
    advance(0);
    assert.equal(instance.display, '01:30:00');
    advance(1000);
    assert.equal(instance.display, '01:29:59');
    advance(5389000);
    assert.equal(instance.warning, false);
    advance(5390000);
    assert.equal(instance.display, '00:00:10');
    assert.equal(instance.warning, true);
});

test('zero disables answering, closes confirmation, and checks server without submitting', t => {
    const { instance, advance } = timer(t, 10);
    advance(0);
    assert.equal(instance.warning, true);
    advance(10000);
    assert.equal(instance.display, '00:00:00');
    assert.equal(instance.ended, true);
    assert.equal(instance.canSubmit(), false);
    assert.equal(instance.submitting, false);
    assert.deepEqual(instance.$dispatch.mock.calls[0].arguments, ['close-modal', 'confirm-submit-assessment']);
    assert.ok(instance.sync.mock.callCount() > 0);
});

test('clock rollback and suspended interval cannot extend remaining time', t => {
    const { instance, advance } = timer(t, 30);
    advance(20000, -60000);
    assert.equal(instance.remaining, 10);
    advance(21000, 40000);
    assert.equal(instance.ended, true);
});

test('untimed assessments do not expire', t => {
    const { instance, advance } = timer(t, null);
    advance(100000000);
    assert.equal(instance.remaining, null);
    assert.equal(instance.ended, false);
    assert.equal(instance.canSubmit(), true);
});

test('offline expiration stays closed and reconnect reconciles an accepted submission', async t => {
    const originalFetch = globalThis.fetch;
    const originalDocument = globalThis.document;
    const originalWindow = globalThis.window;
    t.after(() => { globalThis.fetch = originalFetch; globalThis.document = originalDocument; globalThis.window = originalWindow; });
    const redirects = [];
    globalThis.document = { querySelector: () => ({ content: 'test-csrf' }) };
    globalThis.window = { location: { replace: url => redirects.push(url) } };
    globalThis.fetch = async () => { throw new Error('offline'); };
    const instance = assessmentTimer({ attemptId: 7, expireUrl: '/expire' });
    instance.ended = true;
    await instance.sync();
    assert.equal(instance.ended, true);
    assert.match(instance.message, /answering is closed/);
    assert.equal(instance.syncing, false);
    globalThis.fetch = async (url, options) => {
        assert.equal(JSON.parse(options.body).attempt_id, 7);
        return { ok: true, status: 200, json: async () => ({ state: 'submitted', redirect_url: '/result?attempt=7' }) };
    };
    await instance.sync();
    assert.deepEqual(redirects, ['/result?attempt=7']);
});
