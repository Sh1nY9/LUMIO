<?php
require_once __DIR__ . '/lib/common.php';
// 보호 페이지 — 2단계 인증까지 끝난 세션만 접근 가능.
$me = require_auth();

$err = '';          // 오류 메시지
$ok  = '';          // 성공 메시지
$newSecret = null;  // OTP 재설정 시 1회만 보여줄 새 비밀키
$newCodes  = null;  // 백업 코드 재발급 시 1회만 보여줄 코드 목록

$row = find_user($me['username']);
if (!$row) {                      // 세션은 남았는데 계정이 지워진 경우
    session_destroy();
    header('Location: /login');
    exit;
}

/* 탭 — /settings, /settings/log, /settings/security, /settings/danger */
$TABS = ['profile' => '개인정보', 'log' => '로그', 'security' => '보안', 'danger' => '계정 삭제'];
$tab  = $_GET['tab'] ?? 'profile';
if (!isset($TABS[$tab])) $tab = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_ok()) {
        $err = '요청이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도하세요.';
    }

    // ── 비밀번호 변경 ────────────────────────────────────────
    elseif ($action === 'password') {
        $tab = 'security';
        $cur = $_POST['current_password'] ?? '';
        $p1  = $_POST['new_password'] ?? '';
        $p2  = $_POST['new_password2'] ?? '';

        if (!password_verify($cur, $row['pwhash'])) {
            log_login($me['username'], 'pwchange', 'fail');
            $err = '현재 비밀번호가 올바르지 않습니다.';
        } elseif (strlen($p1) < 4) {
            $err = '새 비밀번호는 4자 이상이어야 합니다.';
        } elseif ($p1 !== $p2) {
            $err = '새 비밀번호가 서로 일치하지 않습니다.';
        } elseif (password_verify($p1, $row['pwhash'])) {
            $err = '현재 비밀번호와 다른 값을 사용하세요.';
        } else {
            db()->prepare('UPDATE users SET pwhash = ? WHERE username = ?')
                ->execute([password_hash($p1, PASSWORD_DEFAULT), $me['username']]);
            log_login($me['username'], 'pwchange', 'ok');
            // 비밀번호가 바뀌었으니 세션 ID도 새로 발급한다(탈취된 세션 무력화).
            session_regenerate_id(true);
            $ok  = '비밀번호가 변경되었습니다.';
            $row = find_user($me['username']);
        }
    }

    // ── OTP 비밀키 재설정 ────────────────────────────────────
    elseif ($action === 'otp') {
        $tab = 'security';
        if (!password_verify($_POST['otp_password'] ?? '', $row['pwhash'])) {
            $err = '비밀번호가 올바르지 않습니다.';
        } else {
            $newSecret = totp_secret_new();
            db()->prepare('UPDATE users SET totp_secret = ? WHERE username = ?')
                ->execute([$newSecret, $me['username']]);
            log_login($me['username'], 'otp-reset', 'ok');
            $ok  = 'OTP 비밀키가 새로 발급되었습니다. 인증 앱에 다시 등록하세요.';
            $row = find_user($me['username']);
        }
    }

    // ── 백업 코드 재발급 ─────────────────────────────────────
    elseif ($action === 'recovery') {
        $tab = 'security';
        if (!password_verify($_POST['recovery_password'] ?? '', $row['pwhash'])) {
            $err = '비밀번호가 올바르지 않습니다.';
        } else {
            $newCodes = recovery_generate($me['username']);
            log_login($me['username'], 'recovery-gen', 'ok');
            $ok = '백업 코드를 새로 발급했습니다. 이전 코드는 모두 무효가 됩니다.';
        }
    }

    // ── 삭제 예약 취소(복구) ─────────────────────────────────
    elseif ($action === 'restore') {
        restore_user($me['username']);
        log_login($me['username'], 'restore', 'ok');
        $ok  = '계정 삭제가 취소되었습니다. 계정이 정상 상태로 돌아왔습니다.';
        $row = find_user($me['username']);
    }

    // ── 계정 삭제 ────────────────────────────────────────────
    elseif ($action === 'delete') {
        $tab = 'danger';
        $cur     = $_POST['delete_password'] ?? '';
        $confirm = trim($_POST['delete_confirm'] ?? '');

        if (!password_verify($cur, $row['pwhash'])) {
            log_login($me['username'], 'delete', 'fail');
            $err = '비밀번호가 올바르지 않습니다.';
        } elseif ($confirm !== $me['username']) {
            $err = '확인란에 아이디를 정확히 입력해야 삭제됩니다.';
        } else {
            // 즉시 지우지 않고 삭제 예약만 한다. 유예 기간 안에 다시 로그인하면
            // 되돌릴 수 있다. 감사 기록은 지우지 않는다.
            mark_deleted($me['username']);
            log_login($me['username'], 'delete', 'ok');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $c = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
            }
            session_destroy();
            header('Location: /login?deleted=1');
            exit;
        }
    }
}

$daysLeft  = deletion_days_left($row);
$remaining = recovery_remaining($me['username']);
$src_ip    = $_SERVER['REMOTE_ADDR'] ?? '-';
$xff       = ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') !== '' ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '(없음)';

$logs = [];
if ($tab === 'log') {
    $st = db()->prepare('SELECT stage,result,src_ip,xff,at FROM login_log
                         WHERE username = ? ORDER BY id DESC LIMIT 50');
    $st->execute([$me['username']]);
    $logs = $st->fetchAll(PDO::FETCH_ASSOC);
}

function tab_url(string $t): string { return $t === 'profile' ? '/settings' : '/settings/' . $t; }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($TABS[$tab]) ?> | Lumio</title>
  <link rel="stylesheet" href="/style.css" />
  <link rel="stylesheet" href="/app.css" />
  <script src="/app.js" defer></script>
  <script src="/codes.js" defer></script>
</head>
<body class="app-body">
<?php app_header($me['username'], 'settings'); ?>

  <main class="settings-shell">
    <!-- ── 사이드바 ── -->
    <aside class="side-nav" aria-label="설정 메뉴">
      <p class="side-title">내 계정</p>
      <?php foreach ($TABS as $key => $label): ?>
        <a href="<?= h(tab_url($key)) ?>"
           class="side-item<?= $tab === $key ? ' active' : '' ?><?= $key === 'danger' ? ' danger' : '' ?>">
          <?= h($label) ?>
        </a>
      <?php endforeach; ?>
    </aside>

    <section class="settings-main">
      <?php if ($daysLeft !== null): ?>
        <div class="banner danger" role="alert">
          <div>
            <strong>삭제 예약됨</strong>
            이 계정은 <strong><?= (int)$daysLeft ?>일 후</strong> 영구 삭제됩니다. 지금 되돌릴 수 있습니다.
          </div>
          <form method="post" action="/settings">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="restore">
            <button class="btn" type="submit">삭제 취소</button>
          </form>
        </div>
      <?php endif; ?>

      <?php if ($ok):  ?><div class="banner ok"  role="status"><?= h($ok)  ?></div><?php endif; ?>
      <?php if ($err): ?><div class="banner err" role="alert" ><?= h($err) ?></div><?php endif; ?>

      <?php /* ─────────── 개인정보 ─────────── */ if ($tab === 'profile'): ?>
        <h1 class="page-title">개인정보</h1>
        <p class="page-sub">계정의 기본 정보입니다.</p>
        <div class="card">
          <table class="kv">
            <tr><th>아이디</th><td class="mono"><?= h($me['username']) ?></td></tr>
            <tr><th>가입일</th><td class="mono"><?= h($row['created_at'] ?? '-') ?></td></tr>
            <tr><th>2단계 인증</th><td>사용 중 (TOTP)</td></tr>
            <tr><th>백업 코드</th><td><?= (int)$remaining ?>개 남음</td></tr>
          </table>
        </div>

      <?php /* ─────────── 로그 ─────────── */ elseif ($tab === 'log'): ?>
        <h1 class="page-title">로그</h1>
        <p class="page-sub">현재 세션 정보와 최근 인증 기록입니다. 출발지 IP가 평소와 다르면 계정이 도용되었을 수 있습니다.</p>

        <div class="card">
          <h2 class="card-title">현재 세션</h2>
          <table class="kv">
            <tr><th>로그인 시각</th><td class="mono"><?= h($_SESSION['login_at'] ?? '-') ?></td></tr>
            <tr><th>세션 ID</th><td class="mono"><?= h(session_id()) ?></td></tr>
            <tr><th>출발지 IP</th><td class="mono"><?= h($src_ip) ?></td></tr>
            <tr><th>X-Forwarded-For</th><td class="mono"><?= h($xff) ?></td></tr>
          </table>
        </div>

        <div class="card">
          <h2 class="card-title">최근 인증 기록</h2>
          <?php if (!$logs): ?>
            <p class="muted">기록이 없습니다.</p>
          <?php else: ?>
            <div class="table-scroll">
              <table class="log">
                <thead>
                  <tr><th>시각(UTC)</th><th>단계</th><th>결과</th><th>출발지 IP</th><th>X-Forwarded-For</th></tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                  <tr>
                    <td class="mono"><?= h($l['at']) ?></td>
                    <td><?= h($l['stage']) ?></td>
                    <td><?= $l['result'] === 'ok'
                          ? '<span class="ok-dot">성공</span>'
                          : '<span class="bad-dot">실패</span>' ?></td>
                    <td class="mono"><?= h($l['src_ip']) ?></td>
                    <td class="mono"><?= h(($l['xff'] ?? '') !== '' ? $l['xff'] : '-') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      <?php /* ─────────── 보안 ─────────── */ elseif ($tab === 'security'): ?>
        <h1 class="page-title">보안</h1>
        <p class="page-sub">비밀번호와 2단계 인증을 관리합니다.</p>

        <?php if ($newSecret): ?>
          <div class="card highlight">
            <h2 class="card-title">새 OTP 비밀키</h2>
            <p class="muted">인증 앱에 등록하세요. <strong>이 화면을 벗어나면 다시 볼 수 없습니다.</strong></p>
            <div class="secret-box"><?= h($newSecret) ?></div>
            <div class="secret-box tiny">oathtool --totp -b <?= h($newSecret) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($newCodes): ?>
          <div class="card highlight">
            <h2 class="card-title">새 백업 코드</h2>
            <p class="muted">각 코드는 <strong>한 번만</strong> 쓸 수 있습니다. <strong>지금 파일로 받아 안전한 곳에 보관하세요.</strong></p>

            <?php /* 코드 값은 숨겨 두고 내려받기 버튼이 읽어 간다 */ ?>
            <div id="codeStore" hidden>
              <?php foreach ($newCodes as $c): ?><i data-code="<?= h($c) ?>"></i><?php endforeach; ?>
            </div>

            <button type="button" class="btn primary" data-download-codes="#codeStore"
                    data-user="<?= h($me['username']) ?>">백업 코드 내려받기 (.txt)</button>

            <details class="codes-reveal">
              <summary>화면으로 보기</summary>
              <div class="code-grid">
                <?php foreach ($newCodes as $c): ?><span class="code-chip"><?= h($c) ?></span><?php endforeach; ?>
              </div>
            </details>
          </div>
        <?php endif; ?>

        <div class="card split">
          <div>
            <h2 class="card-title">비밀번호 변경</h2>
            <p class="muted">변경하면 현재 세션 ID가 새로 발급되어, 탈취된 세션이 있더라도 무력화됩니다.</p>
          </div>
          <form method="post" action="/settings" autocomplete="off">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <label for="cp">현재 비밀번호</label>
            <input id="cp" name="current_password" type="password" autocomplete="current-password" required>
            <label for="np">새 비밀번호</label>
            <input id="np" name="new_password" type="password" placeholder="4자 이상" autocomplete="new-password" required>
            <label for="np2">새 비밀번호 확인</label>
            <input id="np2" name="new_password2" type="password" autocomplete="new-password" required>
            <button class="btn primary" type="submit">비밀번호 변경</button>
          </form>
        </div>

        <div class="card split">
          <div>
            <h2 class="card-title">2단계 인증 재설정</h2>
            <p class="muted">인증 앱을 바꾸거나 비밀키가 유출됐을 때 새로 발급합니다. 기존 비밀키로 만든 코드는 더 이상 쓸 수 없습니다.</p>
          </div>
          <form method="post" action="/settings" autocomplete="off">
            <?= csrf_field() ?><input type="hidden" name="action" value="otp">
            <label for="op">비밀번호 확인</label>
            <input id="op" name="otp_password" type="password" autocomplete="current-password" required>
            <button class="btn" type="submit">새 OTP 비밀키 발급</button>
          </form>
        </div>

        <div class="card split">
          <div>
            <h2 class="card-title">백업 코드</h2>
            <p class="muted">
              휴대폰을 잃어버렸을 때 OTP 대신 쓰는 일회용 코드입니다.
              <strong>OTP 재설정은 로그인한 뒤에만 가능</strong>하므로, 백업 코드가 없으면 계정에 다시 들어올 방법이 없습니다.<br><br>
              남은 코드: <strong><?= (int)$remaining ?>개</strong>
              <?php if ($remaining === 0): ?><span class="bad-dot">아직 발급받지 않았습니다.</span><?php endif; ?>
            </p>
          </div>
          <form method="post" action="/settings" autocomplete="off">
            <?= csrf_field() ?><input type="hidden" name="action" value="recovery">
            <label for="rp">비밀번호 확인</label>
            <input id="rp" name="recovery_password" type="password" autocomplete="current-password" required>
            <button class="btn" type="submit"><?= $remaining > 0 ? '백업 코드 새로 발급' : '백업 코드 발급' ?></button>
          </form>
        </div>

      <?php /* ─────────── 계정 삭제 ─────────── */ else: ?>
        <h1 class="page-title">계정 삭제</h1>
        <p class="page-sub">삭제를 요청하면 <?= DELETE_GRACE_DAYS ?>일간의 유예 기간이 시작됩니다.</p>

        <div class="card danger split">
          <div>
            <h2 class="card-title">계정을 삭제합니다</h2>
            <p class="muted">
              유예 기간 안에 다시 로그인하면 <strong>되돌릴 수 있고</strong>, 기간이 지나면 영구 삭제됩니다.
              확인을 위해 비밀번호와 아이디를 함께 입력해야 합니다.
            </p>
          </div>
          <form method="post" action="/settings" autocomplete="off"
                onsubmit="return confirm('정말 계정을 삭제하시겠습니까? 유예 기간 안에는 되돌릴 수 있습니다.');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <label for="dp">비밀번호</label>
            <input id="dp" name="delete_password" type="password" autocomplete="current-password" required>
            <label for="dc">확인 — 아이디 <code><?= h($me['username']) ?></code> 입력</label>
            <input id="dc" name="delete_confirm" type="text" placeholder="<?= h($me['username']) ?>" autocomplete="off" required>
            <button class="btn danger-btn" type="submit">계정 삭제</button>
          </form>
        </div>
      <?php endif; ?>
    </section>
  </main>

<?php app_footer(); ?>
</body>
</html>
