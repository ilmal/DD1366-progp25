<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];

$list_id = $_GET['list_id'] ?? $_SESSION['selected_list_id'] ?? null;
if (!$list_id) {
    header("Location: shopping_lists.php");
    exit();
}

$shopping_list = getShoppingListItems($list_id);
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
                        
                        <?php if (empty($shopping_list)): ?>
                            <div class="alert alert-info text-center" role="alert">
                                Din valda inköpslista är tom. <a href="generate_shopping_list.php?list_id=<?php echo $list_id; ?>" class="alert-link">Lägg till varor i inköpslistan</a> först.
                            </div>
                        <?php else: ?>
                            <form method="post" action="process_confirm.php">
                                <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
                                <div class="mb-4">
                                    <h5>Markera varor som du har köpt:</h5>
                                    <ul class="list-group mb-3">
                                        <?php foreach ($shopping_list as $item): ?>
                                            <li class="list-group-item">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="purchased[]" value="<?php echo $item['list_item_id']; ?>" id="item-<?php echo $item['list_item_id']; ?>">
                                                    <label class="form-check-label" for="item-<?php echo $item['list_item_id']; ?>">
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </label>
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
                            <a href="generate_shopping_list.php?list_id=<?php echo $list_id; ?>" class="btn btn-info">Redigera inköpslista</a>
                            <a href="menu.php" class="btn btn-secondary">Tillbaka till menyn</a>
                            <a href="logout.php" class="btn btn-warning">Logga ut</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>