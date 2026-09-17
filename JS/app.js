let screen_login = document.getElementById("screen-login");
let screen_game  = document.getElementById("screen-game");
let logout       = document.getElementById("btn-logout");
let btnHide      = document.getElementById("btn-hide");
let menuOpen     = document.getElementById("btn-menu");
let menuClose    = document.getElementById("btn-game");
let menuScreen   = document.getElementById("screen-menu");

let panelName    = document.getElementById("panel-name");
let panelText    = document.getElementById("panel-text");
let background   = document.getElementById("layer-bg");
let character    = document.getElementById("layer-character");
let spriteStack  = document.getElementById("sprite-stack");

// Variables d'état de l'application
let qcmActive = false;
let isMenuOpen = false;
let textHidden = false;

// Définition de la variable spriteStack si elle ne l'est pas encore
if(!spriteStack)
{
  spriteStack = document.createElement('div');
  spriteStack.id = 'sprite-stack';
  character.appendChild(spriteStack); 
}

// Préchargement des images de background des pages
const images = [
  "Images/login.jpg",
  "Images/game.png",
  "Images/menu.jpg"
];

const preloaded = [];

images.forEach(src => {
  const img = new Image();
  img.src = src;
  preloaded.push(img);
});

// Gestion de la connexion
function connect()
{
    console.log("connexion");

    screen_login.style.display = "none";
    screen_game.style.display = "block";
    document.body.style.backgroundImage = `url('Images/game.png')`;
}

// Gestion de la déconnexion
logout.addEventListener("click", disconnect);

function disconnect(event)
{
    event.preventDefault();
    console.log("déconnexion");

    window.location.href = "PHP/logout.php";  // Redirection vers le script de déconnexion (logout.php)
    document.body.style.backgroundImage = `url('Images/login.jpg')`;
}

// Gestion de l'affichage/camouflage du menu de sauvegardes (click gauche MENU ou touche 'escape')
menuOpen.addEventListener("click", openMenu);
menuClose.addEventListener("click", closeMenu);

document.addEventListener("keydown", function (event)  // Ouvrir/fermer le menu avec 'escape'
{
  if (event.key === "Escape")
  {
    if(menuScreen.style.display === "flex")
    {
      closeMenu();
    }
    else
    {
      openMenu();
    }
  }
});

function openMenu()  // Ouvrir le menu
{
  isMenuOpen = true;

  menuScreen.style.display = "flex";
  document.getElementById("screen-game").style.display = "none";
  document.body.style.backgroundImage = `url('Images/menu.jpg')`;
  loadSaves();  // On charge les sauvegardes du joueur via la fonction loadSaves (saves.js)
}

function closeMenu()  // Fermer le menu
{
  isMenuOpen = false;

  menuScreen.style.display = "none";
  document.getElementById("screen-game").style.display = "block";
  document.body.style.backgroundImage = `url("Images/game.png")`;

}

// Gestion du bouton recommencer (présent dans le menu)
const btnRestartMenu = document.getElementById("btn-restart-menu");
if (btnRestartMenu)
{
  btnRestartMenu.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();

    closeMenu();
    restartGame();
  });
}

function restartGame() //Fonction pour recommencer le jeu
{
  // envoi d'une requête à restart.php
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "PHP/restart.php", true);
  xhr.onreadystatechange = function ()
  {
    if (xhr.readyState === 4 && xhr.status === 200)
    {
      console.log("Restart");
      panelName.textContent = ""; // Nettoyage visuel
      panelText.textContent = "";
      spriteStack.replaceChildren();
    }
  };
  xhr.send();
}

// Gestion de l'affichage/camouflage du texte (click gauche HIDE/SHOW)
btnHide.addEventListener("click", hiddenShow);

function hiddenShow()
{
    textHidden = !textHidden;
    if(textHidden)
    {
        panelName.style.opacity = 0;
        panelText.style.opacity = 0;
    }
    else
    {
        panelName.style.opacity = 1;
        panelText.style.opacity = 1;
    }
}

// Gestion du passage à la ligne suivante (click gauche sur la VN ou touche 'espace')
screen_game.addEventListener("click", nextLine);

document.addEventListener("keydown", function(event)  // Changer de ligne avec 'espace'
{
  if(event.key === " ")
  { 
    event.preventDefault();
    nextLine(event);
  }
});

function nextLine(event)  // Vérifier que l'on peut passer à la ligne suivante
{
    if(event.target.closest('button') || qcmActive || isMenuOpen)
    {
        return;
    }

    let centeredBox = document.getElementById('centered-box');
    if(centeredBox)
    {
      centeredBox.remove();
    }

    fetchNextLine();
}

function fetchNextLine()  // Changer de ligne (requête à next.php)
{
  const xhr = new XMLHttpRequest();  // On envoie une requête à next.php
  xhr.open("GET", "PHP/next.php", true);
  xhr.onreadystatechange = function ()
  {
    if (xhr.readyState === 4 && xhr.status === 200)
    {
      try
      {
        const data = JSON.parse(xhr.responseText);
        Handle(data); // On envoie les données à la fonction Handle (code principal)
      }
      catch(e)
      {
        console.error("Erreur de parsing JSON :", e, xhr.responseText);
      }
    }
  };
  xhr.send();
}

function AutoAdvance() //Fonction pour contrôler l'enchaînement de lignes
{
  setTimeout(fetchNextLine, 0);
}

// CASE 0 : Gestion de l'affichage des qcm
function ShowQCM(data)  // Afficher un qcm
{
  qcmActive = true;

  // On supprime un QCM éventuel (sécurité)
  ClearQCM();

  const overlay = document.createElement('div');
  overlay.id = 'qcm-overlay';

  const box = document.createElement('div');
  box.id = 'qcm-box';

  const question = document.createElement('div');
  question.className = 'qcm-question';
  question.textContent = data.question;

  box.appendChild(question);

  (data.options || []).forEach((optText, index) => {
    const btn = document.createElement('button');
    btn.className = "qcm-button";
    btn.textContent = optText;

    btn.addEventListener('click', (event) => {
      event.stopPropagation();
      HandleQCMChoice(index + 1, data.tag);
    });

    box.appendChild(btn);
  });

  overlay.appendChild(box);
  document.getElementById('stage').appendChild(overlay);
}

function ClearQCM()  // Supprimer le qcm
{
  const overlay = document.getElementById('qcm-overlay');
  if (overlay)
  {
    overlay.remove();
  }
}

function HandleQCMChoice(choiceIndex, tag)  // Capturer le choix du joueur
{
  console.log("Choix QCM :", tag, "->", choiceIndex);

  qcmActive = false;
  ClearQCM();

  const params = "tag=" + encodeURIComponent(tag) + 
                 "&choice=" + encodeURIComponent(choiceIndex);

  const xhr = new XMLHttpRequest();  // On envoie une requête à qcm_answer.php
  xhr.open("POST", "PHP/qcm_answer.php", true);
  xhr.setRequestHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );
  xhr.onreadystatechange = function()
  {
    if(xhr.readyState === 4 && xhr.status === 200)
    {
      try
      {
        const res = JSON.parse(xhr.responseText);
        if(!res.ok)
        {
          console.error("Erreur QCM côté serveur", res.error);
        }
      }
      catch(e)
      {
        console.error()
      }
    }
  }
  xhr.send(params);

  fetchNextLine();
}

// CASE 1 : Gestion de l'affichage des dialogues
function ShowDialogue(data)  // Afficher le texte et le nom du personnage
{
  const name  = data.speaker || "";
  let texte = data.texte   || data.text || "";
  const color = data.color   || "#FFFFFF";

  texte = texte.replace(/~/g, "").replace(/{w}/g, "");

  panelName.textContent = name;
  panelText.textContent = texte;
  panelName.style.color = color;
  panelText.style.color = color;
}

// CASE 2 : Gestion de l'affichage background
function SetSceneBackground(url)  // Supprime les sprites et change le bg
{
  spriteStack.replaceChildren();
  SetBackgroundOnly(url);
}

function SetBackgroundOnly(url)  // Afficher un bg sans toucher aux sprites
{
  const layer = document.getElementById('layer-bg');
  const img = new Image();
  img.onload = () => {
    layer.style.backgroundImage = `url("${url}")`;
    layer.style.backgroundSize = 'cover';
    layer.style.backgroundPosition = 'center'; 
    layer.style.backgroundRepeat = 'no-repeat';
  };
  img.onerror = () => console.error('BG introuvable :', url);
  img.src = url;
}

// CASE 3 : Gestion de l'affichage des sprites
function ShowOrMove(data)  // Afficher ou déplacer un sprite
{
  const { tag, url, xpos, z, h, w } = data;

  let holder = document.getElementById(`sprite-${tag}`); // Chercher si une div avec ce tag existe déjà
  
  if (!holder)
  {
    holder = document.createElement('div');
    holder.id = `sprite-${tag}`;
    holder.className = 'sprite';
    holder.style.position = 'absolute';
    holder.style.bottom = '0';
    holder.style.pointerEvents = 'none';
    
    spriteStack.appendChild(holder);
  }
  holder.style.backgroundImage = `url("${url}")`;
  holder.style.backgroundRepeat = 'no-repeat';
  holder.style.backgroundPosition = 'center';
  holder.style.backgroundSize     = 'contain';

  if (w) holder.style.width  = w + 'px';
  if (h) holder.style.height = h + 'px';

  const width  = w;
  const leftPx = (xpos ?? 0) - (width / 2);
  holder.style.left = leftPx + 'px';

  const count = spriteStack.querySelectorAll(".sprite").length;
  holder.style.zIndex = String(10 * (z ?? 0) + count);
}

// CASE 4 : Gestion de la suppression d'un sprite
function HideTag(data)  // Supprimer un sprite grâce à son tag
{
  const tag = data.tag || data;
  const holder = document.getElementById(`sprite-${tag}`);
  if (holder) holder.remove();
}

// CASE 5 : Gestion des textes centrés
function ShowCenteredText(texte)  // Afficher un message centré
{
  let box = document.getElementById('centered-box');
  if (!box)
  {
    box = document.createElement('div');
    box.id = 'centered-box';
    box.style.position = 'absolute';
    box.style.left = '50%';
    box.style.top = '50%';
    box.style.transform = 'translate(-50%, -50%)';
    box.style.zIndex = '100';
    box.style.padding = '16px 24px';
    box.style.background = 'rgba(0,0,0,.6)';
    box.style.color = '#fff';
    box.style.borderRadius = '12px';
    box.style.maxWidth = '70%';
    box.style.textAlign = 'center';
    box.style.whiteSpace = 'pre-wrap';
    character.appendChild(box);
  }

  box.innerHTML = texte.replace(/\\n/g, "<br>");
}

// CASE 6 : Gestion de l'injection d'HTML
function ShowHtml(html)  // Injectien d'HTML dans le panelText
{
  panelText.innerHTML = html;
}

// CODE PRINCIPAL : Gestion du scénario (instructions)
function Handle(data)
{
  console.log(data);
  switch (data.type)
  {
    case 0: // QCM
      ShowQCM(data);
      break;

    case 1: // Dialogue
      ShowDialogue(data);
      break;

    case 2: // Changement de fond et suppression des sprites
      SetSceneBackground(data.url);
      AutoAdvance();
      break;

    case 3: // Afficher ou déplacer un sprite
      if(data.tag === "BG")
      {
        SetBackgroundOnly(data.url);
      }
      else
      {
        ShowOrMove(data);
      }
      AutoAdvance();
      break;

    case 4: // Supprimer un sprite
      HideTag(data);
      AutoAdvance();
      break;

    case 5: // Texte centré
      ShowCenteredText(data.texte || data.text || "");
      break;

    case 6: // HTML dans le panelText
      ShowHtml(data.html || "");
      break;
  }
}