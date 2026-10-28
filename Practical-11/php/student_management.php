<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.html");
    exit;
}

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../pages/student_dashboard.php");
    exit;
}

if (isset($_SESSION["last_activity"]) && time() - $_SESSION["last_activity"] > 900) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    header("Location: ../pages/login.html?timeout=1");
    exit;
}

$_SESSION["last_activity"] = time();

require_once __DIR__ . "/db_connect_mysqli.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit("Database connection failed.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectWithStatus(string $status): void
{
    header("Location: student_management.php?status=" . rawurlencode($status));
    exit;
}

/* Load courses */
$courses = [];

$courseResult = $conn->query(
    "SELECT course_id, course_name FROM courses ORDER BY course_name"
);

if ($courseResult) {
    while ($row = $courseResult->fetch_assoc()) {
        $courses[] = $row;
    }

    $courseResult->free();
}

$error = "";

/* Add, update and delete */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add" || $action === "update") {
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string)($_POST["name"] ?? ""));
        $email = trim((string)($_POST["email"] ?? ""));
        $mobile = trim((string)($_POST["mobile"] ?? ""));
        $courseId = filter_var($_POST["course_id"] ?? null, FILTER_VALIDATE_INT);
        $year = filter_var($_POST["year"] ?? null, FILTER_VALIDATE_INT);
        $gender = trim((string)($_POST["gender"] ?? ""));

        $selectedCourse = null;

        foreach ($courses as $courseOption) {
            if ((int)$courseOption["course_id"] === (int)$courseId) {
                $selectedCourse = $courseOption;
                break;
            }
        }

        if ($name === "" || mb_strlen($name) > 100) {
            $error = "Enter a student name (maximum 100 characters).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $error = "Enter a valid email address.";
        } elseif ($mobile === "" || strlen($mobile) > 15) {
            $error = "Enter a valid mobile number.";
        } elseif (!$courseId || !$selectedCourse) {
            $error = "Select a valid course.";
        } elseif ($year === false || $year < 1 || $year > 10) {
            $error = "Enter a valid academic year.";
        } elseif (!in_array($gender, ["Male", "Female", "Other"], true)) {
            $error = "Select a valid gender.";
        } elseif ($action === "update" && (!$id || $id < 1)) {
            $error = "Invalid student ID.";
        } else {
            /*
             * TC-13: Check whether the email already belongs
             * to another student before adding or updating.
             *
             * During an update, exclude the current student's ID
             * so the student can keep their existing email.
             */
            $check = $conn->prepare(
                "SELECT id FROM students WHERE email = ? AND id <> ? LIMIT 1"
            );

            if (!$check) {
                $error = "Unable to validate student email.";
            } else {
                $excludeId = ($action === "update") ? (int)$id : 0;

                $check->bind_param("si", $email, $excludeId);

                if (!$check->execute()) {
                    $error = "Unable to validate student email.";
                    $check->close();
                } else {
                    $checkResult = $check->get_result();
                    $emailExists = $checkResult->num_rows > 0;

                    $checkResult->free();
                    $check->close();

                    if ($emailExists) {
                        $error = "Email already exists. Please use a different email.";
                    } elseif ($action === "add") {
                        $stmt = $conn->prepare(
                            "INSERT INTO students
                            (name, email, mobile, year, gender, course_id)
                            VALUES (?, ?, ?, ?, ?, ?)"
                        );

                        if ($stmt) {
                            $stmt->bind_param(
                                "sssisi",
                                $name,
                                $email,
                                $mobile,
                                $year,
                                $gender,
                                $courseId
                            );

                            if ($stmt->execute()) {
                                $stmt->close();
                                redirectWithStatus("added");
                            }

                            $error = $stmt->errno === 1062
                                ? "Email already exists. Please use a different email."
                                : "Unable to add student. Check the entered details.";

                            $stmt->close();
                        } else {
                            $error = "Unable to prepare the add operation.";
                        }
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE students
                             SET name = ?, email = ?, mobile = ?,
                                 year = ?, gender = ?, course_id = ?
                             WHERE id = ?"
                        );

                        if ($stmt) {
                            $stmt->bind_param(
                                "sssisii",
                                $name,
                                $email,
                                $mobile,
                                $year,
                                $gender,
                                $courseId,
                                $id
                            );

                            if ($stmt->execute()) {
                                $stmt->close();
                                redirectWithStatus("updated");
                            }

                            $error = $stmt->errno === 1062
                                ? "Email already exists. Please use a different email."
                                : "Unable to update student.";

                            $stmt->close();
                        } else {
                            $error = "Unable to prepare the update operation.";
                        }
                    }
                }
            }
        }
    } elseif ($action === "delete") {
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $error = "Invalid student ID.";
        } else {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");

            if ($stmt) {
                $stmt->bind_param("i", $id);

                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $stmt->close();
                    redirectWithStatus("deleted");
                }

                $error = "Student was not deleted. The record may not exist.";
                $stmt->close();
            } else {
                $error = "Unable to prepare the delete operation.";
            }
        }
    } else {
        $error = "Unknown action.";
    }
}

/* Search and pagination */
$search = trim((string)($_GET["q"] ?? ""));
$perPage = 10;

$page = filter_var($_GET["page"] ?? 1, FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$like = "%" . $search . "%";

$students = [];
$totalRecords = 0;

if ($search !== "") {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM students s
         LEFT JOIN courses c ON s.course_id = c.course_id
         WHERE s.name LIKE ? OR s.email LIKE ? OR c.course_name LIKE ?"
    );

    if ($stmt) {
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();

        $result = $stmt->get_result();
        $totalRecords = (int)($result->fetch_assoc()["total"] ?? 0);

        $result->free();
        $stmt->close();
    }
} else {
    $result = $conn->query("SELECT COUNT(*) AS total FROM students");

    if ($result) {
        $totalRecords = (int)($result->fetch_assoc()["total"] ?? 0);
        $result->free();
    }
}

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

if ($search !== "") {
    $stmt = $conn->prepare(
        "SELECT s.id, s.name, s.email, s.mobile,
                c.course_name AS course, s.year, s.gender, s.course_id
         FROM students s
         LEFT JOIN courses c ON s.course_id = c.course_id
         WHERE s.name LIKE ? OR s.email LIKE ? OR c.course_name LIKE ?
         ORDER BY s.id DESC
         LIMIT ? OFFSET ?"
    );

    if ($stmt) {
        $stmt->bind_param(
            "sssii",
            $like,
            $like,
            $like,
            $perPage,
            $offset
        );
    }
} else {
    $stmt = $conn->prepare(
        "SELECT s.id, s.name, s.email, s.mobile,
                c.course_name AS course, s.year, s.gender, s.course_id
         FROM students s
         LEFT JOIN courses c ON s.course_id = c.course_id
         ORDER BY s.id DESC
         LIMIT ? OFFSET ?"
    );

    if ($stmt) {
        $stmt->bind_param("ii", $perPage, $offset);
    }
}

if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $result->free();
    } else {
        $error = $error ?: "Unable to load student records.";
    }

    $stmt->close();
}

/* Status messages */
$statusMessages = [
    "added" => "Student added successfully.",
    "updated" => "Student updated successfully.",
    "deleted" => "Student deleted successfully."
];

$successMessage = $statusMessages[(string)($_GET["status"] ?? "")] ?? "";

/* Load student for editing */
$editStudent = null;
$editId = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $stmt = $conn->prepare(
        "SELECT s.id, s.name, s.email, s.mobile,
                c.course_name AS course, s.year, s.gender, s.course_id
         FROM students s
         LEFT JOIN courses c ON s.course_id = c.course_id
         WHERE s.id = ?"
    );

    if ($stmt) {
        $stmt->bind_param("i", $editId);
        $stmt->execute();

        $result = $stmt->get_result();
        $editStudent = $result->fetch_assoc() ?: null;

        $result->free();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub - Student Management</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>
        .crud-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .crud-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .crud-form input,
        .crud-form select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        .crud-form .full-width {
            grid-column: 1 / -1;
        }

        .crud-form button,
        .search-form button,
        .action-button {
            border: 0;
            border-radius: 7px;
            padding: 10px 15px;
            background: #0056B3;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font: inherit;
        }

        .secondary-button {
            background: #64748b !important;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-form input {
            flex: 1;
            min-width: 200px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        .crud-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .crud-table th,
        .crud-table td {
            padding: 11px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .crud-table th {
            background: #edf3fc;
            color: #0056B3;
        }

        .crud-table tr:hover td {
            background: #f8fbff;
        }

        .action-cell,
        .delete-form {
            white-space: nowrap;
        }

        .delete-form {
            display: inline;
        }

        .delete-button {
            background: #b42318;
            border: 0;
            border-radius: 7px;
            color: white;
            padding: 9px 12px;
            cursor: pointer;
            font: inherit;
        }

        .notice {
            padding: 12px 15px;
            border-radius: 8px;
            margin: 12px 0;
        }

        .notice-success {
            background: #e8f7ee;
            color: #17663a;
            border: 1px solid #b8e3c8;
        }

        .notice-error {
            background: #fff0f0;
            color: #a61b1b;
            border: 1px solid #f2c2c2;
        }

        @media (max-width: 650px) {
            .crud-form {
                grid-template-columns: 1fr;
            }

            .crud-form .full-width {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>
<main class="dashboard-page">

    <header class="dashboard-header">
        <div>
            <h1>StudentHub</h1>
            <p>Student Management</p>
        </div>

        <a class="dashboard-logout" href="logout.php">Logout</a>
    </header>

    <section class="dashboard-welcome">
        <h2>Manage Students</h2>
        <p>Add, view, edit, delete, and search student records.</p>
        <p>Total matching records: <strong><?php echo $totalRecords; ?></strong></p>
    </section>

    <?php if ($successMessage !== ""): ?>
        <div class="notice notice-success" role="status">
            <?php echo h($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notice notice-error" role="alert">
            <?php echo h($error); ?>
        </div>
    <?php endif; ?>

    <section class="dashboard-info">
        <h2><?php echo $editStudent ? "Edit Student" : "Add Student"; ?></h2>

        <?php if (!$courses): ?>
            <div class="notice notice-error">
                No courses found. Add courses to the courses table first.
            </div>
        <?php endif; ?>

        <form class="crud-form" method="post" action="student_management.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo h($_SESSION["csrf_token"]); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="<?php echo $editStudent ? "update" : "add"; ?>"
            >

            <?php if ($editStudent): ?>
                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int)$editStudent["id"]; ?>"
                >
            <?php endif; ?>

            <div>
                <label for="name">Student Name</label>
                <input
                    id="name"
                    name="name"
                    maxlength="100"
                    required
                    value="<?php echo h($editStudent["name"] ?? ""); ?>"
                >
            </div>

            <div>
                <label for="email">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="150"
                    required
                    value="<?php echo h($editStudent["email"] ?? ""); ?>"
                >
            </div>

            <div>
                <label for="mobile">Mobile</label>
                <input
                    id="mobile"
                    name="mobile"
                    maxlength="15"
                    required
                    value="<?php echo h($editStudent["mobile"] ?? ""); ?>"
                >
            </div>

            <div>
                <label for="course_id">Course</label>

                <select id="course_id" name="course_id" required>
                    <option value="">Select Course</option>

                    <?php foreach ($courses as $courseOption): ?>
                        <option
                            value="<?php echo (int)$courseOption["course_id"]; ?>"
                            <?php
                            echo (
                                isset($editStudent["course_id"]) &&
                                (int)$editStudent["course_id"] ===
                                (int)$courseOption["course_id"]
                            ) ? "selected" : "";
                            ?>
                        >
                            <?php echo h($courseOption["course_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="year">Academic Year</label>
                <input
                    id="year"
                    name="year"
                    type="number"
                    min="1"
                    max="10"
                    required
                    value="<?php echo h($editStudent["year"] ?? "1"); ?>"
                >
            </div>

            <div>
                <label for="gender">Gender</label>

                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>

                    <?php foreach (["Male", "Female", "Other"] as $genderOption): ?>
                        <option
                            value="<?php echo h($genderOption); ?>"
                            <?php
                            echo (($editStudent["gender"] ?? "") === $genderOption)
                                ? "selected"
                                : "";
                            ?>
                        >
                            <?php echo h($genderOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full-width">
                <button type="submit" <?php echo !$courses ? "disabled" : ""; ?>>
                    <?php echo $editStudent ? "Save Changes" : "Add Student"; ?>
                </button>

                <?php if ($editStudent): ?>
                    <a
                        class="action-button secondary-button"
                        href="student_management.php"
                    >
                        Cancel Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="dashboard-info">
        <h2>Student Records</h2>

        <form class="search-form" method="get" action="student_management.php">
            <input
                type="search"
                name="q"
                value="<?php echo h($search); ?>"
                placeholder="Search by name, email, or course"
                aria-label="Search students"
            >

            <button type="submit">Search</button>

            <a
                class="action-button secondary-button"
                href="student_management.php"
            >
                Clear
            </a>
        </form>

        <div style="overflow-x:auto;">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$students): ?>
                    <tr>
                        <td colspan="8">No student records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo (int)$student["id"]; ?></td>
                            <td><?php echo h($student["name"]); ?></td>
                            <td><?php echo h($student["email"]); ?></td>
                            <td><?php echo h($student["mobile"]); ?></td>
                            <td><?php echo h($student["course"] ?? "Unknown"); ?></td>
                            <td><?php echo (int)$student["year"]; ?></td>
                            <td><?php echo h($student["gender"]); ?></td>

                            <td class="action-cell">
                                <a
                                    class="action-button"
                                    href="student_management.php?edit=<?php echo (int)$student["id"]; ?>"
                                >
                                    Edit
                                </a>

                                <form
                                    class="delete-form"
                                    method="post"
                                    action="student_management.php"
                                    onsubmit="return confirm('Are you sure you want to delete this student record?');"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo h($_SESSION["csrf_token"]); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo (int)$student["id"]; ?>"
                                    >

                                    <button class="delete-button" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:center; align-items:center; gap:15px; margin-top:20px; flex-wrap:wrap;">
            <?php if ($page > 1): ?>
                <a
                    class="action-button secondary-button"
                    href="?page=<?php echo $page - 1; ?>&amp;q=<?php echo urlencode($search); ?>"
                >
                    Previous
                </a>
            <?php endif; ?>

            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

            <?php if ($page < $totalPages): ?>
                <a
                    class="action-button"
                    href="?page=<?php echo $page + 1; ?>&amp;q=<?php echo urlencode($search); ?>"
                >
                    Next
                </a>
            <?php endif; ?>
        </div>
    </section>

</main>
</body>
</html>