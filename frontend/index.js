"use strict";

const API_URL = "src/API/requestHandler.php";

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("btn-create").addEventListener("click", (e) => {
        sendRequest(e, "create");
    });
    document.getElementById("btn-open").addEventListener("click", (e) => {
        sendRequest(e, "open");
    });
    document.getElementById("btn-send").addEventListener("click", (e) => {
        sendRequest(e, "send");
    });
});


function getFormData() {
    const cdl = document.getElementById("cdl").value;
    const dataLaurea = document.getElementById("dataLaurea").value;
    const matricoleText = document.getElementById("matricole").value;

    const matricole = matricoleText
        .split(/[ ]+/)
        .filter(m => m !== "")
        .map(m => Number(m))
        .filter(m => !isNaN(m));

    return { cdl, dataLaurea, matricole: JSON.stringify(matricole) };
}



function updateStatus(message) {
	const statusText = document.getElementById("status-text");

	if (statusText) {
		statusText.textContent = message;
	}

	
}



function sendRequest(e, requestType) {
    e.preventDefault();
    const formData = getFormData();

    const data = new FormData();
    data.append("request-type", requestType);
    data.append("cdl", formData.cdl);

    switch(requestType) {
        case "create":
            data.append("dataLaurea", formData.dataLaurea);
            data.append("matricole", formData.matricole);
            break;
        case "open":
            break;
        case "send":
            updateStatus("Invio mail in corso...");
            processBatchMail(formData);
            return;
        default:
            return;
    }

    return fetch(API_URL, {
        method: "POST",
        body: data
    })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || "Errore nella richiesta");
                });
            }
            return response.json();
        })
        .then(result => {
            if (result.error) {
                throw new Error(result.message);
            }
            updateStatus(result.message);
            if (requestType == "open") {
                if (!window.open(result.pdf_url, '_blank')) {
                    if (confirm("Il browser ha bloccato l'apertura automatica. Vuoi aprire il file qui?")) {
                        window.location.href = result.pdf_url;
                    }
                }
            }
            return result;
        })
        .catch(error => {
            console.error("Errore:", error);
            updateStatus(error.message || "Errore di connessione");
            return null;
        })

}



async function processBatchMail(formData) {
	const data = new FormData();
	data.append("request-type", "send");
	data.append("cdl", formData.cdl);

	

	try {
		const response = await fetch(API_URL, {
			method: "POST",
			body: data
		});

		if (!response.ok) {
			const errorData = await response.json();
			throw new Error(errorData.message || "Errore nella richiesta");
		}

		const result = await response.json();

		if (result.error) {
			throw new Error(result.message);
		}

		if (result.finished) {
			updateStatus(result.message);
			return;
		}
		else{
			updateStatus(result.message);

			await processBatchMail(formData);
		}
	} 
	catch (error) {
		console.error("Errore:", error);
		updateStatus(error.message || "Errore di connessione");
		return null;
	}
}




