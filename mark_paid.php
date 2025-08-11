<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_id'])) {
    $entry_id = (int)$_POST['entry_id'];

    $stmt = $conn->prepare("UPDATE entries SET fine_paid = 1 WHERE id = ?");
    $stmt->bind_param("i", $entry_id);
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Failed to update.";
    }
}
?>
