<?php
class API {
    public $db;
    public $C;
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->C = getController();
    }
}