<?php

class Review
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* =========================================
       GET ALL REVIEWS
       ========================================= */

    public function getAll(): array
    {
        $query = $this->db->prepare("
            SELECT
                r.*,
                c.full_name AS customer_name,
                c.email AS customer_email
            FROM reviews r
            INNER JOIN customers c
                ON r.customer_id = c.id
            ORDER BY r.created_at DESC
        ");

        $query->execute();

        return $query->fetchAll();
    }


    /* =========================================
       GET APPROVED REVIEWS
       ========================================= */

    public function getApproved(): array
    {
        $query = $this->db->prepare("
            SELECT
                r.*,
                c.full_name AS customer_name,
                c.email AS customer_email
            FROM reviews r
            INNER JOIN customers c
                ON r.customer_id = c.id
            WHERE r.status = 'Approved'
            ORDER BY r.created_at DESC
        ");

        $query->execute();

        return $query->fetchAll();
    }


    /* =========================================
       GET FEATURED REVIEW
       ========================================= */

    public function getFeatured(): ?array
    {
        $query = $this->db->prepare("
            SELECT
                r.*,
                c.full_name AS customer_name,
                c.email AS customer_email
            FROM reviews r
            INNER JOIN customers c
                ON r.customer_id = c.id
            WHERE r.status = 'Approved'
            AND r.is_featured = 1
            LIMIT 1
        ");

        $query->execute();

        $review = $query->fetch();

        return $review ?: null;
    }


    /* =========================================
       CREATE REVIEW
       ========================================= */

    public function create(
        int $customer_id,
        int $rating,
        string $comment
    ): bool {

        $query = $this->db->prepare("
            INSERT INTO reviews (
                customer_id,
                rating,
                comment,
                status,
                is_featured
            )
            VALUES (
                :customer_id,
                :rating,
                :comment,
                'Pending',
                0
            )
        ");

        return $query->execute([
            ":customer_id" => $customer_id,
            ":rating" => $rating,
            ":comment" => $comment
        ]);
    }


    /* =========================================
       UPDATE REVIEW STATUS
       ========================================= */

    public function updateStatus(
        int $review_id,
        string $status
    ): bool {

        /*
         * If the review is no longer approved,
         * it cannot remain featured.
         */

        if ($status !== "Approved") {

            $clearFeatured = $this->db->prepare("
                UPDATE reviews
                SET is_featured = 0
                WHERE id = :id
            ");

            $clearFeatured->execute([
                ":id" => $review_id
            ]);
        }


        $query = $this->db->prepare("
            UPDATE reviews
            SET status = :status
            WHERE id = :id
        ");

        return $query->execute([
            ":status" => $status,
            ":id" => $review_id
        ]);
    }


    /* =========================================
       SET REVIEW AS FEATURED
       ========================================= */

    public function setFeatured(int $review_id): bool
    {
        try {

            $this->db->beginTransaction();


            /*
             * Remove featured status
             * from every review.
             */

            $clearAll = $this->db->prepare("
                UPDATE reviews
                SET is_featured = 0
            ");

            $clearAll->execute();


            /*
             * Set selected review as featured,
             * but only if it is approved.
             */

            $setFeatured = $this->db->prepare("
                UPDATE reviews
                SET is_featured = 1
                WHERE id = :id
                AND status = 'Approved'
            ");

            $setFeatured->execute([
                ":id" => $review_id
            ]);


            /*
             * Make sure the selected review
             * actually exists and is approved.
             */

            if ($setFeatured->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }


            $this->db->commit();

            return true;

        } catch (PDOException $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }


    /* =========================================
       DELETE REVIEW
       ========================================= */

    public function delete(int $review_id): bool
    {
        $query = $this->db->prepare("
            DELETE FROM reviews
            WHERE id = :id
        ");

        return $query->execute([
            ":id" => $review_id
        ]);
    }
}