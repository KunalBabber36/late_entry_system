<?php include 'db.php'; 
date_default_timezone_set('Asia/Kolkata'); // Set the time zone to IST (Indian Standard Time)
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Student Entry</title>
</head>
<body>
  <form method="POST" action="submit_entry.php">
    <input name="roll_no" placeholder="Enter Roll Number" required>
    <button type="submit">Submit Entry</button>
  </form>
</body>
</html>