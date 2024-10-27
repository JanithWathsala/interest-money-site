<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "interest";

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
date_default_timezone_set('Asia/Colombo');

// Update each borrower's number of payments in the database
$query = "UPDATE borrowers b
    LEFT JOIN (
        SELECT borrower_id, COUNT(du_date) AS no_pay
        FROM payments
        WHERE payment_date <= CURDATE()
        GROUP BY borrower_id
    ) p ON b.id = p.borrower_id
    SET b.no_pay = IFNULL(p.no_pay, 0)
";

if (!mysqli_query($conn, $query)) {
    die("Update failed: " . mysqli_error($conn));
}


?>

