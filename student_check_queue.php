
<?php
require_once 'db.php';

$studentNumber = '';
$queues = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNumber = trim($_POST['student_number'] ?? '');

    if ($studentNumber === '') {
        $error = 'Please enter your student number.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT
                s.first_name,
                s.last_name,
                q.queue_number,
                q.transaction_type,
                q.status,
                q.created_at
             FROM students s
             INNER JOIN queue_transactions q
                ON s.student_id = q.student_id
             WHERE s.student_number = ?
               AND q.queue_date = CURDATE()
             ORDER BY q.created_at DESC"
        );

        $stmt->execute([$studentNumber]);
        $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$queues) {
            $error = 'No queue records were found for this student today.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check My Queue | MATS</title>
    <meta http-equiv="refresh" content="30">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 25px 15px;
        }

        .container {
            max-width: 750px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #173b70;
            text-align: center;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #173b70;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .error {
            padding: 12px;
            background: #ffe8e8;
            color: #a40000;
            border-radius: 6px;
            margin: 15px 0;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #173b70;
            color: white;
        }

        .Waiting {
            color: #9a6700;
            font-weight: bold;
        }

        .Serving {
            color: #0969da;
            font-weight: bold;
        }

        .Completed {
            color: #176534;
            font-weight: bold;
        }

        .Cancelled {
            color: #a40000;
            font-weight: bold;
        }

        .links {
            text-align: center;
            margin-top: 25px;
        }

        a {
            color: #173b70;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Check My Queue</h1>
    <p style="text-align:center;">
        Enter your student number to view today's queue records.
    </p>

    <form method="POST" action="student_check_queue.php">
        <label for="student_number">Student Number</label>
        <input
            type="text"
            id="student_number"
            name="student_number"
            value="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Enter your student number"
            required
        >

        <button type="submit">Check Queue Status</button>
    </form>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($queues): ?>
        <h2>Queue Records</h2>

        <p>
            Student:
            <strong>
                <?= htmlspecialchars(
                    $queues[0]['first_name'] . ' ' . $queues[0]['last_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Queue Number</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Time Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queues as $queue): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($queue['queue_number'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($queue['transaction_type'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="<?= htmlspecialchars($queue['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($queue['status'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($queue['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="links">
        <a href="student_queue.php">Request a Queue</a>
        |
        <a href="queue_status.php">Public Queue Status</a>
    </div>
</div>

</body>
</html>
