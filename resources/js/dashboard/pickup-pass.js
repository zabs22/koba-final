/*
 * The customer's pickup pass: the KB code (copy, share, read aloud) and the
 * full-screen QR. Methods take the pass (an order with pickup_code and qr)
 * so the same markup works on the dashboard tracker and the confirmation page.
 */

/** Copy / share / read aloud. Usage: x-data="pickupCode", then copy(pass). */
export function pickupCode() {
    return {
        copied: false,
        canShare: typeof navigator.share === 'function',
        canSpeak: 'speechSynthesis' in window,

        async copy(pass) {
            const code = pass.pickup_code;
            try {
                await navigator.clipboard.writeText(code);
            } catch (e) {
                // Older browsers or no permission: copy from a hidden field.
                const field = Object.assign(document.createElement('textarea'), { value: code, readOnly: true });
                field.style.cssText = 'position:fixed;opacity:0';
                document.body.append(field);
                field.select();
                document.execCommand('copy');
                field.remove();
            }
            this.copied = true;
            this.$dispatch('toast', { type: 'success', message: `Pickup code ${code} copied.` });
            clearTimeout(this._copied);
            this._copied = setTimeout(() => { this.copied = false; }, 2000);
        },

        async share(pass) {
            try {
                await navigator.share({ title: 'My KOBA pickup code', text: `KOBA pickup code ${pass.pickup_code} · ${pass.id} · ${pass.branch_full}, ${pass.pickup_time}` });
            } catch (e) { /* closed the share sheet */ }
        },

        /** Letter by letter, for a noisy counter. */
        speak(pass) {
            if (!this.canSpeak) return;
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(`Pickup code: ${pass.pickup_code.replace(/-/g, '').split('').join(', ')}`);
            utterance.rate = 0.8;
            speechSynthesis.speak(utterance);
        },
    };
}

/**
 * Full-screen QR for scanning at the counter: always black on white (even in
 * dark mode), keeps the screen awake, closes with X, Escape or a swipe down.
 * Open from anywhere: $dispatch('open-pickup-qr', pass).
 */
export function pickupFullscreen() {
    return {
        open: false,
        pass: null,
        lock: null,
        startY: null,
        pull: 0,

        init() {
            this._visible = () => { if (this.open && !document.hidden) this.wake(); };
            document.addEventListener('visibilitychange', this._visible);
        },

        destroy() {
            document.removeEventListener('visibilitychange', this._visible);
            this.release();
        },

        show(pass) {
            if (!pass?.qr) return;
            this._opener = document.activeElement;
            this.pass = pass;
            this.open = true;
            this.wake();
        },

        close() {
            this.open = false;
            this.pull = 0;
            this.release();
            this._opener?.focus?.();
        },

        async wake() {
            try { this.lock = await navigator.wakeLock?.request('screen'); } catch (e) { this.lock = null; }
        },

        release() {
            this.lock?.release?.().catch(() => {});
            this.lock = null;
        },

        touchStart(event) { this.startY = event.touches[0].clientY; },
        touchMove(event) { if (this.startY !== null) this.pull = Math.max(0, event.touches[0].clientY - this.startY); },
        touchEnd() {
            if (this.pull > 110) this.close();
            this.startY = null;
            this.pull = 0;
        },
    };
}
