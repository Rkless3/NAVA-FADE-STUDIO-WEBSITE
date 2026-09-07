<?php

class Database
{
    private string $host = "localhost";
    private string $db_name = "nava_fade_studio";
    private string $username = "root";
    private string $password = "";

    public PDO $conn;

    public function connect(): PDO
    {
        $this->conn = new PDO(
            "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
            $this->username,
            $this->password
        );

        $this->conn->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->conn->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        return $this->conn;
    }
}