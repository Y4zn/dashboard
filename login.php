<?php
try {
  $pdo = new PDO("mysql:host=localhost;dbname=dashboard", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

session_start();

if (isset($_SESSION['username'])) {
header("Location: dashboard.php");
exit;
}

if (isset($_POST['login'])) {

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute(array($username, $username));
$user = $stmt->fetch();

if ($user && $password === $user['password']) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['lastname'] = $user['lastname'];

    header("Location: dashboard.php");
    exit;
    } else {
    $message = "Invalid username or password.";
}
}
?>
    
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #f6f8fa;
            --card-bg: #fff;
            --border-radius: 12px;
            --shadow: 0 4px 24px #0002;
        }
        body {
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            margin: 0;
            background: var(--bg);
            color: #222;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: var(--card-bg);
            padding: 2.5em 2em 2em 2em;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid #e5e7eb;
            max-width: 350px;
            width: 100%;
            animation: fadeInUp 0.6s cubic-bezier(.23,1.01,.32,1) both;
        }
        h1 {
            margin-top: 0;
            margin-bottom: 1.2em;
            font-size: 2em;
            text-align: center;
            color: var(--primary-dark);
        }
        label {
            display: block;
            margin-top: 1.2em;
            font-weight: 500;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.7em 1em;
            margin-top: 0.5em;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1em;
            background: #f9fafb;
            transition: border 0.2s, box-shadow 0.2s, background 0.3s;
        }
        input:focus {
            background: #e8f0fe;
            box-shadow: 0 0 0 2px #2563eb55;
            outline: none;
        }
        button {
            margin-top: 2em;
            padding: 0.9em 2em;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            box-shadow: 0 2px 8px #2563eb22;
            transition: background 0.2s, box-shadow 0.2s;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        button:hover {
            background: var(--primary-dark);
        }
        button:active::after {
            content: "";
            position: absolute;
            left: 50%; top: 50%;
            width: 200%; height: 200%;
            background: rgba(37,99,235,0.15);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            animation: ripple 0.5s linear;
            pointer-events: none;
        }
        @keyframes ripple {
            to { transform: translate(-50%, -50%) scale(1); opacity: 0; }
        }
        .login-message {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 0.8em 1em;
            margin-bottom: 1em;
            text-align: center;
            font-size: 1em;
            animation: fadeInUp 0.5s;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-footer {
            margin-top: 2em;
            text-align: center;
            color: #888;
            font-size: 0.95em;
        }
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s;
        }
        .login-footer a:hover {
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Login</h1>
        <?php if (isset($message)): ?>
            <div class="login-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="username">Gebruikersnaam of e-mail</label>
            <input type="text" name="username" id="username" required autofocus>
            <label for="password">Wachtwoord</label>
            <input type="password" name="password" id="password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <div class="login-footer">
            <!-- <a href="register.php">Nog geen account?</a> -->
        </div>
    </div>
</body>
</html>
