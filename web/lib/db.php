<?php
// SQLite DB. 웹 루트 밖에 두어 직접 다운로드되지 않게 한다.
const DB_PATH = '/var/lib/sunrinns/app.db';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        username    TEXT UNIQUE NOT NULL,
        pwhash      TEXT NOT NULL,
        totp_secret TEXT NOT NULL,
        created_at  TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    // 백업 코드 — OTP 기기를 잃어버렸을 때 쓰는 일회용 코드.
    // 평문이 아니라 해시로 저장하고, 한 번 쓰면 used_at 을 채워 재사용을 막는다.
    $pdo->exec("CREATE TABLE IF NOT EXISTS recovery_codes (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        username   TEXT NOT NULL,
        code_hash  TEXT NOT NULL,
        used_at    TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_recovery_user ON recovery_codes (username)");

    // ── 마이그레이션: 기존 DB에 deleted_at 컬럼이 없으면 추가 ──
    // 계정 삭제를 즉시 삭제가 아니라 "유예 삭제"로 바꾸기 위한 컬럼.
    // 실수로 지웠을 때 유예 기간 안에 다시 로그인하면 되돌릴 수 있다.
    $cols = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('deleted_at', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN deleted_at TEXT');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS login_log (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        username  TEXT,
        stage     TEXT,
        result    TEXT,
        src_ip    TEXT,
        xff       TEXT,
        ua        TEXT,
        at        TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    return $pdo;
}

// 접속 출발지 기록 — AiTM 분석 때 "출발지 IP가 공격자로 바뀐다"를 보이는 근거가 된다.
function log_login(?string $user, string $stage, string $result): void {
    $st = db()->prepare('INSERT INTO login_log (username,stage,result,src_ip,xff,ua)
                         VALUES (?,?,?,?,?,?)');
    $st->execute([
        $user, $stage, $result,
        $_SERVER['REMOTE_ADDR']          ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_USER_AGENT']      ?? '',
    ]);
}

function find_user(string $username): ?array {
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$username]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

/* ── 백업 코드 ─────────────────────────────────────────────
   OTP 기기를 잃어버리면 로그인할 수 없고, OTP 재설정은 로그인 후에만
   가능하므로 계정이 완전히 잠긴다. 그 막다른 길을 여는 수단이다.

   ※ 보고서용 메모: 백업 코드도 결국 "사람이 눈으로 읽어 옮겨 적는 값"이다.
     따라서 AiTM 은 OTP 와 똑같이 이 코드도 실시간 중계할 수 있다.
     피싱 저항성을 주는 것은 코드의 종류가 아니라 도메인 바인딩이다. */

/** 혼동하기 쉬운 글자(I, O, 0, 1)를 뺀 8자리 코드. 예: A7KD-M3PQ */
function recovery_code_new(): string {
    $alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 8; $i++) {
        if ($i === 4) $s .= '-';
        $s .= $alpha[random_int(0, strlen($alpha) - 1)];
    }
    return $s;
}

/** 입력값을 표준형(XXXX-XXXX 대문자)으로 맞춘다. 하이픈·공백 유무를 가리지 않는다. */
function recovery_normalize(string $s): string {
    $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $s));
    return strlen($s) === 8 ? substr($s, 0, 4) . '-' . substr($s, 4) : $s;
}

/** 기존 코드를 모두 버리고 새로 n개 발급. 평문은 이때 한 번만 반환된다. */
function recovery_generate(string $user, int $n = 8): array {
    $pdo = db();
    $pdo->prepare('DELETE FROM recovery_codes WHERE username = ?')->execute([$user]);
    $ins = $pdo->prepare('INSERT INTO recovery_codes (username, code_hash) VALUES (?,?)');
    $codes = [];
    for ($i = 0; $i < $n; $i++) {
        $c = recovery_code_new();
        $codes[] = $c;
        $ins->execute([$user, password_hash($c, PASSWORD_DEFAULT)]);
    }
    return $codes;
}

/** 코드가 맞으면 소모 처리하고 true. 한 번 쓴 코드는 다시 통하지 않는다. */
function recovery_consume(string $user, string $code): bool {
    $code = recovery_normalize($code);
    if ($code === '') return false;

    $st = db()->prepare('SELECT id, code_hash FROM recovery_codes
                         WHERE username = ? AND used_at IS NULL');
    $st->execute([$user]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (password_verify($code, $r['code_hash'])) {
            db()->prepare("UPDATE recovery_codes SET used_at = datetime('now') WHERE id = ?")
                ->execute([$r['id']]);
            return true;
        }
    }
    return false;
}

/** 아직 쓰지 않은 코드 개수 */
function recovery_remaining(string $user): int {
    $st = db()->prepare('SELECT COUNT(*) FROM recovery_codes
                         WHERE username = ? AND used_at IS NULL');
    $st->execute([$user]);
    return (int)$st->fetchColumn();
}

/* ── 유예 삭제 ─────────────────────────────────────────────
   "삭제" 버튼을 눌러도 데이터를 바로 지우지 않고 삭제 예정 표시만 남긴다.
   유예 기간(DELETE_GRACE_DAYS) 안에 다시 로그인하면 복구할 수 있고,
   기간이 지나면 관리자 도구(sunrinns-admin purge)가 실제로 지운다.
   실수로 누른 삭제를 되돌릴 수 있게 하는 것이 목적이다. */
const DELETE_GRACE_DAYS = 7;

function mark_deleted(string $user): void {
    db()->prepare("UPDATE users SET deleted_at = datetime('now') WHERE username = ?")
        ->execute([$user]);
}

function restore_user(string $user): void {
    db()->prepare('UPDATE users SET deleted_at = NULL WHERE username = ?')
        ->execute([$user]);
}

/** 삭제 예약 상태면 남은 일수를, 아니면 null 을 돌려준다. */
function deletion_days_left(?array $row): ?int {
    if (!$row || empty($row['deleted_at'])) return null;
    $when = strtotime($row['deleted_at'] . ' UTC');
    if ($when === false) return null;
    $left = DELETE_GRACE_DAYS - (int)floor((time() - $when) / 86400);
    return max(0, $left);
}
