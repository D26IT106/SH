
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if (time() - ($_SESSION["last_activity"] ?? time()) > 900) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f5f1eb;
        color: #40352d;
    }

    header {
        background: #0056B3;
        color: white;
        padding: 20px 35px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    main {
        padding: 35px;
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 25px;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 3px 10px #00000010;
    }

    .card h3 {
        color: #0056B3;
    }

    a {
        color: white;
        text-decoration: none;
        background: #0056B3;
        padding: 10px 16px;
        border-radius: 6px;
        display: inline-block;
    }

    a:hover {
        background: #004494;
    }

    @media (max-width: 500px) {
        header {
            padding: 18px;
            gap: 15px;
        }

        main {
            padding: 20px;
        }
    }
</style>

</head>
<body>
<header>
    <h2>StudentHub Admin</h2>
    <a href="../php/logout.php">Logout</a>
</header>

<main>
    <h1>Welcome, <?= htmlspecialchars($_SESSION["user_name"]) ?>!</h1>
    <p>Manage and monitor the StudentHub portal.</p>

    <p>
        <a href="../php/student_management.php">
            Manage Students
        </a>

        <a href="../php/event_management.php" class="event-button">
            Manage Events
        </a>
    </p>

    <div class="cards">
        <div class="card">
            <h3>Students</h3>
            <p>Manage student information.</p>
        </div>
        <div class="card">
            <h3>Events</h3>
            <p>View campus events.</p>
        </div>
        <div class="card">
            <h3>Registrations</h3>
            <p>Review student registrations.</p>
        </div>
        <div class="card">
            <h3>Admin Access</h3>
            <p>You are logged in as an administrator.</p>
        </div>
    </div>
</main>
</body>
</html>
