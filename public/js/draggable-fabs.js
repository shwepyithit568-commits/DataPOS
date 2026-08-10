/* ---------------------------------------------------------------------------
 * draggable-fabs.js
 *
 * Makes the storefront floating action buttons draggable:
 *   - the push-notification bell (#push-notification-bell)
 *   - the chat/contact FAB ([data-draggable-fab="chat"])
 *
 * Behaviour:
 *   - Press + drag to reposition (mouse, touch, pen).
 *   - The new position is saved to localStorage and restored on reload.
 *   - A small movement threshold (6px) keeps normal taps/clicks intact.
 *   - The click that follows a drag is suppressed so the button's own
 *     action (open chat popup / enable notifications) does not fire.
 *   - Position is clamped to the viewport.
 * ------------------------------------------------------------------------- */
(function () {
    'use strict';

    var DRAG_THRESHOLD = 6; // px of movement before it becomes a drag
    var PADDING = 4;

    function makeDraggable(el, storageKey) {
        if (!el || el.dataset.draggableReady) return;
        el.dataset.draggableReady = '1';

        var startX = 0, startY = 0, origLeft = 0, origTop = 0;
        var dragging = false, moved = false;
        var savedTransform = '', savedTransition = '';

        function restorePosition() {
            try {
                var raw = localStorage.getItem(storageKey);
                if (!raw) return;
                var pos = JSON.parse(raw);
                if (typeof pos.x !== 'number' || typeof pos.y !== 'number') return;
                el.style.left = pos.x + 'px';
                el.style.top = pos.y + 'px';
                el.style.right = 'auto';
                el.style.bottom = 'auto';
            } catch (e) { /* storage blocked or corrupted — ignore */ }
        }

        function clamp(x, y) {
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            var w = el.offsetWidth;
            var h = el.offsetHeight;
            return [
                Math.max(PADDING, Math.min(x, vw - w - PADDING)),
                Math.max(PADDING, Math.min(y, vh - h - PADDING)),
            ];
        }

        restorePosition();

        el.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            dragging = true;
            moved = false;
            startX = e.clientX;
            startY = e.clientY;
            var rect = el.getBoundingClientRect();
            origLeft = rect.left;
            origTop = rect.top;
            savedTransform = el.style.transform;
            savedTransition = el.style.transition;
            el.style.touchAction = 'none';
            try { el.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
            e.preventDefault();
        });

        el.addEventListener('pointermove', function (e) {
            if (!dragging) return;
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            if (!moved && Math.abs(dx) + Math.abs(dy) > DRAG_THRESHOLD) {
                moved = true;
                el.style.transition = 'none';
                el.style.transform = 'none';
            }
            if (!moved) return;
            e.preventDefault();
            var pos = clamp(origLeft + dx, origTop + dy);
            el.style.left = pos[0] + 'px';
            el.style.top = pos[1] + 'px';
            el.style.right = 'auto';
            el.style.bottom = 'auto';
        });

        function endDrag() {
            if (!dragging) return;
            dragging = false;
            el.style.touchAction = '';
            el.style.transition = savedTransition;
            el.style.transform = savedTransform;
            if (moved) {
                try {
                    localStorage.setItem(storageKey, JSON.stringify({
                        x: parseInt(el.style.left, 10),
                        y: parseInt(el.style.top, 10),
                    }));
                } catch (err) { /* ignore */ }
                // Some touch browsers do not fire a click after a drag; clear
                // the suppression flag shortly after so the next tap is not
                // accidentally swallowed.
                setTimeout(function () { moved = false; }, 500);
            }
        }

        el.addEventListener('pointerup', endDrag);
        el.addEventListener('pointercancel', endDrag);

        /* Suppress the click that follows a drag (capture phase, so it also
         * stops the event from reaching the button's own @click handlers). */
        el.addEventListener('click', function (e) {
            if (moved) {
                moved = false;
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        }, true);
    }

    function init() {
        makeDraggable(
            document.getElementById('push-notification-bell'),
            'datapos-fab-bell'
        );
        makeDraggable(
            document.querySelector('[data-draggable-fab="chat"]'),
            'datapos-fab-chat'
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
