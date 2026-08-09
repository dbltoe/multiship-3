<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
if (isset($multiship_info) && is_array($multiship_info)) {
    foreach ($multiship_info as $address_id => $currentInfo) {
?>
<?php
// -----
// Name first, address second and quieter.
//
// The name is what a customer actually checks against here. They chose these addresses a
// few minutes ago and verified each one when it went into their address book, so the full
// postal address restated in full for every recipient is bulk that hides the one word they
// are scanning for.
//
// The address is kept rather than dropped, because name alone is not always enough to tell
// two entries apart: dbltoe has a PO box and a street address for the same person, which is
// exactly the case this page has to let a customer catch. Quieter, not absent.
//
$multishipRecipientName = $currentInfo['delivery']['name'] ?? '';
?>
<div class="multishipOrder">
    <div class="multishipOrderHeader">
        <span class="multishipOrderWho">
            <span class="multishipOrderName"><?php echo TEXT_SHIPPING_TO . $multishipRecipientName; ?></span>
            <span class="multishipOrderAddress"><?php echo $currentInfo['address']; ?></span>
        </span>
<?php
// -----
// This recipient's totals, on the same line as the name they belong to.
//
// They were three stacked rows under the table, then one row under the table, and are now
// no rows at all: the header line already runs the width of the box with nothing to its
// right, so the totals cost nothing to put there. dbltoe asked for this ("it would be nice
// to have the Sub, Ship, and Total up in the other") and it is the right place on its own
// merits -- who the parcel is for and what it comes to are one fact about one delivery, and
// reading them together beats reading one at the top and the other at the bottom.
//
// The classes core puts on each total are kept, so a store that has styled ot_shipping or
// ot_total keeps that styling.
//
        if (MODULE_ORDER_TOTAL_INSTALLED) {
?>
        <span class="multishipOrderTotals">
<?php
            foreach ($currentInfo['totals'] as $currentTotal) {
?>
            <span class="multishipTotalItem <?php echo $currentTotal['class']; ?>">
                <span class="multishipTotalTitle"><?php echo $currentTotal['title']; ?></span>
                <span class="multishipTotalValue"><?php echo $currentTotal['text']; ?></span>
            </span>
<?php
            }
?>
        </span>
<?php
        }
?>
    </div>
<?php
// -----
// The id is left alone, and the class is left unstyled.
//
// id="cartContentsDisplay" repeats once per recipient, which is invalid -- but it is what
// lat9 wrote, and it is also what makes this table look like every other product table in
// the store, because the store's own stylesheet is what spaces it. dbltoe confirmed the same
// crowding on the account order-history page and elsewhere on the site: it is the store's
// look, not a defect in this plugin, and padding it here would make the pages this plugin
// touches the odd ones out.
//
// .multishipItems is a hook, carried by every copy of the table and styled by none of them,
// so a store owner can space these without having to target an id that repeats down the page.
//
?>
    <table id="cartContentsDisplay" class="multishipItems">
        <tr class="cartTableHeading">
            <th scope="col" id="ccQuantityHeading"><?php echo TABLE_HEADING_QUANTITY; ?></th>
            <th scope="col" id="ccProductsHeading"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
<?php
        // If there are tax groups, display the tax columns for price breakdown
        $show_tax_group = false;
        if (isset($currentInfo['info']['tax_groups']) && count($currentInfo['info']['tax_groups']) > 1) {
            $show_tax_group = true;
?>
            <th scope="col" id="ccTaxHeading"><?php echo HEADING_TAX; ?></th>
<?php
        }
?>  
            <th scope="col" id="ccTotalHeading"><?php echo TABLE_HEADING_TOTAL; ?></th>
        </tr>
<?php 
        foreach ($currentInfo['products'] as $currentProduct) {
?>
        <tr>
            <td  class="cartQuantity"><?php echo $currentProduct['qty']; ?>&nbsp;x</td>
            <td class="cartProductDisplay"><?php echo $currentProduct['name']; ?>
<?php 
            // if there are attributes, loop thru them and display one per line
            if (isset($currentProduct['attributes']) && count($currentProduct['attributes']) > 0 ) {
?>
                <ul class="cartAttribsList">
<?php
                for ($j = 0, $m = count($currentProduct['attributes']); $j < $m; $j++) {
?>
                    <li><?php echo $currentProduct['attributes'][$j]['option'] . ': ' . nl2br(zen_output_string_protected($currentProduct['attributes'][$j]['value'])); ?></li>
<?php
                }
?>
                </ul>
<?php
            } // endif attribute-info
?>
            </td>
<?php 
            if ($show_tax_group)  { 
?>
            <td class="cartTotalDisplay"><?php echo zen_display_tax_value($currentProduct['tax']); ?>%</td>
<?php
            }  // endif tax info display  
?>
            <td class="cartTotalDisplay">
<?php 
            echo $currencies->display_price($currentProduct['final_price'], $currentProduct['tax'], $currentProduct['qty']);
            if ($currentProduct['onetime_charges'] != 0) {
                echo '<br /> ' . $currencies->display_price($currentProduct['onetime_charges'], $currentProduct['tax'], 1);
            }
?>
            </td>
        </tr>
<?php  
        }  // end for loopthru all products 
?>
    </table>
</div>
<?php
    }  // END foreach loop
}
// -----
// No grand total here. It belongs to whichever page is including this.
//
// This partial is rendered in two places and they do not agree about it. The account
// order-history page shows nothing else after it, so the grand total is the only place that
// page states what the order came to -- it adds its own, immediately after including this.
// checkout_confirmation has core's order totals in the products card a few inches below,
// which is where a customer expects to find the figure and where they will compare it
// against what they are charged; a second one at the end of the breakdown just gave dbltoe
// the same number twice.
//
// Per-address totals stay here. Those are this partial's own business and appear nowhere
// else.
//
