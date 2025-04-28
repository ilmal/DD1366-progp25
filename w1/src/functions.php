<?php
//Här ska du lägga till flera funktioner 

//Observera att följande funktion är sårbar för sql-injection och behöver förbättras
function selectPwd($username){
    // Öppna SQLite-databasen
    $db = new SQLite3('../database/account_items.db');

    // Förbered SQL-frågan
    $sql = "SELECT password FROM users WHERE username = '".$username."'";

    // Utför frågan
    $result = $db->query($sql);

    // Hämta raden från resultatet
    $row = $result->fetchArray();

    // Stäng databasanslutningen
    $db->close();
    // Returnera resultatet (kan vara null om användarnamnet inte hittades)
    return $row;

}
?>


