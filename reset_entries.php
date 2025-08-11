<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php';
date_default_timezone_set('Asia/Kolkata'); // Set the time zone to IST (Indian Standard Time)


$conn->query("DELETE FROM entries WHERE entry_date < CURDATE() - INTERVAL 6 MONTH");
header("Location: index.php");
