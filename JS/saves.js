// Récupération du span pour afficher les messages
const menuSpan = document.getElementById("menu-span");

// Boutons de sauvegardes (max 5)
const buttons = [1,2,3,4,5];

// Fonction utilitaire pour afficher les messages du span
function showMessage(message, type = 'info')
{
    menuSpan.textContent = message;
    menuSpan.className = type;  // Le type est 'info' ou 'error'
}

// Fonction 1 : créer/écraser une sauvegarde
function saveSlot(i)
{
    // Récupération du nom de la sauvegarde (par défaut : Sauvegarde {i})
    const input = document.getElementById("input-save" + i);
    let saveName = input.value.trim() || `Sauvegarde ${i}`;

    if (saveName.length < 1 || saveName.length > 20)
    {
        showMessage("Le nom de la sauvegarde doit contenir entre 1 et 20 caractères !", 'error');
        input.focus();
        return;
    }

    // Envoi d'une requête à saves.php
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "PHP/saves.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function()
    {
        if (xhr.readyState === 4 && xhr.status === 200)
        {
            const data = JSON.parse(xhr.responseText);
            showMessage(data.message, 'info');
            loadSaves();  // on affiche les slots (Fonction 2)
        }
    };
    const bgLayer = document.getElementById('layer-bg');
    const currentBg = bgLayer.style.backgroundImage.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
    xhr.send("save_number=" + i + "&save_name=" + encodeURIComponent(saveName) + "&current_bg=" + encodeURIComponent(currentBg));
}

// Fonction 2 : afficher les 5 sauvegardes
function loadSaves()
{
    // Envoi d'une requête à get_saves.php
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "PHP/get_saves.php", true);
    xhr.onreadystatechange = function()
    {
        if (xhr.readyState === 4)
        {
            if (xhr.status === 200)
            {
                try
                {
                    const slots = JSON.parse(xhr.responseText);
                    buttons.forEach(i => {
                        const btn = document.getElementById('btn-save'+i);
                        const slot = slots[i] || slots[i.toString()] || {name:'Vide', date:''};
                        btn.textContent = `SAVE ${i} - ${slot.name} ${slot.date}`;
                    });
                    
                }
                catch(e)
                {
                    showMessage("Erreur JSON lors du chargement des sauvegardes", 'error');
                }
            }
            else
            {
                showMessage("Erreur serveur : " + xhr.status, 'error');
            }
        }
    };

    xhr.send();
}

// Fonction 3 : charger une sauvegarde (c'est-à-dire la rendre active)
function loadSlot(i)
{
    // Envoi d'une requête à load_save.php
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "PHP/load_save.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function()
    {
        if (xhr.readyState === 4 && xhr.status === 200)
        {
            try
            {
                const data = JSON.parse(xhr.responseText);
                if (data.status === 'ok')
                {
                    showMessage(data.message, 'info');
                    if (data.background)
                    {
                        SetBackgroundOnly(data.background);
                    }
                    replayCurrentLine();  // on recharge la ligne courante dans la vn
                }
                else
                {
                    showMessage(data.message, 'error');
                }
            }
            catch(e)
            {
                showMessage("Erreur JSON lors du chargement de la sauvegarde", 'error');
            }
        }
    };

    xhr.send("save_number=" + i);
}

// Fonction 4 : rejouer la ligne courante dans la vn
function replayCurrentLine()
{
    // envoi d'une requête à next.php
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "PHP/next.php?replay=1", true);
    xhr.onreadystatechange = function ()
    {
        if (xhr.readyState === 4 && xhr.status === 200)
        {
            const data = JSON.parse(xhr.responseText);
            Handle(data);
        }
    };
    xhr.send();
}

// Gestion du clic gauche/clic droit de chaque bouton SAVE
buttons.forEach(i => {
    const btn = document.getElementById('btn-save' + i);
    if (!btn) return;

    btn.addEventListener('click', () => saveSlot(i)); // clic gauche pour écraser (Fonction 1)
    btn.addEventListener('contextmenu', e => {        
        e.preventDefault();
        loadSlot(i);                                  // clic droit pour charger (Fonction 3)
    });
});