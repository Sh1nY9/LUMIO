// 비밀번호 표시/숨김 토글.
// 원본 login_ui 의 프론트엔드 데모 인증(admin/admin)은 제거했다.
// 인증은 서버(PHP)에서 비밀번호 해시 검증 + TOTP 2단계로 처리한다.
document.querySelectorAll('.password-toggle').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const input = toggle.parentElement.querySelector('input');
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    toggle.textContent = isPassword ? '○' : '◉';
    toggle.setAttribute('aria-label', isPassword ? '비밀번호 숨기기' : '비밀번호 표시');
  });
});

// OTP 입력칸: 숫자만 허용하고 6자리 채우면 자동 제출
const otp = document.querySelector('.otp-input');
if (otp) {
  otp.addEventListener('input', () => {
    otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
    if (otp.value.length === 6) otp.form?.requestSubmit();
  });
}

// 백업 코드 입력칸: 소문자를 쳐도 실제 값이 대문자로 들어가고,
// 하이픈도 자동으로 붙는다. (CSS 의 text-transform 은 보이기만 바꿀 뿐이다)
const code = document.querySelector('.code-input');
if (code) {
  code.addEventListener('input', () => {
    const atEnd = code.selectionStart === code.value.length;
    const raw = code.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
    const formatted = raw.length > 4 ? raw.slice(0, 4) + '-' + raw.slice(4) : raw;
    if (formatted !== code.value) {
      code.value = formatted;
      if (atEnd) code.setSelectionRange(formatted.length, formatted.length);
    }
  });
}
