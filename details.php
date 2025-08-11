<?php session_start();
include 'phpqrcode/qrlib.php'; // Ensure this is included
include 'db.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}


// Handle QR regeneration
if (isset($_GET['regenerate_qr'])) {
    $id = intval($_GET['regenerate_qr']);

    $get = mysqli_query($conn, "SELECT roll_no FROM students WHERE id=$id");
    if ($row = mysqli_fetch_assoc($get)) {
        $roll = $row['roll_no'];
        $dir = "qr-codes";
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $qr_file = "$dir/$roll.png";
        QRcode::png($roll, $qr_file, 'L', 6, 2);
        header("Location: details.php#row-$id");
        exit;
    }
}

// Handle Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];

    $query = "UPDATE students SET 
        name='$name', 
        department='$department', 
        phone='$phone', 
        address='$address', 
        parent_name='$parent_name', 
        parent_phone='$parent_phone' 
        WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: details.php#row-$id");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $get = mysqli_query($conn, "SELECT roll_no FROM students WHERE id=$id");
    $data = mysqli_fetch_assoc($get);
    $roll = $data['roll_no'];
    $qr = "qr-codes/$roll.png";
    if (file_exists($qr)) unlink($qr);

    mysqli_query($conn, "DELETE FROM students WHERE id=$id");
    header("Location: details.php");
    exit;
}

$students = mysqli_query($conn, "SELECT * FROM students");
$edit_id = isset($_GET['edit']) ? $_GET['edit'] : null;
$departments = ['Computer Science', 'Mechatronics', 'Tool & Die', 'Electronics'];
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage QR Students</title>
    <style>
        /* Reset */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            color: #2d3e50;
            padding: 30px 15px 60px;
            margin: 0;
        }

        h2 {
            text-align: center;
            color: #34495e;
            margin-bottom: 25px;
            font-weight: 700;
            letter-spacing: 1.2px;
        }

        /* Search Bar */
        #search-container {
            max-width: 600px;
            margin: 0 auto 30px auto;
            /* position: sticky; */
            top: 0;
            background: #f0f4f8;
            padding: 10px 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.05);
            z-index: 10;
        }
        #search {
            width: 100%;
            padding: 12px 18px;
            border: 2px solid #3498db;
            border-radius: 30px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        #search:focus {
            border-color: #2980b9;
            background: #fff;
            box-shadow: 0 0 8px rgba(41, 128, 185, 0.5);
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 14px;
            background: #fff;
            box-shadow: 0 6px 16px rgb(0 0 0 / 0.1);
            border-radius: 16px;
            overflow: hidden;
            table-layout: fixed;
            font-size: 15px;
        }

        thead tr {
            background-color: #2980b9;
            color: white;
            font-weight: 700;
            text-align: center;
            user-select: none;
        }

        th, td {
            padding: 14px 15px;
            vertical-align: middle;
            word-wrap: break-word;
            text-align: center;
        }

        tbody tr {
            background: #fcfdff;
            transition: background-color 0.3s ease;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.06);
        }

        tbody tr:hover {
            background-color: #dbe9fb;
        }

        /* Input fields inside table */
        input[type="text"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1.6px solid #a4b0be;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            font-family: inherit;
            resize: vertical;
            text-align: center;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            border-color: #2980b9;
            outline: none;
            background-color: #fff;
            box-shadow: 0 0 6px rgba(41, 128, 185, 0.4);
        }

        textarea {
            min-height: 60px;
            max-height: 90px;
            resize: vertical;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            user-select: none;
            text-align: center;
            min-width: 72px;
        }

        .btn.edit {
            background-color: #f39c12;
            color: white;
            box-shadow: 0 4px 10px rgba(243, 156, 18, 0.3);
        }
        .btn.edit:hover {
            background-color: #d48806;
            box-shadow: 0 6px 14px rgba(243, 156, 18, 0.6);
        }

        .btn.delete {
            background-color: #e74c3c;
            color: white;
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }
        .btn.delete:hover {
            background-color: #c0392b;
            box-shadow: 0 6px 14px rgba(231, 76, 60, 0.6);
        }

        .btn.update {
            background-color: #27ae60;
            color: white;
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);
        }
        .btn.update:hover {
            background-color: #1e8449;
            box-shadow: 0 6px 14px rgba(39, 174, 96, 0.6);
        }

        /* QR code images */
        img {
            height: 80px;
            width: 80px;
            object-fit: contain;
            border-radius: 10px;
            border: 1.5px solid #d1d9e6;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        img:hover {
            transform: scale(1.12);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }

        /* Form inside table row */
        form {
            margin: 0;
        }

        /* Fix alignment in edit form - inputs centered vertically */
        tr form td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        tr form td input,
        tr form td select,
        tr form td textarea {
            text-align: left;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            table {
                font-size: 14px;
            }
        }
        @media (max-width: 850px) {
            #search-container {
                max-width: 100%;
                border-radius: 0;
                box-shadow: none;
                padding: 8px 10px;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tbody tr {
                background: #fff;
                margin-bottom: 22px;
                border-radius: 14px;
                box-shadow: 0 6px 18px rgb(0 0 0 / 0.1);
                padding: 20px 18px;
            }
            tbody tr td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: left;
                word-break: break-word;
            }
            tbody tr td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: 700;
                color: #34495e;
                top: 20px;
                white-space: nowrap;
            }
            td img {
                width: 70px;
                height: 70px;
                display: block;
                margin: 10px 0;
                border-radius: 10px;
            }
            tr form td input,
            tr form td select,
            tr form td textarea {
                text-align: left;
            }
        }

    </style>
</head>
<body>

<h2>All Registered Students with QR Codes</h2>
<div id="search-container">
    <input type="text" id="search" placeholder="Search by Name, Roll No or Department...">
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Roll No</th>
        <th>Department</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Parent Name</th>
        <th>Parent Phone</th>
        <th>QR Code</th>
        <th>Actions</th>
    </tr>

    <?php while($row = mysqli_fetch_assoc($students)): ?>
    <tr id="row-<?= $row['id'] ?>">
        <?php if ($edit_id == $row['id']): ?>
            <form method="post">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <td><?= $row['id'] ?></td>
                <td><input name="name" value="<?= htmlspecialchars($row['name']) ?>"></td>
                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                <td>
                    <select name="department">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept ?>" <?= ($row['department'] == $dept) ? 'selected' : '' ?>><?= $dept ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input name="phone" value="<?= htmlspecialchars($row['phone']) ?>"></td>
                <td><textarea name="address"><?= htmlspecialchars($row['address']) ?></textarea></td>
                <td><input name="parent_name" value="<?= htmlspecialchars($row['parent_name']) ?>"></td>
                <td><input name="parent_phone" value="<?= htmlspecialchars($row['parent_phone']) ?>"></td>
                <td>
                    <a href="qr-codes/<?= $row['roll_no'] ?>.png" download="<?= $row['roll_no'] ?>.png">
                        <img src="qr-codes/<?= $row['roll_no'] ?>.png" alt="QR Code" width="80">
                    </a>
                    <br>
                    <a href="details.php?regenerate_qr=<?= $row['id'] ?>" class="btn" style="margin-top: 5px; background-color: #28a745; color: white;" onclick="return confirm('Regenerate QR Code?')">Regenerate QR</a>
                </td>
                <td>
                    <button class="btn update" type="submit" name="update">Update</button>
                    <a class="btn delete" href="details.php#row-<?= $row['id'] ?>">Cancel</a>
                </td>
            </form>
        <?php else: ?>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['roll_no']) ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td><?= htmlspecialchars($row['parent_name']) ?></td>
            <td><?= htmlspecialchars($row['parent_phone']) ?></td>
            <td>
                <a href="qr-codes/<?= $row['roll_no'] ?>.png" download="<?= $row['roll_no'] ?>.png">
                    <img src="qr-codes/<?= $row['roll_no'] ?>.png" alt="QR Code" width="80">
                </a>
                <br>
                <a href="details.php?regenerate_qr=<?= $row['id'] ?>" class="btn" style="margin-top: 5px; background-color: #28a745; color: white;" onclick="return confirm('Regenerate QR Code?')">Regenerate QR</a>
            </td>
            <td>
                <a class="btn edit" href="details.php?edit=<?= $row['id'] ?>#row-<?= $row['id'] ?>">Edit</a>
                <a class="btn delete" onclick="return confirm('Delete student?')" href="details.php?delete=<?= $row['id'] ?>">Delete</a>
            </td>
        <?php endif; ?>
    </tr>
    <?php endwhile; ?>
</table>
<script>
    const searchInput = document.getElementById('search');
    const table = document.querySelector('table');
    const tbody = table.tBodies[0];  // tbody element

    searchInput.addEventListener('input', () => {
        const filter = searchInput.value.toLowerCase();

        // Loop through all rows in tbody
        for (let row of tbody.rows) {
            // Get text content for name, roll_no, and department cells
            // Your columns: 
            // 1 -> Name (td index 1)
            // 2 -> Roll No (td index 2)
            // 3 -> Department (td index 3)
            const name = row.cells[1].textContent.toLowerCase();
            const rollNo = row.cells[2].textContent.toLowerCase();
            const department = row.cells[3].textContent.toLowerCase();

            // If any field matches filter, show row, else hide it
            if (name.includes(filter) || rollNo.includes(filter) || department.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
</script>

</body>
</html>
