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


/* =========================================
   GET LOGGED-IN CUSTOMER
========================================= */

$customer_id = $_SESSION["customer_id"];


$customer_query = $db->prepare("
    SELECT
        full_name,
        email,
        contact_number
    FROM customers
    WHERE id = :id
    LIMIT 1
");


$customer_query->execute([
    ":id" => $customer_id
]);


$customer_data = $customer_query->fetch(PDO::FETCH_ASSOC);


/* =========================================
   GET SERVICES FROM DATABASE
========================================= */

$stmt = $db->prepare("
    SELECT
        id,
        service_name,
        description,
        price,
        duration
    FROM services
    ORDER BY id ASC
");


$stmt->execute();


$services = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================
   MESSAGES
========================================= */

$success = "";
$error = "";


/* =========================================
   PRESELECT SERVICE FROM URL
   Example:
   book.php?service=8
========================================= */

$preselected_service = isset($_GET["service"])
    ? (int) $_GET["service"]
    : 0;


/* =========================================
   HANDLE BOOKING
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selected_services = $_POST["services"] ?? [];

    $appointment_date =
        $_POST["appointment_date"] ?? "";

    $appointment_time =
        $_POST["appointment_time"] ?? "";

    $notes =
        trim($_POST["notes"] ?? "");


    /* =========================================
       CLEAN SERVICE IDS
    ========================================= */

    if (is_array($selected_services)) {

        $selected_services = array_map(
            "intval",
            $selected_services
        );

        $selected_services = array_values(
            array_unique(
                array_filter(
                    $selected_services,
                    function ($id) {
                        return $id > 0;
                    }
                )
            )
        );

    } else {

        $selected_services = [];

    }


    /* =========================================
       VALIDATION
    ========================================= */

    if (
        empty($selected_services) ||
        empty($appointment_date) ||
        empty($appointment_time)
    ) {

        $error =
            "Please select at least one service, date, and time.";

    } elseif ($appointment_date < date("Y-m-d")) {

        $error =
            "Please select a valid appointment date.";

    } else {

        try {

            /* =========================================
               GET SELECTED SERVICES
            ========================================= */

            $placeholders = implode(
                ",",
                array_fill(
                    0,
                    count($selected_services),
                    "?"
                )
            );


            $service_query = $db->prepare("
                SELECT
                    id,
                    service_name,
                    price,
                    duration
                FROM services
                WHERE id IN ($placeholders)
                ORDER BY id ASC
            ");


            $service_query->execute(
                $selected_services
            );


            $selected_data =
                $service_query->fetchAll(
                    PDO::FETCH_ASSOC
                );


            if (empty($selected_data)) {

                throw new Exception(
                    "No valid services were selected."
                );

            }


            /* =========================================
               COMBINE SERVICE NAMES
            ========================================= */

            $selected_service_names = [];


            foreach ($selected_data as $service) {

                $selected_service_names[] =
                    $service["service_name"];

            }


            $service_names =
                implode(
                    ", ",
                    $selected_service_names
                );


            /* =========================================
               INSERT APPOINTMENT
            ========================================= */

            $booking_query = $db->prepare("
                INSERT INTO appointments (
                    customer_id,
                    service,
                    appointment_date,
                    appointment_time,
                    notes,
                    status
                )
                VALUES (
                    :customer_id,
                    :service,
                    :appointment_date,
                    :appointment_time,
                    :notes,
                    'Pending'
                )
            ");


            $booking_query->execute([

                ":customer_id" =>
                    $customer_id,

                ":service" =>
                    $service_names,

                ":appointment_date" =>
                    $appointment_date,

                ":appointment_time" =>
                    $appointment_time,

                ":notes" =>
                    $notes

            ]);


            $success =
                "Your appointment has been booked successfully!";

        } catch (Exception $e) {

            $error =
                "Unable to submit your booking. Please try again.";

        }

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
        Book an Appointment | NAVA Fade Studio
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
        ========================================= */

        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;

        }


        /* =========================================
           BODY
        ========================================= */

        body {

            min-height: 100vh;

            font-family:
                Bahnschrift,
                "Myriad Pro",
                Arial,
                sans-serif;

            color: #ffffff;

            background-color: #0e1423;

            background-image:

                linear-gradient(
                    rgba(7, 14, 29, 0.72),
                    rgba(7, 14, 29, 0.72)
                ),

                url("assets/images/pattern3.png");

            background-size: cover;

            background-position: center;

            background-repeat: repeat;

            background-attachment: fixed;

        }


        /* =========================================
           PAGE
        ========================================= */

        .booking-page {

            width: 100%;

            min-height: 100vh;

            padding: 50px 20px 70px;

            display: flex;

            justify-content: center;

        }


        /* =========================================
           MAIN CONTAINER
        ========================================= */

        .booking-container {

            width: 100%;

            max-width: 820px;

            background: #111827;

            border: 2px solid #b8862c;

            border-radius: 28px;

            padding: 45px 55px;

            box-shadow:
                0 20px 60px
                rgba(0, 0, 0, 0.45);

        }


        /* =========================================
           HEADER
        ========================================= */

        .booking-header {

            text-align: center;

            margin-bottom: 38px;

        }


        /* =========================================
           LOGO
        ========================================= */

        .booking-logo {

            width: 300px;

            max-width: 85%;

            height: auto;

            display: block;

            margin: 0 auto 22px;

        }


        /* =========================================
           TITLE
        ========================================= */

        .booking-header h1 {

            color: #ffffff;

            font-size: 32px;

            font-weight: 800;

            letter-spacing: 0.5px;

            margin-bottom: 10px;

        }


        .booking-header h1 span {

            color: #b8862c;

        }


        /* =========================================
           HEADER SUBTITLE
        ========================================= */

        .booking-header p {

            color: #cbd5e1;

            font-size: 16px;

            line-height: 1.6;

        }


        .booking-header p strong {

            color: #d4a33a;

        }


        /* =========================================
           MESSAGES
        ========================================= */

        .success-message,
        .error-message {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 15px 18px;

            margin-bottom: 25px;

            border-radius: 12px;

            font-size: 15px;

            line-height: 1.5;

        }


        .success-message {

            background: rgba(31, 107, 69, 0.25);

            border: 1px solid #2f9e67;

            color: #b8f3d3;

        }


        .error-message {

            background: rgba(139, 32, 32, 0.25);

            border: 1px solid #c84a4a;

            color: #ffbcbc;

        }


        .success-message i {

            color: #53d88b;

            font-size: 19px;

        }


        .error-message i {

            color: #ff7777;

            font-size: 19px;

        }


        /* =========================================
           CUSTOMER INFORMATION
        ========================================= */

        .customer-booking-info {

            padding: 22px 24px;

            margin-bottom: 32px;

            border-radius: 16px;

            background: #0b1220;

            border: 1px solid
                rgba(184, 134, 44, 0.35);

        }


        .customer-booking-info h3 {

            color: #d4a33a;

            font-size: 18px;

            margin-bottom: 17px;

        }


        .customer-details {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

        }


        .customer-detail {

            display: flex;

            align-items: flex-start;

            gap: 10px;

        }


        .customer-detail i {

            color: #b8862c;

            font-size: 15px;

            margin-top: 4px;

        }


        .customer-detail-content {

            min-width: 0;

        }


        .customer-detail-label {

            display: block;

            color: #8892a5;

            font-size: 12px;

            margin-bottom: 4px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

        }


        .customer-detail-value {

            color: #ffffff;

            font-size: 14px;

            font-weight: 600;

            word-break: break-word;

        }


        /* =========================================
           SECTION TITLE
        ========================================= */

        .section-title {

            margin-bottom: 8px;

            color: #ffffff;

            font-size: 21px;

            font-weight: 700;

        }


        .section-title i {

            color: #b8862c;

            margin-right: 8px;

        }


        .section-note {

            margin-bottom: 18px;

            color: #9ca8bb;

            font-size: 14px;

        }


        /* =========================================
           SERVICE GRID
        ========================================= */

        .booking-services {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;

        }


        /* =========================================
           SERVICE CARD
        ========================================= */

        .booking-service-card {

            position: relative;

            display: flex;

            align-items: center;

            justify-content: space-between;

            min-height: 105px;

            padding: 20px;

            background: #0b1220;

            border: 1px solid #293244;

            border-radius: 15px;

            cursor: pointer;

            transition:
                border-color 0.25s ease,
                background-color 0.25s ease,
                transform 0.25s ease,
                box-shadow 0.25s ease;

        }


        .booking-service-card:hover {

            border-color: #b8862c;

            background: #111a2b;

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, 0.25);

        }


        .booking-service-card.selected {

            border-color: #b8862c;

            background:
                linear-gradient(
                    135deg,
                    rgba(184, 134, 44, 0.13),
                    rgba(11, 18, 32, 0.95)
                );

            box-shadow:
                0 0 0 1px
                rgba(184, 134, 44, 0.25);

        }


        /* =========================================
           HIDDEN CHECKBOX
        ========================================= */

        .service-checkbox {

            position: absolute;

            opacity: 0;

            pointer-events: none;

        }


        /* =========================================
           SERVICE INFO
        ========================================= */

        .booking-service-info {

            padding-right: 15px;

        }


        .booking-service-info h3 {

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;

            margin-bottom: 9px;

        }


        .service-meta {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

        }


        .service-price {

            color: #d4a33a;

            font-size: 17px;

            font-weight: 800;

        }


        .service-duration {

            color: #aeb8c8;

            font-size: 13px;

        }


        .service-duration::before {

            content: "•";

            color: #6d7685;

            margin-right: 8px;

        }


        /* =========================================
           CUSTOM CHECK
        ========================================= */

        .service-check {

            width: 25px;

            height: 25px;

            min-width: 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 2px solid #667085;

            border-radius: 7px;

            color: transparent;

            font-size: 13px;

            transition:
                all 0.2s ease;

        }


        .booking-service-card.selected
        .service-check {

            border-color: #b8862c;

            background: #b8862c;

            color: #0e1423;

        }


        /* =========================================
           SELECTED SERVICES SUMMARY
        ========================================= */

        .selected-services-summary {

            margin-top: 28px;

            padding: 22px 24px;

            background: #0b1220;

            border: 1px solid
                rgba(184, 134, 44, 0.35);

            border-radius: 16px;

        }


        .summary-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 15px;

        }


        .summary-header h3 {

            color: #ffffff;

            font-size: 18px;

        }


        .summary-count {

            padding: 5px 10px;

            border-radius: 20px;

            background: rgba(
                184,
                134,
                44,
                0.15
            );

            color: #d4a33a;

            font-size: 12px;

            font-weight: 700;

        }


        .selected-services-list {

            display: flex;

            flex-direction: column;

            gap: 9px;

        }


        .selected-service-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 10px 0;

            border-bottom: 1px solid
                rgba(255, 255, 255, 0.08);

            color: #dce2eb;

            font-size: 14px;

        }


        .selected-service-item:last-child {

            border-bottom: none;

        }


        .selected-service-item strong {

            color: #d4a33a;

        }


        .no-service {

            color: #7f8a9d;

            font-size: 14px;

            text-align: center;

            padding: 8px 0;

        }


        /* =========================================
           TOTAL
        ========================================= */

        .booking-total {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 17px;

            padding-top: 17px;

            border-top: 1px solid
                rgba(184, 134, 44, 0.35);

        }


        .booking-total span {

            color: #cbd5e1;

            font-size: 15px;

            font-weight: 600;

        }


        .booking-total strong {

            color: #d4a33a;

            font-size: 25px;

            font-weight: 800;

        }


        /* =========================================
           DATE / TIME
        ========================================= */

        .booking-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;

            margin-top: 28px;

        }


        /* =========================================
           FORM GROUP
        ========================================= */

        .form-group {

            margin-top: 26px;

        }


        .booking-row .form-group {

            margin-top: 0;

        }


        /* =========================================
           LABEL
        ========================================= */

        .form-label {

            display: block;

            margin-bottom: 9px;

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;

        }


        .form-label i {

            color: #b8862c;

            margin-right: 6px;

        }


        /* =========================================
           INPUTS
        ========================================= */

        .form-input,
        .form-textarea {

            width: 100%;

            border: 1px solid #3c4658;

            border-radius: 11px;

            background: #ffffff;

            color: #111827;

            font-family: inherit;

            font-size: 15px;

            outline: none;

            transition:
                border-color 0.25s ease,
                box-shadow 0.25s ease;

        }


        .form-input {

            padding: 14px 15px;

        }


        .form-textarea {

            padding: 14px 15px;

            min-height: 115px;

            resize: vertical;

        }


        .form-input:focus,
        .form-textarea:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 3px
                rgba(184, 134, 44, 0.15);

        }


        /* =========================================
           BOOK BUTTON
        ========================================= */

        .booking-btn {

            width: 100%;

            margin-top: 30px;

            padding: 16px 20px;

            border: none;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #b8862c,
                    #d4a33a
                );

            color: #0e1423;

            font-family: inherit;

            font-size: 17px;

            font-weight: 800;

            letter-spacing: 0.4px;

            cursor: pointer;

            box-shadow:
                0 8px 20px
                rgba(184, 134, 44, 0.20);

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;

        }


        .booking-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(184, 134, 44, 0.30);

        }


        .booking-btn i {

            margin-right: 8px;

        }


        /* =========================================
           BACK LINK
        ========================================= */

        .booking-back {

            margin-top: 24px;

            text-align: center;

        }


        .booking-back a {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #b8862c;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            transition: color 0.2s ease;

        }


        .booking-back a:hover {

            color: #d4a33a;

            text-decoration: underline;

        }


        /* =========================================
           SMALL INFORMATION
        ========================================= */

        .booking-footer-note {

            margin-top: 18px;

            text-align: center;

            color: #717d90;

            font-size: 12px;

            line-height: 1.5;

        }


        /* =========================================
           TABLET
        ========================================= */

        @media (max-width: 750px) {

            .booking-container {

                padding: 38px 30px;

            }


            .customer-details {

                grid-template-columns: 1fr;

                gap: 12px;

            }


            .booking-services {

                grid-template-columns: 1fr;

            }

        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 600px) {

            .booking-page {

                padding: 25px 12px 45px;

            }


            .booking-container {

                padding: 30px 20px;

                border-radius: 22px;

            }


            .booking-logo {

                width: 240px;

                margin-bottom: 18px;

            }


            .booking-header {

                margin-bottom: 30px;

            }


            .booking-header h1 {

                font-size: 26px;

            }


            .booking-header p {

                font-size: 14px;

            }


            .customer-booking-info {

                padding: 18px;

            }


            .booking-row {

                grid-template-columns: 1fr;

                gap: 20px;

            }


            .booking-service-card {

                min-height: 95px;

                padding: 17px;

            }


            .booking-service-info h3 {

                font-size: 16px;

            }


            .service-price {

                font-size: 16px;

            }


            .selected-services-summary {

                padding: 18px;

            }


            .booking-total strong {

                font-size: 22px;

            }

        }


        /* =========================================
           SMALL MOBILE
        ========================================= */

        @media (max-width: 400px) {

            .booking-container {

                padding: 25px 16px;

            }


            .booking-logo {

                width: 210px;

            }


            .booking-header h1 {

                font-size: 23px;

            }


            .customer-detail-value {

                font-size: 13px;

            }


            .booking-service-card {

                padding: 15px;

            }


            .service-duration {

                font-size: 12px;

            }

        }

    </style>

</head>


<body>


<section class="booking-page">


    <div class="booking-container">


        <!-- =========================================
             BOOKING HEADER
        ========================================== -->

        <div class="booking-header">


            <img
                src="assets/images/logo.png"
                alt="NAVA Fade Studio Logo"
                class="booking-logo"
            >


            <h1>
                BOOK AN <span>APPOINTMENT</span>
            </h1>


            <p>

                Welcome,
                <strong>
                    <?= htmlspecialchars(
                        $customer_data["full_name"] ?? "Customer"
                    ) ?>
                </strong>!

                Reserve your time with us.

            </p>


        </div>


        <!-- =========================================
             SUCCESS MESSAGE
        ========================================== -->

        <?php if (!empty($success)): ?>

            <div class="success-message">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?= htmlspecialchars($success) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =========================================
             ERROR MESSAGE
        ========================================== -->

        <?php if (!empty($error)): ?>

            <div class="error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= htmlspecialchars($error) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =========================================
             CUSTOMER INFORMATION
        ========================================== -->

        <div class="customer-booking-info">


            <h3>

                <i class="fa-solid fa-user"></i>

                Your Information

            </h3>


            <div class="customer-details">


                <!-- NAME -->

                <div class="customer-detail">

                    <i class="fa-solid fa-user"></i>

                    <div class="customer-detail-content">

                        <span class="customer-detail-label">
                            Name
                        </span>

                        <span class="customer-detail-value">

                            <?= htmlspecialchars(
                                $customer_data["full_name"] ?? ""
                            ) ?>

                        </span>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="customer-detail">

                    <i class="fa-solid fa-envelope"></i>

                    <div class="customer-detail-content">

                        <span class="customer-detail-label">
                            Email
                        </span>

                        <span class="customer-detail-value">

                            <?= htmlspecialchars(
                                $customer_data["email"] ?? ""
                            ) ?>

                        </span>

                    </div>

                </div>


                <!-- CONTACT -->

                <div class="customer-detail">

                    <i class="fa-solid fa-phone"></i>

                    <div class="customer-detail-content">

                        <span class="customer-detail-label">
                            Contact
                        </span>

                        <span class="customer-detail-value">

                            <?= htmlspecialchars(
                                $customer_data["contact_number"] ?? ""
                            ) ?>

                        </span>

                    </div>

                </div>


            </div>


        </div>


        <!-- =========================================
             BOOKING FORM
        ========================================== -->

        <form method="POST" action="">


            <!-- =====================================
                 SELECT SERVICES
            ====================================== -->

            <div class="form-group">


                <h2 class="section-title">

                    <i class="fa-solid fa-scissors"></i>

                    Select Service(s)

                </h2>


                <p class="section-note">

                    Choose one or more services for your appointment.

                </p>


                <div class="booking-services">


                    <?php foreach ($services as $service_item): ?>


                        <?php

                        $service_id =
                            (int) $service_item["id"];

                        $is_selected =
                            $preselected_service === $service_id;

                        ?>


                        <label
                            class="booking-service-card <?= $is_selected ? 'selected' : '' ?>"
                        >


                            <!-- CHECKBOX -->

                            <input
                                type="checkbox"
                                name="services[]"
                                value="<?= $service_id ?>"
                                class="service-checkbox"
                                data-name="<?= htmlspecialchars(
                                    $service_item["service_name"],
                                    ENT_QUOTES
                                ) ?>"
                                data-price="<?= htmlspecialchars(
                                    $service_item["price"]
                                ) ?>"
                                <?= $is_selected ? "checked" : "" ?>
                            >


                            <!-- SERVICE INFORMATION -->

                            <div class="booking-service-info">


                                <h3>

                                    <?= htmlspecialchars(
                                        $service_item["service_name"]
                                    ) ?>

                                </h3>


                                <div class="service-meta">


                                    <span class="service-price">

                                        ₱<?= number_format(
                                            $service_item["price"],
                                            0
                                        ) ?>

                                    </span>


                                    <span class="service-duration">

                                        <?= htmlspecialchars(
                                            $service_item["duration"]
                                        ) ?>

                                    </span>


                                </div>


                            </div>


                            <!-- CHECK ICON -->

                            <div class="service-check">

                                <i class="fa-solid fa-check"></i>

                            </div>


                        </label>


                    <?php endforeach; ?>


                </div>


            </div>


            <!-- =========================================
                 SELECTED SERVICES SUMMARY
            ========================================== -->

            <div class="selected-services-summary">


                <div class="summary-header">

                    <h3>

                        <i class="fa-solid fa-receipt"></i>

                        Selected Services

                    </h3>


                    <span
                        class="summary-count"
                        id="serviceCount"
                    >
                        0 services
                    </span>

                </div>


                <div
                    id="selectedServices"
                    class="selected-services-list"
                >

                    <p class="no-service">

                        No service selected yet.

                    </p>

                </div>


                <div class="booking-total">

                    <span>
                        Total
                    </span>

                    <strong id="totalPrice">
                        ₱0
                    </strong>

                </div>


            </div>


            <!-- =========================================
                 DATE AND TIME
            ========================================== -->

            <div class="booking-row">


                <!-- DATE -->

                <div class="form-group">


                    <label
                        for="appointment_date"
                        class="form-label"
                    >

                        <i class="fa-regular fa-calendar"></i>

                        Appointment Date

                    </label>


                    <input
                        type="date"
                        id="appointment_date"
                        name="appointment_date"
                        class="form-input"
                        min="<?= date('Y-m-d') ?>"
                        required
                    >


                </div>


                <!-- TIME -->

                <div class="form-group">


                    <label
                        for="appointment_time"
                        class="form-label"
                    >

                        <i class="fa-regular fa-clock"></i>

                        Appointment Time

                    </label>


                    <input
                        type="time"
                        id="appointment_time"
                        name="appointment_time"
                        class="form-input"
                        required
                    >


                </div>


            </div>


            <!-- =========================================
                 NOTES
            ========================================== -->

            <div class="form-group">


                <label
                    for="notes"
                    class="form-label"
                >

                    <i class="fa-regular fa-note-sticky"></i>

                    Additional Notes

                </label>


                <textarea
                    id="notes"
                    name="notes"
                    class="form-textarea"
                    rows="4"
                    placeholder="Tell us anything we should know about your appointment... (Optional)"
                ></textarea>


            </div>


            <!-- =========================================
                 BOOK BUTTON
            ========================================== -->

            <button
                type="submit"
                class="booking-btn"
            >

                <i class="fa-solid fa-calendar-check"></i>

                BOOK APPOINTMENT

            </button>


        </form>


        <!-- =========================================
             BACK BUTTON
        ========================================== -->

        <div class="booking-back">

            <a href="index.php">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Home

            </a>

        </div>


        <p class="booking-footer-note">

            Your appointment will be reviewed and confirmed
            by NAVA Fade Studio.

        </p>


    </div>


</section>



<!-- =========================================
     SERVICE SELECTION JAVASCRIPT
========================================== -->

<script>

    const serviceCheckboxes =
        document.querySelectorAll(
            ".service-checkbox"
        );


    const serviceCards =
        document.querySelectorAll(
            ".booking-service-card"
        );


    const selectedServices =
        document.getElementById(
            "selectedServices"
        );


    const totalPrice =
        document.getElementById(
            "totalPrice"
        );


    const serviceCount =
        document.getElementById(
            "serviceCount"
        );


    /* =========================================
       UPDATE SELECTED SERVICES
    ========================================== */

    function updateSelectedServices() {

        let total = 0;

        let selectedHTML = "";

        let selectedCount = 0;


        serviceCheckboxes.forEach(
            function (checkbox) {


                const card =
                    checkbox.closest(
                        ".booking-service-card"
                    );


                /* =================================
                   UPDATE CARD SELECTED STATE
                ================================= */

                if (checkbox.checked) {

                    card.classList.add(
                        "selected"
                    );

                } else {

                    card.classList.remove(
                        "selected"
                    );

                }


                /* =================================
                   ADD TO SUMMARY
                ================================= */

                if (checkbox.checked) {

                    const name =
                        checkbox.dataset.name;


                    const price =
                        parseFloat(
                            checkbox.dataset.price
                        );


                    total += price;

                    selectedCount++;


                    selectedHTML += `

                        <div class="selected-service-item">

                            <span>
                                ${name}
                            </span>

                            <strong>
                                ₱${price.toFixed(0)}
                            </strong>

                        </div>

                    `;

                }

            }
        );


        /* =========================================
           NO SERVICE SELECTED
        ========================================== */

        if (selectedCount === 0) {

            selectedServices.innerHTML = `

                <p class="no-service">

                    No service selected yet.

                </p>

            `;

            serviceCount.textContent =
                "0 services";

        }


        /* =========================================
           SERVICES SELECTED
        ========================================== */

        else {

            selectedServices.innerHTML =
                selectedHTML;


            serviceCount.textContent =
                selectedCount +
                (
                    selectedCount === 1
                        ? " service"
                        : " services"
                );

        }


        /* =========================================
           UPDATE TOTAL
        ========================================== */

        totalPrice.textContent =
            "₱" + total.toFixed(0);

    }


    /* =========================================
       CHECKBOX EVENTS
    ========================================== */

    serviceCheckboxes.forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                updateSelectedServices
            );

        }
    );


    /* =========================================
       CARD CLICK SUPPORT
    ========================================== */

    serviceCards.forEach(
        function (card) {

            card.addEventListener(
                "click",
                function (event) {

                    /*
                     * The checkbox itself is hidden,
                     * so clicking anywhere on the
                     * label naturally toggles it.
                     */

                    updateSelectedServices();

                }
            );

        }
    );


    /* =========================================
       INITIAL LOAD
    ========================================== */

    updateSelectedServices();

</script>


</body>

</html>