
<?php

require "db_connect_mysqli.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request.");
}

// Receive form data
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";

// 1. Validate required fields
if (
    $name === "" ||
    $email === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    exit("All required fields must be filled.");
}

// 2. Validate name
if (!preg_match("/^[A-Za-z ]{2,50}$/", $name)) {
    exit("Name must contain 2-50 letters and spaces.");
}

// 3. Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Please enter a valid email address.");
}

// 4. Validate password length
if (strlen($password) < 8) {
    exit("Password must be at least 8 characters.");
}

// 5. Validate password strength
if (
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password) ||
    !preg_match("/[^A-Za-z0-9]/", $password)
) {
    exit(
        "Password must contain uppercase, lowercase, " .
        "a number and a special character."
    );
}

// 6. Confirm passwords match
if ($password !== $confirmPassword) {
    exit("Passwords do not match.");
}

// 7. Check duplicate email
$check = $conn->prepare(
    "SELECT id FROM users WHERE email = ?"
);
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    exit("This email is already registered. Please log in.");
}

$check->close();

// 8. Hash password
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// 9. Insert user using a prepared statement
$sql = "INSERT INTO users (name, email, password)
        VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sss",
    $name,
    $email,
    $hashedPassword
);

if ($stmt->execute()) {
    echo "<h2>Registration Successful!</h2>";
    echo "<p>Your account has been created securely.</p>";
    echo "<a href='../pages/register.html'>Register Another User</a>";
} else {
    echo "Registration failed. Please try again.";
}

$stmt->close();
$conn->close();

?>
