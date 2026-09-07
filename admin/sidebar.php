<?php
/* NAVA Fade Studio - Shared Admin Sidebar */
$currentPage = basename($_SERVER["PHP_SELF"]);
?>

<style>
    .admin-layout {
        display: flex;
        min-height: calc(100vh - 95px);
    }

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
        margin: 0 15px 28px;
        color: #71809b;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 3px;
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
        transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
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
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
    }

    .sidebar-menu a.active:hover {
        background: #d19a2b;
        color: #0e1423;
        transform: none;
    }

    @media (max-width: 900px) {
        .sidebar { width: 215px; }
    }

    @media (max-width: 700px) {
        .admin-layout { flex-direction: column; }
        .sidebar {
            position: static;
            width: 100%;
            min-height: auto;
            padding: 18px;
            border-right: none;
            border-bottom: 1px solid rgba(184, 134, 44, 0.35);
        }
        .sidebar-title { margin-bottom: 12px; }
        .sidebar-menu {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }
        .sidebar-menu a { height: 42px; font-size: 13px; }
    }

    @media (max-width: 430px) {
        .sidebar-menu { grid-template-columns: 1fr; }
    }
</style>

<aside class="sidebar">
    <div class="sidebar-title">Admin Panel</div>

    <nav class="sidebar-menu">
        <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="services.php" class="<?= $currentPage === 'services.php' ? 'active' : '' ?>">Services</a>
        <a href="bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">Bookings</a>
        <a href="products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">Products</a>
        <a href="orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
        <a href="reviews.php" class="<?= $currentPage === 'reviews.php' ? 'active' : '' ?>">Reviews</a>
        <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">Settings</a>
    </nav>
</aside>
