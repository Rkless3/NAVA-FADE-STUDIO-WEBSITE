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
require_once "../classes/Order.php";

$database = new Database();
$db = $database->connect();

$orderModel = new Order($db);

$allowedStatuses = [
    "Pending",
    "Confirmed",
    "Processing",
    "Completed",
    "Cancelled"
];

$message = "";


// ==============================
// UPDATE ORDER STATUS
// ==============================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {

    $order_id = (int) ($_POST["order_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if ($order_id > 0 && in_array($status, $allowedStatuses, true)) {

        if ($orderModel->updateStatus($order_id, $status)) {
            header("Location: orders.php?message=updated");
            exit;
        }

        $message = "Failed to update order status.";

    } else {

        $message = "Invalid order information.";
    }
}


// ==============================
// SUCCESS MESSAGE
// ==============================

if (isset($_GET["message"]) && $_GET["message"] === "updated") {
    $message = "Order status updated successfully.";
}


// ==============================
// GET ORDERS
// ==============================

$orders = $orderModel->getAll();


// ==============================
// GET ORDER ITEMS
// ==============================

$orderItems = [];

foreach ($orders as $order) {

    $orderItems[$order["id"]] =
        $orderModel->getItemsByOrderId((int) $order["id"]);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Orders | NAVA Fade Studio</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Bahnschrift, "Myriad Pro", Arial, sans-serif;
            background:
                url("../assets/images/pattern3.png");

            background-size: cover;

            background-position: center;
            
            color: white;
            min-height: 100vh;
        }


        /* =========================
           HEADER
        ========================= */

        .admin-header {
            width: 100%;

            height: 95px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 50px;

            background: #0e1423;

            border-bottom: 2px solid #b8862c;

            position: sticky;
            top: 0;

            z-index: 1000;
        }


        .admin-logo {
            display: flex;
            align-items: center;

            gap: 15px;
        }


        .admin-logo img {
            width: 165px;
            height: 165px;

            object-fit: contain;
        }

        .admin-user {

            display: flex;

            align-items: center;

            gap: 20px;

        }

        .admin-user span {

            font-size: 15px;

        }


        .logout-btn {

            padding: 10px 20px;

            color: #b8862c;

            border: 2px solid #b8862c;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

        }

        .logout-btn:hover {
            background: #b8862c;
            color: #0e1423;
        }


        /* =========================================
           DASHBOARD LAYOUT
           ========================================= */

        .dashboard {
            display: flex;

            min-height: calc(100vh - 80px);
        }


        /* =========================================
           SIDEBAR
           ========================================= */

        .sidebar {
            width: 250px;

            padding: 35px 20px;

            background: rgba(14, 20, 35, 0.95);

            border-right: 1px solid rgba(184, 134, 44, 0.5);
        }


        .sidebar-title {
            margin-bottom: 25px;

            padding-left: 15px;

            color: #888;

            font-size: 13px;

            text-transform: uppercase;

            letter-spacing: 2px;
        }


        .sidebar a {
            display: block;

            padding: 15px 18px;

            margin-bottom: 8px;

            border-radius: 10px;

            color: #ffffff;

            text-decoration: none;

            transition: 0.3s ease;
        }


        .sidebar a:hover,
        .sidebar a.active {
            background: #b8862c;

            color: #0e1423;
        }


        /* =========================================
           MAIN CONTENT
           ========================================= */

        .main-content {
            flex: 1;

            padding: 50px;
        }

        .page-header {
            margin-bottom: 35px;
        }

        .page-header h1 {
            font-size: 38px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #aeb5c3;
            font-size: 17px;
        }


        /* =========================
           MESSAGE
        ========================= */

        .message {
            background: rgba(46, 204, 113, 0.12);

            color: #2ecc71;

            border: 1px solid #2ecc71;

            padding: 15px 18px;
            border-radius: 9px;

            margin-bottom: 25px;

            font-weight: bold;
        }


        /* =========================
           TABLE
        ========================= */

        .table-container {
            background: rgba(14, 20, 35, 0.96);

            border: 1px solid #b8862c;
            border-radius: 15px;

            overflow-x: auto;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        thead {
            background: #b8862c;
            color: #0e1423;
        }

        th {
            padding: 18px 16px;
            text-align: left;

            font-size: 14px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 16px;

            border-bottom: 1px solid rgba(255, 255, 255, 0.08);

            color: #e5e8ee;
            vertical-align: top;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }


        /* =========================
           ORDER NUMBER
        ========================= */

        .order-number {
            color: #d19a2a;
            font-weight: bold;
            font-size: 16px;
        }


        /* =========================
           CUSTOMER
        ========================= */

        .customer-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .customer-details {
            color: #9fa7b8;
            font-size: 13px;
            line-height: 1.5;
        }


        /* =========================
           PRODUCTS
        ========================= */

        .product-list {
            list-style: none;
            padding: 0;
        }

        .product-list li {
            margin-bottom: 7px;
            color: #dfe3ea;
            font-size: 14px;
        }

        .product-list li:last-child {
            margin-bottom: 0;
        }

        .product-qty {
            color: #b8862c;
            font-weight: bold;
        }


        /* =========================
           TOTAL
        ========================= */

        .order-total {
            color: #d19a2a;
            font-size: 17px;
            font-weight: bold;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;

            padding: 7px 12px;
            border-radius: 20px;

            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .status-confirmed {
            background: rgba(0, 188, 212, 0.15);
            color: #00bcd4;
        }

        .status-processing {
            background: rgba(33, 150, 243, 0.15);
            color: #2196f3;
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.15);
            color: #4caf50;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.15);
            color: #f44336;
        }


        /* =========================
           UPDATE FORM
        ========================= */

        .status-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-select {
            background: #151d30;
            color: white;

            border: 1px solid #4d5567;
            border-radius: 7px;

            padding: 9px 10px;

            font-family: inherit;
            font-size: 13px;
        }

        .update-btn {
            background: #b8862c;
            color: #0e1423;

            border: none;
            border-radius: 7px;

            padding: 9px 12px;

            font-family: inherit;
            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }

        .update-btn:hover {
            background: #d19a2a;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty-orders {
            padding: 60px 20px;
            text-align: center;

            color: #9fa7b8;
            font-size: 17px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .admin-header {
                padding: 0 20px;
            }

            .admin-user {
                gap: 12px;
                font-size: 14px;
            }

            .sidebar {
                width: 220px;
            }

            .main-content {
                padding: 35px 25px;
            }

        }


        @media (max-width: 650px) {

            .admin-header {
                height: auto;
                min-height: 80px;
                padding: 12px 15px;
            }

            .admin-logo img {
                width: 130px;
            }

            .admin-user span {
                display: none;
            }

            .admin-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 15px;
                border-right: none;
                border-bottom: 1px solid rgba(184, 134, 44, 0.15);
            }

            .sidebar-title {
                margin: 5px 10px 15px;
            }

            .sidebar a {
                display: inline-block;
                margin: 3px;
                padding: 10px 14px;
                font-size: 14px;
            }

            .main-content {
                padding: 30px 15px;
            }

            .page-header h1 {
                font-size: 30px;
            }

        }

    </style>

</head>

<body>


<!-- ==============================
     ADMIN HEADER
================================ -->

<header class="admin-header">

    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="NAVA Fade Studio">
    </div>

    <div class="admin-user">

        <span>
            Welcome, <?= htmlspecialchars($_SESSION["admin_username"] ?? "Admin") ?>
        </span>

        <a href="../logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</header>


<!-- ==============================
     ADMIN LAYOUT
================================ -->

<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-title">
            ADMIN PANEL
        </div>

        <a href="dashboard.php">
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

        <a href="orders.php" class="active">
            Orders
        </a>

        <a href="reviews.php">
            Reviews
        </a>

        <a href="settings.php">
            Settings
        </a>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <div class="page-header">

            <h1>Orders</h1>

            <p>
                View and manage customer product orders.
            </p>

        </div>


        <?php if ($message): ?>

            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <div class="table-container">

            <?php if (empty($orders)): ?>

                <div class="empty-orders">
                    No orders have been placed yet.
                </div>

            <?php else: ?>

                <table>

                    <thead>

                        <tr>

                            <th>Order #</th>

                            <th>Customer</th>

                            <th>Products</th>

                            <th>Total</th>

                            <th>Status</th>

                            <th>Date</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($orders as $order): ?>

                            <tr>

                                <!-- ORDER NUMBER -->

                                <td>

                                    <div class="order-number">
                                        #<?= (int) $order["id"] ?>
                                    </div>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <div class="customer-name">
                                        <?= htmlspecialchars($order["full_name"]) ?>
                                    </div>

                                    <div class="customer-details">

                                        <?= htmlspecialchars($order["email"]) ?>

                                        <br>

                                        <?= htmlspecialchars($order["contact_number"]) ?>

                                    </div>

                                </td>


                                <!-- PRODUCTS -->

                                <td>

                                    <ul class="product-list">

                                        <?php foreach ($orderItems[$order["id"]] ?? [] as $item): ?>

                                            <li>

                                                <?= htmlspecialchars($item["product_name"]) ?>

                                                <span class="product-qty">
                                                    × <?= (int) $item["quantity"] ?>
                                                </span>

                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <div class="order-total">

                                        ₱<?= number_format(
                                            (float) $order["total_amount"],
                                            2
                                        ) ?>

                                    </div>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $statusClass = strtolower($order["status"]);

                                    ?>

                                    <span class="status status-<?= htmlspecialchars($statusClass) ?>">

                                        <?= htmlspecialchars($order["status"]) ?>

                                    </span>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= date(
                                        "M d, Y",
                                        strtotime($order["created_at"])
                                    ) ?>

                                    <br>

                                    <span style="color:#9fa7b8;font-size:13px;">

                                        <?= date(
                                            "h:i A",
                                            strtotime($order["created_at"])
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <form
                                        method="POST"
                                        action="orders.php"
                                        class="status-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= (int) $order["id"] ?>"
                                        >

                                        <select
                                            name="status"
                                            class="status-select"
                                        >

                                            <?php foreach ($allowedStatuses as $status): ?>

                                                <option
                                                    value="<?= htmlspecialchars($status) ?>"
                                                    <?= $order["status"] === $status ? "selected" : "" ?>
                                                >
                                                    <?= htmlspecialchars($status) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <button
                                            type="submit"
                                            name="update_status"
                                            class="update-btn"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </main>

</div>


</body>

</html>