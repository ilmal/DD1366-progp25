#!/bin/bash

PROJECT_DIR="shopping-list-app"

# Create project directory and navigate into it
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

# Create docker-compose.yml
cat << 'EOF' > docker-compose.yml
version: '3'
services:
  web:
    build: .
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
  db:
    image: postgres:13
    environment:
      POSTGRES_DB: shopping_list
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - db-data:/var/lib/postgresql/data
      - ./init.sql:/docker-entrypoint-initdb.d/init.sql
volumes:
  db-data:
EOF

# Create Dockerfile
cat << 'EOF' > Dockerfile
FROM php:7.4-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pgsql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html
EOF

# Create init.sql
cat << 'EOF' > init.sql
CREATE TABLE Users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE Products (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    UNIQUE (user_id, name)
);

CREATE TABLE ShoppingLists (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(id) ON DELETE CASCADE,
    created_at DATE NOT NULL,
    confirmed_at DATE
);

CREATE TABLE ShoppingListItems (
    id SERIAL PRIMARY KEY,
    shopping_list_id INT REFERENCES ShoppingLists(id) ON DELETE CASCADE,
    product_id INT REFERENCES Products(id) ON DELETE CASCADE,
    purchased BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE Purchases (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(id) ON DELETE CASCADE,
    product_id INT REFERENCES Products(id) ON DELETE CASCADE,
    purchase_date DATE NOT NULL
);
EOF

# Create database.php
cat << 'EOF' > database.php
<?php
$pdo = new PDO('pgsql:host=db;dbname=shopping_list', 'user', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
EOF

# Create header.php
cat << 'EOF' > header.php
<?php
session_start();
if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'create_account.php'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping List App</title>
</head>
<body>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="shopping_list.php">Shopping List</a>
            <a href="products.php">Products</a>
            <a href="confirm.php">Confirm Purchases</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="create_account.php">Create Account</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
EOF

# Create footer.php
cat << 'EOF' > footer.php
</body>
</html>
EOF

# Create login.php
cat << 'EOF' > login.php
<?php
include 'database.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare('SELECT id, password_hash FROM Users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: shopping_list.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<h1>Login</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>
<form method="post">
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <button type="submit">Login</button>
</form>
<?php include 'footer.php'; ?>
EOF

# Create create_account.php
cat << 'EOF' > create_account.php
<?php
include 'database.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare('INSERT INTO Users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $password_hash]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == '23505') { // unique violation
            $error = 'Username already exists';
        } else {
            $error = 'Error creating account';
        }
    }
}
?>

<h1>Create Account</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>
<form method="post">
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <button type="submit">Create Account</button>
</form>
<?php include 'footer.php'; ?>
EOF

# Create shopping_list.php
cat << 'EOF' > shopping_list.php
<?php
include 'database.php';
include 'header.php';

$user_id = $_SESSION['user_id'];

// Function to get suggested products
function getSuggestedProducts($pdo, $user_id) {
    // Products with no purchases
    $stmt = $pdo->prepare('
        SELECT p.id, p.name
        FROM Products p
        LEFT JOIN Purchases pu ON p.id = pu.product_id AND pu.user_id = p.user_id
        WHERE p.user_id = ?
        GROUP BY p.id, p.name
        HAVING COUNT(pu.id) = 0
    ');
    $stmt->execute([$user_id]);
    $no_purchases = $stmt->fetchAll();

    // Products with at least two purchases where last_purchase + avg_interval <= today
    $stmt = $pdo->prepare('
        WITH intervals AS (
            SELECT p.id, (pu.purchase_date - LAG(pu.purchase_date) OVER (PARTITION BY p.id ORDER BY pu.purchase_date))::int AS interval
            FROM Products p
            JOIN Purchases pu ON p.id = pu.product_id AND p.user_id = pu.user_id
            WHERE p.user_id = ?
        ),
        avg_intervals AS (
            SELECT id, AVG(interval) AS avg_interval
            FROM intervals
            WHERE interval IS NOT NULL
            GROUP BY id
            HAVING COUNT(*) >= 1
        ),
        last_purchases AS (
            SELECT p.id, MAX(pu.purchase_date) AS last_purchase
            FROM Products p
            JOIN Purchases pu ON p.id = pu.product_id AND p.user_id = pu.user_id
            WHERE p.user_id = ?
            GROUP BY p.id
        )
        SELECT lp.id, p.name
        FROM last_purchases lp
        JOIN avg_intervals ai ON lp.id = ai.id
        JOIN Products p ON lp.id = p.id
        WHERE lp.last_purchase + (ai.avg_interval || \' days\')::interval <= CURRENT_DATE
    ');
    $stmt->execute([$user_id, $user_id]);
    $with_purchases = $stmt->fetchAll();

    return array_merge($no_purchases, $with_purchases);
}

$suggested_products = getSuggestedProducts($pdo, $user_id);

// Get all products for the user
$stmt = $pdo->prepare('SELECT id, name FROM Products WHERE user_id = ?');
$stmt->execute([$user_id]);
$all_products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create new shopping list
    $stmt = $pdo->prepare('INSERT INTO ShoppingLists (user_id, created_at) VALUES (?, CURRENT_DATE)');
    $stmt->execute([$user_id]);
    $shopping_list_id = $pdo->lastInsertId();

    // Add selected products to shopping list
    if (isset($_POST['products'])) {
        foreach ($_POST['products'] as $product_id) {
            $stmt = $pdo->prepare('INSERT INTO ShoppingListItems (shopping_list_id, product_id, purchased) VALUES (?, ?, FALSE)');
            $stmt->execute([$shopping_list_id, $product_id]);
        }
    }

    // Add new products if any
    if (isset($_POST['new_products'])) {
        $new_products = explode(',', $_POST['new_products']);
        foreach ($new_products as $name) {
            $name = trim($name);
            if ($name != '') {
                // Check if product already exists
                $stmt = $pdo->prepare('SELECT id FROM Products WHERE user_id = ? AND name = ?');
                $stmt->execute([$user_id, $name]);
                $product = $stmt->fetch();
                if ($product) {
                    $product_id = $product['id'];
                } else {
                    $stmt = $pdo->prepare('INSERT INTO Products (user_id, name) VALUES (?, ?)');
                    $stmt->execute([$user_id, $name]);
                    $product_id = $pdo->lastInsertId();
                }
                // Add to shopping list
                $stmt = $pdo->prepare('INSERT INTO ShoppingListItems (shopping_list_id, product_id, purchased) VALUES (?, ?, FALSE)');
                $stmt->execute([$shopping_list_id, $product_id]);
            }
        }
    }

    header('Location: confirm.php');
    exit;
}
?>

<h1>Create Shopping List</h1>
<form method="post">
    <h2>All Products</h2>
    <?php foreach ($all_products as $product): ?>
        <label><input type="checkbox" name="products[]" value="<?= $product['id'] ?>"> <?= htmlspecialchars($product['name']) ?></label><br>
    <?php endforeach; ?>

    <h2>Add New Products</h2>
    <input type="text" name="new_products" placeholder="Enter new products, separated by commas">
    <button type="submit">Save Shopping List</button>
</form>
<?php include 'footer.php'; ?>
EOF

# Create confirm.php
cat << 'EOF' > confirm.php
<?php
include 'database.php';
include 'header.php';

$user_id = $_SESSION['user_id'];

// Find the most recent unconfirmed shopping list
$stmt = $pdo->prepare('SELECT id FROM ShoppingLists WHERE user_id = ? AND confirmed_at IS NULL ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$user_id]);
$shopping_list = $stmt->fetch();
if (!$shopping_list) {
    echo 'No unconfirmed shopping list found.';
    exit;
}
$shopping_list_id = $shopping_list['id'];

// Get shopping list items
$stmt = $pdo->prepare('SELECT sli.id, p.name, sli.purchased FROM ShoppingListItems sli JOIN Products p ON sli.product_id = p.id WHERE sli.shopping_list_id = ?');
$stmt->execute([$shopping_list_id]);
$items = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update purchased status for existing items
    if (isset($_POST['purchased'])) {
        $purchased_ids = $_POST['purchased'];
        $stmt = $pdo->prepare('UPDATE ShoppingListItems SET purchased = TRUE WHERE id = ?');
        foreach ($purchased_ids as $id) {
            $stmt->execute([$id]);
        }
    }

    // Add new products
    if (isset($_POST['new_products'])) {
        $new_products = explode(',', $_POST['new_products']);
        foreach ($new_products as $name) {
            $name = trim($name);
            if ($name != '') {
                // Check if product exists
                $stmt = $pdo->prepare('SELECT id FROM Products WHERE user_id = ? AND name = ?');
                $stmt->execute([$user_id, $name]);
                $product = $stmt->fetch();
                if ($product) {
                    $product_id = $product['id'];
                } else {
                    $stmt = $pdo->prepare('INSERT INTO Products (user_id, name) VALUES (?, ?)');
                    $stmt->execute([$user_id, $name]);
                    $product_id = $pdo->lastInsertId();
                }
                // Add to shopping list with purchased = true
                $stmt = $pdo->prepare('INSERT INTO ShoppingListItems (shopping_list_id, product_id, purchased) VALUES (?, ?, TRUE)');
                $stmt->execute([$shopping_list_id, $product_id]);
            }
        }
    }

    // Confirm the shopping list
    $stmt = $pdo->prepare('UPDATE ShoppingLists SET confirmed_at = CURRENT_DATE WHERE id = ?');
    $stmt->execute([$shopping_list_id]);

    // Insert purchases for purchased items
    $stmt = $pdo->prepare('SELECT product_id FROM ShoppingListItems WHERE shopping_list_id = ? AND purchased = TRUE');
    $stmt->execute([$shopping_list_id]);
    $purchased_products = $stmt->fetchAll();
    foreach ($purchased_products as $product) {
        $stmt = $pdo->prepare('INSERT INTO Purchases (user_id, product_id, purchase_date) VALUES (?, ?, CURRENT_DATE)');
        $stmt->execute([$user_id, $product['product_id']]);
    }

    header('Location: shopping_list.php');
    exit;
}
?>

<h1>Confirm Purchases</h1>
<form method="post">
    <h2>Shopping List Items</h2>
    <?php foreach ($items as $item): ?>
        <label><input type="checkbox" name="purchased[]" value="<?= $item['id'] ?>" <?= $item['purchased'] ? 'checked' : '' ?>> <?= htmlspecialchars($item['name']) ?></label><br>
    <?php endforeach; ?>

    <h2>Add Impulse Buys</h2>
    <input type="text" name="new_products" placeholder="Enter new products, separated by commas">
    <button type="submit">Confirm Purchases</button>
</form>
<?php include 'footer.php'; ?>
EOF

# Create products.php
cat << 'EOF' > products.php
<?php
include 'database.php';
include 'header.php';

$user_id = $_SESSION['user_id'];

// Get all products
$stmt = $pdo->prepare('SELECT id, name FROM Products WHERE user_id = ?');
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $stmt = $pdo->prepare('INSERT INTO Products (user_id, name) VALUES (?, ?)');
        $stmt->execute([$user_id, $name]);
    } elseif (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        $stmt = $pdo->prepare('DELETE FROM Products WHERE id = ? AND user_id = ?');
        $stmt->execute([$product_id, $user_id]);
    }
    header('Location: products.php');
    exit;
}
?>

<h1>Manage Products</h1>
<h2>Existing Products</h2>
<ul>
<?php foreach ($products as $product): ?>
    <li><?= htmlspecialchars($product['name']) ?> <form method="post" style="display:inline;"><input type="hidden" name="product_id" value="<?= $product['id'] ?>"><button name="delete_product">Delete</button></form></li>
<?php endforeach; ?>
</ul>

<h2>Add New Product</h2>
<form method="post">
    <input type="text" name="name" required>
    <button name="add_product">Add</button>
</form>
<?php include 'footer.php'; ?>
EOF

# Create logout.php
cat << 'EOF' > logout.php
<?php
session_start();
session_destroy();
header('Location: login.php');
exit;
EOF

echo "Project setup complete. Run 'docker-compose up --build' to start the application."