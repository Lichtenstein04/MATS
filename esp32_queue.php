
<?php
require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('ERROR:POST_REQUIRED');
}

$transactionType = trim($_POST['transaction_type'] ?? '');

$prefixes = [
    'Tuition Fee Payment' => 'TF',
    'Enrollment Fee' => 'EF',
    'Tuition Balance' => 'TB',
    'White Form' => 'WF',
    'Intramurals Payment' => 'IP',
    'Credentials Payment' => 'CP'
];

if (!isset($prefixes[$transactionType])) {
    http_response_code(400);
    exit('ERROR:INVALID_SERVICE');
}

$prefix = $prefixes[$transactionType];
$lockName = 'mats_queue_' . $prefix;
$lockAcquired = false;

try {
    // Use the same lock as student_queue.php.
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
    $stmt->execute([$lockName]);

    if ((int) $stmt->fetchColumn() !== 1) {
        throw new RuntimeException('Could not lock queue generator.');
    }

    $lockAcquired = true;

    $pdo->beginTransaction();

    // Find the highest number issued for this service today.
    $stmt = $pdo->prepare(
        "SELECT MAX(
            CAST(SUBSTRING(queue_number, 3) AS UNSIGNED)
         )
         FROM queue_transactions
         WHERE queue_date = CURDATE()
           AND queue_number REGEXP ?"
    );

    $stmt->execute([
        '^' . $prefix . '[0-9]+$'
    ]);

    $lastNumber = (int) $stmt->fetchColumn();
    $nextNumber = $lastNumber + 1;

    $queueNumber = $prefix . str_pad(
        (string) $nextNumber,
        3,
        '0',
        STR_PAD_LEFT
    );

    // Create the anonymous queue ticket.
    $stmt = $pdo->prepare(
        "INSERT INTO queue_transactions
            (student_id, queue_number, transaction_type, status, queue_date)
         VALUES (NULL, ?, ?, 'Waiting', CURDATE())"
    );

    $stmt->execute([
        $queueNumber,
        $transactionType
    ]);

    $queueId = (int) $pdo->lastInsertId();

    // Record a notification for the issued ticket.
    $stmt = $pdo->prepare(
        "INSERT INTO notifications
            (student_id, queue_id, title, message, notification_type)
         VALUES (NULL, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $queueId,
        'Queue Number Issued',
        'Queue number ' . $queueNumber .
            ' has been issued for ' . $transactionType . '.',
        'Queue'
    ]);

    $pdo->commit();

    // Send only the queue number to the ESP32.
    echo $queueNumber;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('ESP32 queue error: ' . $e->getMessage());

    http_response_code(500);
    echo 'ERROR:DATABASE';

} finally {
    if ($lockAcquired) {
        try {
            $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $stmt->execute([$lockName]);
        } catch (Throwable $e) {
            error_log('Queue lock release error: ' . $e->getMessage());
        }
    }
}
?>
