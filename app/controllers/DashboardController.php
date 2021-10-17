<?php
class DashboardController extends Controller {
    public function index() {
        $this->setTitle(l('home'));
        $this->setActiveIconMenu('home');

        return $this->render($this->view('dashboard/index'), true);
    }

    public function search() {
        $term = $this->request->input('term');

        $caption = $this->model('caption')->countSearch($term);
        $hashtag  = $this->model('hashtag')->countSearch($term);
        $file = $this->model('file')->countSearch($term);
        $graphics = $this->model('admin')->countGraphicSearch($term);
        return $this->view('search/result', array(
            'term' =>$term,
            'drafts' => $this->model('post')->countSearch($term, 'draft'),
            'scheduled' => $this->model('post')->countSearch($term, 'scheduled'),
            'published' =>$this->model('post')->countSearch($term, 'published'),
            'caption' => $caption,
            'hashtag' => $hashtag,
            'party' => $this->model('party')->countSearch($term, 'party'),
            'templates' => $this->model('party')->countSearch($term, 'template'),
            'file' => $file,
            'graphics' => $graphics
        ));
    }

    public function notifications() {
        return $this->view('notifications/index');
    }

    public function loadHelp() {
        $type = $this->request->input('type');
        $term = $this->request->input('term');
        $page = $this->request->input('page');
        $basic = ($type == 'basic') ? 1: 0;
        $goals = ($type == 'goals') ? 1: 0;
        $helps = $this->model('admin')->getHelps(null,null,$basic,$goals, $term);
        return $this->view('help/load', array('helps' => $helps));
    }
    public function getstarted() {
        $this->setfrontend();
        $this->setWrapLayout('auth/layout');

        if ($val = $this->request->input('val')) {
            $name = $val['name'];
            $describe = $val['describe'];
            $products = (isset($val['products'])) ? implode(',',$val['products']) : '';

            Database::getInstance()->query("UPDATE users SET company=?,company_products=?,personality=?,get_started=? WHERE id=?", $name, $products,$describe, 1, $this->model('user')->authId);

            $id = $this->model('workspace')->create(array(
                'title' => $name.' Space',
                'timezone' => $this->model('user')->authUser['timezone']
            ));
            $defaultLabels = array(
                array('color' => '#FF5722', 'title' => 'Advertisement'),
                array('color' => '#673AB7', 'title' => 'Parties'),
                array('color' => '#3F51B5', 'title' => 'Engagement'),
                array('color' => '#4CAF50', 'title' => 'Games'),
            );
            $defaultLabels = perfectSerialize($defaultLabels);
            Database::getInstance()->query("UPDATE workspace SET labels=? WHERE id=?", $defaultLabels, $id);
            session_put('selected-workspace', perfectSerialize($id));
            return json_encode(array(
                'type' => 'normal-url',
                'value' => url('home')
            ));
        }
        return $this->render($this->view('getstarted/index'), true);
    }
}