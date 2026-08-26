<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Keeps the customer's place when choosing a delivery address reloads the page.
//
// Each Send To menu submits the form on change, so a five-item order means five reloads.
// The browser lands wherever it lands, and the customer has to find their way back down
// the grid every time. On a page whose whole job is working down a list, that is the
// difference between a task and a chore.
//
// The previous attempt pointed the form's action at the next row's anchor --
// form.action = form.action.split('#')[0] + '#multishipRow3' -- so the reload would land
// there. It did not hold: the fragment of a POST target is not reliably applied, and even
// where it is, an anchor scroll is still a visible jump to somewhere the customer did not
// ask to go.
//
// This restores the scroll position instead, so the page comes back exactly where it was
// and the reload costs no movement at all. Nothing to land on, nothing to jump to.
//
// -----
// Focus is restored as well, and that is the half that matters most.
//
// A reload puts keyboard focus back at the start of the document. A customer working down
// this grid with the keyboard would be thrown to the top of the page on every single
// choice and have to tab all the way back -- once per item. Returning focus to the next
// menu means the reload is invisible to them too: choose, and the next one is ready.
//
// Focus moves to the next *unanswered* menu rather than simply the next one, so a customer
// revisiting the page to change one address is not dragged onward through rows already
// done. When everything is answered, focus goes to the control that leaves the page.
//
if (empty($_SESSION['multiship']) || !$_SESSION['multiship']->isChosen()) {
    return;
}
?>
<script>
(function () {
    'use strict';

    var KEY = 'multishipGridPosition';

    // Called from each menu's onchange, before the form is submitted. Guarded at the call
    // site, so if this file is missing the submit still happens -- the customer simply gets
    // the old behavior rather than a broken page.
    window.multishipRemember = function () {
        try {
            window.sessionStorage.setItem(KEY, String(window.pageYOffset || 0));
        } catch (e) {
            // Private browsing can refuse sessionStorage. Losing the position is not worth
            // stopping the submit for.
        }
    };

    function restore() {
        // -----
        // The stored value is a marker, not a destination.
        //
        // Its presence says "this load followed a choice on this page", which is what stops
        // any of the below happening on a first arrival -- a customer landing here should
        // see the top of the page, the shipping choices and the instructions, not be thrown
        // into the middle of the grid.
        //
        // An earlier version scrolled back to the stored position, on the reasoning that
        // moving the customer is an interruption. On a page whose entire job is working down
        // a list it is the opposite: they have just answered one row and the next is what
        // they want. Restoring the old position left them looking at the row they had
        // finished with, and dbltoe reported exactly that -- selecting a shipping method
        // "highlights the first address block but does not take you there", and address one
        // "does not take you to address 2".
        //
        var marked = null;
        try {
            marked = window.sessionStorage.getItem(KEY);
            window.sessionStorage.removeItem(KEY);
        } catch (e) {
            return;
        }
        if (marked === null) {
            return;
        }

        // -----
        // Where the customer was, kept as a fallback rather than discarded.
        //
        // multishipRemember() records the scroll offset and, until now, nothing ever read it
        // back -- restore() only tested the value for existence and then moved to a computed
        // target instead. That is right whenever a target is found. But three paths below
        // reach the end without one: no menus match the selector, everything is answered and
        // no Continue link is present, or the browser has no scrollIntoView. Each returned
        // having scrolled nowhere, which leaves the customer at the top of a page they were
        // working their way down -- the one place they certainly did not ask to be.
        //
        // Falling back to the remembered offset makes the worst case "stay where you were".
        // That is never worse than the top of the page, and it is what the value was being
        // recorded for in the first place.
        //
        var remembered = parseInt(marked, 10);
        function keepPlace() {
            if (!isNaN(remembered) && remembered > 0) {
                window.scrollTo(0, remembered);
            }
        }

        var menus = document.querySelectorAll('#multishipTable select[name="address[]"]');
        var target = null;
        for (var i = 0; i < menus.length; i++) {
            if (menus[i].value === '') {
                target = menus[i];
                break;
            }
        }

        // -----
        // Nothing left unanswered: the work is done and the only thing left is to leave, so
        // go to the button that does it. It can easily be below the fold on a long grid.
        //
        // Only the Continue link is looked for. Save Addresses used to be the fallback here,
        // but it now lives inside <noscript> -- so by definition it never exists on any load
        // that runs this code, and naming it would be searching for something that cannot be
        // there.
        //
        if (target === null) {
            target = document.querySelector('#multishipContinue a');
        }
        if (target === null) {
            keepPlace();
            return;
        }

        // Scroll first, then focus without scrolling again -- focus() would otherwise land
        // the element wherever the browser chooses rather than where this puts it.
        if (typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ block: 'center' });
        } else {
            keepPlace();
        }
        if (typeof target.focus === 'function') {
            target.focus({ preventScroll: true });
        }
    }

    // -----
    // Wait until the document can actually be scrolled before trying to scroll it.
    //
    // This ran at DOMContentLoaded and did nothing at all on responsive_classic. That
    // template's html_header.php sets <html class="no-fouc"> while the head is still
    // parsing, its stylesheet declares .no-fouc { display: none }, and the class is not
    // removed until a jQuery handler in jscript_responsive_framework.php runs. So at
    // DOMContentLoaded the whole document is display:none: it has no layout boxes, so
    // scrollIntoView() has nothing to scroll to, and no scroll height, so scrollTo() clamps
    // to zero. The class then comes off, the page paints, and the customer is at the top --
    // on every single selection. ZCA has no such guard, which is why this was invisible
    // there and why the two templates disagreed.
    //
    // The test is for a scrollable document rather than for the no-fouc class, because that
    // name belongs to one template and the problem belongs to any template that hides the
    // body while loading. Polling by animation frame costs a frame or two in the normal case
    // and keeps the scroll immediate; window.load is the backstop for a page that never
    // becomes taller than the viewport, and the attempt cap stops a short page waiting
    // forever. restore() clears the stored marker, so the guard below is what keeps the two
    // entry points from both acting on it.
    //
    var ran = false;
    function runOnce() {
        if (ran) {
            return;
        }
        ran = true;
        restore();
    }

    function whenScrollable(attemptsLeft) {
        if (ran) {
            return;
        }
        if (attemptsLeft <= 0 || document.documentElement.scrollHeight > window.innerHeight) {
            runOnce();
            return;
        }
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () { whenScrollable(attemptsLeft - 1); });
        } else {
            window.setTimeout(function () { whenScrollable(attemptsLeft - 1); }, 16);
        }
    }

    window.addEventListener('load', runOnce);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { whenScrollable(90); });
    } else {
        whenScrollable(90);
    }
})();
</script>
