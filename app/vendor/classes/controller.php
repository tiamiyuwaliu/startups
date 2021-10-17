<?php
class Controller {
    public $request;
    public $db;
    private $models = array();
    private $apis = array();

    private  $pageTitle;
    private  $titleSeparator = "|";

    private $metaTags = array();

    private $headerContent = "";
    private $footerBeforeContent = "";
    private $footerAfterContent = "";

    public $pageType = 'backend';
    private $mainLayout = "main::includes/layout";
    private $wrapLayout = 'main::includes/app/layout';
    public $appSideLayout = "";


    public $theme = "main";
    public $showHeader = true;

    public $activeMenu = "";
    public $activeSubMenu = "";
    public $activeIconMenu = "";
    public $subMenuIcon = '';

    public $keywords = "";
    public $description = "";
    public $favicon = '';

    public $loginRequired = true;
    public $adminRequired = false;


    public $breadcrumbs = array();
    public $useBreadcrumbs = true;

    public $collapsed = false;
    public $useEditor = false;
    public $fullLoader = true;

    public $selectedAccount = array();
    public $workspaceId = null;
    public $workspace = null;
    public function __construct($request)
    {
        $this->request = $request;
        $this->db = Database::getInstance();
        $this->theme = config('theme', 'main');
        $this->setTitle();
        $this->keywords = config('site-keywords', '');
        $this->description = config('site-description', '');
        $this->favicon = assetUrl(config('favicon', 'favicon.png'));
        $this->model('user')->processLogin();

        $headerContent = '<meta property="og:image" content="'.assetUrl(config('site_logo', 'assets/images/logo.png')).'"/>';
        $headerContent .= '<meta property="og:title" content="'.config('site-title').'"/>';
        $headerContent .= '<meta property="og:url" content="'.url().'"/>';
        $headerContent .= '<meta property="og:description" content="'.config('site-description', '').'"/>';
        $this->addHeaderContent($headerContent);



        Hook::getInstance()->fire('controller.loaded', null, array($this));


    }

    public function isDemo() {
        return config('demo');
    }
    public function defendDemo() {
        if (config('demo')) {
            if ($this->model('user')->authId == 66) {
                if (is_ajax()) {
                    exit(json_encode(array(
                        'type' => 'error',
                        'message' => 'Action is disabled on demo'
                    )));
                } else {
                    $this->request->redirect_back();
                }
            }
        }
    }

    public function setfrontend() {
        $this->pageType = 'frontend';
        return $this;
    }

    public function setAdminLayout() {
        $this->pageType = 'admincp';
        $this->setWrapLayout('includes/admin/layout');
        return $this;
    }

    public function setLayout($layout) {
        $this->mainLayout = $layout;
        return $this;
    }

    public function setWrapLayout($layout) {
        $this->wrapLayout = $layout;
        return $this;
    }

    public function setSideLayout($layout) {
        $this->appSideLayout = $layout;
        return $this;
    }

    public function setActiveMenu($menu) {
        $this->activeMenu = $menu;
        return $this;
    }

    public function setActiveSubMenu($menu) {
        $this->activeSubMenu = $menu;
        return $this;
    }

    public function setActiveIconMenu($menu) {
        $this->activeIconMenu = $menu;
        return $this;
    }

    public function render($content, $wrap = false) {

        if ($wrap) {
            $content = $this->view($this->wrapLayout, array('content' => $content));
        }



        if (is_ajax()) {
            //if (!$this->model('user')->isLoggedIn()) exit('login');

            return json_encode(array(
                'title' => $this->pageTitle,
                'content' => $content,
                'container' => '#page-container',
            ));
        }

        //if ($this->request->segment(0) == 'admin') $this->mainLayout = "main::admin/includes/layout";
        $content = $this->view($this->mainLayout, array(
            'content' => $content,
            'title' => $this->pageTitle,
            'header_content' => $this->headerContent,
            'active_menu' => $this->activeMenu,
            'keywords' => $this->keywords,
            'description' => $this->description,
            'favicon' => $this->favicon,
            'pageType' => $this->pageType,
            'beforeContent' => $this->footerBeforeContent,
            'afterContent' => $this->footerAfterContent
        ));
        $output = "";

        $output .= $content;

        return $output;
    }

    public function setTitle($title = "") {
        $titleStr = config('site-title', '');
        if ($title) $titleStr .= " ".$this->titleSeparator." {$title}";
        $this->pageTitle = $titleStr;
        return $this;
    }

    public function getTitle() {
        return $this->pageTitle;
    }

    public function addHeaderContent($content = "") {
        $this->headerContent .= $content;
        return $this;
    }

    public function addFooterBeforeContent($content = "") {
        $this->footerBeforeContent .= $content;
        return $this;
    }

    public function addFooterAfterContent($content = "") {
        $this->footerAfterContent .= $content;
        return $this;
    }

    public function view($view, $param = array()) {
        $param = array_merge(array(
            'C' => $this,
            'request' => $this->request
        ), $param);
        return View::instance()->find($view, $param);
    }

    public function model($model) {
        if (isset($this->models[$model])) return $this->models[$model];
        $modelObj = $this->loadModel($model);
        if ($model) {
            $this->models[$model] = $modelObj;
            return $this->models[$model];
        } else {
            //exit($model.' class does not exists');
        }

    }

    public function loadModel($model) {
        $base = path('app/model/');

        if (preg_match("@::@", $model)) {
            list($module, $model) = explode("::", $model);
            $base = path('module/'.$module.'/model/');
            if ($this->request->isSocial($module)) {
                $base = path('app/social/'.$model.'/model/');
            }
        }
        $modelFile = $base.$model.'.php';
        include_once $modelFile;
        $model = ucwords($model).'Model';
        if (class_exists($model)) {
            return new $model($this);
        }
        return false;
    }

    public function api($api) {
        if (isset($this->apis[$api])) return $this->apis[$api];
        $apiObj = $this->loadApi($api);
        if ($api) {
            $this->apis[$api] = $apiObj;
            return $this->apis[$api];
        } else {
            //exit($api.' class does not exists');
        }

    }

    public function loadApi($api) {
        $base = path('app/api/');
        if (preg_match("@::@", $api)) {
            list($module, $api) = explode("::", $api);
            $base = path('module/'.$module.'/api/');
            if ($this->request->isSocial($module)) {
                $base = path('app/social/'.$api.'/api/');
            }
        }
        $api = ucwords($api).'API';
        $modelFile = $base.$api.'.php';
        include_once $modelFile;

        if (class_exists($api)) {
            return new $api($this);
        }
        return false;
    }

    public function after(){}

    public function before() {

        if ($selectedAccount = session_get('selected-account') ) {
            $this->selectedAccount = perfectUnserialize($selectedAccount);
        }

        //if (empty($this->selectedAccount)) $this->selectedAccount[] = $this->model('social')->firstAccountId();
        if ($switchAccount = $this->request->input('account')) {
            if ($switchAccount == 'clear') {
                $this->selectedAccount  = array();
            } else {
                $this->selectedAccount  = explode(',', $switchAccount);

            }


        }
        session_put('selected-account', perfectSerialize($this->selectedAccount));

        if ($workspaceStored = session_get('selected-workspace')) {
            $this->workspaceId = perfectUnserialize($workspaceStored);
        }

        if ($workspace = $this->request->input('workspace')) {
            $workspace = $this->model('workspace')->getWorkspaceData($workspace);
            if ($workspace) $this->workspaceId = $workspace;
        }
        if ($this->workspaceId) {
            session_put('selected-workspace', perfectSerialize($this->workspaceId));
            $this->workspace = $this->model('workspace')->find($this->workspaceId);
        } else {
            if ($this->model('user')->isLoggedIn()) {
                $workspaces = $this->model('workspace')->getMyWorkspace();
                if ($workspaces) {
                    $this->workspace = $workspaces[0];
                    $this->workspaceId = $this->workspace['id'];
                } else {
                    $workspaces = $this->model('workspace')->getAssignedWorkspace();
                    if ($workspaces) {
                        $this->workspace = $workspaces[0];
                        $this->workspaceId = $this->workspace['id'];
                    }
                }
            }
        }

        //set users owner base on the workspace
        if ($this->workspace) {
            $this->model('user')->authOwnerId = $this->workspace['userid'];
            $this->model('user')->authOwner = $this->model('user')->getUser($this->workspace['userid']);
        }

        if ($this->model('user')->isLoggedIn()) {
            $user = $this->model('user')->authOwner;
            if($user['timezone']) date_default_timezone_set($user['timezone']);
            if (!$user['get_started'] and $this->request->segment(0) != 'getstarted'){
                return $this->request->redirect(url('getstarted'));
            }


            if ($user['expire_date'] and $user['expire_date']<time()) {
                if ($this->request->segment(0) != 'expired') {
                    return $this->request->redirect(url('expired'));
                }
            }
        }
    }

    public function securePage() {
        if (!$this->model('user')->isLoggedIn()) {
            if (is_ajax()) {
                exit('login');
            } else {
                $this->request->redirect(url('login'));
            }
        }
        if ($this->adminRequired) {
            if (!$this->model('user')->isAdmin()) $this->request->redirect(url('login'));
        }
    }


}