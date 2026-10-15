<?php
/**
 * Inquiry form endpoint.
 *   GET  ?token=1  -> {"token": "..."}   (issued when the page loads; proves a real browser)
 *   POST           -> stores the inquiry; JSON for fetch(), 303 redirect for plain HTML forms
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$langs = ['cz', 'en', 'de'];

function reply(bool $ok, array $errors, string $lang, bool $isAjax): never {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        if (!$ok && !$errors) http_response_code(429);
        elseif (!$ok) http_response_code(422);
        echo json_encode(['ok' => $ok, 'errors' => (object)$errors], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $q = $ok ? 'poptavka=ok' : 'poptavka=chyba';
    header('Location: /' . $lang . '/?' . $q . '#kontakt', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['token' => form_token_issue()]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

$in = static fn(string $k): string => is_string($_POST[$k] ?? null) ? (string)$_POST[$k] : '';
$lang = in_array($in('lang'), $langs, true) ? $in('lang') : 'cz';

// honeypot: invisible field that only bots fill in -> pretend success, store nothing
if (trim($in('website')) !== '') reply(true, [], $lang, $isAjax);

// rate limit: max 5 inquiries per hour from one address
if (!throttle('form:' . client_ip_hash(), 5, 3600)) reply(false, [], $lang, $isAjax);

$data = [
    'name'    => one_line($in('name'), 120),
    'company' => one_line($in('company'), 160),
    'email'   => one_line($in('email'), 200),
    'phone'   => one_line($in('phone'), 40),
    'service' => $in('service'),
    'urgency' => $in('urgency'),
    'message' => mb_substr(trim(str_replace("\r\n", "\n", $in('message'))), 0, 5000),
];

// validation (error codes are translated in the browser)
$err = [];
if (mb_strlen($data['name']) < 2) $err['name'] = 'required';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = $data['email'] === '' ? 'required' : 'email';
if ($data['phone'] !== '' && !preg_match('/^[+()0-9 .\-\/]{6,40}$/', $data['phone'])) $err['phone'] = 'phone';
if (!array_key_exists($data['service'], SERVICES)) $err['service'] = 'required';
if (!array_key_exists($data['urgency'], URGENCY)) $data['urgency'] = 'bezna';
if (mb_strlen($data['message']) < 10) $err['message'] = 'short';
if ($err) reply(false, $err, $lang, $isAjax);

// spam heuristics -> kept, but filed under "Spam" so nothing is lost
$spam = null;
$age = form_token_age($in('ts'));
if ($age === null) $spam = 'chybí token formuláře (odesláno bez JavaScriptu nebo robotem)';
elseif ($age < 3) $spam = 'formulář vyplněn za méně než 3 s';
elseif ($age > 86400 * 2) $spam = 'prošlý token formuláře';
elseif (preg_match_all('~https?://~i', $data['message']) > 3) $spam = 'příliš mnoho odkazů ve zprávě';

$pdo = db();
$ts = now();
$pdo->prepare('INSERT INTO inquiries(created_at, updated_at, lang, name, company, email, phone, service, urgency, message, status, spam_reason, ip_hash, user_agent)
               VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$ts, $ts, $lang, $data['name'], $data['company'], $data['email'], $data['phone'], $data['service'], $data['urgency'],
               $data['message'], $spam ? 'spam' : 'nova', $spam, client_ip_hash(), one_line($_SERVER['HTTP_USER_AGENT'] ?? '', 250)]);
$id = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO notes(inquiry_id, user_id, kind, body, created_at) VALUES(?, NULL, 'system', ?, ?)")
    ->execute([$id, $spam ? 'Poptávka přijata z webu a zařazena do spamu: ' . $spam : 'Poptávka přijata z webu (' . strtoupper($lang) . ').', $ts]);

notify_new_inquiry($data + ['id' => $id, 'lang' => $lang, 'status' => $spam ? 'spam' : 'nova']);
reply(true, [], $lang, $isAjax);
