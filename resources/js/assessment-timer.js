export default function assessmentTimer(config) {
    return {
        remaining: null,
        ended: false,
        submitting: false,
        syncing: false,
        message: '',
        interval: null,
        baseline: 0,
        wallBaseline: 0,
        budget: null,
        nextSync: 0,

        init() {
            this.setClock(config.serverNow, config.expiresAt);
            this.tick();
            this.interval = setInterval(() => this.tick(), 250);
            this.sync();
        },

        destroy() {
            clearInterval(this.interval);
        },

        setClock(serverNow, expiresAt) {
            this.budget = expiresAt === null ? null : Math.max(0, expiresAt - serverNow);
            this.baseline = performance.now();
            this.wallBaseline = Date.now();
        },

        get warning() {
            return this.remaining !== null && this.remaining <= 10 && !this.ended;
        },

        get display() {
            const seconds = this.remaining ?? 0;
            return [Math.floor(seconds / 3600), Math.floor(seconds / 60) % 60, seconds % 60]
                .map(value => String(value).padStart(2, '0')).join(':');
        },

        tick() {
            if (this.budget !== null) {
                // Monotonic elapsed time prevents a clock rollback extending the
                // display. Wall elapsed time also catches OS/browser suspension.
                const elapsed = Math.max(performance.now() - this.baseline, Date.now() - this.wallBaseline);
                this.remaining = Math.max(0, Math.ceil((this.budget - elapsed) / 1000));
                if (this.remaining === 0 && !this.ended) {
                    this.ended = true;
                    this.message = 'Time is up. Checking your attempt…';
                    this.$dispatch('close-modal', 'confirm-submit-assessment');
                    this.nextSync = 0;
                }
            }
            // Reconcile other tabs and recover failed requests without a busy loop.
            if (performance.now() >= this.nextSync) this.sync();
        },

        canSubmit() {
            this.tick();
            return !this.ended && !this.submitting;
        },

        submitAnswers() {
            if (!this.canSubmit()) return;
            // Submit before Alpine disables the fieldset: disabled answers are
            // omitted by the browser when constructing a form submission.
            document.getElementById('submit-assessment-form').submit();
            this.submitting = true;
        },

        async sync() {
            if (this.syncing) return;
            this.syncing = true;
            this.nextSync = performance.now() + 15000;
            const sentAt = performance.now();
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 5000);
            try {
                const response = await fetch(config.expireUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ attempt_id: config.attemptId }),
                    signal: controller.signal,
                });
                if ([401, 403, 404, 419].includes(response.status)) {
                    this.ended = true;
                    this.message = 'This attempt is no longer accessible. Return to My Assessments.';
                    return;
                }
                if (!response.ok) throw new Error('Unable to check attempt');
                const state = await response.json();
                if (state.state !== 'active') {
                    this.ended = true;
                    this.message = state.state === 'submitted' ? 'Your assessment was submitted.' : 'Time is up. This attempt counts toward your limit.';
                    window.location.replace(state.redirect_url || config.resultUrl);
                    return;
                }
                // Conservatively account for request latency. Only a server
                // response may correct a clock that appeared to reach zero early.
                this.setClock(state.server_now + (performance.now() - sentAt), state.expires_at);
                this.ended = false;
                this.message = '';
                this.tick();
            } catch {
                this.message = this.ended
                    ? 'Time is up. Unable to connect; answering is closed. Reconnect to view your result.'
                    : 'Connection lost. The timer continues; reconnect to submit.';
                this.nextSync = performance.now() + 5000;
            } finally {
                clearTimeout(timeout);
                this.syncing = false;
            }
        },
    };
}
