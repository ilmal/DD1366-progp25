<?php
session_start();
require('functions.php');

if (isset($_SESSION["logged_in_user"])) {
    header("Location: menu.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Användarnamn och lösenord krävs.";
    } else {
        $db = getDb();
        $stmt = $db->prepare("SELECT user_id, username, password_hash FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && hash('sha3-512', $password) === $user['password_hash']) {
            $_SESSION['logged_in_user'] = $user['username'];
            $_SESSION['user_id'] = $user['user_id'];
            session_regenerate_id(true); // Prevent session fixation
            header("Location: menu.php");
            exit();
        } else {
            $error = "Felaktigt användarnamn eller lösenord.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <title>Logga in</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous" />
    <style>
        .card { margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Logga in</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="form-group">
                                <label for="username">Användarnamn</label>
                                <input type="text" id="username" name="username" class="form-control" placeholder="Användarnamn" required autofocus />
                            </div>
                            <div class="form-group">
                                <label for="password">Lösenord</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Lösenord" required />
                            </div>
                            <button class="btn btn-primary btn-block" type="submit">Logga in</button>
                            <div class="text-center mt-3">
                                <a href="registration.php" class="btn btn-secondary btn-sm">Registrera dig</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>