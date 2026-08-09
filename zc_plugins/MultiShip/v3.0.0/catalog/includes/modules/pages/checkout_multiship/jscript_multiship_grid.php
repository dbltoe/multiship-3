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
    // the old behaviour rather than a broken page.
    window.multishipRemember = function () {
        try {
            window.sessionStorage.setItem(KEY, String(window.pageYOffset || 0));
        } catch (e) {
            // Private browsing can refuse sessionStorage. Losing the position is not worth
            // stopping the submit for.
        }
    };

    function restore() {
        var saved = null;
        try {
            saved = window.sessionStorage.getItem(KEY);
            window.sessionStorage.removeItem(KEY);
        } catch (e) {
            return;
        }
        if (saved === null) {
            return;
        }

        // Before the scroll, so moving focus cannot fight it: focusing an off-screen
        // control scrolls to it, which is the jump this file exists to remove.
        var menus = document.querySelectorAll('#multishipTable select[name="address[]"]');
        var target = null;
        for (var i = 0; i < menus.length; i++) {
            if (menus[i].value === '') {
                target = menus[i];
                break;
            }
        }

        // -----
        // Nothing left unanswered: the work is done and the only thing remaining is to
        // leave the page, so go to that button rather than staying put.
        //
        // This is the one case where moving is right. Everywhere else the customer is
        // mid-task and being moved is an interruption; here they have finished, and the
        // control that finishes it can easily be below the fold on a long grid. Keeping
        // the position would just highlight a button they cannot see -- which is what
        // dbltoe found: "highlights the Continue with Checkout button which may be hidden
        // from view and the highlight never seen by the customer".
        //
        if (target === null) {
            var finish = document.querySelector('#multishipContinue a') ||
                         document.querySelector('#multishipControls input[name="save"]');
            if (finish !== null) {
                if (typeof finish.scrollIntoView === 'function') {
                    finish.scrollIntoView({ block: 'center' });
                }
                if (typeof finish.focus === 'function') {
                    finish.focus({ preventScroll: true });
                }
            }
            return;
        }

        if (typeof target.focus === 'function') {
            target.focus({ preventScroll: true });
        }

        window.scrollTo(0, parseInt(saved, 10) || 0);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restore);
    } else {
        restore();
    }
})();
</script>
