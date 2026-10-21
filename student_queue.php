<?php
session_start();
require_once 'db.php';

$message = '';
$messageType = '';
$student = null;
$studentNumber = '';
$queueDetails = null;

$allowedTypes = [
    'Tuition Fee Payment',
    'Enrollment Fee',
    'Tuition Balance',
    'White Form',
    'Intramurals Payment',
    'Credentials Payment'
];

$prefixes = [
    'Tuition Fee Payment' => 'TF',
    'Enrollment Fee' => 'EF',
    'Tuition Balance' => 'TB',
    'White Form' => 'WF',
    'Intramurals Payment' => 'IP',
    'Credentials Payment' => 'CP'
];

$transactionType = 'Tuition Fee Payment';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $service = $_GET['service'] ?? '';

    if (is_string($service) && in_array($service, $allowedTypes, true)) {
        $transactionType = $service;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNumber = trim($_POST['student_number'] ?? '');
    $submittedType = $_POST['transaction_type'] ?? '';

    if (is_string($submittedType)) {
        $transactionType = $submittedType;
    }

    if (
        $studentNumber === '' ||
        !preg_match('/^[A-Za-z0-9-]{1,30}$/', $studentNumber)
    ) {
        $message = 'Please enter a valid student number.';
        $messageType = 'error';

    } elseif (
        !is_string($submittedType) ||
        !in_array($submittedType, $allowedTypes, true)
    ) {
        $message = 'Please select a valid service.';
        $messageType = 'error';
        $transactionType = 'Tuition Fee Payment';

    } else {
        $lockName = null;
        $lockAcquired = false;

        try {
            $stmt = $pdo->prepare(
                "SELECT student_id, student_number, first_name, last_name
                 FROM students
                 WHERE student_number = ?
                 LIMIT 1"
            );
            $stmt->execute([$studentNumber]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                $message = 'Student number not found. Please check your entry.';
                $messageType = 'error';

            } else {
                $stmt = $pdo->prepare(
                    "SELECT queue_number
                     FROM queue_transactions
                     WHERE student_id = ?
                       AND queue_date = CURDATE()
                       AND status IN ('Waiting', 'Serving')
                     LIMIT 1"
                );
                $stmt->execute([$student['student_id']]);
                $existingQueue = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingQueue) {
                    $message = 'You already have an active queue today: '
                        . $existingQueue['queue_number'];
                    $messageType = 'error';

                } else {
                    $prefix = $prefixes[$transactionType];
                    $lockName = 'mats_queue_' . $prefix;

                    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
                    $stmt->execute([$lockName]);
                    $lockAcquired = ((int) $stmt->fetchColumn() === 1);

                    if (!$lockAcquired) {
                        throw new RuntimeException(
                            'Unable to lock the queue number generator.'
                        );
                    }

                    try {
                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare(
                            "SELECT MAX(
                                CAST(SUBSTRING(queue_number, 3) AS UNSIGNED)
                             )
                             FROM queue_transactions
                             WHERE queue_date = CURDATE()
                               AND queue_number REGEXP ?"
                        );
                        $stmt->execute(['^' . $prefix . '[0-9]+$']);

                        $lastNumber = (int) $stmt->fetchColumn();
                        $nextNumber = $lastNumber + 1;

                        $queueNumber = $prefix . str_pad(
                            (string) $nextNumber,
                            3,
                            '0',
                            STR_PAD_LEFT
                        );

                        $stmt = $pdo->prepare(
                            "INSERT INTO queue_transactions
                                (student_id, queue_number, transaction_type,
                                 status, queue_date)
                             VALUES (?, ?, ?, 'Waiting', CURDATE())"
                        );

                        $stmt->execute([
                            $student['student_id'],
                            $queueNumber,
                            $transactionType
                        ]);

                        $pdo->commit();

                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $e;

                    } finally {
                        if ($lockAcquired) {
                            $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                            $stmt->execute([$lockName]);
                            $lockAcquired = false;
                        }
                    }

                    $queueDetails = [
                        'queue_number' => $queueNumber,
                        'student_name' => $student['first_name'] . ' '
                            . $student['last_name'],
                        'transaction_type' => $transactionType,
                        'status' => 'Waiting',
                        'date' => date('F d, Y'),
                        'time' => date('h:i A')
                    ];

                    $message = 'Queue request created successfully!';
                    $messageType = 'success';

                    $studentNumber = '';
                    $student = null;
                }
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($lockAcquired && $lockName !== null) {
                try {
                    $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                    $stmt->execute([$lockName]);
                } catch (Throwable $releaseError) {
                    error_log($releaseError->getMessage());
                }
            }

            error_log('Student queue error: ' . $e->getMessage());

            $message = 'Unable to create your queue request. Please check the PHP error log.';
            $messageType = 'error';
        }
    }
}

function safe($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MATS College of Technology | Student Queue</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px 15px;
            font-family: Arial, sans-serif;
            background: #f1f6fc;
            color: #17365d;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d6e3f2;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(23, 54, 93, 0.10);
        }

        .header {
            background: #174a8b;
            color: #ffffff;
            text-align: center;
            padding: 25px 18px;
            border-bottom: 5px solid #ffffff;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .header p {
            margin: 0;
            color: #eaf2ff;
        }

        .content {
            padding: 25px;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
            color: #174a8b;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #b7cce5;
            border-radius: 7px;
            font-size: 16px;
            background: #ffffff;
            color: #17365d;
        }

        input:focus,
        select:focus {
            outline: 2px solid #7daee8;
            border-color: #174a8b;
        }

        button,
        .back-link {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: 12px 18px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        button {
            width: 100%;
            margin-top: 22px;
            background: #174a8b;
            color: #ffffff;
        }

        button:hover {
            background: #103765;
        }

        .back-link {
            margin-top: 14px;
            background: #eaf2fb;
            color: #174a8b;
            border: 1px solid #d6e3f2;
        }

        .message {
            padding: 12px;
            margin-bottom: 16px;
            border-radius: 7px;
        }

        .success {
            background: #eaf7ee;
            color: #17652c;
        }

        .error {
            background: #fff0f0;
            color: #a12222;
        }

        /* Thermal-style ticket preview */
        .receipt {
            width: 100%;
            max-width: 320px;
            margin: 20px auto;
            padding: 18px 15px;
            background: #ffffff;
            color: #000000;
            border: 1px dashed #777777;
            font-family: "Courier New", monospace;
            font-size: 12px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .receipt-header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px dashed #000000;
        }

        .receipt-header h2 {
            font-size: 15px;
            margin: 0 0 5px;
        }

        .receipt-header p {
            margin: 3px 0;
        }

        .receipt-label {
            text-align: center;
            font-weight: bold;
            margin: 12px 0 5px;
        }

        .queue-number {
            text-align: center;
            font-size: 34px;
            font-weight: bold;
            line-height: 1.3;
            margin: 8px 0 12px;
            overflow-wrap: anywhere;
        }

        .receipt-divider {
            border-top: 1px dashed #000000;
            margin: 10px 0;
        }

        .receipt-details {
            text-align: left;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .receipt-details p {
            margin: 5px 0;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #000000;
        }

        .receipt-footer p {
            margin: 4px 0;
        }

        .print-button {
            background: #174a8b;
            color: #ffffff;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 3mm;
            }

            body {
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
                overflow: visible;
            }

            .header,
            form,
            .back-link,
            .message,
            .print-button,
            .content > h2,
            .content > p {
                display: none !important;
            }

            .content {
                padding: 0;
            }

            .receipt {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 2mm;
                border: none;
                font-family: "Courier New", monospace;
                font-size: 10pt;
                line-height: 1.4;
            }

            .receipt-header h2 {
                font-size: 12pt;
            }

            .queue-number {
                font-size: 28pt;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <h1>MATS College of Technology</h1>
        <p>Smart Queue Management System</p>
    </div>

    <div class="content">

        <?php if ($message !== ''): ?>
            <div class="message <?= safe($messageType) ?>">
                <?= safe($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($queueDetails === null): ?>

            <h2>Get Your Queue Number</h2>
            <p>Enter your student number and select your transaction type.</p>

            <form method="POST" action="student_queue.php">
                <label for="student_number">Student Number</label>

                <input
                    type="text"
                    id="student_number"
                    name="student_number"
                    value="<?= safe($studentNumber) ?>"
                    maxlength="30"
                    required
                    autocomplete="off"
                >

                <label for="transaction_type">Transaction Type</label>

                <select
                    id="transaction_type"
                    name="transaction_type"
                    required
                >
                    <?php foreach ($allowedTypes as $type): ?>
                        <option
                            value="<?= safe($type) ?>"
                            <?= $transactionType === $type ? 'selected' : '' ?>
                        >
                            <?= safe($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Get Queue Number</button>
            </form>

            <a class="back-link" href="index.php">Back to Home</a>

        <?php else: ?>

            <div class="receipt" id="receipt">

                <div class="receipt-header">
                    <h2>MATS COLLEGE OF TECHNOLOGY</h2>
                    <p>SMART QUEUE MANAGEMENT SYSTEM</p>
                    <p>QUEUE TICKET</p>
                </div>

                <div class="receipt-label">YOUR QUEUE NUMBER</div>

                <div class="queue-number">
                    <?= safe($queueDetails['queue_number']) ?>
                </div>

                <div class="receipt-divider"></div>

                <div class="receipt-details">
                    <p>
                        <strong>Student:</strong><br>
                        <?= safe($queueDetails['student_name']) ?>
                    </p>

                    <p>
                        <strong>Service:</strong><br>
                        <?= safe($queueDetails['transaction_type']) ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?= safe($queueDetails['status']) ?>
                    </p>

                    <p>
                        <strong>Date:</strong>
                        <?= safe($queueDetails['date']) ?>
                    </p>

                    <p>
                        <strong>Time:</strong>
                        <?= safe($queueDetails['time']) ?>
                    </p>
                </div>

                <div class="receipt-footer">
                    <p>PLEASE WAIT FOR YOUR NUMBER</p>
                    <p>THANK YOU!</p>
                    <p>----------------------------</p>
                    <p>MATS COLLEGE OF TECHNOLOGY</p>
                </div>

            </div>

            <button
                type="button"
                class="print-button"
                onclick="window.print()"
            >
                Print Receipt
            </button>

            <a class="back-link" href="index.php">Back to Home</a>

        <?php endif; ?>

    </div>
</div>

<?php if ($queueDetails !== null): ?>
<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
<?php endif; ?>

</body>
</html>