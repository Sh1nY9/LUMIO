<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/totp.php';

session_start();

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_auth(): array {
    if (empty($_SESSION['authed']) || empty($_SESSION['username'])) {
        header('Location: /login');
        exit;
    }
    return ['username' => $_SESSION['username']];
}

/* ── CSRF 보호 ──────────────────────────────────────────────
   비밀번호 변경·계정 삭제처럼 상태를 바꾸는 요청은, 공격자가 만든
   페이지에서 몰래 제출되는 것(교차 사이트 요청 위조)을 막아야 한다.
   세션마다 난수 토큰을 만들어 폼에 심고, 제출 시 일치하는지 본다. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_ok(): bool {
    return !empty($_POST['csrf'])
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/* ── 인증 화면(로그인/가입/OTP) 레이아웃 ───────────────────── */
function lumio_head(string $title, string $cardClass = ''): void { ?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($title) ?> | Lumio</title>
  <link rel="stylesheet" href="/style.css" />
</head>
<body>
  <main class="page-shell">
    <section class="auth-card <?= h($cardClass) ?>" aria-label="<?= h($title) ?> 영역">
<?php }

function lumio_visual(string $eyebrow, string $h2, string $desc): void { ?>
      <div class="visual-panel">
        <img class="panel-image" src="/MainImage.jpg" alt="" aria-hidden="true" />
        <div class="visual-mark" aria-hidden="true">✱</div>
        <div class="visual-copy">
          <p class="eyebrow"><?= h($eyebrow) ?></p>
          <h2><?= $h2 ?></h2>
          <p class="visual-description"><?= h($desc) ?></p>
        </div>
        <div class="visual-orb orb-one"></div>
        <div class="visual-orb orb-two"></div>
      </div>
<?php }

function lumio_foot(): void { ?>
    </section>
  </main>
</body>
</html>
<?php }

/* ── 로그인 후 공통 헤더 ────────────────────────────────────
   프로필 아바타를 누르면 설정·로그아웃이 들어 있는 메뉴가 열린다.
   메뉴는 <details> 로 만들어 JS 없이도 동작한다. */
function app_header(string $username, string $active = 'home'): void {
    $initial = strtoupper(substr($username, 0, 1));
    ?>
  <header class="app-header">
    <a class="app-logo" href="/"><span>✱</span> LUMIO</a>

    <!-- 계정 관련 메뉴는 헤더에 노출하지 않는다. 오른쪽 프로필 아바타를
         눌렀을 때 열리는 메뉴로만 들어간다. -->
    <nav class="app-nav" aria-label="주요 메뉴">
      <a href="/"<?= $active === 'home' ? ' class="active"' : '' ?>>홈</a>
      <a href="/saved"<?= $active === 'saved' ? ' class="active"' : '' ?>>아카이브</a>
    </nav>

    <details class="avatar-menu">
      <summary aria-label="계정 메뉴" title="<?= h($username) ?>">
        <span class="avatar"><?= h($initial) ?></span>
      </summary>
      <div class="menu-panel" role="menu">
        <div class="menu-head">
          <span class="avatar lg"><?= h($initial) ?></span>
          <div>
            <strong><?= h($username) ?></strong>
            <span>로그인됨</span>
          </div>
        </div>
        <div class="menu-sep"></div>
        <?php /* 개인정보·로그·보안은 설정 안의 탭이므로 메뉴에서는 '설정' 하나로 묶는다.
                 PHP 주석이라 브라우저로 전송되지 않는다. */ ?>
        <a role="menuitem" href="/settings"><span class="mi">⚙</span> 설정</a>
        <a role="menuitem" class="danger" href="/logout"><span class="mi">⏻</span> 로그아웃</a>
      </div>
    </details>
  </header>
<?php }

function app_footer(): void { ?>
  <footer class="app-footer">
    <span>✱ LUMIO</span>
    <span>Curated references for curious people.</span>
  </footer>
<?php }
