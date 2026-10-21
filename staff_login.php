
<?php
session_start();
require_once 'db.php';

$error = '';

if (isset($_SESSION['staff_id'])) {
    header('Location: staff_queue.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT staff_id, username, password_hash
             FROM staff_accounts
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->execute([$username]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff && password_verify($password, $staff['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['staff_id'] = $staff['staff_id'];
            $_SESSION['staff_username'] = $staff['username'];

            header('Location: staff_queue.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | MATS Queue System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: white;
            width: 90%;
            max-width: 400px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #173b70;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #173b70;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #24549a;
        }

        .error {
            background: #ffe8e8;
            color: #a40000;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>MATS Queue System</h2>
    <p class="subtitle">Staff Login Portal</p>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="staff_login.php">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            required
            autocomplete="username"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Log In</button>
    </form>
</div>

</body>
</html>
