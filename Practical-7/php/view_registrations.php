<?php

$csvFile = "../data/registrations.csv";


if (!file_exists($csvFile)) {

    die("No registration records found.");

}


$file = fopen($csvFile, "r");


if ($file === false) {

    die("Unable to open registration file.");

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>StudentHub - Registrations</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f5f5f5;
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #333;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

    </style>

</head>

<body>

    <h1>Registered Students</h1>

    <table>

        <thead>

            <tr>

                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Course</th>
                <th>Year</th>
                <th>Gender</th>

            </tr>

        </thead>

        <tbody>

<?php

fgetcsv($file);

while (($row = fgetcsv($file)) !== false) {

    echo "<tr>";

    echo "<td>" .
         htmlspecialchars($row[0]) .
         "</td>";

    echo "<td>" .
         htmlspecialchars($row[1]) .
         "</td>";

    echo "<td>" .
         htmlspecialchars($row[2]) .
         "</td>";

    echo "<td>" .
         htmlspecialchars($row[4]) .
         "</td>";

    echo "<td>" .
         htmlspecialchars($row[5]) .
         "</td>";

    echo "<td>" .
         htmlspecialchars($row[6]) .
         "</td>";

    echo "</tr>";

}

fclose($file);

?>

        </tbody>

    </table>

</body>

</html>