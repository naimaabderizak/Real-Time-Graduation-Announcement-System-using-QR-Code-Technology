<?php
require_once '../admin/db.php';

// Fetch All Settings
$raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Fetch All Faculties and their colors for CSS
$faculties_data = $pdo->query("SELECT * FROM faculties")->fetchAll();
$faculty_colors_js = [];
foreach ($faculties_data as $f) {
    $faculty_colors_js[$f['faculty_name']] = $f['faculty_color'];
}

function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}

// Fetch Top 3 Students from every faculty
$query = "SELECT * FROM students WHERE student_rank IN (1, 2, 3) ORDER BY faculty ASC, student_rank ASC";
$top_students = $pdo->query($query)->fetchAll();

// Group students by faculty
$faculty_groups = [];
foreach ($top_students as $student) {
    $faculty_groups[$student['faculty']][] = $student;
}
?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Rankers • Benadir University • Convocation 2024</title>
    
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bu-deep-blue: #002855;
            --bu-gold: #C5A047;
            --bu-gold-dark: #A6822D;
            --bu-white: #FFFFFF;
            --bu-cream: #FDFBF4;
            
            --font-primary: 'Inter', sans-serif;
            --font-secondary: 'Marcellus', serif;
            --font-display: 'Playfair Display', serif;
            
            --scale-factor: 0.8;
            --card-w: calc(450px * var(--scale-factor));
            --card-h: calc(650px * var(--scale-factor));
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-primary);
            color: var(--bu-deep-blue);
            background: #fdfbf4;
            overflow-x: hidden;
            background-image: url('../assets/images/no jiingad.jpg');
            background-size: cover;
            background-attachment: fixed;
        }

        .overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(253, 251, 244, 0.85);
            z-index: -1;
        }

        /* Header */
        .stage-header {
            width: 100%;
            height: 160px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4rem;
            border-bottom: 4px solid var(--bu-gold);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-logos { display: flex; align-items: center; gap: 2rem; }
        .header-logo { height: 100px; object-fit: contain; }
        
        .header-branding { display: flex; flex-direction: column; }
        .header-uni-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--bu-deep-blue);
            text-transform: uppercase;
        }
        .header-center-title { text-align: center; }
        .title-main { font-family: var(--font-secondary); font-size: 3.5rem; font-weight: 900; color: var(--bu-deep-blue); line-height: 0.9; }
        .title-sub { font-size: 1.5rem; font-weight: 800; color: var(--bu-gold); letter-spacing: 5px; margin-top: 5px; text-transform: uppercase; }

        /* Content */
        .container {
            max-width: 1600px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .faculty-section {
            margin-bottom: 6rem;
        }

        .faculty-title {
            font-family: var(--font-secondary);
            font-size: 3rem;
            color: var(--bu-deep-blue);
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .faculty-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--bu-gold);
        }

        .rank-grid {
            display: flex;
            justify-content: center; align-items: flex-end;
            gap: 3rem;
            perspective: 1000px;
        }

        /* Student Card */
        .student-card {
            width: var(--card-w);
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            padding: 15px;
            display: flex;
            flex-direction: column;
            border: 4px solid var(--faculty-color, var(--bu-gold));
            position: relative;
            transition: transform 0.4s ease;
        }

        .student-card:hover { transform: translateY(-10px); }

        /* Podium Heights */
        .rank-1 { height: calc(var(--card-h) + 50px); order: 2; z-index: 3; }
        .rank-2 { height: var(--card-h); order: 1; z-index: 2; }
        .rank-3 { height: calc(var(--card-h) - 50px); order: 3; z-index: 1; }

        .student-info {
            background: #f1f5f9;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .rank-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem 2rem;
            border-radius: 50px;
            font-family: var(--font-secondary);
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--bu-deep-blue);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 2px solid var(--bu-gold);
            z-index: 10;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .rank-1 .rank-badge { background: linear-gradient(135deg, #fff 0%, #fdfbf4 100%); border-color: #ffd700; color: #b8860b; font-size: 3rem; }
        .rank-2 .rank-badge { background: linear-gradient(135deg, #fff 0%, #f8fafc 100%); border-color: #c0c0c0; color: #708090; }
        .rank-3 .rank-badge { background: linear-gradient(135deg, #fff 0%, #fff5ee 100%); border-color: #cd7f32; color: #8b4513; }

        .student-info {
            background: #f1f5f9;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .student-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 5px;
            color: var(--bu-deep-blue);
        }

        .student-dept {
            font-size: 1rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Dynamic Faculty Colors */
        <?php foreach ($faculties_data as $f): 
            $safe_name = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $f['faculty_name']));
        ?>
        .f-<?= $safe_name ?> { 
            --faculty-color: <?= $f['faculty_color'] ?>; 
            --faculty-color-rgb: <?= hexToRgb($f['faculty_color']) ?>;
        }
        <?php endforeach; ?>

        /* Footer */
        .stage-footer {
            width: 100%;
            height: 120px;
            background: var(--bu-deep-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border-top: 4px solid var(--bu-gold);
            margin-top: 4rem;
        }

        .footer-text {
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.8;
        }

        @media (max-width: 1200px) {
            .rank-grid { flex-wrap: wrap; align-items: center; }
            .student-card { height: var(--card-h) !important; order: initial !important; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <header class="stage-header">
        <div class="header-logos">
            <img src="../<?= $settings['primary_logo'] ?? 'assets/images/benadir_logo.jpg' ?>" class="header-logo">
            <div class="header-branding">
                <span class="header-uni-name"><?= $settings['uni_name'] ?? 'Benadir University' ?></span>
            </div>
        </div>
        <div class="header-center-title">
            <div class="title-main">TOP GRADUATES</div>
            <div class="title-sub"><?= $settings['anniversary_subtext'] ?? 'CONVOCATION 2024' ?></div>
        </div>
        <div class="header-logos">
            <img src="../<?= $settings['secondary_logo'] ?? 'assets/images/secondary_logo.png' ?>" class="header-logo">
        </div>
    </header>

    <div class="container">
        <?php if (empty($faculty_groups)): ?>
            <div style="text-align: center; padding: 5rem;">
                <h1 style="color: #64748b; opacity: 0.5;">No ranked students assigned yet.</h1>
                <p>Assign ranks (1, 2, or 3) to students in the admin panel to display them here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($faculty_groups as $faculty_name => $students): 
                $safe_faculty_class = 'f-' . strtolower(preg_replace('/[^a-z0-9]/', '', $faculty_name));
            ?>
                <section class="faculty-section <?= $safe_faculty_class ?>">
                    <h2 class="faculty-title"><?= htmlspecialchars($faculty_name) ?></h2>
                    <div class="rank-grid">
                        <?php foreach ($students as $student): 
                            $rank = $student['student_rank'];
                            $rank_text = ($rank == 1) ? "1st Rank" : (($rank == 2) ? "2nd Rank" : "3rd Rank");
                            $photo = (!empty($student['photo_path']) && file_exists('../' . $student['photo_path'])) 
                                     ? '../' . $student['photo_path'] 
                                     : '../assets/images/default_student.png';
                        ?>
                            <div class="student-card rank-<?= $rank ?>">
                                <div class="rank-badge"><?= $rank_text ?></div>
                                <div class="student-info">
                                    <h3 class="student-name"><?= htmlspecialchars($student['full_name']) ?></h3>
                                    <?php 
                                    $displayDept = $student['department'] ?? '';
                                    if (!empty($displayDept) && $displayDept !== '-' && strtolower(trim($displayDept)) !== strtolower(trim($student['faculty']))): ?>
                                        <p class="student-dept"><?= htmlspecialchars($displayDept) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="stage-footer">
        <p class="footer-text"><?= $settings['footer_text'] ?? 'Benadir University • Excellence in Education' ?></p>
    </footer>

    <script>
        // Reveal sections on scroll
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    gsap.from(entry.target.querySelectorAll('.student-card'), {
                        y: 100,
                        opacity: 0,
                        stagger: 0.2,
                        duration: 1,
                        ease: "power3.out"
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.faculty-section').forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>
