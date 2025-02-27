document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM pronto - Caricamento date eventi");

    // Riferimento al form
    const form = document.getElementById('formEvent');
    const messageBox = document.getElementById('messageBox'); // Elemento per i messaggi di stato
    const eventoSelect = document.getElementById('evento');

    // Funzione per mostrare messaggi di stato
    function showMessage(message, type = 'success') {
        messageBox.textContent = message;
        messageBox.style.color = type === 'success' ? 'green' : 'red';
        messageBox.style.display = 'block';
    }

    // Caricamento delle opzioni evento dal file JSON
    fetch('date.json')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Errore nel caricamento del file JSON: ${response.status}`);
            }
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
            console.error("Errore durante il caricamento delle date:", error);
            showMessage("Impossibile caricare gli eventi. Riprova più tardi.", 'error');
        });

    // Gestione invio del form
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(form);

        // Validazione campi obbligatori
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

        // Costruzione dei parametri della query per il metodo GET
        const queryParams = new URLSearchParams({
            nome: formData.get('nome'),
            cognome: formData.get('cognome'),
            email: formData.get('email'),
            telefono: formData.get('telefono'),
            dataNascita: formData.get('data-nascita'),
            citta: formData.get('citta'),
            evento: formData.get('evento'),
            consenso: formData.get('consenso') !== null,
            pubblicita: formData.get('pubblicita') !== null
        }).toString();

        console.log("Dati inviati:", queryParams);

        // Timeout per richieste
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout di 10 secondi

        // Invia la richiesta al Google Apps Script tramite metodo GET
        fetch(`https://script.google.com/macros/s/AKfycbxkIYJoMFZJM6nbD3iIb0GTu6PPmQEAOV_46dFDdeJ58AnKNQpncsjCUnj6wTYI52MN/exec?${queryParams}`, {
            method: 'GET',
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId); // Cancella il timeout
            if (!response.ok) {
                throw new Error(`Errore nella risposta: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            console.log("Risultato:", result);
            if (result.status === 'success') {
                showMessage("Dati inviati correttamente!");
                document.getElementById('thankYouMessage').style.display = 'block';
                form.style.display = 'none';
            } else {
                showMessage("Errore: " + result.message, 'error');
            }
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                showMessage("La richiesta ha impiegato troppo tempo. Riprova.", 'error');
            } else {
                console.error("Errore durante l'invio:", error);
                showMessage("Si è verificato un errore durante l'invio. Riprova.", 'error');
            }
        });
    });
});