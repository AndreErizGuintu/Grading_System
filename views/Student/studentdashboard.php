<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('student');

$user = current_user();

$student = null;
$stmt = $mysqli->prepare('SELECT student_id, full_name, program FROM tb_students WHERE user_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user['user_id']);
    if ($stmt->execute()) {
        $student = $stmt->get_result()->fetch_assoc();
    } else {
        error_log('Error fetching student: ' . $stmt->error);
    }
    $stmt->close();
} else {
    error_log('Prepare error: ' . $mysqli->error);
}

$syList = [];
$semList = [];

$syResult = $mysqli->query('SELECT sy_id, school_year FROM tb_school_years ORDER BY school_year DESC');
if ($syResult === false) {
    error_log('Error fetching school years: ' . $mysqli->error);
} else {
    $syList = $syResult->fetch_all(MYSQLI_ASSOC);
}

$semResult = $mysqli->query('SELECT sem_id, semester FROM tb_semesters ORDER BY sem_id ASC');
if ($semResult === false) {
    error_log('Error fetching semesters: ' . $mysqli->error);
} else {
    $semList = $semResult->fetch_all(MYSQLI_ASSOC);
}

$syId = $_GET['sy_id'] ?? '';
$semId = $_GET['sem_id'] ?? '';

if (!$student) {
    http_response_code(404);
    echo 'Student record not found.';
    exit;
}

$studentId = (int)($student['student_id'] ?? 0);
require __DIR__ . '/../../crud/student_read.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HCC Portal | Academic Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --bg-main: #050505;
            --bg-sidebar: #0a0a0b;
            --bg-card: rgba(18, 18, 20, 0.7);
            --text-main: #fafafa;
            --text-muted: #a1a1aa;
            --accent: #38bdf8;
            --accent-glow: rgba(56, 189, 248, 0.1);
            --border: rgba(255, 255, 255, 0.06);
            --sidebar-width: 260px;
        }

        body.light-mode {
            --bg-main: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: rgba(255, 255, 255, 0.8);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #0284c7;
            --accent-glow: rgba(2, 132, 199, 0.05);
            --border: rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            transition: background 0.4s ease;
            overflow-x: hidden;
        }

        /* Responsive Sidebar for Desktop, Bottom Nav for Mobile */
        .sidebar { 
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar); 
            border-right: 1px solid var(--border);
            backdrop-filter: blur(20px);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--accent);
            background: var(--accent-glow);
        }

        /* Mobile Bottom Navigation */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 70px;
                flex-direction: row !important;
                justify-content: space-around;
                padding: 0 10px !important;
                z-index: 100;
                border-right: none;
                border-top: 1px solid var(--border);
            }
            .sidebar .nav-link {
                flex-direction: column;
                gap: 4px;
                font-size: 0.65rem;
                padding: 8px;
                border-radius: 8px;
            }
            .sidebar .nav-link i { font-size: 1.2rem; }
            .sidebar .logo-section, .sidebar .nav-label, .sidebar .user-section, .sidebar .sign-out-btn { display: none !important; }
            main { padding-bottom: 100px !important; }
        }

        /* Glass Cards */
        .glass-card { 
            background: var(--bg-card); 
            backdrop-filter: blur(12px);
            border: 1px solid var(--border); 
            border-radius: 20px;
        }

        /* Mobile-first Table Scroll */
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="flex flex-col lg:flex-row min-h-screen">

    <aside class="sidebar flex flex-col p-6 h-screen sticky top-0 z-50">
        <div class="logo-section mb-10 px-2 flex items-center gap-3">
            <div class="p-2 bg-sky-500/10 rounded-xl">
                <img src="https://hccp-sms.holycrosscollegepampanga.edu.ph/public/assets/images/logo4.png" class="w-7 h-7 object-contain">
            </div>
            <div>
                <h1 class="text-md font-black leading-none">HCC Portal</h1>
                <span class="text-[9px] text-sky-500 font-bold uppercase tracking-widest">Student Hub</span>
            </div>
        </div>

        <nav class="flex flex-1 flex-row lg:flex-col lg:space-y-2">
            <a href="#" class="nav-link"><i class="fa-solid fa-house"></i><span class="lg:inline">Home</span></a>
            <a href="#" class="nav-link active"><i class="fa-solid fa-graduation-cap"></i><span class="lg:inline">Grades</span></a>
            <a href="#" class="nav-link"><i class="fa-solid fa-book-open"></i><span class="lg:inline">Courses</span></a>
            <button onclick="toggleTheme()" class="nav-link"><i id="theme-icon" class="fa-solid fa-moon"></i><span class="lg:inline">Theme</span></button>
        </nav>

        <div class="user-section mt-auto pt-6 border-t border-[var(--border)]">
            <div class="flex items-center gap-3 px-2 mb-4">
                <div class="w-9 h-9 rounded-full bg-sky-500 flex items-center justify-center font-bold text-white text-sm">
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-[11px] font-bold truncate"><?php echo h($student['full_name']); ?></p>
                    <p class="text-[9px] text-gray-500">Online</p>
                </div>
            </div>
            <a href="/Grading_System/logout.php" class="sign-out-btn nav-link text-red-400 hover:bg-red-500/10">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col w-full overflow-hidden">
        <header class="h-16 lg:h-20 px-6 lg:px-10 flex items-center justify-between sticky top-0 bg-[var(--bg-main)]/70 backdrop-blur-lg z-40 border-b border-[var(--border)]">
            <div>
                <h1 class="text-lg lg:text-xl font-black tracking-tight">Academic Records</h1>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest">School Transcript</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col items-end pr-4 border-r border-[var(--border)]">
                    <span class="text-[9px] font-bold text-sky-500 uppercase">Program</span>
                    <span class="text-[11px] font-extrabold italic"><?php echo h($student['program']); ?></span>
                </div>
                <button class="w-10 h-10 rounded-full bg-[var(--bg-card)] border border-[var(--border)] flex items-center justify-center text-gray-400">
                    <i class="fa-solid fa-bell"></i>
                </button>
            </div>
        </header>

        <main class="p-4 lg:p-10 max-w-6xl mx-auto w-full space-y-6 lg:space-y-10">
            
            <div class="glass-card p-5 no-print">
                <form class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-5">
                        <label class="text-[10px] font-black text-gray-500 uppercase ml-1 mb-2 block">Academic Year</label>
                        <select name="sy_id" class="w-full bg-[var(--bg-main)] border border-[var(--border)] rounded-xl px-4 py-3 text-sm font-bold outline-none focus:ring-2 ring-sky-500/20">
                            <?php foreach ($syList as $sy): ?>
                                <option value="<?php echo (int)$sy['sy_id']; ?>" <?php echo ((string)$syId === (string)$sy['sy_id']) ? 'selected' : ''; ?>><?php echo h($sy['school_year']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-5">
                        <label class="text-[10px] font-black text-gray-500 uppercase ml-1 mb-2 block">Semester</label>
                        <select name="sem_id" class="w-full bg-[var(--bg-main)] border border-[var(--border)] rounded-xl px-4 py-3 text-sm font-bold outline-none focus:ring-2 ring-sky-500/20">
                            <?php foreach ($semList as $sem): ?>
                                <option value="<?php echo (int)$sem['sem_id']; ?>" <?php echo ((string)$semId === (string)$sem['sem_id']) ? 'selected' : ''; ?>><?php echo h($sem['semester']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="w-full bg-sky-500 text-white h-[48px] rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-sky-500/20 active:scale-95 transition-all">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="glass-card overflow-hidden">
                <div class="p-5 border-b border-[var(--border)] flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-sky-500 rounded-full"></div>
                        <h3 class="font-black uppercase text-xs tracking-widest">Official Record</h3>
                    </div>
                    <button onclick="window.print()" class="no-print w-full sm:w-auto flex items-center justify-center gap-2 text-[10px] font-black uppercase bg-sky-500/10 text-sky-500 px-5 py-3 rounded-xl hover:bg-sky-500 hover:text-white transition-all">
                        <i class="fa-solid fa-file-pdf"></i> Save as PDF
                    </button>
                </div>
                <div class="table-container p-2 lg:p-4">
                    <?php require __DIR__ . '/../../includes/components/student_grades_table.php'; ?>
                </div>
            </div>

            <footer class="text-center py-10 opacity-30">
                <p class="text-[10px] font-bold uppercase tracking-[0.4em]">HCC Digital Registrar Secured</p>
            </footer>
        </main>
    </div>

    <script>
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('theme-icon');
            body.classList.toggle('light-mode');
            const isLight = body.classList.contains('light-mode');
            icon.className = isLight ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            localStorage.setItem('hcc_theme', isLight ? 'light' : 'dark');
        }

        if (localStorage.getItem('hcc_theme') === 'light') toggleTheme();
    </script>
</body>
</html>