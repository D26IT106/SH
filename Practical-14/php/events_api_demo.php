
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.html");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Admin access required.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>StudentHub - Fetch API Events</title>
<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; padding: 16px; background: #f5f7fb; }
section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 10px; }
form { display: grid; gap: 12px; }
input, textarea, button { padding: 10px; font: inherit; }
button { cursor: pointer; border: 0; border-radius: 5px; background: #0056b3; color: white; }
.delete { background: #b42318; }
.secondary { background: #64748b; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
.wrapper { overflow-x: auto; }
#message { margin: 12px 0; }
</style>
</head>
<body>

<h1>StudentHub Event Management</h1>
<p>Manage events asynchronously using PHP JSON API and Fetch API.</p>
<p id="message" role="status"></p>

<section>
<h2 id="formTitle">Add Event</h2>

<form id="eventForm">
    <input type="hidden" id="event_id">

    <label>Event Name
        <input id="event_name" maxlength="150" required>
    </label>

    <label>Description
        <textarea id="description" required></textarea>
    </label>

    <label>Event Date
        <input type="date" id="event_date" required>
    </label>

    <label>Event Time
        <input type="time" id="event_time" required>
    </label>

    <label>Venue
        <input id="venue" maxlength="200" required>
    </label>

    <label>Capacity
        <input type="number" id="capacity" min="1" max="100000" value="50" required>
    </label>

    <button type="submit">Save Event</button>
    <button type="button" class="secondary" id="cancelEdit" hidden>Cancel Edit</button>
</form>
</section>

<section>
<h2>Event List</h2>
<div class="wrapper">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Event</th>
    <th>Date</th>
    <th>Venue</th>
    <th>Capacity</th>
    <th>Actions</th>
</tr>
</thead>
<tbody id="eventList">
<tr><td colspan="6">Loading events...</td></tr>
</tbody>
</table>
</div>
</section>

<script>
const API = "events_api.php";
const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;

const form = document.getElementById("eventForm");
const list = document.getElementById("eventList");
const message = document.getElementById("message");

function showMessage(text, isError = false) {
    message.textContent = text;
    message.style.color = isError ? "#b42318" : "#17663a";
}

async function apiRequest(method, data = null) {
    const options = {
        method,
        headers: {
            "Accept": "application/json",
            "X-CSRF-Token": CSRF_TOKEN
        }
    };

    if (data !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
    }

    const response = await fetch(API, options);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

async function loadEvents() {
    try {
        const result = await apiRequest("GET");
        list.replaceChildren();

        if (result.events.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = 6;
            cell.textContent = "No events found.";
            row.appendChild(cell);
            list.appendChild(row);
            return;
        }

        result.events.forEach(event => {
            const row = document.createElement("tr");

            [
                event.event_id,
                event.event_name,
                event.event_date,
                event.venue,
                event.capacity
            ].forEach(value => {
                const cell = document.createElement("td");
                cell.textContent = value ?? "";
                row.appendChild(cell);
            });

            const actions = document.createElement("td");

            const editButton = document.createElement("button");
            editButton.textContent = "Edit";
            editButton.type = "button";
            editButton.className = "secondary";
            editButton.addEventListener("click", () => editEvent(event));

            const deleteButton = document.createElement("button");
            deleteButton.textContent = "Delete";
            deleteButton.type = "button";
            deleteButton.className = "delete";
            deleteButton.addEventListener("click", () => deleteEvent(event.event_id));

            actions.append(editButton, document.createTextNode(" "), deleteButton);
            row.appendChild(actions);
            list.appendChild(row);
        });
    } catch (error) {
        showMessage(error.message, true);
    }
}

function editEvent(event) {
    document.getElementById("event_id").value = event.event_id;
    document.getElementById("event_name").value = event.event_name;
    document.getElementById("description").value = event.description;
    document.getElementById("event_date").value = event.event_date;
    document.getElementById("event_time").value = (event.event_time || "").slice(0, 5);
    document.getElementById("venue").value = event.venue;
    document.getElementById("capacity").value = event.capacity;

    document.getElementById("formTitle").textContent = "Update Event";
    document.getElementById("cancelEdit").hidden = false;
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function resetForm() {
    form.reset();
    document.getElementById("event_id").value = "";
    document.getElementById("capacity").value = 50;
    document.getElementById("formTitle").textContent = "Add Event";
    document.getElementById("cancelEdit").hidden = true;
}

form.addEventListener("submit", async event => {
    event.preventDefault();

    const id = document.getElementById("event_id").value;

    const data = {
        event_name: document.getElementById("event_name").value.trim(),
        description: document.getElementById("description").value.trim(),
        event_date: document.getElementById("event_date").value,
        event_time: document.getElementById("event_time").value,
        venue: document.getElementById("venue").value.trim(),
        capacity: Number(document.getElementById("capacity").value)
    };

    if (id) {
        data.event_id = Number(id);
    }

    try {
        const result = await apiRequest(id ? "PUT" : "POST", data);
        showMessage(result.message);
        resetForm();
        await loadEvents();
    } catch (error) {
        showMessage(error.message, true);
    }
});

async function deleteEvent(id) {
    if (!confirm("Are you sure you want to delete this event?")) {
        return;
    }

    try {
        const result = await apiRequest("DELETE", { event_id: Number(id) });
        showMessage(result.message);
        await loadEvents();
    } catch (error) {
        showMessage(error.message, true);
    }
}

document.getElementById("cancelEdit").addEventListener("click", resetForm);

loadEvents();
</script>
</body>
</html>