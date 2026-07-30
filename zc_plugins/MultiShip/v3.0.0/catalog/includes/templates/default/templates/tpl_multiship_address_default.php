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
// layout, country/zone behaviour and styling match every other address form on the site
// rather than being a plugin's approximation of one.
//
?>
<div class="centerColumn" id="multishipAddress">
<h1 id="multishipAddress-pageHeading" class="pageHeading"><?php echo HEADING_TITLE_MULTISHIP_ADDRESS; ?></h1>

<?php
if ($messageStack->size('addressbook') > 0) {
    echo $messageStack->output('addressbook');
}
?>

<div id="multishipAddress-intro" class="content"><?php echo TEXT_MULTISHIP_ADDRESS_INTRO; ?></div>

<?php echo zen_draw_form('multiship_address', zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'), 'post', 'onsubmit="return check_form(multiship_address);"') . zen_draw_hidden_field('action', 'process'); ?>

<?php require $template->get_template_dir('tpl_modules_address_book_details.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_address_book_details.php'; ?>

    <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_UPDATE_ALT); ?></div>
</form>

<div class="buttonRow back"><a class="multishipActionLink" href="<?php echo zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'); ?>"><?php echo TEXT_MULTISHIP_ADDRESS_CANCEL; ?></a></div>

</div>
