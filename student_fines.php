<?php 
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php'; 
date_default_timezone_set('Asia/Kolkata'); // Set the time zone to IST (Indian Standard Time)

?>
<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Fines</title></head>
<body>
  <h2>Fine Summary</h2>
  <table border="1">
    <tr><th>Student</th><th>Roll</th><th>Total Late</th><th>Total Fine</th></tr>
    <?php
    $res = $conn->query("SELECT s.name, s.roll_no, COUNT(e.id) AS late_count, SUM(e.fine) AS total_fine 
                         FROM students s 
                         JOIN entries e ON s.id = e.student_id 
                         WHERE e.status='Late' 
                         GROUP BY s.id");

    while($row = $res->fetch_assoc()):
    ?>
    <tr>
      <td><?= $row['name'] ?></td>
      <td><?= $row['roll_no'] ?></td>
      <td><?= $row['late_count'] ?></td>
      <td>₹<?= $row['total_fine'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
