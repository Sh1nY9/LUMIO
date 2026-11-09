<?php
require_once __DIR__ . '/lib/common.php';
// 저장한 레퍼런스 모아보기. 저장 목록은 브라우저(localStorage)에 보관하므로
// 서버에는 아무것도 저장하지 않는다 — 이 실습의 관심사는 인증이지 데이터가 아니다.
$me = require_auth();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>아카이브 | Lumio</title>
  <link rel="stylesheet" href="/style.css" />
  <link rel="stylesheet" href="/app.css" />
  <script src="/app.js" defer></script>
  <script src="/content.js" defer></script>
</head>
<body class="app-body">
<?php app_header($me['username'], 'saved'); ?>

  <main class="feed">
    <div class="feed-bar">
      <div class="feed-heading">
        <h1>아카이브</h1>
        <p id="savedCount">—</p>
      </div>
      <button class="chip ghost" id="clearSaved" type="button" hidden>전체 비우기</button>
    </div>

    <section class="masonry" id="referenceGrid" data-mode="saved" aria-live="polite"></section>
    <p class="feed-empty" id="emptyState" hidden>
      아직 저장한 레퍼런스가 없습니다.<br />
      홈에서 카드의 ♡ 를 눌러 저장해 보세요.
    </p>
  </main>

<?php app_footer(); ?>
</body>
</html>
