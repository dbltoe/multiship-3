/* -----
   Back-to-top control for this readme.

   Replaces back_to_top.min.js, a packed jQuery plugin whose payload was unpacked at runtime by a dynamic-code call.
   Zen Cart's plugin security scan flagged that, and it was right to: shipping obfuscated
   JavaScript inside a plugin is indistinguishable, to a scanner or a reviewer, from shipping
   something worth hiding. This does the same job in code anyone can read, needs no jQuery,
   and lets the readme drop its CDN script tag along with it.

   The markup it builds is what style.css already expects -- an anchor with id BackToTop
   containing a span, hidden until the page has been scrolled. An anchor rather than a button
   because it goes somewhere: #top exists at the head of the document, so this still works
   with scripting off, and keyboard and screen-reader users get a real link.

   Motion is smooth unless the reader has asked their system for less of it.
   ----------------------------------------------------------------------------------------- */
(function () {
    'use strict';

    var SHOW_AFTER = 200;

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        if (document.getElementById('BackToTop') !== null) {
            return;
        }

        var link = document.createElement('a');
        link.id = 'BackToTop';
        link.href = '#top';

        var label = document.createElement('span');
        label.appendChild(document.createTextNode('Back to Top'));
        link.appendChild(label);

        document.body.appendChild(link);

        function toggle() {
            var y = window.pageYOffset || document.documentElement.scrollTop || 0;
            link.style.display = (y > SHOW_AFTER) ? 'block' : 'none';
        }

        link.addEventListener('click', function (e) {
            var reduced = window.matchMedia &&
                          window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Let the browser follow the href when it cannot scroll smoothly for us, so the
            // control still works rather than doing nothing.
            if (typeof window.scrollTo !== 'function') {
                return;
            }
            e.preventDefault();
            try {
                window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
            } catch (err) {
                window.scrollTo(0, 0);
            }
            // Move focus with the view, or a keyboard user is returned to the top visually
            // and left at the bottom of the document.
            var top = document.getElementById('top');
            if (top !== null && typeof top.focus === 'function') {
                top.setAttribute('tabindex', '-1');
                top.focus({ preventScroll: true });
            }
        });

        window.addEventListener('scroll', toggle);
        toggle();
    });
})();
