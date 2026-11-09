// 로그인 후 화면의 공통 동작

// 프로필 드롭다운 — <details> 는 바깥을 눌러도 닫히지 않으므로 직접 닫아준다.
// (열어둔 채 페이지를 가리는 일이 없게 한다)
const menu = document.querySelector('.avatar-menu');

document.addEventListener('click', (e) => {
  if (menu && menu.open && !menu.contains(e.target)) menu.open = false;
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && menu?.open) {
    menu.open = false;
    menu.querySelector('summary')?.focus();
  }
});
