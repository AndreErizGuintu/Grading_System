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
    <title>HCC - Portal Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700&display=swap');
        
        :root {
            --hcc-blue: #0a1d56;
            --hcc-accent: #4f46e5;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #c4c1c1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            height: 720px;
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            display: flex;
        }
        
        .left-panel {
            flex: 1.1;
            padding: 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-title {
            font-family: 'Outfit', sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: #1a202c;
            letter-spacing: -0.02em;
        }
        
        .right-panel {
            flex: 0.9;
            background-color: var(--hcc-blue);
            background-image: 
                linear-gradient(rgba(10, 29, 86, 0.9), rgba(10, 29, 86, 0.98)),
                url('https://holycrosscollegepampanga.edu.ph/wp-content/uploads/2026/01/c4cd126f-88ad-498e-9582-f905779a1568-2-1.png');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .college-logo-container {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 14px;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--hcc-accent);
            background: white;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .role-tab {
            font-size: 13px;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            color: #64748b;
        }

        .role-tab.active {
            background: white;
            color: var(--hcc-blue);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 14px 18px;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.12);
        }

        .feature-icon {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #818cf8;
        }

        @media (max-width: 950px) {
            .login-wrapper { height: auto; flex-direction: column; max-width: 450px; }
            .right-panel { padding: 50px 30px; order: 2; }
            .left-panel { padding: 40px 30px; order: 1; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="left-panel">
            <div class="mb-8">
                <h1 class="welcome-title">Portal Login</h1>
                <p class="text-slate-500 text-sm mt-1">Sign in to manage your academic profile.</p>
            </div>
            
            <div class="flex bg-slate-100 p-1.5 rounded-2xl mb-8 w-fit">
                <div class="role-tab active" data-role="student">Student</div>
                <div class="role-tab" data-role="professor">Faculty</div>
                <div class="role-tab" data-role="admin">Admin</div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 px-4 py-3 rounded-xl text-sm mb-6 border border-red-100 flex items-center gap-3">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Identification</label>
                    <input type="text" name="username" id="username-input" required class="form-input" placeholder="ID Number or Email">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Secret Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password-input" required class="form-input" placeholder="••••••••">
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-600 group-hover:text-slate-900">Remember me</span>
                    </label>
                    <a href="#" class="text-sm font-semibold text-indigo-600 hover:underline">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full bg-[#0a1d56] text-white py-4 rounded-2xl font-bold text-md hover:bg-[#162a6b] transition-all flex justify-center items-center gap-3 mt-4">
                    Sign In to Portal
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>
            
            <div class="mt-auto pt-8 border-t border-slate-100 flex justify-between items-center text-[12px] text-slate-400 font-medium">
                <span>© 2026 HOLY CROSS COLLEGE</span>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-slate-600">Privacy</a>
                    <a href="#" class="hover:text-slate-600">Support</a>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="college-logo-container">
                <img src="https://hccp-sms.holycrosscollegepampanga.edu.ph/public/assets/images/logo4.png" alt="HCC Logo" class="w-full">
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-center uppercase">Holy Cross College</h2>
            <p class="text-indigo-200 text-xs mt-2 font-medium tracking-[0.2em] uppercase">Sta. Ana, Pampanga</p>
            
            <div class="mt-10 space-y-4 w-full max-w-[320px]">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <p class="text-sm font-bold">Secure Access</p>
                        <p class="text-[11px] text-indigo-200">Protected by 256-bit encryption</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <p class="text-sm font-bold">Real-time Grade Sync</p>
                        <p class="text-[11px] text-indigo-200">Get instant updates on your grades</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <p class="text-sm font-bold">Attendance Tracking</p>
                        <p class="text-[11px] text-indigo-200">Monitor your daily class presence</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <p class="text-sm font-bold">Digital Clearance</p>
                        <p class="text-[11px] text-indigo-200">Hassle-free semester processing</p>
                    </div>
                </div>
            </div>

            <p class="mt-10 text-[11px] text-indigo-300 italic opacity-70">
                "Caritas et Scientia — Love and Knowledge"
            </p>
        </div>
    </div>

    <script>
        document.querySelectorAll('.role-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // UI Toggle
                document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Change Placeholder based on role
                const input = document.getElementById('username-input');
                const role = this.getAttribute('data-role');
                
                if (role === 'student') {
                    input.placeholder = "ID Number or Email";
                } else if (role === 'professor') {
                    input.placeholder = "Faculty ID or Email";
                } else if (role === 'admin') {
                    input.placeholder = "Admin Username or Email";
                }
            });
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Authenticating...';
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        });
    </script>
</body>
</html>