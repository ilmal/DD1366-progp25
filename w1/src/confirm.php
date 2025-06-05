<?php
include 'database.php';
session_start();
$user_id = $_SESSION['user_id'];

try {
    // Get the most recent unconfirmed shopping list
    $stmt = $pdo->prepare('SELECT id FROM ShoppingLists WHERE user_id = ? AND confirmed_at IS NULL ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $shopping_list = $stmt->fetch();
    
    if (!$shopping_list) {
        $error = 'No unconfirmed shopping list found. Please create a shopping list first.';
    } else {
        $shopping_list_id = $shopping_list['id'];
        error_log("Working with shopping list ID: $shopping_list_id");

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                error_log("POST request received in confirm.php");
                error_log("POST data: " . print_r($_POST, true));
                
                // set all items in this shopping list to NOT purchased
                $stmt = $pdo->prepare('UPDATE ShoppingListItems SET purchased = FALSE WHERE shopping_list_id = ?');
                $result = $stmt->execute([$shopping_list_id]);
                error_log("Reset all items to unpurchased, result: " . ($result ? 'success' : 'failed'));
                
                // // count items 
                // $stmt = $pdo->prepare('SELECT COUNT(*) FROM ShoppingListItems WHERE shopping_list_id = ?');
                // $stmt->execute([$shopping_list_id]);
                // $total_items = $stmt->fetchColumn();
                // error_log("Total items in shopping list: $total_items");
                
                // Then mark the checked items as purchased
                if (isset($_POST['purchased']) && is_array($_POST['purchased'])) {
                    error_log("Processing " . count($_POST['purchased']) . " purchased items");
                    $stmt = $pdo->prepare('UPDATE ShoppingListItems SET purchased = TRUE WHERE id = ? AND shopping_list_id = ?');
                    foreach ($_POST['purchased'] as $item_id) {
                        $result = $stmt->execute([$item_id, $shopping_list_id]);
                        if ($result) {
                            error_log("Marked item ID $item_id as purchased");
                        } else {
                            error_log("Failed to mark item ID $item_id as purchased");
                        }
                    }
                } else {
                    error_log("No purchased items in POST data");
                }
                
                // Add any impulse buys
                if (isset($_POST['new_products']) && !empty(trim($_POST['new_products']))) {
                    $new_products = array_map('trim', explode(',', $_POST['new_products']));
                    foreach ($new_products as $name) {
                        if ($name !== '') {
                            // Check if product exists
                            $stmt = $pdo->prepare('SELECT id FROM Products WHERE user_id = ? AND LOWER(name) = LOWER(?)');
                            $stmt->execute([$user_id, $name]);
                            $product = $stmt->fetch();
                            
                            if ($product) {
                                $product_id = $product['id'];
                            } else {
                                // Create new product
                                $stmt = $pdo->prepare('INSERT INTO Products (user_id, name) VALUES (?, ?)');
                                $stmt->execute([$user_id, $name]);
                                $product_id = $pdo->lastInsertId();
                                error_log("Created new impulse buy product: $name");
                            }
                            
                            // Add to shopping list as purchased
                            $stmt = $pdo->prepare('INSERT INTO ShoppingListItems (shopping_list_id, product_id, purchased) VALUES (?, ?, TRUE)');
                            $stmt->execute([$shopping_list_id, $product_id]);
                            error_log("Added impulse buy $name to shopping list");
                        }
                    }
                }

                // Confirm the shopping list
                $stmt = $pdo->prepare('UPDATE ShoppingLists SET confirmed_at = CURRENT_DATE WHERE id = ?');
                $stmt->execute([$shopping_list_id]);
                error_log("Confirmed shopping list $shopping_list_id");

                // Record purchases for all purchased items
                $stmt = $pdo->prepare('
                    SELECT sli.product_id 
                    FROM ShoppingListItems sli 
                    WHERE sli.shopping_list_id = ? AND sli.purchased = TRUE
                ');
                $stmt->execute([$shopping_list_id]);
                $purchased_items = $stmt->fetchAll();
                
                $purchases_recorded = 0;
                foreach ($purchased_items as $item) {
                    $stmt = $pdo->prepare('INSERT INTO Purchases (user_id, product_id, purchase_date) VALUES (?, ?, CURRENT_DATE)');
                    $stmt->execute([$user_id, $item['product_id']]);
                    $purchases_recorded++;
                    error_log("Recorded purchase for product ID {$item['product_id']}");
                }
                
                error_log("Recorded $purchases_recorded purchases");
                header('Location: shopping_list.php?success=1');
                exit;
                
            } catch (PDOException $e) {
                error_log("Error confirming purchases: " . $e->getMessage());
                $error = "Error confirming purchases: " . $e->getMessage();
            }
        }

        // Get all items in the shopping list
        $stmt = $pdo->prepare('
            SELECT sli.id, p.name, sli.purchased, p.id as product_id
            FROM ShoppingListItems sli 
            JOIN Products p ON sli.product_id = p.id 
            WHERE sli.shopping_list_id = ? 
            ORDER BY p.name
        ');
        $stmt->execute([$shopping_list_id]);
        $items = $stmt->fetchAll();
        
        error_log("Retrieved " . count($items) . " items for confirmation");
        if (empty($items)) {
            $error = "No items found in shopping list. Please go back and create a new shopping list.";
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    error_log("Database error in confirm.php: " . $e->getMessage());
}

include 'header.php';
?>

<h1>Confirm Purchases</h1>

<?php if (isset($error)): ?>
    <div style="color: red; margin: 20px 0;">
        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        <br><a href="shopping_list.php">← Go back to create a new shopping list</a>
    </div>
<?php else: ?>
    <p>Check the items you actually purchased:</p>
    
    <form method="post" onsubmit="return confirmSubmit()">
        <h2>Shopping List Items (<?= count($items) ?> items)</h2>
        
        <div style="margin: 20px 0;">
            <button type="button" onclick="selectAllPurchased()">Select All</button>
            <button type="button" onclick="deselectAllPurchased()">Deselect All</button>
        </div>
        
        <?php foreach ($items as $item): ?>
            <div style="margin: 10px 0;">
                <label>
                    <input type="checkbox" name="purchased[]" value="<?= $item['id'] ?>" <?= $item['purchased'] ? 'checked' : '' ?>>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                </label>
            </div>
        <?php endforeach; ?>

        <h2>Add Impulse Buys</h2>
        <p>Did you buy anything not on your list?</p>
        <input type="text" name="new_products" placeholder="Enter impulse buys, separated by commas" style="width: 300px;">
        
        <br><br>
        <button type="submit" style="padding: 10px 20px; font-size: 16px;">Confirm Purchases</button>
        <a href="shopping_list.php" style="margin-left: 20px;">← Back to Shopping List</a>
    </form>
    
    <script>
    function selectAllPurchased() {
        const checkboxes = document.querySelectorAll('input[name="purchased[]"]');
        checkboxes.forEach(cb => cb.checked = true);
    }
    function deselectAllPurchased() {
        const checkboxes = document.querySelectorAll('input[name="purchased[]"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
    function confirmSubmit() {
        const checkboxes = document.querySelectorAll('input[name="purchased[]"]:checked');
        console.log('Selected items:', checkboxes.length);
        return true;
    }
    </script>
<?php endif; ?>

 