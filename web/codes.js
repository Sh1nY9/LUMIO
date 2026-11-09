// 백업 코드를 .txt 파일로 내려받는다.
// 코드는 화면에 흩뿌리지 않고 숨겨 두었다가 이 스크립트가 읽어 파일로 만든다.
// 서버 왕복 없이 브라우저에서 바로 만들어 받는다.

document.querySelectorAll('[data-download-codes]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const store = document.querySelector(btn.dataset.downloadCodes);
    if (!store) return;

    const codes = Array.from(store.querySelectorAll('[data-code]')).map((e) => e.dataset.code);
    if (!codes.length) return;

    const user = btn.dataset.user || 'account';
    const now  = new Date().toISOString().slice(0, 10);

    const lines = [
      'Lumio 백업 코드',
      '================================',
      `계정   : ${user}`,
      `발급일 : ${now}`,
      '',
      '인증 앱을 쓸 수 없을 때 2단계 인증 화면에서',
      'OTP 대신 아래 코드를 입력하세요.',
      '각 코드는 한 번만 사용할 수 있습니다.',
      '',
      ...codes.map((c, i) => `${String(i + 1).padStart(2, '0')}.  ${c}`),
      '',
      '※ 이 파일을 안전한 곳에 보관하세요.',
      '※ 코드를 모두 쓰거나 잃어버리면 설정에서 새로 발급받을 수 있습니다.',
      ''
    ];

    const blob = new Blob([lines.join('\r\n')], { type: 'text/plain;charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = `lumio-backup-codes-${user}.txt`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);

    const original = btn.textContent;
    btn.textContent = '내려받았습니다 ✓';
    btn.classList.add('done');
    setTimeout(() => { btn.textContent = original; btn.classList.remove('done'); }, 2600);
  });
});
