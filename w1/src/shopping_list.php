<?php
include 'database.php';
session_start();
$user_id = $_SESSION['user_id'];

// Handle success message
if (isset($_GET['success'])) {
    $success_message = "Shopping list confirmed successfully!";
}

// Function to get purchase frequency estimate for a product
function getPurchaseFrequency($pdo, $user_id, $product_id) {
    $stmt = $pdo->prepare('
        WITH intervals AS (
            SELECT (pu.purchase_date - LAG(pu.purchase_date) OVER (ORDER BY pu.purchase_date))::int AS interval
            FROM Purchases pu
            WHERE pu.user_id = ? AND pu.product_id = ?
        )
        SELECT GREATEST(AVG(interval), 0) AS avg_interval
        FROM intervals
        WHERE interval IS NOT NULL
    ');
    $stmt->execute([$user_id, $product_id]);
    $result = $stmt->fetch();
    
    if (!$result || $result['avg_interval'] === null) {
        return "No pattern yet";
    }
    
    $avg_interval = $result['avg_interval'];
    
    if ($avg_interval < 7) {
        return "~" . round($avg_interval) . " days";
    } elseif ($avg_interval < 30) {
        return "~" . round($avg_interval / 7) . " weeks";
    } else {
        return "~" . round($avg_interval / 30) . " months";
    }
}

// Function to get next suggested date
function getNextSuggestedDate($pdo, $user_id, $product_id) {
    $stmt = $pdo->prepare('
        WITH intervals AS (
            SELECT (pu.purchase_date - LAG(pu.purchase_date) OVER (ORDER BY pu.purchase_date))::int AS interval
            FROM Purchases pu
            WHERE pu.user_id = ? AND pu.product_id = ?
        ),
        avg_interval AS (
            SELECT GREATEST(AVG(interval), 0) AS avg_interval
            FROM intervals
            WHERE interval IS NOT NULL
        ),
        last_purchase AS (
            SELECT MAX(purchase_date) AS last_purchase
            FROM Purchases
            WHERE user_id = ? AND product_id = ?
        )
        SELECT 
            lp.last_purchase + (GREATEST(ai.avg_interval, 0) || \' days\')::interval AS next_suggested
        FROM last_purchase lp, avg_interval ai
        WHERE ai.avg_interval IS NOT NULL
    ');
    $stmt->execute([$user_id, $product_id, $user_id, $product_id]);
    $result = $stmt->fetch();
    
    if ($result && $result['next_suggested']) {
        $next_date = new DateTime($result['next_suggested']);
        $today = new DateTime();
        $diff = $today->diff($next_date);
        
        if ($next_date <= $today) {
            return "Due now";
        } else {
            if ($diff->days < 7) {
                return "in " . $diff->days . " days";
            } elseif ($diff->days < 30) {
                return "in " . ceil($diff->days / 7) . " weeks";
            } else {
                return "in " . ceil($diff->days / 30) . " months";
            }
        }
    }
    
    return "Unknown";
}

// Simplified function to get suggested products
function getSuggestedProducts($pdo, $user_id) {
    // Get all products for the user
    $stmt = $pdo->prepare('SELECT id, name FROM Products WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $all_products = $stmt->fetchAll();
    
    $suggested_products = [];
    
    foreach ($all_products as $product) {
        $frequency = getPurchaseFrequency($pdo, $user_id, $product['id']);
        $next_suggested = getNextSuggestedDate($pdo, $user_id, $product['id']);
        
        // Suggest if never bought or if due now
        if ($frequency === "No pattern yet" || $next_suggested === "Due now") {
            $suggested_products[] = $product;
        }
    }
    
    error_log("Suggested products: " . print_r(array_column($suggested_products, 'name'), true));
    
    return $suggested_products;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        error_log("POST request received");
        error_log("POST data: " . print_r($_POST, true));
        
        // Check if there's already an unconfirmed shopping list
        $stmt = $pdo->prepare('SELECT id FROM ShoppingLists WHERE user_id = ? AND confirmed_at IS NULL');
        $stmt->execute([$user_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Delete the existing unconfirmed list and its items
            $stmt = $pdo->prepare('DELETE FROM ShoppingListItems WHERE shopping_list_id = ?');
            $stmt->execute([$existing['id']]);
            $stmt = $pdo->prepare('DELETE FROM ShoppingLists WHERE id = ?');
            $stmt->execute([$existing['id']]);
            error_log("Deleted existing unconfirmed shopping list: " . $existing['id']);
        }

        // Create new shopping list
        $stmt = $pdo->prepare('INSERT INTO ShoppingLists (user_id, created_at) VALUES (?, CURRENT_DATE)');
        $stmt->execute([$user_id]);
        $shopping_list_id = $pdo->lastInsertId();
        error_log("Created shopping list ID: $shopping_list_id");

        $items_added = 0;

        // Add selected existing products
        if (isset($_POST['products']) && is_array($_POST['products'])) {
            error_log("Processing " . count($_POST['products']) . " selected products");
            foreach ($_POST['products'] as $product_id) {
                $stmt = $pdo->prepare('INSERT INTO ShoppingListItems (shopping_list_id, product_id, purchased) VALUES (?, ?, FALSE)');
                $stmt->execute([$shopping_list_id, $product_id]);
                $items_added++;
                error_log("Added existing product ID $product_id to shopping list");
            }
        } else {
            error_log("No products selected");
        }

        // Add new products with duplicate checking
        if (isset($_POST['new_products']) && !empty(trim($_POST['new_products']))) {
            $new_products = array_map('trim', explode(',', $_POST['new_products']));
            $duplicate_products = [];
            
            foreach ($new_products as $name) {
                if ($name !== '') {
                    // Check if product already exists for this user
                    $stmt = $pdo->prepare('SELECT id, name FROM Products WHERE user_id = ? AND LOWER(name) = LOWER(?)');
                    $stmt->execute([$user_id, $name]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $duplicate_products[] = $product['name'];
                        continue;
                    } else {
                        // Create new product
                        $stmt = $pdo->prepare('INSERT INTO Products (user_id, name) VALUES (?, ?)');
                        $stmt->execute([$user_id, $name]);
                        $product_id = $pdo->lastInsertId();
                        error_log("Created new product: $name with ID $product_id");
                        
                        // Add to shopping list
                        $stmt = $pdo->prepare('INSERT INTO ShoppingListItems (shopping_list_id, product_id, purchased) VALUES (?, ?, FALSE)');
                        $stmt->execute([$shopping_list_id, $product_id]);
                        $items_added++;
                        error_log("Added new product $name to shopping list");
                    }
                }
            }
            
            if (!empty($duplicate_products)) {
                throw new Exception("The following products already exist: " . implode(', ', $duplicate_products) . ". Please select them from the list instead.");
            }
        }

        if ($items_added > 0) {
            error_log("Redirecting to confirm.php with $items_added items");
            header('Location: confirm.php');
            exit;
        } else {
            $error = "Please select at least one product or add new products.";
            error_log("No items added, showing error");
        }
    } catch (Exception $e) {
        error_log("Error creating shopping list: " . $e->getMessage());
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log("Database error creating shopping list: " . $e->getMessage());
        $error = "Failed to create shopping list: " . $e->getMessage();
    }
}

// Get suggested products
$suggested_products = getSuggestedProducts($pdo, $user_id);
$suggested_ids = array_column($suggested_products, 'id');

// Get all products for the user with frequency information
$stmt = $pdo->prepare('SELECT id, name FROM Products WHERE user_id = ? ORDER BY name');
$stmt->execute([$user_id]);
$all_products = $stmt->fetchAll();

include 'header.php';
?>

<h1>Create Shopping List</h1>
<?php if (isset($success_message)): ?>
    <p style="color: green;"><?= htmlspecialchars($success_message) ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <h2>All Products (Suggested items are pre-checked)</h2>
    <?php if (empty($all_products)): ?>
        <p>No products found. Add some products first or create new ones below.</p>
    <?php else: ?>
        <div style="margin: 20px 0;">
            <button type="button" onclick="selectAll()">Select All</button>
            <button type="button" onclick="deselectAll()">Deselect All</button>
        </div>
        
        <div style="background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <strong>Info:</strong> 
            <span style="color: green;">Green = Due now</span> | 
            <span style="color: orange;">Orange = Due soon</span> | 
            <span style="color: blue;">Blue = Suggested (never bought)</span>
        </div>
        
        <?php foreach ($all_products as $product): ?>
            <?php 
            $is_suggested = in_array($product['id'], $suggested_ids);
            $frequency = getPurchaseFrequency($pdo, $user_id, $product['id']);
            $next_suggested = getNextSuggestedDate($pdo, $user_id, $product['id']);
            
            // Determine color based on status
            $color = '#000';
            if ($is_suggested) {
                if ($next_suggested === "Due now" || $next_suggested === "in 0 days") {
                    $color = 'green';
                } elseif ($frequency === "No pattern yet") {
                    $color = 'blue';
                } elseif (strpos($next_suggested, 'in') === 0) {
                    $color = 'orange';
                }
            }
            
            error_log("Product {$product['name']} (ID: {$product['id']}): Frequency=$frequency, Next=$next_suggested, Suggested=" . ($is_suggested ? 'YES' : 'NO'));
            ?>
            <div style="margin: 8px 0; padding: 5px; border-left: 3px solid <?= $color ?>;">
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" name="products[]" value="<?= $product['id'] ?>" <?= $is_suggested ? 'checked' : '' ?> style="margin-right: 10px;">
                    <span style="flex: 1;">
                        <strong><?= htmlspecialchars($product['name']) ?></strong>
                        <?= $is_suggested ? ' <em>(Suggested)</em>' : '' ?>
                    </span>
                    <span style="font-size: 0.9em; color: #666; margin-left: 10px;">
                        Frequency: <?= $frequency ?>
                        <?php if ($frequency !== "No pattern yet"): ?>
                            | Next: <?= $next_suggested ?>
                        <?php endif; ?>
                    </span>
                </label>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Add New Products</h2>
    <p style="color: #666; font-size: 0.9em;">Note: Adding a product that already exists will show an error.</p>
    <input type="text" name="new_products" placeholder="Enter new products, separated by commas" style="width: 400px;">
    
    <br><br>
    <button type="submit">Create Shopping List</button>
</form>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="products[]"]');
    checkboxes.forEach(cb => cb.checked = true);
}
function deselectAll() {
    const checkboxes = document.querySelectorAll('input[name="products[]"]');
    checkboxes.forEach(cb => cb.checked = false);
}
</script>

<p><a href="products.php">Manage Products</a></p>

 