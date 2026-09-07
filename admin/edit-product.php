<?php

session_start();

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

require_once "../config/Database.php";
require_once "../classes/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    header("Location: products.php?message=error");
    exit;
}

$currentProduct = $product->getById($id);

if (!$currentProduct) {
    header("Location: products.php?message=error");
    exit;
}


/* =========================
   UPDATE PRODUCT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $stock = trim($_POST["stock"] ?? "");
    $status = trim($_POST["status"] ?? "Active");

    if (
        $name === "" ||
        $price === "" ||
        $stock === ""
    ) {
        header("Location: edit-product.php?id=$id&message=empty");
        exit;
    }


    /* Keep current image */

    $image = $currentProduct["image"];


    /* =========================
       NEW IMAGE UPLOAD
    ========================= */

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK
    ) {

        $originalName = $_FILES["image"]["name"];
        $tmpName = $_FILES["image"]["tmp_name"];

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $allowedExtensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (!in_array($extension, $allowedExtensions)) {

            header(
                "Location: edit-product.php?id=$id&message=invalid_image"
            );

            exit;
        }


        $newImageName =
            uniqid("product_", true) . "." . $extension;


        $uploadPath =
            "../assets/images/" . $newImageName;


        if (!move_uploaded_file($tmpName, $uploadPath)) {

            header(
                "Location: edit-product.php?id=$id&message=upload_error"
            );

            exit;
        }


        $image = $newImageName;


        /* Delete old image */

        if (
            !empty($currentProduct["image"]) &&
            file_exists(
                "../assets/images/" .
                $currentProduct["image"]
            )
        ) {

            unlink(
                "../assets/images/" .
                $currentProduct["image"]
            );
        }
    }


    /* =========================
       PRODUCT DATA
    ========================= */

    $product->id = $id;

    $product->product_name = $name;

    $product->description = $description;

    $product->price = (float) $price;

    $product->stock = (int) $stock;

    $product->image = $image;

    $product->status = $status;


    /* =========================
       UPDATE DATABASE
    ========================= */

    if ($product->update()) {

        header(
            "Location: products.php?message=updated"
        );

        exit;

    } else {

        header(
            "Location: edit-product.php?id=$id&message=error"
        );

        exit;
    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Product | NAVA Fade Studio</title>


        <style>

        /* =========================================
        BRAND
        ========================================= */

        :root {
            --navy: #0e1423;
            --gold: #b8862c;
            --light-gold: #d4a33a;

            --white: #ffffff;
            --black: #111111;

            --light-gray: #f5f5f5;
            --gray: #555555;
            --dark-gray: #aaa;
        }


        /* =========================================
        RESET
        ========================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* =========================================
        BODY
        ========================================= */

        body {
            min-height: 100vh;

            font-family: "Myriad Pro", Arial, sans-serif;

            background:
                url("../assets/images/pattern1.png");

            background-size: cover;
            background-position: center;

            color: var(--white);
        }


        /* =========================================
        HEADER
        ========================================= */

        .header {
            height: 80px;

            padding: 0 50px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            background: var(--navy);

            border-bottom: 2px solid var(--gold);

            position: sticky;

            top: 0;

            z-index: 1000;
        }


        .header h1 {
            font-family: "Bahnschrift", Arial, sans-serif;

            color: var(--gold);

            font-size: 22px;

            letter-spacing: 1px;
        }


        /* =========================================
        BACK BUTTON
        ========================================= */

        .back-btn {
            padding: 10px 20px;

            color: var(--gold);

            border: 2px solid var(--gold);

            border-radius: 8px;

            text-decoration: none;

            font-family: "Bahnschrift", Arial, sans-serif;

            font-weight: bold;

            transition: 0.3s ease;
        }


        .back-btn:hover {
            background: var(--gold);

            color: var(--navy);
        }


        /* =========================================
        MAIN CONTAINER
        ========================================= */

        .container {
            width: 100%;

            max-width: 1000px;

            margin: 50px auto;

            padding: 0 30px;
        }


        /* =========================================
        EDIT PRODUCT SECTION
        SAME STYLE AS ADD PRODUCT
        ========================================= */

        .form-card {
            width: 100%;

            padding: 35px;

            background: var(--navy);

            border: 1px solid var(--gray);

            border-radius: 15px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.25);
        }


        /* =========================================
        FORM HEADER
        ========================================= */

        .form-card h2 {
            font-family: "Bahnschrift";

            font-size: 28px;

            margin-bottom: 12px;

            color: var(--light-gold);
        }


        .form-card::before {

            display: block;

            margin-bottom: 30px;

            color: var(--dark-gray);

            font-family: "Myriad Pro";

            font-size: 14px;
        }


        /* =========================================
        MESSAGE
        ========================================= */

        .message {
            padding: 15px 20px;

            margin-bottom: 25px;

            background: #6fcf97;

            color: var(--navy);

            border-radius: 8px;

            font-family: "Myriad Pro", Arial, sans-serif;

            font-weight: bold;
        }


        /* =========================================
        FORM
        ========================================= */

        form {
            max-width: 850px;
        }


        .form-group {
            display: flex;

            flex-direction: column;

            margin-bottom: 20px;
        }


        /* =========================================
        LABEL
        ========================================= */

        .form-group label {
            margin-bottom: 8px;

            color: var(--white);

            font-family: "Bahnschrift", Arial, sans-serif;

            font-weight: bold;
        }


        /* =========================================
        INPUT / TEXTAREA / SELECT
        ========================================= */

        .form-group input,
        .form-group textarea,
        .form-group select {

            width: 100%;

            padding: 13px 15px;

            background: #151c2d;

            color: var(--white);

            border: 1px solid var(--gray);

            border-radius: 8px;

            font-family: "Myriad Pro", Arial, sans-serif;

            font-size: 14px;

            outline: none;

            transition: 0.3s ease;
        }


        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {

            border-color: var(--gold);

            box-shadow:
                0 0 0 2px rgba(184, 134, 44, 0.15);
        }


        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #777;
        }


        /* =========================================
        TEXTAREA
        ========================================= */

        .form-group textarea {
            resize: vertical;

            min-height: 120px;
        }


        /* =========================================
        PRICE + STOCK
        ========================================= */

        .form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;
        }


        /* =========================================
        CURRENT IMAGE
        ========================================= */

        .current-image {
            margin-bottom: 15px;
        }


        .current-image img {

            width: 130px;

            height: 130px;

            object-fit: contain;

            background: var(--white);

            border-radius: 8px;

            border: 2px solid var(--gold);

            padding: 5px;

            display: block;
        }


        .current-image p {

            margin-top: 7px;

            color: var(--dark-gray);

            font-family: "Myriad Pro", Arial, sans-serif;

            font-size: 13px;
        }


        /* =========================================
        FILE INPUT
        ========================================= */

        .form-group input[type="file"] {

            padding: 10px;

            cursor: pointer;
        }


        .form-group small {

            margin-top: 7px;

            color: #888;

            font-family: "Myriad Pro", Arial, sans-serif;
        }


        /* =========================================
        ACTION BUTTONS
        ========================================= */

        .form-actions {

            display: flex;

            gap: 10px;

            margin-top: 10px;
        }


        /* =========================================
        SAVE CHANGES
        ========================================= */

        .save-btn {

            padding: 13px 25px;

            background: var(--gold);

            color: var(--navy);

            border: none;

            border-radius: 8px;

            font-family: "Bahnschrift", Arial, sans-serif;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s ease;
        }


        .save-btn:hover {

            background: var(--light-gold);

            transform: translateY(-2px);
        }


        /* =========================================
        CANCEL
        ========================================= */

        .cancel-btn {

            padding: 13px 25px;

            background: transparent;

            color: var(--gold);

            border: 2px solid var(--gold);

            border-radius: 8px;

            text-decoration: none;

            font-family: "Bahnschrift", Arial, sans-serif;

            font-weight: bold;

            transition: 0.3s ease;
        }


        .cancel-btn:hover {

            background: var(--gold);

            color: var(--navy);
        }


        /* =========================================
        RESPONSIVE
        ========================================= */

        @media (max-width: 800px) {

            .header {
                padding: 0 20px;
            }


            .container {
                padding: 0 20px;

                margin: 30px auto;
            }


            .form-card {
                padding: 25px;
            }


            .form-row {
                grid-template-columns: 1fr;
            }


            .form-actions {
                flex-direction: column;
            }


            .save-btn,
            .cancel-btn {
                width: 100%;

                text-align: center;
            }
        }


        @media (max-width: 500px) {

            .header {
                height: auto;

                padding: 20px;

                flex-direction: column;

                gap: 15px;

                align-items: flex-start;
            }


            .form-card {
                padding: 20px;
            }
        }

    </style>

</head>


<body>


    <!-- =========================
         HEADER
    ========================== -->

    <header class="header">

        <h1>
            Edit Product
        </h1>

        <a
            href="products.php"
            class="back-btn"
        >
            ← Back to Products
        </a>

    </header>


    <!-- =========================
         MAIN
    ========================== -->

    <div class="container">

        <div class="form-card">

            <h2>
                Edit Product Information
            </h2>


            <!-- =========================
                 MESSAGES
            ========================== -->

            <?php if (isset($_GET["message"])): ?>

                <?php if ($_GET["message"] === "empty"): ?>

                    <div class="message">
                        Please fill in all required fields.
                    </div>


                <?php elseif ($_GET["message"] === "invalid_image"): ?>

                    <div class="message">
                        Invalid image format.
                        Please use JPG, JPEG, PNG, or WEBP.
                    </div>


                <?php elseif ($_GET["message"] === "upload_error"): ?>

                    <div class="message">
                        Image upload failed.
                        Please try again.
                    </div>


                <?php elseif ($_GET["message"] === "error"): ?>

                    <div class="message">
                        Something went wrong.
                        Please try again.
                    </div>

                <?php endif; ?>

            <?php endif; ?>


            <!-- =========================
                 FORM
            ========================== -->

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- PRODUCT NAME -->

                <div class="form-group">

                    <label for="name">
                        Product Name *
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($currentProduct["product_name"]) ?>"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                    ><?= htmlspecialchars($currentProduct["description"]) ?></textarea>

                </div>


                <!-- PRICE -->

                <div class="form-group">

                    <label for="price">
                        Price *
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        value="<?= htmlspecialchars($currentProduct["price"]) ?>"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>


                <!-- STOCK -->

                <div class="form-group">

                    <label for="stock">
                        Stock *
                    </label>

                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        value="<?= htmlspecialchars($currentProduct["stock"]) ?>"
                        min="0"
                        required
                    >

                </div>


                <!-- CURRENT IMAGE -->

                <div class="form-group">

                    <label>
                        Current Image
                    </label>


                    <?php if (!empty($currentProduct["image"])): ?>

                        <div class="current-image">

                            <img
                                src="../assets/images/<?= htmlspecialchars($currentProduct["image"]) ?>"
                                alt="Current Product Image"
                            >

                            <p>
                                Choose a new image below only if you want to replace it.
                            </p>

                        </div>

                    <?php else: ?>

                        <p class="current-image">
                            No image currently uploaded.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- NEW IMAGE -->

                <div class="form-group">

                    <label for="image">
                        New Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/png, image/jpeg, image/webp"
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label for="status">
                        Status *
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >

                        <option
                            value="Active"
                            <?= $currentProduct["status"] === "Active" ? "selected" : "" ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?= $currentProduct["status"] === "Inactive" ? "selected" : "" ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- ACTIONS -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="save-btn"
                    >
                        Save Changes
                    </button>

                    <a
                        href="products.php"
                        class="cancel-btn"
                    >
                        Cancel
                    </a>

                </div>


            </form>

        </div>

    </div>


</body>

</html>