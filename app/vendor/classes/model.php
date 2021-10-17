<?php
class Model {
    public $db;
    public $C;
    public function __construct($controller)
    {
        $this->db = Database::getInstance();
        $this->C = $controller;
    }

    public function model($model) {
        return model($model);
    }

    public function controller() {
        return getController();
    }
}