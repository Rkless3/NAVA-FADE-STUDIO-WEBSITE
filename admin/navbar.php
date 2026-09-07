<?php
/* NAVA Fade Studio - Shared Admin Navbar */
$adminUsername = $_SESSION["admin_username"] ?? "Admin";
?>

<style>
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

    .admin-logo {
        display: flex;
        align-items: center;
        height: 100%;
    }

    .admin-logo img {
        width: 165px;
        height: 165px;
        object-fit: contain;
        display: block;
    }

    .admin-user {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .admin-user span {
        color: #f3f3f3;
        font-size: 14px;
        font-weight: 600;
    }

    .admin-user strong {
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

    @media (max-width: 900px) {
        .admin-header { padding: 0 25px; }
    }

    @media (max-width: 700px) {
        .admin-header { height: 80px; padding: 0 18px; }
        .admin-logo img { width: 135px; height: 135px; }
        .admin-user span { display: none; }
    }

    @media (max-width: 430px) {
        .admin-header { padding: 0 12px; }
        .admin-logo img { width: 125px; height: 125px; }
        .logout-btn { min-width: 72px; height: 35px; padding: 0 13px; font-size: 12px; }
    }
</style>

<header class="admin-header">
    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="NAVA Fade Studio">
    </div>

    <div class="admin-user">
        <span>
            Welcome,
            <strong><?= htmlspecialchars($adminUsername) ?></strong>
        </span>

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>
