<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];
$db = getDb();

// Hämta list-id från GET eller SESSION
$list_id = isset($_GET['list_id']) ? $_GET['list_id'] : (isset($_SESSION['selected_list_id']) ? $_SESSION['selected_list_id'] : null);
if (!$list_id) {
    // Redirect to shopping_lists.php if no list is selected, to allow user to pick or create one.
    header("Location: shopping_lists.php");
    exit();
}
$_SESSION['selected_list_id'] = $list_id; // Ensure selected_list_id is in session for consistency

// Hantera POST (lägg till/ta bort)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_item']) && !empty($_POST['item_id'])) {
        $item_id = $_POST["item_id"];
        // Check if item is already in the list to prevent duplicates (optional, depends on desired behavior)
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM shopping_list_items WHERE list_id = :list_id AND item_id = :item_id");
        $stmt_check->execute(['list_id' => $list_id, 'item_id' => $item_id]);
        if ($stmt_check->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO shopping_list_items (list_id, item_id) VALUES (:list_id, :item_id)");
            $stmt->execute(['list_id' => $list_id, 'item_id' => $item_id]);
        }
        header("Location: generate_shopping_list.php?list_id=$list_id");
        exit();
    } elseif (isset($_POST['remove_item']) && !empty($_POST['list_item_id'])) {
        $list_item_id = $_POST["list_item_id"];
        $stmt = $db->prepare("DELETE FROM shopping_list_items WHERE list_item_id = :list_item_id AND list_id = :list_id"); // Added list_id for security
        $stmt->execute(['list_item_id' => $list_item_id, 'list_id' => $list_id]);
        header("Location: generate_shopping_list.php?list_id=$list_id");
        exit();
    } elseif (isset($_POST['save'])) {
        header("Location: menu.php"); // "Save" here means done editing, go to menu
        exit();
    }
}

// Hämta lista och items
$list = getShoppingListById($user_id, $list_id);
$list_items = $list ? $list['items'] : [];
$current_list_item_ids = array_column($list_items, 'item_id');

// Hämta föreslagna varor
$suggested_items_raw = getSuggestedItems($user_id, $list_id);
$suggestions_to_display = [];
foreach ($suggested_items_raw as $suggested_item) {
    if (!in_array($suggested_item['item_id'], $current_list_item_ids)) {
        $suggestions_to_display[] = $suggested_item;
    }
}

// Hämta tillgängliga varor för manuell tilläggning (inte redan i listan eller bland förslagen som visas)
$all_available_items = getAvailableItems($user_id, $list_id); // Gets items not in current list
$suggested_ids_to_display = array_column($suggestions_to_display, 'item_id');
$available_for_dropdown = [];
foreach ($all_available_items as $avail_item) {
    if (!in_array($avail_item['item_id'], $suggested_ids_to_display)) {
        $available_for_dropdown[] = $avail_item;
    }
}

$list_name_obj = $db->prepare("SELECT name, created_date FROM shopping_lists WHERE list_id = :list_id AND user_id = :user_id");
$list_name_obj->execute(['list_id' => $list_id, 'user_id' => $user_id]);
$list_info = $list_name_obj->fetch(PDO::FETCH_ASSOC);
$display_list_name = "";
if ($list_info) {
    $display_list_name = !empty($list_info['name']) ? htmlspecialchars($list_info['name']) : "Inköpslista skapad " . date('Y-m-d H:i', strtotime($list_info['created_date']));
}

?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Skapa Inköpslista - <?php echo $display_list_name; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .card { margin-top: 20px; }
        .alert { margin-top: 10px; }
        .btn { margin-right: 5px; margin-bottom: 5px; }
        .list-group-item { display: flex; justify-content: space-between; align-items: center; }
        .suggestions-card { margin-top: 30px; }
        .item-reason {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 3px;
        }
        .suggestion-badge {
            background-color: #17a2b8;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }
        .purchase-history {
            margin-top: 5px;
            font-size: 0.85rem;
            color: #6c757d;
            border-left: 3px solid #17a2b8;
            padding-left: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mt-4">Din Inköpslista: <?php echo $display_list_name; ?></h2>

        <!-- Current List Items -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Nuvarande varor i listan</h5>
                <?php if (empty($list_items)): ?>
                    <div class="alert alert-info" role="alert">
                        Din lista är tom. Du kan lägga till varor från förslagen nedan eller manuellt.
                    </div>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($list_items as $item): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                                <form method="post" action="generate_shopping_list.php?list_id=<?php echo $list_id; ?>" style="display:inline;">
                                    <input type="hidden" name="list_item_id" value="<?php echo $item['list_item_id']; ?>">
                                    <button type="submit" name="remove_item" class="btn btn-danger btn-sm">Ta bort</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Suggested Items -->
        <?php if (!empty($suggestions_to_display)): ?>
        <div class="card suggestions-card">
            <div class="card-body">
                <h5 class="card-title">Föreslagna varor</h5>
                <p class="card-text">Baserat på din inköpshistorik och automatiskt beräknade förbrukningsintervaller.</p>
                <ul class="list-group mb-3">
                    <?php foreach ($suggestions_to_display as $item): ?>
                        <li class="list-group-item">
                            <div>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <?php if (isset($item['last_purchase']) && $item['last_purchase']): ?>
                                    <small class="text-muted">(Senast köpt: <?php echo date("Y-m-d", strtotime($item['last_purchase'])); ?>)</small>
                                <?php else: ?>
                                    <small class="text-muted">(Aldrig köpt)</small>
                                <?php endif; ?>
                                
                                <div class="item-reason mt-1">
                                    <?php if (isset($item['avg_interval'])): ?>
                                        <span class="badge badge-info">Beräknat förbrukningsintervall: <?php echo htmlspecialchars($item['avg_interval']); ?> dagar</span> <br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['reason']); ?>
                                    <?php if (isset($item['days_since_last'])): ?>
                                        <br>Det har gått <?php echo htmlspecialchars($item['days_since_last']); ?> dagar sedan senaste inköp
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($item['interval_detail']) && !empty($item['interval_detail'])): ?>
                                    <a href="#" class="toggle-history small" data-item-id="<?php echo $item['item_id']; ?>">Visa/dölj inköpsdetaljer</a>
                                    <div class="purchase-history" id="history-<?php echo $item['item_id']; ?>">
                                        <pre class="mb-0 small"><?php echo htmlspecialchars($item['interval_detail']); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="generate_shopping_list.php?list_id=<?php echo $list_id; ?>" style="margin-left: auto;">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <button type="submit" name="add_item" class="btn btn-info btn-sm">Lägg till i lista</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Manually Add Other Items -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Lägg till andra varor manuellt</h5>
                <?php if (empty($available_for_dropdown) && empty($suggestions_to_display) && empty($list_items) && !getAllItemsForUser($user_id)): // getAllItemsForUser would be a new function to check if there are any items at all for the user ?>
                     <div class="alert alert-warning" role="alert">
                        Det finns inga varor i din varudatabas. <a href="modify_db.php" class="alert-link">Lägg till varor i databasen först</a>.
                    </div>
                <?php elseif (!empty($available_for_dropdown)): ?>
                    <form method="post" action="generate_shopping_list.php?list_id=<?php echo $list_id; ?>" class="form-inline mb-3">
                        <select name="item_id" class="form-control mr-2">
                            <option value="">Välj vara...</option>
                            <?php foreach ($available_for_dropdown as $item): ?>
                                <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="add_item" class="btn btn-primary">Lägg till manuellt</button>
                    </form>
                <?php else: ?>
                     <div class="alert alert-info" role="alert">
                        Alla tillgängliga varor finns antingen i din lista eller bland förslagen. Du kan <a href="modify_db.php" class="alert-link">lägga till nya typer av varor</a> i din databas.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <hr class="mt-4 mb-3">
        <form method="post" action="generate_shopping_list.php?list_id=<?php echo $list_id; ?>" style="display:inline-block;">
            <button type="submit" name="save" class="btn btn-success">Klar med listan (Till Menyn)</button>
        </form>
        <a href="shopping_lists.php" class="btn btn-info">Byt/Skapa ny lista</a>
        <a href="menu.php" class="btn btn-secondary">Avbryt (Till Menyn)</a>
        <a href="logout.php" class="btn btn-warning float-right">Logga ut</a>

    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle purchase history visibility
        document.querySelectorAll('.toggle-history').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.getAttribute('data-item-id');
                const history = document.getElementById('history-' + itemId);
                if (history) {
                    history.style.display = history.style.display === 'block' ? 'none' : 'block';
                }
            });
        });
    });
    </script>
</body>
</html>