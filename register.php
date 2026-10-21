
<?php
require_once "db.php";

$message = "";
$messageType = "";
$studentNumber = "";
$firstName = "";
$lastName = "";
$course = "";
$yearLevel = "";

$allowedYears = [
    "1st Year",
    "2nd Year",
    "3rd Year",
    "4th Year",
    "5th Year"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentNumber = trim($_POST["student_number"] ?? "");
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $yearLevel = trim($_POST["year_level"] ?? "");

    if (
        $studentNumber === "" ||
        $firstName === "" ||
        $lastName === "" ||
        $course === "" ||
        !in_array($yearLevel, $allowedYears, true) ||
        strlen($studentNumber) > 30 ||
        strlen($firstName) > 100 ||
        strlen($lastName) > 100 ||
        strlen($course) > 100
    ) {
        $message = "Please complete all fields correctly.";
        $messageType = "error";
    } else {
        try {
            // Check whether the student number is already registered.
            $check = $pdo->prepare(
                "SELECT student_id
                 FROM students
                 WHERE student_number = ?
                 LIMIT 1"
            );

            $check->execute([$studentNumber]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $message = "This student number is already registered.";
                $messageType = "error";
            } else {
                // Register the new student.
                $stmt = $pdo->prepare(
                    "INSERT INTO students
                    (student_number, first_name, last_name, course, year_level)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $studentNumber,
                    $firstName,
                    $lastName,
                    $course,
                    $yearLevel
                ]);

                $message = "Student registered successfully!";
                $messageType = "success";

                // Clear the form after successful registration.
                $studentNumber = "";
                $firstName = "";
                $lastName = "";
                $course = "";
                $yearLevel = "";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            // Handle a duplicate student number if the database
            // has a UNIQUE constraint on student_number.
            if ($e->getCode() === "23000") {
                $message = "This student number may already be registered.";
            } else {
                $message = "Unable to save the record. Please try again.";
            }

            $messageType = "error";
        }
    }
}

function safe($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | MATS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #123b70;
            text-align: center;
        }

        .subtitle {
            color: #64748b;
            text-align: center;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus, select:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }

        button, .back-link {
            display: block;
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            border: none;
            border-radius: 6px;
            text-align: center;
            font-size: 15px;
            text-decoration: none;
        }

        button {
            background: #123b70;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #1d4f91;
        }

        .back-link {
            color: #123b70;
            background: #eaf1f9;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>Student Registration</h1>

    <p class="subtitle">
        MATS College of Technology<br>
        Enter your information below.
    </p>

    <?php if ($message !== ""): ?>
        <div
            class="message <?= safe($messageType) ?>"
            role="status"
            aria-live="polite"
        >
            <?= safe($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label for="student_number">Student Number</label>
        <input
            type="text"
            id="student_number"
            name="student_number"
            maxlength="30"
            value="<?= safe($studentNumber) ?>"
            required
        >

        <label for="first_name">First Name</label>
        <input
            type="text"
            id="first_name"
            name="first_name"
            maxlength="100"
            value="<?= safe($firstName) ?>"
            required
        >

        <label for="last_name">Last Name</label>
        <input
            type="text"
            id="last_name"
            name="last_name"
            maxlength="100"
            value="<?= safe($lastName) ?>"
            required
        >

        <label for="course">Course</label>
        <input
            type="text"
            id="course"
            name="course"
            maxlength="100"
            value="<?= safe($course) ?>"
            placeholder="Example: BSIT"
            required
        >

        <label for="year_level">Year Level</label>
        <select id="year_level" name="year_level" required>
            <option value="">Select year level</option>

            <?php foreach ($allowedYears as $year): ?>
                <option
                    value="<?= safe($year) ?>"
                    <?= $yearLevel === $year ? "selected" : "" ?>
                >
                    <?= safe($year) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Register Student</button>
    </form>

    <a class="back-link" href="index.php">
        Back to Home
    </a>
</div>
</body>
</html>
