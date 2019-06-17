<?php

    use \pachno\core\framework\Settings;

    /**
     * @var \pachno\core\entities\Workflow $workflow
     */

    $pachno_response->setTitle(__('Configure issue type scheme %scheme_name', ['%scheme_name' => $workflow->getName()]));

?>
<div class="content-with-sidebar">
    <?php include_component('configuration/sidebar', ['selected_section' => Settings::CONFIGURATION_SECTION_WORKFLOW]); ?>
    <div class="configuration-container">
        <div class="configuration-content centered">
            <div class="form-container">
                <form action="<?= make_url('configure_workflow_post', ['workflow_id' => $workflow->getId()]); ?>" onsubmit="Pachno.Config.Workflows.Workflow.save(this);return false;" data-interactive-form data-interactive-form-method="Pachno.Config.IssuetypeScheme.save">
                    <div class="form-row">
                        <input type="text" name="name" value="<?= $workflow->getName(); ?>" class="invisible title" id="workflow_<?= $workflow->getID(); ?>_name_input">
                        <label for="workflow_<?= $workflow->getID(); ?>_name_input"><?= __('Workflow name'); ?><?= fa_image_tag('spinner', ['class' => 'fa-spin submit-indicator icon']); ?></label>
                    </div>
                    <div class="form-row">
                        <input type="text" name="description" value="<?= $workflow->getDescription(); ?>" class="invisible" id="workflow_<?= $workflow->getID(); ?>_description_input" placeholder="<?= __('Enter an optional workflow description'); ?>">
                        <label for="workflow_<?= $workflow->getID(); ?>_description_input"><?= __('Workflow description'); ?><?= fa_image_tag('spinner', ['class' => 'fa-spin submit-indicator icon']); ?></label>
                    </div>
                    <div class="form-row error-container">
                        <div class="error"></div>
                    </div>
                </form>
            </div>
            <div class="configurable-components-container" id="workflow-steps-container">
                <div class="configurable-components-list-container">
                    <h3><?php echo __('Workflow steps'); ?></h3>
                    <div class="configurable-components-list" id="workflow-steps-list">
                        <?php foreach ($workflow->getSteps() as $step): ?>
                            <?php include_component('configuration/workflowstep', ['step' => $step]); ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="configurable-components-list">
                        <a class="configurable-component" href="javascript:void(0);" onclick="Pachno.Main.Helpers.Backdrop.show('<?= make_url('get_partial_for_backdrop', ['key' => 'edit_workflow_step', 'workflow_id' => $workflow->getId()]); ?>');">
                            <span class="row">
                                <span class="icon"><?= fa_image_tag('plus'); ?></span>
                                <span class="name">
                                    <span class="title"><?= __('Add workflow step'); ?></span>
                                </span>
                            </span>
                        </a>
                    </div>
                </div>
                <div class="configurable-component-options" id="selected-workflow-step-options"></div>
            </div>
        </div>
    </div>
</div>
<script>
    require(['domReady', 'pachno/index', 'jquery'], function (domReady, pachno_index_js, jQuery) {
        domReady(function () {
            jQuery('body').on('click', '.workflow-step .open', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const $item = jQuery(this).parents('.workflow-step');
                pachno_index_js.Config.Workflows.Workflow.Step.show($item);
            });

            jQuery('body').on('click', '.list-item[data-issue-field]:not(.disabled)', function(event) {
                const key = jQuery(this).data('id'),
                    url = jQuery(this).data('url');

                pachno_index_js.Config.IssuetypeScheme.addField(url, key);
            });

            jQuery('body').on('click', '.configurable-component[data-issue-field] .remove-item', function(event) {
                const $item = jQuery(this).parents('.configurable-component'),
                    key = $item.data('id');

                $item.remove();
                jQuery('.list-item[data-issue-field][data-id=' + key + ']').removeClass('disabled');
            });
        });
    });
</script>
