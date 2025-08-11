<?php session_start();

include 'db.php';
include 'phpqrcode/qrlib.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $name = trim($_POST['name']);
    $roll_no = strtoupper(trim($_POST['roll_no']));  // Normalize to uppercase
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $parent_name = trim($_POST['parent_name']);
    $parent_phone = trim($_POST['parent_phone']);

    // Validation
    if (!preg_match('/^RNTC/i', $roll_no)) {
        $errors[] = "Roll number must start with 'RNTC' (e.g., RNTC0823029).";
    }

    if (!ctype_digit($phone) || strlen($phone) < 10) {
        $errors[] = "Phone number must be numeric and at least 10 digits.";
    }

    if (!ctype_digit($parent_phone) || strlen($parent_phone) < 10) {
        $errors[] = "Parent phone number must be numeric and at least 10 digits.";
    }

    // Check for duplicate roll number
    $check = $conn->prepare("SELECT id FROM students WHERE roll_no = ?");
    $check->bind_param("s", $roll_no);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = "Roll number already exists!";
    }
    $check->close();

    // If no errors, insert and generate QR
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO students (name, roll_no, department, phone, address, parent_name, parent_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $roll_no, $department, $phone, $address, $parent_name, $parent_phone);

        if ($stmt->execute()) {
            // Create directory if not exists
            $dir = "qr-codes";
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $qr_file = "$dir/$roll_no.png";
            QRcode::png($roll_no, $qr_file, 'L', 6, 2);

            $success = "✅ Registration Successful!";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register Student</title>
    <style>
        /* Reset and base */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 8px 20px rgb(0 0 0 / 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        form label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
        }
        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 18px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
            resize: vertical;
        }
        input[type="text"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 8px rgba(74,144,226,0.3);
        }
        textarea {
            min-height: 80px;
        }
        .btn {
            width: 100%;
            background-color: #4a90e2;
            color: white;
            border: none;
            padding: 14px 0;
            font-size: 1.1rem;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #357abd;
        }
        .error, .success {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .error {
            background-color: #ffe6e6;
            color: #cc0000;
            border: 1px solid #cc0000;
        }
        .success {
            background-color: #e6f4ea;
            color: #2d7a2d;
            border: 1px solid #2d7a2d;
            text-align: center;
        }
        .qr-code-container {
            text-align: center;
            margin-top: 20px;
        }
        .qr-code-container img {
            max-width: 180px;
            border: 2px solid #ddd;
            padding: 8px;
            border-radius: 8px;
            background: #fff;
        }
        .download-link {
            display: inline-block;
            margin-top: 12px;
            font-size: 1rem;
            text-decoration: none;
            color: #4a90e2;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .download-link:hover {
            color: #357abd;
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .container {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register Student</h2>

        <?php
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<div class='error'>❌ $error</div>";
            }
        }

        if ($success) {
            echo "<div class='success'>$success</div>";
            echo "<div class='qr-code-container'>";
            echo "<p>Roll No: <strong>" . htmlspecialchars($roll_no) . "</strong></p>";
            echo "<p>QR Code for entry:</p>";
            echo "<img src='$qr_file' alt='QR Code'>";
            echo "<br><a class='download-link' href='$qr_file' download>📥 Download QR Code</a>";
            echo "</div><br>";
        }
        ?>

        <form method="POST" novalidate>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required placeholder="Full name" />

            <label for="roll_no">Roll Number:</label>
            <input type="text" id="roll_no" name="roll_no" required placeholder="e.g., RNTC0823029" />

            <label for="department">Department:</label>
            <select id="department" name="department" required>
                <option value="" disabled selected>Select Department</option>
                <option value="Computer Science">Computer Science</option>
                <option value="Mechatronics">Mechatronics</option>
                <option value="Tool & Die">Tool & Die</option>
                <option value="Electronics">Electronics</option>
            </select>

            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" required placeholder="Digits only" />

            <label for="address">Address:</label>
            <textarea id="address" name="address" placeholder="Enter your address"></textarea>

            <label for="parent_name">Parent Name:</label>
            <input type="text" id="parent_name" name="parent_name" placeholder="Enter your parent name" />

            <label for="parent_phone">Parent Phone:</label>
            <input type="text" id="parent_phone" name="parent_phone" required placeholder="Digits only" />

            <button type="submit" class="btn">Register</button>
        </form>
    </div>
</body>
</html>
