<?php
/** Admin: session, auth, CSRF and page layout. */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

const IDLE_TIMEOUT = 8 * 3600;

function admin_boot(): void {
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_name('tfadmin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/admin/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    ini_set('session.use_strict_mode', '1');
    session_start();
    if (!empty($_SESSION['uid']) && time() - ($_SESSION['last'] ?? 0) > IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Byli jste odhlášeni po delší nečinnosti.', 'info');
    }
    $_SESSION['last'] = time();
}

function current_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT id, username, name, created_at, last_login FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([now(), $user['id']]);
}

function users_count(): int { return (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Neplatný bezpečnostní token formuláře. Obnovte stránku a zkuste to znovu.');
    }
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }
function redirect(string $to): never { header('Location: ' . $to, true, 303); exit; }
function url(array $q = []): string { return './' . ($q ? '?' . http_build_query($q) : ''); }

function password_ok(string $p): ?string {
    if (mb_strlen($p) < 10) return 'Heslo musí mít alespoň 10 znaků.';
    if (mb_strlen($p) > 200) return 'Heslo je příliš dlouhé.';
    return null;
}

function fmt_date(?string $d, bool $time = true): string {
    if (!$d) return '—';
    $t = strtotime($d);
    $today = date('Y-m-d');
    if (date('Y-m-d', $t) === $today) return 'dnes ' . date('H:i', $t);
    if (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day'))) return 'včera ' . date('H:i', $t);
    return date($time ? 'j. n. Y H:i' : 'j. n. Y', $t);
}

function badge_status(string $s): string { return '<span class="pill st-' . e($s) . '">' . e(STATUSES[$s] ?? $s) . '</span>'; }
function badge_urgency(string $u): string { return '<span class="urg u-' . e($u) . '">' . e(URGENCY[$u] ?? $u) . '</span>'; }

/* ------------------------------------------------------------ layout */
function page_start(string $title, string $active = ''): void {
    $u = current_user();
    $newCount = $u ? (int)db()->query("SELECT COUNT(*) FROM inquiries WHERE status = 'nova'")->fetchColumn() : 0;
    ?><!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Administrace TF International</title>
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="../assets/fonts.css">
<link rel="stylesheet" href="admin.css">
<script src="admin.js" defer></script>
</head>
<body class="<?= $u ? 'app' : 'auth' ?>">
<?php if ($u): ?>
<aside class="side">
  <a class="brand" href="./"><img src="../assets/logo-tfint.svg" alt="TF International" width="70" height="33"><span>Administrace</span></a>
  <nav>
    <a href="./" class="<?= $active === 'list' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h10"/></svg>Zakázky<?php if ($newCount): ?><b class="count"><?= $newCount ?></b><?php endif ?></a>
    <a href="<?= url(['p' => 'users']) ?>" class="<?= $active === 'users' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/></svg>Uživatelé</a>
    <a href="<?= url(['p' => 'account']) ?>" class="<?= $active === 'account' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Můj účet</a>
  </nav>
  <div class="me">
    <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
    <span class="who"><b><?= e($u['name']) ?></b><small><?= e($u['username']) ?></small></span>
    <form method="post" action="<?= url(['p' => 'logout']) ?>"><?= csrf_field() ?><button class="icon-btn" title="Odhlásit se" aria-label="Odhlásit se"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h10"/></svg></button></form>
  </div>
  <a class="to-site" href="../cz/" target="_blank" rel="noopener">Zobrazit web ↗</a>
</aside>
<?php endif ?>
<main class="main">
<?php foreach ($_SESSION['flash'] ?? [] as [$type, $msg]): ?>
  <div class="flash <?= e($type) ?>" role="status"><?= e($msg) ?></div>
<?php endforeach; unset($_SESSION['flash']);
}

function page_end(): void {
    echo "\n</main>\n</body>\n</html>\n";
}
