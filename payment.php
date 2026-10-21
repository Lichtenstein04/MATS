
<?php
require_once 'db.php';

$message = '';
$student = null;
$payment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNumber = trim($_POST['student_number'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($studentNumber === '') {
        $message = 'Please enter your student number.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT student_id, student_number, first_name, last_name
                 FROM students
                 WHERE student_number = ?'
            );
            $stmt->execute([$studentNumber]);
            $student = $stmt->fetch();

            if (!$student) {
                $message = 'Student number not found. Please register first.';
            } elseif ($action === 'record_payment') {
                $queueId = filter_input(INPUT_POST, 'queue_id', FILTER_VALIDATE_INT);
                $paymentMethod = $_POST['payment_method'] ?? '';

                $allowedMethods = ['Cash', 'Online Transfer'];

                if (!$queueId || !in_array($paymentMethod, $allowedMethods, true)) {
                    $message = 'Please select a valid payment method.';
                } else {
                    $pdo->beginTransaction();

                    // Verify that this queue belongs to the student.
                    $stmt = $pdo->prepare(
                        "SELECT queue_id
                         FROM queue_transactions
                         WHERE queue_id = ?
                           AND student_id = ?
                           AND transaction_type = 'Examination Payment'
                         FOR UPDATE"
                    );
                    $stmt->execute([$queueId, $student['student_id']]);
                    $queue = $stmt->fetch();

                    if (!$queue) {
                        throw new Exception('The examination payment queue was not found.');
                    }

                    // Find the payment record.
                    $stmt = $pdo->prepare(
                        'SELECT payment_id, payment_status
                         FROM payments
                         WHERE queue_id = ?
                         FOR UPDATE'
                    );
                    $stmt->execute([$queueId]);
                    $existingPayment = $stmt->fetch();

                    if (!$existingPayment) {
                        throw new Exception('Payment record not found.');
                    }

                    if ($existingPayment['payment_status'] === 'Paid') {
                        throw new Exception('This payment has already been recorded as paid.');
                    }

                    // This is a demonstration only. Real payment must be verified
                    // by authorized cashier personnel before marking it as paid.
                    $stmt = $pdo->prepare(
                        "UPDATE payments
                         SET payment_status = 'Paid',
                             payment_method = ?,
                             paid_at = NOW()
                         WHERE payment_id = ?"
                    );
                    $stmt->execute([
                        $paymentMethod,
                        $existingPayment['payment_id']
                    ]);

                    $stmt = $pdo->prepare(
                        'INSERT INTO notifications
                         (student_id, queue_id, title, message, notification_type)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $student['student_id'],
                        $queueId,
                        'Payment Status Updated',
                        'Your demonstration payment status has been marked as Paid.',
                        'Payment'
                    ]);

                    $pdo->commit();
                    $message = 'Demonstration payment status updated successfully.';
                }
            }

            // Load the most recent examination payment queue.
            if ($student) {
                $stmt = $pdo->prepare(
                    "SELECT q.queue_id, q.queue_number, q.status AS queue_status,
                            p.amount, p.payment_status, p.payment_method,
                            p.reference_number, p.paid_at
                     FROM queue_transactions q
                     JOIN payments p ON p.queue_id = q.queue_id
                     WHERE q.student_id = ?
                       AND q.transaction_type = 'Examination Payment'
                     ORDER BY q.queue_id DESC
                     LIMIT 1"
                );
                $stmt->execute([$student['student_id']]);
                $payment = $stmt->fetch();
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

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
    <title>Examination Payment | MATS College of Technology</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5fb;
            color: #243247;
        }

        header {
            background: #123c78;
            color: white;
            text-align: center;
            padding: 22px;
        }

        main {
            max-width: 650px;
            margin: 35px auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,.08);
        }

        h2 { color: #123c78; }

        label {
            display: block;
            margin: 16px 0 8px;
            font-weight: bold;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
        }

        input, select {
            border: 1px solid #ccd5e2;
            background: white;
        }

        button {
            margin-top: 18px;
            border: none;
            background: #1764c0;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .message, .details {
            margin-top: 18px;
            padding: 16px;
            background: #eef4ff;
            border-radius: 8px;
            overflow-wrap: anywhere;
        }

        .warning {
            margin-top: 16px;
            color: #8a4b00;
            background: #fff4dc;
            padding: 12px;
            border-radius: 6px;
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
    <p>Examination Payment Monitoring</p>
</header>

<main>
    <h2>Check Examination Payment</h2>
    <p>Enter your registered student number to view your payment record.</p>

    <form method="POST" action="payment.php">
        <label for="student_number">Student Number</label>
        <input
            type="text"
            id="student_number"
            name="student_number"
            maxlength="30"
            required
            value="<?= htmlspecialchars($_POST['student_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        >
        <button type="submit" name="action" value="check">Check Payment</button>
    </form>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($student): ?>
        <div class="details">
            <h3>Student Information</h3>
            <p><strong>Name:</strong>
                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name'], ENT_QUOTES, 'UTF-8') ?>
            </p>
            <p><strong>Student Number:</strong>
                <?= htmlspecialchars($student['student_number'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if ($payment): ?>
                <h3>Latest Examination Payment</h3>
                <p><strong>Queue Number:</strong>
                    <?= htmlspecialchars($payment['queue_number'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p><strong>Amount:</strong>
                    ₱<?= number_format((float)$payment['amount'], 2) ?>
                </p>
                <p><strong>Payment Status:</strong>
                    <?= htmlspecialchars($payment['payment_status'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p><strong>Queue Status:</strong>
                    <?= htmlspecialchars($payment['queue_status'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if ($payment['payment_status'] !== 'Paid'): ?>
                    <div class="warning">
                        Demonstration only: do not record a real payment through this form.
                        Actual payments must be verified by authorized cashier personnel.
                    </div>

                    <form method="POST" action="payment.php">
                        <input type="hidden" name="student_number"
                            value="<?= htmlspecialchars($student['student_number'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="queue_id"
                            value="<?= (int)$payment['queue_id'] ?>">

                        <label for="payment_method">Demonstration Payment Method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">-- Select method --</option>
                            <option value="Cash">Cash (Demo)</option>
                            <option value="Online Transfer">Online Transfer (Demo)</option>
                        </select>

                        <button type="submit" name="action" value="record_payment">
                            Record Demo Payment
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p>No examination payment record was found for this student.</p>
                <p>Please request an Examination Payment queue number first.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a class="back" href="index.php">← Back to Home</a>
</main>
</body>
</html>
