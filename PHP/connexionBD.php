<?php
    // Connexion à la BD
    include_once("mesparametres.php");
    try
    {
        $PDO = new PDO(DSN, USER, PASS);
        return $PDO;
    }
    catch(PDOException $except)
    {
        echo "Échec de la connexion".$except->getMessage();
        return FALSE;
        exit();
    }
?>