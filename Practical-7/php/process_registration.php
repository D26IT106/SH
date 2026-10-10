<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}


$name = trim($_POST["studentName"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$terms = isset($_POST["terms"]);


$errors = array();


/* Name */

if ($name === "") {

    $errors[] = "Name is required.";

} elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {

    $errors[] =
        "Name should contain letters and spaces only.";

}


/* Email */

if ($email === "") {

    $errors[] = "Email is required.";

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errors[] =
        "Please enter a valid email address.";

}


/* Mobile */

if ($mobile === "") {

    $errors[] =
        "Mobile number is required.";

} elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {

    $errors[] =
        "Please enter a valid 10-digit mobile number.";

}


/* Password */

if ($password === "") {

    $errors[] =
        "Password is required.";

} elseif (
    strlen($password) < 8 ||
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password) ||
    !preg_match("/[@$!%*?&]/", $password)
) {

    $errors[] =
        "Password must contain at least 8 characters, " .
        "one uppercase letter, one lowercase letter, " .
        "one number and one special character.";

}


/* Confirm Password */

if ($confirmPassword === "") {

    $errors[] =
        "Please confirm your password.";

} elseif ($password !== $confirmPassword) {

    $errors[] =
        "Passwords do not match.";

}


/* Course */

if ($course === "") {

    $errors[] =
        "Please select your course.";

}


/* Year */

if ($year === "") {

    $errors[] =
        "Please select your year.";

}


/* Gender */

if ($gender === "") {

    $errors[] =
        "Please select your gender.";

}


/* Terms */

if (!$terms) {

    $errors[] =
        "You must accept the Terms and Conditions.";

}


/* Duplicate Mobile Number */

$csvFile = "../data/registrations.csv";

if (
    $mobile !== "" &&
    preg_match("/^[6-9][0-9]{9}$/", $mobile) &&
    file_exists($csvFile)
) {

    $file = fopen($csvFile, "r");

    if ($file !== false) {

        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {

            if (isset($row[2]) && $row[2] === $mobile) {

                $errors[] =
                    "This mobile number is already registered.";

                break;

            }

        }

        fclose($file);

    }

}


/* Display Errors */

if (count($errors) > 0) {

    echo "<h2>Registration Failed</h2>";

    echo "<ul>";

    foreach ($errors as $error) {

        echo "<li>" .
             htmlspecialchars($error) .
             "</li>";

    }

    echo "</ul>";

    echo "<a href='../pages/register.html'>Go Back</a>";

    exit;
}


/* Sanitize Data */

$name =
    htmlspecialchars($name, ENT_QUOTES, "UTF-8");

$email =
    htmlspecialchars($email, ENT_QUOTES, "UTF-8");

$mobile =
    htmlspecialchars($mobile, ENT_QUOTES, "UTF-8");

$course =
    htmlspecialchars($course, ENT_QUOTES, "UTF-8");

$year =
    htmlspecialchars($year, ENT_QUOTES, "UTF-8");

$gender =
    htmlspecialchars($gender, ENT_QUOTES, "UTF-8");


/* Hash Password */

$hashedPassword =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/* Create Data Folder */

$dataFolder = "../data";

if (!is_dir($dataFolder)) {

    mkdir($dataFolder, 0777, true);

}

$csvFile =
    $dataFolder . "/registrations.csv";


/* Create CSV Header */

if (!file_exists($csvFile)) {

    $file = fopen($csvFile, "w");

    if ($file === false) {

        die(
            "Unable to create registration storage file."
        );

    }

    fputcsv(
        $file,
        array(
            "Name",
            "Email",
            "Mobile",
            "Password",
            "Course",
            "Year",
            "Gender"
        )
    );

    fclose($file);

}


/* Store Registration */

$file = fopen($csvFile, "a");

if ($file === false) {

    die(
        "Unable to open registration storage file."
    );

}


fputcsv(
    $file,
    array(
        $name,
        $email,
        $mobile,
        $hashedPassword,
        $course,
        $year,
        $gender
    )
);


fclose($file);


/* Success */

echo "<h2>Registration Successful!</h2>";

echo "<p>Your registration has been submitted successfully.</p>";

echo "<p>Your information has been stored safely.</p>";

echo "<a href='../pages/register.html'>Back to Registration</a>";

echo "<br><br>";

echo "<a href='view_registrations.php'>View Registered Students</a>";

?>