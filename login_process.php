<?php
session_start();
include 'db.php';

// Dummy credentials (replace with a database table for real use)
$valid_user = 'admin';
$valid_pass = 'admin123';

$username = $_POST['username'];
$password = $_POST['password'];

if ($username === $valid_user && $password === $valid_pass) {
    $_SESSION['logged_in'] = true;
    header("Location: index.php");
} else {
    header("Location: login.php?error=Invalid credentials");
}
