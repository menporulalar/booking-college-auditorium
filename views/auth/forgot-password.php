<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — <?= APP_NAME ?></title>
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
      padding: 28px 36px;
    }

    .card-header h1 { font-size: 18px; font-weight: 700; color: var(--white); }
    .card-header p  { font-size: 13px; color: #BDD7EE; margin-top: 4px; }

    /* Step indicator */
    .steps {
      display: flex;
      align-items: center;
      gap: 0;
      padding: 20px 36px 0;
    }

    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      position: relative;
    }

    .step:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 14px;
      left: 50%;
      width: 100%;
      height: 2px;
      background: var(--border);
      z-index: 0;
    }

    .step.done:not(:last-child)::after,
    .step.active:not(:last-child)::after {
      background: var(--blue);
    }

    .step-dot {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 2px solid var(--border);
      background: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      z-index: 1;
      transition: all .2s;
    }

    .step.active .step-dot {
      border-color: var(--blue);
      background: var(--blue);
      color: var(--white);
    }

    .step.done .step-dot {
      border-color: var(--green);
      background: var(--green);
      color: var(--white);
    }

    .step-label {
      font-size: 11px;
      color: var(--muted);
      margin-top: 5px;
      white-space: nowrap;
    }

    .step.active .step-label { color: var(--navy); font-weight: 600; }

    .card-body { padding: 28px 36px 32px; }

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

    .alert svg { flex-shrink: 0; width: 16px; height: 16px; margin-top: 1px; }

    .step-title    { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
    .step-subtitle { font-size: 13px; color: var(--muted); margin-bottom: 22px; line-height: 1.5; }

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

    /* OTP input special style */
    .otp-input {
      text-align: center;
      font-size: 28px !important;
      font-weight: 700 !important;
      letter-spacing: 12px;
      padding: 14px 12px !important;
    }

    .otp-hint {
      font-size: 12px;
      color: var(--muted);
      margin-top: 6px;
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

    .password-strength { margin-top: 8px; }

    .strength-bar {
      height: 4px;
      border-radius: 2px;
      background: var(--border);
      overflow: hidden;
      margin-bottom: 4px;
    }

    .strength-fill {
      height: 100%;
      border-radius: 2px;
      transition: width .3s, background .3s;
      width: 0%;
    }

    .strength-text { font-size: 11px; color: var(--muted); }

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
      margin-top: 4px;
    }

    .btn-primary:hover { background: #162d4a; }

    .back-link {
      display: block;
      text-align: center;
      font-size: 13px;
      color: var(--blue);
      text-decoration: none;
      margin-top: 16px;
    }

    .back-link:hover { text-decoration: underline; }

    @media (max-width: 480px) {
      .card-header, .card-body { padding-left: 24px; padding-right: 24px; }
      .steps { padding-left: 24px; padding-right: 24px; }
    }
  </style>
</head>
<body>

<?php
$step = $_SESSION['reset_step'] ?? 'email';
$stepNum = match($step) { 'email' => 1, 'otp' => 2, 'password' => 3, default => 1 };
?>

<div class="card">
  <div class="card-header">
    <h1>Reset Password</h1>
    <p><?= htmlspecialchars(APP_NAME) ?></p>
  </div>

  <!-- Step indicator -->
  <div class="steps">
    <?php
    $steps = ['Email', 'Verify OTP', 'New Password'];
    foreach ($steps as $i => $label):
      $n = $i + 1;
      $cls = $n < $stepNum ? 'done' : ($n === $stepNum ? 'active' : '');
    ?>
    <div class="step <?= $cls ?>">
      <div class="step-dot">
        <?php if ($n < $stepNum): ?>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <?php else: ?>
          <?= $n ?>
        <?php endif; ?>
      </div>
      <div class="step-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card-body">

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- ── Step 1: Email ───────────────────────────── -->
    <?php if ($step === 'email'): ?>

    <p class="step-title">Enter your college email</p>
    <p class="step-subtitle">We'll send a 6-digit OTP to your registered email address.</p>

    <form method="POST" action="<?= APP_URL ?>/forgot-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="form-group">
        <label for="email">College Email</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          <input type="email" id="email" name="email" placeholder="you@college.edu" autocomplete="email" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Send OTP</button>
    </form>

    <!-- ── Step 2: OTP ─────────────────────────────── -->
    <?php elseif ($step === 'otp'): ?>

    <p class="step-title">Enter the OTP</p>
    <p class="step-subtitle">Enter the 6-digit code sent to your email. It expires in <?= OTP_EXPIRY_MINUTES ?> minutes.</p>

    <form method="POST" action="<?= APP_URL ?>/forgot-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="form-group">
        <label for="otp">6-Digit OTP</label>
        <div class="input-wrap">
          <input
            type="text"
            id="otp"
            name="otp"
            class="otp-input"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            placeholder="——————"
            autocomplete="one-time-code"
            required
          >
        </div>
        <p class="otp-hint">Check your spam folder if you don't see the email.</p>
      </div>

      <button type="submit" class="btn btn-primary">Verify OTP</button>
    </form>

    <!-- ── Step 3: New password ────────────────────── -->
    <?php elseif ($step === 'password'): ?>

    <p class="step-title">Set a new password</p>
    <p class="step-subtitle">Choose a strong password — at least 8 characters with one number.</p>

    <form method="POST" action="<?= APP_URL ?>/forgot-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="form-group">
        <label for="password">New Password</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="New password"
            oninput="checkStrength(this.value)"
            autocomplete="new-password"
            required
          >
          <button type="button" class="toggle-password" onclick="togglePwd('password')" aria-label="Toggle password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="password-strength">
          <div class="strength-bar"><div class="strength-fill" id="str-fill"></div></div>
          <p class="strength-text" id="str-text"></p>
        </div>
      </div>

      <div class="form-group">
        <label for="password_confirm">Confirm Password</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input
            type="password"
            id="password_confirm"
            name="password_confirm"
            placeholder="Repeat password"
            autocomplete="new-password"
            required
          >
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>

    <?php endif; ?>

    <a href="<?= APP_URL ?>/login" class="back-link">← Back to sign in</a>

  </div>
</div>

<script>
function togglePwd(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

function checkStrength(val) {
  const fill = document.getElementById('str-fill');
  const text = document.getElementById('str-text');
  let score = 0;
  if (val.length >= 8)             score++;
  if (/[0-9]/.test(val))           score++;
  if (/[A-Z]/.test(val))           score++;
  if (/[^A-Za-z0-9]/.test(val))    score++;

  const levels = [
    { pct: '0%',   color: '#D1D5DB', label: '' },
    { pct: '30%',  color: '#DC2626', label: 'Weak' },
    { pct: '55%',  color: '#F59E0B', label: 'Fair' },
    { pct: '75%',  color: '#3B82F6', label: 'Good' },
    { pct: '100%', color: '#16A34A', label: 'Strong' },
  ];
  const lvl = levels[score] || levels[0];
  fill.style.width = lvl.pct;
  fill.style.background = lvl.color;
  text.textContent = lvl.label;
  text.style.color = lvl.color;
}

// Auto-format OTP input: digits only
const otpInput = document.getElementById('otp');
if (otpInput) {
  otpInput.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
  });
}
</script>

</body>
</html>
