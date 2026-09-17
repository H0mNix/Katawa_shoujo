<?php  // Code appelé par saves.js
session_start();
header("Content-Type: application/json; charset=utf-8");

$PDO = include 'connexionBD.php';
if (!$PDO)
{
    echo json_encode(['status' => 'error', 'message' => 'Impossible de se connecter à la bd']);
    exit;
}

// Récupération de l'id de l'utilisateur connecté et de ses slots de sauvegardes
$userId = $_SESSION['userid'] ?? null;
$saveNumber = (int)($_POST['save_number'] ?? 0);

// Validation des données
if (!$userId || $saveNumber < 1 || $saveNumber > 5)
{
    echo json_encode(['status'=>'error','message'=>'Utilisateur ou slot invalide']);
    exit;
}

// Récupération de la sauvegarde
$stmt = $PDO->prepare("SELECT save_data FROM saves WHERE user_id = :user_id AND save_number = :save_number");
$stmt->execute([':user_id'=>$userId, ':save_number'=>$saveNumber]);
$save = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$save)
{
    echo json_encode(['status'=>'error','message'=>'Sauvegarde vide']);
    exit;
}

// Restauration des données de session
$_SESSION = unserialize($save['save_data'], ['allowed_classes' => false]);

// Retour du JSON
//echo json_encode(['status'=>'ok','message'=>"Sauvegarde $saveNumber chargée"]);
echo json_encode([
    'status' => 'ok',
    'message' => "Sauvegarde $saveNumber chargée",
    'background' => $_SESSION['current_bg'] ?? 'AJAX_img/black.png'
]);
?>