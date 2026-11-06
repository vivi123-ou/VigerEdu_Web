/* Nhúng khối "Trắc nghiệm Holland": <script src=".../embed.js" data-target="#holland"></script> */
(function () {
    'use strict';
    var script = document.currentScript;
    var selector = (script && script.getAttribute('data-target')) || '#holland';
    var embedUrl = @json($embedUrl);
    var embedOrigin = new URL(embedUrl).origin;

    function mount() {
        var target = document.querySelector(selector);
        if (!target || target.querySelector('iframe[data-holland]')) return;
        var frame = document.createElement('iframe');
        frame.src = embedUrl;
        frame.title = 'Trắc nghiệm Holland';
        frame.setAttribute('data-holland', '');
        frame.setAttribute('loading', 'lazy');
        frame.style.cssText = 'width:100%;border:0;display:block;min-height:220px;';
        target.appendChild(frame);

        window.addEventListener('message', function (event) {
            if (event.origin !== embedOrigin || !event.data || event.data.type !== 'holland-embed-height') return;
            frame.style.height = Math.ceil(event.data.height) + 'px';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
