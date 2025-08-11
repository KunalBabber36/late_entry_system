<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php';

date_default_timezone_set('Asia/Kolkata');

$roll_no = $_POST['roll_no'];
$today = date('Y-m-d');
$entry_time = date('H:i:s');

// Reset fines older than 6 months
$conn->query("UPDATE entries SET fine = 0 WHERE entry_date < CURDATE() - INTERVAL 6 MONTH AND fine > 0");

// Check if student exists
$stmt = $conn->prepare("SELECT * FROM students WHERE roll_no = ?");
$stmt->bind_param("s", $roll_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Student not found!";
    $_SESSION['messageType'] = "error";
    header("Location: index.php");
    exit;
}

$student = $result->fetch_assoc();
$student_id = $student['id'];

// Check if entry already exists for today
$check = $conn->prepare("SELECT * FROM entries WHERE student_id = ? AND entry_date = ?");
$check->bind_param("is", $student_id, $today);
$check->execute();
$entry_result = $check->get_result();

if ($entry_result->num_rows > 0) {
    $existing_entry = $entry_result->fetch_assoc();
    $status = $existing_entry['status'];
    $fine = $existing_entry['fine'];

    $_SESSION['message'] = "Entry Already Marked for Roll: <strong>{$roll_no}</strong> Today<br>Status: <strong>{$status}</strong><br>Fine: ₹{$fine}";
    $_SESSION['messageType'] = "warning";
    header("Location: index.php");
    exit;
}

// Determine status
$status = ($entry_time > '08:30:00') ? 'Late' : 'On Time';

// Count previous LATE entries in last 6 months
$stmt_late_count = $conn->prepare("
    SELECT COUNT(*) as late_count 
    FROM entries 
    WHERE student_id = ? 
      AND status = 'Late' 
      AND entry_date >= CURDATE() - INTERVAL 6 MONTH
");
$stmt_late_count->bind_param("i", $student_id);
$stmt_late_count->execute();
$late_count_res = $stmt_late_count->get_result();
$late_data = $late_count_res->fetch_assoc();
$late_count = $late_data['late_count'];

if ($status == 'Late') {
    $late_count += 1;
}

// Fine logic
$fine = 0;
if ($status == 'Late') {
    if ($late_count == 1) {
        $fine = 100;
    } elseif ($late_count == 2) {
        $fine = 250;
    } elseif ($late_count >= 3) {
        $fine = 500;
    }
}

// Insert entry
$insert = $conn->prepare("
    INSERT INTO entries (student_id, entry_time, entry_date, status, fine, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
");
$insert->bind_param("isssi", $student_id, $entry_time, $today, $status, $fine);
$insert->execute();

$_SESSION['message'] = "Entry Recorded for Roll: <strong>{$roll_no}</strong><br>Status: <strong>{$status}</strong><br>Fine: ₹{$fine}";
$_SESSION['messageType'] = "success";
header("Location: index.php");
exit;
?>
