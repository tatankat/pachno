<?php

    namespace pachno\core\modules\publish;

    use pachno\core\entities\Article;
    use pachno\core\entities\Project;
    use pachno\core\entities\tables\ArticleFiles;
    use pachno\core\entities\tables\Articles;
    use pachno\core\entities\tables\Links;
    use pachno\core\entities\tables\UserArticles;
    use pachno\core\entities\User;
    use pachno\core\framework;
    use pachno\core\framework\cli\Command;
    use pachno\core\framework\CoreModule;
    use pachno\core\framework\Event;
    use pachno\core\framework\Request;
    use pachno\core\helpers\TextParser;

    /**
     * The wiki class
     *
     * @package pachno
     * @subpackage publish
     *
     * @Table(name="\pachno\core\entities\tables\Modules")
     */
    class Publish extends CoreModule
    {

        const VERSION = '2.0';

        const PERMISSION_READ_ARTICLE = 'readarticle';

        const PERMISSION_EDIT_ARTICLE = 'editarticle';

        const PERMISSION_DELETE_ARTICLE = 'deletearticle';

        protected $_longname = 'Wiki';

        protected $_description = 'Enables Wiki-functionality';

        protected $_module_config_title = 'Wiki';

        protected $_module_config_description = 'Set up the Wiki module from this section';

        protected $_has_config_settings = true;

        public static function getArticleLink($article_name, $project = null, $mode = 'show')
        {
            $article = Articles::getTable()->getArticleByName($article_name, $project);
            if (!$article instanceof Article) {
                $article = new Article();
                $article->setName($article_name);
                $article->setProject($project);
            }

            return $article->getLink($mode);
        }

        public function hasProjectAwareRoute()
        {
            return true;
        }

        public function getProjectAwareRoute($project_key)
        {
            return framework\Context::getRouting()->generate('publish_article', ['article_name' => ucfirst($project_key) . ":MainPage"]);
        }

        public function postConfigSettings(Request $request)
        {
            if ($request->hasParameter('import_articles')) {
                $cc = 0;
                foreach ($request['import_article'] as $article_name => $import) {
                    $cc++;
                    Articles::getTable()->deleteArticleByName(urldecode($article_name));
                    $content = file_get_contents(PACHNO_MODULES_PATH . 'publish' . DS . 'fixtures' . DS . $article_name);
                    Article::createNew(urldecode($article_name), $content, null, ['overwrite' => true, 'noauthor' => true]);
                }
                framework\Context::setMessage('module_message', framework\Context::getI18n()->__('%number_of_articles articles imported successfully', ['%number_of_articles' => $cc]));
            } else {
                $settings = ['allow_camelcase_links', 'menu_title', 'hide_wiki_links', 'free_edit', 'require_change_reason'];
                foreach ($settings as $setting) {
                    if ($request->hasParameter($setting)) {
                        $this->saveSetting($setting, $request->getParameter($setting));
                    }
                }
            }
        }

        public function stripExclamationMark($matches, $parser)
        {
            return mb_substr($matches[0], 1);
        }

        /**
         * Helper function for obtaining article link during parsing of
         * an Article.
         *
         * @param array $matches Result of regular expression matching. First element should be the article name.
         * @param TextParser $parser Parser used for processing the originating article.
         *
         * @return string Fully HTML-encoded link (i.e. <a> tag). If article does not exist, tag will be assigned class "missing_wiki_page".
         */
        public function getArticleLinkTag($matches, $parser)
        {
            $article_link = $matches[0];
            $parser->addInternalLinkOccurrence($article_link);
            $article_name = self::getSpacedName($matches[0]);

            if (!framework\Context::isCLI()) {
                framework\Context::loadLibrary('ui');
                $options = [];

                // Assign CSS class to article if it does not exist.
                if (Articles::getTable()->getArticleByName($matches[0]) === null) {
                    $options["class"] = "missing_wiki_page";
                }

                return link_tag(make_url('publish_article', ['article_name' => $matches[0]]), $article_name, $options);
            } else {
                return $matches[0];
            }
        }

        public static function getSpacedName($camelcased)
        {
            return preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $camelcased);
        }

        public function getLatestArticles(Project $project = null)
        {
            return Articles::getTable()->getArticles($project);
        }

        public function getMenuItems($target_id = 0)
        {
            return Links::getTable()->getLinks('wiki', $target_id);
        }

        public function listen_frontpageArticle(Event $event)
        {
            $article = $this->getFrontpageArticle('main');
            if ($article instanceof Article && $article->hasContent()) {
                framework\ActionComponent::includeComponent('publish/articledisplay', ['article' => $article, 'show_title' => false, 'show_details' => false, 'show_actions' => false, 'embedded' => true]);
            }
        }

        public function getFrontpageArticle($type)
        {
            $article_name = ($type == 'main') ? 'FrontpageArticle' : 'FrontpageLeftmenu';
            $article = Articles::getTable()->getArticleByName($article_name);

            return $article;
        }

        public function listen_frontpageLeftmenu(Event $event)
        {
            $article = $this->getFrontpageArticle('menu');
            if ($article instanceof Article && $article->hasContent()) {
                framework\ActionComponent::includeComponent('publish/articledisplay', ['article' => $article, 'show_title' => false, 'show_details' => false, 'show_actions' => false, 'embedded' => true]);
            }
        }

        public function listen_projectLinks(Event $event)
        {
            framework\ActionComponent::includeComponent('publish/projectlinks', ['project' => $event->getSubject()]);
        }

        public function getPermissionDetails($permission)
        {
            $permissions = $this->_getPermissionslist();
            if (array_key_exists($permission, $permissions)) {
                return $permissions[$permission];
            }
        }

        protected function _getPermissionslist()
        {
            $permissions = [];
            $permissions['editwikimenu'] = ['description' => framework\Context::getI18n()->__('Can edit the wiki lefthand menu'), 'permission' => 'editwikimenu'];
            $permissions['readarticle'] = ['description' => framework\Context::getI18n()->__('Can access the project wiki'), 'permission' => 'readarticle'];
            $permissions['editarticle'] = ['description' => framework\Context::getI18n()->__('Can write articles in project wiki'), 'permission' => 'editarticle'];
            $permissions['deletearticle'] = ['description' => framework\Context::getI18n()->__('Can delete articles from project wiki'), 'permission' => 'deletearticle'];

            return $permissions;
        }

        public function listen_rolePermissionsEdit(Event $event)
        {
            framework\ActionComponent::includeComponent('configuration/rolepermissionseditlist', ['role' => $event->getSubject(), 'permissions_list' => $this->_getPermissionslist(), 'module' => 'publish', 'target_id' => '%project_key%']);
        }

        public function listen_BreadcrumbMainLinks(Event $event)
        {
            $link = ['url' => framework\Context::getRouting()->generate('publish'), 'title' => $this->getMenuTitle(false)];
            $event->addToReturnList($link);
        }

        public function getMenuTitle($project_context = null)
        {
            $project_context = ($project_context !== null) ? $project_context : framework\Context::isProjectContext();
            $i18n = framework\Context::getI18n();
            if (($menu_title = $this->getSetting('menu_title')) !== null) {
                switch ($menu_title) {
                    case 5:
                        return ($project_context) ? $i18n->__('Project archive') : $i18n->__('Archive');
                    case 3:
                        return ($project_context) ? $i18n->__('Project documentation') : $i18n->__('Documentation');
                    case 4:
                        return ($project_context) ? $i18n->__('Project documents') : $i18n->__('Documents');
                    case 2:
                        return ($project_context) ? $i18n->__('Project help') : $i18n->__('Help');
                }
            }

            return ($project_context) ? $i18n->__('Project wiki') : $i18n->__('Wiki');
        }

        public function listen_fileHasAccess(Event $event)
        {
            $article_ids = ArticleFiles::getTable()->getArticlesByFileID($event->getSubject()->getID());

            foreach ($article_ids as $article_id) {
                $article = new Article($article_id);
                if ($article->canRead()) {
                    $event->setProcessed();
                    $event->setReturnValue(true);
                    break;
                }
            }
        }

        public function listen_BreadcrumbProjectLinks(Event $event)
        {
            $link = ['url' => framework\Context::getRouting()->generate('publish_article', ['article_name' => framework\Context::getCurrentProject()->getKey() . ':MainPage']), 'title' => $this->getMenuTitle(true)];
            $event->addToReturnList($link);
        }

        /**
         * Header wiki menu and search dropdown / list
         *
         * @Listener(module="core", identifier="templates/headermainmenu::projectmenulinks")
         *
         * @param Event $event
         */
        public function listen_MenustripLinks(Event $event)
        {
            $article = Articles::getTable()->getArticleByName('MainPage', $event->getSubject());
            if (!$article instanceof Article) {
                return;
            }

            if ($event->getSubject() instanceof Project) {
                $project_url = framework\Context::getRouting()->generate('publish_project_article', ['project_key' => $event->getSubject()->getKey(), 'article_id' => $article->getId(), 'article_name' => 'MainPage']);
            } else {
                $project_url = framework\Context::getRouting()->generate('publish_article', ['article_id' => $article->getId(), 'article_name' => 'MainPage']);
            }

            $wiki_url = ($event->getSubject() instanceof Project && $event->getSubject()->hasWikiURL()) ? $event->getSubject()->getWikiURL() : null;
            $top_level_articles = Articles::getTable()->getManualSidebarArticles($article->getProject());
            framework\ActionComponent::includeComponent('publish/menustriplinks', ['project_url' => $project_url, 'project' => $event->getSubject(), 'wiki_url' => $wiki_url, 'top_level_articles' => $top_level_articles]);
        }

        public function listen_createNewProject(Event $event)
        {
            if (!Article::getByName(ucfirst($event->getSubject()->getKey()) . ':MainPage') instanceof Article) {
                $project_key = $event->getSubject()->getKey();
                $article = Article::createNew("Main Page", "This is the frontpage for {$event->getSubject()->getName()}", null, [], $event->getSubject());
                $this->loadArticles($project_key);
            }
        }

        public function getTabKey()
        {
            return (framework\Context::isProjectContext()) ? parent::getTabKey() : 'wiki';
        }

        public function canUserReadArticle($article_name)
        {
            return $this->_checkArticlePermissions($article_name, self::PERMISSION_READ_ARTICLE);
        }

        protected function _checkArticlePermissions($article_name, $permission_name)
        {
            $user = framework\Context::getUser();
            switch ($this->getSetting('free_edit')) {
                case 1:
                    $permissive = !$user->isGuest();
                    break;
                case 2:
                    $permissive = true;
                    break;
                case 0:
                default:
                    $permissive = false;
                    break;
            }
            $retval = $user->hasPermission($permission_name, $article_name, 'publish');
            if ($retval !== null) {
                return $retval;
            }
            $namespaces = explode(':', $article_name);
            if (count($namespaces) > 1) {
                array_pop($namespaces);
                $composite_ns = '';
                foreach ($namespaces as $namespace) {
                    $composite_ns .= ($composite_ns != '') ? ":{$namespace}" : $namespace;
                    $retval = $user->hasPermission($permission_name, $composite_ns, 'publish');
                    if ($retval !== null) {
                        return $retval;
                    }
                }
            }
            $permissive = ($permission_name == self::PERMISSION_READ_ARTICLE) ? false : $permissive;
            $retval = $user->hasPermission($permission_name, 0, 'publish');

            return ($retval !== null) ? $retval : $permissive;
        }

        public function canUserEditArticle($article_name)
        {
            return $this->_checkArticlePermissions($article_name, self::PERMISSION_EDIT_ARTICLE);
        }

        public function canUserDeleteArticle($article_name)
        {
            return $this->_checkArticlePermissions($article_name, self::PERMISSION_DELETE_ARTICLE);
        }

        public function listen_quicksearchDropdownFirstItems(Event $event)
        {
            $searchterm = $event->getSubject();
            framework\ActionComponent::includeComponent('publish/quicksearch_dropdown_firstitems', ['searchterm' => $searchterm]);
        }

        public function listen_quicksearchDropdownFoundItems(Event $event)
        {
            $searchterm = $event->getSubject();
            list ($resultcount, $articles) = Article::findArticlesByContentAndProject($searchterm, framework\Context::getCurrentProject());
            framework\ActionComponent::includeComponent('publish/quicksearch_dropdown_founditems', ['searchterm' => $searchterm, 'articles' => $articles, 'resultcount' => $resultcount]);
        }

        /**
         * Returns an array of articles ids which are "starred" by this user
         *
         * @return array
         */
        public function User__getStarredArticles(Event $event)
        {
            $user = $event->getSubject();
            $this->User__populateStarredArticles($user);
            $event->setProcessed();
            $event->setReturnValue($user->_retrieve('publish', 'starredarticles'));

            return;
        }

        /**
         * Populate the array of starred articles
         */
        protected function User__populateStarredArticles(User $user)
        {
            if ($user->_isset('publish', 'starredarticles') === null) {
                $articles = UserArticles::getTable()->getUserStarredArticles($user->getID());
                $user->_store('publish', 'starredarticles', $articles);
            }
        }

        /**
         * Returns whether or not an article is starred
         *
         * @return boolean
         */
        public function User__isArticleStarred(Event $event)
        {
            $user = $event->getSubject();
            $arguments = $event->getParameters();
            $article_id = $arguments[0];
            if ($user->_isset('publish', 'starredarticles')) {
                $articles = $user->getStarredArticles();
                $event->setProcessed();
                $event->setReturnValue(array_key_exists($article_id, $articles));

                return;
            } else {
                $event->setProcessed();
                $event->setReturnValue(UserArticles::getTable()->hasStarredArticle($user->getID(), $article_id));

                return;
            }
        }

        /**
         * Adds an article to the list of articles "starred" by this user
         *
         * @return boolean
         */
        public function User__addStarredArticle(Event $event)
        {
            $user = $event->getSubject();
            $arguments = $event->getParameters();
            $article_id = $arguments[0];
            if ($user->isLoggedIn() && !$user->isGuest()) {
                if (UserArticles::getTable()->hasStarredArticle($user->getID(), $article_id)) {
                    $event->setProcessed();
                    $event->setReturnValue(true);

                    return;
                }

                UserArticles::getTable()->addStarredArticle($user->getID(), $article_id);
                if ($user->_isset('publish', 'starredarticles')) {
                    $article = Articles::getTable()->selectById($article_id);
                    $articles = $user->_retrieve('publish', 'starredarticles');
                    $articles[$article->getID()] = $article;
                    $user->_store('publish', 'starredarticles', $articles);
                }
                $event->setProcessed();
                $event->setReturnValue(true);

                return;
            }

            $event->setProcessed();
            $event->setReturnValue(false);

            return;
        }

        /**
         * Removes an article from the list of flagged articles
         *
         * @param Event $event
         */
        public function User__removeStarredArticle(Event $event)
        {
            $user = $event->getSubject();
            $arguments = $event->getParameters();
            $article_id = $arguments[0];
            UserArticles::getTable()->removeStarredArticle($user->getID(), $article_id);
            if (isset($user->_starredarticles)) {
                $articles = $user->_retrieve('publish', 'starredarticles');
                unset($articles[$article_id]);
                $user->_store('publish', 'starredarticles', $articles);
            }
            $event->setProcessed();
            $event->setReturnValue(true);
        }

        /**
         * Removes an article from the list of flagged articles
         *
         * @param Event $event
         */
        public function Files__getUnattachedFiles(Event $event)
        {
            $event->setProcessed();
            $event->addToReturnList(ArticleFiles::getTable()->getLinkedFileIds());
        }

        public function getFontAwesomeIcon()
        {
            return 'newspaper';
        }

        public function getFontAwesomeIconStyle()
        {
            return 'fas';
        }

        public function getFontAwesomeColor()
        {
            return '#555';
        }

        protected function _initialize()
        {
            if ($this->isEnabled() && $this->getSetting('allow_camelcase_links')) {
                TextParser::addRegex('/(?<![\!|\"|\[|\>|\/\:])\b[A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', [$this, 'getArticleLinkTag']);
                TextParser::addRegex('/(?<!")\![A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', [$this, 'stripExclamationMark']);
            }
        }

        protected function _addAvailablePermissions()
        {
            $this->addAvailablePermission(self::PERMISSION_READ_ARTICLE, 'Read all articles');
            $this->addAvailablePermission(self::PERMISSION_EDIT_ARTICLE, 'Edit all articles');
            $this->addAvailablePermission(self::PERMISSION_DELETE_ARTICLE, 'Delete any articles');
        }

        protected function _addListeners()
        {
            Event::listen('core', 'index_left', [$this, 'listen_frontpageLeftmenu']);
            Event::listen('core', 'index_right_top', [$this, 'listen_frontpageArticle']);
            if ($this->isWikiTabsEnabled()) {
                Event::listen('core', 'project_overview_item_links', [$this, 'listen_projectLinks']);
                Event::listen('core', 'breadcrumb_main_links', [$this, 'listen_BreadcrumbMainLinks']);
                Event::listen('core', 'breadcrumb_project_links', [$this, 'listen_BreadcrumbProjectLinks']);
            }
            Event::listen('core', 'pachno\core\entities\Project::_postSave', [$this, 'listen_createNewProject']);
            Event::listen('core', 'pachno\core\entities\File::hasAccess', [$this, 'listen_fileHasAccess']);
            Event::listen('core', 'pachno\core\entities\User::__getStarredArticles', [$this, 'User__getStarredArticles']);
            Event::listen('core', 'pachno\core\entities\User::__isArticleStarred', [$this, 'User__isArticleStarred']);
            Event::listen('core', 'pachno\core\entities\User::__addStarredArticle', [$this, 'User__addStarredArticle']);
            Event::listen('core', 'pachno\core\entities\User::__removeStarredArticle', [$this, 'User__removeStarredArticle']);
            Event::listen('core', 'pachno\core\entities\\tables\Files::getUnattachedFiles', [$this, 'Files__getUnattachedFiles']);
            Event::listen('core', 'upload', [$this, 'listen_upload']);
            Event::listen('core', 'quicksearch_dropdown_firstitems', [$this, 'listen_quicksearchDropdownFirstItems']);
            Event::listen('core', 'quicksearch_dropdown_founditems', [$this, 'listen_quicksearchDropdownFoundItems']);
            Event::listen('core', 'rolepermissionsedit', [$this, 'listen_rolePermissionsEdit']);
        }

        public function isWikiTabsEnabled()
        {
            return (bool)($this->getSetting('hide_wiki_links') != 1);
        }

        protected function _install($scope)
        {
//            framework\Context::setPermission('article_management', 0, 'publish', 0, 1, 0, true, $scope);
//            $this->saveSetting('allow_camelcase_links', 1);
//            $this->saveSetting('require_change_reason', 0);

//            framework\Context::getRouting()->addRoute('publish_article', '/wiki/:article_name', 'publish', 'showArticle');
//            TextParser::addRegex('/(?<![\!|\"|\[|\>|\/\:])\b[A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', array($this, 'getArticleLinkTag'));
//            TextParser::addRegex('/(?<!")\![A-Z]+[a-z]+[A-Z][A-Za-z]*\b/', array($this, 'stripExclamationMark'));
        }

        protected function _loadFixtures($scope)
        {
            $this->loadFixturesArticles($scope);

            Links::getTable()->addLink('wiki', 0, 'MainPage', 'Wiki Frontpage', 1, $scope);
            Links::getTable()->addLink('wiki', 0, 'WikiFormatting', 'Formatting help', 2, $scope);
            Links::getTable()->addLink('wiki', 0, 'Category:Help', 'Help topics', 3, $scope);
            framework\Context::setPermission(self::PERMISSION_READ_ARTICLE, 0, 'publish', 0, 1, 0, true, $scope);
            framework\Context::setPermission(self::PERMISSION_EDIT_ARTICLE, 0, 'publish', 0, 1, 0, true, $scope);
            framework\Context::setPermission(self::PERMISSION_DELETE_ARTICLE, 0, 'publish', 0, 1, 0, true, $scope);
        }

        public function loadFixturesArticles($scope, $overwrite = true)
        {
            if (framework\Context::isCLI())
                Command::cli_echo("Loading default articles\n");
            $this->loadArticles('', $overwrite, $scope);
            if (framework\Context::isCLI())
                Command::cli_echo("... done\n");
        }

        public function loadArticles($namespace = '', $overwrite = true, $scope = null)
        {
            $scope = framework\Context::getScope()->getID();
            $namespace = mb_strtolower($namespace);
            $_path_handle = opendir(PACHNO_MODULES_PATH . 'publish' . DS . 'fixtures' . DS);
            while ($original_article_name = readdir($_path_handle)) {
                if (mb_strpos($original_article_name, '.') === false) {
                    $article_name = mb_strtolower($original_article_name);
                    $imported = false;
                    $import = false;
                    if ($namespace) {
                        if (mb_strpos(urldecode($article_name), "{$namespace}:") === 0 || (mb_strpos(urldecode($article_name), "category:") === 0 && mb_strpos(urldecode($article_name), "{$namespace}:") === 9)) {
                            $import = true;
                        }
                    } else {
                        if (mb_strpos(urldecode($article_name), "category:help:") === 0) {
                            $name_test = mb_substr(urldecode($article_name), 14);
                        } elseif (mb_strpos(urldecode($article_name), "category:") === 0) {
                            $name_test = mb_substr(urldecode($article_name), 9);
                        } else {
                            $name_test = urldecode($article_name);
                        }
                        if (mb_strpos($name_test, ':') === false)
                            $import = true;
                    }
                    if ($import) {
                        if (framework\Context::isCLI()) {
                            Command::cli_echo('Saving ' . urldecode($original_article_name) . "\n");
                        }
                        if ($overwrite) {
                            Articles::getTable()->deleteArticleByName(urldecode($original_article_name));
                        }
                        if (Articles::getTable()->getArticleByName(urldecode($original_article_name)) === null) {
                            $content = file_get_contents(PACHNO_MODULES_PATH . 'publish' . DS . 'fixtures' . DS . $original_article_name);
                            Article::createNew(urldecode($original_article_name), $content, $scope, ['overwrite' => $overwrite, 'noauthor' => true]);
                            $imported = true;
                        }
                        Event::createNew('publish', 'fixture_article_loaded', urldecode($original_article_name), ['imported' => $imported])->trigger();
                    }
                }
            }
        }

        protected function _uninstall()
        {
            if (framework\Context::getScope()->getID() == 1) {
                Articles::getTable()->drop();
            }
            Links::getTable()->removeByTargetTypeTargetIDandLinkID('wiki', 0);
            parent::_uninstall();
        }

    }
