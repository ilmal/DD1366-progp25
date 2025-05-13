<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION["user_id"];
$db = getDb();

// Felmeddelande för validering
$errorMessage = '';
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        $item_name = trim($_POST["item_name"]);
        
        // Validera input
        if (empty($item_name)) {
            $errorMessage = "Varunamn kan inte vara tomt.";
        } else {
            // Lägg till vara i databasen
            if (addItemToDatabase($user_id, $item_name)) {
                $successMessage = "Varan har lagts till!";
            } else {
                $errorMessage = "Ett fel uppstod när varan skulle läggas till.";
            }
        }
    } elseif (isset($_POST['delete'])) {
        $item_id = $_POST["item_id"];
        if (removeItemFromDatabase($user_id, $item_id)) {
            $successMessage = "Varan har tagits bort!";
        } else {
            $errorMessage = "Ett fel uppstod när varan skulle tas bort.";
        }
    } elseif (isset($_POST['record_purchase'])) {
        $item_id = $_POST["item_id"];
        if (recordPurchase($item_id)) {
            $successMessage = "Inköpet har registrerats!";
        } else {
            $errorMessage = "Ett fel uppstod när inköpet skulle registreras.";
        }
    } elseif (isset($_POST['discontinue'])) {
        $item_id = $_POST["item_id"];
        if (markItemAsDiscontinued($user_id, $item_id)) {
            $successMessage = "Varan har markerats som utgången!";
        } else {
            $errorMessage = "Ett fel uppstod när varan skulle markeras som utgången.";
        }
    } elseif (isset($_POST['restore'])) {
        $item_id = $_POST["item_id"];
        if (restoreDiscontinuedItem($user_id, $item_id)) {
            $successMessage = "Varan har återställts!";
        } else {
            $errorMessage = "Ett fel uppstod när varan skulle återställas.";
        }
    }
}

// Hämta alla varor för användaren
$items = getAllItemsForUser($user_id);
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Hantera Varor</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .card { margin-top: 20px; }
        .alert { margin-top: 10px; }
        .btn { margin-right: 5px; }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.25rem;
        }
        .info-icon {
            font-size: 1rem;
            color: #17a2b8;
            cursor: pointer;
            margin-left: 5px;
        }
        .item-details {
            margin-top: 5px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .purchase-history {
            margin-top: 10px;
            font-size: 0.85rem;
            color: #6c757d;
            border-left: 3px solid #17a2b8;
            padding-left: 10px;
            display: none;
        }
        .purchase-dates {
            margin-top: 5px;
        }
        .badge-interval {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mt-4">Hantera dina varor</h2>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Lägg till ny vara</h5>
            </div>
            <div class="card-body">
                <form method="post" class="form-row">
                    <div class="col-md-8 mb-3">
                        <input type="text" name="item_name" class="form-control" placeholder="Namn på vara" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="submit" name="add" class="btn btn-primary">Lägg till vara</button>
                        <span class="info-icon" data-toggle="tooltip" title="Förbrukningsintervallet beräknas automatiskt baserat på dina inköpsmönster">?</span>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Dina varor</h5>
                <small class="text-muted">Förbrukningsintervall beräknas automatiskt baserat på dina inköpsmönster</small>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <div class="alert alert-info">Du har inga varor i din databas. Lägg till varor ovan!</div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($items as $item): ?>
                            <li class="list-group-item">
                                <div class="item-container">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        <?php if ($item['discontinued']): ?>
                                            <span class="badge badge-danger ml-2">Utgången</span>
                                        <?php endif; ?>
                                        <div class="item-details">
                                            <?php if ($item['avg_interval']): ?>
                                                <span class="badge badge-interval">Beräknat förbrukningsintervall: <?php echo htmlspecialchars($item['avg_interval']); ?> dagar</span>
                                            <?php elseif ($item['purchase_count'] == 1): ?>
                                                <span class="badge badge-secondary">Köpt endast en gång - kan inte beräkna förbrukningsintervall än</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Aldrig köpt - inget förbrukningsintervall</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($item['last_purchase']): ?>
                                                <br>Senast köpt: <?php echo htmlspecialchars($item['last_purchase']); ?>
                                            <?php else: ?>
                                                <br>Aldrig köpt tidigare
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($item['purchase_dates'])): ?>
                                            <a href="#" class="toggle-history" data-item-id="<?php echo $item['item_id']; ?>">Visa/dölj inköpshistorik</a>
                                            <div class="purchase-history" id="history-<?php echo $item['item_id']; ?>">
                                                <div>Inköpsdatum (<?php echo count($item['purchase_dates']); ?> st):</div>
                                                <div class="purchase-dates">
                                                    <?php echo implode(', ', array_map(function($date) { 
                                                        return date('Y-m-d', strtotime($date)); 
                                                    }, $item['purchase_dates'])); ?>
                                                </div>
                                                
                                                <?php if (!empty($item['intervals'])): ?>
                                                    <div class="mt-2">Intervall mellan inköp (dagar):</div>
                                                    <div><?php echo implode(', ', $item['intervals']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <button type="submit" name="record_purchase" class="btn btn-success btn-sm">Registrera inköp</button>
                                        </form>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Är du säker på att du vill ta bort denna vara?')">Ta bort</button>
                                        </form>
                                        <?php if (!$item['discontinued']): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" name="discontinue" class="btn btn-warning btn-sm">Markera som utgången</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" name="restore" class="btn btn-info btn-sm">Återställ</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="menu.php" class="btn btn-secondary">Tillbaka till menyn</a>
            <a href="logout.php" class="btn btn-warning float-right">Logga ut</a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
            
            // Toggle purchase history visibility
            $('.toggle-history').click(function(e) {
                e.preventDefault();
                const itemId = $(this).data('item-id');
                $('#history-' + itemId).toggle();
            });
        });
    </script>
</body>
</html>