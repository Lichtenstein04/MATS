
<?php
require_once 'db.php';

// Display today's queue records.
$stmt = $pdo->query(
    "SELECT
        q.queue_number,
        q.transaction_type,
        q.status,
        q.created_at,
        s.student_number,
        s.first_name,
        s.last_name
     FROM queue_transactions q
     INNER JOIN students s ON q.student_id = s.student_id
     WHERE q.queue_date = CURDATE()
     ORDER BY q.queue_id ASC"
);

$queues = $stmt->fetchAll();


 // Get the queue currently being served.
$servingStmt = $pdo->query(
    "SELECT queue_number, transaction_type
     FROM queue_transactions
     WHERE queue_date = CURDATE()
       AND status = 'Serving'
     ORDER BY queue_id ASC
     LIMIT 1"
);

$nowServing = $servingStmt->fetch(PDO::FETCH_ASSOC);


 // Get the next waiting queue.
$nextStmt = $pdo->query(
    "SELECT queue_number, transaction_type
     FROM queue_transactions
     WHERE queue_date = CURDATE()
       AND status = 'Waiting'
     ORDER BY queue_id ASC
     LIMIT 1"
);

$nextQueue = $nextStmt->fetch(PDO::FETCH_ASSOC);



function statusClass($status) {
    switch ($status) {
        case 'Waiting':
            return 'waiting';
        case 'Serving':
            return 'serving';
        case 'Completed':
            return 'completed';
        case 'Cancelled':
            return 'cancelled';
        default:
            return 'waiting';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Queue Status | MATS College of Technology</title>


<p>
    Staff member?
    <a href="staff_login.php">Staff Login</a>
</p>


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
            max-width: 1100px;
            margin: 30px auto;
            padding: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #123c78;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 20px 0;
        }

        .summary-card {
            flex: 1;
            min-width: 130px;
            padding: 16px;
            background: #eef4ff;
            border-radius: 8px;
        }

        .summary-card strong {
            display: block;
            font-size: 26px;
            color: #123c78;
            margin-top: 8px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #e0e6ef;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #123c78;
            color: white;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: bold;
        }

        .waiting {
            background: #fff0c2;
            color: #805700;
        }

        .serving {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .completed {
            background: #dcfce7;
            color: #166534;
        }

        .cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .back {
            display: inline-block;
            margin-top: 24px;
            color: #1764c0;
            text-decoration: none;
        }

        .empty {
            padding: 24px;
            text-align: center;
            color: #667085;
        }

        
.now-serving {
    background: #173b70;
    color: white;
    text-align: center;
    padding: 25px 15px;
    margin: 25px 0;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.now-serving p {
    margin: 8px 0;
}

.now-serving h2 {
    font-size: 48px;
    margin: 12px 0;
    color: white;
}


.next-in-line {
    background: #eaf2ff;
    color: #173b70;
    text-align: center;
    padding: 20px 15px;
    margin: 20px 0;
    border-radius: 12px;
    border: 1px solid #c8d9f5;
}

.next-in-line p {
    margin: 8px 0;
}

.next-in-line h2 {
    font-size: 36px;
    margin: 10px 0;
    color: #173b70;
}


    </style>
</head>
<body>

<header>
    <h1>MATS College of Technology</h1>
    <p>Smart Queue Management System</p>
</header>

<main>
    <h2>Today's Queue Status</h2>
    <p>This page refreshes automatically every 30 seconds.</p>
    
<div class="now-serving">
    <p>NOW SERVING</p>

    <?php if (!empty($nowServing)): ?>
        <h2>
            <?= htmlspecialchars(
                $nowServing['queue_number'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h2>

        <p>
            <?= htmlspecialchars(
                $nowServing['transaction_type'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    <?php else: ?>
        <h2>---</h2>
        <p>No student is currently being served.</p>
    <?php endif; ?>
</div>


<div class="next-in-line">
    <p>NEXT IN LINE</p>

    <?php if (!empty($nextQueue)): ?>
        <h2>
            <?= htmlspecialchars(
                $nextQueue['queue_number'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h2>

        <p>
            <?= htmlspecialchars(
                $nextQueue['transaction_type'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    <?php else: ?>
        <h2>---</h2>
        <p>No waiting students.</p>
    <?php endif; ?>
</div>



    <div class="summary">
        <div class="summary-card">
            Total Queues
            <strong><?= count($queues) ?></strong>
        </div>

        <div class="summary-card">
            Waiting
            <strong><?= count(array_filter($queues, fn($q) => $q['status'] === 'Waiting')) ?></strong>
        </div>

        <div class="summary-card">
            Serving
            <strong><?= count(array_filter($queues, fn($q) => $q['status'] === 'Serving')) ?></strong>
        </div>

        <div class="summary-card">
            Completed
            <strong><?= count(array_filter($queues, fn($q) => $q['status'] === 'Completed')) ?></strong>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Queue Number</th>
                    <th>Student Number</th>
                    <th>Student Name</th>
                    <th>Transaction</th>
                    <th>Status</th>
                    <th>Request Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($queues)): ?>
                    <tr>
                        <td colspan="6" class="empty">
                            No queue requests have been recorded today.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($queues as $queue): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($queue['queue_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($queue['student_number'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($queue['first_name'] . ' ' . $queue['last_name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($queue['transaction_type'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <span class="status <?= statusClass($queue['status']) ?>">
                                    <?= htmlspecialchars($queue['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars(date('h:i A', strtotime($queue['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a class="back" href="index.php">← Back to Home</a>
</main>

</body>
</html>
