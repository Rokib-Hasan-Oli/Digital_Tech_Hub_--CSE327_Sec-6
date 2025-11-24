/**
 * Database Connection Class
 * * Implements the SINGLETON Design Pattern.
 */
<?php

class Database {

    private static $instance = null;
    
    private $connection;

    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'digital_tech_hub';

    private function __construct() {
 
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            die("Database Connection Failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function __clone() { }

    public function __wakeup() { }
}
?>