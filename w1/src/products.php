<?php
include 'database.php';
session_start();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_product'])) {
            $name = trim($_POST['name']);
            if (empty($name)) {
                throw new Exception('Product name cannot be empty.');
            }
            
            // Check if product already exists
            $stmt = $pdo->prepare('SELECT name FROM Products WHERE user_id = ? AND LOWER(name) = LOWER(?)');
            $stmt->execute([$user_id, $name]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                throw new Exception('Product "' . htmlspecialchars($existing['name']) . '" already exists.');
            }
            
            $stmt = $pdo->prepare('INSERT INTO Products (user_id, name) VALUES (?, ?)');
            $stmt->execute([$user_id, $name]);
            $success = "Product added successfully!";
        } elseif (isset($_POST['delete_product'])) {
            $product_id = $_POST['product_id'];
            $stmt = $pdo->prepare('DELETE FROM Products WHERE id = ? AND user_id = ?');
            $stmt->execute([$product_id, $user_id]);
            $success = "Product deleted successfully!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
    
    // Only redirect if no error occurred
    if (!isset($error)) {
        header('Location: products.php?success=' . urlencode($success));
        exit;
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

$stmt = $pdo->prepare('SELECT id, name FROM Products WHERE user_id = ? ORDER BY name');
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

include 'header.php';
?>

<h1>Manage Products</h1>

<?php if (isset($success)): ?>
    <p><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h2>Existing Products (<?= count($products) ?>)</h2>
<?php if (empty($products)): ?>
    <p>No products found. Add your first product below.</p>
<?php else: ?>
    <ul>
    <?php foreach ($products as $product): ?>
        <li>
            <span><strong><?= htmlspecialchars($product['name']) ?></strong></span>
            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product? This will also remove all purchase history.')">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button name="delete_product">Delete</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Add New Product</h2>
<form method="post">
    <div>
        <input type="text" name="name" required placeholder="Product name">
        <button name="add_product">Add Product</button>
    </div>
    <p>Note: Product names are case-insensitive. Duplicates will be rejected.</p>
</form>

<p><a href="shopping_list.php">← Back to Shopping List</a></p>

 