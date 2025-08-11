<?php 
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php'; 
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $like_search = "%$search%";
    $stmt = $conn->prepare("
        SELECT e.*, s.name, s.roll_no, s.department, s.created_at AS student_created_at, e.created_at AS entry_created_at
        FROM entries e 
        JOIN students s ON s.id = e.student_id 
        WHERE entry_date = ? AND (s.name LIKE ? OR s.roll_no LIKE ?)
        ORDER BY e.entry_time ASC
    ");
    $stmt->bind_param("sss", $today, $like_search, $like_search);
} else {
    $stmt = $conn->prepare("
        SELECT e.*, s.name, s.roll_no, s.department, s.created_at AS student_created_at, e.created_at AS entry_created_at
        FROM entries e 
        JOIN students s ON s.id = e.student_id 
        WHERE entry_date = ?
        ORDER BY e.entry_time ASC
    ");
    $stmt->bind_param("s", $today);
}

$stmt->execute();
$res = $stmt->get_result();

$three_years_ago = date('Y-m-d', strtotime('-3 years'));
$summary_stmt = $conn->prepare("
    SELECT 
        s.id AS student_id, s.name, s.roll_no, s.department,
        COUNT(e.id) AS total_entries,
        SUM(CASE WHEN e.status = 'Late' THEN 1 ELSE 0 END) AS total_late_entries,
        SUM(e.fine) AS total_fines,
        SUM(CASE WHEN e.fine_paid = 1 THEN e.fine ELSE 0 END) AS fine_paid
    FROM students s
    LEFT JOIN entries e ON s.id = e.student_id AND e.entry_date >= ?
    GROUP BY s.id
    ORDER BY s.name
");
$summary_stmt->bind_param("s", $three_years_ago);
$summary_stmt->execute();
$summary_res = $summary_stmt->get_result();
?>
<?php if (isset($_SESSION['message'])): ?>
    <div class="message-box <?php echo $_SESSION['messageType']; ?>">
        <?php echo $_SESSION['message']; ?>
    </div>
    <?php
        unset($_SESSION['message']);
        unset($_SESSION['messageType']);
    ?>
<?php endif; ?>


<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Student Entry System</title>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.7/html5-qrcode.min.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #aaa; padding: 10px; text-align: center; }
    button { padding: 5px 10px; }
    .message-box {
    max-width: 360px;
    background: white;
    padding: 20px 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    line-height: 1.5;
    font-family: Arial, sans-serif;
}

.success { 
    border-left: 6px solid #28a745; 
    color: #155724; 
    background-color: #d4edda; 
}

.warning { 
    border-left: 6px solid #ffc107; 
    color: #856404; 
    background-color: #fff3cd; 
}

.error { 
    border-left: 6px solid #dc3545; 
    color: #721c24; 
    background-color: #f8d7da; 
}

    #search-form {
      margin-top: 20px;
      margin-bottom: 10px;
    }
    #search-input {
      width: 300px;
      padding: 5px 10px;
      font-size: 16px;
    }
    #search-button {
      padding: 6px 15px;
      font-size: 16px;
    }
    /* General body style */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f4f7fa;
  color: #333;
  margin: 30px;
  line-height: 1.6;
}

/* Heading styles */
h2, h3 {
  color: #2c3e50;
  font-weight: 700;
  margin-bottom: 10px;
}

/* Logout button */
form[method="POST"] button {
  background-color: #e74c3c;
  border: none;
  color: white;
  padding: 8px 16px;
  font-weight: 600;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

form[method="POST"] button:hover {
  background-color: #c0392b;
}

/* Form inputs and buttons */
input[type="text"], 
#search-input, 
button, 
form[action="submit_entry.php"] input[type="text"] {
  font-size: 16px;
  padding: 10px 12px;
  border: 1.5px solid #ccc;
  border-radius: 5px;
  transition: border-color 0.3s ease;
  outline: none;
  box-sizing: border-box;
}

input[type="text"]:focus, 
#search-input:focus {
  border-color: #3498db;
  box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
}

button {
  background-color: #3498db;
  border: none;
  color: white;
  cursor: pointer;
  border-radius: 5px;
  padding: 10px 18px;
  font-weight: 600;
  transition: background-color 0.3s ease;
}

button:hover {
  background-color: #2980b9;
}

/* Search form and entry form styling */
#search-form {
  margin-top: 20px;
  margin-bottom: 20px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}

#search-input {
  flex-grow: 1;
  max-width: 350px;
}

#search-button, #search-form button[type="button"] {
  min-width: 100px;
}

/* Tables */
table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 10px;
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 0 8px rgb(0 0 0 / 0.1);
}

th, td {
  padding: 14px 12px;
  text-align: center;
  border-bottom: 1px solid #ddd;
}

th {
  background-color: #3498db;
  color: white;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

tr:hover {
  background-color: #f1f8ff;
}

td {
  font-weight: 500;
}

/* Buttons inside table */
table button {
  padding: 6px 12px;
  font-size: 14px;
  border-radius: 4px;
}

table button:hover {
  background-color: #2980b9;
}

/* Fine paid / unpaid badges */
td:nth-child(7) {
  font-weight: 700;
  color: #2c3e50;
}

td:nth-child(7):contains("Unpaid") {
  color: #e74c3c;
  font-weight: 700;
}
.button-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
  }

  .button-container a {
    padding: 10px 18px;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 16px;
    text-align: center;
    flex: 1 1 auto;
    min-width: 150px;
    max-width: 200px;
  }

  .btn-history { background-color: #4CAF50; }
  .btn-fines { background-color: #f44336; }
  .btn-register { background-color: #2196F3; }
  .btn-search { background-color: #FF9800; }
  .btn-details { background-color: rgb(89, 0, 255); }
  .btn-upload { background-color: rgb(0, 0, 0); }


  @media (max-width: 600px) {
    .button-container a {
      flex: 1 1 100%;
      max-width: 100%;
    }
  }
/* Responsive */
@media (max-width: 768px) {
  body {
    margin: 10px;
  }

  table, thead, tbody, th, td, tr {
    display: block;
  }

  thead tr {
    display: none;
  }

  tr {
    margin-bottom: 15px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    padding: 15px;
    border-radius: 8px;
    background: white;
  }

  td {
    text-align: right;
    padding-left: 50%;
    position: relative;
    border-bottom: 1px solid #eee;
  }

  td::before {
    content: attr(data-label);
    position: absolute;
    left: 15px;
    top: 14px;
    font-weight: 600;
    color: #555;
    text-transform: uppercase;
    font-size: 12px;
  }

  /* Make buttons full width on small screens */
  table button {
    width: 100%;
    padding: 10px;
    font-size: 16px;
  }
}

  </style>
</head>
<body>

<form action="logout.php" method="POST" style="text-align: right;">
  <button type="submit">Logout</button>
</form>

<h2>Student Entry Gate System</h2>

<div class="button-container">
  <a href="history.php" class="btn-history">History</a>
  <a href="fine_report.php" class="btn-fines">Fines Report</a>
  <a href="register.php" class="btn-register">Registered Students</a>
  <a href="search_student.php" class="btn-search">Search Student</a>
  <a href="details.php" class="btn-details">Student Details</a>
  <a href="bulk_upload.php" class="btn-upload">Upload Csv</a>

</div>

<form action="submit_entry.php" method="POST">
  <input type="text" name="roll_no" placeholder="Enter Roll Number" required>
  <button type="submit">Submit Entry</button>
</form>

<!-- QR Scanner -->
<h3>Scan QR Code</h3>
<div id="reader" style="width:300px;"></div>
<script>
function onScanSuccess(decodedText, decodedResult) {
  document.querySelector('input[name="roll_no"]').value = decodedText;
  document.querySelector('form[action="submit_entry.php"]').submit();
  html5QrcodeScanner.clear();
}
var html5QrcodeScanner = new Html5QrcodeScanner(
  "reader", { fps: 10, qrbox: 250 }, false);
html5QrcodeScanner.render(onScanSuccess);
</script>

<form id="search-form" method="GET" action="">
  <input type="text" id="search-input" name="search" placeholder="Search by Name or Roll Number" value="<?= htmlspecialchars($search) ?>">
  <button type="submit" id="search-button">Search</button>
  <?php if($search): ?>
    <button type="button" onclick="window.location='<?= basename(__FILE__) ?>';">Clear</button>
  <?php endif; ?>
</form>

<h3>Today's Entry Logs (<?= date('d M Y') ?>)</h3>
<table border="1">
  <thead>
    <tr><th>Name</th><th>Roll No</th><th>Dept</th><th>Entry Time</th><th>Status</th><th>Fine</th><th>Fine Paid</th><th>Mark as Paid</th></tr>
  </thead>
  <tbody>
  <?php if ($res->num_rows === 0): ?>
    <tr><td colspan="8">No entries found.</td></tr>
  <?php endif; ?>
  <?php while ($row = $res->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['name']) ?></td>
      <td><?= htmlspecialchars($row['roll_no']) ?></td>
      <td><?= htmlspecialchars($row['department']) ?></td>
      <td><?= htmlspecialchars($row['entry_time']) ?></td>
      <td><?= htmlspecialchars($row['status']) ?></td>
      <td><?= $row['fine'] ? '₹' . htmlspecialchars($row['fine']) : '-' ?></td>
      <td><?= $row['fine_paid'] ? '<strong style="color:green;">Paid</strong>' : '<span style="color:red;">Unpaid</span>' ?></td>
      <td>
        <?php if ($row['fine'] && !$row['fine_paid']): ?>
          <form action="mark_paid.php" method="POST">
            <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
            <button type="submit">Mark Paid</button>
          </form>
        <?php else: ?>
          <span style="color:#777;">-</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<h3>Summary: Entries & Fines (Last 3 Years)</h3>
<table border="1">
  <thead>
    <tr><th>Name</th><th>Roll No</th><th>Department</th><th>Total Entries</th><th>Late Entries</th><th>Total Fine</th><th>Fine Paid</th><th>Actions</th><th>History</th></tr>
  </thead>
  <tbody>
  <?php while ($summary = $summary_res->fetch_assoc()):
    $due = $summary['total_fines'] - $summary['fine_paid'];
  ?>
    <tr>
      <td><?= htmlspecialchars($summary['name']) ?></td>
      <td><?= htmlspecialchars($summary['roll_no']) ?></td>
      <td><?= htmlspecialchars($summary['department']) ?></td>
      <td><?= $summary['total_entries'] ?></td>
      <td><?= $summary['total_late_entries'] ?></td>
      <td>₹<?= $summary['total_fines'] ?: 0 ?></td>
      <td>₹<?= $summary['fine_paid'] ?: 0 ?></td>
      <td>
        <?php if ($due > 0): ?>
          <button onclick="payFine('<?= $summary['student_id'] ?>', <?= $due ?>)">Pay Now (₹<?= $due ?>)</button>
        <?php else: ?>
          <span style="color:green; font-weight:600;">No Due</span>
        <?php endif; ?>
      </td>
      <td>
        <a href="student_history.php?student_id=<?= $summary['student_id'] ?>" target="_blank">
          <button>View History</button>
        </a>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<script>
function payFine(student_id, amount) {
  var options = {
    "key": "rzp_test_k8YtiLfoA2rXm3",
    "amount": amount * 100,
    "currency": "INR",
    "name": "College Entry Fine",
    "description": "Late Entry Fine Payment",
    "handler": function (response) {
      fetch("payment_success.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `student_id=${student_id}&amount=${amount}&payment_id=${response.razorpay_payment_id}`
      })
      .then(res => res.text())
      .then(data => {
        if (data === "1") {
          alert("Payment Successful!");
          location.reload();
        } else {
          alert("Error recording payment.");
        }
      });
    },
    "prefill": {
      "name": "Student",
      "email": "",
      "contact": "9999999999"
    },
    "theme": { "color": "#3399cc" }
  };
  new Razorpay(options).open();
}
</script>

</body>
</html>
