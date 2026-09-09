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

    /*
    |--------------------------------------------------------------------------
    | GET ALL PRODUCTS
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE PRODUCTS
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | GET PRODUCT BY ID
    |--------------------------------------------------------------------------
    */
    public function getById(int $id): ?array
    {
        $query = "
            SELECT *
            FROM products
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(
            ":id",
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $product = $stmt->fetch();

        return $product ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PRODUCT
    |--------------------------------------------------------------------------
    |
    | Stock can be entered when creating a NEW product.
    |
    */
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

        $stmt->bindValue(
            ":product_name",
            $this->product_name
        );

        $stmt->bindValue(
            ":description",
            $this->description
        );

        $stmt->bindValue(
            ":price",
            $this->price
        );

        $stmt->bindValue(
            ":stock",
            $this->stock,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":image",
            $this->image
        );

        $stmt->bindValue(
            ":status",
            $this->status
        );

        return $stmt->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | STOCK IS NOT UPDATED HERE.
    |
    | Edit Product can only change:
    | - name
    | - description
    | - price
    | - image
    | - status
    |
    */
    public function update(): bool
    {
        $query = "
            UPDATE products
            SET
                product_name = :product_name,
                description = :description,
                price = :price,
                image = :image,
                status = :status
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(
            ":product_name",
            $this->product_name
        );

        $stmt->bindValue(
            ":description",
            $this->description
        );

        $stmt->bindValue(
            ":price",
            $this->price
        );

        $stmt->bindValue(
            ":image",
            $this->image
        );

        $stmt->bindValue(
            ":status",
            $this->status
        );

        $stmt->bindValue(
            ":id",
            $this->id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | ADD STOCK
    |--------------------------------------------------------------------------
    |
    | This is the ONLY method used to increase existing stock.
    |
    */
    public function addStock(
        int $id,
        int $quantity
    ): bool {

        if ($id <= 0 || $quantity <= 0) {
            return false;
        }

        $query = "
            UPDATE products
            SET stock = stock + :quantity
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(
            ":quantity",
            $quantity,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":id",
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */
    public function delete(int $id): bool
    {
        $query = "
            DELETE FROM products
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(
            ":id",
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}