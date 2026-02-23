<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CommVault — Treasurer Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Syne:wght@400;600;700&display=swap" rel="stylesheet"/>
  <style>
    :root{--ink:#120E0A;--ivory:#F9F6F1;--white:#fff;--line:rgba(18,14,10,.14);--green:#1A7A45;--green2:#22A05A;--red:#C0392B;--soft:#8A7F78;}
    *{box-sizing:border-box}
    body{margin:0;background:var(--ivory);font-family:'Syne',sans-serif;color:var(--ink)}
    .wrap{max-width:520px;margin:0 auto;padding:44px 22px}
    .card{background:var(--white);border:1px solid var(--line);border-radius:18px;overflow:hidden}
    .head{padding:26px 26px 18px;border-bottom:1px solid rgba(18,14,10,.08)}
    h1{margin:0;font-family:'Cormorant Garamond',serif;font-size:2rem}
    .sub{margin-top:6px;color:var(--soft);font-size:.9rem}
    .body{padding:22px 26px 26px}
    label{display:block;font-size:.68rem;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:var(--soft);margin:14px 0 8px}
    input{width:100%;padding:14px 14px;border-radius:10px;border:1.5px solid rgba(18,14,10,.2);background:var(--ivory);outline:none;font-size:.95rem}
    input:focus{border-color:var(--green2);box-shadow:0 0 0 3px rgba(34,160,90,.22);background:#fff}
    button{width:100%;margin-top:18px;padding:14px 16px;border:0;border-radius:10px;cursor:pointer;background:linear-gradient(135deg,var(--green),var(--green2));color:#fff;font-weight:800;letter-spacing:.8px;text-transform:uppercase;font-size:.78rem}
    .msg{margin-top:14px;font-size:.9rem}
    .msg.err{color:var(--red)}
    .msg.ok{color:var(--green)}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="head">
        <h1>Treasurer Login</h1>
        <div class="sub">Only authorized treasurers can access the dashboard.</div>
      </div>
      <div class="body">
        <label for="email">Email</label>
        <input id="email" type="email" placeholder="treasurer@example.com" autocomplete="username"/>

        <label for="password">Password</label>
        <input id="password" type="password" placeholder="••••••••" autocomplete="current-password"/>

        <button id="btn">Sign in</button>
        <div id="msg" class="msg"></div>
      </div>
    </div>
  </div>

  <script>
    async function login() {
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const msg = document.getElementById('msg');
      msg.textContent = '';
      msg.className = 'msg';

      if (!email || !password) {
        msg.textContent = 'Enter email and password.';
        msg.classList.add('err');
        return;
      }

      try {
        const res = await fetch('/backend/api/treasurers/login.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ email, password })
        });

        const text = await res.text();
        let out;
        try { out = JSON.parse(text); } catch { throw new Error('Bad JSON: ' + text.slice(0, 200)); }
        if (!out.ok) throw new Error(out.error || 'Login failed');

        msg.textContent = 'Login successful. Redirecting…';
        msg.classList.add('ok');
        setTimeout(() => window.location.href = 'treasurer.html', 600);
      } catch (e) {
        msg.textContent = e.message || 'Login failed';
        msg.classList.add('err');
      }
    }

    document.getElementById('btn').addEventListener('click', login);
    document.getElementById('password').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') login();
    });
  </script>
</body>
</html>