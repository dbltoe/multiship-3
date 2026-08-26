<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Stops the login page throwing the viewport past the create-account section, for a
// customer this plugin has just redirected here.
//
// Two separate things focus that field, and both scroll:
//
//   1. the autofocus attribute on the input, applied by the browser at parse time
//      (tpl_login_default.php, in the split-login block -- and split-login is selected
//      whenever USE_SPLIT_LOGIN_MODE is on OR PayPal Express is enabled)
//   2. includes/modules/pages/login/on_load_main.js, whose entire contents are
//      document.loginForm.email_address.focus(); read into the body's onload attribute
//
// In split-login the sign-in field sits BELOW the create-account section, so either one
// lands the customer past both the "you need an account" notice this plugin just added and
// the create-account option it is telling them about.
//
// This replaces on_load_multiship.js, which ran at body onload -- after the damage -- and
// undid it with blur() and scrollTo(0, 0). That worked, but the correction was visible: the
// page arrived, jumped, and jumped back, which reads as a fault rather than a fix.
//
// A jscript_*.php file is required inline into the <head> by
// html_header_js_loader.php:52 (listModulePagesFiles('jscript_', '.php')), so this runs
// before the form is parsed and can get in front of both causes rather than reacting to
// them.
//
// Reported upstream as core-requirements 2.1, with the one-line fix core could make:
// focus({preventScroll: true}) plus dropping the attribute. No response so far, hence this.
//
// -----
// Scoped to customers this plugin sent here, deliberately.
//
// The store's other shoppers keep core's behavior. A shipping plugin quietly changing how
// the login page behaves for everyone is not its business -- and the harm is specific to
// this case, where the customer has been redirected mid-task and needs to read something
// before acting. Drop the guard below to apply it to every login.
//
if (empty($_SESSION['multiship'])
    || !$_SESSION['multiship']->isChosen()
    || !empty($_SESSION['customer_id'])
) {
    return;
}
?>
<script>
(function () {
    'use strict';

    var field = null;

    function neutralise(el) {
        field = el;

        // Kill cause 1 before the browser acts on it. Autofocus candidates are flushed
        // during the rendering steps; a MutationObserver callback is a microtask and runs
        // first, so removing the attribute here beats it.
        el.removeAttribute('autofocus');

        // Kill cause 2. on_load_main.js calls .focus() on this exact element, so an own
        // property shadowing the prototype method makes that call a no-op without touching
        // focus() anywhere else on the page.
        el.focus = function () { return undefined; };
    }

    function find() {
        return document.querySelector('form[name="loginForm"] input[name="email_address"]')
            || document.querySelector('input[name="email_address"]');
    }

    // The field does not exist yet -- this is the head, the form has not been parsed.
    var watcher = new MutationObserver(function () {
        var el = find();
        if (el !== null) {
            neutralise(el);
            watcher.disconnect();
        }
    });
    watcher.observe(document.documentElement, { childList: true, subtree: true });

    // Give the field back once core's onload script has had its turn. The timeout matters:
    // this listener is registered in the head, so it would otherwise run BEFORE the body's
    // own onload attribute, handing focus() back just in time to be called. A zero timeout
    // defers past every load handler.
    window.addEventListener('load', function () {
        window.setTimeout(function () {
            watcher.disconnect();
            if (field !== null) {
                delete field.focus;
                field = null;
            }
        }, 0);
    });
})();
</script>
