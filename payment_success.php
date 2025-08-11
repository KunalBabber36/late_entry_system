<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $amount = $_POST['amount'];

    // Update fine_paid in entries for this student
    $stmt = $conn->prepare("
        UPDATE entries SET fine_paid = 1 
        WHERE student_id = ? AND fine_paid = 0
    ");
    $stmt->bind_param("i", $student_id);

    echo $stmt->execute() ? "1" : "0";
}
?>
