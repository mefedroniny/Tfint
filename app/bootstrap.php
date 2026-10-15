<?php
/**
 * TF International — shared core for the inquiry form (api/) and the admin (admin/).
 * Plain PHP 8 + SQLite (PDO), no dependencies.
 */
declare(strict_types=1);

const APP_ROOT = __DIR__;

$cfgFile = APP_ROOT . '/config.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit('Chybí app/config.php — zkopírujte app/config.sample.php na app/config.php a vyplňte hodnoty.');
}
$GLOBALS['CFG'] = require $cfgFile;

function cfg(string $key, $default = null) { return $GLOBALS['CFG'][$key] ?? $default; }

date_default_timezone_set('Europe/Prague');

/* ------------------------------------------------------------ constants */
const SERVICES = [
    'task_force' => 'Krizové řízení — Task Force',
    'procesy'    => 'Podpora stávajících procesů',
    'inovace'    => 'Inovace, výzkum a vývoj',
    'jine'       => 'Jiné / nevím',
];
const URGENCY = [
    'bezna'  => 'Běžná',
    'spech'  => 'Spěchá',
    'krize'  => 'Krize — ihned',
];
const STATUSES = [
    'nova'        => 'Nová',
    'kontaktovano'=> 'Kontaktováno',
    'v_reseni'    => 'V řešení',
    'nabidka'     => 'Nabídka odeslána',
    'realizace'   => 'Realizace',
    'uzavreno'    => 'Uzavřeno',
    'zamitnuto'   => 'Zamítnuto',
    'spam'        => 'Spam',
];

/* ------------------------------------------------------------ database */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $path = cfg('db_path', APP_ROOT . '/../data/tfint.sqlite');
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 4000;');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void {
    $v = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($v < 1) {
        $pdo->exec(<<<SQL
        CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            username TEXT NOT NULL UNIQUE COLLATE NOCASE,
            name TEXT NOT NULL,
            pass_hash TEXT NOT NULL,
            created_at TEXT NOT NULL,
            last_login TEXT
        );
        CREATE TABLE inquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,  -- order numbers are never reused, even after deletes
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            lang TEXT NOT NULL DEFAULT 'cz',
            name TEXT NOT NULL,
            company TEXT NOT NULL DEFAULT '',
            email TEXT NOT NULL,
            phone TEXT NOT NULL DEFAULT '',
            service TEXT NOT NULL,
            urgency TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'nova',
            seen INTEGER NOT NULL DEFAULT 0,
            assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
            spam_reason TEXT,
            ip_hash TEXT,
            user_agent TEXT
        );
        CREATE INDEX idx_inq_status ON inquiries(status, created_at);
        CREATE TABLE notes (
            id INTEGER PRIMARY KEY,
            inquiry_id INTEGER NOT NULL REFERENCES inquiries(id) ON DELETE CASCADE,
            user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            kind TEXT NOT NULL DEFAULT 'note',
            body TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE INDEX idx_notes_inq ON notes(inquiry_id, created_at);
        CREATE TABLE throttle (
            k TEXT NOT NULL,
            ts INTEGER NOT NULL
        );
        CREATE INDEX idx_throttle ON throttle(k, ts);
        PRAGMA user_version = 1;
        SQL);
    }
}

function now(): string { return date('Y-m-d H:i:s'); }

/* ------------------------------------------------------------ helpers */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function client_ip_hash(): string {
    // stored only as a salted hash (GDPR: we never keep the raw IP)
    return substr(hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', cfg('secret')), 0, 24);
}

/** true if the action is allowed; records the hit. */
function throttle(string $key, int $max, int $windowSec, bool $record = true): bool {
    $pdo = db();
    $since = time() - $windowSec;
    $pdo->prepare('DELETE FROM throttle WHERE ts < ?')->execute([time() - 86400]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM throttle WHERE k = ? AND ts >= ?');
    $st->execute([$key, $since]);
    if ((int)$st->fetchColumn() >= $max) return false;
    if ($record) $pdo->prepare('INSERT INTO throttle(k, ts) VALUES(?, ?)')->execute([$key, time()]);
    return true;
}
function throttle_clear(string $key): void { db()->prepare('DELETE FROM throttle WHERE k = ?')->execute([$key]); }

function one_line(string $s, int $max): string {
    $s = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '');
    return mb_substr($s, 0, $max);
}

/* ------------------------------------------------------------ form token (proves a real browser loaded the page) */
function form_token_issue(): string {
    $t = (string)time();
    return $t . '.' . hash_hmac('sha256', 'form|' . $t, cfg('secret'));
}
/** returns age in seconds, or null if the token is missing/invalid */
function form_token_age(string $tok): ?int {
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $tok, $m)) return null;
    if (!hash_equals(hash_hmac('sha256', 'form|' . $m[1], cfg('secret')), $m[2])) return null;
    return time() - (int)$m[1];
}

/* ------------------------------------------------------------ e-mail notification */
function notify_new_inquiry(array $q): void {
    $to = trim((string)cfg('notify_email', ''));
    if ($to === '' || $q['status'] === 'spam') return;
    $urg = URGENCY[$q['urgency']] ?? $q['urgency'];
    $subject = ($q['urgency'] === 'krize' ? '[KRIZE] ' : '') . 'Nová poptávka z webu: ' . one_line($q['name'], 60);
    $admin = rtrim((string)cfg('base_url', ''), '/') . '/admin/?p=detail&id=' . $q['id'];
    $body = "Nová poptávka z webu tfint.cz\n\n"
        . "Jméno:     {$q['name']}\nFirma:     {$q['company']}\nE-mail:    {$q['email']}\nTelefon:   {$q['phone']}\n"
        . "Oblast:    " . (SERVICES[$q['service']] ?? $q['service']) . "\nNaléhavost: {$urg}\nJazyk:     " . strtoupper($q['lang']) . "\n\n"
        . "Zpráva:\n{$q['message']}\n\nDetail v administraci:\n{$admin}\n";
    $from = one_line((string)cfg('mail_from', 'web@tfint.cz'), 120);
    $headers = [
        'From' => 'Web TF International <' . $from . '>',
        'Reply-To' => one_line($q['email'], 200),
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Mailer' => 'tfint-web',
    ];
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
