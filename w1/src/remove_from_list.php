<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $list_item_id = $_POST["list_item_id"];
    $list_id = $_SESSION["selected_list_id"] ?? null;
    if ($list_id) {
        $db = getDb();
        $stmt = $db->prepare("
            DELETE FROM shopping_list_items 
            WHERE list_item_id = :list_item_id AND list_id = :list_id
        ");
        $stmt->execute(['list_item_id' => $list_item_id, 'list_id' => $list_id]);
        header("Location: generate_shopping_list.php?list_id=$list_id");
        exit();
    }
    header("Location: shopping_lists.php");
    exit();
}
?>