let form            = document.getElementById("Form-Conn");
let usernameInput   = document.getElementById("username");
let passwordInput   = document.getElementById("mdp");
let span            = document.getElementById("Form-span");

// Gestion de la validation du formulaire
form.addEventListener("submit", validate);

function validate(event)
{
  event.preventDefault();

  // Remise à zéro du span
  span.textContent = "";
  span.style.display = "none";


  let isValid = true;

  let usernameValid;
  let passwordValid;

  usernameInput.value = usernameInput.value.trim();
  passwordInput.value = passwordInput.value.trim();

  usernameValid = validateUsername();
  passwordValid = validatePassword();

  if(!usernameValid || !passwordValid)
  {
    span.textContent = "Nom ou mot de passe incorrect !";
    span.style.color = "red";
    span.style.display = "inline";
    isValid = false;
    return;
  }
  else
  {
    span.textContent = "";
    span.style.color = "black";
    span.style.display = "none";
  }

  if(isValid == false)
  {
    event.preventDefault();
  }
  
  sendLoginRequest();
}

// Gestion de l'envoi des données valides vers connexion.php
function sendLoginRequest()
{
  let params = "username=" + encodeURIComponent(usernameInput.value) +
               "&mdp=" + encodeURIComponent(passwordInput.value);

  // Envoi requête à connexion.php
  const xhr = new XMLHttpRequest();
  xhr.open("POST", 'PHP/connexion.php', true);
  xhr.onreadystatechange = function ()
  {
    if (xhr.readyState === 4 && xhr.status === 200)
    {
      let response;
      try
      {
        response = JSON.parse(xhr.responseText);
      }
      catch(e)
      {
        console.error("Erreur de parsing JSON :", e, xhr.responseText);
        return;
      }

      if(response.success)
      {
        console.log("connexion réussie");
        connect();
      }
      else
      {
        span.textContent = response.error;
        span.style.color = "red";
        span.style.display = "inline";
        return;
      }
    }
  };
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
  xhr.send(params);
}

// Fonction de validation du pseudo
function validateUsername()
{
  let regex = /^[A-Za-z\d]{5,20}$/;
  let usernameValue = usernameInput.value;
  return regex.test(usernameValue);
}

// Fonction de validation du mot de passe
function validatePassword()
{
  let regex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,20}$/;
  let passwordValue = passwordInput.value;
  return regex.test(passwordValue);
}
