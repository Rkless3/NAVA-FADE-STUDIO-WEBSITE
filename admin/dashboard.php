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

$database = new Database();
$db = $database->connect();

/* =========================================================
   ADMIN USERNAME
========================================================= */

$adminUsername = $_SESSION["admin_username"] ?? "Admin";


/* =========================================================
   SERVICES
========================================================= */

$serviceStmt = $db->query("
    SELECT COUNT(*)
    FROM services
");

$totalServices = (int) $serviceStmt->fetchColumn();


/* =========================================================
   BOOKINGS
========================================================= */

$bookingStmt = $db->query("
    SELECT COUNT(*)
    FROM appointments
");

$totalBookings = (int) $bookingStmt->fetchColumn();


$pendingBookingStmt = $db->query("
    SELECT COUNT(*)
    FROM appointments
    WHERE status = 'Pending'
");

$pendingBookings = (int) $pendingBookingStmt->fetchColumn();


$confirmedBookingStmt = $db->query("
    SELECT COUNT(*)
    FROM appointments
    WHERE status = 'Confirmed'
");

$confirmedBookings = (int) $confirmedBookingStmt->fetchColumn();


$completedBookingStmt = $db->query("
    SELECT COUNT(*)
    FROM appointments
    WHERE status = 'Completed'
");

$completedBookings = (int) $completedBookingStmt->fetchColumn();


/* =========================================================
   PRODUCTS
========================================================= */

$productStmt = $db->query("
    SELECT COUNT(*)
    FROM products
");

$totalProducts = (int) $productStmt->fetchColumn();


$activeProductStmt = $db->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'Active'
");

$activeProducts = (int) $activeProductStmt->fetchColumn();


/* =========================================================
   ORDERS
========================================================= */

$orderStmt = $db->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int) $orderStmt->fetchColumn();


$pendingOrderStmt = $db->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'Pending'
");

$pendingOrders = (int) $pendingOrderStmt->fetchColumn();


$completedOrderStmt = $db->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'Completed'
");

$completedOrders = (int) $completedOrderStmt->fetchColumn();


/* =========================================================
   REVIEWS
========================================================= */

$reviewStmt = $db->query("
    SELECT COUNT(*)
    FROM reviews
");

$totalReviews = (int) $reviewStmt->fetchColumn();


$pendingReviewStmt = $db->query("
    SELECT COUNT(*)
    FROM reviews
    WHERE status = 'Pending'
");

$pendingReviews = (int) $pendingReviewStmt->fetchColumn();


/* =========================================================
   SERVICE INCOME
   Completed appointments only
========================================================= */

$serviceIncomeStmt = $db->query("
    SELECT COALESCE(SUM(s.price), 0)
    FROM appointments a
    INNER JOIN services s
        ON a.service = s.service_name
    WHERE a.status = 'Completed'
");

$serviceIncome = (float) $serviceIncomeStmt->fetchColumn();


/* =========================================================
   PRODUCT INCOME
   Completed orders only
========================================================= */

$productIncomeStmt = $db->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status = 'Completed'
");

$productIncome = (float) $productIncomeStmt->fetchColumn();


/* =========================================================
   TOTAL INCOME
========================================================= */

$totalIncome = $serviceIncome + $productIncome;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NAVA Fade Studio | Admin Dashboard</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Bahnschrift, "Segoe UI", Arial, sans-serif;
            background:
                url("../assets/images/pattern3.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #ffffff;
            min-height: 100vh;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .admin-header {
            height: 95px;
            width: 100%;
            background: #0e1423;
            border-bottom: 2px solid #b8862c;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 46px;

            position: sticky;
            top: 0;
            z-index: 1000;
        }


        .header-left {
            display: flex;
            align-items: center;
            height: 100%;
        }


        .logo {
            width: 165px;
            height: 165px;
            object-fit: contain;
            display: block;
        }


        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }


        .welcome-text {
            font-size: 14px;
            font-weight: 600;
            color: #f3f3f3;
        }


        .welcome-text span {
            color: #d5a63a;
        }


        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 82px;
            height: 38px;

            padding: 0 18px;

            border: 1px solid #b8862c;
            border-radius: 7px;

            color: #d5a63a;
            background: transparent;

            text-decoration: none;

            font-size: 13px;
            font-weight: 700;

            transition: all 0.25s ease;
        }


        .logout-btn:hover {
            background: #b8862c;
            color: #0e1423;
            transform: translateY(-1px);
        }


        /* =====================================================
           MAIN LAYOUT
        ===================================================== */

        .admin-layout {
            display: flex;
            min-height: calc(100vh - 95px);
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            width: 250px;
            flex-shrink: 0;

            padding: 35px 20px;

            background: rgba(14, 20, 35, 0.95);

            border-right: 1px solid rgba(184, 134, 44, 0.5);

            min-height: calc(100vh - 95px);

            position: sticky;
            top: 95px;
            align-self: flex-start;
        }


        .sidebar-title {
            font-size: 11px;
            letter-spacing: 3px;
            color: #71809b;

            margin: 0 15px 28px;

            text-transform: uppercase;
        }


        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }


        .sidebar-menu a {
            display: flex;
            align-items: center;

            height: 46px;

            padding: 0 18px;

            border-radius: 9px;

            color: #f1f1f1;
            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            transition:
                background 0.25s ease,
                color 0.25s ease,
                transform 0.25s ease;
        }


        .sidebar-menu a:hover {
            background: rgba(184, 134, 44, 0.15);
            color: #d5a63a;
            transform: translateX(2px);
        }


        .sidebar-menu a.active {
            background: #c28c25;
            color: #0e1423;
            font-weight: 700;

            box-shadow:
                0 6px 18px rgba(0, 0, 0, 0.18);
        }


        .sidebar-menu a.active:hover {
            background: #d19a2b;
            color: #0e1423;
            transform: none;
        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .main-content {
            flex: 1;
            min-width: 0;

            padding: 42px 44px 60px;
        }


        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            margin-bottom: 30px;
        }


        .page-heading h1 {
            font-size: 36px;
            line-height: 1.1;

            margin-bottom: 10px;

            color: #ffffff;
            font-weight: 700;
        }


        .page-heading p {
            color: #a9b1c1;
            font-size: 14px;
            line-height: 1.6;
        }


        /* =====================================================
           INCOME OVERVIEW
        ===================================================== */

        .income-section {
            margin-bottom: 35px;
        }


        .section-title {
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;

            margin-bottom: 15px;
        }


        .income-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 18px;
        }


        .income-card {
            min-height: 145px;

            padding: 25px;

            border: 1px solid rgba(184, 134, 44, 0.35);
            border-radius: 14px;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 46, 0.96),
                    rgba(13, 20, 35, 0.96)
                );

            position: relative;
            overflow: hidden;
        }


        .income-card::after {
            content: "";

            position: absolute;

            width: 95px;
            height: 95px;

            right: -45px;
            bottom: -45px;

            border: 1px solid rgba(184, 134, 44, 0.3);

            border-radius: 50%;
        }


        .income-label {
            color: #9ca8bc;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;

            margin-bottom: 12px;
        }


        .income-value {
            color: #d5a63a;
            font-size: 31px;
            font-weight: 700;
        }


        .income-description {
            margin-top: 9px;

            color: #7f8ca1;
            font-size: 12px;
        }


        /* =====================================================
           OVERVIEW CARDS
        ===================================================== */

        .overview-section {
            margin-bottom: 35px;
        }


        .overview-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;
        }


        .stat-card {
            min-height: 135px;

            padding: 23px;

            background: rgba(15, 23, 40, 0.94);

            border: 1px solid rgba(184, 134, 44, 0.27);

            border-radius: 13px;

            position: relative;

            overflow: hidden;

            transition:
                transform 0.25s ease,
                border-color 0.25s ease;
        }


        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(184, 134, 44, 0.6);
        }


        .stat-card::after {
            content: "";

            position: absolute;

            width: 75px;
            height: 75px;

            right: -35px;
            bottom: -35px;

            border: 1px solid rgba(184, 134, 44, 0.2);

            border-radius: 50%;
        }


        .stat-label {
            font-size: 11px;

            letter-spacing: 1.4px;

            text-transform: uppercase;

            color: #8c98ad;

            margin-bottom: 10px;
        }


        .stat-number {
            font-size: 29px;

            color: #d5a63a;

            font-weight: 700;
        }


        .stat-subtitle {
            margin-top: 7px;

            font-size: 12px;

            color: #758197;
        }


        /* =====================================================
           QUICK ACTIONS
        ===================================================== */

        .quick-section {
            margin-top: 5px;
        }


        .quick-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 15px;
        }


        .quick-card {
            min-height: 105px;

            padding: 21px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            background: rgba(14, 20, 35, 0.92);

            border: 1px solid rgba(184, 134, 44, 0.28);

            border-radius: 12px;

            text-decoration: none;

            transition: all 0.25s ease;
        }


        .quick-card:hover {
            border-color: #b8862c;

            background: rgba(184, 134, 44, 0.08);

            transform: translateY(-2px);
        }


        .quick-title {
            color: #ffffff;

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 6px;
        }


        .quick-description {
            color: #77849a;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1200px) {

            .main-content {
                padding: 38px 30px 50px;
            }

            .income-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

            .overview-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .quick-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 900px) {

            .admin-header {
                padding: 0 25px;
            }

            .sidebar {
                width: 215px;
            }

            .income-grid {
                grid-template-columns: 1fr;
            }

            .page-heading h1 {
                font-size: 31px;
            }
        }


        @media (max-width: 700px) {

            .admin-header {
                height: 80px;
                padding: 0 18px;
            }

            .admin-layout {
                min-height: calc(100vh - 80px);
            }

            .logo {
                width: 135px;
                height: 135px;
            }

            .welcome-text {
                display: none;
            }

            .sidebar {
                position: static;

                width: 100%;

                min-height: auto;

                border-right: none;
                border-bottom: 1px solid rgba(184, 134, 44, 0.35);

                padding: 18px;
            }

            .admin-layout {
                flex-direction: column;
            }

            .sidebar-title {
                margin-bottom: 12px;
            }

            .sidebar-menu {
                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 6px;
            }

            .sidebar-menu a {
                height: 42px;
                font-size: 13px;
            }

            .main-content {
                padding: 30px 18px 45px;
            }

            .page-heading {
                margin-bottom: 25px;
            }

            .page-heading h1 {
                font-size: 28px;
            }

            .overview-grid {
                grid-template-columns: 1fr;
            }

            .quick-grid {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 430px) {

            .admin-header {
                padding: 0 12px;
            }

            .logo {
                width: 125px;
                height: 125px;
            }

            .logout-btn {
                min-width: 72px;
                height: 35px;

                padding: 0 13px;

                font-size: 12px;
            }

            .sidebar-menu {
                grid-template-columns: 1fr;
            }

            .income-card,
            .stat-card,
            .quick-card {
                padding: 19px;
            }

            .income-value {
                font-size: 27px;
            }

            .stat-number {
                font-size: 26px;
            }
        }

    </style>

</head>

<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="admin-header">

    <div class="header-left">

        <img
            src="../assets/images/logo.png"
            alt="NAVA Fade Studio"
            class="logo"
        >

    </div>


    <div class="header-right">

        <div class="welcome-text">
            Welcome,
            <span>
                <?= htmlspecialchars($adminUsername) ?>
            </span>
        </div>

        <a
            href="logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>

</header>


<!-- =========================================================
     ADMIN LAYOUT
========================================================= -->

<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-title">
            Admin Panel
        </div>


        <nav class="sidebar-menu">

            <a
                href="dashboard.php"
                class="active"
            >
                Dashboard
            </a>


            <a href="services.php">
                Services
            </a>


            <a href="bookings.php">
                Bookings
            </a>


            <a href="products.php">
                Products
            </a>


            <a href="orders.php">
                Orders
            </a>


            <a href="reviews.php">
                Reviews
            </a>


            <a href="settings.php">
                Settings
            </a>

        </nav>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome back. Here's an overview of your NAVA Fade Studio.
                </p>

            </div>

        </div>


        <!-- =================================================
             INCOME OVERVIEW
        ================================================== -->

        <section class="income-section">

            <h2 class="section-title">
                Income Overview
            </h2>


            <div class="income-grid">


                <!-- TOTAL INCOME -->

                <div class="income-card">

                    <div class="income-label">
                        Total Income
                    </div>

                    <div class="income-value">
                        ₱<?= number_format($totalIncome, 2) ?>
                    </div>

                    <div class="income-description">
                        From completed services and product orders
                    </div>

                </div>


                <!-- SERVICE INCOME -->

                <div class="income-card">

                    <div class="income-label">
                        Service Income
                    </div>

                    <div class="income-value">
                        ₱<?= number_format($serviceIncome, 2) ?>
                    </div>

                    <div class="income-description">
                        Completed appointments
                    </div>

                </div>


                <!-- PRODUCT INCOME -->

                <div class="income-card">

                    <div class="income-label">
                        Product Income
                    </div>

                    <div class="income-value">
                        ₱<?= number_format($productIncome, 2) ?>
                    </div>

                    <div class="income-description">
                        Completed product orders
                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             OVERVIEW
        ================================================== -->

        <section class="overview-section">

            <h2 class="section-title">
                Overview
            </h2>


            <div class="overview-grid">


                <!-- SERVICES -->

                <div class="stat-card">

                    <div class="stat-label">
                        Total Services
                    </div>

                    <div class="stat-number">
                        <?= $totalServices ?>
                    </div>

                    <div class="stat-subtitle">
                        Services offered by NAVA
                    </div>

                </div>


                <!-- BOOKINGS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Total Bookings
                    </div>

                    <div class="stat-number">
                        <?= $totalBookings ?>
                    </div>

                    <div class="stat-subtitle">
                        <?= $pendingBookings ?> pending ·
                        <?= $confirmedBookings ?> confirmed
                    </div>

                </div>


                <!-- COMPLETED BOOKINGS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Completed Bookings
                    </div>

                    <div class="stat-number">
                        <?= $completedBookings ?>
                    </div>

                    <div class="stat-subtitle">
                        Successfully completed appointments
                    </div>

                </div>


                <!-- PRODUCTS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Total Products
                    </div>

                    <div class="stat-number">
                        <?= $totalProducts ?>
                    </div>

                    <div class="stat-subtitle">
                        <?= $activeProducts ?> active products
                    </div>

                </div>


                <!-- ORDERS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Total Orders
                    </div>

                    <div class="stat-number">
                        <?= $totalOrders ?>
                    </div>

                    <div class="stat-subtitle">
                        <?= $pendingOrders ?> pending ·
                        <?= $completedOrders ?> completed
                    </div>

                </div>


                <!-- REVIEWS -->

                <div class="stat-card">

                    <div class="stat-label">
                        Total Reviews
                    </div>

                    <div class="stat-number">
                        <?= $totalReviews ?>
                    </div>

                    <div class="stat-subtitle">
                        <?= $pendingReviews ?> reviews awaiting approval
                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="quick-section">

            <h2 class="section-title">
                Quick Actions
            </h2>


            <div class="quick-grid">


                <a
                    href="services.php"
                    class="quick-card"
                >

                    <div class="quick-title">
                        Manage Services
                    </div>

                    <div class="quick-description">
                        Add, edit, or remove NAVA services.
                    </div>

                </a>


                <a
                    href="bookings.php"
                    class="quick-card"
                >

                    <div class="quick-title">
                        Manage Bookings
                    </div>

                    <div class="quick-description">
                        Review appointments and update booking status.
                    </div>

                </a>


                <a
                    href="products.php"
                    class="quick-card"
                >

                    <div class="quick-title">
                        Manage Products
                    </div>

                    <div class="quick-description">
                        Manage your shop products, prices, and stock.
                    </div>

                </a>


                <a
                    href="orders.php"
                    class="quick-card"
                >

                    <div class="quick-title">
                        Manage Orders
                    </div>

                    <div class="quick-description">
                        Review customer purchases and order status.
                    </div>

                </a>


                <a
                    href="reviews.php"
                    class="quick-card"
                >

                    <div class="quick-title">
                        Manage Reviews
                    </div>

                    <div class="quick-description">
                        Approve, hide, delete, or feature customer reviews.
                    </div>

                </a>


                <a
                    href="services.php"
                    class="quick-card"
                >

                    <div class="quick-title">
                        View Services
                    </div>

                    <div class="quick-description">
                        Check the current services available at NAVA.
                    </div>

                </a>

            </div>

        </section>


    </main>

</div>


</body>

</html>