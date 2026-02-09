<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('teacher');

$user = current_user();
$stmt = $mysqli->prepare('SELECT teacher_id, full_name FROM tb_teachers WHERE user_id = ?');
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacherId = (int)($teacher['teacher_id'] ?? 0);
$message = '';

// GET FILTERS FROM URL
$filterSy = (int)($_GET['filter_sy'] ?? 0);
$filterSem = (int)($_GET['filter_sem'] ?? 0);
$activeOfferingId = (int)($_GET['offering_id'] ?? 0);

// BATCH SAVE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_save'])) {
    $offeringId = (int)($_POST['offering_id'] ?? 0);
    $check = $mysqli->prepare('SELECT offering_id FROM tb_course_offerings WHERE offering_id = ? AND teacher_id = ?');
    $check->bind_param('ii', $offeringId, $teacherId);
    $check->execute();
    $isValid = (bool)$check->get_result()->fetch_row();
    $check->close();

    if ($isValid && isset($_POST['grades'])) {
        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare(
                'INSERT INTO tb_grades (enrollment_id, prelim, midterm, finals, semestral, status) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE prelim = VALUES(prelim), midterm = VALUES(midterm), finals = VALUES(finals), semestral = VALUES(semestral), status = VALUES(status)'
            );

            foreach ($_POST['grades'] as $enrollmentId => $g) {
                // Trim and get raw input
                $prelim = trim($g['prelim']) !== '' ? trim($g['prelim']) : null;
                $midterm = trim($g['midterm']) !== '' ? trim($g['midterm']) : null;
                $finals = trim($g['finals']) !== '' ? trim($g['finals']) : null;

                // Compute semestral and status (handles both numeric and text)
                [$semestral, $status] = compute_semestral($prelim, $midterm, $finals);
                
                $stmt->bind_param('isssss', $enrollmentId, $prelim, $midterm, $finals, $semestral, $status);
                $stmt->execute();
            }
            $mysqli->commit();
            $message = 'Grades synchronized successfully!';
        } catch (Exception $e) {
            $mysqli->rollback();
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// FETCH DATA
$syList = $mysqli->query("SELECT * FROM tb_school_years ORDER BY school_year DESC")->fetch_all(MYSQLI_ASSOC);
$semList = $mysqli->query("SELECT * FROM tb_semesters ORDER BY sem_id ASC")->fetch_all(MYSQLI_ASSOC);

$sql = 'SELECT o.offering_id, c.course_code, c.course_name, sy.school_year, s.semester 
        FROM tb_course_offerings o
        INNER JOIN tb_courses c ON c.course_id = o.course_id
        INNER JOIN tb_school_years sy ON sy.sy_id = o.sy_id
        INNER JOIN tb_semesters s ON s.sem_id = o.sem_id
        WHERE o.teacher_id = ?';

$params = [$teacherId];
$types = 'i';
if ($filterSy > 0) { $sql .= ' AND o.sy_id = ?'; $params[] = $filterSy; $types .= 'i'; }
if ($filterSem > 0) { $sql .= ' AND o.sem_id = ?'; $params[] = $filterSem; $types .= 'i'; }

$sql .= ' ORDER BY sy.school_year DESC, s.semester ASC';
$offStmt = $mysqli->prepare($sql);
$offStmt->bind_param($types, ...$params);
$offStmt->execute();
$offerings = $offStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$students = [];
if ($activeOfferingId) {
    $enrollStmt = $mysqli->prepare(
        'SELECT e.enrollment_id, st.student_no, st.full_name, g.prelim, g.midterm, g.finals, g.semestral, g.status
         FROM tb_enrollments e
         INNER JOIN tb_students st ON st.student_id = e.student_id
         LEFT JOIN tb_grades g ON g.enrollment_id = e.enrollment_id
         WHERE e.offering_id = ? ORDER BY st.full_name'
    );
    $enrollStmt->bind_param('i', $activeOfferingId);
    $enrollStmt->execute();
    $students = $enrollStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HCC Faculty | Grade Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        :root { 
            --bg: #030712; 
            --card: #111827; 
            --accent: #6366f1; 
            --border: rgba(255, 255, 255, 0.08); 
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg); 
            color: #f3f4f6;
            background-image: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent),
                              radial-gradient(circle at bottom left, rgba(168, 85, 247, 0.05), transparent);
        }

        .glass-panel { 
            background: rgba(17, 24, 39, 0.7); 
            backdrop-filter: blur(12px); 
            border: 1px solid var(--border);
        }

        .grade-input { 
            background: rgba(0, 0, 0, 0.2); 
            border: 1px solid var(--border); 
            transition: all 0.3s ease; 
        }

        .grade-input:focus { 
            border-color: var(--accent); 
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            background: rgba(0, 0, 0, 0.4);
        }

        .nav-link { transition: all 0.2s ease; }
        .nav-link.active { background: linear-gradient(to right, rgba(99, 102, 241, 0.1), transparent); border-left: 3px solid var(--accent); }

        .badge { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; }
        .badge-passed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-failed { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .badge-inc { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen">

    <aside class="w-72 hidden lg:flex flex-col bg-[#030712] border-r border-white/5 h-screen sticky top-0 z-50">
    <div class="p-8">
        <div class="mb-10 flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 flex-shrink-0 transition-transform group-hover:scale-110 duration-300">
                <img src="https://hccp-sms.holycrosscollegepampanga.edu.ph/public/assets/images/logo4.png" 
                     alt="HCC Logo" 
                     class="w-full h-full object-contain filter drop-shadow-[0_0_8px_rgba(99,102,241,0.3)]">
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight leading-none text-white">HCC <span class="text-indigo-500">FACULTY</span></h1>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em] mt-1">Portal System</p>
            </div>
        </div>

        <nav class="space-y-6">
            <div>
                <p class="text-[10px] font-black text-gray-600 uppercase mb-4 px-4 tracking-[0.2em]">Academic Tools</p>
                <div class="space-y-1">
                    <a href="teacherdashboard.php" class="nav-link flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-chart-pie text-sm group-hover:text-indigo-400 transition-colors"></i> 
                            <span class="text-sm font-semibold">Dashboard</span>
                        </div>
                    </a>
                    
                    <a href="grade_management.php" class="nav-link active flex items-center justify-between px-4 py-3 rounded-xl text-indigo-400">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-layer-group text-sm"></i> 
                            <span class="text-sm font-semibold">Grade Encoding</span>
                        </div>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shadow-[0_0_8px_#6366f1]"></span>
                    </a>

                    <a href="attendance.php" class="nav-link flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-clipboard-user text-sm group-hover:text-indigo-400 transition-colors"></i> 
                            <span class="text-sm font-semibold">Attendance</span>
                        </div>
                    </a>

                    <a href="schedule.php" class="nav-link flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-calendar-day text-sm group-hover:text-indigo-400 transition-colors"></i> 
                            <span class="text-sm font-semibold">Class Schedule</span>
                        </div>
                    </a>
                </div>
            </div>

            <div>
                <p class="text-[10px] font-black text-gray-600 uppercase mb-4 px-4 tracking-[0.2em]">Insights & Account</p>
                <div class="space-y-1">
                    <a href="reports.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 group">
                        <i class="fa-solid fa-file-invoice text-sm group-hover:text-indigo-400 transition-colors"></i> 
                        <span class="text-sm font-semibold">Grade Reports</span>
                    </a>
                    <a href="settings.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 group">
                        <i class="fa-solid fa-gears text-sm group-hover:text-indigo-400 transition-colors"></i> 
                        <span class="text-sm font-semibold">Settings</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>
    
    <div class="mt-auto p-6">
        <div class="glass-panel p-4 rounded-2xl border-indigo-500/10 bg-indigo-500/[0.03]">
            <div class="flex items-center gap-3 mb-4">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                        <?php echo substr($teacher['full_name'] ?? 'U', 0, 1); ?>
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-[#030712] rounded-full"></span>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($teacher['full_name'] ?? 'User'); ?></p>
                    <p class="text-[9px] text-indigo-400 font-bold uppercase tracking-tighter">Active Faculty</p>
                </div>
            </div>
            <a href="/Grading_System/logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-red-500/10 text-red-500 text-[10px] font-black uppercase hover:bg-red-500 hover:text-white transition-all duration-300 border border-red-500/20 group">
                <i class="fa-solid fa-power-off group-hover:rotate-90 transition-transform"></i> Sign Out
            </a>
        </div>
    </div>
</aside>

    <div class="flex-1 flex flex-col">
        <header class="h-20 px-10 flex items-center justify-between sticky top-0 bg-[#030712]/80 backdrop-blur-xl z-50 border-b border-white/5">
            <div>
                <h2 class="text-xs font-bold text-indigo-500 uppercase tracking-widest">Portal / Grades</h2>
            </div>
            
            <form method="GET" class="flex gap-3">
                <div class="relative">
                    <select name="filter_sy" onchange="this.form.submit()" class="appearance-none bg-gray-900 border border-white/5 text-[11px] font-bold text-gray-300 pl-4 pr-10 py-2.5 rounded-xl focus:border-indigo-500 outline-none cursor-pointer">
                        <option value="0">All School Years</option>
                        <?php foreach ($syList as $sy): ?>
                            <option value="<?= $sy['sy_id']; ?>" <?= $filterSy == $sy['sy_id'] ? 'selected' : ''; ?>><?= $sy['school_year']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-[9px] text-gray-500 pointer-events-none"></i>
                </div>
            </form>
        </header>

        <main class="p-8 lg:p-12 max-w-[1400px] mx-auto w-full">
            <?php if ($message): ?>
                <div class="mb-8 p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-bold flex items-center gap-3 animate-pulse">
                    <i class="fa-solid fa-circle-info text-lg"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Grade Entry Guide -->
            <div class="mb-8 glass-panel p-6 rounded-2xl">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-lightbulb text-amber-500 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-white mb-3">Grade Entry Guide</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-[10px]">
                            <div class="bg-black/20 rounded-lg px-3 py-2 border border-white/5">
                                <span class="font-black text-amber-400">INC</span>
                                <span class="text-gray-500 block mt-0.5">Incomplete</span>
                            </div>
                            <div class="bg-black/20 rounded-lg px-3 py-2 border border-white/5">
                                <span class="font-black text-gray-400">DROP</span>
                                <span class="text-gray-500 block mt-0.5">Dropped</span>
                            </div>
                            <div class="bg-black/20 rounded-lg px-3 py-2 border border-white/5">
                                <span class="font-black text-blue-400">WP</span>
                                <span class="text-gray-500 block mt-0.5">Withdrawn Pass</span>
                            </div>
                            <div class="bg-black/20 rounded-lg px-3 py-2 border border-white/5">
                                <span class="font-black text-red-400">WF</span>
                                <span class="text-gray-500 block mt-0.5">Withdrawn Fail</span>
                            </div>
                            <div class="bg-black/20 rounded-lg px-3 py-2 border border-white/5">
                                <span class="font-black text-purple-400">ABS</span>
                                <span class="text-gray-500 block mt-0.5">Absent</span>
                            </div>
                            <div class="bg-black/20 rounded-lg px-3 py-2 border border-white/5">
                                <span class="font-black text-gray-400">NA</span>
                                <span class="text-gray-500 block mt-0.5">Not Applicable</span>
                            </div>
                        </div>
                        <p class="text-gray-500 text-[10px] mt-3 italic">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            Enter numeric grades (0-100) or use text codes above. System auto-formats on blur.
                        </p>
                    </div>
                </div>
            </div>

            <?php if (!$activeOfferingId): ?>
                <div class="mb-10">
                    <h3 class="text-2xl font-extrabold text-white">My Offerings</h3>
                    <p class="text-gray-500 text-sm mt-1">Select a course to start encoding student grades.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($offerings as $off): ?>
                        <a href="?offering_id=<?= $off['offering_id']; ?>&filter_sy=<?= $filterSy; ?>&filter_sem=<?= $filterSem; ?>" 
                           class="glass-panel p-8 rounded-3xl group hover:-translate-y-2 hover:border-indigo-500/40 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-20 transition-opacity">
                                <i class="fa-solid fa-book-open text-6xl"></i>
                            </div>
                            <span class="inline-block text-[10px] font-black text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full mb-4"><?= $off['course_code']; ?></span>
                            <h4 class="font-bold text-white text-lg leading-tight mb-6"><?= $off['course_name']; ?></h4>
                            <div class="flex items-center gap-4 text-[11px] font-bold text-gray-500">
                                <span class="flex items-center gap-2"><i class="fa-regular fa-calendar"></i> <?= $off['school_year']; ?></span>
                                <span class="flex items-center gap-2"><i class="fa-regular fa-clock"></i> <?= $off['semester']; ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="flex items-center gap-4 mb-8">
                    <a href="?filter_sy=<?= $filterSy; ?>&filter_sem=<?= $filterSem; ?>" class="w-10 h-10 rounded-full border border-white/5 flex items-center justify-center hover:bg-white/5 transition-all">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                    </a>
                    <div>
                        <h3 class="text-2xl font-bold tracking-tight text-white">Class Roster</h3>
                        <p class="text-xs font-medium text-gray-500 mt-1 uppercase tracking-tighter italic"><?= count($students); ?> Students Enrolled</p>
                    </div>
                </div>

                <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl">
                    <form method="POST" id="gradeForm">
                        <input type="hidden" name="offering_id" value="<?= $activeOfferingId; ?>">
                        
                        <div class="p-6 border-b border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white/[0.01]">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <p class="text-[11px] font-bold text-gray-400">Live Editing Mode</p>
                            </div>
                            <button type="submit" name="batch_save" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-3 rounded-2xl text-[11px] font-black uppercase shadow-lg shadow-indigo-500/20 transition-all flex items-center justify-center gap-3 group">
                                <i class="fa-solid fa-cloud-arrow-up group-hover:translate-y-[-2px] transition-transform"></i> Save All Grades
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-black text-gray-500 uppercase tracking-widest bg-black/20">
                                        <th class="px-8 py-5">Student Information</th>
                                        <th class="text-center w-32">Prelim</th>
                                        <th class="text-center w-32">Midterm</th>
                                        <th class="text-center w-32">Finals</th>
                                        <th class="text-center w-32">Average</th>
                                        <th class="text-center px-8 w-40">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($students as $st): 
                                        $p_val = ($st['status'] == 'INCOMPLETE' && is_null($st['prelim'])) ? 'INC' : ($st['prelim'] ?? '');
                                        $m_val = ($st['status'] == 'INCOMPLETE' && is_null($st['midterm'])) ? 'INC' : ($st['midterm'] ?? '');
                                        $f_val = ($st['status'] == 'INCOMPLETE' && is_null($st['finals'])) ? 'INC' : ($st['finals'] ?? '');
                                    ?>
                                        <tr class="group hover:bg-white/[0.02] transition-colors">
                                            <td class="px-8 py-6">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-white group-hover:text-indigo-400 transition-colors uppercase"><?= $st['full_name']; ?></span>
                                                    <span class="text-[10px] text-gray-600 font-mono mt-1 tracking-widest"><?= $st['student_no']; ?></span>
                                                </div>
                                            </td>
                                            <td class="py-4 text-center">
                                                <input type="text" name="grades[<?= $st['enrollment_id']; ?>][prelim]" 
                                                       class="grade-input w-20 p-3 rounded-xl text-center font-bold text-sm text-indigo-400" 
                                                       value="<?= $p_val; ?>" placeholder="—" onblur="formatInput(this)">
                                            </td>
                                            <td class="py-4 text-center">
                                                <input type="text" name="grades[<?= $st['enrollment_id']; ?>][midterm]" 
                                                       class="grade-input w-20 p-3 rounded-xl text-center font-bold text-sm text-indigo-400" 
                                                       value="<?= $m_val; ?>" placeholder="—" onblur="formatInput(this)">
                                            </td>
                                            <td class="py-4 text-center">
                                                <input type="text" name="grades[<?= $st['enrollment_id']; ?>][finals]" 
                                                       class="grade-input w-20 p-3 rounded-xl text-center font-bold text-sm text-indigo-400" 
                                                       value="<?= $f_val; ?>" placeholder="—" onblur="formatInput(this)">
                                            </td>
                                            <td class="py-4 text-center">
                                                <span class="text-sm font-black text-white">
                                                    <?= !is_null($st['semestral']) ? (is_numeric($st['semestral']) ? number_format($st['semestral'], 2) : strtoupper($st['semestral'])) : '—'; ?>
                                                </span>
                                            </td>
                                            <td class="px-8 py-4 text-center">
                                                <?php 
                                                    $s = $st['status'] ?? 'PENDING';
                                                    $badge_class = 'badge-inc';
                                                    if($s == 'PASSED') $badge_class = 'badge-passed';
                                                    if($s == 'FAILED') $badge_class = 'badge-failed';
                                                ?>
                                                <span class="badge <?= $badge_class; ?>"><?= $s; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function formatInput(input) {
        let val = input.value.toUpperCase().trim();
        input.style.borderColor = ''; 
        input.style.background = '';

        if (val === 'INC') {
            input.value = 'INC';
            input.style.color = '#f59e0b'; 
            input.style.borderColor = 'rgba(245, 158, 11, 0.3)';
        } else if (val !== '' && !isNaN(val)) {
            input.value = parseFloat(val).toFixed(2);
            input.style.color = '#818cf8'; 
        } else {
            input.value = '';
            input.style.color = '';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.grade-input').forEach(input => formatInput(input));
    });
    </script>
</body>
</html>