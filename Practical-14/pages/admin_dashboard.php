
<?php
session_start();

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

// Allow only admin users
if (!isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin") {
    header("Location: student_dashboard.php");
    exit;
}

// Database connection
require_once __DIR__ . "/../php/db_connect_mysqli.php";

// Escape output to prevent HTML injection
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$totalUsers = 0;
$studentCount = 0;
$eventCount = 0;
$latestRegistrations = [];
$error = "";

// 1. Count all users
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
);

if ($result) {
    $totalUsers = (int)$result->fetch_assoc()["total"];
    $result->free();
} else {
    $error = "Unable to load total user count.";
}

// 2. Count students from the students table
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM students"
);

if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $studentCount = (int)$result->fetch_assoc()["total"];
        $result->free();
    } else {
        $error = "Unable to load student count.";
    }

    $stmt->close();
} else {
    $error = "Unable to prepare student count query.";
}

// 3. Count events
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM events"
);

if ($result) {
    $eventCount = (int)$result->fetch_assoc()["total"];
    $result->free();
} else {
    $error = "Unable to load event count.";
}

// 4. Get latest five registrations
$result = $conn->query(
    "SELECT id, name, email, role, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT 5"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestRegistrations[] = $row;
    }

    $result->free();
} else {
    $error = "Unable to load latest registrations.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub - Admin Dashboard</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>
        .admin-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .stat-card,
        .admin-panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.07);
        }

        .stat-card h3 {
            margin-top: 0;
            color: #475569;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0056b3;
        }

        .admin-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 20px 0;
        }

        .admin-button {
            display: inline-block;
            background: #0056b3;
            color: white;
            padding: 10px 15px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font: inherit;
        }

        .admin-button.secondary {
            background: #64748b;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .admin-table th {
            background: #edf3fc;
            color: #0056b3;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .notice-error {
            background: #fff0f0;
            color: #a61b1b;
            padding: 12px;
            border-radius: 8px;
        }

        @media (max-width: 650px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-container {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
<main class="admin-container">

    <header class="admin-header">
        <div>
            <h1>StudentHub</h1>
            <p>Administrator Dashboard</p>
        </div>

        <a class="admin-button secondary" href="../php/logout.php">
            Logout
        </a>
    </header>

    <?php if ($error !== ""): ?>
        <p class="notice-error" role="alert">
            <?php echo h($error); ?>
        </p>
    <?php endif; ?>

    <!-- Statistics -->
    <section class="stats-grid">

        <article class="stat-card">
            <h3>Total Users</h3>
            <div class="stat-number">
                <?php echo $totalUsers; ?>
            </div>
        </article>

        <article class="stat-card">
            <h3>Total Students</h3>
            <div class="stat-number">
                <?php echo $studentCount; ?>
            </div>
        </article>

        <article class="stat-card">
            <h3>Total Events</h3>
            <div class="stat-number">
                <?php echo $eventCount; ?>
            </div>
        </article>

    </section>

    <!-- Role-based navigation -->
    <section class="admin-panel">
        <h2>Admin Menu</h2>

        <nav class="admin-menu">
            <?php if ($_SESSION["user_role"] === "admin"): ?>

                <a class="admin-button" href="../php/student_management.php">
                    Manage Users
                </a>

                <a class="admin-button" href="../php/event_management.php">
                    Manage Events
                </a>

            <?php elseif ($_SESSION["user_role"] === "student"): ?>

                <a class="admin-button" href="student_dashboard.php">
                    Student Dashboard
                </a>

            <?php endif; ?>
        </nav>
    </section>

    <!-- Latest registrations -->
    <section class="admin-panel" style="margin-top: 25px;">

        <h2>Latest Registrations</h2>

        <div class="table-wrap">
            <table class="admin-table">

                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered On</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($latestRegistrations)): ?>

                        <tr>
                            <td colspan="5">No registrations found.</td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($latestRegistrations as $user): ?>
                            <tr>
                                <td>
                                    <?php echo (int)$user["id"]; ?>
                                </td>

                                <td>
                                    <?php echo h($user["name"]); ?>
                                </td>

                                <td>
                                    <?php echo h($user["email"]); ?>
                                </td>

                                <td>
                                    <?php echo h($user["role"]); ?>
                                </td>

                                <td>
                                    <?php echo h($user["created_at"]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>

            </table>
        </div>
    </section>

</main>
</body>
</html>
