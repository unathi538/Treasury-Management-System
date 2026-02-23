<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

start_secure_session();
if (!empty($_SESSION['user_id'])) {
    header('Location: /backend/app.php');
    exit;
}

$csrf = generate_csrf_token();
$error = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CommVault</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #020617);
            color: #e5e7eb;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: rgba(15, 23, 42, 0.95);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.05);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            color: #60a5fa;
        }

        .subtitle {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .error {
            background: rgba(220,38,38,0.15);
            border: 1px solid rgba(220,38,38,0.4);
            color: #fca5a5;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: #cbd5f5;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 6px;
            border: 1px solid #1e293b;
            background: #020617;
            color: #e5e7eb;
            outline: none;
            transition: 0.2s;
        }

        input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            margin-bottom: 15px;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-google {
            background: white;
            color: #111827;
            text-decoration: none;
            display: block;
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .btn-google:hover {
            background: #f3f4f6;
        }

        .divider {
            text-align: center;
            margin: 15px 0;
            color: #64748b;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
        }

        .footer a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                margin: 20px;
                padding: 25px;
            }
        }

    </style>

</head>
<body>

<div class="container">

    <div class="logo">CommVault</div>
    <div class="subtitle">Create your account</div>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/backend/auth/register.php">

        <input type="hidden"
               name="csrf_token"
               value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

        <label>Name</label>
        <input type="text"
               name="name"
               maxlength="190"
               placeholder="Your name">

        <label>Email</label>
        <input type="email"
               name="email"
               required
               placeholder="you@example.com">

        <label>Password</label>
        <input type="password"
               name="password"
               required
               minlength="8"
               placeholder="Minimum 8 characters">

        <button class="btn btn-primary" type="submit">
            Create Account
        </button>

    </form>

    <!--<div class="divider">or</div>

  Optional: only works if you actually implemented google.php --
  <a class="btn-google" href="/backend/auth/google.php">Continue with Google</a>-->

    <div class="footer">
        Already have an account?
        <a href="/backend/login.php">Login</a>
    </div>

</div>

</body>
</html>
