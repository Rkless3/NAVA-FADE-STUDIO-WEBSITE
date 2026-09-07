<?php

session_start();

/* =========================================
   CHECK IF CUSTOMER IS LOGGED IN
========================================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}


/* =========================================
   DATABASE
========================================= */

require_once "config/Database.php";

$database = new Database();
$db = $database->connect();

$customer_id = $_SESSION["customer_id"];


/* =========================================
   GET CUSTOMER ORDERS
========================================= */

$order_query = $db->prepare("
    SELECT
        id,
        total_amount,
        status,
        created_at
    FROM orders
    WHERE customer_id = :customer_id
    ORDER BY id DESC
");

$order_query->execute([
    ":customer_id" => $customer_id
]);

$orders = $order_query->fetchAll(PDO::FETCH_ASSOC);


/* =========================================
   GET ORDER ITEMS
========================================= */

$order_items = [];

if (!empty($orders)) {

    $item_query = $db->prepare("
        SELECT
            oi.order_id,
            oi.quantity,
            oi.price,
            oi.subtotal,
            COALESCE(
                p.product_name,
                'Product no longer available'
            ) AS product_name,
            p.image
        FROM order_items oi
        LEFT JOIN products p
            ON p.id = oi.product_id
        WHERE oi.order_id = :order_id
        ORDER BY oi.id ASC
    ");

    foreach ($orders as $order) {

        $item_query->execute([
            ":order_id" => $order["id"]
        ]);

        $order_items[$order["id"]] =
            $item_query->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* =========================================
   HELPER FUNCTIONS
========================================= */

function getStatusClass(string $status): string
{
    return strtolower(str_replace(" ", "-", $status));
}


function getStatusIcon(string $status): string
{
    switch ($status) {

        case "Pending":
            return "🕐";

        case "Confirmed":
            return "✓";

        case "Processing":
            return "⚙";

        case "Completed":
            return "✓";

        case "Cancelled":
            return "×";

        default:
            return "•";
    }
}


function getStatusDescription(string $status): string
{
    switch ($status) {

        case "Pending":
            return "Your order has been received and is waiting for confirmation.";

        case "Confirmed":
            return "Your order has been confirmed by NAVA Fade Studio.";

        case "Processing":
            return "Your order is currently being prepared.";

        case "Completed":
            return "Your order has been completed successfully.";

        case "Cancelled":
            return "This order has been cancelled.";

        default:
            return "Your order status has been updated.";
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

    <title>My Orders | NAVA Fade Studio</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /* =========================================
           MY ORDERS PAGE
        ========================================= */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            background: #0e1423;
        }


        .orders-page {

            min-height: 100vh;

            padding: 125px 20px 80px;

            background:
                linear-gradient(
                    rgba(14, 20, 35, 0.94)
                ),
                url("assets/images/pattern3.png");

            background-size: 300px;

            background-attachment: fixed;
        }


        .orders-container {

            width: 100%;

            max-width: 1120px;

            margin: 0 auto;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .orders-header {

            text-align: center;

            margin-bottom: 50px;
        }


        .orders-eyebrow {

            display: inline-block;

            color: #c8942f;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 4px;

            text-transform: uppercase;

            margin-bottom: 12px;
        }


        .orders-header h1 {

            margin: 0;

            color: #ffffff;

            font-size: clamp(38px, 5vw, 58px);

            line-height: 1.1;

            font-weight: 800;

            letter-spacing: -1px;
        }


        .orders-header h1 span {

            color: #c8942f;
        }


        .orders-header p {

            margin: 15px auto 0;

            max-width: 600px;

            color: #aeb7c8;

            font-size: 16px;

            line-height: 1.6;
        }


        /* =========================================
           ORDER CARD
        ========================================= */

        .order-card {

            position: relative;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 45, 0.98),
                    rgba(10, 16, 29, 0.98)
                );

            border: 1px solid rgba(200, 148, 47, 0.65);

            border-radius: 18px;

            padding: 30px;

            margin-bottom: 28px;

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.30);

            overflow: hidden;

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }


        .order-card::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;
            height: 3px;

            background: linear-gradient(
                90deg,
                transparent,
                #c8942f,
                transparent
            );
        }


        .order-card:hover {

            transform: translateY(-3px);

            border-color: #c8942f;

            box-shadow:
                0 22px 55px rgba(0, 0, 0, 0.40);
        }


        /* =========================================
           ORDER HEADER
        ========================================= */

        .order-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 25px;

            padding-bottom: 24px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.08);
        }


        .order-heading {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .order-label {

            color: #c8942f;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 2px;

            text-transform: uppercase;
        }


        .order-number {

            color: #ffffff;

            font-size: 22px;

            font-weight: 800;
        }


        .order-date {

            color: #9fa9bb;

            font-size: 14px;
        }


        .order-status-wrapper {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            gap: 8px;
        }


        .order-status {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 9px 16px;

            border-radius: 30px;

            font-size: 13px;

            font-weight: 700;

            border: 1px solid transparent;

            white-space: nowrap;
        }


        .status-icon {

            width: 19px;

            height: 19px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            font-size: 11px;

            font-weight: 900;
        }


        /* =========================================
           STATUS COLORS
        ========================================= */

        .status-pending {

            color: #ffc107;

            background: rgba(255, 193, 7, 0.10);

            border-color: rgba(255, 193, 7, 0.45);
        }


        .status-pending .status-icon {

            background: rgba(255, 193, 7, 0.20);
        }


        .status-confirmed {

            color: #36c7d8;

            background: rgba(54, 199, 216, 0.10);

            border-color: rgba(54, 199, 216, 0.45);
        }


        .status-confirmed .status-icon {

            background: rgba(54, 199, 216, 0.20);
        }


        .status-processing {

            color: #55a9ff;

            background: rgba(85, 169, 255, 0.10);

            border-color: rgba(85, 169, 255, 0.45);
        }


        .status-processing .status-icon {

            background: rgba(85, 169, 255, 0.20);
        }


        .status-completed {

            color: #62d47b;

            background: rgba(98, 212, 123, 0.10);

            border-color: rgba(98, 212, 123, 0.45);
        }


        .status-completed .status-icon {

            background: rgba(98, 212, 123, 0.20);
        }


        .status-cancelled {

            color: #ff6565;

            background: rgba(255, 101, 101, 0.10);

            border-color: rgba(255, 101, 101, 0.45);
        }


        .status-cancelled .status-icon {

            background: rgba(255, 101, 101, 0.20);
        }


        .status-description {

            color: #7f8ba0;

            font-size: 12px;

            text-align: right;

            max-width: 280px;

            line-height: 1.4;
        }


        /* =========================================
           ORDER PROGRESS
        ========================================= */

        .order-progress {

            display: flex;

            align-items: flex-start;

            margin: 28px 0 12px;

            position: relative;
        }


        .progress-line {

            position: absolute;

            top: 15px;

            left: 10%;

            right: 10%;

            height: 2px;

            background: rgba(255, 255, 255, 0.10);

            z-index: 0;
        }


        .progress-line-active {

            position: absolute;

            top: 15px;

            left: 10%;

            height: 2px;

            background: #c8942f;

            z-index: 1;

            transition: width 0.4s ease;
        }


        .progress-step {

            position: relative;

            z-index: 2;

            width: 25%;

            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 8px;

            color: #707b8f;

            font-size: 11px;

            font-weight: 600;

            text-align: center;
        }


        .progress-circle {

            width: 30px;

            height: 30px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #111a2c;

            border: 2px solid #394255;

            color: #707b8f;

            font-size: 12px;

            font-weight: 800;
        }


        .progress-step.active {

            color: #dca63b;
        }


        .progress-step.active .progress-circle {

            background: #c8942f;

            border-color: #c8942f;

            color: #0e1423;

            box-shadow:
                0 0 0 5px rgba(200, 148, 47, 0.10);
        }


        .progress-step.completed {

            color: #c8942f;
        }


        .progress-step.completed .progress-circle {

            background: #c8942f;

            border-color: #c8942f;

            color: #0e1423;
        }


        .progress-cancelled {

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 25px 0 5px;

            padding: 14px;

            border-radius: 10px;

            background: rgba(255, 101, 101, 0.07);

            border: 1px solid rgba(255, 101, 101, 0.20);

            color: #ff7777;

            font-size: 13px;

            font-weight: 600;
        }


        /* =========================================
           PRODUCTS
        ========================================= */

        .products-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-top: 25px;

            margin-bottom: 5px;
        }


        .products-heading span:first-child {

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;
        }


        .items-count {

            color: #8d98aa;

            font-size: 12px;
        }


        .order-item {

            display: flex;

            align-items: center;

            gap: 20px;

            padding: 20px 0;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.07);

            transition: padding 0.2s ease;
        }


        .order-item:last-child {

            border-bottom: none;
        }


        .order-item:hover {

            padding-left: 5px;

            padding-right: 5px;
        }


        /* =========================================
           PRODUCT IMAGE
        ========================================= */

        .order-item-image {

            width: 86px;

            height: 86px;

            flex-shrink: 0;

            border-radius: 13px;

            overflow: hidden;

            background: #ffffff;

            border: 1px solid rgba(200, 148, 47, 0.35);

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .order-item-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

            transition: transform 0.3s ease;
        }


        .order-item:hover .order-item-image img {

            transform: scale(1.05);
        }


        /* =========================================
           PRODUCT INFORMATION
        ========================================= */

        .order-item-info {

            flex: 1;

            min-width: 0;
        }


        .order-item-name {

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;

            margin-bottom: 7px;
        }


        .order-item-details {

            color: #929db0;

            font-size: 13px;

            line-height: 1.5;
        }


        .order-item-details strong {

            color: #c8942f;
        }


        .order-item-subtotal {

            color: #dca63b;

            font-size: 17px;

            font-weight: 800;

            white-space: nowrap;
        }


        /* =========================================
           ORDER FOOTER
        ========================================= */

        .order-bottom {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-top: 10px;

            padding-top: 24px;

            border-top:
                1px solid
                rgba(255, 255, 255, 0.10);
        }


        .order-summary-label {

            color: #8994a7;

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: 1.5px;

            font-weight: 700;
        }


        .order-total {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .order-total-label {

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;
        }


        .order-total-price {

            color: #e0a936;

            font-size: 25px;

            font-weight: 900;
        }


        /* =========================================
           EMPTY ORDERS
        ========================================= */

        .empty-orders {

            text-align: center;

            padding: 75px 25px;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 45, 0.98),
                    rgba(10, 16, 29, 0.98)
                );

            border:
                1px solid
                rgba(200, 148, 47, 0.60);

            border-radius: 18px;

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.30);
        }


        .empty-orders-icon {

            width: 75px;

            height: 75px;

            margin: 0 auto 20px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: rgba(200, 148, 47, 0.10);

            border: 1px solid rgba(200, 148, 47, 0.30);

            font-size: 32px;
        }


        .empty-orders h2 {

            margin: 0 0 10px;

            color: #ffffff;

            font-size: 25px;
        }


        .empty-orders p {

            max-width: 500px;

            margin: 0 auto 25px;

            color: #8f9bad;

            font-size: 14px;

            line-height: 1.6;
        }


        .shop-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            background: #c8942f;

            color: #0e1423;

            text-decoration: none;

            padding: 13px 25px;

            border-radius: 8px;

            font-weight: 800;

            font-size: 13px;

            transition:
                background 0.25s ease,
                transform 0.25s ease;
        }


        .shop-btn:hover {

            background: #e0aa3b;

            transform: translateY(-2px);
        }


        /* =========================================
           BACK BUTTON
        ========================================= */

        .orders-back {

            text-align: center;

            margin-top: 35px;
        }


        .orders-back a {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 18px;

            border: 1px solid rgba(200, 148, 47, 0.45);

            border-radius: 8px;

            color: #c8942f;

            background: rgba(14, 20, 35, 0.50);

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition:
                background 0.25s ease,
                border-color 0.25s ease,
                transform 0.25s ease;
        }


        .orders-back a:hover {

            background: rgba(200, 148, 47, 0.10);

            border-color: #c8942f;

            transform: translateY(-2px);
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 800px) {

            .orders-page {

                padding: 110px 15px 60px;

                background-size: 240px;
            }


            .order-card {

                padding: 22px;

                border-radius: 15px;
            }


            .order-top {

                flex-direction: column;

                gap: 15px;
            }


            .order-status-wrapper {

                align-items: flex-start;
            }


            .status-description {

                text-align: left;

                max-width: 100%;
            }


            .order-progress {

                margin-top: 25px;
            }


            .progress-step {

                font-size: 10px;
            }


            .order-item {

                gap: 14px;
            }


            .order-item-image {

                width: 72px;

                height: 72px;
            }


            .order-item-name {

                font-size: 15px;
            }


            .order-item-subtotal {

                font-size: 15px;
            }
        }


        @media (max-width: 600px) {

            .orders-page {

                padding: 100px 12px 50px;
            }


            .orders-header {

                margin-bottom: 35px;
            }


            .orders-header h1 {

                font-size: 38px;
            }


            .orders-header p {

                font-size: 14px;
            }


            .order-card {

                padding: 18px;

                margin-bottom: 20px;
            }


            .order-number {

                font-size: 19px;
            }


            .order-date {

                font-size: 12px;
            }


            .order-status {

                font-size: 12px;

                padding: 8px 13px;
            }


            .order-progress {

                margin-left: -5px;

                margin-right: -5px;
            }


            .progress-circle {

                width: 27px;

                height: 27px;

                font-size: 10px;
            }


            .progress-line,
            .progress-line-active {

                top: 13px;
            }


            .progress-step {

                font-size: 9px;
            }


            .order-item {

                display: grid;

                grid-template-columns: 65px 1fr;

                gap: 12px;

                padding: 17px 0;
            }


            .order-item-image {

                width: 65px;

                height: 65px;

                grid-row: span 2;
            }


            .order-item-info {

                width: 100%;
            }


            .order-item-name {

                font-size: 14px;

                margin-bottom: 4px;
            }


            .order-item-details {

                font-size: 12px;
            }


            .order-item-subtotal {

                grid-column: 2;

                font-size: 15px;

                margin-top: -5px;
            }


            .order-bottom {

                align-items: flex-end;

                flex-direction: column;

                gap: 8px;
            }


            .order-total {

                width: 100%;

                justify-content: space-between;
            }


            .order-total-price {

                font-size: 23px;
            }


            .empty-orders {

                padding: 55px 18px;
            }
        }


        @media (max-width: 400px) {

            .orders-header h1 {

                font-size: 34px;
            }


            .order-card {

                padding: 15px;
            }


            .progress-step {

                font-size: 8px;
            }


            .progress-circle {

                width: 24px;

                height: 24px;
            }


            .progress-line,
            .progress-line-active {

                top: 12px;
            }
        }

    </style>

</head>


<body>


<section class="orders-page">

    <div class="orders-container">


        <!-- =========================================
             PAGE HEADER
        ========================================= -->

        <div class="orders-header">

            <div class="orders-eyebrow">
                NAVA FADE STUDIO
            </div>

            <h1>
                My <span>Orders</span>
            </h1>

            <p>
                Track your grooming products and stay updated
                with the status of every order you've placed.
            </p>

        </div>


        <?php if (empty($orders)): ?>


            <!-- =====================================
                 NO ORDERS
            ====================================== -->

            <div class="empty-orders">

                <div class="empty-orders-icon">
                    🛍️
                </div>

                <h2>
                    No Orders Yet
                </h2>

                <p>
                    You haven't purchased any grooming products
                    from NAVA Fade Studio yet.
                </p>

                <a
                    href="shop.php"
                    class="shop-btn"
                >
                    🛒 SHOP PRODUCTS
                </a>

            </div>


        <?php else: ?>


            <!-- =====================================
                 ORDERS
            ====================================== -->

            <?php foreach ($orders as $order): ?>

                <?php

                $status = $order["status"];

                $statusClass = getStatusClass($status);

                $statusIcon = getStatusIcon($status);

                $items = $order_items[$order["id"]] ?? [];

                $itemCount = 0;

                foreach ($items as $item) {
                    $itemCount += (int) $item["quantity"];
                }

                ?>


                <div class="order-card">


                    <!-- =================================
                         ORDER HEADER
                    ================================== -->

                    <div class="order-top">

                        <div class="order-heading">

                            <div class="order-label">
                                Order Details
                            </div>

                            <div class="order-number">
                                Order #<?= (int) $order["id"] ?>
                            </div>

                            <div class="order-date">

                                Placed on
                                <?= date(
                                    "F j, Y • h:i A",
                                    strtotime($order["created_at"])
                                ) ?>

                            </div>

                        </div>


                        <div class="order-status-wrapper">

                            <span
                                class="order-status status-<?= htmlspecialchars($statusClass) ?>"
                            >

                                <span class="status-icon">
                                    <?= htmlspecialchars($statusIcon) ?>
                                </span>

                                <?= htmlspecialchars($status) ?>

                            </span>


                            <div class="status-description">

                                <?= htmlspecialchars(
                                    getStatusDescription($status)
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- =================================
                         ORDER PROGRESS
                    ================================== -->

                    <?php if ($status !== "Cancelled"): ?>

                        <?php

                        $progressMap = [
                            "Pending" => 1,
                            "Confirmed" => 2,
                            "Processing" => 3,
                            "Completed" => 4
                        ];

                        $currentStep =
                            $progressMap[$status] ?? 1;

                        $activeWidth =
                            (($currentStep - 1) / 3) * 80;

                        ?>

                        <div class="order-progress">

                            <div class="progress-line"></div>

                            <div
                                class="progress-line-active"
                                style="width: <?= $activeWidth ?>%;"
                            ></div>


                            <!-- STEP 1 -->

                            <div
                                class="
                                    progress-step
                                    <?= $currentStep >= 1
                                        ? "completed"
                                        : "" ?>
                                    <?= $currentStep === 1
                                        ? "active"
                                        : "" ?>
                                "
                            >

                                <div class="progress-circle">
                                    1
                                </div>

                                <span>
                                    Pending
                                </span>

                            </div>


                            <!-- STEP 2 -->

                            <div
                                class="
                                    progress-step
                                    <?= $currentStep >= 2
                                        ? "completed"
                                        : "" ?>
                                    <?= $currentStep === 2
                                        ? "active"
                                        : "" ?>
                                "
                            >

                                <div class="progress-circle">
                                    2
                                </div>

                                <span>
                                    Confirmed
                                </span>

                            </div>


                            <!-- STEP 3 -->

                            <div
                                class="
                                    progress-step
                                    <?= $currentStep >= 3
                                        ? "completed"
                                        : "" ?>
                                    <?= $currentStep === 3
                                        ? "active"
                                        : "" ?>
                                "
                            >

                                <div class="progress-circle">
                                    3
                                </div>

                                <span>
                                    Processing
                                </span>

                            </div>


                            <!-- STEP 4 -->

                            <div
                                class="
                                    progress-step
                                    <?= $currentStep >= 4
                                        ? "completed"
                                        : "" ?>
                                    <?= $currentStep === 4
                                        ? "active"
                                        : "" ?>
                                "
                            >

                                <div class="progress-circle">
                                    4
                                </div>

                                <span>
                                    Completed
                                </span>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="progress-cancelled">

                            ✕ &nbsp;

                            This order has been cancelled.

                        </div>

                    <?php endif; ?>


                    <!-- =================================
                         PRODUCTS HEADER
                    ================================== -->

                    <div class="products-heading">

                        <span>
                            Products
                        </span>

                        <span class="items-count">

                            <?= $itemCount ?>

                            <?= $itemCount === 1
                                ? "item"
                                : "items" ?>

                        </span>

                    </div>


                    <!-- =================================
                         PRODUCTS
                    ================================== -->

                    <?php foreach ($items as $item): ?>

                        <div class="order-item">


                            <!-- PRODUCT IMAGE -->

                            <div class="order-item-image">

                                <?php if (!empty($item["image"])): ?>

                                    <img
                                        src="assets/images/<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        🛍️
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- PRODUCT INFORMATION -->

                            <div class="order-item-info">

                                <div class="order-item-name">

                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>

                                </div>


                                <div class="order-item-details">

                                    ₱<?= number_format(
                                        (float) $item["price"],
                                        2
                                    ) ?>

                                    each

                                    &nbsp; • &nbsp;

                                    Quantity:

                                    <strong>
                                        <?= (int) $item["quantity"] ?>
                                    </strong>

                                </div>

                            </div>


                            <!-- SUBTOTAL -->

                            <div class="order-item-subtotal">

                                ₱<?= number_format(
                                    (float) $item["subtotal"],
                                    2
                                ) ?>

                            </div>


                        </div>

                    <?php endforeach; ?>


                    <!-- =================================
                         ORDER TOTAL
                    ================================== -->

                    <div class="order-bottom">

                        <div>

                            <div class="order-summary-label">
                                Order Summary
                            </div>

                        </div>


                        <div class="order-total">

                            <span class="order-total-label">
                                Total
                            </span>

                            <span class="order-total-price">

                                ₱<?= number_format(
                                    (float) $order["total_amount"],
                                    2
                                ) ?>

                            </span>

                        </div>

                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


        <!-- =========================================
             BACK TO HOME
        ========================================= -->

        <div class="orders-back">

            <a href="index.php">
                ← Back to Home
            </a>

        </div>


    </div>

</section>


</body>

</html>