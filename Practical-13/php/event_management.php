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
        setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
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

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectEventStatus(string $status): void {
    header("Location: event_management.php?status=" . rawurlencode($status));
    exit;
}

function saveEventPoster(array $file, string &$error): ?string {
    $error = "";

    if (!isset($file["error"]) || is_array($file["error"])) {
        $error = "Invalid poster upload.";
        return null;
    }

    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return "";
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $error = "Poster upload failed. Please try again.";
        return null;
    }

    if (!isset($file["size"]) || $file["size"] < 1 || $file["size"] > 2 * 1024 * 1024) {
        $error = "Poster must be larger than 0 bytes and no bigger than 2 MB.";
        return null;
    }

    if (empty($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
        $error = "Invalid uploaded file.";
        return null;
    }

    if (!class_exists("finfo")) {
        $error = "Server fileinfo support is required to validate poster types.";
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png"
    ];

    if (!isset($allowedTypes[$mime])) {
        $error = "Invalid poster type. Upload a JPG, JPEG, or PNG image.";
        return null;
    }

    $uploadDir = __DIR__ . "/../uploads/events/";

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $error = "Unable to create the poster upload directory.";
        return null;
    }

    if (!is_writable($uploadDir)) {
        $error = "The poster upload directory is not writable by PHP.";
        return null;
    }

    try {
        $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mime];
    } catch (Throwable $e) {
        $error = "Unable to generate a safe poster filename.";
        return null;
    }

    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
        $error = "Unable to save the poster.";
        return null;
    }

    return $filename;
}

function removeEventPoster(?string $filename): void {
    if (!$filename || !preg_match('/\A[a-f0-9]{32}\.(jpg|png)\z/', $filename)) {
        return;
    }

    $path = __DIR__ . "/../uploads/events/" . $filename;

    if (is_file($path)) {
        @unlink($path);
    }
}

$error = "";

$statusMessages = [
    "added" => "Event added successfully.",
    "updated" => "Event updated successfully.",
    "deleted" => "Event deleted successfully."
];

$successMessage = $statusMessages[(string)($_GET["status"] ?? "")] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $action = (string)($_POST["action"] ?? "");

    if ($action === "add" || $action === "update") {
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
        $title = trim((string)($_POST["title"] ?? ""));
        $description = trim((string)($_POST["description"] ?? ""));
        $eventDate = trim((string)($_POST["event_date"] ?? ""));
        $eventTime = trim((string)($_POST["event_time"] ?? ""));
        $venue = trim((string)($_POST["venue"] ?? ""));
        $capacity = filter_var($_POST["capacity"] ?? null, FILTER_VALIDATE_INT);

        $dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);
        $validDate = $dateObject && $dateObject->format("Y-m-d") === $eventDate;

        $timeObject = DateTime::createFromFormat("!H:i", $eventTime);
        $validTime = $timeObject && $timeObject->format("H:i") === $eventTime;

        if ($action === "update" && (!$id || $id < 1)) {
            $error = "Invalid event ID.";
        } elseif ($title === "" || mb_strlen($title) > 150) {
            $error = "Enter an event title (maximum 150 characters).";
        } elseif ($description === "") {
            $error = "Enter an event description.";
        } elseif (!$validDate) {
            $error = "Enter a valid event date.";
        } elseif (!$validTime) {
            $error = "Enter a valid event time.";
        } elseif ($venue === "" || mb_strlen($venue) > 200) {
            $error = "Enter an event venue (maximum 200 characters).";
        } elseif ($capacity === false || $capacity < 1 || $capacity > 100000) {
            $error = "Enter a valid event capacity (1 to 100000).";
        } else {
            $existingPoster = null;
            $existingEventFound = true;

            if ($action === "update") {
                $find = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");

                if (!$find) {
                    $error = "Unable to prepare the event lookup.";
                    $existingEventFound = false;
                } else {
                    $find->bind_param("i", $id);
                    $find->execute();
                    $result = $find->get_result();
                    $existing = $result->fetch_assoc();
                    $result->free();
                    $find->close();

                    if (!$existing) {
                        $error = "Event not found.";
                        $existingEventFound = false;
                    } else {
                        $existingPoster = $existing["poster"] ?? null;
                    }
                }
            }

            if ($error === "" && $existingEventFound) {
                $uploadError = "";
                $newPoster = saveEventPoster($_FILES["poster"] ?? [], $uploadError);

                if ($newPoster === null) {
                    $error = $uploadError;
                } elseif ($action === "add" && $newPoster === "") {
                    $error = "Please upload an event poster.";
                } else {
                    $posterToSave = $newPoster !== "" ? $newPoster : $existingPoster;

                    if ($action === "add") {
                        $stmt = $conn->prepare(
                            "INSERT INTO events
                            (event_name, description, event_date, event_time, venue, capacity, poster)
                            VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );

                        if ($stmt) {
                            $stmt->bind_param("sssssis", $title, $description, $eventDate, $eventTime, $venue, $capacity, $posterToSave);

                            if ($stmt->execute()) {
                                $stmt->close();
                                redirectEventStatus("added");
                            }

                            $stmt->close();

                            if ($newPoster !== "") {
                                removeEventPoster($newPoster);
                            }

                            $error = "Unable to add the event. Check the database table and entered details.";
                        } else {
                            if ($newPoster !== "") {
                                removeEventPoster($newPoster);
                            }

                            $error = "Unable to prepare the add operation.";
                        }
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE events
                             SET event_name = ?, description = ?, event_date = ?,
                                 event_time = ?, venue = ?, capacity = ?, poster = ?
                             WHERE event_id = ?"
                        );

                        if ($stmt) {
                            $stmt->bind_param("sssssisi", $title, $description, $eventDate, $eventTime, $venue, $capacity, $posterToSave, $id);

                            if ($stmt->execute()) {
                                $stmt->close();

                                if ($newPoster !== "" && $existingPoster !== $newPoster) {
                                    removeEventPoster($existingPoster);
                                }

                                redirectEventStatus("updated");
                            }

                            $stmt->close();

                            if ($newPoster !== "") {
                                removeEventPoster($newPoster);
                            }

                            $error = "Unable to update the event.";
                        } else {
                            if ($newPoster !== "") {
                                removeEventPoster($newPoster);
                            }

                            $error = "Unable to prepare the update operation.";
                        }
                    }
                }
            }
        }
    } elseif ($action === "delete") {
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $error = "Invalid event ID.";
        } else {
            $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");

            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $event = $result->fetch_assoc();
                $result->free();
                $stmt->close();

                if (!$event) {
                    $error = "Event not found.";
                } else {
                    $poster = $event["poster"] ?? null;
                    $delete = $conn->prepare("DELETE FROM events WHERE event_id = ?");

                    if ($delete) {
                        $delete->bind_param("i", $id);

                        if ($delete->execute() && $delete->affected_rows > 0) {
                            $delete->close();
                            removeEventPoster($poster);
                            redirectEventStatus("deleted");
                        }

                        $delete->close();
                        $error = "Unable to delete the event.";
                    } else {
                        $error = "Unable to prepare the delete operation.";
                    }
                }
            } else {
                $error = "Unable to prepare the event lookup.";
            }
        }
    } else {
        $error = "Unknown action.";
    }
}

$editEvent = null;
$editId = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $stmt = $conn->prepare(
        "SELECT event_id, event_name, description, event_date, event_time, venue, capacity, poster
         FROM events WHERE event_id = ?"
    );

    if ($stmt) {
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editEvent = $result->fetch_assoc() ?: null;
        $result->free();
        $stmt->close();
    }
}

$events = [];

$result = $conn->query(
    "SELECT event_id, event_name, description, event_date, event_time, venue, capacity, poster
     FROM events ORDER BY event_date DESC, event_id DESC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    $result->free();
} elseif ($error === "") {
    $error = "Unable to load events. Make sure the events table exists.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Event Management</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .event-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .event-panel {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            margin: 22px 0;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .07);
        }

        .event-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .event-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .event-form input,
        .event-form textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        .event-form textarea {
            min-height: 110px;
            resize: vertical;
        }

        .full {
            grid-column: 1 / -1;
        }

        .event-button {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: 10px 14px;
            background: #0056b3;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
        }

        .event-button.secondary {
            background: #64748b;
        }

        .event-button.danger {
            background: #b42318;
        }

        .event-table-wrap {
            overflow-x: auto;
        }

        .event-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .event-table th,
        .event-table td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .event-table th {
            background: #edf3fc;
            color: #0056b3;
        }

        .event-table img {
            width: 110px;
            height: 75px;
            object-fit: cover;
            border-radius: 6px;
        }

        .event-notice {
            padding: 12px 15px;
            margin: 12px 0;
            border-radius: 8px;
        }

        .event-success {
            background: #e8f7ee;
            color: #17663a;
        }

        .event-error {
            background: #fff0f0;
            color: #a61b1b;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .event-actions form {
            margin: 0;
        }

        .muted {
            color: #64748b;
            font-size: .9rem;
        }

        @media (max-width: 650px) {
            .event-page {
                padding: 12px;
            }

            .event-form {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>
<main class="event-page">

    <header class="event-header">
        <div>
            <h1>StudentHub</h1>
            <p>Admin Event Management</p>
        </div>

        <div class="event-actions">
            <a class="event-button secondary" href="../pages/admin_dashboard.php">Dashboard</a>
            <a class="event-button secondary" href="logout.php">Logout</a>
        </div>
    </header>

    <?php if ($successMessage !== ""): ?>
        <div class="event-notice event-success" role="status">
            <?php echo h($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="event-notice event-error" role="alert">
            <?php echo h($error); ?>
        </div>
    <?php endif; ?>
    <div id="ajaxNotice" class="event-notice" role="status" hidden></div>

    <section class="event-panel">
        <h2><?php echo $editEvent ? "Update Event" : "Add New Event"; ?></h2>

        <p class="muted">Poster formats: JPG, JPEG, PNG. Maximum file size: 2 MB.</p>

        <form id="eventForm" class="event-form" method="post"
              action="event_management.php<?php echo $editEvent ? "?edit=" . (int)$editEvent["event_id"] : ""; ?>"
              enctype="multipart/form-data">

            <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
            <input type="hidden" name="action" value="<?php echo $editEvent ? "update" : "add"; ?>">

            <?php if ($editEvent): ?>
                <input type="hidden" name="id" value="<?php echo (int)$editEvent["event_id"]; ?>">
            <?php endif; ?>

            <div>
                <label for="title">Event Title</label>
                <input id="title" name="title" maxlength="150" required
                       value="<?php echo h($editEvent["event_name"] ?? ""); ?>">
            </div>

            <div>
                <label for="venue">Venue</label>
                <input id="venue" name="venue" maxlength="200" required
                       value="<?php echo h($editEvent["venue"] ?? ""); ?>">
            </div>

            <div>
                <label for="capacity">Capacity</label>
                <input id="capacity" name="capacity" type="number" min="1" max="100000" required
                       value="<?php echo h($editEvent["capacity"] ?? "50"); ?>">
            </div>

            <div>
                <label for="event_date">Event Date</label>
                <input id="event_date" name="event_date" type="date" required
                       value="<?php echo h($editEvent["event_date"] ?? ""); ?>">
            </div>

            <div>
                <label for="event_time">Event Time</label>
                <input id="event_time" name="event_time" type="time" required
                       value="<?php echo h(isset($editEvent["event_time"]) ? substr($editEvent["event_time"], 0, 5) : ""); ?>">
            </div>

            <div class="full">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo h($editEvent["description"] ?? ""); ?></textarea>
            </div>

            <div class="full">
                <label for="poster">
                    Event Poster <?php echo $editEvent ? "(optional when keeping the current poster)" : "(required)"; ?>
                </label>

                <input id="poster" name="poster" type="file"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       <?php echo $editEvent ? "" : "required"; ?>>

                <?php if ($editEvent && !empty($editEvent["poster"])): ?>
                    <p class="muted">Current poster:</p>
                    <img src="../uploads/events/<?php echo rawurlencode($editEvent["poster"]); ?>"
                         alt="Current event poster"
                         style="max-width:180px;max-height:130px;object-fit:contain;">
                <?php endif; ?>
            </div>

            <div class="full">
                <button class="event-button" type="submit">
                    <?php echo $editEvent ? "Save Changes" : "Add Event"; ?>
                </button>

                <?php if ($editEvent): ?>
                    <a class="event-button secondary" href="event_management.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="event-panel">
        <h2 id="eventCount">All Events (<?php echo count($events); ?>)</h2>

        <div class="event-table-wrap">
            <table class="event-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Event</th>
                    <th>Date &amp; Time</th>
                    <th>Venue</th>
                    <th>Capacity</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody id="eventsTableBody">
                <?php if (!$events): ?>
                    <tr><td colspan="7">No events have been added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo (int)$event["event_id"]; ?></td>

                            <td>
                                <?php if (!empty($event["poster"])): ?>
                                    <img src="../uploads/events/<?php echo rawurlencode($event["poster"]); ?>"
                                         alt="<?php echo h($event["event_name"]); ?> poster">
                                <?php else: ?>
                                    <span class="muted">No poster</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong><?php echo h($event["event_name"]); ?></strong><br>
                                <?php echo nl2br(h($event["description"])); ?>
                            </td>

                            <td>
                                <?php echo h($event["event_date"]); ?><br>
                                <?php echo h(substr($event["event_time"], 0, 5)); ?>
                            </td>

                            <td><?php echo h($event["venue"]); ?></td>
                            <td><?php echo (int)$event["capacity"]; ?></td>

                            <td>
                                <div class="event-actions">
                                    <a class="event-button"
                                       href="event_management.php?edit=<?php echo (int)$event["event_id"]; ?>">Edit</a>

                                    <form class="delete-event-form" method="post" action="event_management.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$event["event_id"]; ?>">

                                        <button class="event-button danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
\n<script>
(() => {
    const apiUrl = 'events_api.php';
    const eventForm = document.getElementById('eventForm');
    const tableBody = document.getElementById('eventsTableBody');
    const eventCount = document.getElementById('eventCount');
    const notice = document.getElementById('ajaxNotice');

    function csrfToken() {
        return document.querySelector('input[name="csrf_token"]')?.value || '';
    }

    function showNotice(message, isError = false) {
        notice.textContent = message;
        notice.className = 'event-notice ' + (isError ? 'event-error' : 'event-success');
        notice.hidden = false;
    }

    async function requestApi(method, body = null, extraHeaders = {}) {
        const options = {
            method,
            headers: { 'X-CSRF-Token': csrfToken(), ...extraHeaders }
        };
        if (body !== null) options.body = body;
        const response = await fetch(apiUrl, options);
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); }
        catch (_) { throw new Error('The API returned a non-JSON response. Check PHP errors and events_api.php.'); }
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'The request failed.');
        }
        return data;
    }

    function makeCell(text) {
        const td = document.createElement('td');
        td.textContent = text == null ? '' : String(text);
        return td;
    }

    function renderEvents(events) {
        tableBody.replaceChildren();
        eventCount.textContent = `All Events (${events.length})`;
        if (!events.length) {
            const row = document.createElement('tr');
            const cell = makeCell('No events have been added yet.');
            cell.colSpan = 7;
            row.appendChild(cell);
            tableBody.appendChild(row);
            return;
        }

        for (const event of events) {
            const row = document.createElement('tr');
            row.appendChild(makeCell(event.event_id));

            const posterCell = document.createElement('td');
            if (event.poster) {
                const img = document.createElement('img');
                img.src = '../uploads/events/' + encodeURIComponent(event.poster);
                img.alt = (event.event_name || 'Event') + ' poster';
                posterCell.appendChild(img);
            } else {
                const muted = document.createElement('span');
                muted.className = 'muted';
                muted.textContent = 'No poster';
                posterCell.appendChild(muted);
            }
            row.appendChild(posterCell);

            const eventCell = document.createElement('td');
            const strong = document.createElement('strong');
            strong.textContent = event.event_name || '';
            eventCell.appendChild(strong);
            eventCell.appendChild(document.createElement('br'));
            const description = document.createElement('span');
            description.textContent = event.description || '';
            eventCell.appendChild(description);
            row.appendChild(eventCell);

            const dateCell = document.createElement('td');
            dateCell.textContent = (event.event_date || '') + '\n' + (event.event_time ? String(event.event_time).substring(0, 5) : '');
            row.appendChild(dateCell);
            row.appendChild(makeCell(event.venue));
            row.appendChild(makeCell(event.capacity));

            const actionsCell = document.createElement('td');
            const actions = document.createElement('div');
            actions.className = 'event-actions';
            const edit = document.createElement('a');
            edit.className = 'event-button';
            edit.href = 'event_management.php?edit=' + encodeURIComponent(event.event_id);
            edit.textContent = 'Edit';
            actions.appendChild(edit);

            const deleteForm = document.createElement('form');
            deleteForm.className = 'delete-event-form';
            deleteForm.method = 'post';
            deleteForm.action = 'event_management.php';
            const token = document.createElement('input');
            token.type = 'hidden'; token.name = 'csrf_token'; token.value = csrfToken();
            const action = document.createElement('input');
            action.type = 'hidden'; action.name = 'action'; action.value = 'delete';
            const id = document.createElement('input');
            id.type = 'hidden'; id.name = 'id'; id.value = event.event_id;
            deleteForm.append(token, action, id);
            const deleteButton = document.createElement('button');
            deleteButton.className = 'event-button danger';
            deleteButton.type = 'submit';
            deleteButton.textContent = 'Delete';
            deleteForm.appendChild(deleteButton);
            actions.appendChild(deleteForm);
            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);
            tableBody.appendChild(row);
        }
    }

    async function loadEvents() {
        const data = await requestApi('GET');
        renderEvents(data.events || []);
    }

    function resetFormToAddMode() {
        eventForm.reset();
        const actionInput = eventForm.querySelector('input[name="action"]');
        if (actionInput) actionInput.value = 'add';
        const idInput = eventForm.querySelector('input[name="id"]');
        if (idInput) idInput.remove();
        const heading = eventForm.closest('.event-panel').querySelector('h2');
        if (heading) heading.textContent = 'Add New Event';
        const submitButton = eventForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.textContent = 'Add Event';
        const cancelLink = eventForm.querySelector('a[href*="event_management.php"]');
        if (cancelLink && cancelLink.textContent.trim() === 'Cancel') cancelLink.remove();
        const posterInput = document.getElementById('poster');
        if (posterInput) posterInput.required = true;
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', 'event_management.php');
        }
    }

    eventForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = eventForm.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        try {
            const formData = new FormData(eventForm);
            const action = formData.get('action');
            if (action === 'add') {
                const data = await requestApi('POST', formData);
                showNotice(data.message || 'Event added successfully.');
            } else {
                const poster = formData.get('poster');
                if (poster instanceof File && poster.size > 0) {
                    formData.set('_method', 'PUT');
                    const data = await requestApi('POST', formData, { 'X-HTTP-Method-Override': 'PUT' });
                    showNotice(data.message || 'Event updated successfully.');
                } else {
                    const payload = {
                        id: Number(formData.get('id')),
                        title: formData.get('title'),
                        description: formData.get('description'),
                        event_date: formData.get('event_date'),
                        event_time: formData.get('event_time'),
                        venue: formData.get('venue'),
                        capacity: Number(formData.get('capacity'))
                    };
                    const data = await requestApi('PUT', JSON.stringify(payload), { 'Content-Type': 'application/json' });
                    showNotice(data.message || 'Event updated successfully.');
                }
            }
            resetFormToAddMode();
            await loadEvents();
        } catch (error) {
            showNotice(error.message || 'Unable to save the event.', true);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = eventForm.querySelector('input[name="action"]')?.value === 'update' ? 'Save Changes' : 'Add Event';
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('.delete-event-form');
        if (!form) return;
        event.preventDefault();
        if (!window.confirm('Delete this event and its poster?')) return;
        const id = Number(form.querySelector('input[name="id"]')?.value);
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        try {
            const data = await requestApi('DELETE', JSON.stringify({ id }), { 'Content-Type': 'application/json' });
            showNotice(data.message || 'Event deleted successfully.');
            await loadEvents();
        } catch (error) {
            showNotice(error.message || 'Unable to delete the event.', true);
            if (button) button.disabled = false;
        }
    });

    loadEvents().catch(error => showNotice(error.message || 'Unable to load events from the API.', true));
})();
</script>

</body>
</html>