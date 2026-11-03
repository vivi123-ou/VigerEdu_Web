/* ═══════════════════════════════════════════════════════════════
   MOUSE FX — bộ hiệu ứng tương tác chuột (vanilla JS, không thư viện)

   1. Glow       : đốm sáng đỏ + vàng "đuổi" theo chuột (cần .mouse-glow)
   2. Cursor     : chấm + vòng con trỏ tuỳ chỉnh, phình to khi rê vào link
   3. Trail      : hạt sáng đỏ/vàng bắn ra theo tốc độ chuột (canvas)
   4. Ripple     : gợn sóng tại điểm bấm
   5. Tilt 3D    : thẻ nghiêng theo chuột + đốm sáng bên trong + phần tử nổi
   6. Magnetic   : nút bị hút nhẹ về phía con trỏ
   7. Parallax   : ảnh/tiêu đề hero + các orb trôi theo chuột

   Tự tắt trên màn hình cảm ứng và khi người dùng bật "giảm chuyển động".
   Chỉnh nhanh ở FX và SEL bên dưới.
   ═══════════════════════════════════════════════════════════════ */
(function () {
  "use strict";

  var fine = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (!fine || reduce) return;

  /* ── Bật/tắt từng hiệu ứng ─────────────────────────────────── */
  var FX = {
    glow: true,
    cursor: true,
    trail: true,
    ripple: true,
    tilt: true,
    magnetic: true,
    parallax: true,
  };

  /* ── Phần tử nào nhận hiệu ứng nào ─────────────────────────── */
  var SEL = {
    tilt: ".testimonial-card, .stat-box, .timeline-step, .grid-img",
    spot: ".testimonial-card, .stat-box", // thẻ được chèn lớp đốm sáng
    magnetic: ".btn-red, .btn-outline-custom, .cta-pill, .link-custom, .link-more",
    interactive: 'a, button, [role="button"], label[for], summary',
    text: 'input, textarea, select, [contenteditable="true"]',
    hero: ".hero-section",
  };

  var TILT_MAX = 9; // độ nghiêng tối đa (deg)
  var MAG_PULL = 0.3; // lực hút của nút từ tính
  var MAG_MAX = 14; // px dịch tối đa
  var MAG_REACH = 60; // px: bán kính hút tính từ mép nút
  var TRAIL_MAX = 90; // số hạt tối đa
  var COLORS = ["193,18,31", "255,183,3", "255,120,80", "255,255,255"];

  var root = document.documentElement;
  var lerp = function (a, b, t) { return a + (b - a) * t; };
  var clamp = function (v, a, b) { return Math.min(b, Math.max(a, v)); };
  var rand = function (a, b) { return a + Math.random() * (b - a); };
  var $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };
  function make(tag, cls) {
    var n = document.createElement(tag);
    n.className = cls;
    return n;
  }

  /* ── Trạng thái dùng chung ─────────────────────────────────── */
  var mx = window.innerWidth / 2;
  var my = window.innerHeight / 2;
  var seen = false;
  var ring = { x: mx, y: my };
  var glowA = { x: mx, y: my };
  var glowB = { x: mx, y: my };
  var running = false;

  function wake() {
    if (!running) {
      running = true;
      requestAnimationFrame(tick);
    }
  }

  /* ════════ 1. GLOW ════════ */
  var glowEl = FX.glow ? document.querySelector(".mouse-glow") : null;
  var glowSpeed = 0.07, glowSize = 700, glowColor = "193 18 31", glowOpacity = 0.14;
  if (glowEl) {
    var cs = getComputedStyle(glowEl);
    glowSpeed = parseFloat(glowEl.getAttribute("data-glow-speed")) || glowSpeed;
    glowSize = parseFloat(cs.getPropertyValue("--glow-size")) || glowSize;
    glowColor = cs.getPropertyValue("--glow-color").trim() || glowColor;
    glowOpacity = parseFloat(cs.getPropertyValue("--glow-opacity")) || glowOpacity;
  }
  function paintGlow() {
    // Đốm chính (đỏ, lớn, chậm) + đốm phụ (vàng, nhỏ, còn chậm hơn) → hiệu ứng "sao chổi"
    glowEl.style.setProperty(
      "--_glow-pos",
      "radial-gradient(circle " + glowSize + "px at " + glowA.x.toFixed(1) + "px " + glowA.y.toFixed(1) +
        "px, rgb(" + glowColor + " / " + glowOpacity + "), transparent 70%)," +
        "radial-gradient(circle 260px at " + glowB.x.toFixed(1) + "px " + glowB.y.toFixed(1) +
        "px, rgb(255 183 3 / 0.12), transparent 70%)"
    );
  }

  /* ════════ 2. CURSOR ════════ */
  var dot = null, ringEl = null;
  if (FX.cursor) {
    dot = make("div", "fx-cursor-dot");
    ringEl = make("div", "fx-cursor-ring");
    document.body.appendChild(dot);
    document.body.appendChild(ringEl);
  }
  var lastHover = "", lastText = false;
  function updateHover(target) {
    if (!ringEl || !target || !target.closest) return;
    var isText = !!target.closest(SEL.text);
    if (isText !== lastText) {
      lastText = isText;
      root.classList.toggle("fx-text", isText);
    }
    var state = "";
    if (target.closest(SEL.interactive)) state = "hover";
    else if (target.closest(".fx-tilt")) state = "card";
    if (state !== lastHover) {
      lastHover = state;
      ringEl.classList.toggle("fx-hover", state === "hover");
      ringEl.classList.toggle("fx-card", state === "card");
    }
  }

  /* ════════ 3. TRAIL (canvas) ════════ */
  var cv = null, ctx = null, dpr = 1, parts = [];
  if (FX.trail) {
    cv = make("canvas", "fx-canvas");
    document.body.appendChild(cv);
    ctx = cv.getContext("2d");
    var resize = function () {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      cv.width = window.innerWidth * dpr;
      cv.height = window.innerHeight * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener("resize", resize);
  }
  function spawn(dist) {
    var n = Math.min(3, Math.floor(dist / 9));
    for (var i = 0; i < n; i++) {
      parts.push({
        x: mx + rand(-4, 4), y: my + rand(-4, 4),
        vx: rand(-0.7, 0.7), vy: rand(-0.7, 0.4),
        life: 0, max: rand(28, 56), size: rand(1.6, 4.4),
        c: COLORS[(Math.random() * COLORS.length) | 0],
      });
    }
    if (parts.length > TRAIL_MAX) parts.splice(0, parts.length - TRAIL_MAX);
  }
  var hadParts = false;
  function drawParts() {
    if (!ctx) return false;
    if (!parts.length) {
      if (hadParts) {
        ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
        hadParts = false;
      }
      return false;
    }
    hadParts = true;
    ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
    for (var i = parts.length - 1; i >= 0; i--) {
      var p = parts[i];
      p.life++;
      p.x += p.vx; p.y += p.vy; p.vy += 0.012;
      var t = p.life / p.max;
      if (t >= 1) { parts.splice(i, 1); continue; }
      ctx.beginPath();
      ctx.arc(p.x, p.y, Math.max(0.2, p.size * (1 - t * 0.6)), 0, 6.2832);
      ctx.fillStyle = "rgba(" + p.c + "," + (0.9 * (1 - t)).toFixed(3) + ")";
      ctx.shadowColor = "rgba(" + p.c + ",0.8)";
      ctx.shadowBlur = 10;
      ctx.fill();
    }
    return true;
  }

  /* ════════ 4. RIPPLE ════════ */
  function ripple(x, y) {
    var r = make("span", "fx-ripple");
    r.style.left = x + "px";
    r.style.top = y + "px";
    document.body.appendChild(r);
    r.addEventListener("animationend", function () { r.remove(); });
  }

  /* ════════ 5. TILT 3D + SPOTLIGHT ════════ */
  function initTilt() {
    $$(SEL.tilt).forEach(function (el) {
      el.classList.add("fx-tilt");

      // Lớp đốm sáng bên trong thẻ (không áp dụng cho <img>)
      if (el.matches(SEL.spot) && el.tagName !== "IMG") {
        if (getComputedStyle(el).position === "static") el.style.position = "relative";
        var spot = make("span", "fx-spot");
        spot.setAttribute("aria-hidden", "true");
        el.appendChild(spot);
      }

      var timer = null;
      el.addEventListener("pointerenter", function () {
        clearTimeout(timer);
        el.classList.add("fx-tilting");
        el.style.transition = "transform .12s ease-out, box-shadow .3s ease";
      });
      el.addEventListener("pointermove", function (e) {
        var r = el.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width;
        var py = (e.clientY - r.top) / r.height;
        el.style.setProperty("--mx", e.clientX - r.left + "px");
        el.style.setProperty("--my", e.clientY - r.top + "px");
        el.style.transform =
          "perspective(900px) rotateX(" + ((0.5 - py) * 2 * TILT_MAX).toFixed(2) +
          "deg) rotateY(" + ((px - 0.5) * 2 * TILT_MAX).toFixed(2) +
          "deg) scale3d(1.03,1.03,1.03)";
      });
      el.addEventListener("pointerleave", function () {
        el.classList.remove("fx-tilting");
        el.style.transition = "transform .7s cubic-bezier(.2,.8,.2,1), box-shadow .3s ease";
        el.style.transform = "perspective(900px) rotateX(0deg) rotateY(0deg) scale3d(1,1,1)";
        // Sau khi về vị trí cũ, trả quyền điều khiển lại cho CSS gốc
        timer = setTimeout(function () {
          el.style.transform = "";
          el.style.transition = "";
        }, 740);
      });
    });
  }

  /* ════════ 6. MAGNETIC ════════ */
  var mags = [];
  function initMagnetic() {
    mags = $$(SEL.magnetic).map(function (el) {
      el.classList.add("fx-magnetic");
      return { el: el, x: 0, y: 0, tx: 0, ty: 0 };
    });
  }

  /* ════════ 7. PARALLAX (hero) ════════ */
  var hero = FX.parallax ? document.querySelector(SEL.hero) : null;
  var layers = [];
  var par = { x: 0, y: 0 }, parT = { x: 0, y: 0 };
  function initParallax() {
    if (!hero) return;

    // Các orb sáng trôi phía sau nội dung hero
    var wrap = make("div", "fx-orbs");
    wrap.setAttribute("aria-hidden", "true");
    ["o1", "o2", "o3"].forEach(function (c) { wrap.appendChild(make("div", "fx-orb " + c)); });
    hero.insertBefore(wrap, hero.firstChild);
    var orbs = wrap.children;

    // dx/dy: px dịch tối đa theo mỗi trục. Âm = trôi ngược hướng chuột.
    var defs = [
      [orbs[0], 70, 50],
      [orbs[1], -55, 40],
      [orbs[2], 85, -45],
      [hero.querySelector(".hero-image img"), -20, -14],
      [hero.querySelector(".hero-heading"), 9, 6],
      [hero.querySelector(".hero-subheading"), 14, 8],
      [hero.querySelector(".hero-desc"), 5, 3],
    ];
    layers = defs
      .filter(function (d) { return d[0]; })
      .map(function (d) { return { el: d[0], dx: d[1], dy: d[2] }; });
  }

  /* ── Tính lại mục tiêu của nút từ tính + parallax ───────────── */
  function retarget() {
    mags.forEach(function (m) {
      var r = m.el.getBoundingClientRect();
      if (!r.width) { m.tx = m.ty = 0; return; }
      // Trừ phần đang dịch để lấy tâm "gốc" của nút
      var cx = r.left + r.width / 2 - m.x;
      var cy = r.top + r.height / 2 - m.y;
      var dx = mx - cx, dy = my - cy;
      var reach = Math.max(r.width, r.height) / 2 + MAG_REACH;
      if (Math.hypot(dx, dy) < reach) {
        m.tx = clamp(dx * MAG_PULL, -MAG_MAX, MAG_MAX);
        m.ty = clamp(dy * MAG_PULL, -MAG_MAX, MAG_MAX);
      } else {
        m.tx = m.ty = 0;
      }
    });

    if (hero) {
      var h = hero.getBoundingClientRect();
      var inside = my >= h.top && my <= h.bottom && mx >= h.left && mx <= h.right;
      parT.x = inside ? clamp((mx - (h.left + h.width / 2)) / (h.width / 2), -1, 1) : 0;
      parT.y = inside ? clamp((my - (h.top + h.height / 2)) / (h.height / 2), -1, 1) : 0;
    }
  }

  /* ════════ VÒNG LẶP CHÍNH ════════ */
  function tick() {
    var busy = false;

    // Cursor: chấm bám sát, vòng đuổi chậm
    ring.x = lerp(ring.x, mx, 0.18);
    ring.y = lerp(ring.y, my, 0.18);
    if (dot) dot.style.transform = "translate3d(" + mx + "px," + my + "px,0)";
    if (ringEl) ringEl.style.transform = "translate3d(" + ring.x.toFixed(2) + "px," + ring.y.toFixed(2) + "px,0)";
    if (Math.abs(mx - ring.x) + Math.abs(my - ring.y) > 0.15) busy = true;

    // Glow
    if (glowEl) {
      glowA.x = lerp(glowA.x, mx, glowSpeed);
      glowA.y = lerp(glowA.y, my, glowSpeed);
      glowB.x = lerp(glowB.x, mx, glowSpeed * 0.45);
      glowB.y = lerp(glowB.y, my, glowSpeed * 0.45);
      paintGlow();
      if (Math.abs(mx - glowB.x) + Math.abs(my - glowB.y) > 0.3) busy = true;
    }

    // Magnetic (dùng thuộc tính `translate` riêng, không đụng tới `transform` của CSS gốc)
    for (var i = 0; i < mags.length; i++) {
      var m = mags[i];
      m.x = lerp(m.x, m.tx, 0.2);
      m.y = lerp(m.y, m.ty, 0.2);
      if (Math.abs(m.x - m.tx) < 0.05 && Math.abs(m.y - m.ty) < 0.05) { m.x = m.tx; m.y = m.ty; }
      else busy = true;
      m.el.style.translate = m.x || m.y ? m.x.toFixed(2) + "px " + m.y.toFixed(2) + "px" : "";
    }

    // Parallax
    if (layers.length) {
      par.x = lerp(par.x, parT.x, 0.08);
      par.y = lerp(par.y, parT.y, 0.08);
      if (Math.abs(par.x - parT.x) + Math.abs(par.y - parT.y) > 0.002) busy = true;
      for (var j = 0; j < layers.length; j++) {
        var L = layers[j];
        L.el.style.translate = (par.x * L.dx).toFixed(2) + "px " + (par.y * L.dy).toFixed(2) + "px";
      }
    }

    // Particles
    if (drawParts()) busy = true;

    running = busy;
    if (busy) requestAnimationFrame(tick);
  }

  /* ════════ SỰ KIỆN ════════ */
  window.addEventListener(
    "pointermove",
    function (e) {
      if (e.pointerType === "touch") return;
      var px = mx, py = my;
      mx = e.clientX;
      my = e.clientY;

      if (!seen) {
        seen = true;
        ring.x = glowA.x = glowB.x = mx;
        ring.y = glowA.y = glowB.y = my;
        if (FX.cursor) root.classList.add("fx-on");
      }
      root.classList.add("fx-seen");

      if (FX.trail) spawn(Math.hypot(mx - px, my - py));
      updateHover(e.target);
      retarget();
      wake();
    },
    { passive: true }
  );

  window.addEventListener("scroll", function () { if (seen) { retarget(); wake(); } }, { passive: true });
  root.addEventListener("mouseleave", function () {
    root.classList.remove("fx-seen");
    mags.forEach(function (m) { m.tx = m.ty = 0; });
    parT.x = parT.y = 0;
    wake();
  });
  root.addEventListener("mouseenter", function () { if (seen) root.classList.add("fx-seen"); });

  window.addEventListener("pointerdown", function (e) {
    if (e.pointerType === "touch") return;
    if (ringEl) ringEl.classList.add("fx-down");
    if (FX.ripple) ripple(e.clientX, e.clientY);
    // Bắn một chùm hạt khi bấm
    if (FX.trail) { for (var i = 0; i < 14; i++) { spawn(40); } wake(); }
  });
  window.addEventListener("pointerup", function () {
    if (ringEl) ringEl.classList.remove("fx-down");
  });

  /* ── Khởi tạo ─────────────────────────────────────────────── */
  function init() {
    if (FX.tilt) initTilt();
    if (FX.magnetic) initMagnetic();
    initParallax();
    if (glowEl) paintGlow();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();