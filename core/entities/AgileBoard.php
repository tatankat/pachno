<?php

    namespace pachno\core\entities;

    use pachno\core\entities\common\IdentifiableScoped;
    use pachno\core\entities\tables\Issues;
    use pachno\core\framework\Context;

    /**
     * Agile board class
     *
     * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
     * @version 3.1
     * @license http://opensource.org/licenses/MPL-2.0 Mozilla Public License 2.0 (MPL 2.0)
     * @package pachno
     * @subpackage agile
     */

    /**
     * Agile board class
     *
     * @package pachno
     * @subpackage agile
     *
     * @Table(name="\pachno\core\entities\tables\AgileBoards")
     */
    class AgileBoard extends IdentifiableScoped
    {

        const TYPE_GENERIC = 0;

        const TYPE_SCRUM = 1;

        const TYPE_KANBAN = 2;

        const SWIMLANES_NONE = '';

        const SWIMLANES_ISSUES = 'issues';

        const SWIMLANES_GROUPING = 'grouping';

        const SWIMLANES_EXPEDITE = 'expedite';

        const BACKGROUND_COLOR_DEFAULT = '#0C8990';
        const BACKGROUND_COLOR_ONE = '#7d7c84';
        const BACKGROUND_COLOR_TWO = '#00aa7f';
        const BACKGROUND_COLOR_THREE = '#d62246';
        const BACKGROUND_COLOR_FOUR = '#4b1d3f';
        const BACKGROUND_COLOR_FIVE = '#dbd56e';
        const BACKGROUND_COLOR_SIX = '#88ab75';
        const BACKGROUND_COLOR_SEVEN = '#de8f6e';

        /**
         * The name of the board
         *
         * @var string
         * @Column(type="string", length=200)
         */
        protected $_name;

        /**
         * Board description
         *
         * @var string
         * @Column(type="string", length=200)
         */
        protected $_description;

        /**
         * Whether this board is the private
         *
         * @var boolean
         * @Column(type="boolean", default=1)
         */
        protected $_is_private = true;

        /**
         * @var User
         * @Column(type="integer", length=10)
         * @Relates(class="\pachno\core\entities\User")
         */
        protected $_user_id;

        /**
         * @var Project
         * @Column(type="integer", length=10)
         * @Relates(class="\pachno\core\entities\Project")
         */
        protected $_project_id;

        /**
         * @var Issuetype
         * @Column(type="integer", length=10)
         * @Relates(class="\pachno\core\entities\Issuetype")
         */
        protected $_epic_issuetype_id;

        /**
         * @var Issuetype
         * @Column(type="integer", length=10)
         * @Relates(class="\pachno\core\entities\Issuetype")
         */
        protected $_task_issuetype_id;

        /**
         * @var SavedSearch
         * @Column(type="integer", length=10)
         * @Relates(class="\pachno\core\entities\SavedSearch")
         */
        protected $_backlog_search_id;

        /**
         * @var File
         * @Column(type="integer", length=10)
         * @Relates(class="\pachno\core\entities\File")
         */
        protected $_background_file_id;

        /**
         * @var string
         * @Column(type="string", length=10)
         */
        protected $_background_color = '';

        /**
         * @var integer
         * @Column(type="integer", length=10)
         */
        protected $_autogenerated_search = SavedSearch::PREDEFINED_SEARCH_PROJECT_OPEN_ISSUES_INCLUDING_SUBPROJECTS;

        /**
         * The board type
         *
         * @var integer
         * @Column(type="integer", length=10)
         */
        protected $_type = self::TYPE_SCRUM;

        /**
         * Whether to use swimlanes
         *
         * @var boolean
         * @Column(type="boolean", default=false)
         */
        protected $_use_swimlanes = false;

        protected $_swimlanes = [];

        /**
         * Swimlane type
         *
         * @var string
         * @Column(type="string", length=50, default="issuetype")
         */
        protected $_swimlane_type = self::SWIMLANES_ISSUES;

        /**
         * Swimlane identifier field
         *
         * @var string
         * @Column(type="string", length=50, default="issuetype")
         */
        protected $_swimlane_identifier = "issuetype";

        /**
         * Swimlane field value
         *
         * @var array
         * @Column(type="serializable", length=500)
         */
        protected $_swimlane_field_values = [];

        /**
         * Cached search object
         * @var SavedSearch
         */
        protected $_search_object;

        /**
         * Array of epic issues
         *
         * @var Issue[]
         */
        protected $_epic_issues = null;

        /**
         * Board columns
         *
         * @var BoardColumn[]
         * @Relates(class="\pachno\core\entities\BoardColumn", collection=true, foreign_column="board_id", orderby="sort_order")
         */
        protected $_board_columns = null;

        /**
         * Issue field value
         *
         * @var array
         * @Column(type="serializable", length=500)
         */
        protected $_issue_field_values = [];

        public static function getAvailableColors()
        {
            return [
                self::BACKGROUND_COLOR_DEFAULT,
                self::BACKGROUND_COLOR_ONE,
                self::BACKGROUND_COLOR_TWO,
                self::BACKGROUND_COLOR_THREE,
                self::BACKGROUND_COLOR_FOUR,
                self::BACKGROUND_COLOR_FIVE,
                self::BACKGROUND_COLOR_SIX,
                self::BACKGROUND_COLOR_SEVEN,
            ];
        }

        /**
         * Returns the associated user
         *
         * @return User
         */
        public function getUser()
        {
            return $this->_b2dbLazyLoad('_user_id');
        }

        public function setUser($user)
        {
            $this->_user_id = $user;
        }

        public function setProject($project)
        {
            $this->_project_id = $project;
        }

        public function setEpicIssuetype($epic_issuetype_id)
        {
            $this->_epic_issuetype_id = $epic_issuetype_id;
        }

        public function setTaskIssuetype($task_issuetype_id)
        {
            $this->_task_issuetype_id = $task_issuetype_id;
        }

        public function getTaskIssuetypeID()
        {
            return ($this->getTaskIssuetype() instanceof Issuetype) ? $this->getTaskIssuetype()->getID() : 0;
        }

        /**
         * Returns the associated task issue type
         *
         * @return Issuetype
         */
        public function getTaskIssuetype()
        {
            return $this->_b2dbLazyLoad('_task_issuetype_id');
        }

        public function setBacklogSearch($backlog_search)
        {
            $this->_backlog_search_id = $backlog_search;
            $this->_autogenerated_search = null;
            $this->_search_object = null;
        }

        public function getBacklogSearchIdentifier()
        {
            return ($this->usesAutogeneratedSearchBacklog()) ? 'predefined_' . $this->getAutogeneratedSearch() : 'saved_' . $this->getBacklogSearchObject()->getID();
        }

        public function usesAutogeneratedSearchBacklog()
        {
            return (bool)$this->_autogenerated_search;
        }

        public function getAutogeneratedSearch()
        {
            return $this->_autogenerated_search;
        }

        public function setAutogeneratedSearch($autogenerated_search)
        {
            $this->_autogenerated_search = $autogenerated_search;
            $this->_backlog_search_id = null;
            $this->_search_object = null;
        }

        /**
         * Returns the associated search object
         *
         * @return SavedSearch
         */
        public function getBacklogSearchObject()
        {
            if ($this->_search_object === null) {
                if ($this->usesSavedSearchBacklog()) {
                    $this->_search_object = $this->getBacklogSearch();
                } elseif (!$this->_search_object instanceof SavedSearch) {
                    $this->_search_object = SavedSearch::getPredefinedSearchObject($this->_autogenerated_search);
                    $this->_search_object->setFilter('issuetype', SearchFilter::createFilter('issuetype', ['o' => '!=', 'v' => $this->getEpicIssuetypeID()]));
                    $this->_search_object->setFilter('milestone', SearchFilter::createFilter('milestone', ['o' => '!=', 'v' => null]));
                }
                $this->_search_object->setIssuesPerPage(0);
                $this->_search_object->setOffset(0);
                $this->_search_object->setSortFields([Issues::MILESTONE_ORDER => 'desc']);
                $this->_search_object->setGroupBy(null);
            }

            return $this->_search_object;
        }

        public function usesSavedSearchBacklog()
        {
            return (bool)$this->_backlog_search_id;
        }

        /**
         * Returns the associated backlog saved search
         *
         * @return SavedSearch
         */
        public function getBacklogSearch()
        {
            return $this->_b2dbLazyLoad('_backlog_search_id');
        }

        public function getEpicIssuetypeID()
        {
            return ($this->getEpicIssuetype() instanceof Issuetype) ? $this->getEpicIssuetype()->getID() : 0;
        }

        /**
         * Returns the associated epic issue type
         *
         * @return Issuetype
         */
        public function getEpicIssuetype()
        {
            return $this->_b2dbLazyLoad('_epic_issuetype_id');
        }

        public function getName()
        {
            return $this->_name;
        }

        public function setName($name)
        {
            $this->_name = $name;
        }

        public function hasDescription()
        {
            return (bool)($this->getDescription() != '');
        }

        public function getDescription()
        {
            return $this->_description;
        }

        public function setDescription($description)
        {
            $this->_description = $description;
        }

        public function isPrivate()
        {
            return $this->getIsPrivate();
        }

        public function getIsPrivate()
        {
            return $this->_is_private;
        }

        public function setIsPrivate($is_private)
        {
            $this->_is_private = $is_private;
        }

        public function getType()
        {
            return $this->_type;
        }

        public function setType($type)
        {
            $this->_type = $type;
        }

        public function getBacklogIssuesUrl()
        {
            if ($this->usesSavedSearchBacklog()) {
                $url = Context::getRouting()->generate('project_issues', ['project_key' => $this->getProject()->getKey(), 'saved_search' => $this->getBacklogSearch()->getID(), 'search' => true, 'format' => 'backlog']);
            } else {
                $url = Context::getRouting()->generate('project_issues', ['project_key' => $this->getProject()->getKey(), 'predefined_search' => $this->getAutogeneratedSearch(), 'search' => true, 'format' => 'backlog']);
            }

            return $url;
        }

        /**
         * Returns the associated project
         *
         * @return Project
         */
        public function getProject()
        {
            return $this->_b2dbLazyLoad('_project_id');
        }

        public function getEpicIssues()
        {
            if ($this->_epic_issues === null) {
                $this->_epic_issues = Issues::getTable()->getOpenIssuesByProjectIDAndIssuetypeID($this->getProject()->getID(), $this->getEpicIssuetypeID());
            }

            return $this->_epic_issues;
        }

        public function getDefaultSelectedMilestone()
        {
            foreach ($this->getMilestones() as $milestone) {
                if (!$milestone->isReached()) {
                    return $milestone;
                }
            }
        }

        public function getMilestones()
        {
            return $this->getProject()->getOpenMilestones();
        }

        public function getReleases()
        {
            return $this->getProject()->getUnreleasedBuilds();
        }

        public function useSwimlanes($use_swimlanes = true)
        {
            $this->setUseSwimlanes($use_swimlanes);
        }

        public function setUseSwimlanes($use_swimlanes = true)
        {
            $this->_use_swimlanes = $use_swimlanes;
        }

        public function clearSwimlaneType()
        {
            $this->_swimlane_type = null;
        }

        public function clearSwimlaneIdentifier()
        {
            $this->_swimlane_identifier = null;
        }

        public function clearSwimlaneFieldValues()
        {
            $this->_swimlane_field_values = [];
        }

        public function clearIssueFieldValues()
        {
            $this->_issue_field_values = [];
        }

        public function hasSwimlaneFieldValue($value)
        {
            return in_array($value, $this->getSwimlaneFieldValues());
        }

        public function getSwimlaneFieldValues()
        {
            return $this->_swimlane_field_values;
        }

        public function setSwimlaneFieldValues($swimlane_field_values)
        {
            $this->_swimlane_field_values = $swimlane_field_values;
        }

        public function hasSwimlaneFieldValues()
        {
            return (count($this->getSwimlaneFieldValues()) > 0);
        }

        public function hasIssueFieldValue($value)
        {
            return in_array($value, $this->getIssueFieldValues());
        }

        public function getIssueFieldValues()
        {
            return $this->_issue_field_values;
        }

        public function setIssueFieldValues($issue_field_values)
        {
            $this->_issue_field_values = $issue_field_values;
        }

        public function hasIssueFieldValues()
        {
            return (count($this->getIssueFieldValues()) > 0);
        }

        /**
         * Returns an array of board columns
         *
         * @return BoardColumn[]
         */
        public function getColumns()
        {
            return $this->_b2dbLazyLoad('_board_columns');
        }

        public function getStatusIds()
        {
            $status_ids = [];
            foreach ($this->getColumns() as $column) {
                foreach ($column->getStatusIds() as $statusId) {
                    $status_ids[$statusId] = $statusId;
                }
            }

            return $status_ids;
        }

        /**
         * Retrieve all available swimlanes for the selected milestone
         *
         * @param Milestone $milestone
         *
         * @return BoardSwimlane[]
         */
        public function getMilestoneSwimlanes(Milestone $milestone = null)
        {
            $swimlanes = [];

            if ($this->usesSwimlanes() && count($this->getColumns())) {
                switch ($this->getSwimlaneType()) {
                    case self::SWIMLANES_EXPEDITE:
                    case self::SWIMLANES_GROUPING:
                        switch ($this->getSwimlaneIdentifier()) {
                            case 'priority':
                                $items = Priority::getAll();
                                break;
                            case 'severity':
                                $items = Severity::getAll();
                                break;
                            case 'category':
                                $items = Category::getAll();
                                break;
                            default:
                                $items = [];
                                break;
                        }
                        if ($this->getSwimlaneType() == self::SWIMLANES_EXPEDITE) {
                            $expedite_items = [];
                            foreach ($this->getSwimlaneFieldValues() as $value) {
                                if (array_key_exists($value, $items)) {
                                    $expedite_items[$items[$value]->getID()] = $items[$value];
                                    unset($items[$value]);
                                }
                            }

                            if (count($expedite_items)) {
                                $swimlanes[] = ['identifiables' => $expedite_items];
                            }
                            $swimlanes[] = ['identifiables' => $items];
                            $swimlanes[] = ['identifiables' => 0];
                        } else {
                            foreach ($items as $item) {
                                $swimlanes[] = ['identifiables' => $item];
                            }
                            $swimlanes[] = ['identifiables' => 0];
                        }
                        break;
                    case self::SWIMLANES_ISSUES:
                        $issues = ($milestone instanceof Milestone) ? $milestone->getIssues() : $this->getBacklogSearchObject()->getIssues();
                        foreach ($issues as $issue) {
                            if ($issue->isChildIssue()) {
                                foreach ($issue->getParentIssues() as $parent) {
                                    if ($parent->getIssueType()->getID() != $this->getEpicIssuetypeID()) continue 2;
                                }
                            }

                            if (in_array($issue->getIssueType()->getID(), $this->getSwimlaneFieldValues())) {
                                $swimlanes[] = ['identifiables' => $issue];
                            }
                        }
                        $swimlanes[] = ['identifiables' => 0];
                        break;
                }
            } else {
                $swimlanes[] = ['identifiables' => 0];
            }

            $boardSwimlanes = [];
            foreach ($swimlanes as $details) {
                $swimlane = new BoardSwimlane();
                $swimlane->setBoard($this);
                $swimlane->setIdentifierType($this->getSwimlaneType());
                $swimlane->setIdentifierGrouping($this->getSwimlaneIdentifier());
                $swimlane->setIdentifiables($details['identifiables']);
                $swimlane->setMilestone($milestone);
                $boardSwimlanes[] = $swimlane;
            }

            return $boardSwimlanes;
        }

        public function usesSwimlanes()
        {
            return $this->_use_swimlanes;
        }

        public function getSwimlaneType()
        {
            return $this->_swimlane_type;
        }

        public function setSwimlaneType($swimlane_type)
        {
            $this->_swimlane_type = $swimlane_type;
        }

        public function getSwimlaneIdentifier()
        {
            return $this->_swimlane_identifier;
        }

        public function setSwimlaneIdentifier($swimlane_identifier)
        {
            $this->_swimlane_identifier = $swimlane_identifier;
        }

        /**
         * Set the file associated with this build
         *
         * @param File $file
         */
        public function setBackgroundFile(File $file)
        {
            $this->_background_file_id = $file;
        }

        public function clearFile()
        {
            $this->_background_file_id = null;
        }

        /**
         * Return whether this build has a file associated to it
         *
         * @return boolean
         */
        public function hasBackgroundFile()
        {
            return (bool) ($this->getBackgroundFile() instanceof File);
        }

        /**
         * Return the file associated with this build, if any
         *
         * @return File
         */
        public function getBackgroundFile()
        {
            return $this->_b2dbLazyLoad('_background_file_id');
        }

        /**
         * @return string
         */
        public function getBackgroundColor()
        {
            return $this->_background_color;
        }

        /**
         * @param string $background_color
         */
        public function setBackgroundColor($background_color = '')
        {
            $this->_background_color = $background_color;
        }

        public function hasBackground()
        {
            return $this->hasBackgroundFile() || $this->getBackgroundColor() != '';
        }

        public function toJSON($detailed = true)
        {
            $json = parent::toJSON($detailed);
            $json['background_color'] = $this->getBackgroundColor();
            $json['background_file_url'] = ($this->_background_file_id) ? Context::getRouting()->generate('showfile', ['id' => $this->_background_file_id]) : '';
            $json['name'] = $this->getName();
            $json['type'] = $this->getType();
            $json['is_private'] = $this->isPrivate();
            $json['backlog_search'] = $this->getBacklogSearchIdentifier();
            $json['url'] = Context::getRouting()->generate('agile_whiteboardissues', ['project_key' => $this->getProject()->getKey(), 'board_id' => $this->getID()]) . '?format=json';
            $json['swimlane_type'] = $this->getSwimlaneType();
            $json['swimlane_identifier'] = $this->getSwimlaneIdentifier();
            $json['swimlane_field_values'] = $this->getSwimlaneFieldValues();
            $json['columns'] = [];
            foreach ($this->getColumns() as $column) {
                $json['columns'][] = $column->toJSON();
            }

            return $json;
        }

        public function toMilestoneJSON(Milestone $milestone = null, $column_id = null)
        {
            $json = ['swimlanes' => []];

            if (count($this->getColumns())) {
                foreach ($this->getMilestoneSwimlanes($milestone) as $swimlane) {
                    $json['swimlanes'][] = $swimlane->toJSON($column_id);
                }
            }

            return $json;
        }

    }
