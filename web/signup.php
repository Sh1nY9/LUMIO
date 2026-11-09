<?php
require_once __DIR__ . '/lib/common.php';

$err = ''; $done = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u  = trim($_POST['email'] ?? '');
    $p  = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';

    if (!csrf_ok())                                    $err = '요청이 만료되었습니다. 새로고침 후 다시 시도하세요.';
    elseif ($u === '' || $p === '')                    $err = '아이디와 비밀번호를 모두 입력하세요.';
    elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $u)) $err = '아이디는 영문·숫자·밑줄 3~20자여야 합니다.';
    elseif (strlen($p) < 4)                            $err = '비밀번호는 4자 이상이어야 합니다.';
    elseif ($p !== $p2)                                $err = '비밀번호가 일치하지 않습니다.';
    elseif (find_user($u))                             $err = '이미 사용 중인 아이디입니다.';
    else {
        $secret = totp_secret_new();
        db()->prepare('INSERT INTO users (username,pwhash,totp_secret) VALUES (?,?,?)')
            ->execute([$u, password_hash($p, PASSWORD_DEFAULT), $secret]);
        // 백업 코드를 함께 발급한다. 이게 없으면 인증 앱을 잃었을 때
        // 계정에 다시 들어올 방법이 없다(OTP 재설정은 로그인 후에만 가능).
        $codes = recovery_generate($u);
        log_login($u, 'signup', 'ok');
        $done = ['user' => $u, 'secret' => $secret, 'uri' => totp_uri($u, $secret), 'codes' => $codes];
    }
}

lumio_head('회원가입');
lumio_visual('JOIN LUMIO', 'Start your<br />own space.',
             '계정을 만들고 나만의 작업 공간을 시작해 보세요.');

if ($done): ?>
      <div class="form-panel">
        <div class="brand-mark" aria-hidden="true">✱</div>
        <div class="heading-group">
          <p class="eyebrow">ACCOUNT CREATED</p>
          <h1>가입 완료</h1>
          <p><strong><?= h($done['user']) ?></strong> 계정이 만들어졌습니다.</p>
        </div>

        <div class="notice warn">
          아래 <strong>OTP 비밀키</strong>를 인증 앱에 등록하세요.<br />이 화면을 벗어나면 다시 볼 수 없습니다.
        </div>

        <label class="mini-label">OTP 비밀키 (Base32)</label>
        <div class="secret-box"><?= h($done['secret']) ?></div>

        <div class="notice warn" style="margin-top:18px">
          <strong>백업 코드</strong>도 받아 두세요. 인증 앱을 쓸 수 없을 때
          OTP 대신 입력하는 일회용 코드입니다. 각 코드는 한 번만 쓸 수 있습니다.
        </div>

        <?php /* 코드 값은 숨겨 두고 내려받기 버튼이 읽어 간다 */ ?>
        <div id="codeStore" hidden>
          <?php foreach ($done['codes'] as $c): ?><i data-code="<?= h($c) ?>"></i><?php endforeach; ?>
        </div>

        <button type="button" class="primary-button" data-download-codes="#codeStore"
                data-user="<?= h($done['user']) ?>">백업 코드 내려받기 (.txt)</button>

        <a class="primary-button button-link ghost" href="/login">로그인하러 가기</a>
      </div>
<?php else: ?>
      <div class="form-panel">
        <div class="brand-mark" aria-hidden="true">✱</div>
        <div class="heading-group">
          <p class="eyebrow">CREATE ACCOUNT</p>
          <h1>회원가입</h1>
          <p>가입 시 2단계 인증(OTP)이<br />자동으로 설정됩니다.</p>
        </div>

        <form class="auth-form" method="post" action="/signup">
          <?= csrf_field() ?>
          <label for="email">아이디</label>
          <input id="email" name="email" type="text" placeholder="영문·숫자 3~20자"
                 autocomplete="username" value="<?= h($_POST['email'] ?? '') ?>" required />

          <label for="password">비밀번호</label>
          <div class="password-field">
            <input id="password" name="password" type="password" placeholder="비밀번호를 입력하세요"
                   autocomplete="new-password" required />
            <button type="button" class="password-toggle" aria-label="비밀번호 표시">◉</button>
          </div>

          <label for="password2">비밀번호 확인</label>
          <input id="password2" name="password2" type="password" placeholder="비밀번호를 다시 입력하세요"
                 autocomplete="new-password" required />

          <button type="submit" class="primary-button">가입하기</button>
          <p class="form-error" role="alert" aria-live="polite"><?= h($err) ?></p>
        </form>

        <p class="bottom-copy">이미 계정이 있나요? <a href="/login" class="text-link">로그인</a></p>
      </div>
<?php endif;
lumio_foot(); ?>
<script src="/auth.js" defer></script>
<script src="/codes.js" defer></script>
