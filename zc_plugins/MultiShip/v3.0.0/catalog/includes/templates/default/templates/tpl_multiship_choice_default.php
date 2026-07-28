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

    <div class="multishipChoiceOption">
        <div class="buttonRow forward">
            <button type="submit" name="multiship_choice" value="yes" class="button multishipChoiceYes"><?php echo BUTTON_MULTISHIP_CHOICE_YES; ?></button>
        </div>
        <div class="multishipChoiceHelp"><?php echo TEXT_MULTISHIP_CHOICE_YES_HELP; ?></div>
    </div>

    <div class="multishipChoiceOption">
        <div class="buttonRow forward">
            <button type="submit" name="multiship_choice" value="no" class="button multishipChoiceNo"><?php echo BUTTON_MULTISHIP_CHOICE_NO; ?></button>
        </div>
        <div class="multishipChoiceHelp"><?php echo TEXT_MULTISHIP_CHOICE_NO_HELP; ?></div>
    </div>

    <div class="multishipChoiceOption">
        <div class="buttonRow back">
            <button type="submit" name="multiship_choice" value="shop" class="button multishipChoiceShop"><?php echo BUTTON_MULTISHIP_CHOICE_SHOP; ?></button>
        </div>
        <div class="multishipChoiceHelp"><?php echo TEXT_MULTISHIP_CHOICE_SHOP_HELP; ?></div>
    </div>

</form>
</div>
