
<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

require_once 'db.php';
$message = '';
$error = '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $queueId = filter_input(INPUT_POST, 'queue_id', FILTER_VALIDATE_INT);

    $submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    http_response_code(403);
    exit('Invalid security token. Reload the page and try again.');
}
    try {
        $pdo->beginTransaction();

        if ($action === 'call_next') {
            // Lock the current day's active queues.
            $stmt = $pdo->query(
                "SELECT queue_id, status
                 FROM queue_transactions
                 WHERE queue_date = CURDATE()
                 AND status IN ('Waiting', 'Serving')
                 ORDER BY queue_id ASC
                 FOR UPDATE"
            );

            $activeQueues = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $currentlyServing = false;

            foreach ($activeQueues as $q) {
                if ($q['status'] === 'Serving') {
                    $currentlyServing = true;
                    break;
                }
            }

            if ($currentlyServing) {
                throw new Exception(
                    'A student is already being served. Complete that queue first.'
                );
            }

            $stmt = $pdo->prepare(
                "UPDATE queue_transactions
                 SET status = 'Serving'
                 WHERE queue_date = CURDATE()
                 AND status = 'Waiting'
                 ORDER BY queue_id ASC
                 LIMIT 1"
            );

            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new Exception('There are no waiting students.');
            }

            $message = 'The next student has been called.';
        } elseif (
            in_array($action, ['complete', 'cancel'], true)
            && $queueId
        ) {
            $newStatus = $action === 'complete'
                ? 'Completed'
                : 'Cancelled';

            $allowedStatus = $action === 'complete'
                ? 'Serving'
                : 'Waiting';

            $stmt = $pdo->prepare(
                "UPDATE queue_transactions
                 SET status = ?
                 WHERE queue_id = ?
                 AND queue_date = CURDATE()
                 AND status = ?"
            );

            $stmt->execute([
                $newStatus,
                $queueId,
                $allowedStatus
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception(
                    'The queue was not updated. Check its current status.'
                );
            }

            $message = 'Queue status updated to ' . $newStatus . '.';
        } else {
            throw new Exception('Invalid action.');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e instanceof PDOException
            ? 'A database error occurred. Check your database configuration.'
            : $e->getMessage();
    }
}

$stmt = $pdo->query(
    "SELECT
        q.queue_id,
        q.queue_number,
        q.transaction_type,
        q.status,
        q.created_at,
        s.student_number,
        s.first_name,
        s.last_name
     FROM queue_transactions q
     INNER JOIN students s
        ON q.student_id = s.student_id
     WHERE q.queue_date = CURDATE()
     ORDER BY
        CASE q.status
            WHEN 'Serving' THEN 1
            WHEN 'Waiting' THEN 2
            WHEN 'Completed' THEN 3
            WHEN 'Cancelled' THEN 4
            ELSE 5
        END,
        q.queue_id ASC"
);

$queues = $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalQueues = count($queues);

$waitingCount = count(array_filter(
    $queues,
    fn($q) => $q['status'] === 'Waiting'
));

$servingCount = count(array_filter(
    $queues,
    fn($q) => $q['status'] === 'Serving'
));

$completedCount = count(array_filter(
    $queues,
    fn($q) => $q['status'] === 'Completed'
));


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Staff Queue Dashboard | MATS</title>


<p>
    Welcome,
    <strong>
        <?= htmlspecialchars(
            $_SESSION['staff_username'] ?? 'Staff',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </strong>
</p>

<a href="staff_logout.php"
   style="display:inline-block;
          background:#dc3545;
          color:white;
          padding:10px 16px;
          text-decoration:none;
          border-radius:6px;">
    Logout
</a>


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
            padding: 22px;
            text-align: center;
        }

        main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px #00000010;
        }

        h2 { color: #123c78; }

        
.summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin: 25px 0;
}

.summary-card {
    background: #f1f5fb;
    border: 1px solid #e0e6ef;
    border-radius: 10px;
    padding: 20px 12px;
    text-align: center;
    color: #526176;
}

.summary-card strong {
    display: block;
    font-size: 30px;
    color: #123c78;
    margin-top: 10px;
}

@media (max-width: 650px) {
    .summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}


        .message {
            padding: 12px;
            margin: 15px 0;
            background: #dcfce7;
            color: #166534;
            border-radius: 6px;
        }

        .error {
            padding: 12px;
            margin: 15px 0;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
        }

        button {
            border: 0;
            border-radius: 6px;
            padding: 10px 13px;
            color: white;
            cursor: pointer;
            margin: 2px;
        }

        .call { background: #1764c0; }
        .complete { background: #15803d; }
        .cancel { background: #b91c1c; }

        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e0e6ef;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #123c78;
            color: white;
        }

        .back {
            display: inline-block;
            margin-top: 24px;
            color: #1764c0;
            text-decoration: none;
        }

        .status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-waiting {
    background: #fef3c7;
    color: #92400e;
}

.status-serving {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

tr.serving-row {
    background: #eff6ff;
}

tr.serving-row td {
    border-top: 2px solid #93c5fd;
    border-bottom: 2px solid #93c5fd;
}
    </style>
</head>
<body>

<header>
    <h1>MATS College of Technology</h1>
    <p>Staff Queue Management Dashboard</p>
</header>

<main>
    <h2>Manage Today's Queue</h2>

<p>Queue information refreshes every 30 seconds.</p>

<p style="font-size: 13px; color: #64748b;">
    Last page update:
    <strong id="lastUpdated"></strong>
</p>

<button
    type="button"
    onclick="window.location.reload();"
    style="background: #64748b; margin-bottom: 15px;"
>
    Refresh Queue
</button>
    
<div class="summary">
    <div class="summary-card">
        Total Queues
        <strong><?= $totalQueues ?></strong>
    </div>

    <div class="summary-card">
        Waiting
        <strong><?= $waitingCount ?></strong>
    </div>

    <div class="summary-card">
        Serving
        <strong><?= $servingCount ?></strong>
    </div>

    <div class="summary-card">
        Completed
        <strong><?= $completedCount ?></strong>
    </div>
</div>


    <?php if ($message !== ''): ?>
        <div class="message"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form
    method="POST"
    id="callNextForm"
    onsubmit="return confirmCallNext();"
>
    <input type="hidden" name="action" value="call_next">

    <button class="call" type="submit" id="callNextButton">
        Call Next Student
    </button>
</form>

    <div style="margin: 20px 0;">
    <label for="queueSearch"><strong>Search Queue:</strong></label>

    <input
        type="text"
        id="queueSearch"
        placeholder="Enter queue number, student number, or name..."
        style="width: 100%; max-width: 450px; padding: 12px;
               margin-top: 8px; border: 1px solid #cbd5e1;
               border-radius: 6px;"
    >
</div>

<div style="margin: 15px 0;">
    <label for="statusFilter"><strong>Filter by Status:</strong></label>

    <select
        id="statusFilter"
        style="padding: 10px; margin-left: 8px;
               border: 1px solid #cbd5e1; border-radius: 6px;"
    >
        <option value="">All Statuses</option>
        <option value="Waiting">Waiting</option>
        <option value="Serving">Serving</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
    </select>

        <button
    type="button"
    id="clearFilters"
    style="background: #64748b; margin-left: 8px;"
>
    Clear Filters
</button>
</div>

<p id="queueCount" style="color: #526176; margin: 15px 0;">
    Showing all queues
</p>
<div class="table-wrapper">
    <table id="queueTable">
            <thead>
                <tr class="<?= $q['status'] === 'Serving' ? 'serving-row' : '' ?>">
                    <th>Queue Number</th>
                    <th>Student Number</th>
                    <th>Student Name</th>
                    <th>Transaction</th>
                    <th>Status</th>
                    <th>Request Time</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($queues)): ?>
                <tr>
                    <td colspan="7">
                        No queue requests recorded today.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($queues as $q): ?>
                    <tr>
                        <td><strong><?= h($q['queue_number']) ?></strong></td>
                        <td><?= h($q['student_number']) ?></td>
                        <td><?= h($q['first_name'] . ' ' . $q['last_name']) ?></td>
                        <td><?= h($q['transaction_type']) ?></td>
                        <td>
    <span class="status status-<?= strtolower(h($q['status'])) ?>">
        <?= h($q['status']) ?>
    </span>
</td>
                        <td><?= h(date('h:i A', strtotime($q['created_at']))) ?></td>
                        <td>
                            <?php if ($q['status'] === 'Serving'): ?>
                                <form method="POST"
      onsubmit="return confirm('Are you sure you want to mark this queue as completed?');">
                                    <input type="hidden" name="queue_id"
                                           value="<?= (int)$q['queue_id'] ?>">
                                    <button class="complete" 
                                            name="action"
                                            value="complete"
                                            type="submit">
                                        Complete
                                    </button>
                                </form>
                            <?php elseif ($q['status'] === 'Waiting'): ?>
                                <form method="POST"
      class="queue-action-form"
      onsubmit="return confirmQueueAction(this, 'complete');">
                                    <input type="hidden" name="queue_id"
                                           value="<?= (int)$q['queue_id'] ?>">
                                    <button class="cancel"
                                            name="action"
                                            value="cancel"
                                            type="submit">
                                        <form method="POST"
      class="queue-action-form"
      onsubmit="return confirmQueueAction(this, 'cancel');">
                                    </button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr id="noResults" style="display: none;">
    <td colspan="7" style="text-align: center; padding: 20px;">
        No matching queues found.
    </td>
</tr>
            </tbody>
        </table>
    </div>

    <a class="back" href="index.php">← Back to Home</a>
    <br>
    <a class="back" href="queue_status.php">View Public Queue Status</a>
</main>

<script>
    const queueSearch = document.getElementById('queueSearch');
    const statusFilter = document.getElementById('statusFilter');
    const queueTable = document.getElementById('queueTable');
    

    function filterQueues() {
        function filterQueues() {
    const searchValue = queueSearch.value.toLowerCase().trim();
    const selectedStatus = statusFilter.value;
    const rows = queueTable.querySelectorAll(
    'tbody tr:not(#noResults)'
);

    let visibleCount = 0;

        rows.forEach(function (row) {
            const rowText = row.textContent.toLowerCase();

            const matchesSearch = rowText.includes(searchValue);
            const statusCell = row.cells[4];
            const rowStatus = statusCell
                ? statusCell.textContent.trim()
                : '';
            document.getElementById('queueCount').textContent =
    'Showing ' + visibleCount + ' of ' + rows.length + ' queues';
    document.getElementById('noResults').style.display =
    visibleCount === 0 ? '' : 'none';

            const matchesStatus =
                selectedStatus === '' ||
                rowStatus === selectedStatus;

            if (matchesSearch && matchesStatus) {
    row.style.display = '';
    visibleCount++;
} else {
    row.style.display = 'none';
        });
    }

    queueSearch.addEventListener('input', filterQueues);
    statusFilter.addEventListener('change', filterQueues);
    document.getElementById('clearFilters')
    .addEventListener('click', function () {
        queueSearch.value = '';
        statusFilter.value = '';
        filterQueues();
        document.getElementById('lastUpdated').textContent =
    new Date().toLocaleString();
    });
    
    function confirmQueueAction(form, action) {
    const message = action === 'complete'
        ? 'Are you sure you want to mark this queue as completed?'
        : 'Are you sure you want to cancel this queue?';

    if (!confirm(message)) {
        return false;
    }

    const buttons = form.querySelectorAll(
        'button[type="submit"]'
    );

    buttons.forEach(function (button) {
        button.disabled = true;
        button.textContent = 'Processing...';
    });

    return true;
}
</script>
</body>
</html>
