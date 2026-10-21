
<?php
session_start();

if (empty($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $error = 'The new passwords do not match.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'The new password must be at least 12 characters.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT password_hash
             FROM staff_accounts
             WHERE staff_id = ?"
        );
        $stmt->execute([$_SESSION['staff_id']]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$staff ||
            !password_verify($currentPassword, $staff['password_hash'])
        ) {
            $error = 'The current password is incorrect.';
        } else {
            $newHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "UPDATE staff_accounts
                 SET password_hash = ?
                 WHERE staff_id = ?"
            );

            $stmt->execute([
                $newHash,
                $_SESSION['staff_id']
            ]);

            $message = 'Password changed successfully.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Staff Password | MATS</title>
</head>
<body>
    <h2>Change Staff Password</h2>

    <?php if ($message !== ''): ?>
        <p style="color: green;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Current Password</label><br>
        <input type="password" name="current_password"
               required autocomplete="current-password"><br><br>

        <label>New Password</label><br>
        <input type="password" name="new_password"
               minlength="12" required
               autocomplete="new-password"><br><br>

        <label>Confirm New Password</label><br>
        <input type="password" name="confirm_password"
               minlength="12" required
               autocomplete="new-password"><br><br>

        <button type="submit">Change Password</button>
    </form>

    <p><a href="staff_queue.php">Back to Staff Dashboard</a></p>
</body>
</html>
