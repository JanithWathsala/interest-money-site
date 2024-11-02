<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "interest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Colombo'); // Set time zone

// Fetch all borrowers
$sql = "SELECT * FROM borrowers";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($borrower = $result->fetch_assoc()) {
        $borrower_id = $borrower['id'];
        $bar_rent = $borrower['rental'];
        
        // Calculate total payments for the borrower
        list($total_payment, $total_py) = fetch_total_payments($conn, $borrower_id);

        // Days passed since the loan date
        $loan_date = new DateTime($borrower['lone_date']);
        $today = new DateTime();
        $yesterday = clone $today;
        $yesterday->modify('-1 day');

        $days_passed = $loan_date->diff($yesterday)->days;

        // Expected total payment by today
        $expected_payment_by_today = $days_passed * $bar_rent;

        // Calculate arrears
        $arrears = max($expected_payment_by_today - $total_payment, 0);
        $total_arrears = round($arrears, 2);

        // Debugging output to verify values
        echo "Borrower ID: $borrower_id, Days Passed: $days_passed, Expected Payment: $expected_payment_by_today, Total Payment: $total_payment, Arrears: $total_arrears<br>";

        // Update the arrears in the database
        $update_sql = "UPDATE borrowers SET total_arrears = ?, total_payments = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ddi", $total_arrears, $total_py, $borrower_id);

        if (!$update_stmt->execute()) {
            echo "Error updating arrears for borrower ID $borrower_id: " . $update_stmt->error . "<br>";
        }
        $update_stmt->close();
    }
    echo "Arrears updated for all borrowers successfully.";
} else {
    echo "No borrowers found.";
}

$conn->close();

// Function to fetch total payments and number of payments for a borrower
function fetch_total_payments($conn, $borrower_id) {
    $sql = "SELECT SUM(rental_amount) AS total_payment FROM payments WHERE borrower_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $total_payment = $data['total_payment'] ?? 0;
    $total_py = $data['total_payment'] ?? 0; // Set to total for total_payments
    
    $stmt->close();
    return [$total_payment, $total_py];
}
?>