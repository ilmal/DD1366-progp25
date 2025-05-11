<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $list_id = $_POST['list_id'] ?? null;
    if (!$list_id) {
        $_SESSION['error'] = "Ingen lista vald.";
        header("Location: shopping_lists.php");
        exit();
    }

    $db = getDb();
    $db->beginTransaction();
    
    try {
        if (isset($_POST['purchased']) && is_array($_POST['purchased'])) {
            foreach ($_POST['purchased'] as $list_item_id) {
                $stmt = $db->prepare("
                    SELECT item_id FROM shopping_list_items 
                    WHERE list_item_id = :list_item_id AND list_id = :list_id
                ");
                $stmt->execute(['list_item_id' => $list_item_id, 'list_id' => $list_id]);
                $item_id = $stmt->fetchColumn();
                
                if ($item_id) {
                    recordPurchase($item_id);
                    markItemAsPurchased($user_id, $list_item_id);
                }
            }
        }
        
        if (isset($_POST['impulse_buys']) && !empty($_POST['impulse_buys'])) {
            $impulse_buys = array_map('trim', explode(',', $_POST['impulse_buys']));
            foreach ($impulse_buys as $item_name) {
                if (empty($item_name)) continue;
                $stmt = $db->prepare("
                    SELECT item_id FROM items 
                    WHERE user_id = :user_id AND item_name ILIKE :item_name
                ");
                $stmt->execute(['user_id' => $user_id, 'item_name' => $item_name]);
                $item_id = $stmt->fetchColumn();
                
                if (!$item_id) {
                    $stmt = $db->prepare("
                        INSERT INTO items (user_id, item_name) 
                        VALUES (:user_id, :item_name) 
                        RETURNING item_id
                    ");
                    $stmt->execute(['user_id' => $user_id, 'item_name' => $item_name]);
                    $item_id = $stmt->fetchColumn();
                }
                recordPurchase($item_id);
            }
        }
        
        $db->commit();
        $_SESSION['message'] = "Dina inköp har registrerats!";
        header("Location: menu.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Ett fel uppstod: " . $e->getMessage();
        header("Location: confirm_purchases.php?list_id=$list_id");
        exit();
    }
} else {
    header("Location: shopping_lists.php");
    exit();
}
?>