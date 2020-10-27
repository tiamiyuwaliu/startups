<?php
class AdminController extends  Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setAdminLayout();
    }

    public function index() {
        $this->setTitle(l('dashboard'));

        return $this->render($this->view('admin/dashboard/index'), true);
    }
}