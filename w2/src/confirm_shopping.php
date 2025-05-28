<?php
// src/confirm_shopping.php
require_once 'auth.php';
requireLogin();

$db = new Database();
$pdo = $db->getPDO();
$userId = getCurrentUserId();

$listId = $_GET['list_id'] ?? 0;

// Verify the shopping list belongs to the current user
$stmt = $pdo->prepare("SELECT id FROM shopping_lists WHERE id = ? AND user_id = ?");
$stmt->execute([$listId, $userId]);
if (!$stmt->fetch()) {
    header('Location: shopping_list.php');
    exit();
}

$message = '';

// Handle adding new product during shopping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_impulse_product'])) {
    $productName = trim($_POST['impulse_product_name'] ?? '');
    
    if (!empty($productName)) {
        try {
            $db->beginTransaction();
            
            // Check if product already exists
            $stmt = $pdo->prepare("SELECT id FROM products WHERE user_id = ? AND name = ?");
            $stmt->execute([$userId, $productName]);
            $productId = $stmt->fetchColumn();
            
            if (!$productId) {
                // Create new product
                $stmt = $pdo->prepare("INSERT INTO products (user_id, name) VALUES (?, ?) RETURNING id");
                $stmt->execute([$userId, $productName]);
                $productId = $stmt->fetchColumn();
            }
            
            // Add to current shopping list
            $stmt = $pdo->prepare("INSERT INTO shopping_list_items (shopping_list_id, product_id) VALUES (?, ?)");
            $stmt->execute([$listId, $productId]);
            
            $db->commit();
            $message = 'Produkten "' . htmlspecialchars($productName) . '" har lagts till i listan.';
        } catch (Exception $e) {
            $db->rollback();
            $message = 'Ett fel uppstod när produkten skulle läggas till.';
        }
    }
}

// Handle confirming purchases
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchases'])) {
    $purchasedItems = $_POST['purchased_items'] ?? [];
    
    if (!empty($purchasedItems)) {
        try {
            $db->beginTransaction();
            
            $today = date('Y-m-d');
            
            // Mark items as purchased and add to purchase history
            $updateStmt = $pdo->prepare("UPDATE shopping_list_items SET is_purchased = TRUE WHERE id = ?");
            $insertStmt = $pdo->prepare("INSERT INTO purchases (user_id, product_id, purchase_date) VALUES (?, ?, ?)");
            
            foreach ($purchasedItems as $itemId) {
                // Verify item belongs to current user's list
                $stmt = $pdo->prepare("
                    SELECT sli.product_id 
                    FROM shopping_list_items sli 
                    JOIN shopping_lists sl ON sli.shopping_list_id = sl.id 
                    WHERE sli.id = ? AND sl.user_id = ?
                ");
                $stmt->execute([$itemId, $userId]);
                $productId = $stmt->fetchColumn();
                
                if ($productId) {
                    $updateStmt->execute([$itemId]);
                    $insertStmt->execute([$userId, $productId, $today]);
                }
            }
            
            $db->commit();
            header('Location: shopping_complete.php');
            exit();
        } catch (Exception $e) {
            $db->rollback();
            $message = 'Ett fel uppstod när inköpen skulle bekräftas.';
        }
    }
}

// Get shopping list items
$stmt = $pdo->prepare("
    SELECT sli.id, p.name, sli.is_purchased
    FROM shopping_list_items sli
    JOIN products p ON sli.product_id = p.id
    JOIN shopping_lists sl ON sli.shopping_list_id = sl.id
    WHERE sl.id = ? AND sl.user_id = ?
    ORDER BY p.name
");
$stmt->execute([$listId, $userId]);
$listItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bekräfta inköp</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section {
            margin-bottom: 30px;
        }
        .section h3 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .shopping-list {
            list-style: none;
            padding: 0;
        }
        .shopping-item {
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
        }
        .shopping-item.purchased {
            background: #d4edda;
            border-color: #c3e6cb;
            text-decoration: line-through;
            color: #666;
        }
        .shopping-item input {
            margin-right: 15px;
            transform: scale(1.2);
        }
        .shopping-item label {
            flex: 1;
            cursor: pointer;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            color: #155724;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .add-form {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        .add-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        .instructions {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bekräfta inköp</h1>
        <p>Bocka av varorna när du lägger dem i varukorgen</p>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="instructions">
            <strong>Instruktioner:</strong> Bocka av varorna när du plockar upp dem i butiken. 
            Om du upptäcker något du glömt eller vill köpa spontant kan du lägga till det här.
        </div>

        <form method="POST">
            <div class="section">
                <h3>Din inköpslista</h3>
                
                <?php if (empty($listItems)): ?>
                    <p>Listan är tom.</p>
                <?php else: ?>
                    <ul class="shopping-list">
                        <?php foreach ($listItems as $item): ?>
                            <li class="shopping-item <?php echo $item['is_purchased'] ? 'purchased' : ''; ?>">
                                <input type="checkbox" 
                                       id="item_<?php echo $item['id']; ?>" 
                                       name="purchased_items[]" 
                                       value="<?php echo $item['id']; ?>"
                                       <?php echo $item['is_purchased'] ? 'checked' : ''; ?>>
                                <label for="item_<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>Lägg till spontanköp</h3>
                <div class="add-form">
                    <div class="form-group">
                        <label for="impulse_product_name">Produktnamn:</label>
                        <input type="text" id="impulse_product_name" name="impulse_product_name" 
                               placeholder="T.ex. något nytt du hittat i butiken">
                    </div>
                    <button type="submit" name="add_impulse_product" class="btn btn-secondary">Lägg till</button>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" name="confirm_purchases" class="btn btn-success">
                    Bekräfta inköp och avsluta
                </button>
                <a href="shopping_list.php" class="btn btn-primary">Tillbaka till listan</a>
            </div>
        </form>
    </div>
</body>
</html>