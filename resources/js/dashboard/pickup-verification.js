import { json, withQuery } from './http';

// Same rule as App\Support\Dashboard\PickupCode: no O/0, no I/1/L.
const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
const MAX_WRONG = 5;
const COOLDOWN_MS = 30_000;
const UNDO_MS = 10_000;
const OFFER_RETURN_MS = 8_000;
const RETURN_MS = 13_000;
const COUNTER_KEY = 'koba:counter-mode';

const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
// Browsers only allow vibration after the person has tapped the page.
const vibrate = (pattern) => { try { if (navigator.userActivation?.hasBeenActive) navigator.vibrate?.(pattern); } catch (e) { /* unsupported */ } };

/** Short generated tones (no audio files): ok, error, beep. */
function tone(kind) {
    const notes = { ok: [[660, 0], [990, 0.12]], beep: [[1180, 0]], error: [[220, 0], [196, 0.16]] }[kind];
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        notes.forEach(([freq, at]) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = kind === 'error' ? 'triangle' : 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime + at);
            gain.gain.exponentialRampToValueAtTime(0.16, ctx.currentTime + at + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + at + 0.3);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + at);
            osc.stop(ctx.currentTime + at + 0.35);
        });
        setTimeout(() => ctx.close(), 900);
    } catch (e) { /* audio unavailable */ }
}

/** Typed, pasted or scanned text → the six code characters (prefix, spaces and QR text removed). */
export function cleanCode(value) {
    const text = String(value ?? '').toUpperCase().trim();
    const qr = text.match(/^KOBA:[A-Z0-9-]+:KB-([A-Z0-9]{6})$/);
    if (qr) return qr[1];
    let code = text.replace(/[\s-]+/g, '');
    if (code.length > 6 && code.startsWith('KB')) code = code.slice(2);
    return code.slice(0, 6);
}

/*
 * Pickup verification (branch counter):
 * idle → scanning | searching → found | not_ready | already_picked_up |
 * wrong_branch | cancelled | not_found | invalid → (confirm dialog) → completed.
 * Finding an order never completes it; only the confirm dialog does.
 */
export default ({ urls, feed, code = '', poll = 15 }) => ({
    state: 'idle',
    input: '',
    result: null,
    via: 'code',
    feed,
    now: Date.now(),
    wrong: 0,
    cooldownUntil: 0,
    shaking: false,
    camera: 'off', // off | requesting | live | simulated | denied | none
    facing: 'environment',
    torch: false,
    checked: [],
    modal: false,
    saving: false,
    completedAt: '',
    undoUntil: 0,
    idleSince: 0,
    stay: false,
    counter: false,
    scenario: 'found',
    demoOpen: false,
    announcement: '',

    init() {
        try { this.counter = localStorage.getItem(COUNTER_KEY) === 'on'; } catch (e) { /* default off */ }
        this.applyCounter();
        this._tick = setInterval(() => this.tick(), 250);
        this._poll = setInterval(() => { if (!document.hidden) this.refreshFeed(); }, poll * 1000);
        this._keys = (event) => this.onKey(event);
        window.addEventListener('keydown', this._keys);

        if (code) {
            this.input = cleanCode(code);
            this.$nextTick(() => this.search('list'));
        } else {
            this.$nextTick(() => this.focusInput());
        }
    },

    destroy() {
        clearInterval(this._tick);
        clearInterval(this._poll);
        window.removeEventListener('keydown', this._keys);
        this.stopCamera();
        document.documentElement.classList.remove('counter-mode');
    },

    /* ---- Code input ------------------------------------------------- */

    get badChars() { return [...new Set(this.input.split('').filter((c) => !ALPHABET.includes(c)))]; },
    get valid() { return this.input.length === 6 && !this.badChars.length; },
    get cooling() { return this.cooldownUntil > this.now; },
    get cooldownLeft() { return Math.ceil((this.cooldownUntil - this.now) / 1000); },
    get busy() { return this.state === 'searching' || this.state === 'scanning'; },

    onInput(event) {
        this.input = cleanCode(event.target.value);
        event.target.value = this.input;
        if (['not_found', 'invalid'].includes(this.state)) this.state = 'idle';
    },

    focusInput() {
        this.$refs.code?.focus();
        this.$refs.code?.select();
    },

    onKey(event) {
        if (this.modal) return;
        const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName) || event.target.isContentEditable;
        if (event.key === '/' && !typing) {
            event.preventDefault();
            this.focusInput();
        } else if (event.key === 'Escape' && (this.$root.contains(event.target) || event.target === document.body)) {
            this.reset();
        }
    },

    /* ---- Lookup ----------------------------------------------------- */

    async search(via = 'code') {
        if (!this.valid || this.cooling || this.state === 'searching') return;
        this.via = via;
        this.stopCamera();
        this.state = 'searching';
        this.result = null;
        this.checked = [];
        this.say(`Searching KB-${this.input}`);
        const started = Date.now();
        try {
            const data = await json(withQuery(urls.lookup, { code: `KB-${this.input}` }));
            await pause(600 - (Date.now() - started));
            this.show(data);
        } catch (error) {
            this.state = 'idle';
            this.$dispatch('toast', { type: 'error', message: error.message });
        }
    },

    show(data) {
        this.result = data;
        this.state = data.state;
        const order = data.order;

        if (['not_found', 'invalid'].includes(data.state)) {
            this.wrong++;
            tone('error');
            vibrate([60, 40, 60]);
            this.shaking = true;
            setTimeout(() => { this.shaking = false; }, 500);
            if (this.wrong >= MAX_WRONG) {
                this.wrong = 0;
                this.cooldownUntil = Date.now() + COOLDOWN_MS;
                this.say('Too many wrong codes. Please wait 30 seconds.');
            } else {
                this.say('Pickup code not found. Check the code and try again.');
            }
            return;
        }

        this.wrong = 0;
        if (data.state === 'found') {
            tone('ok');
            vibrate(40);
            this.say(`Order found: ${order.id} for ${order.customer}. Ready for pickup.`);
            this.$nextTick(() => this.$refs.confirmButton?.focus());
        } else {
            tone('error');
            // A wrong-branch answer carries no order, only the branch name.
            this.say(data.state === 'wrong_branch'
                ? `This order belongs to ${data.branch}.`
                : `${order.id} ${{ not_ready: 'is not ready yet', already_picked_up: `was already picked up at ${order.picked_up?.time}`, cancelled: 'was cancelled' }[data.state]}.`);
        }
    },

    verify(row) {
        this.input = cleanCode(row.pickup_code);
        this.search('list');
    },

    tryAgain() {
        this.state = 'idle';
        this.result = null;
        this.input = '';
        this.focusInput();
    },

    reset() {
        this.stopCamera();
        this.modal = false;
        this.state = 'idle';
        this.result = null;
        this.input = '';
        this.checked = [];
        this.undoUntil = 0;
        this.stay = false;
        this.$nextTick(() => this.focusInput());
    },

    /* ---- QR scanner: the browser's own BarcodeDetector, else a demo scan ---- */

    get cameraSupported() { return 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia; },

    async scan() {
        if (this.cooling || this.busy) return;
        this.result = null;
        if (!this.cameraSupported) return this.simulate();

        this.state = 'scanning';
        this.camera = 'requesting';
        try {
            const formats = (await window.BarcodeDetector.getSupportedFormats?.()) ?? [];
            if (!formats.includes('qr_code')) return this.simulate();
            this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: this.facing } });
            this.$refs.video.srcObject = this.stream;
            await this.$refs.video.play();
            this.camera = 'live';
            this.say('Camera on. Point it at the customer’s QR code.');
            this.detect();
        } catch (error) {
            this.stopTracks();
            this.state = 'idle';
            this.camera = ['NotAllowedError', 'SecurityError'].includes(error?.name) ? 'denied' : 'none';
            this.say(this.camera === 'denied' ? 'Camera access denied. Type the code instead.' : 'No camera found. Type the code instead.');
        }
    },

    async detect() {
        if (this.camera !== 'live') return;
        try {
            const codes = await this.detector.detect(this.$refs.video);
            if (codes.length) return this.scanned(codes[0].rawValue);
        } catch (e) { /* frame not ready */ }
        this._detect = setTimeout(() => this.detect(), 200);
    },

    /** Demo: "scan" the order chosen in the demo panel after 1.5 s. */
    simulate() {
        this.stopCamera();
        this.result = null;
        this.state = 'scanning';
        this.camera = 'simulated';
        this.say('Scanning (demo)…');
        this._simulated = setTimeout(() => {
            const pick = this.feed.scenarios.find((s) => s.key === this.scenario);
            if (!pick?.code) {
                this.stopCamera();
                this.$dispatch('toast', { type: 'info', message: `No “${pick?.label ?? this.scenario}” order at this branch today.`, detail: 'Choose another scenario in the demo panel.' });
                return;
            }
            this.scanned(`KOBA:demo:${pick.code}`);
        }, 1500);
    },

    scanned(text) {
        tone('beep');
        vibrate(60);
        this.stopCamera();
        this.input = cleanCode(text);
        this.search('qr');
    },

    stopCamera() {
        clearTimeout(this._detect);
        clearTimeout(this._simulated);
        this.stopTracks();
        if (['requesting', 'live', 'simulated'].includes(this.camera)) this.camera = 'off';
        if (this.state === 'scanning') this.state = 'idle';
    },

    stopTracks() {
        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;
        this.torch = false;
    },

    /** Torch where the camera supports it; otherwise the button only shows its state. */
    async toggleTorch() {
        this.torch = !this.torch;
        const track = this.stream?.getVideoTracks()[0];
        try {
            if (track?.getCapabilities?.().torch) await track.applyConstraints({ advanced: [{ torch: this.torch }] });
        } catch (e) { /* not supported */ }
    },

    async switchCamera() {
        this.facing = this.facing === 'environment' ? 'user' : 'environment';
        if (this.camera === 'live') {
            this.stopCamera();
            await this.scan();
        }
    },

    /* ---- Confirm pickup --------------------------------------------- */

    toggleLine(index) {
        this.checked = this.checked.includes(index) ? this.checked.filter((i) => i !== index) : [...this.checked, index];
    },

    askConfirm() {
        if (this.state !== 'found') return;
        this.modal = true;
        this.$nextTick(() => this.$refs.modalConfirm?.focus());
    },

    closeModal() {
        if (this.saving) return;
        this.modal = false;
        this.$nextTick(() => this.$refs.confirmButton?.focus());
    },

    async confirm() {
        if (this.saving || this.state !== 'found') return;
        this.saving = true;
        const started = Date.now();
        try {
            const data = await json(urls.confirm.replace('__ID__', this.result.order.id), { method: 'POST', body: { via: this.via } });
            await pause(700 - (Date.now() - started));
            this.modal = false;
            this.result = { ...this.result, order: data.order };
            this.state = 'completed';
            this.completedAt = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            this.undoUntil = Date.now() + UNDO_MS;
            this.idleSince = Date.now();
            this.stay = false;
            tone('ok');
            vibrate([40, 60, 40]);
            this.$dispatch('toast', { type: 'success', message: `${data.order.id} picked up`, detail: `${data.order.customer} has received the order.` });
            this.say(`Pickup completed. ${data.order.customer} has received ${data.order.id}.`);
            this.refreshFeed();
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
        } finally {
            this.saving = false;
        }
    },

    get undoLeft() { return Math.max(0, Math.ceil((this.undoUntil - this.now) / 1000)); },

    async undo() {
        try {
            const data = await json(urls.undo.replace('__ID__', this.result.order.id), { method: 'POST' });
            this.result = { ...this.result, state: 'found', order: data.order };
            this.state = 'found';
            this.undoUntil = 0;
            this.$dispatch('toast', { type: 'info', message: data.message });
            this.say(data.message);
            this.refreshFeed();
            this.$nextTick(() => this.$refs.confirmButton?.focus());
        } catch (error) {
            this.undoUntil = 0;
            this.$dispatch('toast', { type: 'error', message: error.message });
        }
    },

    /* After a pickup: offer to go back for the next customer once the counter is idle. */
    touch() { this.idleSince = Date.now(); },
    get offeringReturn() { return this.state === 'completed' && !this.stay && this.now - this.idleSince >= OFFER_RETURN_MS; },
    get returnLeft() { return Math.max(0, Math.ceil((this.idleSince + RETURN_MS - this.now) / 1000)); },

    tick() {
        this.now = Date.now();
        if (this.state === 'completed' && !this.stay && this.now - this.idleSince >= RETURN_MS) this.reset();
    },

    /* ---- Feed, demo panel, counter mode, announcements ---------------- */

    async refreshFeed() {
        try { this.feed = await json(urls.feed); } catch (e) { /* next poll */ }
    },

    scenarioCode() {
        return this.feed.scenarios.find((s) => s.key === this.scenario)?.code;
    },

    async demoReset() {
        try {
            const data = await json(urls.reset, { method: 'POST' });
            this.wrong = 0;
            this.cooldownUntil = 0;
            this.reset();
            this.$dispatch('toast', { type: 'success', message: data.message });
            this.refreshFeed();
        } catch (error) {
            this.$dispatch('toast', { type: 'error', message: error.message });
        }
    },

    toggleCounter() {
        this.counter = !this.counter;
        try { localStorage.setItem(COUNTER_KEY, this.counter ? 'on' : 'off'); } catch (e) { /* not remembered */ }
        this.applyCounter();
    },

    applyCounter() {
        document.documentElement.classList.toggle('counter-mode', this.counter);
        setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
    },

    say(text) {
        this.announcement = '';
        this.$nextTick(() => { this.announcement = text; });
    },
});
