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
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur ou slot invalide']);
    exit;
}

// Récupération du nom de la sauvegarde
$saveName = trim($_POST['save_name'] ?? "");
$saveName = mb_substr($saveName, 0, 20);

// Nom par défaut s'il est vide
if ($saveName === "")
{
    $saveName = "Sauvegarde $saveNumber";
}

// Sérialisation des données de session
if(!empty($_POST['current_bg']))
{
    $_SESSION['current_bg'] = $_POST['current_bg'];
}
$saveData = serialize($_SESSION);
$timestamp = time();

// Vérification de si la sauvegarde existe
$stmt = $PDO->prepare("SELECT save_id FROM saves
                       WHERE user_id = :user_id
                       AND save_number = :save_number");
$stmt->execute([':user_id' => $userId, ':save_number' => $saveNumber]);
$existing = $stmt->fetch();

// Si c'est le cas, on l'écrase
if ($existing)
{
    $stmt = $PDO->prepare("UPDATE saves
                           SET save_name = :save_name, save_date = :save_date, save_data = :save_data
                           WHERE save_id = :save_id");
    $stmt->execute([
        ':save_name' => $saveName,
        ':save_date' => $timestamp,
        ':save_data' => $saveData,
        ':save_id'   => $existing['save_id']
    ]);

    // Retour du JSON
    echo json_encode(['status' => 'ok', 'message' => "Sauvegarde $saveNumber écrasée"]);
}
else // Sinon, on en crée une nouvelle
{
    $stmt = $PDO->prepare("INSERT INTO saves (user_id, save_number, save_name, save_date, save_data)
                           VALUES (:user_id, :save_number, :save_name, :save_date, :save_data)");
    $stmt->execute([
        ':user_id'  => $userId,
        ':save_number'  => $saveNumber,
        ':save_name' => $saveName,
        ':save_date' => $timestamp,
        ':save_data' => $saveData
    ]);

    // Retour du JSON
    echo json_encode(['status' => 'ok', 'message' => "Sauvegarde $saveNumber créée"]);
}
?>