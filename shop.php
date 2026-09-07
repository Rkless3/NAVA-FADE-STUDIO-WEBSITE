<?php

session_start();


/* =========================================
   REQUIRE FILES
========================================= */

require_once "config/Database.php";
require_once "classes/Product.php";


/* =========================================
   DATABASE CONNECTION
========================================= */

$database = new Database();
$db = $database->connect();

$productModel = new Product($db);

$customer_logged_in = isset($_SESSION["customer_id"]);

$customer_name = $_SESSION["customer_name"] ?? "";

$customer_email = $_SESSION["customer_email"] ?? "";

$customer_id = $_SESSION["customer_id"];

$success = "";

$error = "";


/* =========================================
   UPDATE PROFILE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name =
        trim($_POST["full_name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $contact_number =
        trim($_POST["contact_number"] ?? "");


    if (
        empty($full_name) ||
        empty($email) ||
        empty($contact_number)
    ) {

        $error =
            "Please fill in all fields.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        try {


            /* -------------------------
               CHECK EMAIL
            ------------------------- */

            $email_check =
                $db->prepare("
                    SELECT id
                    FROM customers
                    WHERE email = :email
                    AND id != :id
                    LIMIT 1
                ");


            $email_check->execute([

                ":email" =>
                    $email,

                ":id" =>
                    $customer_id

            ]);


            if ($email_check->fetch()) {

                $error =
                    "This email address is already being used.";

            } else {


                /* -------------------------
                   UPDATE
                ------------------------- */

                $update_query =
                    $db->prepare("
                        UPDATE customers
                        SET
                            full_name = :full_name,
                            email = :email,
                            contact_number = :contact_number
                        WHERE id = :id
                    ");


                $update_query->execute([

                    ":full_name" =>
                        $full_name,

                    ":email" =>
                        $email,

                    ":contact_number" =>
                        $contact_number,

                    ":id" =>
                        $customer_id

                ]);


                /* -------------------------
                   UPDATE SESSION
                ------------------------- */

                $_SESSION["customer_name"] =
                    $full_name;

                $_SESSION["customer_email"] =
                    $email;


                $success =
                    "Your profile has been updated successfully!";

            }

        } catch (PDOException $e) {

            $error =
                "Unable to update your profile. Please try again.";

        }

    }

}


/* =========================================
   GET CUSTOMER
========================================= */

$customer_query =
    $db->prepare("
        SELECT
            full_name,
            email,
            contact_number,
            created_at
        FROM customers
        WHERE id = :id
        LIMIT 1
    ");


$customer_query->execute([

    ":id" =>
        $customer_id

]);


$customer =
    $customer_query->fetch(
        PDO::FETCH_ASSOC
    );


/* =========================================
   SAFETY CHECK
========================================= */

if (!$customer) {

    session_destroy();

    header("Location: login.php");

    exit();

}


/* =========================================
   INITIAL
========================================= */

$name_parts =
    preg_split(
        '/\s+/',
        trim($customer["full_name"])
    );


$initials = "";


foreach (
    array_slice($name_parts, 0, 2)
    as $part
) {

    $initials .=
        strtoupper(
            substr($part, 0, 1)
        );

}


/* =========================================
   ADD TO CART
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {


    /* =====================================
       LOGIN REQUIRED
    ===================================== */

    if (!isset($_SESSION["customer_id"])) {

        header("Location: login.php");
        exit;
    }


    /* =====================================
       PRODUCT ID
    ===================================== */

    $product_id = (int) $_POST["product_id"];


    /* =====================================
       GET PRODUCT
    ===================================== */

    $product = $productModel->getById($product_id);


    /* =====================================
       CHECK PRODUCT AVAILABILITY
    ===================================== */

    if (
        $product &&
        $product["status"] === "Active" &&
        $product["stock"] > 0
    ) {


        /* =================================
           CREATE CART IF NEEDED
        ================================= */

        if (!isset($_SESSION["cart"])) {

            $_SESSION["cart"] = [];
        }


        /* =================================
           PRODUCT ALREADY IN CART
        ================================= */

        if (isset($_SESSION["cart"][$product_id])) {


            if (
                $_SESSION["cart"][$product_id]
                < $product["stock"]
            ) {

                $_SESSION["cart"][$product_id]++;
            }


        } else {


            /* ==============================
               FIRST TIME ADDING
            ============================== */

            $_SESSION["cart"][$product_id] = 1;
        }


        /* =================================
           GO TO CART
        ================================= */

        header("Location: cart.php");
        exit;
    }


    /* =====================================
       PRODUCT UNAVAILABLE
    ===================================== */

    header("Location: shop.php");
    exit;
}


/* =========================================
   GET ACTIVE PRODUCTS
========================================= */

$products = $productModel->getActive();

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <title>
        Shop | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>


        /* =====================================================
           GLOBAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            margin: 0;
            background: #080d1b;
            color: #ffffff;
            font-family:
                Bahnschrift,
                "Segoe UI",
                Arial,
                sans-serif;
        }


        /* =====================================================
           SHOP HERO
        ===================================================== */

        .shop-hero {

            height: 650px;


            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 120px 20px 80px;

            background:
                linear-gradient(
                    90deg,
                    rgba(7, 12, 25, 0.97) 0%,
                    rgba(7, 12, 25, 0.91) 48%,
                    rgba(7, 12, 25, 0.80) 100%
                ),
                url("assets/images/pattern3.png")
                center center / cover fixed;

            border-bottom:
                1px solid rgba(184, 134, 44, 0.35);
        }


        /* GOLD LIGHT EFFECT */

        .shop-hero::before {

            content: "";

            position: absolute;

            width: 700px;

            height: 450px;

            right: -220px;

            top: -200px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(212, 163, 58, 0.20) 0%,
                    rgba(212, 163, 58, 0.08) 35%,
                    transparent 70%
                );

            pointer-events: none;
        }


        .shop-hero::after {

            content: "";

            position: absolute;

            width: 500px;

            height: 450px;

            left: -250px;

            bottom: -300px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(184, 134, 44, 0.13),
                    transparent 70%
                );

            pointer-events: none;
        }


        .shop-hero-overlay {

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    135deg,
                    transparent 0%,
                    rgba(184, 134, 44, 0.03) 50%,
                    transparent 100%
                );

            pointer-events: none;
        }


        /* =====================================================
           HERO CONTAINER
        ===================================================== */

        .shop-hero-content {

            position: relative;

            z-index: 2;

            width: min(1180px, 90%);

            margin: 0 auto;

            display: grid;

            grid-template-columns:
                minmax(0, 1.2fr)
                minmax(320px, 0.8fr);

            align-items: center;

            gap: 70px;

            padding: 85px 0;
        }


        /* =====================================================
           HERO LEFT
        ===================================================== */

        .shop-hero-text {

            max-width: 680px;
        }


        .shop-hero-label span {

            display: inline-flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 22px;

            padding: 8px 14px;

            border:
                1px solid rgba(212, 163, 58, 0.45);

            border-radius: 50px;

            background:
                rgba(184, 134, 44, 0.08);

            color: #d4a33a;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 3px;

            text-transform: uppercase;
        }


        .shop-hero-label::before {

            content: "";

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #d4a33a;

            box-shadow:
                0 0 12px rgba(212, 163, 58, 0.7);
        }


        .shop-hero-text h1 {

            margin: 0;

            font-size:
                clamp(55px, 7vw, 92px);

            line-height: 0.95;

            font-weight: 900;

            letter-spacing: -3px;

            color: #ffffff;
        }


        .shop-hero-text h1 span {

            display: block;

            color: #d4a33a;
        }


        .shop-hero-text p {

            max-width: 600px;

            margin: 28px 0 0;

            color: #c5cad5;

            font-size: 18px;

            line-height: 1.65;
        }


        /* =====================================================
           HERO BUTTON
        ===================================================== */

        .shop-hero-actions {

            display: flex;

            align-items: center;

            gap: 14px;

            margin-top: 34px;

            flex-wrap: wrap;
        }


        .shop-hero-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 50px;

            padding: 14px 28px;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #b8862c,
                    #d4a33a
                );

            color: #0b1020;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .shop-hero-btn:hover {

            transform: translateY(-3px);

            box-shadow:
                0 12px 28px
                rgba(184, 134, 44, 0.28);
        }


        .shop-hero-secondary {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 50px;

            padding: 13px 25px;

            border:
                1px solid rgba(255, 255, 255, 0.20);

            border-radius: 8px;

            color: #ffffff;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            background:
                rgba(255, 255, 255, 0.03);

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                transform 0.2s ease;
        }


        .shop-hero-secondary:hover {

            transform: translateY(-3px);

            border-color: #b8862c;

            background:
                rgba(184, 134, 44, 0.08);

            color: #d4a33a;
        }


        /* =====================================================
           HERO RIGHT VISUAL
        ===================================================== */

        .shop-hero-visual {

            position: relative;

            min-height: 360px;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .hero-product-card {

            position: relative;

            width: min(350px, 100%);

            padding: 30px;

            background:
                linear-gradient(
                    145deg,
                    rgba(22, 30, 48, 0.96),
                    rgba(10, 15, 29, 0.98)
                );

            border:
                1px solid rgba(212, 163, 58, 0.40);

            border-radius: 22px;

            box-shadow:
                0 30px 70px
                rgba(0, 0, 0, 0.40);
        }


        .hero-product-card::before {

            content: "";

            position: absolute;

            inset: 10px;

            border:
                1px solid rgba(255, 255, 255, 0.05);

            border-radius: 16px;

            pointer-events: none;
        }


        .hero-product-top {

            position: relative;

            z-index: 1;

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 24px;
        }


        .hero-product-top span {

            color: #d4a33a;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 2px;

            text-transform: uppercase;
        }


        .hero-product-top strong {

            padding: 5px 9px;

            border-radius: 5px;

            background:
                rgba(184, 134, 44, 0.12);

            color: #d4a33a;

            font-size: 10px;

            letter-spacing: 1px;
        }


        .hero-product-icon {

            position: relative;

            z-index: 1;

            width: 150px;

            height: 150px;

            margin: 15px auto 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            border:
                1px solid rgba(212, 163, 58, 0.40);

            background:
                radial-gradient(
                    circle,
                    rgba(184, 134, 44, 0.17),
                    rgba(184, 134, 44, 0.03) 65%,
                    transparent 70%
                );
        }


        .hero-product-icon::before {

            content: "";

            width: 80px;

            height: 80px;

            border-radius: 50%;

            border:
                2px solid rgba(212, 163, 58, 0.45);
        }


        .hero-product-card h3 {

            position: relative;

            z-index: 1;

            margin: 0;

            text-align: center;

            color: #ffffff;

            font-size: 24px;

            font-weight: 800;
        }


        .hero-product-card p {

            position: relative;

            z-index: 1;

            margin: 10px 0 0;

            text-align: center;

            color: #9fa7b7;

            font-size: 13px;

            line-height: 1.5;
        }


        .hero-product-line {

            position: relative;

            z-index: 1;

            width: 55px;

            height: 3px;

            margin: 18px auto 0;

            border-radius: 5px;

            background: #d4a33a;
        }


        /* =====================================================
           SHOP PRODUCTS SECTION
        ===================================================== */

        .shop-page {

            position: relative;

            padding: 95px 7% 110px;

            background: url("assets/images/pattern4.png")
                center center / cover;

            color: #101522;
        }


        /* =====================================================
           PRODUCTS HEADER
        ===================================================== */

        .shop-page-header {

            max-width: 900px;

            margin: 0 auto 55px;

            text-align: center;

        }


        .shop-page-header span {

            display: block;

            margin-bottom: 10px;

            color: #b8862c;

            font-size: 12px;

            font-weight: 800;

            letter-spacing: 4px;

            text-transform: uppercase;
        }


        .shop-page-header h2 {

            margin: 0;

            color: #101522;

            font-size:
                clamp(38px, 5vw, 58px);

            line-height: 1;

            font-weight: 900;

            letter-spacing: -1px;
        }


        .shop-page-header p {

            max-width: 650px;

            margin: 18px auto 0;

            color: #606978;

            font-size: 16px;

            line-height: 1.6;
        }


        /* =====================================================
           PRODUCT GRID
        ===================================================== */

        .shop-grid {

            width: min(1180px, 100%);

            margin: 0 auto;

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 25px;
        }


        /* =====================================================
           PRODUCT CARD
        ===================================================== */

        .shop-product-card1 {

            position: relative;

            display: flex;

            flex-direction: column;

            min-width: 0;

            overflow: hidden;

            border: 5px solid
                rgba(184, 134, 44, 0.4);

            border:
                1px solid #;

            border-radius: 18px;

            box-shadow:
                0 12px 30px
                rgba(15, 22, 38, 0.08);

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }


        .shop-product-card1:hover {

            transform: translateY(-7px);

            border-color:
               var(--gold);

            box-shadow:
                0 20px 45px
                rgba(15, 22, 38, 0.14);
        }


        /* =====================================================
           PRODUCT IMAGE
        ===================================================== */

        .shop-product-image {

            position: relative;

            height: 280px;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #f5f6f8,
                    #e9ecf1
                );
        }


        .shop-product-image::after {

            content: "";

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    180deg,
                    transparent 60%,
                    rgba(0, 0, 0, 0.05)
                );

            pointer-events: none;
        }


        .shop-product-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 25px;

            display: block;

            transition:
                transform 0.35s ease;
        }


        .shop-product-card1:hover
        .shop-product-image img {

            transform: scale(1.06);
        }


        /* =====================================================
           STOCK BADGE
        ===================================================== */

        .shop-stock-badge {

            position: absolute;

            top: 15px;

            right: 15px;

            z-index: 2;

            padding: 7px 10px;

            border-radius: 6px;

            background:
                rgba(14, 20, 35, 0.90);

            color: #ffffff;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1px;

            text-transform: uppercase;
        }


        .shop-stock-badge.low {

            color: #f0b429;

            border:
                1px solid rgba(240, 180, 41, 0.40);
        }


        /* =====================================================
           PRODUCT INFO
        ===================================================== */

        .product-info {

            display: flex;

            flex-direction: column;

            flex: 1;

            padding: 25px;
        }


        .product-info h3 {

            margin: 0;

            color: #ffffff;

            font-size: 21px;

            font-weight: 800;

            line-height: 1.25;
        }


        /* =====================================================
           RATING
        ===================================================== */

        .product-rating {

            margin-top: 9px;

            color: #d49b22;

            font-size: 14px;

            letter-spacing: 2px;
        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .product-info > p {

            display: -webkit-box;

            -webkit-line-clamp: 3;

            -webkit-box-orient: vertical;

            overflow: hidden;

            min-height: 66px;

            margin: 13px 0 20px;

            color: #ffffff;

            font-size: 14px;

            line-height: 1.55;
        }


        /* =====================================================
           PRODUCT BOTTOM
        ===================================================== */

        .shop-product-bottom {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: auto;

            padding-top: 18px;

            border-top:
                1px solid #eceef2;
        }


        .shop-price {

            color: #b8862c;

            font-size: 22px;

            font-weight: 900;

            white-space: nowrap;
        }


        .shop-product-bottom form {

            margin: 0;
        }


        /* =====================================================
           ADD TO CART
        ===================================================== */

        .shop-add-btn {

            min-height: 43px;

            padding: 11px 17px;

            border: 2px solid #b8862c;

            border-radius: 7px;

            background: none;

            color: #ffffff;

            cursor: pointer;

            font-family: inherit;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 0.5px;

            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .shop-add-btn:hover:not(:disabled) {

            transform: translateY(-2px);

            background:
                #b8862c;

            color: #101522;

            box-shadow:
                0 8px 18px
                rgba(184, 134, 44, 0.20);
        }


        .shop-add-btn:disabled {

            background: #d6d9df;

            color: #747a85;

            cursor: not-allowed;
        }


        /* =====================================================
           EMPTY PRODUCTS
        ===================================================== */

        .shop-empty {

            grid-column: 1 / -1;

            padding: 70px 30px;

            text-align: center;

            background: #ffffff;

            border:
                1px solid #e1e4ea;

            border-radius: 18px;
        }


        .shop-empty-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            border:
                1px solid rgba(184, 134, 44, 0.35);

            background:
                rgba(184, 134, 44, 0.08);

            color: #b8862c;

            font-size: 28px;
        }


        .shop-empty h3 {

            margin: 0 0 8px;

            color: #101522;

            font-size: 24px;
        }


        .shop-empty p {

            margin: 0;

            color: #747b88;

            font-size: 14px;
        }


        /* =====================================================
           FOOTER IMPROVEMENTS
        ===================================================== */

        .footer {

            border-top:
                2px solid #b8862c;
        }


        .footer a {

            transition:
                color 0.2s ease;
        }


        .footer a:hover {

            color: #d4a33a;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1000px) {

            .shop-hero {

                min-height: auto;
            }


            .shop-hero-content {

                grid-template-columns: 1fr;

                gap: 50px;

                padding: 75px 0 85px;
            }


            .shop-hero-text {

                max-width: 750px;

                margin: 0 auto;

                text-align: center;
            }


            .shop-hero-text p {

                margin-left: auto;

                margin-right: auto;
            }


            .shop-hero-actions {

                justify-content: center;
            }


            .shop-hero-visual {

                min-height: auto;
            }


            .shop-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            .shop-hero {

                background-attachment: scroll;
            }


            .shop-hero-content {

                width: 90%;

                padding: 60px 0 70px;

                gap: 40px;
            }


            .shop-hero-label {

                font-size: 9px;

                letter-spacing: 2px;
            }


            .shop-hero-text h1 {

                font-size: 52px;

                letter-spacing: -2px;
            }


            .shop-hero-text p {

                margin-top: 22px;

                font-size: 15px;

                line-height: 1.55;
            }


            .shop-hero-actions {

                flex-direction: column;

                width: 100%;
            }


            .shop-hero-btn,
            .shop-hero-secondary {

                width: 100%;
            }


            .hero-product-card {

                width: min(340px, 100%);

                padding: 25px;
            }


            .hero-product-icon {

                width: 125px;

                height: 125px;
            }


            .shop-page {

                padding: 70px 20px 80px;
            }


            .shop-page-header {

                margin-bottom: 40px;
            }


            .shop-page-header span {

                font-size: 10px;

                letter-spacing: 3px;
            }


            .shop-page-header h2 {

                font-size: 39px;
            }


            .shop-page-header p {

                font-size: 14px;
            }


            .shop-grid {

                grid-template-columns: 1fr;

                gap: 20px;
            }


            .shop-product-image {

                height: 260px;
            }


            .product-info {

                padding: 21px;
            }


            .product-info h3 {

                font-size: 19px;
            }


            .shop-product-bottom {

                align-items: center;
            }


            .shop-price {

                font-size: 20px;
            }


            .shop-add-btn {

                padding-left: 14px;

                padding-right: 14px;
            }
        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 400px) {

            .shop-hero-text h1 {

                font-size: 44px;
            }


            .hero-product-card h3 {

                font-size: 21px;
            }


            .shop-product-bottom {

                flex-direction: column;

                align-items: stretch;
            }


            .shop-price {

                text-align: center;
            }


            .shop-product-bottom form {

                width: 100%;
            }


            .shop-add-btn {

                width: 100%;
            }
        }


    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="container navbar">


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo"
        >

            <img
                src="assets/images/logo.png"
                alt="NAVA Fade Studio Logo"
            >

        </a>


        <!-- NAVIGATION -->

        <nav>


            <a href="index.php">
                Home
            </a>


            <a href="about.php">
                About Us
            </a>


            <a href="index.php#services">
                Service
            </a>


            <a href="reviews.php">
                Reviews
            </a>


            <a
                href="shop.php"
                class="active"
            >
                Shop
            </a>


            <a href="blog.php">
                Blog
            </a>


            <a
                href="book.php"
                class="nav-button"
            >
                Book Now
            </a>


            <?php if (isset($_SESSION["customer_id"])): ?>


                
            <!-- CUSTOMER MENU -->

            <div class="customer-menu">

                <button
                    class="customer-menu-btn"
                    type="button"
                    onclick="toggleCustomerMenu()"
                >

                    <span class="profile-avatar">

                            <?= htmlspecialchars(
                                $initials
                            ) ?>

                        </span>


                    <span class="dropdown-arrow">
                        ▼
                    </span>

                </button>


                <div
                    class="customer-dropdown"
                    id="customerDropdown"
                >


                    <a
                        href="profile.php"
                        class="customer-profile-card"
                    >

                        <span class="profile-avatar">

                            <?= htmlspecialchars(
                                $initials
                            ) ?>

                        </span>


                        <span class="profile-details">

                            <strong>
                                <?= htmlspecialchars(
                                    $customer["full_name"]
                                ) ?>
                            </strong>


                            <small>
                                <?= htmlspecialchars(
                                    $customer["email"]
                                ) ?>
                            </small>

                        </span>

                    </a>


                    <div class="dropdown-divider"></div>


                    <a
                        href="my-orders.php"
                        class="customer-dropdown-link"
                    >
                        <span class="dropdown-icon">
                            🛍️
                        </span>

                        My Orders
                    </a>


                    <a
                        href="appointments.php"
                        class="customer-dropdown-link"
                    >
                        <span class="dropdown-icon">
                            📅
                        </span>

                        My Appointments
                    </a>


                    <a
                        href="review.php"
                        class="customer-dropdown-link"
                    >
                        <span class="dropdown-icon">
                            ⭐
                        </span>

                        Write a Review
                    </a>


                    <div class="dropdown-divider"></div>


                    <a
                        href="logout.php"
                        class="customer-dropdown-link logout-link"
                    >
                        <span class="dropdown-icon">
                            🚪
                        </span>

                        Logout
                    </a>


                </div>

            </div>


            <?php else: ?>


                <a
                    href="register.php"
                    class="nav-button"
                >
                    Register
                </a>


            <?php endif; ?>


        </nav>


    </div>

</header>



<!-- =====================================================
     SHOP HERO
===================================================== -->

<section class="shop-hero">


    <div class="shop-hero-overlay"></div>


    <div class="shop-hero-content">


        <!-- =============================================
             LEFT SIDE
        ============================================== -->

        <div class="shop-hero-text">

            <div class="shop-hero-label">

                <span>
                    Professional Grooming Essentials
                </span>

            </div>

            <h1>

                LOOK
                <span>SHARP.</span>

            </h1>


            <p>

                Premium grooming products selected by
                NAVA Fade Studio to help you maintain
                that fresh-from-the-barbershop look
                wherever you go.

            </p>


            <div class="shop-hero-actions">


                <a
                    href="#products"
                    class="shop-hero-btn"
                >
                    SHOP PRODUCTS
                </a>


                <a
                    href="index.php#services"
                    class="shop-hero-secondary"
                >
                    VIEW SERVICES
                </a>


            </div>


        </div>



        <!-- =============================================
             RIGHT SIDE
        ============================================== -->

        <div class="shop-hero-visual">


            <div class="hero-product-card">


                <div class="hero-product-top">

                    <span>
                        NAVA Essentials
                    </span>

                    <strong>
                        PREMIUM
                    </strong>

                </div>


                <div class="hero-product-icon"></div>


                <h3>
                    Grooming That Goes With You.
                </h3>


                <p>

                    Keep your style looking clean,
                    polished, and ready for the day.

                </p>


                <div class="hero-product-line"></div>


            </div>


        </div>


    </div>


</section>



<!-- =====================================================
     PRODUCTS
===================================================== -->

<section
    class="shop-page"
    id="products"
>


    <!-- =============================================
         SECTION HEADER
    ============================================== -->

    <div class="shop-page-header">


        <span>
            Premium Grooming
        </span>


        <h2>
            OUR PRODUCTS
        </h2>


        <p>

            Take the NAVA experience home with
            quality grooming essentials made for
            everyday styling and maintenance.

        </p>


    </div>



    <!-- =============================================
         PRODUCT GRID
    ============================================== -->

    <div class="shop-grid">


        <?php if (!empty($products)): ?>


            <?php foreach ($products as $item): ?>


                <div class="shop-product-card1">


                    <!-- PRODUCT IMAGE -->

                    <div class="shop-product-image">


                        <?php if ((int) $item["stock"] <= 5): ?>


                            <span class="shop-stock-badge low">

                                <?php if ((int) $item["stock"] === 1): ?>

                                    1 LEFT

                                <?php else: ?>

                                    LOW STOCK

                                <?php endif; ?>

                            </span>


                        <?php else: ?>


                            <span class="shop-stock-badge">

                                IN STOCK

                            </span>


                        <?php endif; ?>


                        <img
                            src="assets/images/<?= htmlspecialchars($item["image"]) ?>"
                            alt="<?= htmlspecialchars($item["product_name"]) ?>"
                        >


                    </div>



                    <!-- PRODUCT INFORMATION -->

                    <div class="product-info">


                        <h3>

                            <?= htmlspecialchars(
                                $item["product_name"]
                            ) ?>

                        </h3>


                        <div class="product-rating">

                            ★★★★★

                        </div>


                        <p>

                            <?= htmlspecialchars(
                                $item["description"]
                            ) ?>

                        </p>



                        <!-- PRODUCT BOTTOM -->

                        <div class="shop-product-bottom">


                            <span class="shop-price">

                                ₱<?= number_format(
                                    $item["price"],
                                    2
                                ) ?>

                            </span>


                            <?php if ($item["stock"] > 0): ?>


                                <form
                                    method="POST"
                                    action="shop.php"
                                >


                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int) $item["id"] ?>"
                                    >


                                    <button
                                        class="shop-add-btn"
                                        type="submit"
                                        name="add_to_cart"
                                    >

                                        ADD TO CART

                                    </button>


                                </form>


                            <?php else: ?>


                                <button
                                    class="shop-add-btn"
                                    type="button"
                                    disabled
                                >

                                    OUT OF STOCK

                                </button>


                            <?php endif; ?>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="shop-empty">


                <div class="shop-empty-icon">
                    +
                </div>


                <h3>
                    No Products Available
                </h3>


                <p>

                    Our grooming products will be
                    available here soon.

                </p>


            </div>


        <?php endif; ?>


    </div>


</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">


    <div class="footer-content">


        <!-- =============================================
             BRAND
        ============================================== -->

        <div>


            <img
                src="assets/images/logo.png"
                class="footer-logo"
                alt="NAVA Fade Studio Logo"
            >


            <p>

                NAVA Fade Studio is dedicated to
                delivering clean, modern, and
                personalized grooming experiences.

            </p>


        </div>



        <!-- =============================================
             ABOUT
        ============================================== -->

        <div>


            <h3>
                About
            </h3>


            <a href="about.php">
                About Us
            </a>


            <a href="index.php#services">
                Services
            </a>


            <a href="shop.php">
                Shop
            </a>


            <a href="blog.php">
                Blog
            </a>


        </div>



        <!-- =============================================
             SUPPORT
        ============================================== -->

        <div>


            <h3>
                Support
            </h3>


            <a href="about.php">
                Who We Are
            </a>


            <a href="index.php#services">
                Our Services
            </a>


            <a href="book.php">
                Book Appointment
            </a>


            <a href="reviews.php">
                Customer Reviews
            </a>


        </div>



        <!-- =============================================
             ADDRESS
        ============================================== -->

        <div>


            <h3>
                Address
            </h3>


            <p>
                📍 Amlan, Negros Oriental
            </p>


            <p>
                📧 navafadestudio@gmail.com
            </p>


            <p>
                📞 0969 407 4629
            </p>


        </div>


    </div>


</footer>



<!-- =====================================================
     CUSTOMER DROPDOWN SCRIPT
===================================================== -->

<script>


function toggleCustomerMenu() {


    const dropdown =
        document.getElementById(
            "customerDropdown"
        );


    if (dropdown) {

        dropdown.classList.toggle("show");

    }

}



document.addEventListener(
    "click",
    function(event) {


        const menu =
            document.querySelector(
                ".customer-menu"
            );


        if (
            menu &&
            !menu.contains(event.target)
        ) {


            document
                .getElementById(
                    "customerDropdown"
                )
                ?.classList
                .remove("show");

        }

    }
);


</script>


</body>

</html>