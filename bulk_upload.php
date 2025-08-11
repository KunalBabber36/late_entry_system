<?php
session_start();
include 'db.php';
include 'phpqrcode/qrlib.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = "";

if (isset($_POST['upload'])) {
    if (is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file); // skip header row

        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 7) continue;

            list($name, $roll_no, $department, $phone, $address, $parent_name, $parent_phone) = $row;
            $roll_no = strtoupper(trim($roll_no));
            if (empty($roll_no)) continue;

            // Check duplicate
            $stmt = $conn->prepare("SELECT id FROM students WHERE roll_no = ?");
            $stmt->bind_param("s", $roll_no);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 0) {
                $stmt->close();

                $insert = $conn->prepare("INSERT INTO students (name, roll_no, department, phone, address, parent_name, parent_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert->bind_param("sssssss", $name, $roll_no, $department, $phone, $address, $parent_name, $parent_phone);
                if ($insert->execute()) {
                    if (!file_exists('qr-codes')) {
                        mkdir('qr-codes', 0755, true);
                    }
                    QRcode::png($roll_no, "qr-codes/$roll_no.png", 'L', 6, 2);
                    $count++;
                }
                $insert->close();
            } else {
                $stmt->close();
            }
        }

        fclose($file);
        $message = "✅ Successfully imported $count students and generated QR codes.";
    } else {
        $message = "❌ Failed to upload file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bulk Upload Students</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; display: flex; justify-content: center; padding: 30px; }
        .container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px #ccc; width: 100%; max-width: 500px; }
        h2 { text-align: center; margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background-color: #e6ffed; color: #2d7a2d; }
        .error { background-color: #ffe6e6; color: #cc0000; }
        input[type="file"], button { width: 100%; padding: 12px; margin-top: 10px; border-radius: 5px; font-size: 1rem; }
        button { background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="container">
    <h2>Upload CSV File</h2>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="upload">Upload & Generate QR</button>
    </form>
</div>
</body>
</html>
