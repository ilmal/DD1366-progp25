<?php
session_start();
if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'create_account.php'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping List App</title>
</head>
<body>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="shopping_list.php">Shopping List</a>
            <a href="products.php">Products</a>
            <a href="confirm.php">Confirm Purchases</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="create_account.php">Create Account</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
