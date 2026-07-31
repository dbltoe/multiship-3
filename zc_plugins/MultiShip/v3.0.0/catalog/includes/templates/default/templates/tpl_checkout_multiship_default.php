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
// Two anchors, not one. Both go to checkout_shipping, but they mean different things --
// one changes the shipping method, the other says the addresses are finished -- and a
// single shared label could only ever be vague enough to cover both, which is how this
// came to say "* HERE *" twice.
//
$change_shipping_anchor = '<a class="multishipActionLink" href="' . $checkout_shipping_link . '">' . TEXT_SHIPPING_METHOD_CHANGE_LINK . '</a>';
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
    $resume_checkout_anchor = zca_button_link($checkout_shipping_link, TEXT_CONTINUE_CHECKOUT_LINK, 'button_checkout multishipActionLink');
} else {
    $resume_checkout_anchor = '<a class="multishipActionLink" href="' . $checkout_shipping_link . '">' . zen_image_button(BUTTON_IMAGE_CHECKOUT, BUTTON_CHECKOUT_ALT) . '</a>';
}
?>
    <div id="checkoutMultishipShipping"><?php echo TEXT_CURRENT_SHIPPING_METHOD; ?><strong><?php echo $_SESSION['shipping']['title']; ?></strong>. <?php echo sprintf(TEXT_SHIPPING_METHOD_CHANGE, $change_shipping_anchor); ?></div>
    <div id="checkoutMultishipInstructions"><?php echo TEXT_MULTISHIP_INSTRUCTIONS; ?></div>
    <div id="checkoutMultishipNewAddress"><?php echo TEXT_NEED_ANOTHER_ADDRESS; ?><a class="multishipActionLink" href="<?php echo zen_href_link(FILENAME_MULTISHIP_ADDRESS, '', 'SSL'); ?>"><?php echo TEXT_ENTER_NEW_ADDRESS; ?></a></div>
    <?php echo zen_draw_form('checkout_multiship', zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL')); ?>
    <table id="multishipTable">
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
    // Choosing an address submits the form, so the page reloads and the browser lands back
    // at the top -- once per item, which on a five-row order means scrolling down four
    // times to do four things. Pointing the form at the *next* row's anchor puts the
    // customer where they were about to work instead.
    //
    // Done by rewriting the action rather than with a scroll script, so it needs nothing
    // beyond the submit that was already happening here. Customers without JavaScript never
    // reach this path at all; they set every row and press Update.
    //
    // The last row has no next row, so it points at the controls below the grid -- which is
    // where that customer is going anyway. Without this it targeted an anchor that does not
    // exist and the browser chose for itself.
    $multishipNextRow = (int)$multishipRowIndex + 1;
    $multishipNextAnchor = ($multishipNextRow < count($productsArray)) ? 'multishipRow' . $multishipNextRow : 'multishipControls';
    $multishipOnChange =
        'ok2leave();'
        . ' this.form.action = this.form.action.split(\'#\')[0] + \'#' . $multishipNextAnchor . '\';'
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
    <div class="buttonRow back"><?php echo zen_draw_hidden_field('save_addresses', '1') . zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_MULTISHIP_SAVE_ADDRESSES, 'name="save" onclick="ok2leave();"'); ?></div>
    <div id="multishipQuantityNote" class="alert alert-info"><?php echo sprintf(TEXT_MULTISHIP_CHANGE_QUANTITIES, $multiship_cart_link, $multiship_shop_link); ?></div>
    <div class="clearBoth"></div>
<?php
// -----
// One position, two states. While items are unanswered the customer gets a reminder where
// the button will be; once none are, the button takes its place.
//
// The reminder lives here rather than in the messageStack at the top of the page because
// the customer is working at the bottom of a long grid -- a message they scrolled past
// before they started is not a reminder. Sharing the position also means the space never
// sits empty next to Update, which read as though the two were related.
//
if ($multiship_unassigned === 0) {
?>
    <div class="buttonRow forward"><?php echo $resume_checkout_anchor; ?></div>
<?php
} else {
?>
    <div class="buttonRow forward multishipIncomplete"><?php echo sprintf(TEXT_MULTISHIP_ITEMS_UNASSIGNED, $multiship_unassigned); ?></div>
<?php
}
?>
    <div class="buttonRow forward multiship-decline"><?php echo sprintf(TEXT_DECLINE_MULTISHIP, '<a class="multishipActionLink" href="' . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, 'action=decline', 'SSL') . '" onclick="ok2leave();">' . TEXT_DECLINE_MULTISHIP_LINK . '</a>'); ?></div>
    <div class="clearBoth"></div>
</div>
    </form>
</div>
