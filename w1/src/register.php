<?php
session_start();
require('functions.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];

    if ($password !== $confirm) {
        $_SESSION['message'] = "<p style='background-color:Tomato;'>Passwords do not match</p>";
        header("Location: registration.php");
        exit();
    }

    $db = getDb();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        $_SESSION['message'] = "<p style='background-color:Tomato;'>Username already taken</p>";
        header("Location: registration.php");
        exit();
    }

    $password_hash = hash('sha3-512', $password);
    $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
    $stmt->execute(['username' => $username, 'password_hash' => $password_hash]);
    $user_id = $db->lastInsertId();

    $_SESSION["logged_in_user"] = $username;
    $_SESSION["user_id"] = $user_id;
    session_regenerate_id(true); // Prevent session fixation
    header("Location: menu.php");
    exit();
}
?>