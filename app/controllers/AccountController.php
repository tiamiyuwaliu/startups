<?php
class AccountController extends Controller {

    public function index() {
        $this->setTitle(l('manage-accounts'));

        return $this->render($this->view('account/index'), true);
    }
}