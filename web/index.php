<?php
require_once __DIR__ . '/lib/common.php';
// 홈 = 아카이브. 2단계 인증까지 끝난 세션만 접근 가능.
$me = require_auth();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lumio</title>
  <link rel="stylesheet" href="/style.css" />
  <link rel="stylesheet" href="/app.css" />
  <script src="/app.js" defer></script>
  <script src="/content.js" defer></script>
</head>
<body class="app-body">
<?php app_header($me['username'], 'home'); ?>

  <main class="feed">
    <!-- 핀터레스트처럼 군더더기 없이 검색과 필터만 상단에 둔다 -->
    <div class="feed-bar">
      <label class="feed-search">
        <span aria-hidden="true">⌕</span>
        <input id="referenceSearch" type="search" placeholder="검색" aria-label="레퍼런스 검색" />
      </label>
      <div class="chips" role="tablist" aria-label="카테고리">
        <button class="chip active" data-category="all" type="button">전체</button>
        <button class="chip" data-category="web" type="button">Web</button>
        <button class="chip" data-category="branding" type="button">Branding</button>
        <button class="chip" data-category="graphic" type="button">Graphic</button>
      </div>
    </div>

    <section class="masonry" id="referenceGrid" aria-live="polite"></section>
    <p class="feed-empty" id="emptyState" hidden>검색 결과가 없습니다.</p>
  </main>

<?php app_footer(); ?>
</body>
</html>
