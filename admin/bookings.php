<?php

session_start();

/* =========================================
   ADMIN LOGIN CHECK
========================================= */

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}


/* =========================================
   DATABASE
========================================= */

require_once "../config/Database.php";

$database = new Database();
$db = $database->connect();

$success = "";
$error = "";


/* =========================================
   UPDATE BOOKING STATUS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $appointment_id = $_POST["appointment_id"] ?? "";
    $status = $_POST["status"] ?? "";

    $allowed_statuses = [
        "Pending",
        "Confirmed",
        "Completed",
        "Cancelled"
    ];

    if (
        empty($appointment_id) ||
        !in_array($status, $allowed_statuses)
    ) {

        $error = "Invalid booking status.";

    } else {

        try {

            $update = $db->prepare("
                UPDATE appointments
                SET status = :status
                WHERE id = :id
            ");

            $update->execute([
                ":status" => $status,
                ":id" => $appointment_id
            ]);

            header("Location: bookings.php?updated=1");
            exit();

        } catch (PDOException $e) {

            $error = "Unable to update booking status.";
        }
    }
}


/* =========================================
   SUCCESS MESSAGE
========================================= */

if (isset($_GET["updated"])) {
    $success = "Booking status updated successfully!";
}


/* =========================================
   GET ALL BOOKINGS
========================================= */

try {

    $query = $db->prepare("
        SELECT
            appointments.id,
            appointments.service,
            appointments.appointment_date,
            appointments.appointment_time,
            appointments.notes,
            appointments.status,
            appointments.created_at,

            customers.full_name,
            customers.email,
            customers.contact_number,

            services.price AS service_price

        FROM appointments

        INNER JOIN customers
            ON appointments.customer_id = customers.id

        LEFT JOIN services
            ON appointments.service = services.service_name

        ORDER BY
            appointments.appointment_date DESC,
            appointments.appointment_time DESC
    ");

    $query->execute();

    $bookings = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $bookings = [];

    $error = "Unable to load bookings.";
}


/* =========================================
   BOOKING COUNTS
========================================= */

$totalBookings = count($bookings);

$pendingCount = 0;
$confirmedCount = 0;
$completedCount = 0;
$cancelledCount = 0;

foreach ($bookings as $booking) {

    switch ($booking["status"]) {

        case "Pending":
            $pendingCount++;
            break;

        case "Confirmed":
            $confirmedCount++;
            break;

        case "Completed":
            $completedCount++;
            break;

        case "Cancelled":
            $cancelledCount++;
            break;
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

    <title>
        Bookings | NAVA Fade Studio Admin
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            font-family:
                Bahnschrift,
                "Myriad Pro",
                sans-serif;

            background:
            linear-gradient(
                    rgba(8, 12, 22, 0.92),
                    rgba(8, 12, 22, 0.96)
                ),
                url("../assets/images/pattern3.png");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;

            color: #ffffff;
        }


        /* =========================================
           HEADER
        ========================================= */

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

            color: #ffffff;

            font-size: 15px;
        }


        .logout-btn {

            padding: 10px 20px;

            background: transparent;

            color: #b8862c;

            border: 2px solid #b8862c;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

            transition: 0.3s ease;
        }


        .logout-btn:hover {

            background: #b8862c;

            color: #0e1423;
        }


        /* =========================================
           LAYOUT
        ========================================= */

        .dashboard {

            display: flex;

            min-height:
                calc(100vh - 95px);
        }


        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {

            width: 250px;

            padding: 35px 20px;

            background:
                rgba(14, 20, 35, 0.95);

            border-right:
                1px solid
                rgba(184, 134, 44, 0.5);

            flex-shrink: 0;
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

            min-width: 0;
        }


        .welcome {

            margin-bottom: 30px;
        }


        .welcome h2 {

            margin-bottom: 8px;

            font-size: 36px;

            color: #ffffff;
        }


        .welcome p {

            color: #aaa;

            font-size: 17px;
        }


        /* =========================================
           ALERTS
        ========================================= */

        .admin-success,
        .admin-error {

            padding: 15px 20px;

            margin-bottom: 25px;

            border-radius: 12px;

            font-weight: bold;
        }


        .admin-success {

            background:
                rgba(40, 167, 69, 0.15);

            border:
                1px solid #4caf50;

            color: #7ee787;
        }


        .admin-error {

            background:
                rgba(220, 53, 69, 0.15);

            border:
                1px solid #dc3545;

            color: #ff8a8a;
        }


        /* =========================================
           SUMMARY CARDS
        ========================================= */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 35px;
        }


        .summary-card {

            padding: 25px;

            background: #0e1423;

            border: 1px solid #555;

            border-radius: 18px;

            transition: 0.3s ease;
        }


        .summary-card:hover {

            transform: translateY(-4px);

            border-color: #b8862c;
        }


        .summary-card h3 {

            color: #aaa;

            font-size: 13px;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 10px;
        }


        .summary-number {

            font-size: 36px;

            font-weight: bold;

            color: #b8862c;
        }


        .summary-card p {

            margin-top: 5px;

            color: #777;

            font-size: 13px;
        }


        /* =========================================
           TOOLBAR
        ========================================= */

        .booking-toolbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            flex-wrap: wrap;
        }


        .toolbar-title {

            font-size: 24px;

            color: #ffffff;
        }


        .toolbar-controls {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;
        }


        .search-box,
        .filter-select {

            height: 44px;

            padding: 0 14px;

            background: #0e1423;

            color: #ffffff;

            border: 1px solid #555;

            border-radius: 9px;

            outline: none;

            font-family: inherit;
        }


        .search-box {

            width: 230px;
        }


        .filter-select {

            min-width: 150px;
        }


        .search-box:focus,
        .filter-select:focus {

            border-color: #b8862c;
        }


        /* =========================================
           TABLE
        ========================================= */

        .bookings-table-container {

            width: 100%;

            overflow-x: auto;

            background: #0e1423;

            border:
                1px solid
                rgba(184, 134, 44, 0.45);

            border-radius: 18px;

            box-shadow:
                0 15px 35px
                rgba(0, 0, 0, 0.25);
        }


        .bookings-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1050px;
        }


        .bookings-table th {

            padding: 18px;

            text-align: left;

            background: #151d31;

            color: #b8862c;

            font-size: 13px;

            text-transform: uppercase;

            letter-spacing: 1px;

            border-bottom:
                1px solid #333;
        }


        .bookings-table td {

            padding: 20px 18px;

            border-bottom:
                1px solid #292d38;

            vertical-align: middle;

            color: #eeeeee;

            font-size: 14px;
        }


        .bookings-table tbody tr {

            transition: 0.2s ease;
        }


        .bookings-table tbody tr:hover {

            background:
                rgba(184, 134, 44, 0.06);
        }


        .bookings-table tbody tr:last-child td {

            border-bottom: none;
        }


        /* =========================================
           CUSTOMER
        ========================================= */

        .customer-name {

            display: block;

            color: #ffffff;

            font-size: 15px;

            margin-bottom: 6px;
        }


        .customer-contact {

            display: block;

            color: #888;

            font-size: 12px;

            margin-top: 3px;

            word-break: break-word;
        }


        /* =========================================
           SERVICE
        ========================================= */

        .service-name {

            color: #ffffff;

            font-weight: bold;
        }


        .service-price {

            display: block;

            margin-top: 5px;

            color: #b8862c;

            font-size: 13px;

            font-weight: bold;
        }


        /* =========================================
           DATE / TIME
        ========================================= */

        .booking-date {

            color: #ffffff;

            font-weight: bold;
        }


        .booking-time {

            display: block;

            margin-top: 5px;

            color: #b8862c;

            font-size: 13px;
        }


        /* =========================================
           NOTES
        ========================================= */

        .booking-notes {

            max-width: 200px;

            color: #aaa;

            line-height: 1.5;

            word-break: break-word;
        }


        .no-notes {

            color: #666;

            font-style: italic;
        }


        /* =========================================
           STATUS BADGES
        ========================================= */

        .booking-status {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            white-space: nowrap;
        }


        .status-pending {

            background:
                rgba(255, 193, 7, 0.15);

            color: #ffc107;

            border:
                1px solid
                rgba(255, 193, 7, 0.4);
        }


        .status-confirmed {

            background:
                rgba(33, 150, 243, 0.15);

            color: #64b5f6;

            border:
                1px solid
                rgba(33, 150, 243, 0.4);
        }


        .status-completed {

            background:
                rgba(76, 175, 80, 0.15);

            color: #81c784;

            border:
                1px solid
                rgba(76, 175, 80, 0.4);
        }


        .status-cancelled {

            background:
                rgba(244, 67, 54, 0.15);

            color: #ef9a9a;

            border:
                1px solid
                rgba(244, 67, 54, 0.4);
        }


        /* =========================================
           UPDATE FORM
        ========================================= */

        .status-form {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .status-select {

            min-width: 120px;

            height: 38px;

            padding: 0 9px;

            background: #151d31;

            color: #ffffff;

            border:
                1px solid #555;

            border-radius: 7px;

            outline: none;

            font-family: inherit;

            cursor: pointer;
        }


        .status-select:focus {

            border-color: #b8862c;
        }


        .update-status-btn {

            height: 38px;

            padding: 0 14px;

            background: #b8862c;

            color: #0e1423;

            border: none;

            border-radius: 7px;

            font-weight: bold;

            font-family: inherit;

            cursor: pointer;

            transition: 0.3s ease;
        }


        .update-status-btn:hover {

            background: #d3a84c;

            transform: translateY(-1px);
        }


        /* =========================================
           NO BOOKINGS
        ========================================= */

        .no-bookings {

            padding: 70px 30px;

            text-align: center;

            background: #0e1423;

            border:
                1px solid #444;

            border-radius: 18px;
        }


        .no-bookings h2 {

            margin-bottom: 10px;

            color: #b8862c;

            font-size: 28px;
        }


        .no-bookings p {

            color: #888;

            font-size: 15px;
        }


        /* =========================================
           HIDDEN SEARCH ROWS
        ========================================= */

        .booking-row.hidden {

            display: none;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1200px) {

            .summary-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        @media (max-width: 900px) {

            .sidebar {

                width: 200px;
            }


            .main-content {

                padding: 30px;
            }


            .booking-toolbar {

                align-items: stretch;
            }


            .toolbar-controls {

                width: 100%;
            }


            .search-box {

                flex: 1;

                width: auto;
            }

        }


        @media (max-width: 650px) {

            .admin-header {

                height: 80px;

                padding: 0 20px;
            }


            .admin-logo img {

                width: 120px;
                height: 120px;
            }


            .admin-user span {

                display: none;
            }


            .logout-btn {

                padding: 8px 14px;

                font-size: 13px;
            }


            .dashboard {

                flex-direction: column;
            }


            .sidebar {

                width: 100%;

                display: flex;

                gap: 5px;

                overflow-x: auto;

                padding: 15px;
            }


            .sidebar-title {

                display: none;
            }


            .sidebar a {

                white-space: nowrap;

                margin: 0;

                padding: 12px 15px;
            }


            .main-content {

                padding: 25px 20px;
            }


            .welcome h2 {

                font-size: 28px;
            }


            .welcome p {

                font-size: 14px;
            }


            .summary-grid {

                grid-template-columns: 1fr;

                gap: 15px;
            }


            .summary-card {

                padding: 22px;
            }


            .toolbar-controls {

                flex-direction: column;
            }


            .search-box,
            .filter-select {

                width: 100%;
            }


            .toolbar-title {

                font-size: 21px;
            }

        }

    </style>

</head>


<body>

<?php include "../admin/navbar.php"; ?>

<!-- =========================================
     DASHBOARD
========================================= -->

<div class="admin-layout">

<?php include "sidebar.php"; ?>

<!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main class="main-content">


        <!-- WELCOME -->

        <div class="welcome">

            <h2>
                Booking Management
            </h2>

            <p>
                View customer appointments and manage their booking status.
            </p>

        </div>



        <!-- =====================================
             ALERTS
        ====================================== -->

        <?php if (!empty($success)): ?>

            <div class="admin-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($error)): ?>

            <div class="admin-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>



        <!-- =====================================
             SUMMARY
        ====================================== -->

        <div class="summary-grid">


            <!-- TOTAL -->

            <div class="summary-card">

                <h3>
                    Total Bookings
                </h3>

                <div class="summary-number">
                    <?= $totalBookings ?>
                </div>

                <p>
                    All appointments
                </p>

            </div>



            <!-- PENDING -->

            <div class="summary-card">

                <h3>
                    Pending
                </h3>

                <div class="summary-number">
                    <?= $pendingCount ?>
                </div>

                <p>
                    Waiting for confirmation
                </p>

            </div>



            <!-- CONFIRMED -->

            <div class="summary-card">

                <h3>
                    Confirmed
                </h3>

                <div class="summary-number">
                    <?= $confirmedCount ?>
                </div>

                <p>
                    Upcoming appointments
                </p>

            </div>



            <!-- COMPLETED -->

            <div class="summary-card">

                <h3>
                    Completed
                </h3>

                <div class="summary-number">
                    <?= $completedCount ?>
                </div>

                <p>
                    Successfully completed
                </p>

            </div>


        </div>



        <!-- =====================================
             TOOLBAR
        ====================================== -->

        <div class="booking-toolbar">


            <h2 class="toolbar-title">
                Customer Appointments
            </h2>


            <div class="toolbar-controls">

                <input
                    type="text"
                    id="bookingSearch"
                    class="search-box"
                    placeholder="Search customer or service..."
                >


                <select
                    id="statusFilter"
                    class="filter-select"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="Pending">
                        Pending
                    </option>

                    <option value="Confirmed">
                        Confirmed
                    </option>

                    <option value="Completed">
                        Completed
                    </option>

                    <option value="Cancelled">
                        Cancelled
                    </option>

                </select>

            </div>

        </div>



        <!-- =====================================
             BOOKINGS TABLE
        ====================================== -->

        <?php if (!empty($bookings)): ?>


            <div class="bookings-table-container">

                <table class="bookings-table">


                    <thead>

                        <tr>

                            <th>
                                Customer
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Date & Time
                            </th>

                            <th>
                                Notes
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Update
                            </th>

                        </tr>

                    </thead>


                    <tbody id="bookingsTableBody">


                        <?php foreach ($bookings as $booking): ?>


                            <tr
                                class="booking-row"
                                data-status="<?= htmlspecialchars(
                                    $booking["status"]
                                ) ?>"
                                data-search="<?= htmlspecialchars(
                                    strtolower(
                                        $booking["full_name"]
                                        . " "
                                        . $booking["service"]
                                        . " "
                                        . $booking["email"]
                                    )
                                ) ?>"
                            >


                                <!-- CUSTOMER -->

                                <td>

                                    <span class="customer-name">

                                        <?= htmlspecialchars(
                                            $booking["full_name"]
                                        ) ?>

                                    </span>


                                    <span class="customer-contact">

                                        <?= htmlspecialchars(
                                            $booking["email"]
                                        ) ?>

                                    </span>


                                    <span class="customer-contact">

                                        <?= htmlspecialchars(
                                            $booking["contact_number"]
                                        ) ?>

                                    </span>

                                </td>



                                <!-- SERVICE -->

                                <td>

                                    <span class="service-name">

                                        <?= htmlspecialchars(
                                            $booking["service"]
                                        ) ?>

                                    </span>


                                    <?php if (
                                        $booking["service_price"] !== null
                                    ): ?>

                                        <span class="service-price">

                                            ₱<?= number_format(
                                                (float)
                                                $booking["service_price"],
                                                2
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- DATE / TIME -->

                                <td>

                                    <span class="booking-date">

                                        <?= date(
                                            "M d, Y",
                                            strtotime(
                                                $booking[
                                                    "appointment_date"
                                                ]
                                            )
                                        ) ?>

                                    </span>


                                    <span class="booking-time">

                                        <?= date(
                                            "g:i A",
                                            strtotime(
                                                $booking[
                                                    "appointment_time"
                                                ]
                                            )
                                        ) ?>

                                    </span>

                                </td>



                                <!-- NOTES -->

                                <td class="booking-notes">

                                    <?php if (
                                        !empty($booking["notes"])
                                    ): ?>

                                        <?= htmlspecialchars(
                                            $booking["notes"]
                                        ) ?>

                                    <?php else: ?>

                                        <span class="no-notes">
                                            No notes
                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="booking-status status-<?= strtolower(
                                            $booking["status"]
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $booking["status"]
                                        ) ?>
                                    </span>

                                </td>



                                <!-- UPDATE -->

                                <td>

                                    <form
                                        method="POST"
                                        class="status-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="appointment_id"
                                            value="<?= $booking["id"] ?>"
                                        >


                                        <select
                                            name="status"
                                            class="status-select"
                                        >

                                            <option
                                                value="Pending"
                                                <?= $booking["status"] === "Pending"
                                                    ? "selected"
                                                    : "" ?>
                                            >
                                                Pending
                                            </option>


                                            <option
                                                value="Confirmed"
                                                <?= $booking["status"] === "Confirmed"
                                                    ? "selected"
                                                    : "" ?>
                                            >
                                                Confirmed
                                            </option>


                                            <option
                                                value="Completed"
                                                <?= $booking["status"] === "Completed"
                                                    ? "selected"
                                                    : "" ?>
                                            >
                                                Completed
                                            </option>


                                            <option
                                                value="Cancelled"
                                                <?= $booking["status"] === "Cancelled"
                                                    ? "selected"
                                                    : "" ?>
                                            >
                                                Cancelled
                                            </option>

                                        </select>


                                        <button
                                            type="submit"
                                            class="update-status-btn"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


            <!-- NO SEARCH RESULTS -->

            <div
                id="noSearchResults"
                class="no-bookings"
                style="display: none;"
            >

                <h2>
                    No Matching Bookings
                </h2>

                <p>
                    Try another customer, service, or status.
                </p>

            </div>


        <?php else: ?>


            <div class="no-bookings">

                <h2>
                    No Bookings Yet
                </h2>

                <p>
                    Customer appointments will appear here.
                </p>

            </div>


        <?php endif; ?>


    </main>


</div>



<!-- =========================================
     SEARCH + FILTER
========================================= -->

<script>

    const searchInput =
        document.getElementById("bookingSearch");

    const statusFilter =
        document.getElementById("statusFilter");

    const rows =
        document.querySelectorAll(".booking-row");

    const noSearchResults =
        document.getElementById("noSearchResults");


    function filterBookings() {

        const searchValue =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : "";


        const statusValue =
            statusFilter
                ? statusFilter.value
                : "all";


        let visibleRows = 0;


        rows.forEach(function(row) {

            const rowSearch =
                row.dataset.search || "";


            const rowStatus =
                row.dataset.status || "";


            const matchesSearch =
                rowSearch.includes(searchValue);


            const matchesStatus =
                statusValue === "all" ||
                rowStatus === statusValue;


            if (
                matchesSearch &&
                matchesStatus
            ) {

                row.classList.remove("hidden");

                visibleRows++;

            } else {

                row.classList.add("hidden");

            }

        });


        if (noSearchResults) {

            noSearchResults.style.display =
                visibleRows === 0
                    ? "block"
                    : "none";

        }

    }


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            filterBookings
        );

    }


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterBookings
        );

    }

</script>


</body>

</html>