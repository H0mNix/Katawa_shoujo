<?php  // Code appelé par app.js
    session_start();
    header("Content-type: application/json; charset=UTF-8");

    // Récupération des données envoyées par app.js (HandleQCMChoice)
    $choice = isset($_POST['choice']) ? $_POST['choice'] : 0;
    $tag = $_POST['tag'] ?? null;

    // Vérification des paramètres
    if($choice < 1 || $choice > 4 || !$tag)
    {
        echo json_encode([
            "ok" => false,
            "error" => "Paramètres QCM invalides"
        ]);
        exit;
    }

    // Si valide, sauvegarde du choix dans la session
    $_SESSION['last_choice'] = $choice;
    $_SESSION['last_menu'] = $tag;

    echo json_encode([
        "ok" => true
    ]);
?>