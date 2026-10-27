<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Two submit buttons in one form. No JavaScript is involved, so the choice works with
// scripting disabled; and because both answers are POSTed, neither can be triggered by
// a crawler or a prefetched link.
//
?>
<div class="centerColumn" id="multishipChoice">
<h1 id="multishipChoiceHeading"><?php echo HEADING_TITLE_MULTISHIP_CHOICE; ?></h1>

<div id="multishipChoiceIntro" class="content"><?php echo sprintf(TEXT_MULTISHIP_CHOICE_INTRO, STORE_NAME); ?></div>

<?php echo zen_draw_form('multiship_choice', zen_href_link(FILENAME_MULTISHIP_CHOICE, '', 'SSL'), 'post'); ?>

<?php
// -----
// The three answers sit side by side on a wide screen, each with its explanation beneath.
//
// They were a vertical stack separated by rules, which on a large monitor left one short
// button per screenful of empty space -- and made three options that should be weighed
// against each other into a list to be read in order. Three abreast is what the page is
// actually asking: pick one of these.
//
// The horizontal rules went with the stack. They separated one full-width row from the
// next; between columns they would be drawing lines across the reading direction. The grid
// gap separates them now, and the narrow layout brings a rule back above each option.
//
// Wrapped in a container of their own rather than laying out #multishipChoice directly, so
// the heading and the introduction above stay full width and only the answers are columned.
//
?>
    <div id="multishipChoiceOptions">
        <div class="multishipChoiceOption">
            <button type="submit" name="multiship_choice" value="yes" class="button multishipChoiceYes"><?php echo BUTTON_MULTISHIP_CHOICE_YES; ?></button>
            <p class="multishipChoiceHelp"><?php echo TEXT_MULTISHIP_CHOICE_YES_HELP; ?></p>
        </div>

        <div class="multishipChoiceOption">
            <button type="submit" name="multiship_choice" value="no" class="button multishipChoiceNo"><?php echo BUTTON_MULTISHIP_CHOICE_NO; ?></button>
            <p class="multishipChoiceHelp"><?php echo TEXT_MULTISHIP_CHOICE_NO_HELP; ?></p>
        </div>

        <div class="multishipChoiceOption">
            <button type="submit" name="multiship_choice" value="shop" class="button multishipChoiceShop"><?php echo BUTTON_MULTISHIP_CHOICE_SHOP; ?></button>
            <p class="multishipChoiceHelp"><?php echo TEXT_MULTISHIP_CHOICE_SHOP_HELP; ?></p>
        </div>
    </div>

</form>
</div>
