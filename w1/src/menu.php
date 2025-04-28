<?php
session_start();
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php"); 
    exit();
}

// Om användaren är inloggad, hämta användarnamnet från sessionen
$loggedInUser = $_SESSION["logged_in_user"];
?>



<!DOCTYPE html>
<html lang="en">
  <head>
       <title>Menu page</title>
    <link
      rel="stylesheet"
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
      integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
      crossorigin="anonymous"
    />
    <link
      href="https://getbootstrap.com/docs/4.0/examples/signin/signin.css"
      rel="stylesheet"
      crossorigin="anonymous"
    />
  </head>
  <body>

<a href="logout.php">logga ut</a><br>
<h1> Menu</h1>
Välj vad du vill göra:<br>
<ol type="1">
  <li><a href="generate_shopping_list.php">skapa inköpslista</a>
  </li>
  <li><a href="modify_db.php">Modifiera databasen med varudatabasen</a></li>
  <li>...och även de andra valen man ska kunna göra enligt laborationsbeskrivning</li>
</ol>
  </body>
</html>
