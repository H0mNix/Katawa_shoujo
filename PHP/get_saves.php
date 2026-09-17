<?php  // Code appelé par saves.js
session_start();
header("Content-Type: application/json; charset=utf-8");

$PDO = include 'connexionBD.php';
if (!$PDO)
{
    echo json_encode([]);
    exit;
}

// Récupération de l'utilisateur
$userId = $_SESSION['userid'] ?? null;
if (!$userId)
{
    echo json_encode([]);
    exit;
}

// Récupération de ses sauvegardes
$stmt = $PDO->prepare("SELECT save_number, save_name, save_date
                       FROM saves WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$saves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remplissage des 5 slots (vide si pas de sauvegarde)
$slots = [];
for ($i = 1; $i <= 5; $i++)
{
    $slot = array_filter($saves, fn($s) => $s['save_number'] == $i);
    if ($slot)
    {
        $s = array_values($slot)[0];
        $slots[$i] = [
            'name' => $s['save_name'],
            'date' => date("Y/m/d H:i:s", $s['save_date'])
        ];
    }
    else
    {
        $slots[$i] = ['name' => 'Vide', 'date' => ''];
    }
}

// Retour du JSON
echo json_encode($slots);
?>