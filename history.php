<?php
include 'db.php';
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('Asia/Kolkata');

// Fetch distinct departments for dropdown
$departments = [];
$deptResult = $conn->query("SELECT DISTINCT department FROM students ORDER BY department ASC");
while ($d = $deptResult->fetch_assoc()) {
    $departments[] = $d['department'];
}

// Filters
$whereClauses = [];
$params = [];
$paramTypes = '';

$monthFilter = $_GET['month'] ?? '';
$nameFilter = $_GET['name'] ?? '';
$deptFilter = $_GET['department'] ?? '';
$rollFilter = $_GET['roll_no'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

// Month filter
if (!empty($monthFilter)) {
    $monthNum = date('m', strtotime($monthFilter));
    $whereClauses[] = "MONTH(e.entry_date) = ?";
    $params[] = $monthNum;
    $paramTypes .= 'i';
}

// Name, Roll No, Department filters
if (!empty($nameFilter)) {
    $whereClauses[] = "s.name LIKE ?";
    $params[] = "%$nameFilter%";
    $paramTypes .= 's';
}
if (!empty($deptFilter)) {
    $whereClauses[] = "s.department = ?";
    $params[] = $deptFilter;
    $paramTypes .= 's';
}
if (!empty($rollFilter)) {
    $whereClauses[] = "s.roll_no LIKE ?";
    $params[] = "%$rollFilter%";
    $paramTypes .= 's';
}

// Date range filter
if (!empty($fromDate) && !empty($toDate)) {
    $whereClauses[] = "e.entry_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $paramTypes .= 'ss';
}

$sql = "
    SELECT s.name, s.roll_no, s.department, 
           e.entry_date, e.entry_time, e.status, e.fine, e.fine_paid
    FROM entries e 
    JOIN students s ON e.student_id = s.id
";
if (count($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY e.entry_date DESC, e.entry_time DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=entry_logs.csv');
    $output = fopen("php://output", "w");
    fputcsv($output, ['Name', 'Roll No', 'Department', 'Date', 'Time', 'Status', 'Fine', 'Fine Status']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['roll_no'],
            $row['department'],
            $row['entry_date'],
            $row['entry_time'],
            $row['status'],
            $row['fine'] > 0 ? '₹' . $row['fine'] : 'No Fine',
            $row['fine'] > 0 ? ($row['fine_paid'] ? 'Paid' : 'Unpaid') : '-'
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>All Entry Logs</title>
  <style>
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
    th { background-color: #f0f0f0; }
    body { font-family: Arial, sans-serif; padding: 30px; }
    .search-form { margin-bottom: 15px; }
    .search-form input, .search-form select, .search-form button {
      padding: 7px;
      font-size: 15px;
      margin-right: 10px;
    }
  </style>
  <style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 30px;
    background-color: #f8f9fa;
    color: #333;
  }

  h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #444;
  }

  .search-form {
    background: #ffffff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: center;
  }

  .search-form label {
    margin-right: 5px;
    font-weight: bold;
  }

  .search-form input,
  .search-form select,
  .search-form button {
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    outline: none;
  }

  .search-form button {
    background-color: #007bff;
    color: white;
    border: none;
    transition: background 0.3s ease;
    cursor: pointer;
  }

  .search-form button:hover {
    background-color: #0056b3;
  }

  .search-form a button {
    background-color: #28a745;
  }

  .search-form a button:hover {
    background-color: #1e7e34;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 30px;
    background-color: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    border-radius: 10px;
    overflow: hidden;
  }

  th, td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
  }

  th {
    background-color: #343a40;
    color: #ffffff;
    font-weight: 600;
  }

  tr:nth-child(even) {
    background-color: #f2f2f2;
  }

  tr:hover {
    background-color: #f1f1f1;
    transition: background-color 0.3s ease;
  }

  td {
    color: #333;
  }
</style>

</head>
<body>

<h2>All Student Entry Logs</h2>

<form method="GET" class="search-form">
  <label><strong>Filter by Month:</strong></label>
  <input type="text" name="month" placeholder="e.g. May" value="<?= htmlspecialchars($monthFilter) ?>">

  <label><strong>Name:</strong></label>
  <input type="text" name="name" placeholder="Name" value="<?= htmlspecialchars($nameFilter) ?>">

  <label><strong>Department:</strong></label>
  <select name="department">
    <option value="">All</option>
    <?php foreach ($departments as $dept): ?>
      <option value="<?= htmlspecialchars($dept) ?>" <?= $dept == $deptFilter ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
    <?php endforeach; ?>
  </select>

  <label><strong>Roll No:</strong></label>
  <input type="text" name="roll_no" placeholder="Roll No" value="<?= htmlspecialchars($rollFilter) ?>">

  <label><strong>From:</strong></label>
  <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">

  <label><strong>To:</strong></label>
  <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>">

  <button type="submit">Search</button>
  <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"><button type="button">Export CSV</button></a>
  <button type="button" onclick="window.location='<?= basename($_SERVER['PHP_SELF']) ?>'">Reset</button>
</form>

<table>
  <tr>
    <th>Name</th>
    <th>Roll No</th>
    <th>Department</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Fine</th>
    <th>Fine Status</th>
  </tr>
  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['roll_no']) ?></td>
        <td><?= htmlspecialchars($row['department']) ?></td>
        <td><?= htmlspecialchars($row['entry_date']) ?></td>
        <td><?= htmlspecialchars($row['entry_time']) ?></td>
        <td><?= htmlspecialchars($row['status']) ?></td>
        <td><?= $row['fine'] > 0 ? '₹' . htmlspecialchars($row['fine']) : 'No Fine' ?></td>
        <td><?= $row['fine'] > 0 ? ($row['fine_paid'] ? 'Paid' : 'Unpaid') : '-' ?></td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="8">No entries found.</td></tr>
  <?php endif; ?>
</table>

</body>
</html>
