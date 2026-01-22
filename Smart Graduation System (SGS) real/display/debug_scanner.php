<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Debugger</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #222; color: #0f0; }
        #log { white-space: pre-wrap; word-break: break-all; }
        .key-event { margin-bottom: 2px; border-bottom: 1px solid #333; }
        .success { color: #0ff; font-weight: bold; }
        .buffer { color: #ff0; }
        #status { font-size: 24px; font-weight: bold; margin-bottom: 20px; border: 2px solid #555; padding: 10px; }
        .focused { border-color: #0f0 !important; background: #003300; }
        .blurred { border-color: #f00 !important; background: #330000; }
    </style>
</head>
<body>
    <h1>HARDWARE SCANNER DEBUGGER</h1>
    <div id="status" class="blurred">CLICK HERE TO FOCUS</div>
    
    <div>
        <h3>Buffer State: <span id="bufferDisplay" class="buffer"></span></h3>
        <button onclick="clearLog()">Clear Log</button>
    </div>
    <hr>
    <div id="log">Logs will appear here...</div>

    <script>
        const logEl = document.getElementById('log');
        const bufferDisplay = document.getElementById('bufferDisplay');
        const statusEl = document.getElementById('status');
        
        let buffer = "";
        let lastTime = Date.now();

        window.onfocus = () => {
            statusEl.innerText = "FOCUSED (READY TO SCAN)";
            statusEl.className = "focused";
            log("Window Focused");
        };
        window.onblur = () => {
            statusEl.innerText = "NOT FOCUSED (CLICK HERE)";
            statusEl.className = "blurred";
            log("Window Lost Focus");
        };

        function log(msg, type = '') {
            const div = document.createElement('div');
            div.className = 'key-event ' + type;
            div.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
            logEl.prepend(div);
        }

        function clearLog() {
            logEl.innerHTML = "";
            buffer = "";
            updateBuffer();
        }

        function updateBuffer() {
            bufferDisplay.innerText = `"${buffer}" (len: ${buffer.length})`;
        }

        document.addEventListener('keydown', (e) => {
            const now = Date.now();
            const diff = now - lastTime;
            lastTime = now;

            let charCode = e.key; // Readable char
            if (e.key === ' ') charCode = '(SPACE)';
            if (e.key === 'Enter') charCode = '(ENTER)';
            
            const msg = `Key: "${charCode}" | Code: ${e.code} | Diff: ${diff}ms`;
            log(msg);

            // Logic Simulation
            if (diff > 500) {
                log(`>> TIMEOUT! Buffer cleared (Gap > 500ms)`, 'buffer');
                buffer = "";
            }

            if (e.key === 'Enter') {
                log(`>> SUBMIT! Final Payload: "${buffer}"`, 'success');
                buffer = "";
            } else if (e.key.length === 1) {
                buffer += e.key;
            }
            
            updateBuffer();
        });
    </script>
</body>
</html>
