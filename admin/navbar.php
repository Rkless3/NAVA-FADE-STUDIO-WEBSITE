<?php
/*
|--------------------------------------------------------------------------
| NAVA FADE STUDIO - ADMIN NAVBAR
|--------------------------------------------------------------------------
| Reusable admin header.
| Displays:
| - NAVA Logo
| - Welcome message
| - Logged-in admin username
| - Logout button
|--------------------------------------------------------------------------
*/

$adminUsername = $_SESSION["admin_username"] ?? "Admin";
?>

<style>
    /* =========================================================
       ADMIN NAVBAR
    ========================================================= */

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

    .admin-logo {
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


    /* =========================================================
       NAVBAR RESPONSIVE
    ========================================================= */

    @media (max-width: 900px) {

        .admin-header {
            padding: 0 25px;
        }

    }


    @media (max-width: 700px) {

        .admin-header {
            height: 80px;
            padding: 0 18px;
        }

        .admin-logo {
            width: 135px;
            height: 135px;
        }

        .welcome-text {
            display: none;
        }

    }


    @media (max-width: 430px) {

        .admin-header {
            padding: 0 12px;
        }

        .admin-logo {
            width: 125px;
            height: 125px;
        }

        .logout-btn {
            min-width: 72px;
            height: 35px;

            padding: 0 13px;

            font-size: 12px;
        }

    }
</style>


<!-- =========================================================
     ADMIN NAVBAR
========================================================= -->

<header class="admin-header">

    <div class="header-left">

        <img
            src="../assets/images/logo.png"
            alt="NAVA Fade Studio"
            class="admin-logo"
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