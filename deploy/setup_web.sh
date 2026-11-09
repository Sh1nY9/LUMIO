#!/bin/bash
set -e

echo "##### [0] 대상 확인"
hostname; ip -4 addr show enp0s3 | grep inet

echo "##### [1] apache2 + PHP 설치"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq apache2 php libapache2-mod-php php-sqlite3 oathtool
apache2 -v | head -1; php -v | head -1

echo "##### [2] DB 디렉터리 (웹 루트 밖)"
mkdir -p /var/lib/sunrinns
chown www-data:www-data /var/lib/sunrinns
chmod 750 /var/lib/sunrinns

echo "##### [3] 기존 웹 루트 백업 후 배포"
ts=$(date +%Y%m%d-%H%M%S)
if [ -f /var/www/html/index.html ] || [ -f /var/www/html/index.php ]; then
  mkdir -p "/root/www-backup-$ts"
  cp -a /var/www/html/. "/root/www-backup-$ts/" 2>/dev/null || true
  echo "백업: /root/www-backup-$ts"
fi
rm -rf /var/www/html/*
cp -a /tmp/websrv/. /var/www/html/

echo "##### [4] style.css 보정 + extra.css 병합"
# 원본 CSS가 존재하지 않는 MainImage.jpeg 를 참조하는 오타가 있어 수정
sed -i "s/MainImage\.jpeg/MainImage.jpg/g" /var/www/html/style.css
cat /var/www/html/extra.css >> /var/www/html/style.css
rm -f /var/www/html/extra.css

echo "##### [5] 권한"
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

echo "##### [6] Apache 설정 (ServerName, lib 접근 차단)"
cat > /etc/apache2/conf-available/sunrinns.conf <<'EOF'
ServerName sunrinNS.com

# 라이브러리 디렉터리는 웹에서 직접 접근 금지
<Directory /var/www/html/lib>
    Require all denied
</Directory>

# 디렉터리 목록 노출 금지
<Directory /var/www/html>
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
    DirectoryIndex index.php index.html
</Directory>
EOF
a2enconf sunrinns >/dev/null
apache2ctl configtest

echo "##### [7] 기동"
systemctl enable apache2 >/dev/null 2>&1 || true
systemctl restart apache2
sleep 2
echo -n "apache2 상태: "; systemctl is-active apache2

echo "##### [8] 동작 테스트 (로컬)"
printf '%-34s -> ' "GET / (→login 리다이렉트)"; curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' http://127.0.0.1/
printf '%-34s -> ' "GET /login.php";            curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/login.php
printf '%-34s -> ' "GET /signup.php";           curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/signup.php
printf '%-34s -> ' "GET /style.css";            curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/style.css
printf '%-34s -> ' "GET /MainImage.jpg";        curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/MainImage.jpg
printf '%-34s -> ' "GET /lib/db.php (차단 기대)"; curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/lib/db.php
printf '%-34s -> ' "GET /success.php (미인증)";  curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' http://127.0.0.1/success.php

echo "##### [9] 로그인 페이지 렌더 확인"
curl -s http://127.0.0.1/login.php | grep -oE '<title>[^<]*</title>|class="(auth-card|form-panel|primary-button)"' | head -5

echo "##### 완료"
