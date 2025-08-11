<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php'; // Your DB connection
date_default_timezone_set('Asia/Kolkata');

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'month';

$today = date('Y-m-d');
switch ($filter) {
    case 'day': $start_date = $today; break;
    case 'week': $start_date = date('Y-m-d', strtotime('monday this week')); break;
    case 'month': $start_date = date('Y-m-01'); break;
    case 'year': $start_date = date('Y-01-01'); break;
    case '3years': $start_date = date('Y-m-d', strtotime('-3 years')); break;
    default: $start_date = date('Y-m-01'); break;
}

// Base SQL to get entries with fines greater than zero from start_date onwards
$base_sql = "
    SELECT 
        e.id, e.student_id, s.name, s.roll_no, s.department, e.entry_date, e.entry_time, e.fine, e.fine_paid
    FROM entries e
    JOIN students s ON s.id = e.student_id
    WHERE e.entry_date >= ? AND e.fine > 0
";

// Prepare bindings
$params = [$start_date];
$types = 's';
$search_sql = '';

if ($search) {
    $search_sql = " AND (s.name LIKE ? OR s.roll_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

// Get Paid Fines
$paid_sql = $base_sql . " AND e.fine_paid = 1 $search_sql ORDER BY e.entry_date DESC, e.entry_time DESC";
$stmt_paid = $conn->prepare($paid_sql);
$stmt_paid->bind_param($types, ...$params);
$stmt_paid->execute();
$paid_result = $stmt_paid->get_result();

$paid_records = [];
$total_paid_fines = 0;
$paid_students = [];

while ($row = $paid_result->fetch_assoc()) {
    $total_paid_fines += $row['fine'];
    $paid_students[$row['roll_no']] = true;
    $paid_records[] = $row;
}

// Get Due Fines
$due_sql = $base_sql . " AND e.fine_paid = 0 $search_sql ORDER BY e.entry_date DESC, e.entry_time DESC";
$stmt_due = $conn->prepare($due_sql);
$stmt_due->bind_param($types, ...$params);
$stmt_due->execute();
$due_result = $stmt_due->get_result();

$due_records = [];
$total_due_fines = 0;
$due_students = [];

while ($row = $due_result->fetch_assoc()) {
    $total_due_fines += $row['fine'];
    $due_students[$row['roll_no']] = true;
    $due_records[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8" />
    <title>Fine Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 30px;
            background-color: #f9f9f9;
        }
        h2, h3 { color: #333; }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        th {
            background-color: #444;
            color: #fff;
            padding: 12px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        form {
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        select, input[type="text"] {
            padding: 8px;
            margin-right: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        button {
            padding: 8px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .summary {
            background: #fff;
            padding: 15px;
            border-left: 5px solid #28a745;
            margin-bottom: 20px;
        }
        .due-summary {
            border-left-color: #dc3545;
        }
        .action-form {
            display: inline;
            margin: 0 5px;
        }
    </style>
</head>
<body>

<h2>Fine Report</h2>

<form method="GET" action="">
    <label for="filter">Filter by:</label>
    <select name="filter" id="filter">
        <option value="day" <?= $filter == 'day' ? 'selected' : '' ?>>Today</option>
        <option value="week" <?= $filter == 'week' ? 'selected' : '' ?>>This Week</option>
        <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>This Month</option>
        <option value="year" <?= $filter == 'year' ? 'selected' : '' ?>>This Year</option>
        <option value="3years" <?= $filter == '3years' ? 'selected' : '' ?>>Last 3 Years</option>
    </select>

    <input type="text" name="search" placeholder="Search by Name or Roll No" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<div class="summary">
    <h3>Paid Fines Summary</h3>
    <p>Total Fines Collected: ₹<?= number_format($total_paid_fines, 2) ?></p>
    <p>Number of Students Paid: <?= count($paid_students) ?></p>
</div>

<?php if (!empty($paid_records)): ?>
    <h3>Paid Fines</h3>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Roll No</th>
            <th>Department</th>
            <th>Entry Date</th>
            <th>Entry Time</th>
            <th>Fine Paid (₹)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($paid_records as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['entry_date']) ?></td>
                <td><?= htmlspecialchars($row['entry_time']) ?></td>
                <td><?= number_format($row['fine'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No paid fine records found.</p>
<?php endif; ?>

<div class="summary due-summary">
    <h3>Due Fines Summary</h3>
    <p>Total Fines Due: ₹<?= number_format($total_due_fines, 2) ?></p>
    <p>Number of Students with Dues: <?= count($due_students) ?></p>
</div>

<?php if (!empty($due_records)): ?>
    <h3>Unpaid/Due Fines</h3>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Roll No</th>
            <th>Department</th>
            <th>Entry Date</th>
            <th>Entry Time</th>
            <th>Due Amount (₹)</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($due_records as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['entry_date']) ?></td>
                <td><?= htmlspecialchars($row['entry_time']) ?></td>
                <td><?= number_format($row['fine'], 2) ?></td>
                <td>
                    <form class="action-form" action="mark_paid.php" method="POST" onsubmit="return confirm('Mark this fine as paid?');" style="display:inline-block;">
                        <input type="hidden" name="entry_id" value="<?= $row['id'] ?>">
                        <button type="submit" aria-label="Mark fine as paid for <?= htmlspecialchars($row['name']) ?>">Mark Paid</button>
                    </form>

                    <button 
                      onclick="payFine('<?= htmlspecialchars($row['student_id']) ?>', <?= $row['fine'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" 
                      aria-label="Pay fine of ₹<?= number_format($row['fine'], 2) ?> for <?= htmlspecialchars($row['name']) ?>"
                      style="margin-left: 8px;"
                    >
                      Pay Now (₹<?= number_format($row['fine'], 2) ?>)
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No unpaid fine records found.</p>
<?php endif; ?>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function payFine(student_id, amount, student_name) {
    if (!student_id || !amount) {
        alert("Invalid payment details.");
        return;
    }
    var options = {
        "key": "rzp_test_k8YtiLfoA2rXm3", // Replace with your live key in production
        "amount": amount * 100, // Amount is in paise
        "currency": "INR",
        "name": "College Entry Fine",
        "description": "Late Entry Fine Payment",
        "handler": function (response) {
            fetch("payment_success.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `student_id=${encodeURIComponent(student_id)}&amount=${encodeURIComponent(amount)}&payment_id=${encodeURIComponent(response.razorpay_payment_id)}`
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
            "name": student_name,
        },
        "theme": {
            "color": "#3399cc"
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
}
</script>

</body>
</html>
