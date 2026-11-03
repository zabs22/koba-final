import{$ as d,r as k,a as Y,o as V,f as X,c as M,p as G}from"./site-CTKzIXzz.js";const B=[0,.16,.36,.58,.8],U=289,D=349,$=297,z=612,j=634,K=50,J=74,i=(t,m,e)=>M((t-m)/(e-m)),H=t=>t*t,N=t=>1-(1-t)**3,C=t=>t<.5?4*t*t*t:1-(-2*t+2)**3/2;function rt(){const t=d("[data-pour]");if(!t||k)return;const m=d("[data-pour-stage]",t),e=d("[data-pour-frame]",t),p=d("[data-pour-pot]",t),s=d("[data-pour-stream]",t),x=d("[data-pour-liquid]",t),a=d("[data-pour-still]",t),R=d("[data-pour-vapour]",t),n=d("[data-pour-glow]",t),w=d("[data-pour-count]",t),F=Y("[data-pour-step]",t),T=Y("[data-pour-tick]",t);t.classList.add("is-live");const v=et(d("[data-pour-steam]",t),m);v&&t.classList.add("has-steam"),d("[data-pour-skip]",t)?.addEventListener("click",()=>{window.scrollTo({top:t.getBoundingClientRect().bottom+window.scrollY,behavior:"instant"})});let E=-1,_=window.scrollY,L=performance.now(),P=0;function O(o){m.style.setProperty("--pour-p",o.toFixed(4));const y=C(i(o,.13,.34)),g=C(i(o,.79,.93)),l=g>0?g*.85:1-y;p.style.transform=`translate3d(${(l*34).toFixed(2)}%, ${(l*-44).toFixed(2)}%, 0) rotate(${(l*16).toFixed(2)}deg) perspective(900px) rotateY(${(l*-18).toFixed(2)}deg)`,p.style.filter=l>.01?`blur(${(l*7).toFixed(2)}px)`:"",p.style.opacity=(1-i(l,.55,.95)).toFixed(3);const b=N(i(o,.43,.63)),q=(1-b)*K,r=Math.min(z+q,j),c=H(i(o,.37,.43)),u=H(i(o,.76,.81)),S=c>0&&u<1;if(S){const h=$-8+(r-$+8)*u,f=$+(r-$)*c;s.style.setProperty("--from",`${(M((h-U)/D)*100).toFixed(2)}%`),s.style.setProperty("--to",`${(M((f-U)/D)*100).toFixed(2)}%`),s.style.visibility=""}else s.style.visibility="hidden";x.style.transform=`translate3d(0, ${(q/J*100).toFixed(2)}%, 0) scale(${(.9+.1*b).toFixed(4)})`,x.style.visibility=b>0?"":"hidden";const I=i(o,.79,.825);a.style.opacity=I.toFixed(3),t.classList.toggle("is-pouring",S&&c>.9),n.style.opacity=(.3+.7*i(o,.4,.68)).toFixed(3),R.style.opacity=(.9*N(i(o,.5,.68))-.25*i(o,.86,1)).toFixed(3),v?.amount(.4*i(o,.43,.52)+.6*i(o,.52,.66)-.35*i(o,.86,1));const W=1.02+.02*i(o,0,.16)-.02*C(i(o,.16,.3))+.11*C(i(o,.55,.72))-.08*C(i(o,.8,.95));e.style.setProperty("--cam",W.toFixed(4));let A=0;B.forEach((h,f)=>{o>=h&&(A=f)}),T.forEach((h,f)=>h.style.setProperty("--f",i(o,B[f],B[f+1]??1).toFixed(3))),t.classList.toggle("is-done",o>.985),A!==E&&(E=A,F.forEach((h,f)=>{h.classList.toggle("is-current",f===A),h.classList.toggle("is-past",f<A)}),T.forEach((h,f)=>{h.classList.toggle("is-current",f===A),h.classList.toggle("is-past",f<A)}),w.textContent=G(A+1))}V(o=>{const y=t.getBoundingClientRect(),g=y.bottom>0&&y.top<o;if(v?.visible(g),!g)return;O(M(-y.top/Math.max(1,y.height-o)));const l=performance.now(),b=(window.scrollY-_)/Math.max(16,l-L);_=window.scrollY,L=l,P=M(P*.6+b*.12,-.5,.5),v?.wind(P)}),X&&Q(m,v)}function Q(t,m){let e=0,p=0,s=0,x=0,a=0;const R=()=>{a=0,s+=(e-s)*.07,x+=(p-x)*.07,t.style.setProperty("--mx",s.toFixed(4)),t.style.setProperty("--my",x.toFixed(4)),(Math.abs(e-s)>.002||Math.abs(p-x)>.002)&&(a=requestAnimationFrame(R))},n=()=>{a||(a=requestAnimationFrame(R))};t.addEventListener("pointermove",w=>{const F=t.getBoundingClientRect();e=((w.clientX-F.left)/F.width-.5)*2,p=((w.clientY-F.top)/F.height-.5)*2,m?.pointer(w.clientX,w.clientY),n()}),t.addEventListener("pointerleave",()=>{e=0,p=0,m?.pointer(null),n()})}const Z=`
attribute vec2 aPos;
void main() { gl_Position = vec4(aPos, 0.0, 1.0); }
`,tt=`
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
`;function et(t,m){if(!t)return null;const e=t.getContext("webgl",{alpha:!0,premultipliedAlpha:!0,antialias:!1,depth:!1,stencil:!1,powerPreference:"low-power"});if(!e)return null;const p=(r,c)=>{const u=e.createShader(r);return e.shaderSource(u,c),e.compileShader(u),e.getShaderParameter(u,e.COMPILE_STATUS)?u:null},s=p(e.VERTEX_SHADER,Z),x=p(e.FRAGMENT_SHADER,tt);if(!s||!x)return null;const a=e.createProgram();if(e.attachShader(a,s),e.attachShader(a,x),e.linkProgram(a),!e.getProgramParameter(a,e.LINK_STATUS))return null;e.useProgram(a),e.bindBuffer(e.ARRAY_BUFFER,e.createBuffer()),e.bufferData(e.ARRAY_BUFFER,new Float32Array([-1,-1,1,-1,-1,1,1,1]),e.STATIC_DRAW);const R=e.getAttribLocation(a,"aPos");e.enableVertexAttribArray(R),e.vertexAttribPointer(R,2,e.FLOAT,!1,0,0);const n=Object.fromEntries(["uRes","uTime","uAmount","uWind","uSwirl","uPointer","uSource","uParallax"].map(r=>[r,e.getUniformLocation(a,r)]));e.uniform2f(n.uSource,.485,.037);const w=Math.min(window.devicePixelRatio||1,1.5)*.5,F=()=>{const r=Math.max(2,Math.round(t.clientWidth*w)),c=Math.max(2,Math.round(t.clientHeight*w));t.width===r&&t.height===c||(t.width=r,t.height=c,e.viewport(0,0,r,c),e.uniform2f(n.uRes,r,c))};new ResizeObserver(F).observe(t);let T=0,v=0,E=0,_=0,L=[-9,-9],P=0,O=0,o=null,y=!1,g=0;const l=performance.now(),b=()=>{if(g=0,!!y){if(F(),v+=(T-v)*.08,E+=(_-E)*.05,P*=.96,O+=((parseFloat(m.style.getPropertyValue("--mx"))||0)-O)*.1,v<.002&&T===0){e.clear(e.COLOR_BUFFER_BIT);return}e.uniform1f(n.uTime,(performance.now()-l)/1e3),e.uniform1f(n.uAmount,v),e.uniform1f(n.uWind,E),e.uniform1f(n.uSwirl,P),e.uniform2f(n.uPointer,L[0],L[1]),e.uniform2f(n.uParallax,O,0),e.drawArrays(e.TRIANGLE_STRIP,0,4),g=requestAnimationFrame(b)}},q=()=>{!g&&y&&(g=requestAnimationFrame(b))};return t.addEventListener("webglcontextlost",r=>r.preventDefault()),{amount(r){T=M(r),q()},wind(r){_=r},visible(r){y=r,q()},pointer(r,c){if(r===null){o=null;return}const u=t.getBoundingClientRect(),S=[(r-u.left)/u.width,1-(c-u.top)/u.height];if(o){const I=Math.hypot(S[0]-o[0],S[1]-o[1]);P=Math.min(1.2,P+I*9)}o=S,L=S}}}export{rt as initPour};
