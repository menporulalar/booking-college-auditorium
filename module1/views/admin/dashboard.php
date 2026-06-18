<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — <?= APP_NAME ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #F3F6FA; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { background: #fff; border-radius: 16px; padding: 48px 40px; text-align: center; box-shadow: 0 4px 24px rgba(30,58,95,.10); max-width: 480px; width: 100%; }
    h1 { font-size: 22px; color: #1E3A5F; margin-bottom: 8px; }
    p  { color: #6B7280; font-size: 14px; margin-bottom: 24px; }
    .badge { display: inline-block; background: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; margin-bottom: 20px; }
    a  { display: inline-block; padding: 10px 24px; background: #1E3A5F; color: #fff; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; }
  </style>
</head>
<body>
<?php $u = Auth::user(); ?>
<div class="card">
  <div class="badge">Admin Dashboard</div>
  <h1>Welcome, <?= htmlspecialchars($u['name']) ?>!</h1>
  <p>Module 1 complete. Admin approval panel coming in Module 6.</p>
  <a href="<?= APP_URL ?>/logout">Sign out</a>
</div>
</body>
</html>
