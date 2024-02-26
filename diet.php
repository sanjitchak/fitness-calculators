<?php
// Include the configuration file for database credentials
include 'config.php';

// Define the function to sanitize input data:
function sanitizeInputData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Sanitize and retrieve input data
$name = sanitizeInputData($_POST['name'] ?? '');
$email = sanitizeInputData($_POST['email'] ?? '');
$feet = sanitizeInputData($_POST['heightFeet'] ?? '');
$inches = sanitizeInputData($_POST['heightInches'] ?? '');
$weight = sanitizeInputData($_POST['weight'] ?? '');
$age = sanitizeInputData($_POST['age'] ?? '');
$gender = sanitizeInputData($_POST['gender'] ?? '');
$activityLevel = sanitizeInputData($_POST['activityLevel'] ?? '');
$bodyFat = sanitizeInputData($_POST['bodyFat'] ?? '');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT email, paid, nooftimes FROM paidcustomers WHERE email = ?");
$stmt->execute([$email]);

// Check if the email exists in the database
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if the customer has paid and nooftimes is not equal to 5
    if ($row['paid'] == "yes" && $row['nooftimes'] != 5) {
        // Update nooftimes in the database
        $nooftimes = $row['nooftimes'] + 1;
        $updateStmt = $conn->prepare("UPDATE paidcustomers SET nooftimes = :nooftimes WHERE email = :email");
        $updateStmt->execute([':nooftimes' => $nooftimes, ':email' => $email]);

        // Perform calculations
        $heightInCm = (($feet * 12) + $inches) * 2.54;
        $bmi = $weight / pow($heightInCm / 100, 2);

        $bmr = $gender == "male" ? 
            (10 * $weight + 6.25 * $heightInCm - 5 * $age + 5) : 
            (10 * $weight + 6.25 * $heightInCm - 5 * $age - 161);

        $tdee = $bmr * $activityLevel;
        if( $nooftimes >= 0)
        {
        // Redirect to the result page with calculations
        $url = "https://diet.simfitindia.com/result.html?name=" . urlencode($name) . "&email=" . $email . "&bodyFat=" . $bodyFat . "&tdee=" . round($tdee) . "&bmr=" . round($bmr) . "&bmi=" . round($bmi). "&credits=" . round(5-$nooftimes);
        header("Location: $url");
        exit;
        }
        else {
 // Redirect to the result page with calculations
 $url = "https://diet.simfitindia.com/result.html?name=" . urlencode($name) . "&email=" . $email . "&bodyFat=" . $bodyFat . "&tdee=" . round($tdee) . "&bmr=" . round($bmr) . "&bmi=" . round($bmi). "&credits=" . "unlimited";
 header("Location: $url");
 exit;
        }
    } else {
        // Redirect to the not paid page with nooftimes=5
        header("Location: https://diet.simfitindia.com/notpay.html?name=" . urlencode($name) . "&email=yes&nooftimes=5");
        exit;
    }
} else {
    // Redirect to the not paid page with email=no
    header("Location: https://diet.simfitindia.com/notpay.html?name=" . urlencode($name) . "&email=no");
    exit;
}

// Close the database connection
$conn = null;
?>
