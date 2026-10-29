<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function apiResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    apiResponse(401, ['success' => false, 'message' => 'Please log in again.']);
}
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    apiResponse(403, ['success' => false, 'message' => 'Administrator access is required.']);
}
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 900) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    apiResponse(401, ['success' => false, 'message' => 'Your session expired. Please log in again.']);
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/db_connect_mysqli.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    apiResponse(500, ['success' => false, 'message' => 'Database connection failed.']);
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'POST' && strtoupper((string)($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_POST['_method'] ?? '')) === 'PUT') {
    $method = 'PUT';
}

if ($method !== 'GET') {
    $headerToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $formToken = (string)($_POST['csrf_token'] ?? '');
    $token = $headerToken !== '' ? $headerToken : $formToken;
    if ($token === '' || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        apiResponse(403, ['success' => false, 'message' => 'Invalid request token. Refresh the page and try again.']);
    }
}

function readInput(): array {
    if (!empty($_POST)) return $_POST;
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    return is_array($decoded) ? $decoded : [];
}

function getEventFields(array $input): array {
    $id = filter_var($input['id'] ?? $input['event_id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim((string)($input['title'] ?? $input['event_name'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $date = trim((string)($input['event_date'] ?? ''));
    $time = trim((string)($input['event_time'] ?? ''));
    $venue = trim((string)($input['venue'] ?? ''));
    $capacity = filter_var($input['capacity'] ?? null, FILTER_VALIDATE_INT);
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    $timeObject = DateTime::createFromFormat('!H:i', $time);
    $validDate = $dateObject && $dateObject->format('Y-m-d') === $date;
    $validTime = $timeObject && $timeObject->format('H:i') === $time;
    if ($title === '' || mb_strlen($title) > 150) return [null, 'Enter an event title (maximum 150 characters).'];
    if ($description === '') return [null, 'Enter an event description.'];
    if (!$validDate) return [null, 'Enter a valid event date.'];
    if (!$validTime) return [null, 'Enter a valid event time.'];
    if ($venue === '' || mb_strlen($venue) > 200) return [null, 'Enter an event venue (maximum 200 characters).'];
    if ($capacity === false || $capacity < 1 || $capacity > 100000) return [null, 'Enter a valid event capacity (1 to 100000).'];
    return [['id' => $id, 'title' => $title, 'description' => $description, 'date' => $date, 'time' => $time, 'venue' => $venue, 'capacity' => $capacity], null];
}

function savePoster(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) return [null, 'Invalid poster upload.'];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return ['', null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Poster upload failed. Please try again.'];
    if (!isset($file['size']) || $file['size'] < 1 || $file['size'] > 2 * 1024 * 1024) return [null, 'Poster must be larger than 0 bytes and no bigger than 2 MB.'];
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return [null, 'Invalid uploaded file.'];
    if (!class_exists('finfo')) return [null, 'Server fileinfo support is required to validate poster types.'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) return [null, 'Invalid poster type. Upload a JPG, JPEG, or PNG image.'];
    $dir = __DIR__ . '/../uploads/events/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) return [null, 'Unable to create the poster upload directory.'];
    if (!is_writable($dir)) return [null, 'The poster upload directory is not writable by PHP.'];
    try { $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime]; }
    catch (Throwable $e) { return [null, 'Unable to generate a safe poster filename.']; }
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) return [null, 'Unable to save the poster.'];
    return [$filename, null];
}
function removePoster(?string $filename): void {
    if (!$filename || !preg_match('/\A[a-f0-9]{32}\.(jpg|png)\z/', $filename)) return;
    $path = __DIR__ . '/../uploads/events/' . $filename;
    if (is_file($path)) @unlink($path);
}
function fetchEvents(mysqli $conn): array {
    $events = [];
    $result = $conn->query('SELECT event_id, event_name, description, event_date, event_time, venue, capacity, poster FROM events ORDER BY event_date DESC, event_id DESC');
    if (!$result) apiResponse(500, ['success' => false, 'message' => 'Unable to load events. Check the events table schema.']);
    while ($row = $result->fetch_assoc()) $events[] = $row;
    $result->free();
    return $events;
}

if ($method === 'GET') {
    apiResponse(200, ['success' => true, 'events' => fetchEvents($conn)]);
}

$input = readInput();
if ($method === 'POST') {
    [$fields, $validationError] = getEventFields($input);
    if ($validationError) apiResponse(422, ['success' => false, 'message' => $validationError]);
    [$poster, $posterError] = savePoster($_FILES['poster'] ?? []);
    if ($posterError) apiResponse(422, ['success' => false, 'message' => $posterError]);
    if ($poster === '') apiResponse(422, ['success' => false, 'message' => 'Please upload an event poster.']);
    $stmt = $conn->prepare('INSERT INTO events (event_name, description, event_date, event_time, venue, capacity, poster) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) { removePoster($poster); apiResponse(500, ['success' => false, 'message' => 'Unable to prepare the add operation.']); }
    $stmt->bind_param('sssssis', $fields['title'], $fields['description'], $fields['date'], $fields['time'], $fields['venue'], $fields['capacity'], $poster);
    if (!$stmt->execute()) { $stmt->close(); removePoster($poster); apiResponse(500, ['success' => false, 'message' => 'Unable to add the event.']); }
    $newId = $conn->insert_id;
    $stmt->close();
    apiResponse(201, ['success' => true, 'message' => 'Event added successfully.', 'event_id' => $newId]);
}

if ($method === 'PUT') {
    [$fields, $validationError] = getEventFields($input);
    if ($validationError) apiResponse(422, ['success' => false, 'message' => $validationError]);
    $id = filter_var($input['id'] ?? $input['event_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) apiResponse(422, ['success' => false, 'message' => 'Invalid event ID.']);
    $find = $conn->prepare('SELECT poster FROM events WHERE event_id = ?');
    if (!$find) apiResponse(500, ['success' => false, 'message' => 'Unable to prepare the event lookup.']);
    $find->bind_param('i', $id); $find->execute(); $found = $find->get_result()->fetch_assoc(); $find->close();
    if (!$found) apiResponse(404, ['success' => false, 'message' => 'Event not found.']);
    $newPoster = '';
    if (isset($_FILES['poster']) && ($_FILES['poster']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        [$newPoster, $posterError] = savePoster($_FILES['poster']);
        if ($posterError) apiResponse(422, ['success' => false, 'message' => $posterError]);
    }
    $poster = $newPoster !== '' ? $newPoster : ($found['poster'] ?? null);
    $stmt = $conn->prepare('UPDATE events SET event_name = ?, description = ?, event_date = ?, event_time = ?, venue = ?, capacity = ?, poster = ? WHERE event_id = ?');
    if (!$stmt) { removePoster($newPoster); apiResponse(500, ['success' => false, 'message' => 'Unable to prepare the update operation.']); }
    $stmt->bind_param('sssssisi', $fields['title'], $fields['description'], $fields['date'], $fields['time'], $fields['venue'], $fields['capacity'], $poster, $id);
    if (!$stmt->execute()) { $stmt->close(); removePoster($newPoster); apiResponse(500, ['success' => false, 'message' => 'Unable to update the event.']); }
    $stmt->close();
    if ($newPoster !== '') removePoster($found['poster'] ?? null);
    apiResponse(200, ['success' => true, 'message' => 'Event updated successfully.']);
}

if ($method === 'DELETE') {
    $id = filter_var($input['id'] ?? $input['event_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) apiResponse(422, ['success' => false, 'message' => 'Invalid event ID.']);
    $find = $conn->prepare('SELECT poster FROM events WHERE event_id = ?');
    if (!$find) apiResponse(500, ['success' => false, 'message' => 'Unable to prepare the event lookup.']);
    $find->bind_param('i', $id); $find->execute(); $event = $find->get_result()->fetch_assoc(); $find->close();
    if (!$event) apiResponse(404, ['success' => false, 'message' => 'Event not found.']);
    $delete = $conn->prepare('DELETE FROM events WHERE event_id = ?');
    if (!$delete) apiResponse(500, ['success' => false, 'message' => 'Unable to prepare the delete operation.']);
    $delete->bind_param('i', $id);
    if (!$delete->execute() || $delete->affected_rows < 1) { $delete->close(); apiResponse(500, ['success' => false, 'message' => 'Unable to delete the event.']); }
    $delete->close(); removePoster($event['poster'] ?? null);
    apiResponse(200, ['success' => true, 'message' => 'Event deleted successfully.']);
}

header('Allow: GET, POST, PUT, DELETE');
apiResponse(405, ['success' => false, 'message' => 'Method not allowed.']);