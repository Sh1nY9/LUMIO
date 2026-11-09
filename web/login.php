<?php
require_once __DIR__ . '/lib/common.php';

if (!empty($_SESSION['authed'])) { header('Location: /'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['email'] ?? '');
    $p = $_POST['password'] ?? '';
    $row = find_user($u);

    if ($row && password_verify($p, $row['pwhash'])) {
        // 1단계 통과 — 아직 인증 완료가 아니다. OTP 단계로 넘긴다.
        $_SESSION['pending_user'] = $u;
        $_SESSION['authed'] = false;
        log_login($u, 'password', 'ok');
        header('Location: /otp');
        exit;
    }
    log_login($u !== '' ? $u : null, 'password', 'fail');
    $err = '아이디 또는 비밀번호가 잘못되었습니다.';
}

// 계정 삭제 직후 돌아온 경우 안내
$notice = isset($_GET['deleted']) ? '계정이 삭제되었습니다.' : '';

lumio_head('로그인');
lumio_visual('WELCOME TO LUMIO', 'Make space for<br />better ideas.',
             '집중을 더 쉽게, 일상을 더 선명하게 만들어 보세요.');
?>
      <div class="form-panel">
        <div class="brand-mark" aria-hidden="true">✱</div>
        <div class="heading-group">
          <p class="eyebrow">WELCOME BACK</p>
          <h1>로그인</h1>
          <p>계정에 로그인하고 나만의 작업을<br />계속 이어가세요.</p>
        </div>

        <form class="auth-form" id="loginForm" action="/login" method="post">
          <label for="email">아이디</label>
          <input id="email" name="email" type="text" placeholder="아이디를 입력하세요"
                 autocomplete="username" value="<?= h($_POST['email'] ?? '') ?>" required />

          <div class="label-row">
            <label for="password">비밀번호</label>
            <a href="#" class="text-link">비밀번호 찾기</a>
          </div>
          <div class="password-field">
            <input id="password" name="password" type="password" placeholder="비밀번호를 입력하세요"
                   autocomplete="current-password" required />
            <button type="button" class="password-toggle" aria-label="비밀번호 표시">◉</button>
          </div>

          <button type="submit" class="primary-button">로그인</button>
          <p class="form-error" id="formError" role="alert" aria-live="polite"><?= h($err) ?></p>
          <?php if ($notice): ?><p class="form-notice" role="status"><?= h($notice) ?></p><?php endif; ?>
        </form>

        <div class="divider"><span>또는</span></div>
        <div class="social-row">
          <button type="button" class="social-button"><span class="google-icon">G</span>Google</button>
          <button type="button" class="social-button"><span class="apple-icon">●</span>Apple</button>
        </div>
        <p class="bottom-copy">아직 계정이 없나요? <a href="/signup" class="text-link">회원가입</a></p>
      </div>
<?php lumio_foot(); ?>
<script src="/auth.js" defer></script>
