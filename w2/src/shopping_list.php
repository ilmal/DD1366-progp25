<?php
// src/shopping_list.php
require_once 'auth.php';
requireLogin();

$db = new Database();
$pdo = $db->getPDO();
$userId = getCurrentUserId();

// Calculate suggestions based on purchase history
function calculateSuggestions($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, 
               COALESCE(AVG(EXTRACT(EPOCH FROM (purchase_date - LAG(purchase_date) OVER (PARTITION BY p.id ORDER BY purchase_date)))/86400), 0) as avg_interval,
               MAX(purchase_date) as last_purchase
        FROM products p
        LEFT JOIN purchases pu ON p.id = pu.product_id AND p.user_id = pu.user_id
        WHERE p.user_id = ?
        GROUP BY p.id, p.name
    ");
    $stmt->execute([$userId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = [];
    $today = new DateTime();
    
    foreach ($products as $product) {
        if ($product['last_purchase'] === null) {
            // Never purchased before - always suggest
            $suggestions[] = $product;
        } else {
            $lastPurchase = new DateTime($product['last_purchase']);
            $daysSinceLastPurchase = $today->diff($lastPurchase)->days;
            $avgInterval = $product['avg_interval'];
            
            if ($avgInterval > 0 && $daysSinceLastPurchase >= $avgInterval) {
                $suggestions[] = $product;
            }
        }
    }
    
    return $suggestions;
}

$suggestions = calculateSuggestions($pdo, $userId);

// Get all products for manual selection
$stmt = $pdo->prepare("SELECT id, name FROM products WHERE user_id = ? ORDER BY name");
$stmt->execute([$userId]);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_list'])) {
    $selectedProducts = $_POST['products'] ?? [];
    
    if (!empty($selectedProducts)) {
        try {
            $db->beginTransaction();
            
            // Create new shopping list
            $stmt = $pdo->prepare("INSERT INTO shopping_lists (user_id) VALUES (?) RETURNING id");
            $stmt->execute([$userId]);
            $listId = $stmt->fetchColumn();
            
            // Add selected products to the list
            $stmt = $pdo->prepare("INSERT INTO shopping_list_items (shopping_list_id, product_id) VALUES (?, ?)");
            foreach ($selectedProducts as $productId) {
                $stmt->execute([$listId, $productId]);
            }
            
            $db->commit();
            header('Location: confirm_shopping.php?list_id=' . $listId);
            exit();
        } catch (Exception $e) {
            $db->rollback();
            $error = "Ett fel uppstod när listan skulle sparas.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inköpslista</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .product-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .product-item label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .product-item input {
            margin-right: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
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
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .nav-links {
            display: flex;
            gap: 10px;
        }
        h1 {
            color: #333;
            margin-bottom: 0;
        }
        .welcome {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Inköpslista</h1>
            <div class="welcome">Välkommen, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
        </div>
        <div class="nav-links">
            <a href="manage_products.php" class="btn btn-secondary">Hantera produkter</a>
            <a href="logout.php" class="btn btn-primary">Logga ut</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="section">
                <h3>Föreslagna produkter</h3>
                <p>Baserat på dina tidigare inköpsmönster föreslår vi följande produkter:</p>
                
                <?php if (empty($suggestions)): ?>
                    <p>Inga förslag just nu. Dina produkter verkar vara välförsedda!</p>
                <?php else: ?>
                    <div class="product-list">
                        <?php foreach ($suggestions as $product): ?>
                            <div class="product-item">
                                <label>
                                    <input type="checkbox" name="products[]" value="<?php echo $product['id']; ?>" checked>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>Alla produkter</h3>
                <p>Välj ytterligare produkter som inte automatiskt föreslagits:</p>
                
                <div class="product-list">
                    <?php 
                    $suggestedIds = array_column($suggestions, 'id');
                    foreach ($allProducts as $product):
                        if (!in_array($product['id'], $suggestedIds)):
                    ?>
                        <div class="product-item">
                            <label>
                                <input type="checkbox" name="products[]" value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </label>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>

            <button type="submit" name="save_list" class="btn btn-success">Spara inköpslista</button>
        </form>
    </div>
</body>
</html>