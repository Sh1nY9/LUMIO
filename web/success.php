<?php
require_once __DIR__ . '/lib/common.php';
$me = require_auth();

lumio_head('로그인 성공', 'success-card');
lumio_visual('YOUR SPACE IS READY', 'Welcome back,<br />stay inspired.',
             '당신의 아이디어가 더 멀리 나아갈 수 있도록 준비했어요.');
?>
      <div class="form-panel success-panel">
        <div class="success-icon" aria-hidden="true">✓</div>
        <p class="eyebrow">LOGIN COMPLETE</p>
        <h1>로그인 성공!</h1>
        <p class="success-message">
          <strong><?= h($me['username']) ?></strong> 님, 환영합니다.
        </p>

        <a class="primary-button button-link" href="/">홈으로 이동</a>

        <p class="bottom-copy">
          세션 정보와 인증 기록은 <a href="/settings/log" class="text-link">로그</a>에서 확인할 수 있습니다.
        </p>
      </div>
<?php lumio_foot();
