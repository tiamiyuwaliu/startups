<?php
class DashboardController extends Controller {
    public function index() {
        $this->setTitle(l('dashboard'));

        return $this->render($this->view('dashboard/index'), true);
    }
}