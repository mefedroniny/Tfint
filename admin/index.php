<?php
/** TF International — administrace poptávek / zakázek. Router: ?p=list|detail|users|account|export|login|setup|logout */
declare(strict_types=1);
require __DIR__ . '/../app/admin_lib.php';
admin_boot();

$p = (string)($_GET['p'] ?? 'list');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

/* ================================================================ first run: create the first admin */
if (users_count() === 0) {
    $err = null;
    if ($post) {
        csrf_check();
        if (!throttle('setup:' . client_ip_hash(), 10, 900)) $err = 'Příliš mnoho pokusů. Zkuste to za 15 minut.';
        elseif (!hash_equals((string)cfg('setup_key'), (string)($_POST['setup_key'] ?? ''))) $err = 'Instalační klíč nesouhlasí (najdete ho v app/config.php).';
        else {
            $name = one_line((string)($_POST['name'] ?? ''), 80);
            $user = strtolower(one_line((string)($_POST['username'] ?? ''), 60));
            $pass = (string)($_POST['password'] ?? '');
            if ($name === '' || !preg_match('/^[a-z0-9._@-]{3,60}$/', $user)) $err = 'Vyplňte jméno a přihlašovací jméno (3–60 znaků: písmena bez diakritiky, čísla, . _ - @).';
            elseif ($m = password_ok($pass)) $err = $m;
            elseif ($pass !== ($_POST['password2'] ?? '')) $err = 'Hesla se neshodují.';
            else {
                db()->prepare('INSERT INTO users(username, name, pass_hash, created_at) VALUES(?,?,?,?)')
                    ->execute([$user, $name, password_hash($pass, PASSWORD_DEFAULT), now()]);
                $st = db()->prepare('SELECT * FROM users WHERE username = ?'); $st->execute([$user]);
                login_user($st->fetch());
                flash('Účet byl vytvořen. Vítejte v administraci!');
                redirect(url());
            }
        }
    }
    page_start('První spuštění'); ?>
    <div class="auth-card">
      <img src="../assets/logo-tfint.svg" alt="TF International" class="auth-logo" width="120" height="56">
      <h1>První spuštění</h1>
      <p class="muted">Založte účet prvního administrátora. Instalační klíč je v souboru <code>app/config.php</code> (položka <code>setup_key</code>).</p>
      <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif ?>
      <form method="post" class="stack">
        <?= csrf_field() ?>
        <label>Instalační klíč<input name="setup_key" required autocomplete="off"></label>
        <label>Vaše jméno<input name="name" required maxlength="80" value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Přihlašovací jméno<input name="username" required maxlength="60" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></label>
        <label>Heslo <small>(min. 10 znaků)</small><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
        <label>Heslo znovu<input type="password" name="password2" required minlength="10" autocomplete="new-password"></label>
        <button class="btn primary">Vytvořit účet a přihlásit</button>
      </form>
    </div>
    <?php page_end(); exit;
}

/* ================================================================ login / logout */
if ($p === 'logout' && $post) {
    csrf_check();
    $_SESSION = [];
    session_regenerate_id(true);
    flash('Byli jste odhlášeni.', 'info');
    redirect(url(['p' => 'login']));
}

if (!current_user()) {
    $err = null;
    if ($post && $p === 'login') {
        csrf_check();
        $user = strtolower(one_line((string)($_POST['username'] ?? ''), 60));
        $ipKey = 'login-ip:' . client_ip_hash();
        $userKey = 'login-user:' . $user;
        if (!throttle($ipKey, 20, 900, false) || !throttle($userKey, 5, 900, false)) {
            $err = 'Příliš mnoho neúspěšných pokusů. Zkuste to znovu za 15 minut.';
        } else {
            $st = db()->prepare('SELECT * FROM users WHERE username = ?');
            $st->execute([$user]);
            $row = $st->fetch();
            // verify against a dummy hash when the user does not exist (same timing)
            $ok = password_verify((string)($_POST['password'] ?? ''), $row['pass_hash'] ?? password_hash('timing-dummy', PASSWORD_DEFAULT));
            if ($row && $ok) {
                if (password_needs_rehash($row['pass_hash'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?')->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $row['id']]);
                }
                throttle_clear($userKey);
                login_user($row);
                $next = (string)($_SESSION['next'] ?? '');
                unset($_SESSION['next']);
                redirect(preg_match('~^\./\?[\w=&%.-]*$~', $next) ? $next : url());
            }
            throttle($ipKey, 20, 900);
            throttle($userKey, 5, 900);
            $err = 'Nesprávné přihlašovací jméno nebo heslo.';
        }
    } elseif ($p !== 'login') {
        $_SESSION['next'] = './?' . ($_SERVER['QUERY_STRING'] ?? '');
    }
    page_start('Přihlášení'); ?>
    <div class="auth-card">
      <img src="../assets/logo-tfint.svg" alt="TF International" class="auth-logo" width="120" height="56">
      <h1>Administrace</h1>
      <p class="muted">Poptávky a zakázky z webu tfint.cz</p>
      <?php if ($err): ?><div class="flash err" role="alert"><?= e($err) ?></div><?php endif ?>
      <form method="post" action="<?= url(['p' => 'login']) ?>" class="stack">
        <?= csrf_field() ?>
        <label>Přihlašovací jméno<input name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></label>
        <label>Heslo<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn primary">Přihlásit se</button>
      </form>
    </div>
    <?php page_end(); exit;
}

$me = current_user();
$pdo = db();

/* ================================================================ list filters (shared by list + export) */
function list_query(array $g): array {
    $status = (string)($g['status'] ?? 'aktivni');
    $q = trim((string)($g['q'] ?? ''));
    $urg = (string)($g['urg'] ?? '');
    $where = []; $args = [];
    if ($status === 'aktivni') $where[] = "i.status NOT IN ('uzavreno','zamitnuto','spam')";
    elseif ($status === 'vse') $where[] = "i.status <> 'spam'";
    elseif (isset(STATUSES[$status])) { $where[] = 'i.status = ?'; $args[] = $status; }
    else $status = 'aktivni';
    if (isset(URGENCY[$urg])) { $where[] = 'i.urgency = ?'; $args[] = $urg; } else $urg = '';
    if ($q !== '') {
        $where[] = '(i.name LIKE ? OR i.company LIKE ? OR i.email LIKE ? OR i.phone LIKE ? OR i.message LIKE ? OR CAST(i.id AS TEXT) = ?)';
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        array_push($args, $like, $like, $like, $like, $like, ltrim($q, '#'));
    }
    $sql = ($where ? ' WHERE ' . implode(' AND ', $where) : '');
    $sql = str_replace('LIKE ?', "LIKE ? ESCAPE '\\'", $sql);
    return [$sql, $args, $status, $q, $urg];
}

/* ================================================================ POST actions */
if ($post) {
    csrf_check();
    $a = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $back = url(['p' => 'detail', 'id' => $id]);
    $log = static function (int $id, string $kind, string $body) use ($pdo, $me) {
        $pdo->prepare('INSERT INTO notes(inquiry_id, user_id, kind, body, created_at) VALUES(?,?,?,?,?)')->execute([$id, $me['id'], $kind, $body, now()]);
        $pdo->prepare('UPDATE inquiries SET updated_at = ? WHERE id = ?')->execute([now(), $id]);
    };
    $inq = null;
    if ($id) { $st = $pdo->prepare('SELECT * FROM inquiries WHERE id = ?'); $st->execute([$id]); $inq = $st->fetch() ?: null; }

    switch ($a) {
        case 'status':
            $new = (string)($_POST['status'] ?? '');
            if ($inq && isset(STATUSES[$new]) && $new !== $inq['status']) {
                $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$new, $id]);
                $log($id, 'status', 'Stav: ' . (STATUSES[$inq['status']] ?? $inq['status']) . ' → ' . STATUSES[$new]);
                flash('Stav změněn na „' . STATUSES[$new] . '“.');
            }
            redirect($back);
        case 'assign':
            $uid = (int)($_POST['user_id'] ?? 0);
            if ($inq) {
                $name = 'nikomu';
                if ($uid) { $s = $pdo->prepare('SELECT name FROM users WHERE id = ?'); $s->execute([$uid]); $name = $s->fetchColumn(); if (!$name) redirect($back); }
                $pdo->prepare('UPDATE inquiries SET assigned_to = ? WHERE id = ?')->execute([$uid ?: null, $id]);
                $log($id, 'status', 'Přiřazeno: ' . $name);
                flash('Přiřazení uloženo.');
            }
            redirect($back);
        case 'note':
            $body = trim(str_replace("\r\n", "\n", (string)($_POST['body'] ?? '')));
            if ($inq && $body !== '') { $log($id, 'note', mb_substr($body, 0, 5000)); flash('Poznámka přidána.'); }
            redirect($back . '#poznamky');
        case 'unread':
            if ($inq) $pdo->prepare('UPDATE inquiries SET seen = 0 WHERE id = ?')->execute([$id]);
            flash('Označeno jako nepřečtené.');
            redirect(url());
        case 'delete':
            if ($inq && ($_POST['confirm'] ?? '') === 'SMAZAT') {
                $pdo->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$id]);
                flash('Zakázka #' . $id . ' byla trvale smazána.');
                redirect(url());
            }
            flash('Pro smazání napište do pole slovo SMAZAT.', 'err');
            redirect($back);
        case 'bulk':
            $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
            $new = (string)($_POST['bulk_status'] ?? '');
            if ($ids && isset(STATUSES[$new])) {
                foreach ($ids as $bid) {
                    $s = $pdo->prepare('SELECT status FROM inquiries WHERE id = ?'); $s->execute([$bid]); $old = $s->fetchColumn();
                    if ($old === false || $old === $new) continue;
                    $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$new, $bid]);
                    $log($bid, 'status', 'Stav: ' . (STATUSES[$old] ?? $old) . ' → ' . STATUSES[$new] . ' (hromadně)');
                }
                flash('Změněno ' . count($ids) . ' zakázek na „' . STATUSES[$new] . '“.');
            }
            $b = (string)($_POST['back'] ?? '');
            redirect(preg_match('~^\./(\?[\w=&%.\-+]*)?$~', $b) ? $b : url());
        case 'user_add':
            $name = one_line((string)($_POST['name'] ?? ''), 80);
            $user = strtolower(one_line((string)($_POST['username'] ?? ''), 60));
            $pass = (string)($_POST['password'] ?? '');
            if ($name === '' || !preg_match('/^[a-z0-9._@-]{3,60}$/', $user)) flash('Vyplňte jméno a platné přihlašovací jméno.', 'err');
            elseif ($m = password_ok($pass)) flash($m, 'err');
            else {
                try {
                    $pdo->prepare('INSERT INTO users(username, name, pass_hash, created_at) VALUES(?,?,?,?)')->execute([$user, $name, password_hash($pass, PASSWORD_DEFAULT), now()]);
                    flash('Uživatel ' . $name . ' přidán. Předejte mu heslo bezpečnou cestou.');
                } catch (PDOException) { flash('Toto přihlašovací jméno už existuje.', 'err'); }
            }
            redirect(url(['p' => 'users']));
        case 'user_delete':
            $uid = (int)($_POST['user_id'] ?? 0);
            if ($uid === (int)$me['id']) flash('Nemůžete smazat sami sebe.', 'err');
            elseif (users_count() <= 1) flash('Musí zůstat alespoň jeden uživatel.', 'err');
            else { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]); flash('Uživatel odebrán.'); }
            redirect(url(['p' => 'users']));
        case 'password':
            $st = $pdo->prepare('SELECT pass_hash FROM users WHERE id = ?'); $st->execute([$me['id']]);
            if (!password_verify((string)($_POST['current'] ?? ''), (string)$st->fetchColumn())) flash('Současné heslo nesouhlasí.', 'err');
            elseif ($m = password_ok((string)($_POST['password'] ?? ''))) flash($m, 'err');
            elseif ($_POST['password'] !== ($_POST['password2'] ?? '')) flash('Nová hesla se neshodují.', 'err');
            else {
                $pdo->prepare('UPDATE users SET pass_hash = ? WHERE id = ?')->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $me['id']]);
                session_regenerate_id(true);
                flash('Heslo změněno.');
            }
            redirect(url(['p' => 'account']));
    }
    redirect(url());
}

/* ================================================================ export CSV */
if ($p === 'export') {
    [$where, $args] = list_query($_GET);
    $st = $pdo->prepare('SELECT i.*, u.name AS assignee FROM inquiries i LEFT JOIN users u ON u.id = i.assigned_to' . $where . ' ORDER BY i.created_at DESC');
    $st->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="zakazky-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel
    $safe = static fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : $v; // CSV injection guard
    fputcsv($out, ['ID', 'Přijato', 'Stav', 'Naléhavost', 'Oblast', 'Jméno', 'Firma', 'E-mail', 'Telefon', 'Zpráva', 'Přiřazeno', 'Jazyk'], ';');
    foreach ($st as $r) {
        fputcsv($out, array_map($safe, [$r['id'], $r['created_at'], STATUSES[$r['status']] ?? $r['status'], URGENCY[$r['urgency']] ?? $r['urgency'],
            SERVICES[$r['service']] ?? $r['service'], $r['name'], $r['company'], $r['email'], $r['phone'], $r['message'], $r['assignee'] ?? '', strtoupper($r['lang'])]), ';');
    }
    exit;
}

/* ================================================================ detail */
if ($p === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare('SELECT i.*, u.name AS assignee FROM inquiries i LEFT JOIN users u ON u.id = i.assigned_to WHERE i.id = ?');
    $st->execute([$id]);
    $q = $st->fetch();
    if (!$q) { http_response_code(404); page_start('Nenalezeno', 'list'); echo '<div class="empty"><h1>Zakázka nenalezena</h1><p><a href="./">← Zpět na seznam</a></p></div>'; page_end(); exit; }
    if (!$q['seen']) $pdo->prepare('UPDATE inquiries SET seen = 1 WHERE id = ?')->execute([$id]);
    $notes = $pdo->prepare('SELECT n.*, u.name AS author FROM notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.inquiry_id = ? ORDER BY n.created_at DESC, n.id DESC');
    $notes->execute([$id]);
    $users = $pdo->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
    $subject = rawurlencode('Re: Vaše poptávka u TF International');
    page_start('#' . $id . ' ' . $q['name'], 'list'); ?>
    <div class="head">
      <div>
        <a class="back" href="./">← Zakázky</a>
        <h1><span class="id">#<?= $q['id'] ?></span> <?= e($q['name']) ?><?php if ($q['company']): ?> <span class="muted">· <?= e($q['company']) ?></span><?php endif ?></h1>
        <div class="meta"><?= badge_status($q['status']) ?> <?= badge_urgency($q['urgency']) ?> <span>Přijato <?= e(fmt_date($q['created_at'])) ?></span> <span><?= e(strtoupper($q['lang'])) ?> verze webu</span></div>
      </div>
      <div class="actions">
        <a class="btn" href="mailto:<?= e($q['email']) ?>?subject=<?= $subject ?>">✉ Odpovědět e-mailem</a>
        <?php if ($q['phone']): ?><a class="btn" href="tel:<?= e(preg_replace('/[^+0-9]/', '', $q['phone'])) ?>">☎ Zavolat</a><?php endif ?>
      </div>
    </div>
    <?php if ($q['status'] === 'spam' && $q['spam_reason']): ?><div class="flash warn">Automaticky zařazeno do spamu: <?= e($q['spam_reason']) ?>. Pokud jde o skutečnou poptávku, změňte stav na „Nová“.</div><?php endif ?>

    <div class="detail">
      <section class="card">
        <h2>Poptávka</h2>
        <dl class="kv">
          <dt>Oblast</dt><dd><?= e(SERVICES[$q['service']] ?? $q['service']) ?></dd>
          <dt>E-mail</dt><dd><a href="mailto:<?= e($q['email']) ?>"><?= e($q['email']) ?></a> <button class="copy" data-copy="<?= e($q['email']) ?>" type="button">kopírovat</button></dd>
          <dt>Telefon</dt><dd><?= $q['phone'] ? '<a href="tel:' . e(preg_replace('/[^+0-9]/', '', $q['phone'])) . '">' . e($q['phone']) . '</a>' : '—' ?></dd>
          <dt>Firma</dt><dd><?= e($q['company'] ?: '—') ?></dd>
        </dl>
        <div class="message"><?= nl2br(e($q['message'])) ?></div>
      </section>

      <aside class="stack-col">
        <section class="card">
          <h2>Stav zakázky</h2>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $id ?>">
            <select name="status" aria-label="Stav"><?php foreach (STATUSES as $k => $v): ?><option value="<?= $k ?>"<?= $k === $q['status'] ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach ?></select>
            <button class="btn primary">Uložit</button>
          </form>
          <h2 class="mt">Řeší</h2>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="assign"><input type="hidden" name="id" value="<?= $id ?>">
            <select name="user_id" aria-label="Přiřadit"><option value="0">— nikdo —</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"<?= (int)$u['id'] === (int)$q['assigned_to'] ? ' selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach ?></select>
            <button class="btn">Přiřadit</button>
          </form>
        </section>

        <section class="card" id="poznamky">
          <h2>Poznámky a historie</h2>
          <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?= $id ?>">
            <textarea name="body" rows="3" required maxlength="5000" placeholder="Interní poznámka (zákazník ji neuvidí)…"></textarea>
            <button class="btn primary">Přidat poznámku</button>
          </form>
          <ol class="timeline">
          <?php foreach ($notes as $n): ?>
            <li class="k-<?= e($n['kind']) ?>"><div class="t"><b><?= e($n['author'] ?? 'Systém') ?></b> <time><?= e(fmt_date($n['created_at'])) ?></time></div><div><?= nl2br(e($n['body'])) ?></div></li>
          <?php endforeach ?>
          </ol>
        </section>

        <section class="card danger">
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="unread"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn ghost">Označit jako nepřečtené</button></form>
          <details>
            <summary>Trvale smazat (např. na žádost podle GDPR)</summary>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>">
              <input name="confirm" placeholder="Napište SMAZAT" aria-label="Potvrzení smazání" autocomplete="off"><button class="btn bad">Smazat</button>
            </form>
          </details>
        </section>
      </aside>
    </div>
    <?php page_end(); exit;
}

/* ================================================================ users */
if ($p === 'users') {
    $users = $pdo->query('SELECT u.*, (SELECT COUNT(*) FROM inquiries i WHERE i.assigned_to = u.id AND i.status NOT IN (\'uzavreno\',\'zamitnuto\',\'spam\')) AS active FROM users u ORDER BY name')->fetchAll();
    page_start('Uživatelé', 'users'); ?>
    <div class="head"><div><h1>Uživatelé</h1><p class="muted">Kdo má přístup do administrace.</p></div></div>
    <div class="grid2">
      <section class="card">
        <table class="table">
          <thead><tr><th>Jméno</th><th>Přihlašovací jméno</th><th>Aktivní zakázky</th><th>Poslední přihlášení</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr><td><b><?= e($u['name']) ?></b><?= (int)$u['id'] === (int)$me['id'] ? ' <span class="muted">(vy)</span>' : '' ?></td><td><?= e($u['username']) ?></td><td><?= (int)$u['active'] ?></td><td><?= e(fmt_date($u['last_login'])) ?></td>
              <td class="r"><?php if ((int)$u['id'] !== (int)$me['id']): ?><form method="post" data-confirm="Opravdu odebrat uživatele <?= e($u['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="user_delete"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn ghost small">Odebrat</button></form><?php endif ?></td></tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </section>
      <section class="card">
        <h2>Přidat uživatele</h2>
        <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="user_add">
          <label>Jméno<input name="name" required maxlength="80"></label>
          <label>Přihlašovací jméno<input name="username" required maxlength="60" autocomplete="off"></label>
          <label>Počáteční heslo <small>(min. 10 znaků)</small><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
          <button class="btn primary">Přidat</button>
        </form>
      </section>
    </div>
    <?php page_end(); exit;
}

/* ================================================================ account */
if ($p === 'account') {
    page_start('Můj účet', 'account'); ?>
    <div class="head"><div><h1>Můj účet</h1><p class="muted"><?= e($me['name']) ?> · <?= e($me['username']) ?> · účet od <?= e(fmt_date($me['created_at'], false)) ?></p></div></div>
    <section class="card narrow">
      <h2>Změna hesla</h2>
      <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <label>Současné heslo<input type="password" name="current" required autocomplete="current-password"></label>
        <label>Nové heslo <small>(min. 10 znaků)</small><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
        <label>Nové heslo znovu<input type="password" name="password2" required minlength="10" autocomplete="new-password"></label>
        <button class="btn primary">Změnit heslo</button>
      </form>
    </section>
    <?php page_end(); exit;
}

/* ================================================================ list (default) */
[$where, $args, $status, $search, $urg] = list_query($_GET);
$perPage = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$cnt = $pdo->prepare('SELECT COUNT(*) FROM inquiries i' . $where); $cnt->execute($args); $total = (int)$cnt->fetchColumn();
$st = $pdo->prepare('SELECT i.*, u.name AS assignee FROM inquiries i LEFT JOIN users u ON u.id = i.assigned_to'
    . $where
    . ' ORDER BY i.seen ASC, CASE WHEN i.status = \'nova\' AND i.urgency = \'krize\' THEN 0 ELSE 1 END, i.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
$st->execute($args);
$rows = $st->fetchAll();

$counts = [];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM inquiries GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];
$active = array_sum(array_diff_key($counts, array_flip(['uzavreno', 'zamitnuto', 'spam'])));
$crisis = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE urgency = 'krize' AND status NOT IN ('uzavreno','zamitnuto','spam')")->fetchColumn();
$month = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status <> 'spam' AND created_at >= '" . date('Y-m-01') . "'")->fetchColumn();
$won = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status IN ('realizace','uzavreno')")->fetchColumn();

$tabs = ['aktivni' => ['Aktivní', $active], 'nova' => ['Nové', $counts['nova'] ?? 0]];
foreach (['kontaktovano', 'v_reseni', 'nabidka', 'realizace', 'uzavreno', 'zamitnuto'] as $s) $tabs[$s] = [STATUSES[$s], $counts[$s] ?? 0];
$tabs['vse'] = ['Vše', array_sum($counts) - ($counts['spam'] ?? 0)];
$tabs['spam'] = ['Spam', $counts['spam'] ?? 0];
$qs = static fn(array $over) => url(array_filter(array_merge(['status' => $status, 'q' => $search, 'urg' => $urg], $over), static fn($v) => $v !== '' && $v !== null));

page_start('Zakázky', 'list'); ?>
<div class="head">
  <div><h1>Zakázky</h1><p class="muted">Poptávky odeslané formulářem na webu.</p></div>
  <div class="actions"><a class="btn" href="<?= e($qs(['p' => 'export', 'page' => null])) ?>">⤓ Export CSV</a></div>
</div>

<div class="stats">
  <a class="stat" href="<?= e(url(['status' => 'nova'])) ?>"><small>Nové</small><b><?= $counts['nova'] ?? 0 ?></b></a>
  <a class="stat hot" href="<?= e(url(['status' => 'aktivni', 'urg' => 'krize'])) ?>"><small>Krize — aktivní</small><b><?= $crisis ?></b></a>
  <a class="stat" href="<?= e(url(['status' => 'aktivni'])) ?>"><small>Aktivní celkem</small><b><?= $active ?></b></a>
  <div class="stat"><small>Tento měsíc</small><b><?= $month ?></b></div>
  <div class="stat"><small>Získané zakázky</small><b><?= $won ?></b></div>
</div>

<nav class="tabs" aria-label="Filtr stavu">
<?php foreach ($tabs as $k => [$label, $n]): ?>
  <a href="<?= e(url(array_filter(['status' => $k, 'q' => $search, 'urg' => $urg]))) ?>" class="<?= $k === $status ? 'on' : '' ?> t-<?= $k ?>"><?= e($label) ?> <span><?= $n ?></span></a>
<?php endforeach ?>
</nav>

<form class="filters" method="get">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Hledat jméno, firmu, e-mail, text nebo #číslo…" aria-label="Hledat">
  <select name="urg" aria-label="Naléhavost"><option value="">Jakákoli naléhavost</option><?php foreach (URGENCY as $k => $v): ?><option value="<?= $k ?>"<?= $k === $urg ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach ?></select>
  <button class="btn">Filtrovat</button>
  <?php if ($search !== '' || $urg !== ''): ?><a class="btn ghost" href="<?= e(url(['status' => $status])) ?>">Zrušit</a><?php endif ?>
</form>

<?php if (!$rows): ?>
  <div class="empty card"><h2>Nic tu není</h2><p class="muted"><?= $total === 0 && !$search && $status === 'aktivni' ? 'Zatím nepřišla žádná poptávka. Jakmile někdo vyplní formulář na webu, objeví se tady.' : 'Tomuto filtru neodpovídá žádná zakázka.' ?></p></div>
<?php else: ?>
<form method="post" id="bulk">
  <?= csrf_field() ?><input type="hidden" name="action" value="bulk"><input type="hidden" name="back" value="<?= e($qs(['page' => $page > 1 ? $page : null])) ?>">
  <div class="card table-wrap">
  <table class="table list">
    <thead><tr><th class="cb"><input type="checkbox" data-all aria-label="Vybrat vše"></th><th>#</th><th>Zákazník</th><th>Oblast</th><th>Naléhavost</th><th>Stav</th><th>Řeší</th><th>Přijato</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $link = url(['p' => 'detail', 'id' => $r['id']]); ?>
      <tr class="<?= $r['seen'] ? '' : 'unseen' ?>" data-href="<?= e($link) ?>">
        <td class="cb"><input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" aria-label="Vybrat #<?= $r['id'] ?>"></td>
        <td class="id"><?= $r['id'] ?></td>
        <td><a href="<?= e($link) ?>"><b><?= e($r['name']) ?></b></a><small><?= e($r['company'] ?: $r['email']) ?></small><span class="snippet"><?= e(mb_substr($r['message'], 0, 110)) ?><?= mb_strlen($r['message']) > 110 ? '…' : '' ?></span></td>
        <td><?= e(SERVICES[$r['service']] ?? $r['service']) ?></td>
        <td><?= badge_urgency($r['urgency']) ?></td>
        <td><?= badge_status($r['status']) ?></td>
        <td><?= e($r['assignee'] ?? '—') ?></td>
        <td class="nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  </div>
  <div class="bulkbar" hidden>
    <span data-count>0</span> vybráno —
    <select name="bulk_status" aria-label="Nový stav"><?php foreach (STATUSES as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach ?></select>
    <button class="btn primary">Změnit stav</button>
  </div>
</form>
<?php if ($total > $perPage): $pages = (int)ceil($total / $perPage); ?>
  <nav class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e($qs(['page' => $i])) ?>" class="<?= $i === $page ? 'on' : '' ?>"><?= $i ?></a><?php endfor ?></nav>
<?php endif ?>
<?php endif;
page_end();
