document.addEventListener('DOMContentLoaded', () => {
  console.log("DOM pronto - Caricamento date eventi");

  // Riferimenti agli elementi del DOM
  const form = document.getElementById('formEvent');
  const messageBox = document.getElementById('messageBox');
  const eventoSelect = document.getElementById('evento');
  const containerForm = document.getElementById('containerForm'); // Contiene il titolo, il testo e il form

  // Funzione per mostrare un messaggio (successo o errore)
  function showMessage(message, type = 'success') {
    if (!messageBox) {
      console.warn("Elemento messageBox non trovato nel DOM.");
      return;
    }
    console.log("showMessage chiamato con:", message);
    messageBox.textContent = message;
    messageBox.style.color = (type === 'success') ? 'white' : 'red';
    messageBox.style.display = 'block';
  }

  // Caricamento eventi dal file JSON "date.json"
  fetch('date.json')
    .then(response => {
      if (!response.ok) throw new Error(`Errore caricamento JSON: ${response.status}`);
      return response.json();
    })
    .then(data => {
      data.events.forEach(event => {
        const option = document.createElement('option');
        option.value = event.date;
        option.textContent = `${event.date} - ${event.titolo}`;
        eventoSelect.appendChild(option);
      });
    })
    .catch(error => {
      console.error("Errore caricamento date:", error);
      showMessage("Errore nel caricamento degli eventi. Riprova più tardi.", 'error');
    });

  // Gestione invio del form
  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(form);

    // Validazione dei campi obbligatori
    if (!formData.get('nome') || !formData.get('email') || !formData.get('evento')) {
      showMessage("Compila tutti i campi obbligatori!", 'error');
      return;
    }

    // Validazione email
    const email = formData.get('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showMessage("Inserisci un'email valida!", 'error');
      return;
    }

 // Validazione numero di telefono (in formato internazionale, es. +39...)
let telefono = formData.get('telefono')?.replace(/\s/g, '');

// Se il numero non inizia con il simbolo '+' aggiungiamo il prefisso +39
if (telefono && telefono.charAt(0) !== '+') {
  telefono = '+39' + telefono;
}

const telefonoRegex = /^\+\d{1,3}\d{4,14}$/;
if (!telefono || !telefonoRegex.test(telefono)) {
  showMessage("Inserisci un numero di telefono valido con prefisso internazionale!", 'error');
  return;
}

    try {
      // 1) Invio dei dati a save_form.php (che invia la mail e fa le operazioni server-side)
      const saveFormResponse = await fetch("save_form.php", {
        method: 'POST',
        body: formData
      });

      if (!saveFormResponse.ok) {
        const errorDetails = await saveFormResponse.text();
        console.error("Errore API save_form.php:", errorDetails);
        showMessage(`Errore nel salvataggio: ${errorDetails}`, 'error');
        return;
      }

      // --- NUOVO BLOCCO: salviamo la risposta in rawResponse e facciamo il parse manuale ---
      const rawResponse = await saveFormResponse.text();
      console.log("Risposta RAW:", rawResponse);

      let saveFormResult;
      try {
        saveFormResult = JSON.parse(rawResponse);
      } catch (err) {
        console.error("Errore nel parse JSON:", err);
        // Se la risposta non è JSON valido, usciamo
        return;
      }
      console.log("Risposta JSON parse:", saveFormResult);

      // Se mail e salvataggio su DB non sono andati a buon fine, blocchiamo subito
      if (!saveFormResult.success) {
        showMessage(`Errore: ${saveFormResult.message || "Errore sconosciuto"}`, 'error');
        return;
      }

      // 2) Se mail e salvataggio su DB sono andati a buon fine, avviamo la chiamata a Spoki
      // Esempio: "https://app.spoki.it/api/1/contacts/sync/"
      // con header "X-Spoki-Api-Key: LA_TUA_API_KEY"
      const spokiEndpoint = "https://app.spoki.it/api/1/contacts/sync/";
      const spokiApiKey = "50dc826e157a4bab95b8f1524a36ab25"; // Inserisci qui la tua API key

      // Ricavo il cognome (se presente nel form)
      const cognome = formData.get('cognome') || '';

      // Preparo il payload da inviare a Spoki
      const spokiPayload = {
        phone: telefono,
        first_name: formData.get('nome'),
        last_name: cognome,
        email: email,
        language: "it", // o "en" se preferisci
        custom_fields: {}
      };

      try {
        const spokiResponse = await fetch(spokiEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Spoki-Api-Key': spokiApiKey
          },
          body: JSON.stringify(spokiPayload)
        });

        if (!spokiResponse.ok) {
          // Se la chiamata a Spoki risponde con errore
          throw new Error(`Spoki ha risposto con status ${spokiResponse.status}`);
        }

        // Se la chiamata a Spoki va a buon fine
        const spokiData = await spokiResponse.json();
        console.log("Risposta da Spoki:", spokiData);

        // A questo punto ABBIAMO:
        //  - mail inviata correttamente
        //  - contatto creato/sincronizzato su Spoki

        // ==> Nascondiamo il form e mostriamo il messaggio di successo
        showMessage("Iscrizione eseguita correttamente, Ti arriverà una Mail", 'success');
        containerForm.style.display = 'none';

      } catch (spokiError) {
        console.error("Errore durante la chiamata a Spoki:", spokiError);
        showMessage("Attenzione: contatto NON aggiunto su Spoki. Riprova più tardi.", 'error');
        // Non nascondiamo il form, così l'utente vede l'errore
      }

    } catch (error) {
      console.error("Errore durante la richiesta a save_form.php:", error);
      showMessage(`Errore: ${error.message}`, 'error');
    }
  });
});