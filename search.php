
<form method="GET">
  <input name="roll_no" placeholder="Search by Roll No">
  <button>Search</button>
</form>

<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
if (isset($_GET['roll_no'])) {
  $roll = $_GET['roll_no'];
  include 'db.php';
  $q = $conn->query("SELECT s.name, s.roll_no, s.department, e.entry_date, e.entry_time, e.status, e.fine
                     FROM students s JOIN entries e ON s.id = e.student_id
                     WHERE s.roll_no = '$roll'
                     ORDER BY e.entry_date DESC");
  echo "<table border='1'><tr><th>Name</th><th>Department</th><th>Date</th><th>Time</th><th>Status</th><th>Fine</th></tr>";
  while ($r = $q->fetch_assoc()) {
    echo "<tr><td>{$r['name']}</td><td>{$r['department']}</td><td>{$r['entry_date']}</td><td>{$r['entry_time']}</td><td>{$r['status']}</td><td>{$r['fine']}</td></tr>";
  }
  echo "</table>";
}
?>