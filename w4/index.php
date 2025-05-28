<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect to shopping list
    header('Location: src/shopping_list.php');
    exit;
} else {
    // User is not logged in, redirect to login
    header('Location: src/login.php');
    exit;
}
?>