<?php
session_start();
if (isset($_SESSION["logged_in_user"])) {
    header("Location: menu.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Registrera dig</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <link href="https://getbootstrap.com/docs/4.0/examples/signin/signin.css" rel="stylesheet" crossorigin="anonymous" />
    <style>
        .card { margin-top: 40px; }
        .btn { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <a href="index.php" class="btn btn-secondary btn-sm">Logga in</a>
                        </div>
                        <h2 class="card-title text-center mb-4">Registrera dig</h2>
                        <?php if (isset($_SESSION['message'])) { echo '<div class="alert alert-danger" role="alert">'.$_SESSION['message'].'</div>'; unset($_SESSION['message']); } ?>
                        <form method="post" action="register.php">
                            <div class="form-group">
                                <label for="username">Användarnamn</label>
                                <input type="text" id="username" name="username" class="form-control" placeholder="Användarnamn" required autofocus />
                            </div>
                            <div class="form-group">
                                <label for="password">Lösenord</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Lösenord" required />
                            </div>
                            <div class="form-group">
                                <label for="confirm">Bekräfta lösenord</label>
                                <input type="password" id="confirm" name="confirm" class="form-control" placeholder="Bekräfta lösenord" required />
                            </div>
                            <button class="btn btn-outline-primary btn-block" type="submit">Registrera</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="module" src="../public/js/index.js"></script>
</body>
</html>