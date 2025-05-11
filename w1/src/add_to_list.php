<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST["item_id"];
    $list_id = $_SESSION["selected_list_id"] ?? null;
    if ($list_id) {
        $db = getDb();
        $stmt = $db->prepare("INSERT INTO shopping_list_items (list_id, item_id) VALUES (:list_id, :item_id) ON CONFLICT DO NOTHING");
        $stmt->execute(['list_id' => $list_id, 'item_id' => $item_id]);
        header("Location: generate_shopping_list.php?list_id=$list_id");
        exit();
    }
    header("Location: shopping_lists.php"); // No list selected
    exit();
}
?>