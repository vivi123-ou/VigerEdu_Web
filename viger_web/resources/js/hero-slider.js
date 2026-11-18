/* ═══════════════════════════════════════════════════════════════
   HERO SLIDER — điều khiển banner slideshow (.hs)
   - Tự chuyển slide, thanh tiến độ đồng bộ với bộ đếm giờ
   - Tạm dừng khi: rê chuột, focus bàn phím, tab bị ẩn, slider ra ngoài màn hình
   - Mũi tên, chấm, phím ← →, vuốt trên cảm ứng
   - Không tự chạy nếu người dùng bật "giảm chuyển động"
   API: element.heroSlider = { go(i), next(), prev() }
   ═══════════════════════════════════════════════════════════════ */
(function () {
  "use strict";

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var FADE_MS = 1150; // khớp với transition opacity trong CSS (1.1s)

  function pad(n) { return n < 10 ? "0" + n : "" + n; }

  function init(root) {
    var slides = Array.prototype.slice.call(root.querySelectorAll(".hs-slide"));
    var dots = Array.prototype.slice.call(root.querySelectorAll(".hs-dot"));
    var bar = root.querySelector(".hs-progress span");
    var cur = root.querySelector(".hs-count .cur");
    var n = slides.length;

    if (n < 2) { root.classList.add("hs-single"); return; }

    var delay = parseInt(root.getAttribute("data-autoplay"), 10) || 6000;
    var i = 0, timer = null, paused = false, startedAt = 0, remaining = delay;
    var holds = { hover: false, focus: false, hidden: document.hidden, offscreen: false };

    root.style.setProperty("--hs-duration", delay + "ms");

    function restartBar() {
      if (!bar) return;
      bar.style.animation = "none";
      void bar.offsetWidth; // ép trình duyệt tính lại để animation chạy từ đầu
      bar.style.animation = "";
    }

    function schedule(ms) {
      clearTimeout(timer);
      startedAt = performance.now();
      remaining = ms;
      timer = setTimeout(function () { go(i + 1); }, ms);
    }

    function go(k) {
      k = ((k % n) + n) % n;
      if (k === i) return;

      var prev = slides[i];
      prev.classList.remove("is-active");
      prev.classList.add("is-leaving");
      prev.setAttribute("aria-hidden", "true");
      setTimeout(function () { prev.classList.remove("is-leaving"); }, FADE_MS);
      if (dots[i]) { dots[i].classList.remove("is-active"); dots[i].removeAttribute("aria-current"); }

      i = k;
      slides[i].classList.remove("is-leaving");
      slides[i].classList.add("is-active");
      slides[i].setAttribute("aria-hidden", "false");
      if (dots[i]) { dots[i].classList.add("is-active"); dots[i].setAttribute("aria-current", "true"); }
      if (cur) cur.textContent = pad(i + 1);

      restartBar();
      if (paused) remaining = delay; else schedule(delay);
    }

    /* ── Tạm dừng / tiếp tục (giữ đúng thời gian còn lại) ─────── */
    function pause() {
      if (paused) return;
      paused = true;
      clearTimeout(timer);
      remaining = Math.max(0, remaining - (performance.now() - startedAt));
      root.classList.add("is-paused");
    }
    function resume() {
      if (!paused) return;
      paused = false;
      root.classList.remove("is-paused");
      schedule(remaining || delay);
    }
    function sync() {
      var hold = reduce || holds.hover || holds.focus || holds.hidden || holds.offscreen;
      if (hold) pause(); else resume();
    }

    /* ── Điều khiển ───────────────────────────────────────────── */
    var prevBtn = root.querySelector(".hs-prev");
    var nextBtn = root.querySelector(".hs-next");
    if (prevBtn) prevBtn.addEventListener("click", function () { go(i - 1); });
    if (nextBtn) nextBtn.addEventListener("click", function () { go(i + 1); });
    dots.forEach(function (d, idx) {
      d.addEventListener("click", function () { go(idx); });
    });

    root.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") { go(i - 1); e.preventDefault(); }
      else if (e.key === "ArrowRight") { go(i + 1); e.preventDefault(); }
    });

    // Rê chuột = tạm dừng
    root.addEventListener("mouseenter", function () { holds.hover = true; sync(); });
    root.addEventListener("mouseleave", function () { holds.hover = false; sync(); });

    // Chỉ tạm dừng khi focus bằng bàn phím (bấm chuột vào mũi tên không làm dừng mãi)
    root.addEventListener("focusin", function (e) {
      holds.focus = !!(e.target.matches && e.target.matches(":focus-visible"));
      sync();
    });
    root.addEventListener("focusout", function () { holds.focus = false; sync(); });

    // Tab bị ẩn / slider ra khỏi màn hình
    document.addEventListener("visibilitychange", function () { holds.hidden = document.hidden; sync(); });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        holds.offscreen = !entries[0].isIntersecting;
        sync();
      }, { threshold: 0.2 }).observe(root);
    }

    // Vuốt trái/phải trên màn hình cảm ứng
    var sx = 0, sy = 0, tracking = false;
    root.addEventListener("pointerdown", function (e) {
      if (e.pointerType === "mouse" || (e.target.closest && e.target.closest("a, button"))) return;
      tracking = true; sx = e.clientX; sy = e.clientY;
    });
    root.addEventListener("pointerup", function (e) {
      if (!tracking) return;
      tracking = false;
      var dx = e.clientX - sx, dy = e.clientY - sy;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) go(dx < 0 ? i + 1 : i - 1);
    });
    root.addEventListener("pointercancel", function () { tracking = false; });

    root.heroSlider = {
      go: go,
      next: function () { go(i + 1); },
      prev: function () { go(i - 1); },
    };

    /* ── Bắt đầu ──────────────────────────────────────────────── */
    schedule(delay);
    sync();
  }

  function boot() {
    Array.prototype.slice.call(document.querySelectorAll(".hs")).forEach(init);
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();