<?php
include 'db.php'; // or whatever DB connection file you're using

// Create folder if needed
if (!file_exists("qr-codes")) mkdir("qr-codes");

$sql = "SELECT roll_no FROM students WHERE roll_no IS NOT NULL";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $roll_no = $row['roll_no'];
    $qr_url = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=$roll_no";
    file_put_contents("qr-codes/$roll_no.png", file_get_contents($qr_url));
}

echo "✅ Bulk QR code generation completed. Check the qr-codes folder.";
?>
