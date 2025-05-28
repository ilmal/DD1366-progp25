<?php
// src/index.php
require_once 'auth.php';

if (isLoggedIn()) {
    header('Location: shopping_list.php');
} else {
    header('Location: login.php');
}
exit();
?>