<?php

class Service
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL SERVICES
    |--------------------------------------------------------------------------
    */

    public function getAll(): array
    {
        $sql = "
            SELECT *
            FROM services
            ORDER BY id ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }


    /*
    |--------------------------------------------------------------------------
    | GET SERVICE BY ID
    |--------------------------------------------------------------------------
    */

    public function getById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM services
            WHERE id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        $service = $stmt->fetch();

        return $service ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE SERVICE
    |--------------------------------------------------------------------------
    */

    public function create(
        string $name,
        string $description,
        float $price,
        string $duration,
        string $image
    ): bool {

        $sql = "
            INSERT INTO services (
                service_name,
                description,
                price,
                duration,
                image
            )
            VALUES (
                :service_name,
                :description,
                :price,
                :duration,
                :image
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":service_name" => $name,
            ":description" => $description,
            ":price" => $price,
            ":duration" => $duration,
            ":image" => $image
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SERVICE
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        string $name,
        string $description,
        float $price,
        string $duration,
        string $image
    ): bool {

        $sql = "
            UPDATE services
            SET
                service_name = :service_name,
                description = :description,
                price = :price,
                duration = :duration,
                image = :image
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":id" => $id,
            ":service_name" => $name,
            ":description" => $description,
            ":price" => $price,
            ":duration" => $duration,
            ":image" => $image
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE SERVICE
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM services
            WHERE id = ?
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$id]);
    }
}