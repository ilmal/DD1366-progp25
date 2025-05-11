<?php
session_start();
require('functions.php');

if (isset($_SESSION["logged_in_user"])) {
    header("Location: menu.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $stored_password_hash = selectPwd($username);

    if ($stored_password_hash && hash('sha3-512', $password) === $stored_password_hash) {
        $_SESSION["logged_in_user"] = $username;
        $_SESSION["user_id"] = getUserId($username);
        session_regenerate_id(true); // Prevent session fixation
        header("Location: menu.php");
        exit();
    } else {
        $errorMessage = "<div class='alert alert-danger' role='alert'>Fel användarnamn eller lösenord</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Logga in</title>
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
                            <a href="registration.php" class="btn btn-secondary btn-sm">Registrera</a>
                        </div>
                        <h2 class="card-title text-center mb-4">Logga in</h2>
                        <?php if (isset($errorMessage)) { echo $errorMessage; unset($errorMessage); } ?>
                        <form method="post" action="index.php">
                            <div class="form-group">
                                <label for="username">Användarnamn</label>
                                <input type="text" id="username" name="username" class="form-control" placeholder="Användarnamn" required autofocus />
                            </div>
                            <div class="form-group">
                                <label for="password">Lösenord</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Lösenord" required />
                            </div>
                            <button class="btn btn-primary btn-block" type="submit">Logga in</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="module" src="../public/js/index.js"></script>
</body>
</html>