<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy:   #1E3A5F; --navy-d: #162d4a; --blue: #2E75B6;
      --lblue:  #EBF3FA; --accent: #3B82F6;
      --red:    #DC2626; --green: #16A34A; --amber: #D97706;
      --text:   #111827; --muted: #6B7280; --border: #E5E7EB;
      --bg:     #F3F6FA; --white: #FFFFFF;
      --sidebar: 230px;  --header: 60px;
      --radius: 8px;     --shadow: 0 1px 4px rgba(0,0,0,.08);
    }
    body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

    .sidebar { position:fixed; top:0; left:0; width:var(--sidebar); height:100vh; background:var(--navy); display:flex; flex-direction:column; z-index:100; transition:transform .25s; }
    .sidebar-brand { padding:20px 20px 16px; border-bottom:1px solid rgba(255,255,255,.08); }
    .sidebar-brand .brand-name { font-size:14px; font-weight:700; color:#fff; }
    .sidebar-brand .brand-sub  { font-size:11px; color:#BDD7EE; margin-top:2px; }
    .sidebar-nav { flex:1; padding:12px 0; overflow-y:auto; }
    .nav-section-label { font-size:10px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#7FA9CC; padding:12px 20px 4px; }
    .nav-item { display:flex; align-items:center; gap:10px; padding:10px 20px; font-size:13.5px; font-weight:500; color:#BDD7EE; text-decoration:none; transition:background .15s, color .15s; position:relative; }
    .nav-item:hover  { background:rgba(255,255,255,.06); color:#fff; }
    .nav-item.active { background:rgba(255,255,255,.10); color:#fff; }
    .nav-item.active::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:#60A5FA; border-radius:0 2px 2px 0; }
    .nav-item svg { width:17px; height:17px; flex-shrink:0; }
    .sidebar-footer { padding:16px 20px; border-top:1px solid rgba(255,255,255,.08); }
    .user-info { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .user-avatar { width:32px; height:32px; background:rgba(255,255,255,.15); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; flex-shrink:0; }
    .user-name { font-size:13px; font-weight:600; color:#fff; }
    .user-role { font-size:11px; color:#7FA9CC; }
    .logout-btn { display:flex; align-items:center; gap:8px; font-size:13px; color:#BDD7EE; text-decoration:none; padding:6px 0; }
    .logout-btn:hover { color:#fff; }
    .logout-btn svg { width:15px; height:15px; }

    .topbar { position:fixed; top:0; left:var(--sidebar); right:0; height:var(--header); background:var(--white); border-bottom:1px solid var(--border); display:flex; align-items:center; padding:0 24px; gap:16px; z-index:90; }
    .topbar-title { font-size:16px; font-weight:700; color:var(--navy); flex:1; }
    .hamburger { display:none; background:none; border:none; cursor:pointer; padding:6px; color:var(--navy); }
    .hamburger svg { width:20px; height:20px; }

    .btn-new-booking { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:var(--navy); color:#fff; border-radius:var(--radius); font-size:13px; font-weight:600; text-decoration:none; transition:background .15s; }
    .btn-new-booking:hover { background:var(--navy-d); }
    .btn-new-booking svg { width:14px; height:14px; }

    .main { margin-left:var(--sidebar); margin-top:var(--header); padding:28px; min-height:calc(100vh - var(--header)); }

    /* Shared utility classes */
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
    .page-header h1 { font-size:22px; font-weight:700; color:var(--navy); }
    .page-header p  { font-size:13px; color:var(--muted); margin-top:2px; }
    .card { background:var(--white); border-radius:12px; box-shadow:var(--shadow); border:1px solid var(--border); }
    .card-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .card-header h2 { font-size:15px; font-weight:700; color:var(--navy); }
    .card-body { padding:20px; }
    .alert { padding:12px 16px; border-radius:var(--radius); font-size:13.5px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px; }
    .alert svg { flex-shrink:0; width:16px; height:16px; margin-top:1px; }
    .alert-success { background:#F0FDF4; color:var(--green); border:1px solid #BBF7D0; }
    .alert-error   { background:#FEF2F2; color:var(--red);   border:1px solid #FECACA; }
    .alert-info    { background:var(--lblue); color:var(--navy); border:1px solid #BFDBFE; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; font-size:13.5px; font-weight:600; font-family:inherit; border:1.5px solid transparent; border-radius:var(--radius); cursor:pointer; text-decoration:none; transition:all .15s; white-space:nowrap; }
    .btn:active { transform:scale(.97); }
    .btn svg { width:15px; height:15px; }
    .btn-primary   { background:var(--navy); color:#fff; }
    .btn-primary:hover { background:var(--navy-d); }
    .btn-secondary { background:var(--white); color:var(--text); border-color:var(--border); }
    .btn-secondary:hover { background:var(--bg); }
    .btn-danger    { background:#FEF2F2; color:var(--red); border-color:#FECACA; }
    .btn-danger:hover { background:#FEE2E2; }
    .btn-sm { padding:6px 12px; font-size:12.5px; }
    .form-group { margin-bottom:20px; }
    .form-row { display:grid; gap:20px; }
    .form-row-2 { grid-template-columns:1fr 1fr; }
    .form-row-3 { grid-template-columns:1fr 1fr 1fr; }
    label.form-label { display:block; font-size:13px; font-weight:600; color:var(--text); margin-bottom:6px; }
    label.form-label span { color:var(--red); margin-left:2px; }
    .form-control { width:100%; padding:10px 12px; font-size:14px; font-family:inherit; color:var(--text); background:var(--white); border:1.5px solid var(--border); border-radius:var(--radius); outline:none; transition:border-color .15s, box-shadow .15s; }
    .form-control:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,130,246,.12); }
    textarea.form-control { resize:vertical; min-height:80px; }
    select.form-control { appearance:none; cursor:pointer; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:36px; }
    .form-hint  { font-size:12px; color:var(--muted); margin-top:5px; }
    .error-list { background:#FEF2F2; border:1px solid #FECACA; border-radius:var(--radius); padding:12px 16px; margin-bottom:20px; }
    .error-list li { font-size:13px; color:var(--red); margin-left:16px; margin-bottom:4px; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#F9FAFB; font-size:12px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--muted); padding:10px 16px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; }
    tbody tr { border-bottom:1px solid var(--border); transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#FAFAFA; }
    tbody td { padding:12px 16px; font-size:13.5px; vertical-align:middle; }
    .facility-tag { display:inline-block; background:var(--lblue); color:var(--blue); font-size:11px; font-weight:500; padding:3px 8px; border-radius:4px; margin:2px; }
    .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:95; }
    .overlay.open { display:block; }

    @media (max-width:768px) {
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .topbar { left:0; }
      .main   { margin-left:0; padding:20px 16px; }
      .hamburger { display:flex; }
      .form-row-2, .form-row-3 { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name"><?= htmlspecialchars(APP_NAME) ?></div>
    <div class="brand-sub">Staff Portal</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="<?= APP_URL ?>/dashboard" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="<?= APP_URL ?>/calendar" class="nav-item <?= ($activePage ?? '') === 'calendar' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Availability Calendar
    </a>
    <div class="nav-section-label">Bookings</div>
    <a href="<?= APP_URL ?>/bookings/new" class="nav-item <?= ($activePage ?? '') === 'new-booking' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Booking
    </a>
    <a href="<?= APP_URL ?>/bookings" class="nav-item <?= ($activePage ?? '') === 'my-bookings' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      My Bookings
    </a>
  </nav>
  <div class="sidebar-footer">
    <?php $u = Auth::user(); ?>
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
        <div class="user-role">Staff</div>
      </div>
    </div>
    <a href="<?= APP_URL ?>/logout" class="logout-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Sign out
    </a>
  </div>
</aside>

<header class="topbar">
  <button class="hamburger" onclick="openSidebar()" aria-label="Open menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
  <a href="<?= APP_URL ?>/bookings/new" class="btn-new-booking">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Booking
  </a>
</header>

<main class="main">
