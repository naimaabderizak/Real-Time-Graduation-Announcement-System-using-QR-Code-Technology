<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGS Scanner Interface</title>
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- html5-qrcode Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-bg: #1a2332; /* Darker navy to match reference */
            --accent-color: #4f46e5;
            --card-bg: #1e293b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--primary-bg);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow-x: hidden;
        }

        .scanner-container {
            width: 100%;
            max-width: 900px; /* Wider for better camera preview */
            padding: 3rem 2rem;
            text-align: center;
        }

        #reader {
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            border: 4px solid #4f46e5 !important; /* Thicker blue border */
            box-shadow: 0 0 40px rgba(79, 70, 229, 0.5), 0 0 80px rgba(79, 70, 229, 0.3); /* Glowing effect */
            background: #000;
            margin: 2rem 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .status-online {
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .status-offline {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Hidden input for USB Scanner */
        #usb-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        .scanner-type-toggle {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        button.active-mode {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        h1 {
            font-size: 3rem; /* Larger title */
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .text-secondary {
            color: #94a3b8 !important;
            font-size: 1.1rem;
        }
        
        /* Focus Guard Overlay */
        #focus-guard {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(220, 38, 38, 0.95); /* Bright Red */
            z-index: 9999;
            display: none; /* Hidden by default */
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-align: center;
        }
        
        #focus-guard h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        #focus-guard p {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        
        #focus-guard i {
            font-size: 5rem;
            margin-bottom: 2rem;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <div id="focus-guard">
        <i class="bi bi-shield-lock-fill"></i>
        <h2>SCANNER PAUSED</h2>
        <p>Click Anywhere to Resume Scanning</p>
    </div>

    <div class="scanner-container">
        <div class="status-badge status-online" id="server-status">
            Real-time Server: CONNECTED
        </div>
        
        <h1 class="mb-4 fw-bold">Scan Student QR</h1>
        <p class="text-secondary mb-3">Place the student's QR code in front of the rear camera.</p>

        <div id="reader-wrapper" style="position: relative;">
            <div id="reader"></div>
            <div id="camera-controls" style="margin-top: 1rem; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <button id="switch-camera-btn" class="btn btn-outline-light btn-sm" style="border-radius: 50px; padding: 0.5rem 1.5rem;">
                    <i class="bi bi-camera-rotate"></i> Switch Camera
                </button>
                <button id="pip-btn" class="btn btn-outline-warning btn-sm" style="border-radius: 50px; padding: 0.5rem 1.5rem;">
                    <i class="bi bi-window-stack"></i> Picture-in-Picture
                </button>
                <button id="scan-image-btn" class="btn btn-outline-info btn-sm" style="border-radius: 50px; padding: 0.5rem 1.5rem;">
                    <i class="bi bi-image"></i> Scan from Image
                </button>
            </div>
            <!-- Hidden file input -->
            <input type="file" id="qr-input-file" accept="image/*" style="display:none">
        </div>

        <!-- Hidden input to capture USB scanner keyboard emulation -->
        <input type="text" id="usb-input" autofocus>

        <div class="scanner-type-toggle">
            <div class="alert alert-info py-2" style="background: rgba(79, 70, 229, 0.1); border: none; color: #818cf8;">
                <small><i class="bi bi-info-circle"></i> USB Scanner is always active. Just scan.</small>
            </div>
        </div>
        
        <!-- Award Session Status / Reset -->
        <div class="mb-4 d-flex justify-content-center">
            <button id="reset-awards-btn" class="btn btn-outline-danger btn-sm rounded-pill px-4">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Award Reveal
            </button>
        </div>

        <!-- VISIBLE DEBUG INPUT (Styled to look like a status bar) -->
        <div class="mb-3">
             <label class="text-secondary small">Scanner Input Buffer (Debug):</label>
             <input type="text" id="scanner-buffer" class="form-control text-center fs-4" 
                    style="background: #0f172a; border: 1px solid #334155; color: #fff; letter-spacing: 2px;"
                    placeholder="Scanning..." readonly>
             <small class="text-muted d-block mt-1">If text appears here but doesn't submit, the scanner is not sending 'Enter'.</small>
        </div>

        <div id="result-log" class="mt-4 text-start" style="height: 100px; overflow-y: auto; font-size: 0.85rem; color: #94a3b8;">
            <!-- Scan history logged here -->
        </div>
    </div>

    <script>
        const usbInput = document.getElementById('usb-input');
        const fileInput = document.getElementById('qr-input-file');
        const scanImageBtn = document.getElementById('scan-image-btn');
        const logContainer = document.getElementById('result-log');
        const serverIp = window.location.hostname;
        const pythonServerUrl = `http://${serverIp}:5001`;

        // --- USB Scanner & Focus Logic ---
        let isFocusLocked = true;
        const focusGuard = document.getElementById('focus-guard');
        const serverBadge = document.getElementById('server-status');
        
        function updateFocusStatus(isFocused) {
            if (isFocused) {
                focusGuard.style.display = 'none';
                usbInput.placeholder = "Scanner Ready...";
            } else {
                // Determine if we should show the guard
                // We give a tiny grace period (200ms) for button clicks to register
                // But for a handheld scanner, we generally want it ALWAYS active.
                if (isFocusLocked) {
                    focusGuard.style.display = 'flex';
                }
            }
        }

        // Force focus sequence
        function ensureFocus() {
            if (!isFocusLocked) return;
            usbInput.focus();
        }

        // Listeners for FOCUS GUARD
        focusGuard.addEventListener('click', () => {
            isFocusLocked = true;
            ensureFocus();
        });

        // Listeners to maintain focus
        document.addEventListener('click', (e) => {
            // If clicking a button, allow it, but then refocus
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                isFocusLocked = false;
                setTimeout(() => { 
                    isFocusLocked = true; 
                    ensureFocus(); 
                }, 500); 
            } else {
                isFocusLocked = true;
                ensureFocus();
            }
        });

        // Loop to check focus
        setInterval(() => {
            if (isFocusLocked && document.activeElement !== usbInput) {
                // If we lost focus and we are locked, show the guard
                updateFocusStatus(false);
            } else {
                updateFocusStatus(true);
            }
        }, 500);

        usbInput.addEventListener('focus', () => updateFocusStatus(true));
        usbInput.addEventListener('blur', () => {
            // Delayed check to allow button clicks to process
            setTimeout(() => {
                if (document.activeElement !== usbInput && isFocusLocked) {
                    updateFocusStatus(false);
                }
            }, 100);
        });

        // Keydown listener - debugs *every* key to help user see if scanner is sending data
        let inputBuffer = '';
        let inputTimer = null;
        const bufferDisplay = document.getElementById('scanner-buffer');

        usbInput.addEventListener('keydown', (e) => {
            // Ignore control keys
            if (['Shift', 'Control', 'Alt', 'CapsLock', 'Tab'].includes(e.key)) return;

            if (e.key === 'Enter') {
                e.preventDefault(); 
                const scannedId = usbInput.value.trim();
                if (scannedId) processScan(scannedId);
                usbInput.value = '';
                inputBuffer = '';
                bufferDisplay.value = '';
                clearTimeout(inputTimer);
            } else {
                // Buffer logic for timeout submission
                inputBuffer = usbInput.value; // Capture value *after* keypress is usually better in 'input' event, but here we monitor.
            }
        });
        
        // Use INPUT event for reliable capturing
        usbInput.addEventListener('input', (e) => {
             const val = usbInput.value.trim();
             bufferDisplay.value = val; // Show user what is being typed
             
             // Reset timer on every keystroke
             clearTimeout(inputTimer);
             
             // If we have a decent length string (e.g. > 5 chars), set a timer to auto-submit
             // This fixes "Scanner doesn't send Enter" issues
             if (val.length > 5) {
                 inputTimer = setTimeout(() => {
                     logResult(`Auto-submitting (Timeout): ${val}`, 'info');
                     processScan(val);
                     usbInput.value = '';
                     bufferDisplay.value = '';
                 }, 400); // 400ms wait after last character
             }
        });


        function logResult(msg, type='info') {
            const time = new Date().toLocaleTimeString();
            const colors = { error: '#ef4444', success: '#10b981', info: '#64748b' };
            const entry = document.createElement('div');
            entry.innerHTML = `<span style="color: ${colors[type] || colors.info}">[${time}]</span> ${msg}`;
            logContainer.prepend(entry);
        }

        async function processScan(decodedText) {
            logResult(`Processing: <b>${decodedText}</b>`, 'info');
            
            // Client-side validation
            if (decodedText.length < 3) {
                 logResult(`Ignored short scan: ${decodedText}`, 'error');
                 return;
            }

            try {
                // Remove Swal.showLoading() to avoid focus theft during network request.
                // We rely on the logResult for immediate feedback.

                const response = await fetch(`${pythonServerUrl}/scan/${decodedText}`, { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.status === 'success') {
                    logResult(`Success: ${data.message}`, 'success');
                    
                    // USE TOAST INSTEAD OF MODAL to preserve focus on input
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#1e293b',
                        color: '#fff',
                        toast: true,           // <--- Critical change
                        position: 'top-end',   // <--- Critical change
                        didOpen: () => usbInput.focus(), // Force focus back just in case
                        willClose: () => usbInput.focus()
                    });
                     
                } else if (data.status === 'warning') {
                    logResult(`Warning: ${data.message}`, 'info');
                    Swal.fire({
                        title: 'Reminder',
                        text: data.message,
                        icon: 'warning',
                        timer: 2000,
                        showConfirmButton: false,
                        background: '#1e293b',
                        color: '#fff',
                        toast: true,
                        position: 'top-end',
                        didOpen: () => usbInput.focus(),
                        willClose: () => usbInput.focus()
                    });
                } else {
                    logResult(`Error: ${data.message}`, 'error');
                    // Errors can still be modals, but ensure we return focus
                    Swal.fire({ 
                        title: 'Scan Error', 
                        text: data.message, 
                        icon: 'error', 
                        timer: 2500,
                        showConfirmButton: false,
                        background: '#1e293b', 
                        color: '#fff',
                        didOpen: () => usbInput.focus(),
                        willClose: () => usbInput.focus()
                    });
                }
            } catch (error) {
                console.error(error);
                logResult(`Failed to connect: ${error.message}`, 'error');
                Swal.fire({ 
                    title: 'Connection Failed', 
                    html: `Could not reach server at <b>${pythonServerUrl}</b>.<br>Is the backend running?`, 
                    icon: 'error', 
                    showConfirmButton: true,
                    background: '#1e293b', 
                    color: '#fff',
                    didClose: () => usbInput.focus()
                });
            }
        }

        // --- Camera Logic ---
        let html5QrCode;
        let currentFacingMode = "environment"; 

        function startScanner() {
            // Only start camera if checking for it. 
            // Use simple check to avoid nagging if they only want USB.
            // But for now, we leave it as requested.
            initializeScanner();
        }

        function initializeScanner() {
            if (typeof Html5Qrcode === 'undefined') return;

            // Simplified camera access
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            // Should we auto-start? Maybe the user prefers just USB. 
            // Let's try to start, but handle errors gracefully without blocking UI
             html5QrCode = new Html5Qrcode("reader");
             html5QrCode.start(
                { facingMode: currentFacingMode }, 
                config, 
                (decodedText) => processScan(decodedText),
                (errorMessage) => {} 
            ).catch(err => {
                console.warn("Camera start failed (ignore if using USB only):", err);
                // Don't popup error immediately, just log it. 
                logResult("Camera not started (USB Mode Active)", 'info');
            });
        }
        
        /* 
           Simulate a scan for debugging if needed 
           window.debugScan = (id) => processScan(id);
        */

        document.getElementById('switch-camera-btn').addEventListener('click', () => {
            if(html5QrCode) {
                html5QrCode.stop().then(() => {
                    currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
                    initializeScanner();
                }).catch(err => initializeScanner());
            }
        });

        // --- File Based Scanning ---
        scanImageBtn.addEventListener('click', () => fileInput.click()); // Check if this button click triggers blur!

        fileInput.addEventListener('change', e => {
            if (e.target.files.length === 0) return;
            const imageFile = e.target.files[0];
            const fileScanner = new Html5Qrcode("reader");
            fileScanner.scanFile(imageFile, true)
                .then(decodedText => {
                    processScan(decodedText);
                    fileScanner.clear();
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Error',
                        text: 'No QR Code found in this image.',
                        icon: 'error',
                        background: '#1e293b',
                        color: '#fff'
                    });
                });
        });

        // --- Picture in Picture ---
        const pipBtn = document.getElementById('pip-btn');
        pipBtn.addEventListener('click', async () => {
             const video = document.querySelector('#reader video');
             if (video && video.readyState >= 2) {
                 if (document.pictureInPictureElement) document.exitPictureInPicture();
                 else video.requestPictureInPicture();
             }
        });

        // Reset Awards Button
        document.getElementById('reset-awards-btn').addEventListener('click', async () => {
             try {
                await fetch(`${pythonServerUrl}/reset-awards`, { method: 'POST' });
                logResult("Awards Reset Sent", 'success');
                usbInput.focus(); // Re-focus after click
            } catch (err) {
                logResult("Reset failed: " + err.message, 'error');
            }
        });

        // Server Check
        async function checkHeartbeat() {
            try {
                const res = await fetch(`${pythonServerUrl}/`, { signal: AbortSignal.timeout(2000) });
                if (res.ok) {
                    serverBadge.innerHTML = "Real-time Server: CONNECTED";
                    serverBadge.className = "status-badge status-online";
                } else {
                    throw new Error("500");
                }
            } catch (e) {
                serverBadge.innerHTML = "Real-time Server: DISCONNECTED";
                serverBadge.className = "status-badge status-offline";
            }
        }
        setInterval(checkHeartbeat, 5000);
        checkHeartbeat(); // Initial check
        
        // Initial Focus
        ensureFocus();

    </script>
</body>
</html>
