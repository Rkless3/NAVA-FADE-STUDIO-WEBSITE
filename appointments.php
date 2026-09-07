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
   GET CUSTOMER APPOINTMENTS
========================================= */

try {

    $appointment_query = $db->prepare("
        SELECT
            id,
            service,
            appointment_date,
            appointment_time,
            notes,
            status,
            created_at
        FROM appointments
        WHERE customer_id = :customer_id
        ORDER BY
            appointment_date DESC,
            appointment_time DESC
    ");

    $appointment_query->execute([
        ":customer_id" => $customer_id
    ]);

    $appointments =
        $appointment_query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $appointments = [];

    $error =
        "Unable to load your appointments.";

}


/* =========================================
   HELPER FUNCTIONS
========================================= */

function getAppointmentStatusClass(string $status): string
{
    return strtolower(
        str_replace(" ", "-", trim($status))
    );
}


function getAppointmentStatusIcon(string $status): string
{
    switch ($status) {

        case "Pending":
            return "🕐";

        case "Confirmed":
            return "✓";

        case "Completed":
            return "✓";

        case "Cancelled":
            return "×";

        default:
            return "•";
    }
}


function getAppointmentStatusDescription(string $status): string
{
    switch ($status) {

        case "Pending":
            return "Your appointment request is waiting for confirmation.";

        case "Confirmed":
            return "Your appointment has been confirmed by NAVA Fade Studio.";

        case "Completed":
            return "Your appointment has been completed. Thank you for visiting us!";

        case "Cancelled":
            return "This appointment has been cancelled.";

        default:
            return "Your appointment status has been updated.";
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
        My Appointments | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* =========================================
           MY APPOINTMENTS PAGE
        ========================================= */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            background: #0e1423;
        }


        .appointments-page {

            min-height: 100vh;

            padding: 125px 20px 80px;

            background:
                linear-gradient(
                    rgba(14, 20, 35, 0.94),
                    rgba(14, 20, 35, 0.97)
                ),
                url("assets/images/pattern2.png");

            background-size: 300px;

            background-attachment: fixed;
        }


        .appointments-container {

            width: 100%;

            max-width: 1120px;

            margin: 0 auto;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .appointments-header {

            text-align: center;

            margin-bottom: 50px;
        }


        .appointments-eyebrow {

            display: inline-block;

            margin-bottom: 12px;

            color: #c8942f;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 4px;

            text-transform: uppercase;
        }


        .appointments-header h1 {

            margin: 0;

            color: #ffffff;

            font-size: clamp(38px, 5vw, 58px);

            line-height: 1.1;

            font-weight: 800;

            letter-spacing: -1px;
        }


        .appointments-header h1 span {

            color: #c8942f;
        }


        .appointments-header p {

            max-width: 600px;

            margin: 15px auto 0;

            color: #aeb7c8;

            font-size: 16px;

            line-height: 1.6;
        }


        /* =========================================
           ERROR
        ========================================= */

        .error-message {

            margin-bottom: 25px;

            padding: 15px 18px;

            border-radius: 10px;

            border: 1px solid rgba(255, 85, 85, 0.45);

            background: rgba(255, 85, 85, 0.08);

            color: #ff8585;

            text-align: center;

            font-size: 14px;

            font-weight: 600;
        }


        /* =========================================
           APPOINTMENT CARD
        ========================================= */

        .appointment-card {

            position: relative;

            padding: 30px;

            margin-bottom: 28px;

            overflow: hidden;

            border-radius: 18px;

            border: 1px solid rgba(200, 148, 47, 0.65);

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 45, 0.98),
                    rgba(10, 16, 29, 0.98)
                );

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.30);

            transition:
                transform 0.25s ease,
                border-color 0.25s ease,
                box-shadow 0.25s ease;
        }


        .appointment-card::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;

            height: 3px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    #c8942f,
                    transparent
                );
        }


        .appointment-card:hover {

            transform: translateY(-3px);

            border-color: #c8942f;

            box-shadow:
                0 22px 55px rgba(0, 0, 0, 0.40);
        }


        /* =========================================
           APPOINTMENT TOP
        ========================================= */

        .appointment-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 25px;

            padding-bottom: 24px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.08);
        }


        .appointment-heading {

            display: flex;

            flex-direction: column;

            gap: 7px;

            min-width: 0;
        }


        .appointment-label {

            color: #c8942f;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 2px;

            text-transform: uppercase;
        }


        .appointment-service {

            color: #ffffff;

            font-size: 23px;

            font-weight: 800;

            line-height: 1.3;
        }


        .appointment-id {

            color: #7f8b9e;

            font-size: 12px;
        }


        /* =========================================
           STATUS
        ========================================= */

        .appointment-status-wrapper {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            gap: 8px;

            flex-shrink: 0;
        }


        .appointment-status {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 9px 16px;

            border-radius: 30px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            border: 1px solid transparent;
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


        /* Pending */

        .status-pending {

            color: #ffc107;

            background: rgba(255, 193, 7, 0.10);

            border-color: rgba(255, 193, 7, 0.45);
        }


        .status-pending .status-icon {

            background: rgba(255, 193, 7, 0.20);
        }


        /* Confirmed */

        .status-confirmed {

            color: #36c7d8;

            background: rgba(54, 199, 216, 0.10);

            border-color: rgba(54, 199, 216, 0.45);
        }


        .status-confirmed .status-icon {

            background: rgba(54, 199, 216, 0.20);
        }


        /* Completed */

        .status-completed {

            color: #62d47b;

            background: rgba(98, 212, 123, 0.10);

            border-color: rgba(98, 212, 123, 0.45);
        }


        .status-completed .status-icon {

            background: rgba(98, 212, 123, 0.20);
        }


        /* Cancelled */

        .status-cancelled {

            color: #ff6565;

            background: rgba(255, 101, 101, 0.10);

            border-color: rgba(255, 101, 101, 0.45);
        }


        .status-cancelled .status-icon {

            background: rgba(255, 101, 101, 0.20);
        }


        .status-description {

            max-width: 300px;

            color: #7f8ba0;

            font-size: 12px;

            line-height: 1.4;

            text-align: right;
        }


        /* =========================================
           APPOINTMENT PROGRESS
        ========================================= */

        .appointment-progress {

            position: relative;

            display: flex;

            align-items: flex-start;

            margin: 30px 0 25px;
        }


        .progress-line {

            position: absolute;

            top: 15px;

            left: 16.66%;

            right: 16.66%;

            height: 2px;

            background: rgba(255, 255, 255, 0.10);

            z-index: 0;
        }


        .progress-line-active {

            position: absolute;

            top: 15px;

            left: 16.66%;

            height: 2px;

            background: #c8942f;

            z-index: 1;

            transition: width 0.4s ease;
        }


        .progress-step {

            position: relative;

            z-index: 2;

            width: 33.333%;

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

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

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


        /* =========================================
           CANCELLED MESSAGE
        ========================================= */

        .progress-cancelled {

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 28px 0;

            padding: 14px;

            border-radius: 10px;

            background: rgba(255, 101, 101, 0.07);

            border: 1px solid rgba(255, 101, 101, 0.20);

            color: #ff7777;

            font-size: 13px;

            font-weight: 600;
        }


        /* =========================================
           APPOINTMENT INFORMATION
        ========================================= */

        .appointment-information {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 15px;

            margin-top: 25px;
        }


        .info-box {

            display: flex;

            align-items: center;

            gap: 14px;

            padding: 18px;

            border-radius: 12px;

            background: rgba(255, 255, 255, 0.025);

            border: 1px solid rgba(255, 255, 255, 0.07);

            transition:
                background 0.25s ease,
                border-color 0.25s ease;
        }


        .info-box:hover {

            background: rgba(200, 148, 47, 0.05);

            border-color: rgba(200, 148, 47, 0.25);
        }


        .info-icon {

            width: 42px;

            height: 42px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: rgba(200, 148, 47, 0.10);

            border: 1px solid rgba(200, 148, 47, 0.20);

            font-size: 18px;
        }


        .info-content {

            display: flex;

            flex-direction: column;

            gap: 3px;
        }


        .info-label {

            color: #7f8a9d;

            font-size: 11px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .info-value {

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;
        }


        /* =========================================
           NOTES
        ========================================= */

        .appointment-notes {

            margin-top: 18px;

            padding: 18px;

            border-radius: 12px;

            background: rgba(200, 148, 47, 0.045);

            border-left: 3px solid #c8942f;
        }


        .notes-label {

            display: block;

            margin-bottom: 7px;

            color: #c8942f;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 1.5px;

            text-transform: uppercase;
        }


        .notes-text {

            color: #c1c8d4;

            font-size: 14px;

            line-height: 1.6;

            word-break: break-word;
        }


        /* =========================================
           NO APPOINTMENTS
        ========================================= */

        .no-appointments {

            padding: 75px 25px;

            text-align: center;

            border-radius: 18px;

            border: 1px solid rgba(200, 148, 47, 0.60);

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 45, 0.98),
                    rgba(10, 16, 29, 0.98)
                );

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.30);
        }


        .no-appointments-icon {

            width: 75px;

            height: 75px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: rgba(200, 148, 47, 0.10);

            border: 1px solid rgba(200, 148, 47, 0.30);

            font-size: 32px;
        }


        .no-appointments h2 {

            margin: 0 0 10px;

            color: #ffffff;

            font-size: 25px;
        }


        .no-appointments p {

            max-width: 500px;

            margin: 0 auto 25px;

            color: #8f9bad;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================================
           BOOK BUTTON
        ========================================= */

        .appointment-book-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 13px 25px;

            border-radius: 8px;

            background: #c8942f;

            color: #0e1423;

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;

            transition:
                background 0.25s ease,
                transform 0.25s ease;
        }


        .appointment-book-btn:hover {

            background: #e0aa3b;

            transform: translateY(-2px);
        }


        /* =========================================
           BACK TO HOME
        ========================================= */

        .appointments-back {

            margin-top: 35px;

            text-align: center;
        }


        .appointments-back a {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 18px;

            border-radius: 8px;

            border: 1px solid rgba(200, 148, 47, 0.45);

            background: rgba(14, 20, 35, 0.50);

            color: #c8942f;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition:
                background 0.25s ease,
                border-color 0.25s ease,
                transform 0.25s ease;
        }


        .appointments-back a:hover {

            background: rgba(200, 148, 47, 0.10);

            border-color: #c8942f;

            transform: translateY(-2px);
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 800px) {

            .appointments-page {

                padding: 110px 15px 60px;

                background-size: 240px;
            }


            .appointment-card {

                padding: 22px;

                border-radius: 15px;
            }


            .appointment-top {

                flex-direction: column;

                gap: 15px;
            }


            .appointment-status-wrapper {

                align-items: flex-start;
            }


            .status-description {

                max-width: 100%;

                text-align: left;
            }


            .appointment-information {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .appointments-page {

                padding: 100px 12px 50px;
            }


            .appointments-header {

                margin-bottom: 35px;
            }


            .appointments-header h1 {

                font-size: 38px;
            }


            .appointments-header p {

                font-size: 14px;
            }


            .appointment-card {

                padding: 18px;

                margin-bottom: 20px;
            }


            .appointment-service {

                font-size: 19px;
            }


            .appointment-id {

                font-size: 11px;
            }


            .appointment-status {

                font-size: 12px;

                padding: 8px 13px;
            }


            .appointment-progress {

                margin-top: 25px;

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


            .info-box {

                padding: 15px;
            }


            .info-icon {

                width: 38px;

                height: 38px;

                font-size: 16px;
            }


            .info-value {

                font-size: 14px;
            }


            .appointment-notes {

                padding: 15px;
            }


            .no-appointments {

                padding: 55px 18px;
            }
        }


        @media (max-width: 400px) {

            .appointments-header h1 {

                font-size: 34px;
            }


            .appointment-card {

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


<section class="appointments-page">

    <div class="appointments-container">


        <!-- =========================================
             PAGE HEADER
        ========================================= -->

        <div class="appointments-header">

            <div class="appointments-eyebrow">
                NAVA FADE STUDIO
            </div>

            <h1>
                My <span>Appointments</span>
            </h1>

            <p>
                View your upcoming bookings and keep track
                of your appointments with NAVA Fade Studio.
            </p>

        </div>


        <!-- =========================================
             ERROR MESSAGE
        ========================================= -->

        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($appointments)): ?>


            <!-- =====================================
                 APPOINTMENTS
            ====================================== -->

            <?php foreach ($appointments as $appointment): ?>

                <?php

                $status =
                    $appointment["status"];

                $statusClass =
                    getAppointmentStatusClass($status);

                $statusIcon =
                    getAppointmentStatusIcon($status);

                ?>


                <div class="appointment-card">


                    <!-- =================================
                         APPOINTMENT HEADER
                    ================================== -->

                    <div class="appointment-top">

                        <div class="appointment-heading">

                            <div class="appointment-label">
                                Appointment
                            </div>

                            <div class="appointment-service">

                                <?= htmlspecialchars(
                                    $appointment["service"]
                                ) ?>

                            </div>

                            <div class="appointment-id">

                                Booking #<?= (int) $appointment["id"] ?>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="appointment-status-wrapper">

                            <span
                                class="
                                    appointment-status
                                    status-<?= htmlspecialchars($statusClass) ?>
                                "
                            >

                                <span class="status-icon">

                                    <?= htmlspecialchars(
                                        $statusIcon
                                    ) ?>

                                </span>

                                <?= htmlspecialchars($status) ?>

                            </span>


                            <div class="status-description">

                                <?= htmlspecialchars(
                                    getAppointmentStatusDescription(
                                        $status
                                    )
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- =================================
                         PROGRESS
                    ================================== -->

                    <?php if ($status !== "Cancelled"): ?>

                        <?php

                        $progressMap = [

                            "Pending" => 1,

                            "Confirmed" => 2,

                            "Completed" => 3

                        ];

                        $currentStep =
                            $progressMap[$status] ?? 1;


                        if ($currentStep === 1) {

                            $activeWidth = 0;

                        } elseif ($currentStep === 2) {

                            $activeWidth = 50;

                        } else {

                            $activeWidth = 100;

                        }

                        ?>


                        <div class="appointment-progress">

                            <div class="progress-line"></div>


                            <div
                                class="progress-line-active"
                                style="
                                    width: <?= $activeWidth ?>%;
                                "
                            ></div>


                            <!-- PENDING -->

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


                            <!-- CONFIRMED -->

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


                            <!-- COMPLETED -->

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
                                    Completed
                                </span>

                            </div>

                        </div>


                    <?php else: ?>


                        <div class="progress-cancelled">

                            ✕ &nbsp;

                            This appointment has been cancelled.

                        </div>


                    <?php endif; ?>


                    <!-- =================================
                         APPOINTMENT INFORMATION
                    ================================== -->

                    <div class="appointment-information">


                        <!-- DATE -->

                        <div class="info-box">

                            <div class="info-icon">
                                📅
                            </div>

                            <div class="info-content">

                                <span class="info-label">
                                    Appointment Date
                                </span>

                                <span class="info-value">

                                    <?= date(
                                        "F j, Y",
                                        strtotime(
                                            $appointment[
                                                "appointment_date"
                                            ]
                                        )
                                    ) ?>

                                </span>

                            </div>

                        </div>


                        <!-- TIME -->

                        <div class="info-box">

                            <div class="info-icon">
                                🕐
                            </div>

                            <div class="info-content">

                                <span class="info-label">
                                    Appointment Time
                                </span>

                                <span class="info-value">

                                    <?= date(
                                        "g:i A",
                                        strtotime(
                                            $appointment[
                                                "appointment_time"
                                            ]
                                        )
                                    ) ?>

                                </span>

                            </div>

                        </div>


                    </div>


                    <!-- =================================
                         NOTES
                    ================================== -->

                    <?php if (!empty($appointment["notes"])): ?>

                        <div class="appointment-notes">

                            <span class="notes-label">
                                Your Notes
                            </span>

                            <div class="notes-text">

                                <?= htmlspecialchars(
                                    $appointment["notes"]
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- =====================================
                 NO APPOINTMENTS
            ====================================== -->

            <div class="no-appointments">

                <div class="no-appointments-icon">
                    📅
                </div>


                <h2>
                    No Appointments Yet
                </h2>


                <p>
                    You haven't booked an appointment with
                    NAVA Fade Studio yet. Ready for your next
                    fresh look?
                </p>


                <a
                    href="book.php"
                    class="appointment-book-btn"
                >
                    📅 BOOK AN APPOINTMENT
                </a>

            </div>


        <?php endif; ?>


        <!-- =========================================
             BACK TO HOME
        ========================================= -->

        <div class="appointments-back">

            <a href="index.php">
                ← Back to Home
            </a>

        </div>


    </div>

</section>


</body>

</html>