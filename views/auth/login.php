<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:   #1E3A5F;
      --blue:   #2E75B6;
      --lblue:  #EBF3FA;
      --accent: #3B82F6;
      --red:    #DC2626;
      --green:  #16A34A;
      --text:   #111827;
      --muted:  #6B7280;
      --border: #D1D5DB;
      --bg:     #F3F6FA;
      --white:  #FFFFFF;
      --radius: 10px;
      --shadow: 0 4px 24px rgba(30,58,95,.10);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }

    .card {
      background: var(--white);
      border-radius: 16px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 420px;
      overflow: hidden;
    }

    .card-header {
      background: var(--navy);
      padding: 32px 36px 28px;
    }

    .card-header .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 4px;
    }

    .card-header .logo-icon {
      width: 40px;
      height: 40px;
      background: rgba(255,255,255,.15);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card-header .logo-icon svg { width: 22px; height: 22px; fill: #BDD7EE; }

    .card-header h1 {
      font-size: 18px;
      font-weight: 700;
      color: var(--white);
    }

    .card-header p {
      font-size: 13px;
      color: #BDD7EE;
      margin-top: 6px;
    }

    .card-body { padding: 32px 36px; }

    .alert {
      padding: 12px 16px;
      border-radius: var(--radius);
      font-size: 13.5px;
      margin-bottom: 20px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    .alert-error   { background: #FEF2F2; color: var(--red);   border: 1px solid #FECACA; }
    .alert-success { background: #F0FDF4; color: var(--green); border: 1px solid #BBF7D0; }
    .alert-info    { background: var(--lblue); color: var(--navy); border: 1px solid #BFDBFE; }

    .alert svg { flex-shrink: 0; width: 16px; height: 16px; margin-top: 1px; }

    .form-group { margin-bottom: 18px; }

    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 6px;
    }

    .input-wrap { position: relative; }

    .input-wrap svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      color: var(--muted);
      pointer-events: none;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 11px 12px 11px 38px;
      font-size: 14px;
      font-family: inherit;
      color: var(--text);
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      outline: none;
      transition: border-color .15s, box-shadow .15s;
    }

    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    }

    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      color: var(--muted);
      display: flex;
    }

    .toggle-password svg { width: 18px; height: 18px; }

    .btn {
      display: block;
      width: 100%;
      padding: 12px;
      font-size: 15px;
      font-weight: 600;
      font-family: inherit;
      border: none;
      border-radius: var(--radius);
      cursor: pointer;
      transition: background .15s, transform .1s;
      text-align: center;
      text-decoration: none;
    }

    .btn:active { transform: scale(.98); }

    .btn-primary {
      background: var(--navy);
      color: var(--white);
      margin-top: 6px;
    }

    .btn-primary:hover { background: #162d4a; }

    .forgot-link {
      display: block;
      text-align: right;
      font-size: 12.5px;
      color: var(--blue);
      text-decoration: none;
      margin-top: -10px;
      margin-bottom: 18px;
    }

    .forgot-link:hover { text-decoration: underline; }

    .card-footer {
      padding: 16px 36px 28px;
      text-align: center;
      font-size: 12px;
      color: var(--muted);
      border-top: 1px solid #F3F4F6;
    }

    @media (max-width: 480px) {
      .card-header, .card-body { padding-left: 24px; padding-right: 24px; }
      .card-footer { padding-left: 24px; padding-right: 24px; }
    }
  </style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <div class="logo">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 19h18v2H3v-2zm2-6h2v5H5v-5zm4 0h2v5H9v-5zm4 0h2v5h-2v-5zm4 0h2v5h-2v-5zM3 9l9-7 9 7H3zm9-4.9L6.3 9h11.4L12 4.1z"/>
        </svg>
      </div>
      <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    </div>
    <p>College Auditorium Management System</p>
  </div>

  <div class="card-body">

    <?php if ($timeout): ?>
    <div class="alert alert-info">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
      You were logged out due to inactivity.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
      <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <form method="POST" action="<?= APP_URL ?>/login" novalidate>
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="form-group">
        <label for="email">College Email</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="you@college.edu"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            autocomplete="email"
            required
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
          <button type="button" class="toggle-password" onclick="togglePwd()" title="Show/hide password" aria-label="Toggle password visibility">
            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <a href="<?= APP_URL ?>/forgot-password" class="forgot-link">Forgot password?</a>

      <button type="submit" class="btn btn-primary">Sign in</button>
    </form>
  </div>

  <div class="card-footer">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.
  </div>
</div>

<script>
function togglePwd() {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('eye-icon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>

</body>
</html>
