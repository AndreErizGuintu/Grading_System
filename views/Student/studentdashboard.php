<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('student');

$user = current_user();

$stmt = $mysqli->prepare('SELECT student_id, full_name, program FROM tb_students WHERE user_id = ?');
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$syList = $mysqli->query('SELECT sy_id, school_year FROM tb_school_years ORDER BY school_year DESC')->fetch_all(MYSQLI_ASSOC);
$semList = $mysqli->query('SELECT sem_id, semester FROM tb_semesters ORDER BY sem_id ASC')->fetch_all(MYSQLI_ASSOC);

$syId = $_GET['sy_id'] ?? '';
$semId = $_GET['sem_id'] ?? '';

$studentId = (int)($student['student_id'] ?? 0);
require __DIR__ . '/../../crud/student_read.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Student Dashboard</h1>
                <p class="text-sm text-slate-500"><?php echo h($student['full_name'] ?? ''); ?> • <?php echo h($student['program'] ?? ''); ?></p>
            </div>
            <a href="/Grading_System/logout.php" class="text-sm text-slate-600 hover:text-slate-800">Logout</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8 space-y-6">
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-slate-800">Filter Grades</h2>
            <form method="get" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">School Year</label>
                    <select name="sy_id" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                        <option value="">All</option>
                        <?php foreach ($syList as $sy): ?>
                            <option value="<?php echo (int)$sy['sy_id']; ?>" <?php echo ((string)$syId === (string)$sy['sy_id']) ? 'selected' : ''; ?>>
                                <?php echo h($sy['school_year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Semester</label>
                    <select name="sem_id" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                        <option value="">All</option>
                        <?php foreach ($semList as $sem): ?>
                            <option value="<?php echo (int)$sem['sem_id']; ?>" <?php echo ((string)$semId === (string)$sem['sem_id']) ? 'selected' : ''; ?>>
                                <?php echo h($sem['semester']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="w-full bg-slate-800 text-white py-2 rounded hover:bg-slate-700">Apply Filter</button>
                </div>
            </form>
        </section>

        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-slate-800">My Grades</h2>
                <span class="text-sm text-slate-500">Raw grades + semestral + status</span>
            </div>
            <?php require __DIR__ . '/../../includes/components/student_grades_table.php'; ?>
        </section>
    </main>
</body>
</html>
