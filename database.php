<?php

class Database
{
    // InfinityFree Hosting Credentials
    private $host = "sql308.infinityfree.com";
    private $username = "if0_40678440";
    private $password = "Vysache2248";
    private $dbname = "if0_40678440_salon_db";

    protected $conn = null;

    public function connect()
    {
        // Return existing connection if already connected
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->dbname;charset=utf8mb4", 
                $this->username, 
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }
}