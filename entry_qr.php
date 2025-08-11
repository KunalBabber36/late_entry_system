<?php
session_start();
include 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');
$now = date("H:i:s");

$message = '';
$messageType = ''; // 'success', 'warning', 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll = trim($_POST['roll_number']);

    if (empty($roll)) {
        $message = "❌ Roll number is empty.";
        $messageType = 'error';
    } else {
        // Reset fines older than 6 months
        $conn->query("UPDATE entries SET fine = 0 WHERE entry_date < CURDATE() - INTERVAL 6 MONTH AND fine > 0");

        // Check if student exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE roll_no = ?");
        $stmt->bind_param("s", $roll);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $student_id = $row['id'];

            // Check if entry for today already exists
            $stmt2 = $conn->prepare("SELECT status, fine FROM entries WHERE student_id = ? AND entry_date = ?");
            $stmt2->bind_param("is", $student_id, $today);
            $stmt2->execute();
            $entry_res = $stmt2->get_result();

            if ($entry = $entry_res->fetch_assoc()) {
                // Entry already exists
                $existing_status = htmlspecialchars($entry['status']);
                $existing_fine = (int)$entry['fine'];
                $messageType = 'warning';
                $message = "<h3>Entry Already Marked for Roll: <strong>" . htmlspecialchars($roll) . "</strong> Today</h3>" .
                    "<p>Status: <strong>$existing_status</strong></p>" .
                    "<p>Fine: <strong>₹$existing_fine</strong></p>";
            } else {
                // Determine status
                $status = ($now > "08:30:00") ? "Late" : "On Time";

                // Count past 6-month late entries
                $stmt_late = $conn->prepare("
                    SELECT COUNT(*) as late_count 
                    FROM entries 
                    WHERE student_id = ? 
                      AND status = 'Late' 
                      AND entry_date >= CURDATE() - INTERVAL 6 MONTH
                ");
                $stmt_late->bind_param("i", $student_id);
                $stmt_late->execute();
                $late_res = $stmt_late->get_result();
                $late_data = $late_res->fetch_assoc();
                $late_count = $late_data['late_count'] + ($status === 'Late' ? 1 : 0);

                // Fine logic
                $fine = 0;
                if ($status === 'Late') {
                    if ($late_count == 1) $fine = 100;
                    elseif ($late_count == 2) $fine = 250;
                    elseif ($late_count >= 3) $fine = 500;
                }

                // Insert entry
                $stmt3 = $conn->prepare("INSERT INTO entries (student_id, entry_date, entry_time, status, fine, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt3->bind_param("isssi", $student_id, $today, $now, $status, $fine);
                $stmt3->execute();

                $messageType = 'success';
                $message = "<h3>Entry Marked for Roll: <strong>" . htmlspecialchars($roll) . "</strong></h3>" .
                    "<p>Status: <strong>$status</strong></p>" .
                    "<p>Fine: <strong>₹$fine</strong></p>";

                $stmt3->close();
                $stmt_late->close();
            }

            $stmt2->close();
        } else {
            $messageType = 'error';
            $message = "❌ Invalid Roll Number Scanned: <strong>" . htmlspecialchars($roll) . "</strong>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta charset="UTF-8" />
<title>Gate Entry System</title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f0f2f5;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 40px;
        color: #333;
    }

    h2 {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 25px;
    }

    #reader {
        width: 320px;
        height: 320px;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        background: white;
        overflow: hidden;
    }

    .message-box {
        max-width: 360px;
        background: white;
        padding: 20px 25px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        line-height: 1.5;
    }

    .success { border-left: 6px solid #28a745; color: #155724; background-color: #d4edda; }
    .warning { border-left: 6px solid #ffc107; color: #856404; background-color: #fff3cd; }
    .error { border-left: 6px solid #dc3545; color: #721c24; background-color: #f8d7da; }

    p { margin: 8px 0; font-size: 1rem; }
    h3 { margin-top: 0; font-weight: 600; }

    @media (max-width: 400px) {
        #reader { width: 90vw; height: 90vw; }
        .message-box { max-width: 90vw; }
    }
</style>
</head>
<body>

<h2>Scan Student QR Code</h2>

<?php if ($message): ?>
    <div class="message-box <?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div id="reader"></div>

<form method="post" id="entryForm" autocomplete="off">
    <input type="hidden" name="roll_number" id="roll_number" />
</form>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
function onScanSuccess(decodedText) {
    document.getElementById("roll_number").value = decodedText;
    document.getElementById("entryForm").submit();
}
let scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
scanner.render(onScanSuccess);
</script>

</body>
</html>
