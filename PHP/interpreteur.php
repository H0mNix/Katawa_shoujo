<?php  // Code appelé par next.php
    // Initialisation des variables (nouvelle session)
    function InitInterpreterSession()
    {
        if (!isset($_SESSION['ip']))
        {
            $_SESSION['ip']          = 1;
            $_SESSION['vars']        = [];
            $_SESSION['seen_scenes'] = [];
        }   
    }

    // Récupération du scénario -> Fonction appelée par next.php
    function RunInterpreter(PDO $PDO)
    {
        InitInterpreterSession(); // Initialisation des variables

        while(true)
        {
            // Récupération de l'instruction actuelle
            $ip = $_SESSION['ip'];

            $sql = "SELECT id, type, name, param, next
                    FROM story
                    WHERE id = ?";
            $stmt = $PDO->prepare($sql);
            $stmt->execute([$ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $id = $row['id'];
            $type = $row['type'];
            $name = $row['name'];
            $param = $row['param'];
            $next = $row['next'];

            $defaultIp = $ip + 1;

            switch($type)
            {
                case 0: //NOP (Passer à l'instruction suivante)
                case 8: //ACT
                    $_SESSION['ip'] = $defaultIp;
                    break;
                
                case 1: //JMP (Faire un saut vers une instruction)
                    $_SESSION['ip'] = $next > 0 ? $next : $defaultIp;
                    break;
                
                case 2: //SCENE (Lancer une nouvelle scène)
                    $sql2 = "SELECT seqid FROM seqtag
                             WHERE tag = ? AND type = 1";
                    $stmt2 = $PDO->prepare($sql2);
                    $stmt2->execute([$name]);
                    $scene = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if(!$scene)
                    {
                        $_SESSION['ip'] = $defaultIp;
                        break;
                    }

                    $seqid = $scene['seqid'];
                    $_SESSION['seen_scenes'][$name] = true;
                    $_SESSION['ip'] = $defaultIp;

                    return [
                        "kind" => "scene",
                        "tag" => $name,
                        "seqid" => $seqid
                    ];
                
                case 3: //MENU (Affiche un qcm et attent le résultat)
                    $_SESSION['ip'] = $defaultIp;
                    return [
                        "kind" => "menu",
                        "tag" => $name
                    ];
                
                case 4: //IFRET (Compare la dernière réponse qcm avec param)
                    $lastchoice = (int)$_SESSION['last_choice'] ?? 0;
                    if($lastchoice === (int)$param && $next > 0)
                    {
                        $_SESSION['ip'] = $next;
                    }
                    else
                    {
                        $_SESSION['ip'] = $defaultIp;
                    }
                    break;
                
                case 5: //IFSEEN (Vérifie si une scène a été visionnée)
                    $seen = !empty($_SESSION['seen_scenes'][$name]);

                    if($seen && $next > 0)
                    {
                        $_SESSION['ip'] = $next;
                    }
                    else
                    {
                        $_SESSION['ip'] = $defaultIp;
                    }
                    break;
                
                case 6: //IFGT (Vérifie si name > param)
                    // On sait que $_SESSION['vars'] existe si 'ip' existe (InitInterpreterSession)
                    $vars = $_SESSION['vars'];

                    // Si la variable $name n'existe pas, on considère qu'elle vaut 0
                    $val = isset($vars[$name]) ? (int)$vars[$name] : 0;

                    if ($val > $param && $next > 0)
                    {
                        $_SESSION['ip'] = $next;
                    }
                    else
                    {
                        $_SESSION['ip'] = $defaultIp;
                    }
                    break;

                
                case 7: //VARADD (Ajoute param à name)
                    $vars = $_SESSION['vars'];

                    // Valeur courante (0 si la variable n'existe pas encore)
                    $current = isset($vars[$name]) ? (int)$vars[$name] : 0;

                    // Ajout du paramètre
                    $vars[$name] = $current + $param;

                    // Sauvegarde
                    $_SESSION['vars'] = $vars;
                    $_SESSION['ip']   = $defaultIp;
                    break;
                
                case 9: //END (fin du jeu)
                    $_SESSION['game_over'] = true;
                    $_SESSION['ip'] = $defaultIp;
                    return [
                        "kind" => "end",
                        "name" => $name
                    ];

                default: //ERREUR
                    return [
                        "kind" => "error",
                        "message" => "Type d'instruction inconnu : {$type}"
                    ];
            }
        }
    }

    // Récupération du menu QCM -> Fonction appelée par next.php
    function GetMenuByTag(PDO $PDO, $tag)
    {
        $sql = "SELECT m.qtext, m.qid, m.opt1, m.opt2, m.opt3, m.opt4
                    FROM seqtag s
                        JOIN menu m ON m.seqid = s.seqid
                    WHERE s.tag = ?
                        AND s.type = 0";
        $stmt = $PDO->prepare($sql);
        $stmt->execute([$tag]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$row)
        {
            return null;
        }

        $options = [];
        for($i = 1; $i <= 4; $i++)
        {
            $key = 'opt'.$i;
            if(!empty($row[$key]))
            {
                $options[] = $row[$key];
            }
        }

        return [
            "type" => 0,
            "tag" => $tag,
            "question" => $row["qtext"],
            "qid" => $row["qid"],
            "options" => $options
        ];
    }
?>