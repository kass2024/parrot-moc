// CONFIG
const chatGptEndpoint = "php/chat_gpt.php";
const ttsEndpoint = "php/tts.php";
const sttEndpoint = "php/stt.php";
const finalScoringEndpoint = "php/final_scoring.php";
const licenseInfoEndpoint = "php/license_info.php";

// Read license from URL param
const urlParams = new URLSearchParams(window.location.search);
const license_key = urlParams.get('license') || '';

if (!license_key) {
    alert('❗ Missing license key in URL.\nExample: https://yourapp.com/index.html?license=YOUR-LICENSE');
    throw new Error('Missing license key — blocked');
}

// Generate or load device_id
let device_id = localStorage.getItem('parrot_device_id');
if (!device_id) {
    device_id = 'dev-' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('parrot_device_id', device_id);
}

// GLOBALS
let conversationHistory = [];
let interviewFinished = false;
let mediaRecorder;
let audioChunks = [];

// ADD message to chat
function addMessage(role, text) {
    const chatBox = document.getElementById("chat-box");

    const msgDiv = document.createElement("div");
    msgDiv.classList.add("message");
    if (role === "user") msgDiv.style.background = "#d4f8d4";

    msgDiv.innerText = text;
    chatBox.appendChild(msgDiv);

    chatBox.scrollTop = chatBox.scrollHeight;
}

// HANDLE send (text)
async function handleSend() {
    if (interviewFinished) return;

    const userInput = document.getElementById("answer-input").value.trim();
    if (userInput === "") return;

    addMessage("user", userInput);
    conversationHistory.push({ role: "user", content: userInput });

    document.getElementById("answer-input").value = "";
    document.getElementById("send-button").disabled = true;

    try {
        const response = await fetch(chatGptEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                license_key: license_key,
                device_id: device_id,
                conversation: conversationHistory
            })
        });

        const data = await response.json();

        if (response.status === 403 || data.error) {
            alert("❌ License error: " + (data.error || "Invalid license"));
            throw new Error("License blocked — " + (data.error || ""));
        }

        if (data.reply) {
            addMessage("assistant", data.reply);
            conversationHistory.push({ role: "assistant", content: data.reply });
            await speakText(data.reply);
        } else {
            console.error("GPT reply missing!", data);
            alert("Error: No reply from GPT.");
        }

    } catch (err) {
        console.error("Error calling chat_gpt.php", err);
        alert("Error: Could not reach server.");
    }

    document.getElementById("send-button").disabled = false;
}

// TTS speak reply
async function speakText(text) {
    try {
        const response = await fetch(ttsEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                license_key: license_key,
                device_id: device_id,
                text
            })
        });

        const data = await response.json();

        if (response.status === 403 || data.error) {
            alert("❌ License error: " + (data.error || "Invalid license"));
            throw new Error("License blocked — " + (data.error || ""));
        }

        if (data.audio_url) {
            const audio = new Audio(data.audio_url);
            audio.addEventListener("canplaythrough", () => {
                audio.play();
            });
            audio.load();
        } else {
            console.warn("TTS: No audio URL", data);
        }
    } catch (err) {
        console.error("Error calling tts.php", err);
    }
}

// HANDLE Finish Interview
async function handleFinishInterview() {
    if (interviewFinished) return;
    interviewFinished = true;

    const finishButton = document.getElementById("finish-interview-btn");
    finishButton.disabled = true;
    finishButton.innerHTML = "⏳ Generating scores...";

    try {
        const response = await fetch(finalScoringEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                license_key: license_key,
                device_id: device_id,
                conversation: conversationHistory
            })
        });

        const data = await response.json();

        if (response.status === 403 || data.error) {
            finishButton.innerHTML = "❌ License Error";
            alert("❌ License error: " + (data.error || "Invalid license"));
            throw new Error("License blocked — " + (data.error || ""));
        }

        if (data.scores) {
            updateScores(data.scores);
            finishButton.innerHTML = "✅ Scores Generated";
        } else {
            finishButton.innerHTML = "⚠️ No Scores Received";
            console.error("No scores returned!", data);
            alert("Error: No scores received.");
        }

    } catch (err) {
        finishButton.innerHTML = "❌ Error";
        console.error("Error calling final_scoring.php", err);
        alert("Error: Could not reach server.");
    }
}


// UPDATE Score panel
function updateScores(scores) {
    for (const [key, value] of Object.entries(scores)) {
        const scoreValue = value.score;
        const feedback = value.feedback;

        let id = "";
        switch (key) {
            case "Study Plan Clarity": id = "score1"; break;
            case "University Choice Rationale": id = "score2"; break;
            case "Motivation for Studying in US": id = "score3"; break;
            case "Financial Stability": id = "score4"; break;
            case "Ties to Home Country": id = "score5"; break;
            case "Post-Graduation Plans": id = "score6"; break;
            case "English Proficiency": id = "score7"; break;
        }

        if (id) {
            document.getElementById(id + "-value").innerText = `${scoreValue}/10`;
            document.getElementById(id).style.width = `${scoreValue * 10}%`;
            document.getElementById(id).setAttribute("title", feedback);
        }
    }
}

// MIC button — start/stop recording
document.getElementById("mic-button").addEventListener("click", async function() {
    if (mediaRecorder && mediaRecorder.state === "recording") {
        mediaRecorder.stop();
        document.getElementById("mic-button").innerText = "🎙️";
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert("Mic not supported in this browser.");
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = event => {
            audioChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
            const formData = new FormData();
            formData.append('audio', audioBlob, 'audio.mp3');
            formData.append('license_key', license_key);
            formData.append('device_id', device_id);

            try {
                const response = await fetch(sttEndpoint, {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                if (data.transcript) {
                    document.getElementById("answer-input").value = data.transcript;
                } else {
                    console.warn("STT: No transcript", data);
                    alert("Could not transcribe audio.");
                }
            } catch (err) {
                console.error("Error calling stt.php", err);
                alert("Error: Could not reach STT server.");
            }
        };

        mediaRecorder.start();
        document.getElementById("mic-button").innerText = "⏹️";

    } catch (err) {
        console.error("Mic error", err);
        alert("Mic access denied.");
    }
});

// INIT listeners
document.getElementById("send-button").addEventListener("click", handleSend);
document.getElementById("answer-input").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        handleSend();
    }
});

document.getElementById("finish-interview-btn").addEventListener("click", handleFinishInterview);

// ON PAGE LOAD — load first AI question
window.addEventListener("load", async function() {
    try {
        const response = await fetch(chatGptEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                license_key: license_key,
                device_id: device_id,
                conversation: []
            })
        });

        const data = await response.json();

        if (response.status === 403 || data.error) {
            alert("❌ License error: " + (data.error || "Invalid license"));
            throw new Error("License blocked — " + (data.error || ""));
        }

        if (data.reply) {
            addMessage("assistant", data.reply);
            conversationHistory.push({ role: "assistant", content: data.reply });
            await speakText(data.reply);
        } else {
            console.error("Error loading first question", data);
        }

    } catch (err) {
        console.error("Error contacting chat_gpt.php", err);
        alert("Error: Could not reach server.");
    }
});

// Load license UTC + countdown
async function loadLicenseInfo() {
    try {
        const response = await fetch(licenseInfoEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                license_key: license_key,
                device_id: device_id
            })
        });

        const data = await response.json();

        if (response.status === 403 || data.error) {
            document.getElementById("now-utc").innerText = "License invalid";
            document.getElementById("license-countdown").innerText = "License invalid";
            return;
        }

        document.getElementById("now-utc").innerText = data.now_utc;

        const expiry = new Date(data.expiry_date + " UTC");

        function updateCountdown() {
            const now = new Date();
            const diff = expiry - now;
            if (diff <= 0) {
                document.getElementById("license-countdown").innerText = "EXPIRED";
                return;
            }
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const mins = Math.floor((diff / (1000 * 60)) % 60);
            document.getElementById("license-countdown").innerText = `${days}d ${hours}h ${mins}m`;
        }

        updateCountdown();
        setInterval(updateCountdown, 60000);

    } catch (err) {
        console.error("Error loading license info", err);
    }
}

window.addEventListener("load", loadLicenseInfo);
