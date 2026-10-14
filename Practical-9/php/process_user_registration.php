
<?php

require "db_connect_mysqli.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";

if (
    $name === "" ||
    $email === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    exit("All required fields must be filled.");
}

if (!preg_match("/^[A-Za-z ]{2,50}$/", $name)) {
    exit("Name must contain 2-50 letters and spaces.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Please enter a valid email address.");
}

if (strlen($password) < 8) {
    exit("Password must be at least 8 characters.");
}

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

if ($password !== $confirmPassword) {
    exit("Passwords do not match.");
}

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

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

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