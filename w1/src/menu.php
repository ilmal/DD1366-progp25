<?php
session_start();
require('functions.php');
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php");
    exit();
}
$loggedInUser = $_SESSION["logged_in_user"];
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Meny</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .card { margin-top: 40px; }
        .list-group-item { font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Meny</h2>
                        <div class="alert alert-info text-center">Välkommen, <?php echo htmlspecialchars($loggedInUser); ?>!</div>
                        <ul class="list-group mb-3">
                            <li class="list-group-item"><a href="shopping_lists.php">Mina Inköpslistor</a></li>
                            <li class="list-group-item"><a href="modify_db.php">Modifiera Varudatabasen</a></li>
                            <li class="list-group-item"><a href="confirm_purchases.php">Bekräfta Inköp</a></li>
                            <li class="list-group-item"><a href="logout.php">Logga ut</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>