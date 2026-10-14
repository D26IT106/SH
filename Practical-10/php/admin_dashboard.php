<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if (isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"] > 900)) {

    $_SESSION = [];
    session_destroy();

    header("Location: login.html");
    exit;
}

$_SESSION["last_activity"] = time();

if ($_SESSION["user_role"] !== "admin") {
    header("Location: student_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="login-container">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</p>
        <p>You are logged in as an administrator.</p>
        <a href="../php/logout.php">Logout</a>
    </main>
</body>
</html>