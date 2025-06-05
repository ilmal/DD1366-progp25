<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare('INSERT INTO Users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $password_hash]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == '23505') { // unique violation
            $error = 'Username already exists';
        } else {
            $error = 'Error creating account';
        }
    }
}

include 'header.php';
?>

<h1>Create Account</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="post">
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <button type="submit">Create Account</button>
</form>
 