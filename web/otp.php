<?php
require_once __DIR__ . '/lib/common.php';

// 1단계(비밀번호)를 통과하지 않았으면 접근 불가
if (empty($_SESSION['pending_user'])) { header('Location: /login'); exit; }

$user = $_SESSION['pending_user'];
$err  = '';
// 링크(GET)로도, 폼 제출(POST)로도 모드가 올 수 있다.
$mode = $_POST['mode'] ?? $_GET['mode'] ?? 'otp';
if ($mode !== 'recovery') $mode = 'otp';

/** 2단계까지 통과한 '인증된 세션'으로 승격한다. */
function finish_login(string $user, ?array $row, string $how): void {
    // 세션 고정 공격 방지를 위해 ID 재발급.
    // ※ AiTM 공격자가 노리는 것이 바로 이 시점에 발급되는 최종 세션 쿠키다.
    session_regenerate_id(true);
    $_SESSION['authed']   = true;
    $_SESSION['username'] = $user;
    $_SESSION['login_at'] = date('Y-m-d H:i:s');
    unset($_SESSION['pending_user']);
    log_login($user, $how, 'ok');
    // 삭제 예약 상태면 복구 버튼이 있는 설정 화면으로 바로 보낸다.
    header('Location: ' . (empty($row['deleted_at']) ? '/success' : '/settings'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $row = find_user($user);

    // 백업 코드 경로 — 인증 앱을 쓸 수 없을 때의 유일한 출구다.
    // (OTP 재설정은 로그인 후에만 가능하므로 이게 없으면 계정이 완전히 잠긴다)
    if ($mode === 'recovery') {
        if ($row && recovery_consume($user, trim($_POST['recovery_code'] ?? ''))) {
            finish_login($user, $row, 'recovery');
        }
        log_login($user, 'recovery', 'fail');
        $err = '백업 코드가 올바르지 않거나 이미 사용된 코드입니다.';
    }
    // OTP 경로
    elseif ($row && totp_verify($row['totp_secret'], trim($_POST['code'] ?? ''))) {
        finish_login($user, $row, 'otp');
    }
    else {
        log_login($user, 'otp', 'fail');
        $err = 'OTP 코드가 올바르지 않습니다.';
    }
}

lumio_head('2단계 인증');
// 비주얼 패널 문구는 로그인 화면과 동일하게 유지한다.
lumio_visual('WELCOME TO LUMIO', 'Make space for<br />better ideas.',
             '집중을 더 쉽게, 일상을 더 선명하게 만들어 보세요.');
?>
      <div class="form-panel">
        <div class="brand-mark" aria-hidden="true">✱</div>
        <div class="heading-group">
          <p class="eyebrow">TWO-FACTOR AUTHENTICATION</p>
          <h1>2단계 인증</h1>
          <p><strong><?= h($user) ?></strong> 님, 인증 앱에 표시된<br />6자리 코드를 입력하세요.</p>
        </div>

        <?php if ($mode !== 'recovery'): ?>
          <form class="auth-form" method="post" action="/otp">
            <input type="hidden" name="mode" value="otp">
            <label for="code">인증 코드</label>
            <input id="code" name="code" type="text" class="otp-input" placeholder="000000"
                   inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                   autocomplete="one-time-code" required autofocus />
            <button type="submit" class="primary-button">인증하기</button>
            <p class="form-error" role="alert" aria-live="polite"><?= h($err) ?></p>
          </form>

          <p class="alt-link">
            인증 앱을 쓸 수 없나요?
            <a class="text-link" href="/otp?mode=recovery">백업 코드로 로그인</a>
          </p>
        <?php else: ?>
          <form class="auth-form" method="post" action="/otp" autocomplete="off">
            <input type="hidden" name="mode" value="recovery">
            <label for="recovery_code">백업 코드</label>
            <input id="recovery_code" name="recovery_code" type="text" class="code-input"
                   placeholder="XXXX-XXXX" maxlength="9"
                   autocapitalize="characters" autocorrect="off" spellcheck="false"
                   autocomplete="one-time-code" required autofocus />
            <button type="submit" class="primary-button">백업 코드로 인증</button>
            <p class="form-error" role="alert" aria-live="polite"><?= h($err) ?></p>
          </form>

          <p class="alt-link">
            가입하거나 설정에서 발급받은 일회용 코드입니다.
            <a class="text-link" href="/otp">OTP로 돌아가기</a>
          </p>
        <?php endif; ?>

        <p class="bottom-copy">다른 계정으로 로그인하려면 <a href="/logout" class="text-link">여기</a></p>
      </div>
<?php lumio_foot(); ?>
<script src="/auth.js" defer></script>
