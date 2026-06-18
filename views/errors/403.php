<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden — <?= APP_NAME ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #F3F6FA; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { background: #fff; border-radius: 16px; padding: 48px 40px; text-align: center; box-shadow: 0 4px 24px rgba(30,58,95,.10); max-width: 420px; width: 100%; }
    .code { font-size: 64px; font-weight: 700; color: #FCA5A5; line-height: 1; margin-bottom: 16px; }
    h1 { font-size: 20px; color: #1E3A5F; margin-bottom: 8px; }
    p  { color: #6B7280; font-size: 14px; margin-bottom: 24px; }
    a  { display: inline-block; padding: 10px 24px; background: #1E3A5F; color: #fff; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; }
  </style>
</head>
<body>
<div class="card">
  <div class="code">403</div>
  <h1>Access Denied</h1>
  <p>You don't have permission to view this page.</p>
  <a href="<?= APP_URL ?>/dashboard">Go to Dashboard</a>
</div>
</body>
</html>
