<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php';
date_default_timezone_set('Asia/Kolkata');

// Fetch student details
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
}

// Update student details
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $roll_no = $_POST['roll_no'];
    $department = $_POST['department'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];

    $stmt = $conn->prepare("UPDATE students SET name = ?, roll_no = ?, department = ?, phone = ?, address = ?, parent_name = ?, parent_phone = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $name, $roll_no, $department, $phone, $address, $parent_name, $parent_phone, $id);

    if ($stmt->execute()) {
        $message = "<p style='color:green;'>Student details updated successfully!</p>";
    } else {
        $message = "<p style='color:red;'>Error: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Edit Student Details</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f8;
      padding: 40px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      background: #ffffff;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      width: 100%;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    form label {
      font-weight: bold;
      color: #444;
      margin-top: 15px;
      display: block;
    }

    input[type="text"],
    select,
    textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin-top: 8px;
      margin-bottom: 10px;
      transition: border 0.3s;
      font-size: 15px;
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: #4CAF50;
      outline: none;
    }

    button {
      background: #4CAF50;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 15px;
      width: 100%;
      font-size: 16px;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #45a049;
    }

    .message {
      margin-bottom: 15px;
      text-align: center;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Edit Student Details</h2>
    
    <?php if (isset($message)) echo "<div class='message'>$message</div>"; ?>

    <form method="POST" action="">
      <label>Student Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>

      <label>Roll Number</label>
      <input type="text" name="roll_no" value="<?= htmlspecialchars($student['roll_no']) ?>" required>

      <label>Department</label>
      <select name="department" required>
        <option value="Computer Science" <?= ($student['department'] == 'Computer Science') ? 'selected' : '' ?>>Computer Science</option>
        <option value="Mechatronics" <?= ($student['department'] == 'Mechatronics') ? 'selected' : '' ?>>Mechatronics</option>
        <option value="Tool & Die" <?= ($student['department'] == 'Tool & Die') ? 'selected' : '' ?>>Tool & Die</option>
        <option value="Electronics" <?= ($student['department'] == 'Electronics') ? 'selected' : '' ?>>Electronics</option>
      </select>

      <label>Phone Number</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($student['phone']) ?>" required>

      <label>Address</label>
      <textarea name="address" required><?= htmlspecialchars($student['address']) ?></textarea>

      <label>Parent's Name</label>
      <input type="text" name="parent_name" value="<?= htmlspecialchars($student['parent_name']) ?>" required>

      <label>Parent's Phone Number</label>
      <input type="text" name="parent_phone" value="<?= htmlspecialchars($student['parent_phone']) ?>" required>

      <button type="submit" name="update">Update Student</button>
    </form>
  </div>
</body>
</html>
