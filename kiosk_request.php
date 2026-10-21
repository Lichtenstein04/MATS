
<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

define('KIOSK_SECRET', 'MATS_KIOSK_CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_8472');

date_default_timezone_set('Asia/Manila');

$providedSecret = $_SERVER['HTTP_X_KIOSK_SECRET'] ?? '';

if (!hash_equals(KIOSK_SECRET, $providedSecret)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized kiosk request.'
    ]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'POST requests only.'
    ]);
    exit;
}

// Read the service selected by the physical kiosk button.
$input = json_decode(file_get_contents('php://input'), true);

$service = $input['service'] ?? '';

$allowedTypes = [
    'Examination Payment',
    'Other Student Service'
];

if (!is_string($service) ||
    !in_array($service, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid service selected.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock queue-number generation to prevent simultaneous
    // requests from receiving the same number.
    $lock = $pdo->query(
        "SELECT GET_LOCK('mats_queue_number_lock', 5)"
    )->fetchColumn();

    if ((int)$lock !== 1) {
        throw new RuntimeException(
            'Could not obtain queue-number lock.'
        );
    }

    // Find the latest A-series queue number for today.
    $stmt = $pdo->query(
        "SELECT MAX(
            CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)
        )
        FROM queue_transactions
        WHERE queue_date = CURDATE()
          AND queue_number REGEXP '^A[0-9]+$'"
    );

    $lastNumber = (int)$stmt->fetchColumn();
    $nextNumber = $lastNumber + 1;

    $queueNumber = 'A' . str_pad(
        (string)$nextNumber,
        3,
        '0',
        STR_PAD_LEFT
    );

    // Create an anonymous queue transaction.
    $stmt = $pdo->prepare(
        "INSERT INTO queue_transactions
            (student_id, queue_number, transaction_type,
             status, queue_date)
         VALUES
            (NULL, ?, ?, 'Waiting', CURDATE())"
    );

    $stmt->execute([
        $queueNumber,
        $service
    ]);

    $queueId = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Queue created successfully.',
        'queue_id' => $queueId,
        'queue_number' => $queueNumber,
        'service' => $service,
        'status' => 'Waiting',
        'date' => date('Y-m-d'),
        'time' => date('h:i A')
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to create queue. Please try again.'
    ]);

} finally {
    // Release the named lock if this request acquired it.
    try {
        $pdo->query(
            "SELECT RELEASE_LOCK('mats_queue_number_lock')"
        );
    } catch (Throwable $ignored) {
        // Ignore lock-release errors.
    }
}
