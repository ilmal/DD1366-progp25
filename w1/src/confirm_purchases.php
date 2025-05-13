<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];

// Hantera AJAX-förfrågan för att spara checked state
if (isset($_POST['ajax']) && $_POST['ajax'] === 'save_checked') {
    $_SESSION['checked_items'] = isset($_POST['checked']) ? $_POST['checked'] : [];
    echo json_encode(['status' => 'ok']);
    exit();
}

// AJAX: markera som utgången
if (isset($_POST['ajax']) && $_POST['ajax'] === 'discontinue_item') {
    $item_id = (int)$_POST['item_id'];
    markItemAsDiscontinued($user_id, $item_id);
    // Ta bort från sessionens shopping_list
    if (isset($_SESSION['shopping_list'])) {
        $_SESSION['shopping_list'] = array_values(array_diff($_SESSION['shopping_list'], [$item_id]));
    }
    echo json_encode(['status' => 'ok']);
    exit();
}

// AJAX: ersätt vara
if (isset($_POST['ajax']) && $_POST['ajax'] === 'replace_item') {
    $original_id = (int)$_POST['original_id'];
    $replacement_id = (int)$_POST['replacement_id'];
    // Registrera båda som köpta
    recordPurchase($original_id);
    recordPurchase($replacement_id);
    markItemAsPurchased($user_id, $original_id);
    markItemAsPurchased($user_id, $replacement_id);
    // Spara ersättningsrelation
    addReplacement($user_id, $original_id, $replacement_id);
    echo json_encode(['status' => 'ok']);
    exit();
}

// PHP: AJAX för ersättning med ny vara (med köpt)
if (isset($_POST['ajax']) && $_POST['ajax'] === 'replace_item_text') {
    $original_id = (int)$_POST['original_id'];
    $replacement_name = trim($_POST['replacement_name']);
    $replacement_purchased = isset($_POST['replacement_purchased']) && $_POST['replacement_purchased'] === '1';
    if ($replacement_name !== '') {
        // Skapa ny vara om den inte finns
        $all_items = getAllItemsForUser($user_id);
        $exists = false;
        foreach ($all_items as $item) {
            if (strcasecmp($item['item_name'], $replacement_name) === 0) {
                $exists = $item['item_id'];
                break;
            }
        }
        if (!$exists) {
            addItemToDatabase($user_id, $replacement_name);
            $all_items = getAllItemsForUser($user_id);
            foreach ($all_items as $item) {
                if (strcasecmp($item['item_name'], $replacement_name) === 0) {
                    $exists = $item['item_id'];
                    break;
                }
            }
        }
        if ($exists) {
            recordPurchase($original_id);
            markItemAsPurchased($user_id, $original_id);
            // Ta bort original från sessionens shopping_list
            if (isset($_SESSION['shopping_list'])) {
                $_SESSION['shopping_list'] = array_values(array_diff($_SESSION['shopping_list'], [$original_id]));
            }
            if ($replacement_purchased) {
                recordPurchase($exists);
                markItemAsPurchased($user_id, $exists);
            }
            addReplacement($user_id, $original_id, $exists);
        }
    }
    echo json_encode(['status' => 'ok']);
    exit();
}

// Hämta användarens inköpslista (alla aktiva varor)
$shopping_list = getShoppingListItems($user_id);
$checked_items = isset($_SESSION['checked_items']) ? $_SESSION['checked_items'] : [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ajax'])) {
    if (isset($_POST['purchased']) && is_array($_POST['purchased'])) {
        foreach ($_POST['purchased'] as $item_id) {
            recordPurchase($item_id);
            markItemAsPurchased($user_id, $item_id);
            // Ta bort från sessionens shopping_list
            if (isset($_SESSION['shopping_list'])) {
                $_SESSION['shopping_list'] = array_values(array_diff($_SESSION['shopping_list'], [$item_id]));
            }
        }
        $successMessage = "Dina inköp har registrerats!";
        unset($_SESSION['checked_items']); // Töm sessionen efter bekräftelse
        $shopping_list = getShoppingListItems($user_id);
        $checked_items = [];
    }
    // Hantera utgångna varor
    if (isset($_POST['discontinued']) && is_array($_POST['discontinued'])) {
        foreach ($_POST['discontinued'] as $item_id) {
            markItemAsDiscontinued($user_id, $item_id);
            // Ta bort från sessionens shopping_list
            if (isset($_SESSION['shopping_list'])) {
                $_SESSION['shopping_list'] = array_values(array_diff($_SESSION['shopping_list'], [$item_id]));
            }
        }
    }
    // Hantera impulsköp
    if (!empty($_POST['impulse_buys'])) {
        $impulse_buys = array_map('trim', explode(',', $_POST['impulse_buys']));
        foreach ($impulse_buys as $item_name) {
            if (empty($item_name)) continue;
            // Lägg till varan om den inte finns
            $all_items = getAllItemsForUser($user_id);
            $exists = false;
            foreach ($all_items as $item) {
                if (strcasecmp($item['item_name'], $item_name) === 0) {
                    $exists = $item['item_id'];
                    break;
                }
            }
            if (!$exists) {
                addItemToDatabase($user_id, $item_name);
                // Hämta id på nya varan
                $all_items = getAllItemsForUser($user_id);
                foreach ($all_items as $item) {
                    if (strcasecmp($item['item_name'], $item_name) === 0) {
                        $exists = $item['item_id'];
                        break;
                    }
                }
            }
            if ($exists) {
                recordPurchase($exists);
            }
        }
        $successMessage = "Dina inköp har registrerats!";
    }
    // Hämta uppdaterad lista
    $shopping_list = getShoppingListItems($user_id);
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Bekräfta Inköp</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .card { margin-top: 40px; }
        .list-group-item { display: flex; align-items: center; }
        .form-check-input { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Bekräfta Inköp</h2>
                        <?php if (isset($successMessage)): ?>
                            <div class="alert alert-success text-center"><?php echo $successMessage; ?></div>
                        <?php endif; ?>
                        <?php if (empty($shopping_list)): ?>
                            <div class="alert alert-info text-center" role="alert">
                                Din inköpslista är tom. <a href="index.php" class="alert-link">Lägg till varor i inköpslistan</a> först.
                            </div>
                        <?php else: ?>
                            <form method="post" id="purchase-form">
                                <div class="mb-4">
                                    <h5>Markera varor som du har köpt:</h5>
                                    <ul class="list-group mb-3" id="shopping-list">
                                        <?php $all_items = getAllItemsForUser($user_id); ?>
                                        <?php foreach ($shopping_list as $item): ?>
                                            <li class="list-group-item flex-column align-items-start" data-item-id="<?php echo $item['item_id']; ?>" style="padding-bottom: 0.5rem;">
                                                <div class="d-flex align-items-center w-100">
                                                    <div class="form-check mr-3">
                                                        <input class="form-check-input purchase-checkbox" type="checkbox" name="purchased[]" value="<?php echo $item['item_id']; ?>" id="item-<?php echo $item['item_id']; ?>" <?php echo in_array($item['item_id'], $checked_items) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="item-<?php echo $item['item_id']; ?>">
                                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                                        </label>
                                                    </div>
                                                    <button type="button" class="btn btn-warning btn-sm ml-2 discontinue-btn">Markera som utgången</button>
                                                    <button type="button" class="btn btn-info btn-sm ml-2 replace-btn">Ersätt med ny vara…</button>
                                                </div>
                                                <div class="replace-form mt-3 p-3 bg-light border rounded" style="display:none;">
                                                    <form class="form-inline flex-wrap">
                                                        <div class="form-group mb-2 mr-2 w-100" style="max-width: 350px;">
                                                            <input type="text" class="form-control form-control-sm w-100 replacement-input" placeholder="Namn på ny vara">
                                                        </div>
                                                        <div class="form-group mb-2 mr-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input replacement-purchased" type="checkbox" id="replacement-purchased-<?php echo $item['item_id']; ?>">
                                                                <label class="form-check-label" for="replacement-purchased-<?php echo $item['item_id']; ?>">Markera som köpt</label>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-success btn-sm mb-2 confirm-replace">Bekräfta</button>
                                                        <button type="button" class="btn btn-secondary btn-sm mb-2 ml-1 cancel-replace">Avbryt</button>
                                                    </form>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="form-group">
                                    <h5>Impulsköp (varor som inte fanns på listan):</h5>
                                    <input type="text" name="impulse_buys" class="form-control" placeholder="Ange kommaseparerade varor, t.ex. Choklad, Tidning, Batterier">
                                    <small class="form-text text-muted">Dessa varor kommer att läggas till i din varudatabas om de inte redan finns där.</small>
                                </div>
                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-success btn-lg">Bekräfta inköp</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        <hr class="mt-4 mb-3">
                        <div class="text-center">
                            <a href="index.php" class="btn btn-info">Redigera inköpslista</a>
                            <a href="menu.php" class="btn btn-secondary">Tillbaka till menyn</a>
                            <a href="logout.php" class="btn btn-warning">Logga ut</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Spara checked state i session via AJAX
    document.querySelectorAll('.purchase-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const checked = Array.from(document.querySelectorAll('.purchase-checkbox:checked')).map(cb => cb.value);
            const formData = new FormData();
            formData.append('ajax', 'save_checked');
            checked.forEach(id => formData.append('checked[]', id));
            fetch('', { method: 'POST', body: formData });
        });
    });

    // Markera som utgången
    const list = document.getElementById('shopping-list');
    list && list.addEventListener('click', function(e) {
        if (e.target.classList.contains('discontinue-btn')) {
            const li = e.target.closest('li');
            const itemId = li.getAttribute('data-item-id');
            const formData = new FormData();
            formData.append('ajax', 'discontinue_item');
            formData.append('item_id', itemId);
            fetch('', { method: 'POST', body: formData })
                .then(() => { li.remove(); });
        }
        // Visa ersättningsformulär
        if (e.target.classList.contains('replace-btn')) {
            const li = e.target.closest('li');
            li.querySelector('.replace-form').style.display = 'block';
        }
        // Avbryt ersättning
        if (e.target.classList.contains('cancel-replace')) {
            e.preventDefault();
            const li = e.target.closest('li');
            li.querySelector('.replace-form').style.display = 'none';
        }
        // Bekräfta ersättning
        if (e.target.classList.contains('confirm-replace')) {
            e.preventDefault();
            const li = e.target.closest('li');
            const originalId = li.getAttribute('data-item-id');
            const input = li.querySelector('.replacement-input');
            const replacementName = input.value.trim();
            const purchased = li.querySelector('.replacement-purchased').checked ? '1' : '';
            if (!replacementName) return;
            const formData = new FormData();
            formData.append('ajax', 'replace_item_text');
            formData.append('original_id', originalId);
            formData.append('replacement_name', replacementName);
            formData.append('replacement_purchased', purchased);
            fetch('', { method: 'POST', body: formData })
                .then(() => { li.remove(); location.reload(); });
        }
    });
    </script>
</body>
</html>