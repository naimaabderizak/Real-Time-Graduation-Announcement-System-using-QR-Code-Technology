<?php
require_once '../admin/db.php';

// Fetch All Settings
$raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Socket Config
$socket_url = "http://" . $_SERVER['SERVER_NAME'] . ":5001";
?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top 3 Reveal • Benadir University</title>
    
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Marcellus&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bu-deep-blue: #002855;
            --bu-deep-blue: #002855;
            --bu-gold: #C5A047;
            --bu-gold-light: #f3e5ab;
            --bu-white: #FFFFFF;
            --bu-cream: #FDFBF4;
            --font-primary: 'Inter', sans-serif;
            --font-secondary: 'Marcellus', serif;
            --font-display: 'Playfair Display', serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-primary);
            color: var(--bu-deep-blue);
            background: #ffffff; /* Plain White */
            overflow: hidden;
            height: 100vh;
            width: 100vw;
        }

        .overlay {
            display: none; /* Removed */
        }

        /* Branding Header */
        header {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5rem;
            background: white;
            border-bottom: 5px solid var(--bu-gold);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .header-logo { height: 120px; }
        .header-center { text-align: center; }
        .header-center h1 { 
            font-family: var(--font-secondary); 
            font-size: 3.5rem; 
            font-weight: 900; 
            color: var(--bu-deep-blue);
            letter-spacing: 2px;
        }

        /* Reveal Stage */
        .reveal-stage {
            height: calc(100vh - 180px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        /* Faculty/Batch Info */
        .reveal-info {
            text-align: center;
            margin-bottom: 3rem;
            opacity: 0;
            transform: translateY(30px);
        }

        .convocation-title {
            font-family: var(--font-secondary);
            font-size: 3rem;
            font-weight: 500;
            color: #000;
            margin-bottom: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .reveal-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--bu-gold);
            text-transform: uppercase;
            letter-spacing: 8px;
            margin-bottom: 15px;
        }

        .reveal-faculty {
            font-family: var(--font-secondary);
            font-size: 3.8rem;
            font-weight: 900;
            color: var(--bu-deep-blue);
            line-height: 1.1;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .reveal-batch {
            font-size: 1.8rem;
            font-weight: 700;
            color: #64748b;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .reveal-dept {
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--bu-gold);
            margin-top: 5px;
            text-transform: italic;
        }

        /* Podium Grid */
        .podium-grid {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 4rem;
            width: 100%;
            max-width: 1400px;
        }

        .student-slot {
            width: 380px;
            opacity: 0;
            transform: scale(0.8) translateY(50px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Rank Order */
        .slot-rank-2 { order: 1; }
        .slot-rank-1 { order: 2; width: 450px; }
        .slot-rank-3 { order: 3; }

        /* Metallic Frames */
        .photo-frame {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            padding: 12px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Rank Badge */
        .rank-number {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-secondary);
            font-size: 2.5rem;
            font-weight: 900;
            color: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border: 4px solid white;
        }

        .slot-rank-1 .rank-number { background: linear-gradient(135deg, #D4AF37, #FFD700); width: 110px; height: 110px; font-size: 3.5rem; }
        .slot-rank-2 .rank-number { background: linear-gradient(135deg, #808080, #C0C0C0); }
        .slot-rank-3 .rank-number { background: linear-gradient(135deg, #8B4513, #CD7F32); }

        .student-name {
            font-size: 2.22rem;
            font-weight: 800;
            color: var(--bu-deep-blue);
            margin-top: 15px;
            text-align: center;
        }

        /* Initial State */
        .idle-screen {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 5;
        }
        .idle-screen img { height: 350px; opacity: 0.1; }
        .idle-screen h2 { font-size: 2rem; color: var(--bu-deep-blue); opacity: 0.2; margin-top: 20px; letter-spacing: 5px; }

    </style>
</head>
<body>
    <div id="scannerIndicator" style="position: fixed; top: 10px; left: 10px; width: 12px; height: 12px; background: #2ecc71; border-radius: 50%; z-index: 10000; box-shadow: 0 0 10px #2ecc71; opacity: 0.8; transition: opacity 0.3s;" title="Scanner Ready (Page Focused)"></div>
    <div class="overlay"></div>

    <header>
        <img src="../<?= $settings['primary_logo'] ?? 'assets/images/benadir_logo.jpg' ?>" class="header-logo">
        <div class="header-center">
            <h1>BENADIR UNIVERSITY</h1>
        </div>
        <img src="../<?= $settings['secondary_logo'] ?? 'assets/images/secondary_logo.png' ?>" class="header-logo">
    </header>

    <main class="reveal-stage">
        <!-- Idle State -->
        <div id="idleScreen" class="idle-screen">
            <img src="../<?= $settings['primary_logo'] ?? 'assets/images/benadir_logo.jpg' ?>">
            <h2>WAITING FOR SCAN...</h2>
        </div>

        <!-- Faculty Info -->
        <div id="revealInfo" class="reveal-info">
            <div class="convocation-title">19th CONVOCATION</div>
            <div class="reveal-title">TOP 3 STUDENTS</div>
            <div id="revealFaculty" class="reveal-faculty">FACULTY NAME</div>
            <div id="revealDept" class="reveal-dept"></div>
        </div>

        <!-- Podium -->
        <div class="podium-grid">
            <!-- Rank 2 -->
            <div id="slot-2" class="student-slot slot-rank-2">
                <div class="photo-frame">
                    <div class="rank-number">2</div>
                </div>
                <div id="name-2" class="student-name">STUDENT NAME</div>
            </div>

            <!-- Rank 1 -->
            <div id="slot-1" class="student-slot slot-rank-1">
                <div class="photo-frame">
                    <div class="rank-number">1</div>
                </div>
                <div id="name-1" class="student-name">STUDENT NAME</div>
            </div>

            <!-- Rank 3 -->
            <div id="slot-3" class="student-slot slot-rank-3">
                <div class="photo-frame">
                    <div class="rank-number">3</div>
                </div>
                <div id="name-3" class="student-name">STUDENT NAME</div>
            </div>
        </div>
    </main>

    <script>
        const socket = io("<?= $socket_url ?>");
        let currentFaculty = null;
        let qrBuffer = "";
        let submitTimer = null;
        const SUBMIT_DELAY = 300; // Auto-submit input after 300ms of silence

        // --- Hardware Scanner Listener ---
        // Supports both "Enter-terminated" and "Timeout-terminated" scanners
        document.addEventListener('keydown', (e) => {
            // Ignore if focus is in an input field (unlikely on this page but good practice)
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            const char = e.key;
            
            // Ignore control keys but allow characters
            // Note: Some scanners send weird control chars, we filter mostly for printable
            if (char.length > 1 && char !== 'Enter' && char !== 'Escape') return; 
            
            // Clear existing timer
            clearTimeout(submitTimer);

            // If Enter is pressed, submit immediately
            if (char === 'Enter') {
                if (qrBuffer.length > 2) {
                    const studentId = qrBuffer.trim();
                    console.log("Submitting via Enter:", studentId);
                    processHardwareScan(studentId);
                    qrBuffer = "";
                }
                return;
            }

            // Append character
            if (char.length === 1) {
                qrBuffer += char;
            }

            // Set timer to auto-submit if scanner stops typing (No 'Enter' key config)
            submitTimer = setTimeout(() => {
                if (qrBuffer.length > 2) {
                    const studentId = qrBuffer.trim();
                    console.log("Auto-submitting (Timeout):", studentId);
                    processHardwareScan(studentId);
                    qrBuffer = "";
                }
            }, SUBMIT_DELAY);
            
            // Optional: Manual Reset key (Escape)
            if (e.key === 'Escape') {
                fetch('<?= $socket_url ?>/reset-awards', { method: 'POST' });
            }
        });

        // Visual feedback on focus
        window.onblur = () => { document.getElementById('scannerIndicator').style.opacity = '0.2'; document.getElementById('scannerIndicator').style.background = '#e74c3c'; };
        window.onfocus = () => { document.getElementById('scannerIndicator').style.opacity = '0.8'; document.getElementById('scannerIndicator').style.background = '#2ecc71'; };

        async function processHardwareScan(studentId) {
            // Pulse indicator on scan
            document.getElementById('scannerIndicator').style.transform = 'scale(2)';
            setTimeout(() => document.getElementById('scannerIndicator').style.transform = 'scale(1)', 200);

            try {
                const response = await fetch(`<?= $socket_url ?>/scan/${studentId}`, { method: 'POST' });
                const data = await response.json();
                console.log("Scan Response:", data);
            } catch (err) {
                console.error("Failed to send scan to backend:", err);
            }
        }

        // 1. Faculty Reveal Event
        socket.on('faculty_reveal', (data) => {
            console.log("Faculty Reveal:", data);
            currentFaculty = data.faculty_name;
            
            // Populate
            document.getElementById('revealFaculty').innerText = data.faculty_name;
            document.getElementById('revealFaculty').innerText = data.faculty_name;
            document.getElementById('revealDept').innerText = ""; // Will be updated if student scan includes dept
            
            // Reset existing students
            gsap.set('.student-slot', { opacity: 0, y: 50, scale: 0.8 });
            
            // Animate
            const tl = gsap.timeline();
            tl.to('#idleScreen', { opacity: 0, duration: 0.5 })
              .to('#revealInfo', { opacity: 1, y: 0, duration: 1, ease: "back.out" });
        });

        // 2. Student Award Reveal Event
        socket.on('award_reveal', (student) => {
            console.log("Award Reveal:", student);
            
            // Auto-Recover Context: If page was refreshed mid-sequence, adopt the student's faculty
            if (!currentFaculty) {
                console.log("Context Missing: Auto-adjusting faculty to", student.faculty);
                currentFaculty = student.faculty;
                
                // Update Header UI immediately
                document.getElementById('revealFaculty').innerText = student.faculty;
                document.getElementById('revealFaculty').innerText = student.faculty;
                
                // Ensure Idle Screen is gone
                 gsap.to('#idleScreen', { opacity: 0, duration: 0.5, onComplete: () => {
                    document.getElementById('idleScreen').style.display = 'none';
                 }});
                 gsap.to('#revealInfo', { opacity: 1, y: 0, duration: 1, ease: "back.out" });
            }

            // Logic: Only reveal if student belongs to the CURRENTLY REVEALED faculty
            if (student.faculty !== currentFaculty) {
                console.warn("Scan ignored: Student faculty (" + student.faculty + ") doesn't match current screen context (" + currentFaculty + ").");
                return;
            }

            const rank = student.student_rank;
            if (![1, 2, 3].includes(rank)) return;

            // Update Dept info if student provides it
            if (student.department && student.department.trim() !== '' && student.department.trim() !== '-' && student.department.toLowerCase().trim() !== student.faculty.toLowerCase().trim()) {
                document.getElementById('revealDept').innerText = "Department of " + student.department;
            } else {
                document.getElementById('revealDept').innerText = "";
            }

            // Populate slot
            const imgPath = student.photo_path ? `../${student.photo_path}` : `../assets/images/default_student.png`;
            document.getElementById(`img-${rank}`).src = imgPath;
            document.getElementById(`name-${rank}`).innerText = student.full_name;

            // Animate Reveal
            gsap.to(`#slot-${rank}`, {
                opacity: 1,
                y: 0,
                scale: 1,
                duration: 1.5,
                ease: "elastic.out(1, 0.5)"
            });
        });

        socket.on('reset_screen', () => {
            console.log("Resetting screen...");
            location.reload(); // Simplest way to fresh start
        });

        socket.on('connect', () => console.log('Connected to SGS Real-time Server'));
    </script>
</body>
</html>
