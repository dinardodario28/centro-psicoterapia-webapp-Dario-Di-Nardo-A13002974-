
function validaRegistrazione() {
    let nome = document.getElementById("nome").value.trim();
    let cognome = document.getElementById("cognome").value.trim();
    let cf = document.getElementById("codiceFiscale").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value;
    let telefono = document.getElementById("telefono").value.trim();

    if(nome === "" || cognome === "" || email === "") {
        alert("Per favore, compila tutti i campi obbligatori.");
        return false; 
    }

    if(cf.length !== 16) {
        alert("Il Codice Fiscale deve essere di 16 caratteri.");
        return false;
    }

   
    if(password.length < 6) {
        alert("La password deve contenere almeno 6 caratteri per la tua sicurezza.");
        return false;
    }

  
    return true;
}

function validaLogin() {
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value;
    if(email === "" || password === "") {
        alert("Inserisci email e password.");
        return false;
    }
    return true;
}