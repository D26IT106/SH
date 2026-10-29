<?php
require "db_connect.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $sql = "SELECT
                event_id,
                event_name,
                description,
                event_date,
                venue,
                capacity
            FROM events
            ORDER BY event_date ASC";

    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedEvents = [];

    foreach ($events as $event) {
        $formattedEvents[] = [
            "id" => $event["event_id"],
            "title" => $event["event_name"],
            "category" => "Event",
            "description" => $event["description"],
            "date" => $event["event_date"],
            "location" => $event["venue"],
            "capacity" => $event["capacity"]
        ];
    }

    echo json_encode($formattedEvents);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "error" => "Unable to fetch events"
    ]);
}
?>