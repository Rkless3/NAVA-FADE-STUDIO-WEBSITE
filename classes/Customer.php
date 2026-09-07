<?php

class Customer
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }


    // =========================================
    // REGISTER CUSTOMER
    // =========================================

    public function register(
        string $full_name,
        string $email,
        string $contact_number,
        string $password
    ): bool {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $query = "
            INSERT INTO customers (
                full_name,
                email,
                contact_number,
                password
            )
            VALUES (
                :full_name,
                :email,
                :contact_number,
                :password
            )
        ";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":full_name" => $full_name,
            ":email" => $email,
            ":contact_number" => $contact_number,
            ":password" => $hashed_password
        ]);
    }


    // =========================================
    // CHECK IF EMAIL EXISTS
    // =========================================

    public function emailExists(
        string $email
    ): bool {

        $query = "
            SELECT id
            FROM customers
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ":email" => $email
        ]);

        return $stmt->fetch() !== false;
    }


    // =========================================
    // CUSTOMER LOGIN
    // =========================================

    public function login(
        string $email,
        string $password
    ): array|false {

        $query = "
            SELECT *
            FROM customers
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ":email" => $email
        ]);

        $customer = $stmt->fetch();

        if (
            $customer &&
            password_verify(
                $password,
                $customer["password"]
            )
        ) {
            return $customer;
        }

        return false;
    }
}