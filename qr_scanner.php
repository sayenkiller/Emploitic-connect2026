<?php
// Real-time QR Code Scanner - Auto-start Camera
// Live MySQL Database - InfinityFree Production
// AUTO-STARTS SCANNING - No button click needed on first load

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner QR - Emploitic Connect</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0a1929 0%, #1a2980 50%, #26d0ce 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .container {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(30px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 30px;
        max-width: 600px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    h1 {
        color: white;
        text-align: center;
        margin-bottom: 10px;
        font-size: 1.8em;
    }

    .subtitle {
        text-align: center;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 30px;
        font-size: 0.9em;
    }

    .scanner-section {
        position: relative;
        margin-bottom: 30px;
        border-radius: 15px;
        overflow: hidden;
        border: 3px solid #26d0ce;
        box-shadow: 0 0 20px rgba(38, 208, 206, 0.5);
    }

    #qr-video {
        width: 100%;
        height: auto;
        display: block;
        background: #000;
    }

    .video-placeholder {
        width: 100%;
        height: 300px;
        background: rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.6);
        text-align: center;
        padding: 20px;
        font-size: 14px;
        flex-direction: column;
        gap: 15px;
    }

    .video-placeholder.hidden {
        display: none;
    }

    .camera-icon {
        font-size: 40px;
    }

    .status {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        color: white;
        text-align: center;
    }

    .status.success {
        background: rgba(40, 167, 69, 0.2);
        border-color: #28a745;
        color: #90EE90;
    }

    .status.error {
        background: rgba(220, 53, 69, 0.2);
        border-color: #dc3545;
        color: #FF6B6B;
    }

    .status.scanning {
        background: rgba(0, 123, 255, 0.2);
        border-color: #007bff;
        color: #87CEEB;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .controls {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .btn {
        flex: 1;
        padding: 12px;
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(38, 208, 206, 0.3);
    }

    .btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(38, 208, 206, 0.5);
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn.secondary {
        background: rgba(255, 255, 255, 0.2);
    }

    .result {
        background: rgba(255, 255, 255, 0.08);
        border: 2px solid #26d0ce;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        display: none;
        color: white;
    }

    .result.active {
        display: block;
    }

    .result h3 {
        color: #26d0ce;
        margin-bottom: 15px;
        font-size: 1.3em;
    }

    .result p {
        margin-bottom: 12px;
        font-size: 0.95em;
        line-height: 1.5;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 10px;
    }

    .info-label {
        font-weight: 600;
        color: #26d0ce;
    }

    .info-value {
        color: rgba(255, 255, 255, 0.9);
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(30px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 40px;
        max-width: 500px;
        text-align: center;
        color: white;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .modal-content h2 {
        color: #26d0ce;
        margin-bottom: 20px;
        font-size: 1.8em;
    }

    .modal-content p {
        margin-bottom: 15px;
        color: rgba(255, 255, 255, 0.9);
    }

    .modal-content .btn {
        width: 100%;
        margin-top: 20px;
    }

    .footer {
        text-align: center;
        color: rgba(255, 255, 255, 0.6);
        font-size: 12px;
        margin-top: 20px;
    }

    .footer a {
        color: #26d0ce;
        text-decoration: none;
    }

    @media (max-width: 600px) {
        .container {
            padding: 20px;
            border-radius: 15px;
        }

        h1 {
            font-size: 1.5em;
        }

        #qr-video {
            height: 250px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Scanner QR</h1>
        <p class="subtitle">Salon Emploitic Connect 2026</p>

        <div class="status scanning">
            <div id="status-text">🔴 Initialisation caméra...</div>
        </div>

        <div class="scanner-section">
            <video id="qr-video" autoplay playsinline></video>
            <div class="video-placeholder" id="placeholder">
                <div class="camera-icon">📷</div>
                <div>Activation de la caméra...</div>
            </div>
        </div>

        <div class="controls">
            <button class="btn" id="start-btn" onclick="startScanner()" style="display:none;">▶ Démarrer</button>
            <button class="btn secondary" id="stop-btn" onclick="stopScanner()" disabled>⏹ Arrêter</button>
        </div>

        <div class="result" id="result-box">
            <h3>✓ Code QR Détecté</h3>
            <div class="info-row">
                <span class="info-label">Participant ID:</span>
                <span class="info-value" id="result-id">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Événement:</span>
                <span class="info-value" id="result-event">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Heure:</span>
                <span class="info-value" id="result-time">-</span>
            </div>
            <button class="btn" onclick="confirmAccess()" style="margin-top: 15px;">✓ Confirmer Accès</button>
        </div>

        <div class="footer">
            <p>🔒 Sécurisé | 📡 En temps réel | 🎥 Caméra activée</p>
            <p><a href="participant_confirmation.php">← Retour Confirmation</a></p>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmation-modal">
        <div class="modal-content">
            <h2 id="modal-title">✓ Confirmé</h2>
            <p id="modal-message">Accès enregistré avec succès</p>
            <button class="btn" onclick="newScan()">📱 Nouveau Scan</button>
        </div>
    </div>

    <!-- Load QR Scanner Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.js"></script>
    
    <script>
    let video = document.getElementById('qr-video');
    let canvas = document.createElement('canvas');
    let canvasContext = canvas.getContext('2d');
    let isScanning = false;
    let lastQrCode = null;
    let animationId = null;
    let cameraStarted = false;

    // AUTO-START on page load
    window.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded - Starting camera...');
        startScannerAuto();
    });

    async function startScannerAuto() {
        try {
            console.log('Starting scanner automatically...');
            document.getElementById('status-text').textContent = '🔴 Demande d\'accès caméra...';
            
            const constraints = {
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('Camera stream obtained:', stream);

            video.srcObject = stream;
            cameraStarted = true;

            document.getElementById('placeholder').classList.add('hidden');
            document.getElementById('start-btn').style.display = 'none';
            document.getElementById('stop-btn').disabled = false;
            document.getElementById('status-text').textContent = '🔴 Scanning... Pointez le QR Code vers la caméra';
            document.getElementById('status-text').parentElement.className = 'status scanning';

            video.onloadedmetadata = () => {
                console.log('Video metadata loaded');
                video.play().then(() => {
                    console.log('Video playing - Starting QR scan loop');
                    isScanning = true;
                    scanQRCode();
                }).catch(err => {
                    console.error('Error playing video:', err);
                });
            };

        } catch (err) {
            console.error('Camera error:', err);
            document.getElementById('status-text').textContent = '❌ Accès caméra refusé. Vérifiez les permissions.';
            document.getElementById('status-text').parentElement.className = 'status error';
            document.getElementById('placeholder').classList.remove('hidden');
            document.getElementById('start-btn').style.display = 'block';
            document.getElementById('start-btn').disabled = false;
            alert('Veuillez autoriser l\'accès à la caméra pour utiliser le scanner QR');
        }
    }

    async function startScanner() {
        try {
            document.getElementById('status-text').textContent = 'Activation caméra...';
            
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
            });

            video.srcObject = stream;
            cameraStarted = true;
            document.getElementById('placeholder').classList.add('hidden');

            document.getElementById('start-btn').disabled = true;
            document.getElementById('stop-btn').disabled = false;
            document.getElementById('status-text').textContent = '🔴 Scanning...';
            document.getElementById('status-text').parentElement.className = 'status scanning';

            video.onloadedmetadata = () => {
                video.play();
                isScanning = true;
                scanQRCode();
            };
        } catch (err) {
            console.error('Erreur caméra:', err);
            document.getElementById('status-text').textContent = '❌ Accès caméra refusé';
            alert('Veuillez autoriser l\'accès à la caméra');
        }
    }

    function stopScanner() {
        isScanning = false;
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
        cameraStarted = false;
        video.style.display = 'none';
        document.getElementById('placeholder').classList.remove('hidden');
        document.getElementById('start-btn').disabled = false;
        document.getElementById('stop-btn').disabled = true;
        document.getElementById('status-text').textContent = 'Scanner arrêté';
        document.getElementById('status-text').parentElement.className = 'status';
        if (animationId) cancelAnimationFrame(animationId);
    }

    function scanQRCode() {
        if (!isScanning || !cameraStarted) return;

        try {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            if (canvas.width === 0 || canvas.height === 0) {
                animationId = requestAnimationFrame(scanQRCode);
                return;
            }

            canvasContext.drawImage(video, 0, 0);
            const imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });

            if (code && code.data !== lastQrCode) {
                lastQrCode = code.data;
                console.log('QR Code detected:', code.data);
                handleQRCode(code.data);
            }
        } catch (err) {
            console.error('Scan error:', err);
        }

        animationId = requestAnimationFrame(scanQRCode);
    }

    function handleQRCode(qrData) {
        try {
            const data = JSON.parse(qrData);
            if (data.id && data.event && data.timestamp) {
                console.log('Valid QR data:', data);
                displayResult(data);
                document.getElementById('status-text').textContent = '✓ QR Code détecté avec succès!';
                document.getElementById('status-text').parentElement.className = 'status success';
            } else {
                console.log('Invalid QR format');
            }
        } catch (e) {
            console.log('QR data is not JSON - might be direct qr_code from database');
            // Try to use it as direct QR code
            document.getElementById('qr-value').value = qrData;
            console.log('Set QR value:', qrData);
        }
    }

    function displayResult(data) {
        document.getElementById('result-id').textContent = data.id;
        document.getElementById('result-event').textContent = data.event;
        document.getElementById('result-time').textContent = new Date(data.timestamp * 1000).toLocaleString('fr-FR');
        document.getElementById('result-box').classList.add('active');
        
        // Store data for confirmation
        document.getElementById('result-box').dataset.qrData = JSON.stringify(data);
    }

    function confirmAccess() {
        const qrData = document.getElementById('result-box').dataset.qrData;
        
        if (!qrData) {
            alert('Erreur: pas de données QR');
            return;
        }

        fetch('participant_confirmation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'search_type=qr_code&search_value=' + encodeURIComponent(qrData) + 
                  '&scanner_id=Mobile Scanner&confirm_access=1'
        })
        .then(r => r.json())
        .then(data => {
            console.log('Response:', data);
            document.getElementById('modal-title').textContent = data.success ? '✓ Confirmé' : '✗ Erreur';
            document.getElementById('modal-message').textContent = data.message;
            document.getElementById('confirmation-modal').classList.add('active');
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Erreur de connexion');
        });
    }

    function closeModal() {
        document.getElementById('confirmation-modal').classList.remove('active');
    }

    function newScan() {
        closeModal();
        lastQrCode = null;
        document.getElementById('result-box').classList.remove('active');
        document.getElementById('status-text').parentElement.className = 'status scanning';
        document.getElementById('status-text').textContent = '🔴 Scanning... Pointez le QR Code vers la caméra';
    }

    // Handle modal close on click outside
    document.getElementById('confirmation-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
</body>
</html>