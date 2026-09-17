<?php  // Code appelé par app.js
    // Déconnexion du joueur
    session_start();

    // Remise à zéro des variables session
    $_SESSION = [
        "seqid" => 1,
        "seqserial" => 1,
        "ip" => 1,
        "vars" => [],
        "seen_scenes" => [],
        "last_choice" => 0,
        "last_menu" => null,
        "game_over" => false
    ];
    unset($_SESSION["username"], $_SESSION["password"]);

    // Retour à la page d'acceuil
    header("Location: ../index.html");
    exit;
?>
