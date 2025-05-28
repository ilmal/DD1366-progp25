<?php
// src/shopping_complete.php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inköp klara</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 50px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
        h1 {
            color: #28a745;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 0 10px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .actions {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        <h1>Inköpen är klara!</h1>
        <p>
            Tack för att du använder vår inköpslista! Dina inköp har registrerats och 
            kommer att användas för att förbättra framtida förslag baserat på dina 
            konsumtionsvanor.
        </p>
        <p>
            Nästa gång du skapar en inköpslista kommer systemet att föreslå produkter 
            baserat på när du brukar köpa dem.
        </p>
        
        <div class="actions">
            <a href="shopping_list.php" class="btn btn-success">Skapa ny inköpslista</a>
            <a href="manage_products.php" class="btn btn-primary">Hantera produkter</a>
        </div>
    </div>
</body>
</html>