<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];
$loggedInUser = $_SESSION["logged_in_user"];

// Hantera session för inköpslistan
if (!isset($_SESSION['shopping_list'])) {
    // Första gången: föreslå varor
    $suggested = getSuggestedItems($user_id);
    $_SESSION['shopping_list'] = array_map(function($item) { return $item['item_id']; }, $suggested);
}

// Hantera POST: lägg till/ta bort varor
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_item_id'])) {
        $add_id = (int)$_POST['add_item_id'];
        if (!in_array($add_id, $_SESSION['shopping_list'])) {
            $_SESSION['shopping_list'][] = $add_id;
        }
    }
    if (isset($_POST['remove_item_id'])) {
        $remove_id = (int)$_POST['remove_item_id'];
        $_SESSION['shopping_list'] = array_values(array_diff($_SESSION['shopping_list'], [$remove_id]));
    }
    if (isset($_POST['save_list'])) {
        header('Location: confirm_purchases.php');
        exit();
    }
}

// Hämta varor på listan
$list_items = [];
if (!empty($_SESSION['shopping_list'])) {
    foreach ($_SESSION['shopping_list'] as $item_id) {
        $item = getItemById($user_id, $item_id);
        if ($item && !$item['discontinued']) {
            $list_items[] = $item;
        }
    }
}

// Hämta tillgängliga varor att lägga till
$all_items = getAllItemsForUser($user_id);
$available_items = array_filter($all_items, function($item) {
    return !$item['discontinued'];
});
// Filtrera bort de som redan är på listan
$available_items = array_filter($available_items, function($item) {
    return !in_array($item['item_id'], $_SESSION['shopping_list']);
});
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Min Inköpslista</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .card { margin-top: 40px; }
        .list-group-item { font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Min Inköpslista</h2>
                        <div class="alert alert-info text-center">Välkommen, <?php echo htmlspecialchars($loggedInUser); ?>! <a href="logout.php" class="btn btn-sm btn-outline-secondary ml-2">Logga ut</a></div>
                        <form method="post" class="mb-3">
                            <div class="form-row align-items-end">
                                <div class="col-md-8">
                                    <label for="add_item_id">Lägg till vara från databasen:</label>
                                    <select class="form-control" name="add_item_id" id="add_item_id">
                                        <?php foreach ($available_items as $item): ?>
                                            <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary btn-block">Lägg till vara</button>
                                </div>
                            </div>
                        </form>
                        <h5>Varor på din inköpslista:</h5>
                        <?php if (empty($list_items)): ?>
                            <div class="alert alert-warning text-center">Din inköpslista är tom.</div>
                        <?php else: ?>
                            <ul class="list-group mb-3">
                                <?php foreach ($list_items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="remove_item_id" value="<?php echo $item['item_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Ta bort</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <form method="post">
                            <button type="submit" name="save_list" class="btn btn-success btn-lg btn-block">Spara inköpslista &amp; gå till bekräftelse</button>
                        </form>
                        <a href="modify_db.php" class="btn btn-secondary mt-3">Hantera varudatabasen</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>