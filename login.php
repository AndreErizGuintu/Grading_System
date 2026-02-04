<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';

	$stmt = $mysqli->prepare('SELECT user_id, username, password_hash, role, is_active FROM tb_users WHERE username = ?');
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$result = $stmt->get_result();
	$user = $result->fetch_assoc();
	$stmt->close();

	if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
		login_user($user);

		if ($user['role'] === 'admin') {
			header('Location: /Grading_System/views/Admin/admindashboard.php');
		} elseif ($user['role'] === 'teacher') {
			header('Location: /Grading_System/views/Teachers/teacherdashboard.php');
		} else {
			header('Location: /Grading_System/views/Student/studentdashboard.php');
		}
		exit;
	}

	$error = 'Invalid credentials or inactive account.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Grading System - Login</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
	<div class="w-full max-w-md bg-white shadow rounded-lg p-8">
		<h1 class="text-2xl font-bold text-slate-800">Login</h1>
		<p class="text-slate-500 mb-6">Use your account to access the grading system.</p>

		<?php if ($error): ?>
			<div class="mb-4 p-3 rounded bg-rose-100 text-rose-700 text-sm">
				<?php echo h($error); ?>
			</div>
		<?php endif; ?>

		<form method="post" class="space-y-4">
			<div>
				<label class="block text-sm font-medium text-slate-700">Username</label>
				<input type="text" name="username" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-slate-300" />
			</div>
			<div>
				<label class="block text-sm font-medium text-slate-700">Password</label>
				<input type="password" name="password" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-slate-300" />
			</div>
			<button type="submit" class="w-full bg-slate-800 text-white py-2 rounded hover:bg-slate-700">Sign In</button>
		</form>
	</div>
</body>
</html>
