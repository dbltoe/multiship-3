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
$resume_checkout_anchor = '<a class="multishipActionLink" href="' . $checkout_shipping_link . '">' . TEXT_RETURN_TO_SHIPPING_LINK . '</a>';
?>
    <div id="checkoutMultishipShipping"><?php echo TEXT_CURRENT_SHIPPING_METHOD; ?><strong><?php echo $_SESSION['shipping']['title']; ?></strong>. <?php echo sprintf(TEXT_SHIPPING_METHOD_CHANGE, $change_shipping_anchor); ?></div>
    <div id="checkoutMultishipInstructions"><?php echo TEXT_MULTISHIP_INSTRUCTIONS; ?></div>
    <div id="checkoutMultishipNewAddress"><?php echo TEXT_NEED_ANOTHER_ADDRESS; ?><a class="multishipActionLink" href="<?php echo zen_href_link(FILENAME_MULTISHIP_ADDRESS, '', 'SSL'); ?>"><?php echo TEXT_ENTER_NEW_ADDRESS; ?></a></div>
    <?php echo zen_draw_form('checkout_multiship', zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL')); ?>
    <table id="multishipTable">
        <tr>
            <th class="item"><?php echo HEADING_ITEM; ?></th>
            <th class="price"><?php echo HEADING_PRICE; ?></th>
            <th class="qty"><?php echo HEADING_QTY; ?></th>
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
            <td class="qty"><?php echo zen_draw_input_field('qty[]', 1, 'onchange="notok2leave();"'); ?></td>
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
    $multishipNextRow = (int)$multishipRowIndex + 1;
    $multishipOnChange =
        'ok2leave();'
        . ' this.form.action = this.form.action.split(\'#\')[0] + \'#multishipRow' . $multishipNextRow . '\';'
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

    <div class="buttonRow back"><?php echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_UPDATE_ALT, 'name="update" onclick="ok2leave();"'); ?></div>
<?php
// -----
// The way onward appears only once every item has an address. Offering it while rows are
// unanswered would let a customer leave with items nobody has claimed -- and the earlier
// behaviour silently sent those to the customer themselves, which is the mistake this
// whole change exists to prevent. The messageStack above says how many are outstanding.
//
if ($multiship_unassigned === 0) {
?>
    <div class="buttonRow forward"><?php echo sprintf(TEXT_RETURN_TO_SHIPPING, $resume_checkout_anchor); ?></div>
<?php
}
?>
    <div class="buttonRow forward multiship-decline"><?php echo sprintf(TEXT_DECLINE_MULTISHIP, '<a class="multishipActionLink" href="' . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, 'action=decline', 'SSL') . '" onclick="ok2leave();">' . TEXT_DECLINE_MULTISHIP_LINK . '</a>'); ?></div>
    </form>
</div>
