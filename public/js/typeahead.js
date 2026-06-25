// signups type-ahead — a dependency-free autocomplete that works where the
// native <datalist> dropdown doesn't, notably iOS Safari (which "supports"
// <datalist> but rarely renders it). It enhances any <input list="..."> by
// reading the linked <datalist>'s option values, then shows a filtered, tappable
// dropdown. The <datalist> stays in the markup as the no-JS fallback; when this
// runs it takes the list attribute off the input so the two don't both fire.
//
// On a phone people swipe the list more than they type, so selection is driven
// by gesture, not by pointerdown or click: a pointer that comes down and up on
// the same row without moving is a tap (select); any real drag is a scroll
// (select nothing). We avoid click because iOS frequently spends the first tap
// just dismissing the keyboard.
//
// Built with AI assistance (Claude, Anthropic).
(function () {
    'use strict';

    var MAX_MATCHES = 50;      // capped for a small DOM; the menu scrolls past this
    var MOVE_THRESHOLD = 8;    // px of travel that turns a tap into a scroll

    function enhance(input) {
        var listId = input.getAttribute('list');
        var datalist = listId && document.getElementById(listId);
        if (!datalist) return;

        // The <datalist> is the single source of truth — the server already
        // rendered the roster (minus anyone already on the sheet) into it, so
        // nothing here is duplicated.
        var names = Array.prototype.map.call(
            datalist.querySelectorAll('option'),
            function (o) { return o.value; }
        );
        if (!names.length) return;

        // Suppress the native datalist (desktop renders it) so we don't get two
        // overlapping dropdowns.
        input.removeAttribute('list');

        var menu = document.createElement('ul');
        menu.className = 'typeahead-menu';
        menu.hidden = true;
        // Placed inside the .add-row form, which is the positioning context.
        input.parentNode.insertBefore(menu, input.nextSibling);

        var active = -1;   // index of the keyboard-highlighted item

        function close() {
            menu.hidden = true;
            menu.innerHTML = '';
            active = -1;
        }

        function position() {
            // Anchor the menu under the input itself, computed each open so it
            // lands correctly even when the flex row wraps (the scouter row does).
            menu.style.top = (input.offsetTop + input.offsetHeight) + 'px';
            menu.style.left = input.offsetLeft + 'px';
            menu.style.width = input.offsetWidth + 'px';
        }

        function open(matches) {
            menu.innerHTML = '';
            matches.forEach(function (name) {
                var li = document.createElement('li');
                li.textContent = name;
                li.setAttribute('role', 'option');
                menu.appendChild(li);
            });
            active = -1;
            if (matches.length) {
                position();
                menu.hidden = false;
            } else {
                close();
            }
        }

        function choose(name) {
            input.value = name;
            // No refocus: keeps the soft keyboard down after a pick and stops
            // the focus handler from immediately reopening the menu.
            close();
        }

        function filter() {
            var q = input.value.trim().toLowerCase();
            // With no text, offer the whole (remaining) roster so you can tap to
            // browse — friendlier on a phone than forcing you to type first.
            var matches = names.filter(function (n) {
                return q === '' || n.toLowerCase().indexOf(q) !== -1;
            }).slice(0, MAX_MATCHES);
            open(matches);
        }

        function highlight() {
            var items = menu.children;
            for (var i = 0; i < items.length; i++) {
                items[i].classList.toggle('active', i === active);
            }
            if (active >= 0) items[active].scrollIntoView({ block: 'nearest' });
        }

        // --- Tap vs. swipe on the menu -------------------------------------
        // Watch the whole gesture rather than acting on pointerdown. A scroll
        // either moves past the threshold (moved=true) or the browser claims the
        // pointer to scroll (pointercancel) — both leave nothing selected.
        var downLi = null, startX = 0, startY = 0, moved = false;
        menu.addEventListener('pointerdown', function (e) {
            downLi = e.target.closest('li');
            moved = false;
            startX = e.clientX;
            startY = e.clientY;
        });
        menu.addEventListener('pointermove', function (e) {
            if (Math.abs(e.clientX - startX) > MOVE_THRESHOLD ||
                Math.abs(e.clientY - startY) > MOVE_THRESHOLD) {
                moved = true;
            }
        });
        menu.addEventListener('pointerup', function () {
            if (!moved && downLi) choose(downLi.textContent);
            downLi = null;
        });
        menu.addEventListener('pointercancel', function () { downLi = null; });

        // --- Input wiring --------------------------------------------------
        input.addEventListener('input', filter);
        input.addEventListener('focus', filter);
        input.addEventListener('keydown', function (e) {
            if (menu.hidden) return;
            var items = menu.children;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                active = Math.min(active + 1, items.length - 1);
                highlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                active = Math.max(active - 1, 0);
                highlight();
            } else if (e.key === 'Enter') {
                if (active >= 0) {
                    e.preventDefault();
                    choose(items[active].textContent);
                }
            } else if (e.key === 'Escape' || e.key === 'Tab') {
                close();
            }
        });

        // Tap or click outside this field and its menu closes it. This replaces
        // closing on blur, which fired mid-swipe and shut the menu before you
        // could scroll it.
        document.addEventListener('pointerdown', function (e) {
            if (e.target !== input && !menu.contains(e.target)) close();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('input[list]'),
            enhance
        );
    });
})();
