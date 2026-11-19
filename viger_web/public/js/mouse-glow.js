/**
 * mouse-glow.js — Hieu ung dom sang theo con tro chuot
 * -------------------------------------------------------
 * Tuy bien qua CSS custom properties tren phan tu .mouse-glow:
 *   --glow-color   : mau tam dom sang  (mac dinh: 193 18 31  -> do thuong hieu)
 *   --glow-size    : ban kinh gradient  (mac dinh: 600px)
 *   --glow-opacity : do mo toi da       (mac dinh: 0.18)
 * Tuy bien toc do qua data-glow-speed (mac dinh: 0.08, nho = cham/muot hon)
 */
(function () {
  "use strict";

  var DEFAULTS = {
    color:   "193 18 31",
    size:    "600px",
    opacity: "0.18",
    speed:   0.08,
  };

  document.addEventListener("DOMContentLoaded", function () {
    var container = document.querySelector(".mouse-glow");
    if (!container) return;

    var speed   = parseFloat(container.dataset.glowSpeed) || DEFAULTS.speed;
    var style   = getComputedStyle(container);
    var color   = style.getPropertyValue("--glow-color").trim()   || DEFAULTS.color;
    var size    = style.getPropertyValue("--glow-size").trim()    || DEFAULTS.size;
    var opacity = style.getPropertyValue("--glow-opacity").trim() || DEFAULTS.opacity;

    var targetX  = window.innerWidth  / 2;
    var targetY  = window.innerHeight / 2;
    var currentX = targetX;
    var currentY = targetY;
    var rafId    = null;

    document.addEventListener("mousemove", function (e) {
      targetX = e.clientX;
      targetY = e.clientY;
    }, { passive: true });

    document.addEventListener("touchmove", function (e) {
      if (e.touches.length > 0) {
        targetX = e.touches[0].clientX;
        targetY = e.touches[0].clientY;
      }
    }, { passive: true });

    function tick() {
      currentX += (targetX - currentX) * speed;
      currentY += (targetY - currentY) * speed;

      var rect = container.getBoundingClientRect();
      var pctX = ((currentX - rect.left) / rect.width)  * 100;
      var pctY = ((currentY - rect.top)  / rect.height) * 100;

      container.style.setProperty(
        "--_glow-pos",
        "radial-gradient(circle " + size + " at " + pctX.toFixed(2) + "% " + pctY.toFixed(2) + "%, " +
          "rgba(" + color + " / " + opacity + ") 0%, " +
          "transparent 70%" +
        ")"
      );

      rafId = requestAnimationFrame(tick);
    }

    document.addEventListener("visibilitychange", function () {
      if (document.hidden) {
        cancelAnimationFrame(rafId);
      } else {
        rafId = requestAnimationFrame(tick);
      }
    });

    rafId = requestAnimationFrame(tick);
  });
})();
