
# Voici un petit aperçu de la structure de notre projet :

connexionBD.php       -> connexion à la base de données
  └── mesparametres.php -> paramètres (host, user, pass)

app.js                -> coeur de l'application côté client
  ├── logout.php        -> déconnexion
  ├── qcm_answer.php    -> sauvegarde du choix du joueur
  ├── restart.php       -> réinitialise l'état de la partie
  ├── next.php          -> renvoie la prochaine ligne du scénario
        └── interpreteur.php -> analyse le type de ligne et renvoie les données

saves.js              -> gestion des sauvegardes côté client
  ├── get_saves.php     -> récupère les 5 sauvegardes du joueur
  ├── load_save.php     -> charge la sauvegarde sélectionnée
  └── saves.php         -> écrase ou crée une nouvelle sauvegarde

connexion.js          -> gestion de la connexion côté client
  └── connexion.php     -> crée ou connecte l'utilisateur