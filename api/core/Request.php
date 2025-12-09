<?php
class Request {
    public $method;
    public $path;
    public $body;

    public function __construct() {
        $this->method = $_SERVER["REQUEST_METHOD"];
        $this->path = strtok($_SERVER["REQUEST_URI"], "?");
        $this->body = json_decode(file_get_contents("php://input"), true);
    }
}
