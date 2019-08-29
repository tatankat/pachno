<?php

    /** @var \pachno\core\entities\Link[] $links */

use pachno\core\modules\publish\Publish;

?>
<div class="list-mode" id="menu_links_<?php echo $target_type; ?>_<?php echo $target_id; ?>_container">
    <div class="header not-selectable">
        <span class="name"><?php echo $title; ?></span>
        <?php if ($pachno_user->canEditMainMenu($target_type) && ((\pachno\core\framework\Context::isProjectContext() && !\pachno\core\framework\Context::getCurrentProject()->isArchived()) || !\pachno\core\framework\Context::isProjectContext())): ?>
            <div class="dropper-container">
                <span class="dropper"><?php echo fa_image_tag('cog'); ?></span>
                <div class="dropdown-container">
                    <div class="list-mode">
                        <?php echo javascript_link_tag('<span class="name">'.__('Toggle menu edit mode').'</span>', ['onclick' => "Pachno.Main.Profile.clearPopupsAndButtons();Pachno.Main.Menu.toggleEditMode('{$target_type}', '{$target_id}', '".make_url('save_menu_order', ['target_type' => $target_type, 'target_id' => $target_id])."');", 'class' => 'list-item']); ?>
                        <?php echo javascript_link_tag('<span class="name">'.__('Add menu item').'</span>', ['onclick' => "Pachno.Main.Profile.clearPopupsAndButtons();$('attach_link_{$target_type}_{$target_id}').toggle();", 'class' => 'list-item']); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pachno_user->canEditMainMenu($target_type) && ((\pachno\core\framework\Context::isProjectContext() && !\pachno\core\framework\Context::getCurrentProject()->isArchived()) || !\pachno\core\framework\Context::isProjectContext())): ?>
        <div class="rounded_box white shadowed" id="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>" style="position: absolute; width: 300px; z-index: 10001; margin: -1px 0 5px 5px; display: none; top: 0; left: 305px;">
            <div class="header_div" style="margin: 0 0 5px 0;"><?php echo __('Add menu item'); ?></div>
            <form action="<?php echo make_url('attach_link', array('target_type' => $target_type, 'target_id' => $target_id)); ?>" method="post" onsubmit="Pachno.Main.Link.add('<?php echo make_url('attach_link', array('target_type' => $target_type, 'target_id' => $target_id)); ?>', '<?php echo $target_type; ?>', '<?php echo $target_id; ?>');return false;" id="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>_form">
                <label for="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>_url"><?php echo ($target_type == 'wiki') ? __('Article name') : __('URL'); ?>:</label>
                <input type="text" name="link_url" id="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>_url" style="width: 96%; margin-left: 3px;">
                <label for="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>_description"><?php echo __('Description'); ?>:</label>
                <input type="text" name="description" id="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>_description" style="width: 96%; margin-left: 3px;">
                <div style="font-size: 12px; padding: 15px 2px 10px 2px;" class="faded_out">
                    <?php if ($target_type == 'wiki'): ?>
                        <?php echo __('Enter the name of the article to link to here, along with an (optional) description, and press "%add_item" to add it to the menu.', array('%add_item' => __('Add item'))); ?><br /><br />
                    <?php else: ?>
                        <?php echo __('Enter the link URL here, along with an (optional) description, and press "%add_item" to add it to the menu.', array('%add_item' => __('Add item'))); ?><br /><br />
                    <?php endif; ?>
                    <?php echo __('To add free text, just enter text in the description - without any url - and press the "%add_item" button (Text will be parsed according to the %wiki_formatting).', array('%add_item' => __('Add item'), '%wiki_formatting' => link_tag(Publish::getArticleLink('WikiFormatting'), 'WikiFormatting'))); ?><br /><br />
                    <?php echo __('To add a spacer, just press "%add_item", without any url or description.', array('%add_item' => __('Add item'))); ?>

                    <div style="text-align: right; margin-top: 10px; font-size: 1.1em;">
                        <?php echo image_tag('spinning_16.gif', array('id' => 'attach_link_'.$target_type.'_'.$target_id.'_indicator', 'style' => 'display: none; vertical-align: middle; margin-right: 5px;')); ?>
                        <?php echo __('%cancel or %attach_link', array('%attach_link' => '', '%cancel' => javascript_link_tag(__('cancel'), array('onclick' => "$('attach_link_{$target_type}_{$target_id}').toggle();")))); ?>
                        <input type="submit" value="<?php echo __('Add item'); ?>" id="attach_link_<?php echo $target_type; ?>_<?php echo $target_id; ?>_submit">
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
    <div class="simple-list" id="<?php echo $target_type; ?>_<?php echo $target_id; ?>_links">
        <?php foreach ($links as $link): ?>
            <?php include_component('main/menulink', compact('link')); ?>
        <?php endforeach; ?>
    </div>
    <div style="padding-left: 5px;<?php if (count($links) > 0): ?> display: none;<?php endif; ?>" id="<?php echo $target_type; ?>_<?php echo $target_id; ?>_no_links"><?php echo __('There are no links in this menu'); ?></div>
    <div style="padding-left: 5px; text-align: center; display: none;" id="<?php echo $target_type; ?>_<?php echo $target_id; ?>_indicator"><?php echo image_tag('spinning_16.gif'); ?></div>
</div>
