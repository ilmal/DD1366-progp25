<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}

// Redirect to the main shopping list page since multiple lists are no longer supported
header("Location: index.php");
exit();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Mina Inköpslistor</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .list-group-item {
            position: relative;
        }
        .list-actions {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .rename-form {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Mina Inköpslistor</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Mina listor</h5>
        </div>
        <div class="card-body">
            <?php if (empty($lists)): ?>
                <div class="alert alert-info">Du har inga inköpslistor än. Skapa en nedan!</div>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($lists as $list): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($list['display_name']); ?></span>
                                <div>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="select_list" value="<?php echo $list['list_id']; ?>">
                                        <button type="submit" class="btn btn-info btn-sm">Redigera lista</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="select_list_for_purchase" value="<?php echo $list['list_id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Handla från denna lista</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary btn-sm toggle-rename" data-list-id="<?php echo $list['list_id']; ?>">Byt namn</button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Är du säker på att du vill ta bort denna lista?');">
                                        <input type="hidden" name="list_id" value="<?php echo $list['list_id']; ?>">
                                        <button type="submit" name="delete_list" class="btn btn-danger btn-sm">Ta bort</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Rename form (hidden by default) -->
                            <form method="post" class="rename-form" id="rename-form-<?php echo $list['list_id']; ?>">
                                <div class="input-group">
                                    <input type="hidden" name="list_id" value="<?php echo $list['list_id']; ?>">
                                    <input type="text" name="new_name" class="form-control" placeholder="Nytt namn" value="<?php echo htmlspecialchars($list['name'] ?? ''); ?>" required>
                                    <div class="input-group-append">
                                        <button type="submit" name="rename_list" class="btn btn-outline-primary">Spara</button>
                                        <button type="button" class="btn btn-outline-secondary cancel-rename" data-list-id="<?php echo $list['list_id']; ?>">Avbryt</button>
                                    </div>
                                </div>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Skapa ny lista</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <label for="new_list_name">Namn på ny inköpslista (valfritt)</label>
                    <input type="text" name="new_list_name" id="new_list_name" class="form-control" placeholder="Lämna tomt för autogenererat namn">
                </div>
                <button type="submit" class="btn btn-primary">Skapa ny lista</button>
            </form>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="menu.php" class="btn btn-secondary">Tillbaka till Meny</a>
        <a href="logout.php" class="btn btn-warning float-right">Logga ut</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle rename form visibility
    document.querySelectorAll('.toggle-rename').forEach(button => {
        button.addEventListener('click', function() {
            const listId = this.getAttribute('data-list-id');
            const form = document.getElementById('rename-form-' + listId);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        });
    });
    
    // Cancel rename
    document.querySelectorAll('.cancel-rename').forEach(button => {
        button.addEventListener('click', function() {
            const listId = this.getAttribute('data-list-id');
            document.getElementById('rename-form-' + listId).style.display = 'none';
        });
    });
});
</script>
</body>
</html>
