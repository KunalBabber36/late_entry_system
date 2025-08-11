<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$student_id = $_GET['student_id'] ?? '';
if (!$student_id) {
    echo "Student ID is required.";
    exit;
}

// Time ranges
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$six_months_ago = date('Y-m-d', strtotime('-6 months'));

// Get student details
$student_stmt = $conn->prepare("SELECT name, roll_no, department FROM students WHERE id = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

function get_entries($conn, $student_id, $from_date, $to_date, $label) {
    $stmt = $conn->prepare("
        SELECT entry_date, entry_time, status, fine, fine_paid
        FROM entries
        WHERE student_id = ? AND entry_date BETWEEN ? AND ?
        ORDER BY entry_date DESC
    ");
    $stmt->bind_param("iss", $student_id, $from_date, $to_date);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h3>$label</h3>";
    if ($result->num_rows === 0) {
        echo "<p>No records found.</p>";
        return;
    }

    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr><th>Date</th><th>Time</th><th>Status</th><th>Fine</th><th>Fine Paid</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>{$row['entry_date']}</td>
            <td>{$row['entry_time']}</td>
            <td>{$row['status']}</td>
            <td>" . ($row['fine'] ? '₹' . $row['fine'] : '-') . "</td>
            <td>" . ($row['fine_paid'] ? 'Paid' : 'Unpaid') . "</td>
        </tr>";
    }
    echo "</table><br>";
}
?>

<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fine History - <?= htmlspecialchars($student['name']) ?></title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 30px;
            background: #f4f6f9;
            color: #333;
        }
        h2 { margin-bottom: 10px; }
        .student-info {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .section {
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .section-header {
            background-color: #007BFF;
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }
        .section-header:hover {
            background-color: #0056b3;
        }
        .section-content {
            display: none;
            padding: 20px;
            animation: fadeIn 0.4s ease-in-out;
        }
        table {
            width: 100%;
            margin-top: 10px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
        }
        .badge.paid { background-color: #28a745; color: #fff; }
        .badge.unpaid { background-color: #dc3545; color: #fff; }
        .badge.late { background-color: #ffc107; color: #212529; }
        .badge.ontime { background-color: #17a2b8; color: #fff; }
        .totals {
            margin-top: 12px;
            font-weight: bold;
            font-size: 15px;
        }
        .download-btn {
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        .download-btn i {
            margin-right: 6px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<h2>Fine History for <?= htmlspecialchars($student['name']) ?> (<?= $student['roll_no'] ?>)</h2>
<div class="student-info">
    <p><strong>Department:</strong> <?= htmlspecialchars($student['department']) ?></p>
</div>

<?php
function get_entries_section($conn, $student_id, $from_date, $to_date, $label, $section_id) {
    $stmt = $conn->prepare("SELECT entry_date, entry_time, status, fine, fine_paid FROM entries WHERE student_id = ? AND entry_date BETWEEN ? AND ? ORDER BY entry_date DESC");
    $stmt->bind_param("iss", $student_id, $from_date, $to_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_fine = 0;
    $total_paid = 0;

    ob_start();
    echo "<table id='table_$section_id' class='display'><thead><tr><th>Date</th><th>Time</th><th>Status</th><th>Fine</th><th>Fine Paid</th></tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        $total_fine += $row['fine'];
        $total_paid += $row['fine_paid'] ? $row['fine'] : 0;
        $statusBadge = $row['status'] === 'Late' ? 'late' : 'ontime';
        $paidBadge = $row['fine_paid'] ? 'paid' : 'unpaid';
        echo "<tr>
            <td>{$row['entry_date']}</td>
            <td>{$row['entry_time']}</td>
            <td><span class='badge $statusBadge'>{$row['status']}</span></td>
            <td>" . ($row['fine'] ? '₹' . $row['fine'] : '-') . "</td>
            <td><span class='badge $paidBadge'>" . ($row['fine_paid'] ? 'Paid' : 'Unpaid') . "</span></td>
        </tr>";
    }
    echo "</tbody></table>";
    $tableHTML = ob_get_clean();

    echo "<div class='section'>
        <div class='section-header' onclick=\"toggleSection('$section_id')\">
            $label <i class='fas fa-chevron-down'></i>
        </div>
        <div class='section-content' id='$section_id'>
            " . ($result->num_rows === 0 ? "<p>No records found.</p>" : $tableHTML) . "
            <div class='totals'>
                Total Fine: ₹$total_fine | Paid: ₹$total_paid | Due: ₹" . ($total_fine - $total_paid) . "
            </div>
            " . ($result->num_rows ? "<button class='download-btn' onclick=\"exportToCSV('table_$section_id', '$label')\"><i class='fas fa-download'></i>Export CSV</button>" : "") . "
        </div>
    </div>";
}

get_entries_section($conn, $student_id, $today, $today, 'Today', 'section_today');
get_entries_section($conn, $student_id, $week_start, $today, 'This Week', 'section_week');
get_entries_section($conn, $student_id, $month_start, $today, 'This Month', 'section_month');
get_entries_section($conn, $student_id, $six_months_ago, $today, 'Last 6 Months', 'section_sixmonths');
?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    function toggleSection(id) {
        const el = document.getElementById(id);
        if (el.style.display === "block") {
            el.style.display = "none";
        } else {
            el.style.display = "block";
        }
    }

    function exportToCSV(tableId, title) {
        let table = document.getElementById(tableId);
        let rows = table.querySelectorAll("tr");
        let csv = Array.from(rows).map(row =>
            Array.from(row.cells).map(cell => `"${cell.textContent.trim()}"`).join(",")
        ).join("\n");

        let blob = new Blob([csv], { type: "text/csv" });
        let link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `${title.replace(/\s+/g, "_")}_Fine_History.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Initialize DataTables
    $(document).ready(function () {
        $('table.display').DataTable();
    });
</script>
</body>
</html>
