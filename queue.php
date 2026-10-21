
<?php
require_once 'db.php';

$message = '';
$queueNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNumber = trim($_POST['student_number'] ?? '');
    $transactionType = trim($_POST['transaction_type'] ?? '');

    $allowedTransactions = [
        'Examination Payment',
        'Other Student Service'
    ];

    if ($studentNumber === '' ||
        !in_array($transactionType, $allowedTransactions, true)) {
        $message = 'Please enter your student number and select a transaction.';
    } else {
        try {
            $pdo->beginTransaction();

            // Find the registered student.
            $stmt = $pdo->prepare(
                'SELECT student_id, first_name, last_name
                 FROM students
                 WHERE student_number = ?'
            );
            $stmt->execute([$studentNumber]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                throw new Exception(
                    'Student number not found. Please register first.'
                );
            }

            // Read the queue prefix from system settings.
            $stmt = $pdo->prepare(
                "SELECT setting_value
                 FROM system_settings
                 WHERE setting_key = 'queue_prefix'"
            );
            $stmt->execute();
            $prefix = $stmt->fetchColumn();

            if (!$prefix) {
                $prefix = 'A';
            }

            // Assign the next number for today's queue.
            $stmt = $pdo->prepare(
                'INSERT INTO queue_counters (queue_date, last_number)
                 VALUES (CURDATE(), 1)
                 ON DUPLICATE KEY UPDATE last_number = last_number + 1'
            );
            $stmt->execute();

            $stmt = $pdo->query(
                'SELECT last_number
                 FROM queue_counters
                 WHERE queue_date = CURDATE()
                 FOR UPDATE'
            );
            $number = (int) $stmt->fetchColumn();

            $queueNumber = $prefix . str_pad(
                (string) $number,
                3,
                '0',
                STR_PAD_LEFT
            );

            // Save the queue request.
            $stmt = $pdo->prepare(
                'INSERT INTO queue_transactions
                 (student_id, queue_number, transaction_type, status, queue_date)
                 VALUES (?, ?, ?, "Waiting", CURDATE())'
            );
            $stmt->execute([
                $student['student_id'],
                $queueNumber,
                $transactionType
            ]);

            $queueId = (int) $pdo->lastInsertId();

            
            // Create a payment record for examination payment requests.
            if ($transactionType === 'Examination Payment') {
                $stmt = $pdo->prepare(
                    "SELECT setting_value
                     FROM system_settings
                     WHERE setting_key = 'examination_fee'"
                );
                $stmt->execute();

                $fee = $stmt->fetchColumn();

                if ($fee === false || !is_numeric($fee)) {
                    $fee = 0.00;
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO payments
                     (student_id, queue_id, amount, payment_status)
                     VALUES (?, ?, ?, 'Pending')"
                );

                $stmt->execute([
                    $student['student_id'],
                    $queueId,
                    $fee
                ]);
            }


            
            // Create a payment record for examination payment requests.
            if ($transactionType === 'Examination Payment') {
                $stmt = $pdo->prepare(
                    "SELECT setting_value
                     FROM system_settings
                     WHERE setting_key = 'examination_fee'"
                );
                $stmt->execute();

                $fee = $stmt->fetchColumn();

                if ($fee === false || !is_numeric($fee)) {
                    $fee = 0.00;
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO payments
                     (student_id, queue_id, amount, payment_status)
                     VALUES (?, ?, ?, 'Pending')"
                );

                $stmt->execute([
                    $student['student_id'],
                    $queueId,
                    $fee
                ]);
            }


            // Record a notification for the student.
            $stmt = $pdo->prepare(
                'INSERT INTO notifications
                 (student_id, queue_id, title, message, notification_type)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $student['student_id'],
                $queueId,
                'Queue Number Issued',
                'Your queue number is ' . $queueNumber .
                    '. Please wait until your number is called.',
                'Queue'
            ]);

            $pdo->commit();

            $message = 'Queue request successful!';

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $queueNumber = '';

            if ($e instanceof PDOException) {
                error_log($e->getMessage());
                $message = 'A database error occurred. Please try again.';
            } else {
                $message = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Queue Number | MATS College of Technology</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5fb;
            color: #243247;
        }

        header {
            background: #123c78;
            color: white;
            padding: 22px;
            text-align: center;
        }

        main {
            max-width: 560px;
            margin: 35px auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #123c78;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
        }

        input, select {
            border: 1px solid #ccd5e2;
            background: white;
        }

        button {
            margin-top: 22px;
            border: none;
            background: #1764c0;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #104b94;
        }

        .message {
            margin-top: 18px;
            padding: 12px;
            border-radius: 6px;
            background: #eef4ff;
            overflow-wrap: anywhere;
        }

        .ticket {
            margin-top: 18px;
            padding: 22px;
            text-align: center;
            background: #e8f2ff;
            border: 2px dashed #1764c0;
            border-radius: 8px;
        }

        .ticket-number {
            font-size: 42px;
            font-weight: bold;
            color: #123c78;
            margin: 12px 0;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #1764c0;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>MATS College of Technology</h1>
        <p>Smart Queue Management System</p>
    </header>

    <main>
        <h2>Get Your Queue Number</h2>
        <p>Enter your registered student number to request a queue ticket.</p>

        <form method="POST" action="queue.php">
            <label for="student_number">Student Number</label>
            <input
                type="text"
                id="student_number"
                name="student_number"
                required
                maxlength="30"
                placeholder="Enter your student number"
                value="<?= htmlspecialchars($_POST['student_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="transaction_type">Transaction Type</label>
            <select id="transaction_type" name="transaction_type" required>
                <option value="">-- Select a transaction --</option>
                <option value="Examination Payment"
                    <?= (($_POST['transaction_type'] ?? '') === 'Examination Payment') ? 'selected' : '' ?>>
                    Examination Payment
                </option>
                <option value="Other Student Service"
                    <?= (($_POST['transaction_type'] ?? '') === 'Other Student Service') ? 'selected' : '' ?>>
                    Other Student Service
                </option>
            </select>

            <button type="submit">Get Queue Number</button>
        </form>

        <?php if ($message !== ''): ?>
            <div class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($queueNumber !== ''): ?>
            <div class="ticket">
                <p>Your queue number is:</p>
                <div class="ticket-number">
                    <?= htmlspecialchars($queueNumber, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <p>Please wait until your number is called.</p>
            </div>
        <?php endif; ?>

        <a class="back" href="index.php">← Back to Home</a>
    </main>
</body>
</html>
