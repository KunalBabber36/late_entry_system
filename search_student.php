<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php';
date_default_timezone_set('Asia/Kolkata');

$search_results = [];
$success_message = "";
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: search_student.php?deleted=1");
    exit;
}

if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_message = "Student record deleted successfully.";
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 1 && isset($_SESSION['last_query'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=students.csv');
    $output = fopen("php://output", "w");
    fputcsv($output, ['ID', 'Name', 'Roll Number', 'Department', 'Phone', 'Address', 'Parent Name', 'Parent Phone']);

    $result = $conn->query($_SESSION['last_query']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle search
$search_sql = "";
$total_records = 0;
if (isset($_POST['search']) || isset($_SESSION['last_search'])) {
    $search_value = $_POST['search_value'] ?? $_SESSION['last_search']['value'];
    $search_by = $_POST['search_by'] ?? $_SESSION['last_search']['by'];

    $_SESSION['last_search'] = ['value' => $search_value, 'by' => $search_by];

    if ($search_by == 'roll_no' || $search_by == 'name' || $search_by == 'department') {
        $column = $conn->real_escape_string($search_by);
        $search_param = '%' . $conn->real_escape_string($search_value) . '%';

        $query = "SELECT * FROM students WHERE $column LIKE '$search_param' LIMIT $limit OFFSET $offset";
        $search_sql_all = "SELECT * FROM students WHERE $column LIKE '$search_param'";
        $_SESSION['last_query'] = $search_sql_all;

        $result = $conn->query($query);
        $total_count_result = $conn->query("SELECT COUNT(*) as count FROM students WHERE $column LIKE '$search_param'");
        $total_records = $total_count_result->fetch_assoc()['count'];

        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
}

// Fetch all students (for full list below)
$all_students = [];
$all_result = $conn->query("SELECT * FROM students ORDER BY id ASC");
while ($row = $all_result->fetch_assoc()) {
    $all_students[] = $row;
}
?>


<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Search Student</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background-color: #f2f2f2; cursor: pointer; }
    input, select, button { padding: 10px; margin-top: 10px; width: 100%; box-sizing: border-box; }
    .success { background: #d4edda; padding: 10px; color: #155724; margin: 10px 0; border-left: 4px solid #28a745; }
    .pagination a { padding: 6px 12px; border: 1px solid #ccc; margin-right: 5px; text-decoration: none; }
    .pagination a.active { background-color: #007bff; color: white; }
    .export-btn { margin-top: 10px; display: inline-block; padding: 10px 15px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px; }
    body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 40px;
    background-color: #f9f9f9;
    color: #333;
}

h2 {
    margin-bottom: 10px;
    color: #2c3e50;
}

form {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

input[type="text"], select, button {
    padding: 10px;
    margin-top: 10px;
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    background-color: #28a745;
    color: #fff;
    font-weight: bold;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #218838;
}

.export-btn {
    margin: 15px 0;
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    transition: background 0.3s ease;
}

.export-btn:hover {
    background: #0056b3;
}

.success {
    background: #d4edda;
    padding: 15px;
    color: #155724;
    margin-bottom: 20px;
    border-left: 5px solid #28a745;
    border-radius: 5px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    margin-top: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

th {
    background-color: #f4f4f4;
    font-weight: bold;
    color: #2c3e50;
}

tr:hover {
    background-color: #f1f1f1;
}

td a {
    color: #007bff;
    text-decoration: none;
    margin: 0 4px;
}

td a:hover {
    text-decoration: underline;
}

.pagination {
    margin-top: 20px;
    text-align: center;
}

.pagination a {
    padding: 8px 14px;
    margin: 0 5px;
    border: 1px solid #ccc;
    background-color: #fff;
    color: #333;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination a.active,
.pagination a:hover {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

  </style>
</head>
<body>

<h2>Search Student</h2>

<?php if ($success_message): ?>
    <div class="success"><?= $success_message ?></div>
<?php endif; ?>

<form method="POST" action="">
  <label for="search_value">Search by Roll Number, Name, or Department:</label>
  <input type="text" name="search_value" required value="<?= htmlspecialchars($_SESSION['last_search']['value'] ?? '') ?>">
  <label for="search_by">Search By:</label>
  <select name="search_by" required>
    <option value="roll_no" <?= (($_SESSION['last_search']['by'] ?? '') === 'roll_no') ? 'selected' : '' ?>>Roll Number</option>
    <option value="name" <?= (($_SESSION['last_search']['by'] ?? '') === 'name') ? 'selected' : '' ?>>Name</option>
    <option value="department" <?= (($_SESSION['last_search']['by'] ?? '') === 'department') ? 'selected' : '' ?>>Department</option>
  </select>
  <button type="submit" name="search">Search</button>
</form>

<?php if (!empty($search_results)): ?>
  <a class="export-btn" href="?export=1">Export to CSV</a>
  <table>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Roll Number</th>
      <th>Department</th>
      <th>Phone</th>
      <th>Address</th>
      <th>Parent's Name</th>
      <th>Parent's Phone</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($search_results as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['id']) ?></td>
      <td><?= htmlspecialchars($row['name']) ?></td>
      <td><?= htmlspecialchars($row['roll_no']) ?></td>
      <td><?= htmlspecialchars($row['department']) ?></td>
      <td><?= htmlspecialchars($row['phone']) ?></td>
      <td><?= htmlspecialchars($row['address']) ?></td>
      <td><?= htmlspecialchars($row['parent_name']) ?></td>
      <td><?= htmlspecialchars($row['parent_phone']) ?></td>
      <td>
        <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a> |
        <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a> |
        <a href="manage_fine.php?student_id=<?= $row['id'] ?>">Fines</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <!-- Pagination -->
  <div class="pagination">
    <?php for ($i = 1; $i <= ceil($total_records / $limit); $i++): ?>
      <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php elseif (isset($_POST['search'])): ?>
  <p>No results found.</p>
<?php endif; ?>

<!-- Full students list below -->
<h2>All Students</h2>

<?php if (!empty($all_students)): ?>
<table>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Roll Number</th>
    <th>Department</th>
    <th>Phone</th>
    <th>Address</th>
    <th>Parent's Name</th>
    <th>Parent's Phone</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($all_students as $student): ?>
  <tr>
    <td><?= htmlspecialchars($student['id']) ?></td>
    <td><?= htmlspecialchars($student['name']) ?></td>
    <td><?= htmlspecialchars($student['roll_no']) ?></td>
    <td><?= htmlspecialchars($student['department']) ?></td>
    <td><?= htmlspecialchars($student['phone']) ?></td>
    <td><?= htmlspecialchars($student['address']) ?></td>
    <td><?= htmlspecialchars($student['parent_name']) ?></td>
    <td><?= htmlspecialchars($student['parent_phone']) ?></td>
    <td>
      <a href="edit_student.php?id=<?= $student['id'] ?>">Edit</a> |
      <a href="?delete_id=<?= $student['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a> |
      <a href="manage_fine.php?student_id=<?= $student['id'] ?>">Fines</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?>
<p>No students found.</p>
<?php endif; ?>

</body>
</html>
