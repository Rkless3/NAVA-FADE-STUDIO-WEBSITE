<?php

class Product
{
    private PDO $conn;

    public int $id;
    public string $product_name;
    public string $description;
    public float $price;
    public int $stock;
    public string $image;
    public string $status;


    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }


    // =========================================
    // GET ALL PRODUCTS
    // =========================================

    public function getAll(): array
    {
        $query = "
            SELECT *
            FROM products
            ORDER BY id DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }


    // =========================================
    // GET ACTIVE PRODUCTS
    // =========================================

    public function getActive(): array
    {
        $query = "
            SELECT *
            FROM products
            WHERE status = 'Active'
            ORDER BY id DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }


    // =========================================
    // GET ONE PRODUCT
    // =========================================

    public function getById(int $id): ?array
    {
        $query = "
            SELECT *
            FROM products
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(
            ":id",
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $product = $stmt->fetch();

        return $product ?: null;
    }


    // =========================================
    // CREATE PRODUCT
    // =========================================

    public function create(): bool
    {
        $query = "
            INSERT INTO products
            (
                product_name,
                description,
                price,
                stock,
                image,
                status
            )
            VALUES
            (
                :product_name,
                :description,
                :price,
                :stock,
                :image,
                :status
            )
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(
            ":product_name",
            $this->product_name
        );

        $stmt->bindParam(
            ":description",
            $this->description
        );

        $stmt->bindParam(
            ":price",
            $this->price
        );

        $stmt->bindParam(
            ":stock",
            $this->stock,
            PDO::PARAM_INT
        );

        $stmt->bindParam(
            ":image",
            $this->image
        );

        $stmt->bindParam(
            ":status",
            $this->status
        );

        return $stmt->execute();
    }


    // =========================================
    // UPDATE PRODUCT
    // =========================================

    public function update(): bool
    {
        $query = "
            UPDATE products
            SET
                product_name = :product_name,
                description = :description,
                price = :price,
                stock = :stock,
                image = :image,
                status = :status
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(
            ":product_name",
            $this->product_name
        );

        $stmt->bindParam(
            ":description",
            $this->description
        );

        $stmt->bindParam(
            ":price",
            $this->price
        );

        $stmt->bindParam(
            ":stock",
            $this->stock,
            PDO::PARAM_INT
        );

        $stmt->bindParam(
            ":image",
            $this->image
        );

        $stmt->bindParam(
            ":status",
            $this->status
        );

        $stmt->bindParam(
            ":id",
            $this->id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }


    // =========================================
    // DELETE PRODUCT
    // =========================================

    public function delete(int $id): bool
    {
        $query = "
            DELETE FROM products
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(
            ":id",
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}