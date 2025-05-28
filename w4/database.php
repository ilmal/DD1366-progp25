<?php
$pdo = new PDO('pgsql:host=db;dbname=shopping_list', 'user', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
