<?php
include 'db.php'; 
date_default_timezone_set('Asia/Kolkata');
?>

<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Register Student</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f8;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .container {
      background: #fff;
      padding: 30px 40px;
      margin-top: 50px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 600px;
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #333;
    }

    label {
      display: block;
      margin-top: 15px;
      color: #555;
      font-weight: 600;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }

    input:focus, select:focus, textarea:focus {
      border-color: #007bff;
      outline: none;
    }

    button {
      margin-top: 20px;
      background: #007bff;
      color: white;
      padding: 12px 18px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #0056b3;
    }

    .message {
      margin-top: 20px;
      padding: 12px;
      border-radius: 5px;
      font-weight: 500;
    }

    .message.success {
      background: #e6ffed;
      color: #2d7a2d;
      border-left: 5px solid #2d7a2d;
    }

    .message.error {
      background: #ffe6e6;
      color: #a94442;
      border-left: 5px solid #a94442;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Student Registration Form</h2>
    <form method="POST" action="">
      <label for="name">Student Name</label>
      <input type="text" name="name" id="name" required>

      <label for="roll_no">Roll Number</label>
      <input type="text" name="roll_no" id="roll_no" required pattern="^(rntc|RNTC)[A-Za-z0-9]+$" title="Roll number must start with RNTC or rntc followed by alphanumeric characters">

      <label for="department">Department</label>
      <select name="department" id="department" required>
        <option value="">Select Department</option>
        <option value="Computer Science">Computer Science</option>
        <option value="Mechatronics">Mechatronics</option>
        <option value="Tool & Die">Tool & Die</option>
        <option value="Electronics">Electronics</option>
      </select>

      <label for="phone">Phone Number</label>
      <input type="tel" name="phone" id="phone" required pattern="[0-9]{10}" title="Phone number must be 10 digits">

      <label for="address">Address</label>
      <textarea name="address" id="address" required></textarea>

      <label for="parent_name">Parent's Name</label>
      <input type="text" name="parent_name" id="parent_name" required>

      <label for="parent_phone">Parent's Phone Number</label>
      <input type="tel" name="parent_phone" id="parent_phone" required pattern="[0-9]{10}" title="Parent's phone number must be 10 digits">

      <button type="submit" name="submit">Register Student</button>
    </form>

<?php
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $roll_no = trim($_POST['roll_no']);
    $department = $_POST['department'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $parent_name = trim($_POST['parent_name']);
    $parent_phone = trim($_POST['parent_phone']);

    if (empty($name) || empty($roll_no) || empty($department) || empty($phone) || empty($address) || empty($parent_name) || empty($parent_phone)) {
        echo "<div class='message error'>All fields are required!</div>";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        echo "<div class='message error'>Invalid phone number. It should be 10 digits long.</div>";
    } elseif (!preg_match("/^[0-9]{10}$/", $parent_phone)) {
        echo "<div class='message error'>Invalid parent's phone number. It should be 10 digits long.</div>";
    } elseif (!preg_match("/^rntc/i", $roll_no)) {
        echo "<div class='message error'>Invalid roll number. It should start with 'RNTC' or 'rntc'.</div>";
    } else {
        $stmt_check = $conn->prepare("SELECT * FROM students WHERE roll_no = ?");
        $stmt_check->bind_param("s", $roll_no);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows > 0) {
            echo "<div class='message error'>Roll number already exists. Please enter a unique roll number.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO students (name, roll_no, department, phone, address, parent_name, parent_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $name, $roll_no, $department, $phone, $address, $parent_name, $parent_phone);

            if ($stmt->execute()) {
                echo "<div class='message success'>Student Registered Successfully!</div>";
            } else {
                echo "<div class='message error'>Error: " . $conn->error . "</div>";
            }

            $stmt->close();
        }

        $stmt_check->close();
    }
}
$conn->close();
?>
  </div>
</body>
</html>
