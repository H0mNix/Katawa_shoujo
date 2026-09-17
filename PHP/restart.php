<?php  // Code appelé par app.js
session_start();

// Réinitialisation complète de l'état du jeu
$_SESSION['ip'] = 1;
$_SESSION['vars'] = [];
$_SESSION['seen_scenes'] = [];
$_SESSION["seqid"] = 1;
$_SESSION["seqserial"] = 1;

echo json_encode([
    "status" => "ok"
]);
?>
