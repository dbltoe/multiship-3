<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
?>
<div class="centerColumn" id="checkoutMultishipDefault">
    <h1 id="checkoutMultishipDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

<?php 
if ($messageStack->size('multiship') > 0) {
    echo $messageStack->output('multiship');
}
if ($messageStack->size('shopping_cart') > 0) {
    echo $messageStack->output('shopping_cart'); 
}
// -----
// Rendered as a button, so leaving this page looks like every other step of checkout --
// the same control the cart uses to start checkout and the confirmation page uses to place
// the order. A text link here read as an aside rather than the way forward.
//
// zca_button_link() is ZCA Bootstrap's own helper, and what its shopping cart uses for the
// Checkout button, so detecting it gives that store its native button with our wording.
// Any other template falls back to the pattern core's cart uses: zen_image_button() inside
// an anchor, which picks up whatever that template does with buttons.
//
// The fallback's accessible name is BUTTON_CHECKOUT_ALT rather than our own wording,
// deliberately: it must match the text drawn on the button image, or a speech-input user
// asking for what they can see would not reach it (WCAG 2.5.3, Label in Name).
//
if (function_exists('zca_button_link')) {
    // button_checkout is the class ZCA's own shopping cart passes for its Checkout button,
    // so this picks up that exact styling rather than merely being some button.
    $resume_checkout_anchor = zca_button_link($multiship_continue_link, TEXT_CONTINUE_CHECKOUT_LINK, 'button_checkout multishipActionLink');
} else {
    $resume_checkout_anchor = '<a class="multishipActionLink" href="' . $multiship_continue_link . '">' . zen_image_button(BUTTON_IMAGE_CHECKOUT, BUTTON_CHECKOUT_ALT) . '</a>';
}
?>
    <?php echo zen_draw_form('checkout_multiship', zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL')); ?>
<?php
// -----
// The shipping method is chosen here now, not on checkout_shipping.
//
// This block used to read "Your current shipping method: X. Not the one you want? Change
// Shipping Method." -- a link back to a page the customer had already been through, which
// is what made the flow five pages. Both questions this page can answer are now on it:
// how the order travels, and where each item goes.
//
// It comes before the grid because it governs it. The method decides which addresses can
// be served, and the warning icons in the grid below are answers to it.
//
// Changing it re-submits, exactly as the address menus do, because every address has to be
// re-quoted against the new method. The same remember/restore keeps the customer's place.
//
$multishipShippingOnChange =
    'ok2leave();'
    . ' if (window.multishipRemember) { window.multishipRemember(); }'
    . ' this.form.submit();';
?>
    <fieldset id="checkoutMultishipShipping">
        <legend id="multishipShippingHeading"><?php echo TEXT_MULTISHIP_SHIPPING_HEADING; ?></legend>
<?php
// -----
// No price against the method, deliberately.
//
// A quote is the cost of sending the whole order to one address, because that is what a
// shipping module is asked. This order is not going to one address. The real figure is the
// sum of a separate quote per destination, and cannot be known until every item has one --
// on dbltoe's five-address test that was $135.90 against a single-address quote of $11.30.
//
// Putting the small number beside the method the customer is about to choose would be
// quoting them a price the order will not cost. The carriers are named here; the real total
// appears once it is real.
//
// -----
// The markup mirrors the store template's own, rather than being invented here.
//
// ZCA Bootstrap draws its shipping methods as
//     <div class="custom-control custom-radio"> <input class="custom-control-input"> <label
//     class="custom-control-label">
// which is Bootstrap's pattern: the native control is taken out of the flow and the visible
// one is drawn by the label's pseudo-elements. Hand-rolled markup gets neither -- which is
// how this page ended up with choices dbltoe could only find by hovering: "the shipping
// choices under the title that are unmarked selections/links".
//
// Those class names cost nothing on a template that has never heard of them: unknown classes
// style nothing and the native radio shows normally. Same tactic as the alert alert-info on
// the note below. Adopting the store's own pattern is also the point -- a checkout page that
// draws its controls differently from every other page of the store is exactly what makes a
// customer wonder whether they are still on the same site.
//
foreach ($quotes as $multishipQuote) {
    if (isset($multishipQuote['error'])) {
?>
        <div class="multishipShipError"><?php echo $multishipQuote['module'] . ': ' . $multishipQuote['error']; ?></div>
<?php
        continue;
    }
    foreach (($multishipQuote['methods'] ?? []) as $multishipMethod) {
        $multishipMethodValue = $multishipQuote['id'] . '_' . $multishipMethod['id'];
        $multishipMethodId = 'ship-' . preg_replace('/[^A-Za-z0-9_-]/', '', $multishipMethodValue);
        $multishipMethodChecked = (isset($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] === $multishipMethodValue);
?>
        <div class="multishipShipMethod custom-control custom-radio">
            <?php echo zen_draw_radio_field('shipping', $multishipMethodValue, $multishipMethodChecked, 'id="' . $multishipMethodId . '" class="custom-control-input" onchange="' . $multishipShippingOnChange . '"'); ?>
            <label class="custom-control-label checkboxLabel" for="<?php echo $multishipMethodId; ?>"><?php
                echo $multishipQuote['module'] . ' &ndash; ' . $multishipMethod['title'];
                if (!empty($multishipQuote['icon'])) {
                    echo ' ' . $multishipQuote['icon'];
                }
            ?></label>
        </div>
<?php
    }
}
?>
    </fieldset>
    <div id="checkoutMultishipInstructions"><?php echo TEXT_MULTISHIP_INSTRUCTIONS; ?></div>
    <div id="checkoutMultishipNewAddress"><?php echo TEXT_NEED_ANOTHER_ADDRESS; ?><a class="multishipActionLink" href="<?php echo zen_href_link(FILENAME_MULTISHIP_ADDRESS, '', 'SSL'); ?>"><?php echo TEXT_ENTER_NEW_ADDRESS; ?></a></div>
<?php
// -----
// Classed the way the store classes its own tables.
//
// ZCA Bootstrap puts "table" and its variants on every table it draws -- its order-history
// table is "orderTableDisplay table table-bordered table-striped" -- so a bare <table> here
// was the one table on the site that did not look like the site.
//
// Inert on a template that does not know those names, exactly like the custom-control
// classes on the shipping choices and the alert alert-info on the note below.
//
// The zebra striping that used to be in checkout_multiship.css has gone with this: leaving
// it in would have fought table-striped for the same rows and won, since page CSS loads
// after the template's, so the grid would have kept its own striping on a store whose other
// tables stripe differently. Letting the template do it is the point.
//
?>
    <table id="multishipTable" class="table table-striped">
        <tr>
            <th class="item"><?php echo HEADING_ITEM; ?></th>
            <th class="price"><?php echo HEADING_PRICE; ?></th>
            <th class="sendto"><?php echo HEADING_SENDTO; ?></th>
        </tr>
<?php
foreach ($productsArray as $multishipRowIndex => $currentProduct) {
?>
        <tr id="multishipRow<?php echo (int)$multishipRowIndex; ?>" class="multishipItem<?php echo ($currentProduct['is_physical']) ? '' : ' virtual'; ?>">
            <td>
                <div class="msipItemName"><?php echo $currentProduct['name'] . zen_draw_hidden_field('prid[]', $currentProduct['id']); ?></div>
<?php
    if (isset($currentProduct['attributes'])) {
?>
                <div class="msipItemAttr"><ul>
<?php
        foreach ($currentProduct['attributes'] as $currentAttribute) {
?>
                    <li><?php echo $currentAttribute['name'] . TEXT_OPTION_DIVIDER . nl2br($currentAttribute['value']); ?></li>
<?php
        }
?>
                </ul></div>
<?php
    }
?>
            </td>
            <td class="msipPrice"><?php echo $currentProduct['price']; ?></td>
<?php
    // -----
    // Choosing an address submits the form, so the page reloads -- once per item, which on
    // a five-row order is five reloads while working down a single list.
    //
    // This used to rewrite the form's action to point at the next row's anchor, so the
    // reload would land there. It did not hold: the fragment of a POST target is not
    // reliably applied, and where it is, an anchor scroll is still a visible jump.
    //
    // jscript_multiship_grid.php now records the scroll position here and restores it on
    // the way back in, so the page returns exactly where it was and the reload costs no
    // movement. It also returns keyboard focus to the next unanswered menu, which a reload
    // would otherwise throw back to the top of the document on every choice.
    //
    // Guarded rather than called outright: if that file is ever absent the submit still
    // happens and the customer gets the old behaviour, not a dead menu. Customers without
    // JavaScript never reach this path at all -- they set every row and press Save.
    //
    $multishipOnChange =
        'ok2leave();'
        . ' if (window.multishipRemember) { window.multishipRemember(); }'
        . ' this.form.submit();';
?>
            <td class="sendto"><?php
                echo zen_draw_pull_down_menu('address[]', $multishipAddresses, $currentProduct['sendto'], 'onchange="' . $multishipOnChange . '"');
                // Only ask about serviceability once there is an address to ask about.
                if ($currentProduct['sendto'] !== '') {
                    echo ' ' . $_SESSION['multiship']->getNoShipIcon($currentProduct['sendto']);
                }
            ?></td>
        </tr>
<?php
}
?>
    </table>
<?php
if ($products_onetime_charges) {
?>
    <div id="onetime_charges"><span class="onetime_charge"><?php echo ONETIME_CHARGE_INDICATOR; ?></span><?php echo TEXT_ONETIME_CHARGES_APPLY; ?></div>
<?php
}
?>

<?php
// -----
// Quantities and extra items belong to the cart, so this points there rather than
// duplicating it. The quantity box that used to sit on every row is gone.
//
$multiship_cart_link = '<a class="multishipActionLink" href="' . zen_href_link(FILENAME_SHOPPING_CART, '', 'SSL') . '">' . TEXT_MULTISHIP_CHANGE_QUANTITIES_CART . '</a>';
$multiship_shop_link = '<a class="multishipActionLink" href="' . zen_href_link(FILENAME_DEFAULT) . '">' . TEXT_MULTISHIP_CHANGE_QUANTITIES_SHOP . '</a>';
?>
<div id="multishipControls">
<?php
// -----
// Save Addresses on the left, the cart/shopping note beside it on the right.
//
// The button is kept even though the address menus submit on change, because that submit
// needs JavaScript: without it a customer with scripting disabled could choose addresses
// and have no way to save them. Named for what it now does, rather than the quantity
// Update it replaced.
//
// alert alert-info gives the note the store's own informational styling on Bootstrap
// templates; checkout_multiship.css carries a plain fallback for templates that have no
// such classes, so it never renders as bare text.
//
?>
<?php
// -----
// Save Addresses only exists for a customer without JavaScript.
//
// Every address menu submits the moment it is changed, so for everyone else the button does
// nothing they have not already done -- and a button labelled Save, on a page full of
// choices, reads as the thing you must press to keep your work. It invited hunting for it,
// and made anyone who had not pressed it doubt their choices had taken. Explaining that in a
// caption underneath only drew more attention to it.
//
// <noscript> answers it properly: with scripting on the browser does not parse this as
// markup at all, so the control never exists rather than being drawn and then hidden. With
// scripting off it is the only way to record anything, and the wording says so plainly
// instead of describing itself as an exception.
//
// The save_addresses hidden field went with the rewrite -- nothing has ever read it. This
// page detects a post by the securityToken that zen_draw_form() emits, which is present
// however the form was submitted.
//
?>
    <noscript>
        <div id="multishipSaveAddresses">
            <?php echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_MULTISHIP_SAVE_ADDRESSES, 'name="save"'); ?>
            <div id="multishipSaveNote"><?php echo TEXT_MULTISHIP_SAVE_ADDRESSES_NOTE; ?></div>
        </div>
    </noscript>
<?php
// -----
// Leaving multiship comes before returning to the cart, and the order is the point.
//
// Both links take the customer off this page and one of them is not what it looks like:
// "Return to Your Cart" reads, to someone scanning for a way out, like starting over --
// but it keeps the multiship decision, so they arrive at the cart still in it. Putting the
// genuine exit first means a customer looking for one finds the right link before they
// find the misleading one.
//
// The wording was fixed too -- both used to open "changed your mind" -- but position is
// the part that helps someone scanning rather than reading.
//
?>
    <div class="multiship-decline"><?php echo sprintf(TEXT_DECLINE_MULTISHIP, '<a class="multishipActionLink" href="' . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, 'action=decline', 'SSL') . '" onclick="ok2leave();">' . TEXT_DECLINE_MULTISHIP_LINK . '</a>'); ?></div>
    <div id="multishipQuantityNote" class="alert alert-info"><?php echo sprintf(TEXT_MULTISHIP_CHANGE_QUANTITIES, $multiship_cart_link, $multiship_shop_link); ?></div>
    <div class="clearBoth"></div>
<?php
// -----
// One position, two states, and it sits last: both ways off this page are offered before
// the way on through it, so the button a finishing customer wants is the final thing on the
// page rather than something to scroll back up for.
//
// While items are unanswered the reminder occupies this spot instead. It lives here rather
// than in the messageStack at the top of the page because the customer is working at the
// bottom of a long grid, and a message they scrolled past before starting is not a
// reminder.
//
if ($multiship_unassigned === 0) {
?>
    <div id="multishipContinue"><?php echo $resume_checkout_anchor; ?></div>
<?php
} else {
?>
    <div id="multishipContinue" class="multishipIncomplete"><?php echo sprintf(TEXT_MULTISHIP_ITEMS_UNASSIGNED, $multiship_unassigned); ?></div>
<?php
}
?>
    <div class="clearBoth"></div>
</div>
    </form>
</div>
