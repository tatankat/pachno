<?php

    use pachno\core\entities\AgileBoard;

    $pachno_response->addBreadcrumb(__('Planning'), make_url('agile_board', array('project_key' => $selected_project->getKey(), 'board_id' => $board->getId())));
    $pachno_response->setTitle(__('"%project_name" project planning', array('%project_name' => $selected_project->getName())));

    switch ($board->getType())
    {
        case AgileBoard::TYPE_GENERIC:
            $newmilestonelabel = __('New milestone');
            $togglemilestoneslabel = __('Toggle hidden milestones');
            $addmilestoneslabel = __('There are no milestones. Why not add one?');
            break;
        case AgileBoard::TYPE_SCRUM:
        case AgileBoard::TYPE_KANBAN:
            $newmilestonelabel = __('Add new sprint');
            $togglemilestoneslabel = __('Toggle hidden sprints');
            $addmilestoneslabel = __('There are no active sprints. Why not add one?');
            break;
    }

?>
<div class="content-with-sidebar">
    <?php include_component('project/sidebar', ['dashboard' => __('Releases'), 'collapsed' => true]); ?>
    <div id="project_planning" class="project_info_container left_toggled <?php if ($board->getType() == AgileBoard::TYPE_GENERIC) echo 'type-generic'; if ($board->getType() == AgileBoard::TYPE_SCRUM) echo 'type-scrum'; if ($board->getType() == AgileBoard::TYPE_KANBAN) echo 'type-kanban'; ?>" data-last-refreshed="<?php echo time(); ?>" data-poll-url="<?php echo make_url('agile_poll', array('project_key' => $selected_project->getKey(), 'board_id' => $board->getID(), 'mode' => 'planning')); ?>" data-retrieve-issue-url="<?php echo make_url('agile_retrieveissue', array('project_key' => $selected_project->getKey(), 'board_id' => $board->getID(), 'mode' => 'planning')); ?>" data-board-id="<?php echo $board->getID(); ?>">
        <div class="planning_indicator" id="planning_indicator">
            <?php echo image_tag('spinning_30.gif'); ?>
            <div class="milestone_percentage" id="planning_loading_progress_indicator">
                <div class="filler" id="planning_percentage_filler" style="width: 5%;"></div>
            </div>
        </div>
        <div class="top-search-filters-container" id="project_planning_action_strip">
            <div class="header">
                <div class="fancytabs">
                    <a class="tab selected" href="<?= make_url('agile_board', array('project_key' => $board->getProject()->getKey(), 'board_id' => $board->getID())); ?>">
                        <span class="icon"><?= fa_image_tag('stream'); ?></span>
                        <span class="name"><?= ($board->getType() == AgileBoard::TYPE_GENERIC) ? __('Planning') : __('Backlog'); ?></span>
                    </a>
                    <a class="tab" href="<?= make_url('agile_whiteboard', array('project_key' => $board->getProject()->getKey(), 'board_id' => $board->getID())); ?>">
                        <span class="icon"><?= fa_image_tag('columns'); ?></span>
                        <span class="name"><?= __('Whiteboard'); ?></span>
                    </a>
                </div>
            </div>
            <div class="search-and-filters-strip">
                <div class="search-strip" id="project_planning_action_strip">
                    <input type="search" class="planning_filter_title" id="planning_filter_title_input" disabled placeholder="<?php echo __('Filter issues by title'); ?>">
                    <?php if ($board->getProject()->isBuildsEnabled()): ?>
                        <a class="button" id="releases_toggler_button" href="javascript:void(0);" onclick="$(this).toggleClassName('button-pressed');$('builds_list').toggleClassName('expanded');"><?php echo __('Releases'); ?></a>
                    <?php endif; ?>
                    <?php if ($board->getEpicIssuetypeID()): ?>
                        <button class="button" id="epics_toggler_button" onclick="$(this).toggleClassName('button-pressed');$('epics_list').toggleClassName('expanded');" disabled><?php echo __('Epics'); ?></button>
                    <?php endif; ?>
                    <?php echo javascript_link_tag($newmilestonelabel, array('class' => 'button', 'onclick' => "Pachno.Main.Helpers.Backdrop.show('".make_url('get_partial_for_backdrop', array('key' => 'agilemilestone', 'project_id' => $board->getProject()->getId(), 'board_id' => $board->getID()))."');")); ?>
                    <?php echo image_tag('spinning_16.gif', array('id' => 'retrieve_indicator', 'class' => 'indicator', 'style' => 'display: none;')); ?>
                    <?php if ($pachno_user->canManageProjectReleases($selected_project)): ?>
                        <?php echo fa_image_tag('cog', array('class' => 'dropper dropdown_link planning_board_settings_gear', 'id' => 'planning_board_settings_gear')); ?>
                        <ul class="more_actions_dropdown popup_box">
                            <li><?php echo javascript_link_tag(__('Sort milestones'), array('onclick' => "Pachno.Project.Planning.toggleMilestoneSorting();")); ?></li>
                            <li class="separator"></li>
                            <li><?php echo javascript_link_tag(__('Toggle closed issues'), array('onclick' => "Pachno.Project.Planning.toggleClosedIssues(this);")); ?></li>
                            <li><?php echo javascript_link_tag($togglemilestoneslabel, array('onclick' => "$('planning_container').toggleClassName('show_unavailable');Pachno.Main.Profile.clearPopupsAndButtons();")); ?></li>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="project_save_container" id="milestone-sort-actions">
                    <button class="button" id="milestone_sort_toggler_button" onclick="Pachno.Project.Planning.toggleMilestoneSorting();"><?php echo __('Done sorting'); ?></button>
                </div>
            </div>
        </div>
        <div class="project_right planning_container" id="planning_container">
            <?php if ($board->getProject()->isBuildsEnabled()): ?>
                <ul id="builds_list" data-releases-url="<?php echo make_url('agile_getreleases', array('project_key' => $selected_project->getKey(), 'board_id' => $board->getID())); ?>">
                </ul>
            <?php endif; ?>
            <?php if ($board->getEpicIssuetypeID()): ?>
                <ul id="epics_list" data-epics-url="<?php echo make_url('agile_getepics', array('project_key' => $selected_project->getKey(), 'board_id' => $board->getID())); ?>">
                </ul>
            <?php endif; ?>
            <?php if ($pachno_user->canManageProjectReleases($selected_project)): ?>
                <table cellpadding=0 cellspacing=0 style="display: none; margin-left: 5px; width: 300px;" id="sprint_add_indicator">
                    <tr>
                        <td style="width: 20px; padding: 2px;"><?php echo image_tag('spinning_20.gif'); ?></td>
                        <td style="padding: 0px; text-align: left;"><?php echo __('Adding sprint, please wait'); ?>...</td>
                    </tr>
                </table>
            <?php endif; ?>
            <ul id="milestone_list" class="jsortable" data-sort-url="<?php echo make_url('project_sort_milestones', array('project_key' => $selected_project->getKey())); ?>">
                <?php foreach ($board->getMilestones() as $milestone): ?>
                    <?php include_component('milestonebox', array('milestone' => $milestone, 'board' => $board, 'include_counts' => !$milestone->isVisibleRoadmap())); ?>
                <?php endforeach; ?>
            </ul>
            <div id="no_milestones" style="<?php if (isset($milestone)) echo 'display: none;'; ?>">
                <?php echo $addmilestoneslabel; ?>
            </div>
        </div>
        <div id="project_backlog_sidebar">
            <div id="milestone_0" class="milestone_box open available backlog_milestone" data-milestone-id="0" data-issues-url="<?php echo make_url('agile_milestoneissues', array('project_key' => $board->getProject()->getKey(), 'milestone_id' => 0, 'board_id' => $board->getID())); ?>" data-assign-issue-url="<?php echo make_url('agile_assignmilestone', array('project_key' => $board->getProject()->getKey(), 'milestone_id' => 0)); ?>" data-backlog-search="<?php echo ($board->usesAutogeneratedSearchBacklog()) ? 'predefined_'.$board->getAutogeneratedSearch() : 'saved_'.$board->getBacklogSearchObject()->getID(); ?>">
                <div class="planning_indicator" id="milestone_0_indicator" style="display: none;"><?php echo image_tag('spinning_30.gif'); ?></div>
                <div class="header backlog" id="milestone_0_header">
                    <div class="milestone_basic_container">
                        <span class="milestone_name"><?php echo __('Backlog'); ?></span>
                        <div class="backlog_toggler dynamic_menu_link" onclick="$('project_planning').toggleClassName('left_toggled');" title="<?php echo __('Click to toggle the show / hide the backlog'); ?>"><?php echo image_tag('icon_sidebar_collapse.png'); ?></div>
                    </div>
                    <div class="milestone_counts_container">
                        <table>
                            <tr>
                                <td id="milestone_0_issues_count">-</td>
                                <td id="milestone_0_points_count" class="issue_estimates estimated_points">-</td>
                                <td id="milestone_0_hours_count" class="issue_estimates estimated_hours">-</td>
                            </tr>
                            <tr>
                                <td><?php echo __('Issues'); ?></td>
                                <td class="issue_estimates estimated_points"><?php echo __('Points'); ?></td>
                                <td class="issue_estimates estimated_hours"><?php echo __('Hours'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php echo image_tag('spinning_20.gif', array('id' => 'milestone_0_issues_indicator', 'class' => 'milestone_issues_indicator', 'style' => 'display: none;')); ?>
                </div>
                <ul id="milestone_0_issues" class="milestone_issues jsortable intersortable empty collapsed"></ul>
                <div class="milestone_no_issues" style="display: none;" id="milestone_0_unassigned"><?php echo __('There are no issues in the backlog'); ?></div>
                <div class="milestone_no_issues" style="display: none;" id="milestone_0_unassigned_filtered"><?php echo __('No issues in the backlog matches selected filters'); ?></div>
                <div class="milestone_error_issues" style="display: none;" id="milestone_0_initialize_error"><?php echo __('The issue list could not be loaded'); ?></div>
            </div>
        </div>
    </div>
</div>
<?php if ($pachno_user->isPlanningTutorialEnabled()): ?>
    <?php include_component('main/tutorial_planning', compact('board')); ?>
<?php endif; ?>
<script type="text/javascript">
    require(['domReady', 'pachno/index'], function (domReady, Pachno) {
        domReady(function () {
            Pachno.Project.Planning.initialize({dragdrop: <?php echo ($pachno_user->canAssignScrumUserStories($selected_project)) ? 'true' : 'false'; ?>});
        });
    });
</script>
