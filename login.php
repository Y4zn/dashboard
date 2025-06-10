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
</head>
<body>
    <?php if (isset($message)): ?>
        <div><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="container">
        <h1>Login</h1>
        <form method="post">
            <label for="username"><b>Username or email:</b></label>
            <input type="text" placeholder="" name="username" required>
            <br>
            <label for="password"><b>Password:</b></label>
            <input type="text" placeholder="" name="password" required>
            <br>
            <button type="submit" name="login">Login</button>
            <br>
            <!-- <button type="button" onclick="window.location.href='register.php'">Doesn't have an account?</button>  old -->
        </form>
    </div>
</body>
</html>
