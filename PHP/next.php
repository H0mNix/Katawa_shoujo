<?php  // Code appelé par app.js et saves.js
session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

$PDO = require_once __DIR__ . '/connexionBD.php';

// Chemin pour les images
const IMG_DIR = 'AJAX_img/';

// Couleur par défaut (pour les dialogues)
const DEFAULT_COLOR = "#FFFFFF";

// Code pour voir si on rejoue une sauvegarde (next.php est appelé avec ?replay=1)
$isReplay = isset($_GET['replay']) && $_GET['replay'] == '1';

// FONCTION 1 : Récupération de la prochaine ligne de seqtext
function SelectNextRow(PDO $PDO, int $seqid, int $seqserial)
{
   $sql = "SELECT seqserial, type, data, elid
                FROM seqtext
                WHERE seqid = ? AND seqserial >= ?
                ORDER BY seqserial ASC
                LIMIT 1;";
    $stmt = $PDO->prepare($sql);
    $stmt->execute([$seqid, $seqserial]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// FONCTION 2 : Préparation du dialogue
function BuildDialogue(PDO $PDO, array $row)
{
    // Nom et couleur par défaut
    $name = '';
    $color = DEFAULT_COLOR;

    // Si le personnage existe, on récupère son nom et sa couleur
    if(!empty($row["elid"]))
    {
        if($char = SelectCharacter($PDO, $row["elid"]))
        {
            $name = $char["name"] ?? '';
            $color = $char["color"] ?? DEFAULT_COLOR;
        }
    }

    return[$color, $name, $row['data']];
}

// Récupération nom et couleur d'un personnage à partir de son cid
function SelectCharacter (PDO $PDO, int $cid)
{
    $stmt = $PDO->prepare("SELECT name, color FROM character WHERE cid = ?");
    $stmt->execute([$cid]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// FONCTION 3 : Préparation des images
function GetImage(PDO $PDO, array $row)
{
    $sql = "SELECT s.elid, i.iid, s.type, s.pos, s.z, i.name, i.h, i.w
                FROM seqtext s RIGHT OUTER JOIN image i ON s.elid = i.iid
                WHERE s.elid = ? AND s.type BETWEEN 2 AND 4;";
    $stmt = $PDO->prepare($sql);
    $stmt->execute([$row["elid"]]);
    $image = $stmt->fetch();

    $name = $image["name"];
    $pos = $image["pos"];
    $z = $image["z"];
    $h = $image["h"];
    $w = $image["w"];
    $name = $image["name"];

    return [$name, $pos, $z, $h, $w, $name];
}

// CODE PRINCIPAL : Exécution de la séquence de la VN
while(true)
    {    
        // Variable pour voir si on est dans une séquence
        if(!isset($_SESSION["in_sequence"]))
        {
            $_SESSION["in_sequence"] = true;
        }
        // Si seqid n'existe pas, on initialise les compteurs de session
        if(!isset($_SESSION['seqid']))
        {
            $_SESSION['seqid'] = 1;
            $_SESSION['seqserial'] = 1;
        }
        
        $seqserial = (int)$_SESSION['seqserial'];
        $seqid = (int)$_SESSION['seqid'];

        // Replay : on lit la ligne précédente / Lecture normale : on lit seqserial
        $effectiveSerial = $isReplay ? max(1, $seqserial - 1) : $seqserial;
        
        // Récupération de la prochaine ligne (FONCTION 1)
        $row = SelectNextRow($PDO, $seqid, $effectiveSerial);
        
        // Si elle n'existe pas, on appelle l'interpreteur
        if(!$row)
        {
            // On est plus dans la séquence
            $_SESSION["in_sequence"] = false;
            // On récupère le type d'action via interpreteur.php (RunInterpreter)
            require_once __DIR__ . '/interpreteur.php';
            $result = RunInterpreter($PDO);

            // S'il s'agit d'une scène, on recommence while
            if($result['kind'] === 'scene')
            {
                // On récupère l'ID numérique correspondant au tag (le nom de la scène)
                $sql = "SELECT seqid FROM seqtag WHERE tag = ? AND type = 1";
                $stmt = $PDO->prepare($sql);
                $stmt->execute([$result['tag']]);
                $rowTag = $stmt->fetch(PDO::FETCH_ASSOC);

                if($rowTag)
                {
                    $_SESSION['seqid'] = $rowTag['seqid'];
                    // On remet le serial à 1 pour la nouvelle scène
                    $_SESSION['seqserial'] = 1;
                    $seqid = $_SESSION["seqid"]; 
                    $seqserial = 1; // On met à jour la variable locale pour le prochain tour du while
                }
                $_SESSION["in_sequence"] = true;
                continue;
            }

            // S'il s'agit d'un menu, on récupère ses données via interpreteur.php (GetMenuByTag)
            if($result['kind'] === 'menu')
            {
                $menu = GetMenuByTag($PDO, $result['tag']);

                if($menu === null)
                {
                    echo json_encode([
                        "type" => -1,
                        "error" => "Menu introuvable : ".$result['tag']
                    ]);
                }
                else
                {
                    echo json_encode($menu);
                }
                exit;
            }

            // S'il s'agit de la fin, on affiche le type de fin obtenu
            if($result['kind'] === 'end')
            {
                echo json_encode([
                    "type" => 5,
                    "texte" => "Fin du jeu : ".($result['name'] ?? "")
                ]);
                exit;
            }

            // Sinon, il s'agit d'une erreur
            echo json_encode([
                "type" => 5,
                "texte" => "Erreur interpréteur : ". ($result['message'] ?? "inconnue")
            ]);
            exit;
        }
        
        // S'il s'agit d'une lecture normale, on augmente seqserial de 1
        if (!$isReplay)
        {
            $_SESSION["seqserial"] = $row["seqserial"] + 1;
        }
        
        $type = $row["type"];
        
        // Gestion des différents types de ligne
        switch($type)
        {
            case 1 : // Dialogue 
                {
                    [$color, $name, $text] = BuildDialogue($PDO, $row);  // appel FONCTION 2
        
                    header("Content-type: application/json; charset=UTF-8");
                    echo json_encode([
                        "type" => 1,
                        "speaker" => $name,
                        "color" => $color,
                        "text" => $text
                    ]);
                    exit;
                }
            case 2 : // Image
                {
                    [$name, $pos, $z, $h, $w, $name] = GetImage($PDO, $row); // appel FONCTION 3           
                    
                    $safeName = ltrim($name, '/');
                    $url = IMG_DIR . $safeName;
        
                    $isBg = (strncasecmp($safeName, 'bg_', 3) === 0);
                    if($isBg)
                    {
                        // On stocke le background dans la session
                        $_SESSION['current_bg'] = $url;

                        echo json_encode([
                            "type" => 2,
                            "url" => $url,
                            "tag" => "BG"
                        ]);
                    }
                    else
                    {
                        header("Content-type: application/json; charset=UTF-8");
                        echo json_encode([
                            "type" => 3,
                            "tag" => "BG",
                            "url" => $url,
                            "xpos" => $pos,
                            "z" => $z,
                            "h" => $h,
                            "w" => $w]);
                    }
                    exit;
                }
            case 3 : // Sprite (move/show)
                {
                    [$name, $pos, $z, $h, $w] = GetImage($PDO, $row); // appel FONCTION 3
        
                    $safeName = ltrim($name, '/');
                    $url = IMG_DIR . $safeName;
                    header("Content-type: application/json; charset=UTF-8");
                    echo json_encode([
                        "type" => 3,
                        "tag" => $row["data"] ?? "",
                        "url" => $url,
                        "xpos" => $pos,
                        "z" => $z,
                        "h" => $h,
                        "w" => $w]);
                    exit;
                }
            case 4 : // Cacher sprite
                {
                    header("Content-type: application/json; charset=UTF-8");
                    echo json_encode([
                        "type" => 4,
                        "tag" => $row["data"] ?? ""]);
                    exit;
                }
            case 5 : // Texte centré
                {
                    header("Content-type: application/json; charset=UTF-8");
                    echo json_encode([
                        "type" => 5,
                        "texte" => $row["data"] ?? ""
                    ]);
                    exit;
                }
            case 6 : // HTML dans le texte
                {
                    header("Content-type: application/json; charset=UTF-8");
                    echo json_encode([
                        "type" => 6,
                        "html" => $row["data"] ?? ""
                    ]);
                    exit;
                }
        }
}
?>