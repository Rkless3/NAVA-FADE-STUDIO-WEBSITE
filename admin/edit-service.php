<?php

session_start();

/*
|--------------------------------------------------------------------------
| CHECK ADMIN LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE & SERVICE
|--------------------------------------------------------------------------
*/

require_once "../config/Database.php";
require_once "../classes/Service.php";

$database = new Database();
$db = $database->connect();

$service = new Service($db);


/*
|--------------------------------------------------------------------------
| GET SERVICE ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    header("Location: services.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET CURRENT SERVICE
|--------------------------------------------------------------------------
*/

$currentService = $service->getById($id);

if (!$currentService) {
    header("Location: services.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$name = $currentService["service_name"];
$description = $currentService["description"];
$price = $currentService["price"];
$duration = $currentService["duration"];
$image = $currentService["image"] ?? "";

$error = "";


/*
|--------------------------------------------------------------------------
| UPDATE SERVICE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $duration = trim($_POST["duration"] ?? "");
    $image = trim($_POST["image"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $name === "" ||
        $description === "" ||
        $price === "" ||
        $duration === ""
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

        if (
            $service->update(
                $id,
                $name,
                $description,
                (float) $price,
                $duration,
                $image
            )
        ) {

            header("Location: services.php?message=updated");
            exit;

        } else {

            $error = "Unable to update service. Please try again.";

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
        Edit Service | NAVA Fade Studio Admin
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
                Arial,
                sans-serif;

            background:
                url("../assets/images/pattern3.png");

            background-size: cover;
            background-position: center;

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

            border-bottom:
                2px solid #b8862c;

            position: sticky;
            top: 0;

            z-index: 1000;

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

            color: #ffffff;

        }


        .logout-btn {

            padding: 10px 20px;

            background: transparent;

            color: #b8862c;

            border:
                2px solid #b8862c;

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

            margin-bottom: 30px;

        }


        .page-header h2 {

            font-size: 36px;

            color: #ffffff;

        }


        .page-header p {

            margin-top: 8px;

            color: #aaa;

            font-size: 16px;

        }


        /* =========================================
           FORM CARD
        ========================================= */

        .form-card {

            max-width: 950px;

            padding: 35px;

            background: #0e1423;

            border:
                1px solid #b8862c;

            border-radius: 18px;

            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.25);

        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 22px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }


        .form-group.full-width {

            grid-column:
                1 / -1;

        }


        .form-group label {

            color: #ffffff;

            font-size: 14px;

            font-weight: bold;

        }


        .required {

            color: #b8862c;

        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            padding: 13px 15px;

            background: #151c2d;

            color: #ffffff;

            border:
                1px solid #444;

            border-radius: 8px;

            font-family: inherit;

            font-size: 14px;

            outline: none;

            transition: 0.3s ease;

        }


        .form-group input:focus,
        .form-group textarea:focus {

            border-color:
                #b8862c;

            box-shadow:
                0 0 0 2px
                rgba(184, 134, 44, 0.12);

        }


        .form-group textarea {

            min-height: 130px;

            resize: vertical;

        }


        .form-help {

            color: #777;

            font-size: 12px;

            line-height: 1.4;

        }


        /* =========================================
           CURRENT IMAGE
        ========================================= */

        .current-image {

            margin-top: 8px;

            display: flex;

            align-items: center;

            gap: 15px;

        }


        .current-image img {

            width: 110px;

            height: 75px;

            object-fit: cover;

            border-radius: 8px;

            border:
                2px solid #b8862c;

        }


        .current-image span {

            color: #888;

            font-size: 12px;

        }


        /* =========================================
           ERROR
        ========================================= */

        .error-message {

            max-width: 950px;

            margin-bottom: 25px;

            padding: 14px 18px;

            background:
                rgba(139, 48, 48, 0.2);

            border:
                1px solid #8b3030;

            border-radius: 8px;

            color: #ffb0b0;

            font-size: 14px;

            font-weight: bold;

        }


        /* =========================================
           ACTIONS
        ========================================= */

        .form-actions {

            display: flex;

            justify-content:
                flex-end;

            gap: 12px;

            margin-top: 30px;

            padding-top: 25px;

            border-top:
                1px solid #333;

        }


        .cancel-btn,
        .save-btn {

            padding: 12px 24px;

            border-radius: 8px;

            font-family: inherit;

            font-size: 14px;

            font-weight: bold;

            text-decoration: none;

            cursor: pointer;

            transition: 0.3s ease;

        }


        .cancel-btn {

            background: transparent;

            color: #ffffff;

            border:
                1px solid #555;

        }


        .cancel-btn:hover {

            border-color:
                #b8862c;

            color: #b8862c;

        }


        .save-btn {

            background: #b8862c;

            color: #0e1423;

            border:
                1px solid #b8862c;

        }


        .save-btn:hover {

            background: #d4a33a;

            transform:
                translateY(-2px);

        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 800px) {

            .admin-header {

                padding: 0 20px;

            }


            .admin-user span {

                display: none;

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

            }


            .main-content {

                padding: 30px 20px;

            }


            .page-header h2 {

                font-size: 30px;

            }


            .form-grid {

                grid-template-columns:
                    1fr;

            }


            .form-group.full-width {

                grid-column: auto;

            }

        }


        @media (max-width: 500px) {

            .admin-header {

                height: 80px;

            }


            .admin-logo img {

                width: 130px;
                height: 130px;

            }


            .main-content {

                padding: 25px 15px;

            }


            .form-card {

                padding: 22px;

            }


            .form-actions {

                flex-direction: column-reverse;

            }


            .cancel-btn,
            .save-btn {

                width: 100%;

                text-align: center;

            }

        }

    </style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header class="admin-header">

    <div class="admin-logo">

        <img
            src="../assets/images/logo.png"
            alt="NAVA Fade Studio"
        >

    </div>


    <div class="admin-user">

        <span>

            Welcome,
            <?= htmlspecialchars(
                $_SESSION["admin_username"]
                ?? "Admin"
            ) ?>

        </span>


        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>

</header>


<!-- =========================================
     ADMIN LAYOUT
========================================= -->

<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-title">
            Admin Panel
        </div>


        <a href="dashboard.php">
            Dashboard
        </a>


        <a
            href="services.php"
            class="active"
        >
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


        <a href="#">
            Settings
        </a>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================= -->

    <main class="main-content">


        <div class="page-header">

            <h2>
                Edit Service
            </h2>

            <p>
                Update the information for this
                NAVA Fade Studio service.
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- =========================================
             FORM
        ========================================= -->

        <div class="form-card">

            <form method="POST">


                <div class="form-grid">


                    <!-- SERVICE NAME -->

                    <div class="form-group">

                        <label for="name">

                            Service Name
                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($name) ?>"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- PRICE -->

                    <div class="form-group">

                        <label for="price">

                            Price
                            <span class="required">*</span>

                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="<?= htmlspecialchars($price) ?>"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>


                    <!-- DURATION -->

                    <div class="form-group">

                        <label for="duration">

                            Duration
                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            id="duration"
                            name="duration"
                            value="<?= htmlspecialchars($duration) ?>"
                            maxlength="50"
                            required
                        >

                    </div>


                    <!-- IMAGE -->

                    <div class="form-group">

                        <label for="image">
                            Image Filename
                        </label>

                        <input
                            type="text"
                            id="image"
                            name="image"
                            value="<?= htmlspecialchars($image) ?>"
                            maxlength="255"
                        >

                        <small class="form-help">

                            Leave the current filename if
                            you don't want to change the image.

                        </small>


                        <?php if (!empty($image)): ?>

                            <div class="current-image">

                                <img
                                    src="../assets/images/<?= htmlspecialchars($image) ?>"
                                    alt="<?= htmlspecialchars($name) ?>"
                                >

                                <span>
                                    Current image
                                </span>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group full-width">

                        <label for="description">

                            Description
                            <span class="required">*</span>

                        </label>

                        <textarea
                            id="description"
                            name="description"
                            required
                        ><?= htmlspecialchars($description) ?></textarea>

                    </div>


                </div>


                <!-- ACTIONS -->

                <div class="form-actions">

                    <a
                        href="services.php"
                        class="cancel-btn"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="save-btn"
                    >
                        Update Service
                    </button>

                </div>


            </form>

        </div>


    </main>

</div>


</body>

</html>