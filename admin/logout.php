<?php

session_start();


/*
 * Remove customer session.
 */

unset(
    $_SESSION["customer_id"],
    $_SESSION["customer_name"],
    $_SESSION["customer_email"]
);


/*
 * Remove administrator session too.
 */

unset(
    $_SESSION["admin_logged_in"],
    $_SESSION["admin_username"]
);


/*
 * Clear everything.
 */

$_SESSION = [];


/*
 * Destroy the session.
 */

session_destroy();


/*
 * Return to ONE LOGIN PAGE.
 */

header("Location: login.php");
exit;

?>