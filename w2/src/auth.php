<?php
// src/auth.php
session_start();
require_once 'Database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function hashPassword($password) {
    return hash('sha3-512', $password);
}

function createUser($username, $password) {
    $db = new Database();
    $pdo = $db->getPDO();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $passwordHash = hashPassword($password);
        $stmt->execute([$username, $passwordHash]);
        return true;
    } catch (PDOException $e) {
        return false; // Username already exists or other error
    }
}

function authenticateUser($username, $password) {
    $db = new Database();
    $pdo = $db->getPDO();
    
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['password_hash'] === hashPassword($password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        return true;
    }
    
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>