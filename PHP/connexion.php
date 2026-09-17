<?php  // Code appelé par connexion.js
session_start();
$PDO = require_once __DIR__ . '/connexionBD.php';

header("Content-Type: application/json; charset=utf-8");

// Gestion de la validation du formulaire
if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
    jsonResponse(false, "Méthode invalide");
}
else
{
    $username = $_POST['username'] ?? "";
    $password = $_POST['mdp'] ?? "";

    $isValid = true;

    if (!validateUsername($username) || !validatePassword($password))
    {
        $isValid = false;
        jsonResponse(false, "Nom ou mot de passe incorrect");
    }

    if ($isValid)
    {
        unset($_SESSION['Form-span']);

        // Recherche utilisateur
        $stmt = $PDO->prepare("SELECT * FROM users WHERE identifiant = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user)
        {
            // Création utilisateur
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $PDO->prepare("INSERT INTO users (identifiant, motdepasse) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);

            $userId = $PDO->lastInsertId();
            $_SESSION["userid"] = $userId;
            $_SESSION["username"] = $username;
            jsonResponse(true);
        }

        // Vérification mot de passe
        if (!password_verify($password, $user["motdepasse"])) {
            jsonResponse(false, "Nom ou mot de passe incorrect");
        }

        $_SESSION["userid"] = $user["userid"];
        $_SESSION["username"] = $username;
        jsonResponse(true);
    }
}

// Fonctions pour les réponses json
function jsonResponse($success, $error = null)
{
    echo json_encode([
        "success" => $success,
        "error"   => $error
    ]);
    exit;
}

// Fonction de validation du pseudo
function validateUsername($username)
{
    return preg_match('/^[A-Za-z\d]{5,20}$/', $username);
}

// Fonction de validation du mot de passe
function validatePassword($password)
{
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,20}$/', $password);
}
?>

