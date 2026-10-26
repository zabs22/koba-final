import { $, $$, clamp, pad, reduceMotion, finePointer, onFrame } from './util';

/*
 * Our Story — the pour. As the visitor scrolls, one cup is filled:
 * empty cup → the pot arrives → the pour → the aroma → the cup, ready.
 * The layers are cut from one photograph and share its 764 × 1024 frame; the steam
 * above the cup is drawn live in WebGL. Reduced motion keeps the static frame.
 */

// eyob  talents 
const STARTS = [0, 0.16, 0.36, 0.58, 0.8]; // where each step begins, as scroll progress

// Frame geometry, in pixels of the photograph.
const STREAM_TOP = 289;
const STREAM_H = 349;
const MOUTH = 297;      // the spout's lip
const IMPACT = 612;     // where the stream meets the full cup
const FRONT_LIP = 634;  // below this the cup's front wall hides everything
const DEEP = 50;        // how far below the full level the coffee starts
const COFFEE_H = 74;    // height of the coffee layer

const span = (p, a, b) => clamp((p - a) / (b - a));
const easeIn = (t) => t * t;
const easeOut = (t) => 1 - (1 - t) ** 3;
const easeInOut = (t) => (t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2);

export function initPour() {
    const section = $('[data-pour]');
    if (!section || reduceMotion) return;

    const stage = $('[data-pour-stage]', section);
    const frame = $('[data-pour-frame]', section);
    const pot = $('[data-pour-pot]', section);
    const stream = $('[data-pour-stream]', section);
    const liquid = $('[data-pour-liquid]', section);
    const still = $('[data-pour-still]', section);
    const vapour = $('[data-pour-vapour]', section);
    const glow = $('[data-pour-glow]', section);
    const count = $('[data-pour-count]', section);
    const steps = $$('[data-pour-step]', section);
    const ticks = $$('[data-pour-tick]', section);

    section.classList.add('is-live');
    const steam = createSteam($('[data-pour-steam]', section), stage);
    if (steam) section.classList.add('has-steam');

    $('[data-pour-skip]', section)?.addEventListener('click', () => {
        window.scrollTo({ top: section.getBoundingClientRect().bottom + window.scrollY, behavior: 'instant' });
    });

    let current = -1;
    let lastY = window.scrollY;
    let lastT = performance.now();
    let wind = 0;

    function render(p) {
        stage.style.setProperty('--pour-p', p.toFixed(4));

        // The pot swings in from the hand above, out of focus until it settles; later it lifts away.
        const potIn = easeInOut(span(p, 0.13, 0.34));
        const potOut = easeInOut(span(p, 0.79, 0.93));
        const away = potOut > 0 ? potOut * 0.85 : 1 - potIn;
        pot.style.transform = `translate3d(${(away * 34).toFixed(2)}%, ${(away * -44).toFixed(2)}%, 0) rotate(${(away * 16).toFixed(2)}deg) perspective(900px) rotateY(${(away * -18).toFixed(2)}deg)`;
        pot.style.filter = away > 0.01 ? `blur(${(away * 7).toFixed(2)}px)` : '';
        pot.style.opacity = (1 - span(away, 0.55, 0.95)).toFixed(3);

        // The coffee rises from behind the cup's front wall; the stream always ends on its surface.
        const level = easeOut(span(p, 0.43, 0.63));
        const sink = (1 - level) * DEEP;
        const impact = Math.min(IMPACT + sink, FRONT_LIP);
        const head = easeIn(span(p, 0.37, 0.43));
        const tail = easeIn(span(p, 0.76, 0.81));
        const flowing = head > 0 && tail < 1;
        if (flowing) {
            // Its head and tail are soft (see .pour__stream's mask), like liquid, not a cut.
            const from = MOUTH - 8 + (impact - MOUTH + 8) * tail;
            const to = MOUTH + (impact - MOUTH) * head;
            stream.style.setProperty('--from', `${(clamp((from - STREAM_TOP) / STREAM_H) * 100).toFixed(2)}%`);
            stream.style.setProperty('--to', `${(clamp((to - STREAM_TOP) / STREAM_H) * 100).toFixed(2)}%`);
            stream.style.visibility = '';
        } else {
            stream.style.visibility = 'hidden';
        }
        liquid.style.transform = `translate3d(0, ${(sink / COFFEE_H * 100).toFixed(2)}%, 0) scale(${(0.9 + 0.1 * level).toFixed(4)})`;
        liquid.style.visibility = level > 0 ? '' : 'hidden';

        // The surface calms as the last of the stream reaches it.
        const settled = span(p, 0.79, 0.825);
        still.style.opacity = settled.toFixed(3);
        section.classList.toggle('is-pouring', flowing && head > 0.9);

        // Light warms, steam gathers, and the camera leans in for the aroma.
        glow.style.opacity = (0.3 + 0.7 * span(p, 0.4, 0.68)).toFixed(3);
        vapour.style.opacity = (0.9 * easeOut(span(p, 0.5, 0.68)) - 0.25 * span(p, 0.86, 1)).toFixed(3);
        steam?.amount(0.4 * span(p, 0.43, 0.52) + 0.6 * span(p, 0.52, 0.66) - 0.35 * span(p, 0.86, 1));
        const cam = 1.02
            + 0.02 * span(p, 0, 0.16)
            - 0.02 * easeInOut(span(p, 0.16, 0.3))
            + 0.11 * easeInOut(span(p, 0.55, 0.72))
            - 0.08 * easeInOut(span(p, 0.8, 0.95));
        frame.style.setProperty('--cam', cam.toFixed(4));

        let index = 0;
        STARTS.forEach((start, i) => { if (p >= start) index = i; });
        ticks.forEach((tick, i) => tick.style.setProperty('--f', span(p, STARTS[i], STARTS[i + 1] ?? 1).toFixed(3)));
        section.classList.toggle('is-done', p > 0.985);
        if (index === current) return;
        current = index;
        steps.forEach((step, i) => {
            step.classList.toggle('is-current', i === index);
            step.classList.toggle('is-past', i < index);
        });
        ticks.forEach((tick, i) => {
            tick.classList.toggle('is-current', i === index);
            tick.classList.toggle('is-past', i < index);
        });
        count.textContent = pad(index + 1);
    }

    onFrame((vh) => {
        const rect = section.getBoundingClientRect();
        const visible = rect.bottom > 0 && rect.top < vh;
        steam?.visible(visible);
        if (!visible) return;
        render(clamp(-rect.top / Math.max(1, rect.height - vh)));

        // Scrolling leans the steam a little, like air moving past the cup.
        const now = performance.now();
        const velocity = (window.scrollY - lastY) / Math.max(16, now - lastT);
        lastY = window.scrollY;
        lastT = now;
        wind = clamp(wind * 0.6 + velocity * 0.12, -0.5, 0.5);
        steam?.wind(wind);
    });

    if (finePointer) initTilt(stage, steam);
}

/* ---- Pointer: the frame turns a few degrees, near layers move further ------ */

function initTilt(stage, steam) {
    let tx = 0;
    let ty = 0;
    let mx = 0;
    let my = 0;
    let raf = 0;

    const step = () => {
        raf = 0;
        mx += (tx - mx) * 0.07;
        my += (ty - my) * 0.07;
        stage.style.setProperty('--mx', mx.toFixed(4));
        stage.style.setProperty('--my', my.toFixed(4));
        if (Math.abs(tx - mx) > 0.002 || Math.abs(ty - my) > 0.002) raf = requestAnimationFrame(step);
    };
    const queueStep = () => { if (!raf) raf = requestAnimationFrame(step); };

    stage.addEventListener('pointermove', (event) => {
        const rect = stage.getBoundingClientRect();
        tx = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
        ty = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
        steam?.pointer(event.clientX, event.clientY);
        queueStep();
    });
    stage.addEventListener('pointerleave', () => {
        tx = 0;
        ty = 0;
        steam?.pointer(null);
        queueStep();
    });
}

/* ---- Steam: domain-warped noise rising from the cup (WebGL) --------------- */

const VERTEX = `
attribute vec2 aPos;
void main() { gl_Position = vec4(aPos, 0.0, 1.0); }
`;

const FRAGMENT = `
precision highp float;
uniform vec2 uRes;
uniform float uTime;
uniform float uAmount;
uniform float uWind;
uniform float uSwirl;
uniform vec2 uPointer;
uniform vec2 uSource;
uniform vec2 uParallax;

float hash(vec2 p) {
    p = fract(p * vec2(123.34, 456.21));
    p += dot(p, p + 45.32);
    return fract(p.x * p.y);
}
float noise(vec2 p) {
    vec2 i = floor(p);
    vec2 f = fract(p);
    vec2 u = f * f * (3.0 - 2.0 * f);
    return mix(mix(hash(i), hash(i + vec2(1.0, 0.0)), u.x), mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x), u.y);
}
float fbm(vec2 p) {
    float v = 0.0;
    float a = 0.5;
    mat2 m = mat2(1.6, 1.2, -1.2, 1.6);
    for (int i = 0; i < 5; i++) { v += a * noise(p); p = m * p; a *= 0.5; }
    return v;
}

// One sheet of steam; depth 0 is the far sheet, 1 the near one.
float plume(vec2 uv, float t, float depth) {
    float aspect = uRes.x / uRes.y;
    float h = clamp((uv.y - uSource.y) / (1.0 - uSource.y), 0.0, 1.0);
    vec2 p = vec2((uv.x - uSource.x) * aspect, uv.y - uSource.y);
    p.x -= uParallax.x * (0.02 + depth * 0.05);

    vec2 d = (uv - uPointer) * vec2(aspect, 1.0);
    float s = exp(-dot(d, d) * 26.0) * uSwirl;
    p += vec2(-d.y, d.x) * s * 0.8;

    float sc = mix(1.0, 0.74, depth);
    vec2 q = vec2(p.x * 2.6, p.y * 1.5 - t * 0.19) * sc + depth * 7.3;
    vec2 w = vec2(fbm(q + vec2(0.0, t * 0.05)), fbm(q + vec2(5.2, 1.3)));
    vec2 r = vec2(fbm(q * 1.7 + 3.2 * w + vec2(1.7, 9.2 - t * 0.08)), fbm(q * 1.7 + 3.2 * w + vec2(8.3, 2.8)));
    float n = fbm(q * 2.1 + 2.6 * r);

    float lean = (r.x - 0.5) * 0.46 * h + uWind * h * h + sin(t * 0.35 + h * 3.5 + depth * 2.0) * 0.035 * h;
    float x = p.x - lean;
    float width = mix(0.15, 0.3, pow(h, 0.8));
    float column = exp(-(x * x) / (width * width));

    float ridge = pow(1.0 - abs(n * 2.0 - 1.0), 4.5);
    float body = smoothstep(0.46, 0.88, n);
    float density = ridge * 0.85 + body * 0.28;

    float fade = smoothstep(0.0, 0.07, h) * (1.0 - smoothstep(0.38, 1.0, h));
    return density * column * fade;
}

void main() {
    vec2 uv = gl_FragCoord.xy / uRes;
    float far = plume(uv, uTime, 0.0);
    float near = plume(uv, uTime * 1.12 + 11.0, 1.0);
    float edge = smoothstep(0.0, 0.18, uv.x) * (1.0 - smoothstep(0.82, 1.0, uv.x));
    float a = (far * 0.75 + near * 0.5) * uAmount * edge;
    a = 1.0 - exp(-a * 1.5);
    float h = clamp((uv.y - uSource.y) / (1.0 - uSource.y), 0.0, 1.0);
    vec3 colour = mix(vec3(1.0, 0.93, 0.84), vec3(0.98, 0.97, 0.95), h);
    gl_FragColor = vec4(colour * a, a);
}
`;

/** @param stage carries --mx, the pointer's smoothed position, so the near sheet can drift further. */
function createSteam(canvas, stage) {
    if (!canvas) return null;
    const gl = canvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false, powerPreference: 'low-power' });
    if (!gl) return null;

    const compile = (type, source) => {
        const shader = gl.createShader(type);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        return gl.getShaderParameter(shader, gl.COMPILE_STATUS) ? shader : null;
    };
    const vs = compile(gl.VERTEX_SHADER, VERTEX);
    const fs = compile(gl.FRAGMENT_SHADER, FRAGMENT);
    if (!vs || !fs) return null;
    const program = gl.createProgram();
    gl.attachShader(program, vs);
    gl.attachShader(program, fs);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return null;
    gl.useProgram(program);

    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    const aPos = gl.getAttribLocation(program, 'aPos');
    gl.enableVertexAttribArray(aPos);
    gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

    const u = Object.fromEntries(['uRes', 'uTime', 'uAmount', 'uWind', 'uSwirl', 'uPointer', 'uSource', 'uParallax']
        .map((name) => [name, gl.getUniformLocation(program, name)]));
    // The coffee's surface, in the canvas: frame x 412 of 160–680, y 600 of 30–622.
    gl.uniform2f(u.uSource, 0.485, 0.037);

    // Steam is soft: render at half resolution and let the browser smooth it up.
    const scale = Math.min(window.devicePixelRatio || 1, 1.5) * 0.5;
    const resize = () => {
        const w = Math.max(2, Math.round(canvas.clientWidth * scale));
        const h = Math.max(2, Math.round(canvas.clientHeight * scale));
        if (canvas.width === w && canvas.height === h) return;
        canvas.width = w;
        canvas.height = h;
        gl.viewport(0, 0, w, h);
        gl.uniform2f(u.uRes, w, h);
    };
    new ResizeObserver(resize).observe(canvas);

    let target = 0;
    let amount = 0;
    let windNow = 0;
    let windTarget = 0;
    let pointer = [-9, -9];
    let swirl = 0;
    let parallax = 0;
    let lastPointer = null;
    let shown = false;
    let raf = 0;
    const t0 = performance.now();

    const frame = () => {
        raf = 0;
        if (!shown) return;
        resize();
        amount += (target - amount) * 0.08;
        windNow += (windTarget - windNow) * 0.05;
        swirl *= 0.96;
        parallax += ((parseFloat(stage.style.getPropertyValue('--mx')) || 0) - parallax) * 0.1;

        if (amount < 0.002 && target === 0) {
            gl.clear(gl.COLOR_BUFFER_BIT);
            return;
        }
        gl.uniform1f(u.uTime, (performance.now() - t0) / 1000);
        gl.uniform1f(u.uAmount, amount);
        gl.uniform1f(u.uWind, windNow);
        gl.uniform1f(u.uSwirl, swirl);
        gl.uniform2f(u.uPointer, pointer[0], pointer[1]);
        gl.uniform2f(u.uParallax, parallax, 0);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        raf = requestAnimationFrame(frame);
    };
    const run = () => { if (!raf && shown) raf = requestAnimationFrame(frame); };

    canvas.addEventListener('webglcontextlost', (event) => event.preventDefault());

    return {
        amount(value) {
            target = clamp(value);
            run();
        },
        wind(value) { windTarget = value; },
        visible(value) {
            shown = value;
            run();
        },
        pointer(x, y) {
            if (x === null) {
                lastPointer = null;
                return;
            }
            const rect = canvas.getBoundingClientRect();
            const next = [(x - rect.left) / rect.width, 1 - (y - rect.top) / rect.height];
            if (lastPointer) {
                const speed = Math.hypot(next[0] - lastPointer[0], next[1] - lastPointer[1]);
                swirl = Math.min(1.2, swirl + speed * 9);
            }
            lastPointer = next;
            pointer = next;
        },
    };
}
