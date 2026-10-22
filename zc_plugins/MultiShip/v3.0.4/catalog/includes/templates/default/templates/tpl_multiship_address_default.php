<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Posts to core's address_book_process, which validates and inserts. See this page's
// header_php.php for why that works even when the store's address-book limit is reached.
//
// The form fields come from the store's own tpl_modules_address_book_details.php, so the
// layout, country/zone behavior and styling match every other address form on the site
// rather than being a plugin's approximation of one.
//
?>
<div class="centerColumn" id="multishipAddress">
<h1 id="multishipAddress-pageHeading" class="pageHeading"><?php echo HEADING_TITLE; ?></h1>

<?php
if ($messageStack->size('addressbook') > 0) {
    echo $messageStack->output('addressbook');
}
?>

<div id="multishipAddress-intro" class="content"><?php echo TEXT_MULTISHIP_ADDRESS_INTRO; ?></div>

<?php
// -----
// No onsubmit="check_form(...)" here, unlike core's address form. That function comes from
// a jscript_ file the address_book_process page loads, and this page does not, so calling
// it would throw. Core validates server-side after the post regardless -- the client-side
// pass is a nicety, not the gate.
//
echo zen_draw_form('multiship_address', zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'), 'post') . zen_draw_hidden_field('action', 'process');
?>

<?php require $template->get_template_dir('tpl_modules_address_book_details.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_address_book_details.php'; ?>

<?php
// -----
// Centered, not floated to the corners.
//
// These carried core's "forward" and "back" -- float right and float left -- which put the
// button that finishes the page in the right margin and the way out in the left, with the
// form between them. Every other page this plugin owns closes with its controls centered one
// under the other, and dbltoe asked for the same here.
//
// "buttonRow" is kept so a store that has styled its button rows still reaches these; only
// the float classes are dropped, because centering is what was asked for and a float would
// win against it.
//
?>
    <div class="buttonRow multishipAddressSubmit"><?php echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_UPDATE_ALT); ?></div>
</form>

<div class="buttonRow multishipAddressCancel"><a class="multishipActionLink" href="<?php echo zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'); ?>"><?php echo TEXT_MULTISHIP_ADDRESS_CANCEL; ?></a></div>

</div>
