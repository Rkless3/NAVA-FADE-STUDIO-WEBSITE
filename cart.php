<?php

session_start();


/* =========================================
   LOGIN REQUIREMENT
========================================= */

if (!isset($_SESSION["customer_id"])) {

    header("Location: login.php");
    exit;

}


/* =========================================
   DATABASE
========================================= */

require_once "config/Database.php";
require_once "classes/Product.php";


$database = new Database();
$db = $database->connect();

$productModel = new Product($db);


/* =========================================
   CART ACTIONS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = (int) ($_POST["product_id"] ?? 0);


    /* =====================================
       ADD QUANTITY
    ===================================== */

    if (isset($_POST["increase"])) {

        if (isset($_SESSION["cart"][$product_id])) {

            $product = $productModel->getById($product_id);

            if ($product) {

                if (
                    $_SESSION["cart"][$product_id]
                    < $product["stock"]
                ) {

                    $_SESSION["cart"][$product_id]++;

                }

            }

        }

    }


    /* =====================================
       DECREASE QUANTITY
    ===================================== */

    if (isset($_POST["decrease"])) {

        if (isset($_SESSION["cart"][$product_id])) {

            $_SESSION["cart"][$product_id]--;

            if (
                $_SESSION["cart"][$product_id] <= 0
            ) {

                unset($_SESSION["cart"][$product_id]);

            }

        }

    }


    /* =====================================
       REMOVE PRODUCT
    ===================================== */

    if (isset($_POST["remove"])) {

        unset(
            $_SESSION["cart"][$product_id]
        );

    }


    /* =====================================
       REFRESH CART
    ===================================== */

    header("Location: cart.php");
    exit;

}


/* =========================================
   GET CART
========================================= */

$cart = $_SESSION["cart"] ?? [];

$total = 0;
$total_items = 0;


/* =========================================
   CUSTOMER NAME
========================================= */

$customer_name =
    $_SESSION["customer_name"] ?? "Customer";


/* =========================================
   PRODUCT IMAGE HELPER
========================================= */

function getProductImage(?string $image): ?string
{
    if (empty($image)) {
        return null;
    }


    $image = trim($image);


    /*
     * If the database already contains
     * a complete URL, use it directly.
     */

    if (
        filter_var(
            $image,
            FILTER_VALIDATE_URL
        )
    ) {

        return $image;

    }


    /*
     * Remove accidental leading slash.
     */

    $cleanImage = ltrim($image, "/");


    /*
     * Possible locations where product
     * images may exist in the project.
     */

    $possiblePaths = [

        $cleanImage,

        "assets/images/" . $cleanImage,

        "assets/images/products/" . $cleanImage,

        "assets/uploads/" . $cleanImage,

        "uploads/" . $cleanImage,

        "uploads/products/" . $cleanImage

    ];


    /*
     * Check each possible path.
     */

    foreach ($possiblePaths as $path) {

        $fullPath =
            __DIR__ . "/" . $path;


        if (is_file($fullPath)) {

            return $path;

        }

    }


    /*
     * Sometimes the database stores
     * the complete path but with a leading
     * project slash. Try the basename too.
     */

    $filename = basename($cleanImage);


    if ($filename !== $cleanImage) {

        $fallbackPaths = [

            "assets/images/" . $filename,

            "assets/images/products/" . $filename,

            "assets/uploads/" . $filename,

            "uploads/" . $filename,

            "uploads/products/" . $filename

        ];


        foreach (
            $fallbackPaths
            as $path
        ) {

            $fullPath =
                __DIR__ . "/" . $path;


            if (is_file($fullPath)) {

                return $path;

            }

        }

    }


    /*
     * Image could not be found.
     */

    return null;
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


    <title>
        Your Cart | NAVA Fade Studio
    </title>


    <!-- =========================================
         FONT AWESOME
    ========================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>


        /* =========================================
           RESET
        ========================================== */

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }


        /* =========================================
           BODY
        ========================================== */

        body {

            min-height: 100vh;

            font-family:
                Bahnschrift,
                "Myriad Pro",
                Arial,
                sans-serif;

            color: #ffffff;

            background-color: #0e1423;

            background:
                linear-gradient(
                    rgba(7, 12, 25, 0.88),
                    rgba(7, 12, 25, 0.92)
                ),
                url("assets/images/pattern3.png")
                center center / cover fixed;

        }


        /* =========================================
           PAGE
        ========================================== */

        .cart-page {

            width: 100%;

            min-height: 100vh;

            padding: 55px 6% 80px;

        }


        /* =========================================
           BACK TO SHOP
        ========================================== */

        .cart-back {

            max-width: 1250px;

            margin: 0 auto 30px;

        }


        .cart-back a {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            color: #d4a33a;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;

            transition: 0.25s ease;

        }


        .cart-back a:hover {

            color: #ffffff;

            transform: translateX(-3px);

        }


        /* =========================================
           CART HEADER
        ========================================== */

        .cart-page-header {

            max-width: 1250px;

            margin: 0 auto 45px;

            text-align: center;

        }


        .cart-page-header .eyebrow {

            display: inline-block;

            margin-bottom: 12px;

            color: #d4a33a;

            font-size: 13px;

            font-weight: 800;

            letter-spacing: 4px;

        }


        .cart-page-header h1 {

            color: #ffffff;

            font-size:
                clamp(38px, 5vw, 60px);

            line-height: 1.1;

            font-weight: 900;

            margin-bottom: 12px;

        }


        .cart-page-header h1 span {

            color: #d4a33a;

        }


        .cart-page-header p {

            max-width: 650px;

            margin: 0 auto;

            color: #aeb8c8;

            font-size: 16px;

            line-height: 1.6;

        }


        /* =========================================
           MAIN CART LAYOUT
        ========================================== */

        .cart-layout {

            max-width: 1250px;

            margin: 0 auto;

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                340px;

            gap: 28px;

            align-items: start;

        }


        /* =========================================
           PRODUCTS
        ========================================== */

        .cart-products {

            display: flex;

            flex-direction: column;

            gap: 15px;

        }


        /* =========================================
           CART ITEM
        ========================================== */

        .cart-item {

            position: relative;

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                auto
                auto
                auto;

            align-items: center;

            gap: 25px;

            padding: 22px 24px;

            background:
                rgba(
                    14,
                    20,
                    35,
                    0.94
                );

            border: 1px solid #30394b;

            border-radius: 18px;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.20);

            transition:
                border-color 0.25s ease,
                transform 0.25s ease,
                box-shadow 0.25s ease;

        }


        .cart-item:hover {

            border-color:
                rgba(
                    184,
                    134,
                    44,
                    0.75
                );

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 30px
                rgba(0, 0, 0, 0.28);

        }


        /* =========================================
           PRODUCT INFORMATION
        ========================================== */

        .cart-item-info {

            min-width: 0;

            display: flex;

            align-items: center;

            gap: 18px;

        }


        /* =========================================
           PRODUCT IMAGE
        ========================================== */

        .cart-product-image {

            width: 82px;

            height: 82px;

            min-width: 82px;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #111a2b,
                    #090f1c
                );

            border:
                1px solid
                rgba(
                    184,
                    134,
                    44,
                    0.60
                );

            border-radius: 14px;

        }


        .cart-product-image img {

            width: 100%;

            height: 100%;

            display: block;

            object-fit: cover;

        }


        .cart-product-placeholder {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 100%;

            height: 100%;

            color: #d4a33a;

            font-size: 27px;

        }


        /* =========================================
           PRODUCT DETAILS
        ========================================== */

        .cart-product-details {

            min-width: 0;

        }


        .cart-product-details h3 {

            color: #ffffff;

            font-size: 19px;

            font-weight: 800;

            margin-bottom: 8px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .cart-product-details p {

            color: #9ca8ba;

            font-size: 14px;

        }


        .cart-product-details p strong {

            color: #d4a33a;

        }


        /* =========================================
           QUANTITY
        ========================================== */

        .cart-quantity {

            display: flex;

            align-items: center;

            gap: 9px;

        }


        .quantity-btn {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid
                #596274;

            border-radius: 9px;

            background: #111827;

            color: #ffffff;

            font-size: 14px;

            cursor: pointer;

            transition:
                background-color 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease;

        }


        .quantity-btn:hover {

            background: #b8862c;

            border-color: #b8862c;

            color: #0e1423;

            transform:
                translateY(-1px);

        }


        .quantity-number {

            min-width: 30px;

            text-align: center;

            color: #ffffff;

            font-size: 16px;

            font-weight: 800;

        }


        /* =========================================
           SUBTOTAL
        ========================================== */

        .cart-subtotal {

            min-width: 105px;

            text-align: right;

            color: #d4a33a;

            font-size: 18px;

            font-weight: 900;

        }


        /* =========================================
           REMOVE
        ========================================== */

        .cart-remove button {

            width: 40px;

            height: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid
                rgba(
                    220,
                    70,
                    70,
                    0.55
                );

            border-radius: 9px;

            background:
                rgba(
                    150,
                    30,
                    30,
                    0.10
                );

            color: #ff7474;

            font-size: 14px;

            cursor: pointer;

            transition:
                all 0.2s ease;

        }


        .cart-remove button:hover {

            background: #b83232;

            border-color: #b83232;

            color: #ffffff;

        }


        /* =========================================
           CART SUMMARY
        ========================================== */

        .cart-summary-box {

            position: sticky;

            top: 25px;

            padding: 28px;

            background:
                rgba(
                    14,
                    20,
                    35,
                    0.97
                );

            border:
                1px solid
                #b8862c;

            border-radius: 20px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.30);

        }


        .summary-label {

            display: block;

            margin-bottom: 8px;

            color: #b8862c;

            font-size: 12px;

            font-weight: 800;

            letter-spacing: 2px;

            text-transform: uppercase;

        }


        .cart-summary-box h2 {

            color: #ffffff;

            font-size: 25px;

            font-weight: 800;

            margin-bottom: 22px;

        }


        /* =========================================
           SUMMARY ITEMS
        ========================================== */

        .summary-items {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding-bottom: 17px;

            margin-bottom: 17px;

            border-bottom:
                1px solid
                #343c4c;

            color: #aeb8c8;

            font-size: 14px;

        }


        .summary-items strong {

            color: #ffffff;

        }


        /* =========================================
           TOTAL
        ========================================== */

        .cart-total {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

        }


        .cart-total span:first-child {

            color: #d7deea;

            font-size: 16px;

            font-weight: 700;

        }


        .cart-total span:last-child {

            color: #d4a33a;

            font-size: 28px;

            font-weight: 900;

        }


        /* =========================================
           CHECKOUT BUTTON
        ========================================== */

        .checkout-btn {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            padding: 15px 18px;

            margin-bottom: 12px;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #b8862c,
                    #d4a33a
                );

            color: #0e1423;

            text-decoration: none;

            font-size: 15px;

            font-weight: 900;

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;

        }


        .checkout-btn:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 9px 22px
                rgba(
                    184,
                    134,
                    44,
                    0.28
                );

        }


        /* =========================================
           CONTINUE SHOPPING
        ========================================== */

        .continue-shopping {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 13px 18px;

            border:
                1px solid
                #465063;

            border-radius: 11px;

            background: transparent;

            color: #d7deea;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            transition:
                all 0.2s ease;

        }


        .continue-shopping:hover {

            border-color: #b8862c;

            color: #d4a33a;

            background:
                rgba(
                    184,
                    134,
                    44,
                    0.06
                );

        }


        /* =========================================
           EMPTY CART
        ========================================== */

        .cart-empty {

            max-width: 650px;

            margin: 35px auto 0;

            padding: 55px 30px;

            text-align: center;

            background:
                rgba(
                    14,
                    20,
                    35,
                    0.96
                );

            border:
                1px solid
                #b8862c;

            border-radius: 22px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.30);

        }


        .empty-cart-icon {

            width: 75px;

            height: 75px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                rgba(
                    184,
                    134,
                    44,
                    0.12
                );

            border:
                1px solid
                rgba(
                    184,
                    134,
                    44,
                    0.45
                );

            color: #d4a33a;

            font-size: 29px;

        }


        .cart-empty h2 {

            color: #ffffff;

            font-size: 27px;

            margin-bottom: 10px;

        }


        .cart-empty p {

            color: #98a4b7;

            font-size: 15px;

            line-height: 1.6;

            margin-bottom: 25px;

        }


        .empty-shop-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 13px 24px;

            border-radius: 10px;

            background: #b8862c;

            color: #0e1423;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;

            transition: 0.2s ease;

        }


        .empty-shop-btn:hover {

            background: #d4a33a;

            transform:
                translateY(-2px);

        }


        /* =========================================
           TABLET
        ========================================== */

        @media (max-width: 1000px) {

            .cart-layout {

                grid-template-columns: 1fr;

            }


            .cart-summary-box {

                position: static;

            }

        }


        /* =========================================
           MOBILE
        ========================================== */

        @media (max-width: 700px) {

            .cart-page {

                padding:
                    35px 15px
                    60px;

            }


            .cart-page-header {

                margin-bottom: 30px;

            }


            .cart-page-header .eyebrow {

                font-size: 11px;

                letter-spacing: 3px;

            }


            .cart-page-header h1 {

                font-size: 38px;

            }


            .cart-page-header p {

                font-size: 14px;

            }


            .cart-item {

                grid-template-columns: 1fr;

                gap: 17px;

                padding: 19px;

                padding-right: 65px;

            }


            .cart-item-info {

                width: 100%;

            }


            .cart-product-image {

                width: 70px;

                height: 70px;

                min-width: 70px;

            }


            .cart-product-details h3 {

                font-size: 17px;

            }


            .cart-quantity {

                justify-content: flex-start;

            }


            .cart-subtotal {

                min-width: auto;

                text-align: left;

                font-size: 19px;

            }


            .cart-remove {

                position: absolute;

                top: 19px;

                right: 19px;

            }


            .cart-summary-box {

                padding: 23px;

            }

        }


        /* =========================================
           SMALL MOBILE
        ========================================== */

        @media (max-width: 420px) {

            .cart-page-header h1 {

                font-size: 33px;

            }


            .cart-product-image {

                width: 62px;

                height: 62px;

                min-width: 62px;

            }


            .cart-product-details h3 {

                font-size: 15px;

            }


            .cart-product-details p {

                font-size: 12px;

            }


            .quantity-btn {

                width: 35px;

                height: 35px;

            }


            .cart-total span:last-child {

                font-size: 24px;

            }

        }


    </style>

</head>


<body>


<main class="cart-page">


    <!-- =========================================
         BACK TO SHOP
    ========================================== -->

    <div class="cart-back">

        <a href="shop.php">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Shop

        </a>

    </div>


    <!-- =========================================
         CART HEADER
    ========================================== -->

    <div class="cart-page-header">

        <span class="eyebrow">
            NAVA FADE STUDIO
        </span>


        <h1>

            Your <span>Cart</span>

        </h1>


        <p>

            Review your selected grooming products
            before completing your order.

        </p>

    </div>


    <?php if (empty($cart)): ?>


        <!-- =====================================
             EMPTY CART
        ====================================== -->

        <div class="cart-empty">


            <div class="empty-cart-icon">

                <i
                    class="fa-solid fa-bag-shopping"
                ></i>

            </div>


            <h2>
                Your Cart is Empty
            </h2>


            <p>

                You haven't added any products
                to your cart yet.

            </p>


            <a
                href="shop.php"
                class="empty-shop-btn"
            >

                <i
                    class="fa-solid fa-store"
                ></i>

                Browse Products

            </a>


        </div>


    <?php else: ?>


        <!-- =====================================
             MAIN CART
        ====================================== -->

        <div class="cart-layout">


            <!-- =================================
                 CART PRODUCTS
            ================================== -->

            <div class="cart-products">


                <?php foreach (
                    $cart
                    as $product_id => $quantity
                ): ?>


                    <?php

                    $product =
                        $productModel->getById(
                            (int) $product_id
                        );


                    /*
                     * Skip products that
                     * no longer exist.
                     */

                    if (!$product) {

                        continue;

                    }


                    $subtotal =
                        $product["price"]
                        * $quantity;


                    $total += $subtotal;

                    $total_items += $quantity;


                    /*
                     * Find the product image.
                     */

                    $productImage =
                        getProductImage(
                            $product["image"]
                            ?? null
                        );

                    ?>


                    <div class="cart-item">


                        <!-- =========================
                             PRODUCT
                        ========================== -->

                        <div class="cart-item-info">


                            <div
                                class="cart-product-image"
                            >


                                <?php if (
                                    $productImage
                                ): ?>


                                    <img
                                        src="<?= htmlspecialchars(
                                            $productImage
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product["product_name"]
                                        ) ?>"
                                    >


                                <?php else: ?>


                                    <div
                                        class="cart-product-placeholder"
                                        title="Product image unavailable"
                                    >

                                        <i
                                            class="fa-solid fa-box"
                                        ></i>

                                    </div>


                                <?php endif; ?>


                            </div>


                            <div
                                class="cart-product-details"
                            >


                                <h3>

                                    <?= htmlspecialchars(
                                        $product["product_name"]
                                    ) ?>

                                </h3>


                                <p>

                                    Price:

                                    <strong>

                                        ₱<?= number_format(
                                            $product["price"],
                                            2
                                        ) ?>

                                    </strong>

                                </p>


                            </div>


                        </div>


                        <!-- =========================
                             QUANTITY
                        ========================== -->

                        <form
                            method="POST"
                            action="cart.php"
                            class="cart-quantity"
                        >


                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $product_id ?>"
                            >


                            <button
                                type="submit"
                                name="decrease"
                                class="quantity-btn"
                                title="Decrease quantity"
                            >

                                <i
                                    class="fa-solid fa-minus"
                                ></i>

                            </button>


                            <span
                                class="quantity-number"
                            >

                                <?= (int) $quantity ?>

                            </span>


                            <button
                                type="submit"
                                name="increase"
                                class="quantity-btn"
                                title="Increase quantity"
                            >

                                <i
                                    class="fa-solid fa-plus"
                                ></i>

                            </button>


                        </form>


                        <!-- =========================
                             SUBTOTAL
                        ========================== -->

                        <div class="cart-subtotal">

                            ₱<?= number_format(
                                $subtotal,
                                2
                            ) ?>

                        </div>


                        <!-- =========================
                             REMOVE
                        ========================== -->

                        <form
                            method="POST"
                            action="cart.php"
                            class="cart-remove"
                            onsubmit="
                                return confirm(
                                    'Remove this product from your cart?'
                                );
                            "
                        >


                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $product_id ?>"
                            >


                            <button
                                type="submit"
                                name="remove"
                                title="Remove product"
                            >

                                <i
                                    class="fa-solid fa-trash"
                                ></i>

                            </button>


                        </form>


                    </div>


                <?php endforeach; ?>


            </div>


            <!-- =================================
                 SUMMARY
            ================================== -->

            <aside
                class="cart-summary-box"
            >


                <span class="summary-label">

                    Order Summary

                </span>


                <h2>
                    Cart Summary
                </h2>


                <div class="summary-items">

                    <span>
                        Items
                    </span>


                    <strong>

                        <?= $total_items ?>

                        <?= $total_items === 1
                            ? "item"
                            : "items"
                        ?>

                    </strong>

                </div>


                <div class="cart-total">

                    <span>
                        Total
                    </span>


                    <span>

                        ₱<?= number_format(
                            $total,
                            2
                        ) ?>

                    </span>

                </div>


                <!-- CHECKOUT -->

                <a
                    href="checkout.php"
                    class="checkout-btn"
                >

                    <i
                        class="fa-solid fa-credit-card"
                    ></i>

                    Proceed to Checkout

                </a>


                <!-- CONTINUE SHOPPING -->

                <a
                    href="shop.php"
                    class="continue-shopping"
                >

                    <i
                        class="fa-solid fa-arrow-left"
                    ></i>

                    Continue Shopping

                </a>


            </aside>


        </div>


    <?php endif; ?>


</main>


</body>

</html>