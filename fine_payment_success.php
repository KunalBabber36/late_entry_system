<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $student_id = $_POST['student_id'];
    $amount = $_POST['amount'];

    // Optional: Save payment record
    $stmt = $conn->prepare("INSERT INTO fine_payments (student_id, amount, payment_id, payment_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ids", $student_id, $amount, $payment_id);
    $stmt->execute();

    // Update all unpaid fines for this student as paid
    $update = $conn->prepare("UPDATE entries SET fine = 0 WHERE student_id = ? AND fine > 0");
    $update->bind_param("i", $student_id);
    $success = $update->execute();

    echo $success ? "1" : "0";
}
?>
