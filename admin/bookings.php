<?php
session_start();

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/Database.php";

$database = new Database();
$db = $database->connect();

$success = "";
$error = "";
$allowed_statuses = ["Pending", "Confirmed", "Completed", "Cancelled"];

/* =========================================
   UPDATE PAYMENT / BOOKING STATUS
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_id = (int) ($_POST["appointment_id"] ?? 0);
    $action = $_POST["action"] ?? "";
    $status = $_POST["status"] ?? "";

    try {
        if ($appointment_id <= 0) {
            throw new RuntimeException("Invalid appointment.");
        }

        $db->beginTransaction();

        $check = $db->prepare("SELECT status FROM appointments WHERE id = :id FOR UPDATE");
        $check->execute([":id" => $appointment_id]);
        $current = $check->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            throw new RuntimeException("Appointment not found.");
        }

        if (in_array($current["status"], ["Completed", "Cancelled"], true)) {
            throw new RuntimeException("This appointment is locked because it is already {$current["status"]}.");
        }

        $payment = $db->prepare("SELECT id, status FROM payments WHERE appointment_id = :id ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $payment->execute([":id" => $appointment_id]);
        $paymentData = $payment->fetch(PDO::FETCH_ASSOC);

        if ($action === "mark_paid") {
            if (!$paymentData) {
                throw new RuntimeException("No payment record was found for this appointment.");
            }
            if ($paymentData["status"] !== "Pending") {
                throw new RuntimeException("This payment can no longer be changed.");
            }

            $paid = $db->prepare("UPDATE payments SET status = 'Paid', paid_at = NOW() WHERE id = :id AND status = 'Pending'");
            $paid->execute([":id" => $paymentData["id"]]);
            $success = "Appointment payment marked as Paid.";
        } elseif ($action === "update_status") {
            if (!in_array($status, $allowed_statuses, true)) {
                throw new RuntimeException("Invalid booking status.");
            }

            if ($status === "Completed") {
                if (!$paymentData || $paymentData["status"] !== "Paid") {
                    throw new RuntimeException("The appointment cannot be marked Completed until the payment is Paid.");
                }
            }

            if ($status === "Cancelled" && $paymentData && $paymentData["status"] === "Pending") {
                $cancelPayment = $db->prepare("UPDATE payments SET status = 'Cancelled' WHERE id = :id AND status = 'Pending'");
                $cancelPayment->execute([":id" => $paymentData["id"]]);
            }

            $update = $db->prepare("UPDATE appointments SET status = :status WHERE id = :id");
            $update->execute([
                ":status" => $status,
                ":id" => $appointment_id
            ]);
            $success = "Booking status updated successfully!";
        }

        $db->commit();
    } catch (RuntimeException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = "Unable to update booking information.";
    }
}

/* =========================================
   GET ALL BOOKINGS + PAYMENT
========================================= */
try {
    $query = $db->prepare("\n        SELECT\n            a.id, a.service, a.appointment_date, a.appointment_time,\n            a.notes, a.status, a.created_at,\n            c.full_name, c.email, c.contact_number,\n            p.id AS payment_id, p.payment_method, p.reference_number,\n            p.amount AS payment_amount, p.status AS payment_status, p.paid_at\n        FROM appointments a\n        INNER JOIN customers c ON a.customer_id = c.id\n        LEFT JOIN payments p ON p.id = (\n            SELECT MAX(p2.id) FROM payments p2 WHERE p2.appointment_id = a.id\n        )\n        ORDER BY a.appointment_date DESC, a.appointment_time DESC\n    ");
    $query->execute();
    $bookings = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $error = "Unable to load bookings.";
}

$totalBookings = count($bookings);
$pendingCount = $confirmedCount = $completedCount = $cancelledCount = 0;
foreach ($bookings as $booking) {
    switch ($booking["status"]) {
        case "Pending": $pendingCount++; break;
        case "Confirmed": $confirmedCount++; break;
        case "Completed": $completedCount++; break;
        case "Cancelled": $cancelledCount++; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings | NAVA Fade Studio Admin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Bahnschrift, "Myriad Pro", "Bahnschrift", sans-serif;
            background: linear-gradient(rgba(8,12,22,.92),rgba(8,12,22,.96)), url("../assets/images/pattern3.png");
            background-size: cover;
            background-position: center;
            color: #fff;
        }
        .main-content { flex: 1; padding: 45px; min-width: 0; }
        .welcome { margin-bottom: 28px; }
        .welcome h2 { font-size: 34px; margin: 0 0 8px; }
        .welcome p { color: #aeb5c3; margin: 0; }
        .alerts { margin-bottom: 22px; }
        .admin-success, .admin-error { padding: 14px 17px; border-radius: 9px; font-weight: 700; }
        .admin-success { background: rgba(46,204,113,.12); color:#2ecc71; border:1px solid #2ecc71; }
        .admin-error { background: rgba(244,67,54,.12); color:#f44336; border:1px solid #f44336; }
        .summary-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:25px; }
        .summary-card { background:rgba(14,20,35,.96); border:1px solid rgba(184,134,44,.45); border-radius:12px; padding:18px; }
        .summary-card span { display:block; color:#9fa7b8; font-size:12px; margin-bottom:7px; }
        .summary-card strong { font-size:25px; color:#d19a2a; }
        .bookings-table-container { background:rgba(14,20,35,.96); border:1px solid #b8862c; border-radius:15px; overflow-x:auto; box-shadow:0 10px 30px rgba(0,0,0,.25); }
        table { width:100%; min-width:1450px; border-collapse:collapse; }
        thead { background:#b8862c; color:#0e1423; }
        th { padding:16px; text-align:left; font-size:13px; }
        td { padding:16px; border-bottom:1px solid rgba(255,255,255,.08); vertical-align:top; color:#e5e8ee; }
        tbody tr:hover { background:rgba(255,255,255,.025); }
        .customer-contact { display:block; color:#9fa7b8; font-size:12px; margin-top:4px; }
        .booking-time { color:#d19a2a; }
        .booking-notes { max-width:220px; color:#c7ccd5; }
        .no-notes { color:#70798a; }
        .booking-status, .payment-status { display:inline-block; padding:6px 10px; border-radius:18px; font-size:11px; font-weight:700; }
        .status-pending, .payment-pending { background:rgba(255,193,7,.15); color:#ffc107; }
        .status-confirmed { background:rgba(0,188,212,.15); color:#00bcd4; }
        .status-completed, .payment-paid { background:rgba(76,175,80,.15); color:#4caf50; }
        .status-cancelled, .payment-cancelled, .payment-failed { background:rgba(244,67,54,.15); color:#f44336; }
        .payment-info { line-height:1.7; min-width:210px; }
        .payment-info strong { color:#fff; }
        .reference { color:#d19a2a; word-break:break-all; }
        .paid-date { color:#4caf50; font-size:12px; }
        .status-form { display:flex; gap:7px; flex-wrap:wrap; align-items:center; }
        .status-select { background:#151d30; color:#fff; border:1px solid #4d5567; border-radius:7px; padding:9px; }
        .update-btn, .paid-btn { border:0; border-radius:7px; padding:9px 12px; font-weight:700; cursor:pointer; }
        .update-btn { background:#b8862c; color:#0e1423; }
        .paid-btn { background:#2ecc71; color:#07140d; }
        .update-btn:disabled, .paid-btn:disabled { opacity:.45; cursor:not-allowed; }
        .locked { color:#9fa7b8; font-size:12px; font-weight:700; }
        .empty-bookings { padding:60px 20px; text-align:center; color:#9fa7b8; }
        @media(max-width:1000px){ .summary-grid{grid-template-columns:repeat(2,1fr)} .main-content{padding:30px 20px;} }
        @media(max-width:500px){ .summary-grid{grid-template-columns:1fr;} .welcome h2{font-size:28px;} }
    </style>
</head>
<body>
<?php include "../admin/navbar.php"; ?>
<div class="admin-layout">
    <?php include "../admin/sidebar.php"; ?>
    <main class="main-content">
        <div class="welcome">
            <h2>Booking Management</h2>
            <p>View appointments, verify payments, and manage booking status.</p>
        </div>

        <div class="summary-grid">
            <div class="summary-card"><span>Total Bookings</span><strong><?= $totalBookings ?></strong></div>
            <div class="summary-card"><span>Pending</span><strong><?= $pendingCount ?></strong></div>
            <div class="summary-card"><span>Confirmed</span><strong><?= $confirmedCount ?></strong></div>
            <div class="summary-card"><span>Completed</span><strong><?= $completedCount ?></strong></div>
            <div class="summary-card"><span>Cancelled</span><strong><?= $cancelledCount ?></strong></div>
        </div>

        <div class="alerts">
            <?php if ($success): ?><div class="admin-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="admin-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        </div>

        <div class="bookings-table-container">
            <?php if (empty($bookings)): ?>
                <div class="empty-bookings">No appointments have been booked yet.</div>
            <?php else: ?>
                <table>
                    <thead><tr>
                        <th>Customer</th><th>Service(s)</th><th>Date & Time</th><th>Notes</th>
                        <th>Payment</th><th>Payment Status</th><th>Booking Status</th><th>Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($booking["full_name"]) ?></strong>
                                <span class="customer-contact"><?= htmlspecialchars($booking["email"]) ?></span>
                                <span class="customer-contact"><?= htmlspecialchars($booking["contact_number"]) ?></span>
                            </td>
                            <td><?= htmlspecialchars($booking["service"]) ?></td>
                            <td>
                                <strong><?= date("M d, Y", strtotime($booking["appointment_date"])) ?></strong><br>
                                <span class="booking-time"><?= date("g:i A", strtotime($booking["appointment_time"])) ?></span>
                            </td>
                            <td class="booking-notes">
                                <?= !empty($booking["notes"]) ? htmlspecialchars($booking["notes"]) : '<span class="no-notes">No notes</span>' ?>
                            </td>
                            <td class="payment-info">
                                <strong><?= htmlspecialchars($booking["payment_method"] ?? "Not recorded") ?></strong><br>
                                ₱<?= number_format((float)($booking["payment_amount"] ?? 0), 2) ?>
                                <?php if (($booking["payment_method"] ?? "") === "GCash"): ?>
                                    <br><span class="reference">Ref: <?= htmlspecialchars($booking["reference_number"] ?? "Pending") ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="payment-status payment-<?= strtolower($booking["payment_status"] ?? "pending") ?>">
                                    <?= htmlspecialchars($booking["payment_status"] ?? "Pending") ?>
                                </span>
                                <?php if (!empty($booking["paid_at"])): ?>
                                    <div class="paid-date"><?= date("M d, Y h:i A", strtotime($booking["paid_at"])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="booking-status status-<?= strtolower($booking["status"]) ?>"><?= htmlspecialchars($booking["status"]) ?></span>
                            </td>
                            <td>
                                <?php if (in_array($booking["status"], ["Completed", "Cancelled"], true)): ?>
                                    <span class="locked">🔒 Locked</span>
                                <?php else: ?>
                                    <?php if (($booking["payment_status"] ?? "") === "Pending"): ?>
                                        <form method="POST" class="status-form" style="margin-bottom:7px;">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$booking["id"] ?>">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <button class="paid-btn" type="submit">✓ Mark as Paid</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="appointment_id" value="<?= (int)$booking["id"] ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <select name="status" class="status-select">
                                            <?php foreach ($allowed_statuses as $option): ?>
                                                <option value="<?= htmlspecialchars($option) ?>" <?= $booking["status"] === $option ? "selected" : "" ?>><?= htmlspecialchars($option) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="update-btn" type="submit">Update</button>
                                    </form>
                                <?php endif; ?>
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
