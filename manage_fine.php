<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$student = null;
$fines = [];
$total_fine = 0.00;
$message = "";

// Fetch student info
if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
}

// If student not found, redirect or show error
if (!$student) {
    echo "<p style='color: red;'>Student not found. <a href='search_student.php'>Go back</a></p>";
    exit;
}

// Add fine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_fine'])) {
    $reason = $_POST['reason'];
    $amount = floatval($_POST['amount']);

    $stmt = $conn->prepare("INSERT INTO fines (student_id, reason, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $student_id, $reason, $amount);
    $stmt->execute();

    $message = "Fine added successfully.";
}

// Delete fine
if (isset($_GET['delete_fine'])) {
    $fine_id = intval($_GET['delete_fine']);
    $stmt = $conn->prepare("DELETE FROM fines WHERE id = ?");
    $stmt->bind_param("i", $fine_id);
    $stmt->execute();

    header("Location: manage_fine.php?student_id=$student_id&deleted=1");
    exit;
}

if (isset($_GET['deleted'])) {
    $message = "Fine deleted successfully.";
}

// Fetch all fines
$stmt = $conn->prepare("SELECT * FROM fines WHERE student_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $fines[] = $row;
    $total_fine += $row['amount'];
}
?>

<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Fines - <?= htmlspecialchars($student['name']) ?></title>
    <style>
        body { font-family: Arial; margin: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        th { background: #f2f2f2; }
        .message { background: #e9ffe9; color: green; padding: 10px; margin-bottom: 15px; border-left: 5px solid green; }
        .total { font-weight: bold; margin-top: 20px; }
        input, button { padding: 10px; width: 100%; margin: 5px 0; box-sizing: border-box; }
        .back-btn { margin-top: 20px; display: inline-block; padding: 8px 16px; background: #ddd; text-decoration: none; }
    </style>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 30px;
        background-color: #f8f9fa;
        color: #333;
    }
    h2, h3 {
        color: #2c3e50;
    }
    .message {
        background: #e0f7e9;
        color: #2e7d32;
        padding: 12px;
        margin-bottom: 20px;
        border-left: 5px solid #2e7d32;
        border-radius: 4px;
    }
    .success-banner {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 5px solid #28a745;
        border-radius: 4px;
        font-weight: bold;
        text-align: center;
    }
    form {
        background: #fff;
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    input, button {
        padding: 10px;
        width: 100%;
        margin: 10px 0;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    button {
        background-color: #007bff;
        color: white;
        border: none;
        cursor: pointer;
        font-weight: bold;
    }
    button:hover {
        background-color: #0056b3;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    th, td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: #f1f1f1;
    }
    tr:hover {
        background-color: #f9f9f9;
    }
    .total {
        font-weight: bold;
        margin-top: 20px;
    }
    .back-btn {
        margin-top: 20px;
        display: inline-block;
        padding: 10px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    .back-btn:hover {
        background-color: #5a6268;
    }
    a {
        color: #dc3545;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style>

</head>
<body>

<h2>Fine Management for <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['roll_no']) ?>)</h2>

<?php if ($message): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>

<h3>Add New Fine</h3>
<form method="POST">
    <label>Reason:</label>
    <input type="text" name="reason" required>
    <label>Amount (₹):</label>
    <input type="number" name="amount" step="0.01" required>
    <button type="submit" name="add_fine">Add Fine</button>
</form>

<h3>Fine History</h3>
<table>
    <tr>
        <th>ID</th>
        <th>Reason</th>
        <th>Amount (₹)</th>
        <th>Date</th>
        <th>Action</th>
    </tr>
    <?php if (count($fines) > 0): ?>
        <?php foreach ($fines as $fine): ?>
        <tr>
            <td><?= $fine['id'] ?></td>
            <td><?= htmlspecialchars($fine['reason']) ?></td>
            <td><?= number_format($fine['amount'], 2) ?></td>
            <td><?= date('d-m-Y H:i A', strtotime($fine['created_at'])) ?></td>
            <td>
                <a href="manage_fine.php?student_id=<?= $student_id ?>&delete_fine=<?= $fine['id'] ?>" onclick="return confirm('Delete this fine?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5" style="text-align:center;">No fines found.</td></tr>
    <?php endif; ?>
</table>

<p class="total">Total Fine Due: ₹<?= number_format($total_fine, 2) ?></p>

<a class="back-btn" href="search_student.php">← Back to Search</a>

</body>
</html>
