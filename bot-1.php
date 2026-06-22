<?php
/**
 * Telegram bot â Stars, Premium, Gift va FixSIM nomer xizmatlari
 * To'lov: HUMO karta (avtomatik)
 * Kanal xabarnomasi: har bir muvaffaqiyatli buyurtma kanalga yoziladi
 */

// âââ .env yuklash âââââââââââââââââââââââââââââââââââââââââââââââ
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
        }
    }
}

// âââ Sozlamalar âââââââââââââââââââââââââââââââââââââââââââââââââ
define('BOT_TOKEN',        $_ENV['BOT_TOKEN']        ?? '8893001472:AAHhcXOXvciHHFJJ9OXxOnVTRZm0XVqFfhQ');
define('ADMIN_ID',   (int)($_ENV['ADMIN_ID']         ?? '8541213007'));
define('HYPERPIN_API_URL', $_ENV['HYPERPIN_API_URL'] ?? 'https://hyperpin.top/api/v1');
define('HYPERPIN_API_KEY', $_ENV['HYPERPIN_API_KEY'] ?? 'hp_859b4f4805a721c8a60e57ed81a2d975');
define('SHOP_ID',    (int)($_ENV['SHOP_ID']          ?? '24'));
define('SHOP_KEY',         $_ENV['SHOP_KEY']         ?? 'sk_f37c8fa4298ab6ec43e91c20d2400a80');
define('SHOP_API',         $_ENV['SHOP_API']         ?? 'https://694bccc3c315b.myxvest1.ru/super/api.php');
define('FIXSIM_API_KEY',   $_ENV['FIXSIM_API_KEY']   ?? 'a708e132cd86637');
define('FIXSIM_API_URL',   $_ENV['FIXSIM_API_URL']   ?? 'https://69eda83c94f75.myxvest2.ru/index');
define('DB_PATH',          $_ENV['DB_PATH']          ?? __DIR__ . '/bot_data.db');

// ð¢ KANAL ID â buyurtma xabarnomasi yuboriladigan kanal
// Masalan: '@mening_kanalim' yoki '-1001234567890'
define('LOG_CHANNEL_ID',   $_ENV['LOG_CHANNEL_ID']   ?? '@spelly_orders');

// âââ Default paketlar âââââââââââââââââââââââââââââââââââââââââââ
define('DEFAULT_STARS_PACKAGES', json_encode([
    ['stars' => 50,   'price' => 15000],
    ['stars' => 100,  'price' => 28000],
    ['stars' => 250,  'price' => 65000],
    ['stars' => 500,  'price' => 125000],
    ['stars' => 1000, 'price' => 240000],
    ['stars' => 2500, 'price' => 580000],
]));

define('DEFAULT_PREMIUM_PACKAGES', json_encode([
    ['months' => 1,  'label' => '1 oy',  'price' => 85000],
    ['months' => 3,  'label' => '3 oy',  'price' => 240000],
    ['months' => 6,  'label' => '6 oy',  'price' => 450000],
    ['months' => 12, 'label' => '12 oy', 'price' => 850000],
]));

define('DEFAULT_GIFT_PACKAGES', json_encode([
    ['gift_id' => 'gift_select:1:15',  'name' => 'ð§¸ Teddy Bear', 'stars_cost' => 15,  'price' => 3000],
    ['gift_id' => 'gift_select:0:15',  'name' => 'ð Heart',      'stars_cost' => 15,  'price' => 3000],
    ['gift_id' => 'gift_select:0:25',  'name' => 'ð Gift Box',   'stars_cost' => 25,  'price' => 5000],
    ['gift_id' => 'gift_select:1:25',  'name' => 'ð¹ Atirgul',    'stars_cost' => 25,  'price' => 5000],
    ['gift_id' => 'gift_select:0:50',  'name' => 'ð Tort',       'stars_cost' => 50,  'price' => 10000],
    ['gift_id' => 'gift_select:1:50',  'name' => 'ð Guldasta',   'stars_cost' => 50,  'price' => 10000],
    ['gift_id' => 'gift_select:2:50',  'name' => 'ð Raketa',     'stars_cost' => 50,  'price' => 10000],
    ['gift_id' => 'gift_select:3:50',  'name' => 'ð¾ Champagne',  'stars_cost' => 50,  'price' => 10000],
    ['gift_id' => 'gift_select:0:100', 'name' => 'ð Kubok',      'stars_cost' => 100, 'price' => 20000],
    ['gift_id' => 'gift_select:1:100', 'name' => 'ð Uzuk',       'stars_cost' => 100, 'price' => 20000],
    ['gift_id' => 'gift_select:2:100', 'name' => 'ð Olmos',      'stars_cost' => 100, 'price' => 20000],
]));

// âââ SQLite ââââââââââââââââââââââââââââââââââââââââââââââââââââââ
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA journal_mode=WAL');
    }
    return $pdo;
}

function init_db(): void {
    $db = get_db();
    $db->exec("CREATE TABLE IF NOT EXISTS settings
        (key TEXT PRIMARY KEY, value TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS orders
        (id INTEGER PRIMARY KEY AUTOINCREMENT,
         user_id INTEGER, order_id INTEGER, type TEXT,
         amount INTEGER, price REAL,
         recipient TEXT DEFAULT '',
         extra_data TEXT DEFAULT '',
         status TEXT DEFAULT 'pending',
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS forced_channels
        (id INTEGER PRIMARY KEY AUTOINCREMENT,
         channel_id TEXT, channel_title TEXT, channel_link TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS users
        (user_id INTEGER PRIMARY KEY, username TEXT, full_name TEXT,
         balance INTEGER DEFAULT 0,
         joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS sms_flood
        (user_id INTEGER, hash_key TEXT, last_checked INTEGER,
         PRIMARY KEY (user_id, hash_key))");

    // ALTER TABLE (agar ustun yo'q bo'lsa)
    foreach (['recipient', 'extra_data'] as $col) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN $col TEXT DEFAULT ''"); } catch (Exception $e) {}
    }

    // Default sozlamalar
    $defaults = [
        'stars_packages'        => DEFAULT_STARS_PACKAGES,
        'premium_packages'      => DEFAULT_PREMIUM_PACKAGES,
        'gift_packages'         => DEFAULT_GIFT_PACKAGES,
        'stars_enabled'         => '1',
        'premium_enabled'       => '1',
        'gift_enabled'          => '1',
        'nomer_enabled'         => '1',
        'nomer_price_overrides' => '{}',
        'welcome_text'          => 'Assalomu alaykum! â­ Stars, ð Premium, ð Gift va ð Telegram nomer xarid qilish uchun quyidagi bo\'limni tanlang.',
    ];
    $st = $db->prepare("INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)");
    foreach ($defaults as $k => $v) $st->execute([$k, $v]);
}

// âââ Settings ââââââââââââââââââââââââââââââââââââââââââââââââââââ
function get_setting(string $key, ?string $default = null): ?string {
    $st = get_db()->prepare("SELECT value FROM settings WHERE key=?");
    $st->execute([$key]);
    $row = $st->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : $default;
}

function set_setting(string $key, string $value): void {
    $st = get_db()->prepare("INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)");
    $st->execute([$key, $value]);
}

// âââ Paketlar ââââââââââââââââââââââââââââââââââââââââââââââââââââ
function get_packages(string $type): array {
    $defaults = [
        'stars'   => DEFAULT_STARS_PACKAGES,
        'premium' => DEFAULT_PREMIUM_PACKAGES,
        'gift'    => DEFAULT_GIFT_PACKAGES,
    ];
    $raw = get_setting($type . '_packages');
    return json_decode($raw ?? $defaults[$type], true) ?? [];
}

function set_packages(string $type, array $pkgs): void {
    set_setting($type . '_packages', json_encode($pkgs, JSON_UNESCAPED_UNICODE));
}

// âââ Nomer override ââââââââââââââââââââââââââââââââââââââââââââââ
function get_nomer_overrides(): array {
    $raw = get_setting('nomer_price_overrides', '{}');
    return json_decode($raw, true) ?? [];
}

function set_nomer_override(string $code, int $price): void {
    $ov = get_nomer_overrides();
    $ov[$code] = $price;
    set_setting('nomer_price_overrides', json_encode($ov));
}

function remove_nomer_override(string $code): void {
    $ov = get_nomer_overrides();
    unset($ov[$code]);
    set_setting('nomer_price_overrides', json_encode($ov));
}

// âââ Majburiy obunalar âââââââââââââââââââââââââââââââââââââââââââ
function get_forced_channels(): array {
    $st = get_db()->query("SELECT id,channel_id,channel_title,channel_link FROM forced_channels");
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function add_forced_channel(string $cid, string $title, string $link): void {
    $st = get_db()->prepare("INSERT INTO forced_channels (channel_id,channel_title,channel_link) VALUES (?,?,?)");
    $st->execute([$cid, $title, $link]);
}

function remove_forced_channel(int $id): void {
    get_db()->prepare("DELETE FROM forced_channels WHERE id=?")->execute([$id]);
}

// âââ Buyurtmalar ââââââââââââââââââââââââââââââââââââââââââââââââ
function create_order(int $uid, int $order_id, string $type, int $amount, float $price, string $recipient = '', string $extra_data = ''): void {
    $st = get_db()->prepare("INSERT INTO orders (user_id,order_id,type,amount,price,recipient,extra_data) VALUES (?,?,?,?,?,?,?)");
    $st->execute([$uid, $order_id, $type, $amount, $price, $recipient, $extra_data]);
}

function get_order(int $order_id): ?array {
    $st = get_db()->prepare("SELECT id,user_id,order_id,type,amount,price,status,recipient,extra_data FROM orders WHERE order_id=?");
    $st->execute([$order_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function update_order_status(int $order_id, string $status): void {
    get_db()->prepare("UPDATE orders SET status=? WHERE order_id=?")->execute([$status, $order_id]);
}

function try_claim_order(int $order_id): bool {
    $st = get_db()->prepare("UPDATE orders SET status='processing' WHERE order_id=? AND status IN ('pending','paid')");
    $st->execute([$order_id]);
    return $st->rowCount() > 0;
}

function get_user_orders(int $uid, int $limit = 10): array {
    $st = get_db()->prepare("SELECT order_id,type,amount,price,status,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
    $st->execute([$uid, $limit]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function get_stats(): array {
    $db = get_db();
    $paid    = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('paid','completed')")->fetchColumn();
    $pending = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $revenue = $db->query("SELECT SUM(price) FROM orders WHERE status IN ('paid','completed')")->fetchColumn() ?: 0;
    $users   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $today   = $db->query("SELECT COUNT(*) FROM users WHERE joined_at >= date('now','-1 day')")->fetchColumn();
    return compact('paid', 'pending', 'revenue', 'users') + ['new_today' => $today];
}

// âââ Foydalanuvchilar ââââââââââââââââââââââââââââââââââââââââââââ
function register_user(int $uid, string $username, string $full_name): bool {
    $db = get_db();
    $st = $db->prepare("SELECT user_id FROM users WHERE user_id=?");
    $st->execute([$uid]);
    $exists = $st->fetch();
    if ($exists) {
        $db->prepare("UPDATE users SET username=?,full_name=? WHERE user_id=?")->execute([$username, $full_name, $uid]);
        return false;
    }
    $db->prepare("INSERT INTO users (user_id,username,full_name) VALUES (?,?,?)")->execute([$uid, $username, $full_name]);
    return true;
}

function get_user(int $uid): ?array {
    $st = get_db()->prepare("SELECT user_id,username,full_name,balance,joined_at FROM users WHERE user_id=?");
    $st->execute([$uid]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function get_all_user_ids(): array {
    return get_db()->query("SELECT user_id FROM users")->fetchAll(PDO::FETCH_COLUMN);
}

// âââ SMS flood tekshiruv âââââââââââââââââââââââââââââââââââââââââ
function fixsim_flood_check(int $uid, string $hash, int $cooldown = 30): bool {
    $now = time();
    $db = get_db();
    $st = $db->prepare("SELECT last_checked FROM sms_flood WHERE user_id=? AND hash_key=?");
    $st->execute([$uid, $hash]);
    $row = $st->fetch(PDO::FETCH_NUM);
    if ($row && ($now - $row[0]) < $cooldown) return false;
    $db->prepare("INSERT OR REPLACE INTO sms_flood (user_id,hash_key,last_checked) VALUES (?,?,?)")->execute([$uid, $hash, $now]);
    return true;
}

// âââ Yordamchilar ââââââââââââââââââââââââââââââââââââââââââââââââ
function fmt($n): string {
    return number_format((int)$n, 0, '.', ' ');
}

function order_status_emoji(string $status): string {
    return ['completed' => 'â', 'paid' => 'â', 'pending' => 'â³', 'expired' => 'â°', 'cancelled' => 'â'][$status] ?? 'â';
}

// âââ Telegram API ââââââââââââââââââââââââââââââââââââââââââââââââ
function tg(string $method, array $params = []): ?array {
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($params),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

function send_message(int|string $chat_id, string $text, array $extra = []): ?array {
    return tg('sendMessage', array_merge(['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'], $extra));
}

function edit_message_text(int|string $chat_id, int $message_id, string $text, array $extra = []): ?array {
    return tg('editMessageText', array_merge(['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $text, 'parse_mode' => 'HTML'], $extra));
}

function answer_callback(string $callback_id, string $text = '', bool $alert = false): void {
    tg('answerCallbackQuery', ['callback_query_id' => $callback_id, 'text' => $text, 'show_alert' => $alert]);
}

function delete_message(int|string $chat_id, int $message_id): void {
    tg('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);
}

function get_chat_member(string $channel_id, int $user_id): ?array {
    $res = tg('getChatMember', ['chat_id' => $channel_id, 'user_id' => $user_id]);
    return $res['ok'] ? $res['result'] : null;
}

// âââ ð¢ KANAL XABARNOMASI ââââââââââââââââââââââââââââââââââââââââ
/**
 * Muvaffaqiyatli buyurtma haqida kanalga xabar yuboradi.
 * Kanal IDsini .env da LOG_CHANNEL_ID sifatida belgilang.
 * Botni kanalga admin qilib qo'shishni unutmang!
 *
 * Misol: @xushnudbek 50 stars sotib oldi â Muvaffaqiyatli yetkazib berildi.
 */
function notify_channel(string $text): void {
    $channel = LOG_CHANNEL_ID;
    if (empty($channel)) return; // Kanal belgilanmagan bo'lsa chiqib ketadi
    tg('sendMessage', [
        'chat_id'    => $channel,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ]);
}

function notify_channel_order(array $order, array $from_user): void {
    $channel = LOG_CHANNEL_ID;
    if (empty($channel)) return;

    $username  = $from_user['username'] ? '@' . $from_user['username'] : $from_user['full_name'];
    $recipient = $order['recipient'] ?? '';
    $price     = fmt($order['price']);
    $oid       = $order['order_id'];

    switch ($order['type']) {
        case 'stars':
            $amount = $order['amount'] . ' Stars';
            $icon   = 'â­';
            $label  = 'Stars';
            break;
        case 'premium':
            $amount = $order['amount'] . ' oy Premium';
            $icon   = 'ð';
            $label  = 'Premium';
            break;
        case 'gift':
            $extra = json_decode($order['extra_data'] ?? '{}', true);
            $amount = ($extra['gift_name'] ?? $order['amount'] . ' stars') . ' Gift';
            $icon   = 'ð';
            $label  = 'Gift';
            break;
        case 'nomer':
            $extra  = json_decode($order['extra_data'] ?? '{}', true);
            $amount = ($extra['country_name'] ?? '') . ' raqam';
            $icon   = 'ð';
            $label  = 'Nomer';
            break;
        default:
            $amount = (string)$order['amount'];
            $icon   = 'â';
            $label  = $order['type'];
    }

    $to = $recipient ? " â¡ï¸ <b>{$recipient}</b>" : '';

    $text = "{$icon} <b>{$username}</b>{$to}\n"
          . "<b>{$amount}</b> sotib oldi\n"
          . "ð° <b>{$price} so'm</b> | ð§¾ #{$oid}\n"
          . "â <b>Muvaffaqiyatli yetkazib berildi!</b>";

    notify_channel($text);
}

// Admin xabarnomasi
function notify_admin(string $title, string $details): void {
    send_message(ADMIN_ID, "ð¨ <b>{$title}</b>\n\n{$details}");
}

// âââ Hyperpin API ââââââââââââââââââââââââââââââââââââââââââââââââ
function hyperpin_call(string $method, string $path, array $data = [], array $params = []): array {
    $url = HYPERPIN_API_URL . $path;
    if ($params) $url .= '?' . http_build_query($params);
    $headers = ['X-API-Key: ' . HYPERPIN_API_KEY];
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $headers];
    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res) return ['success' => false, 'error' => 'Javob yo\'q'];
    $d = json_decode($res, true);
    if (!is_array($d)) return ['success' => false, 'error' => 'Noto\'g\'ri javob'];
    if (isset($d['success']) && $d['success']) return array_merge(['success' => true], $d);
    if (isset($d['id']) || isset($d['balance'])) return array_merge(['success' => true], $d);
    return array_merge(['success' => false, 'error' => $d['error'] ?? 'Noma\'lum xato'], $d);
}

function hyperpin_buy_stars(string $username, int $qty): array {
    return hyperpin_call('POST', '/stars/buy', ['username' => ltrim($username, '@'), 'quantity' => $qty]);
}

function hyperpin_buy_premium(string $username, int $months): array {
    return hyperpin_call('POST', '/premium/buy', ['username' => ltrim($username, '@'), 'months' => $months]);
}

function hyperpin_buy_gift(string $username, string $gift_id, int $stars_cost): array {
    return hyperpin_call('POST', '/gift/buy', ['username' => ltrim($username, '@'), 'gift_id' => $gift_id, 'stars_cost' => $stars_cost]);
}

function hyperpin_balance(): array    { return hyperpin_call('GET', '/balance'); }
function hyperpin_deposit(): array    { return hyperpin_call('GET', '/account/deposit'); }
function hyperpin_stats(): array      { return hyperpin_call('GET', '/account/stats'); }

// âââ FixSIM API ââââââââââââââââââââââââââââââââââââââââââââââââââ
function fixsim_request(array $params): array {
    $url = FIXSIM_API_URL . '?' . http_build_query(array_merge(['key' => FIXSIM_API_KEY], $params));
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
    $res = curl_exec($ch);
    curl_close($ch);
    return ($res ? json_decode($res, true) : null) ?? [];
}

function fixsim_get_countries(): array {
    return fixsim_request(['action' => 'countries'])['countries'] ?? [];
}

function get_countries_with_overrides(): array {
    $countries = fixsim_get_countries();
    $overrides = get_nomer_overrides();
    foreach ($countries as &$c) {
        $code = (string)($c['country_code'] ?? '');
        if (isset($overrides[$code])) {
            $c['price_uzs']       = $overrides[$code];
            $c['price_overridden'] = true;
        } else {
            $c['price_overridden'] = false;
        }
    }
    return $countries;
}

function fixsim_buy_number(string $code): array {
    $res = fixsim_request(['action' => 'getnum', 'code' => $code]);
    if (isset($res['error'])) return ['phone' => null, 'hash' => null, 'error' => $res['error']];
    $phone = $res['phone'] ?? null;
    $hash  = $res['hash']  ?? null;
    if (!$phone || !$hash) return ['phone' => null, 'hash' => null, 'error' => 'no_number'];
    return ['phone' => $phone, 'hash' => $hash, 'error' => null];
}

function fixsim_get_sms(string $hash): array {
    $res = fixsim_request(['action' => 'getsms', 'hash' => $hash]);
    if (isset($res['error'])) return ['sms' => null, 'password' => null, 'error' => $res['error']];
    $sms = $res['sms'] ?? null;
    if (!$sms) return ['sms' => null, 'password' => null, 'error' => 'no_sms'];
    return ['sms' => $sms, 'password' => $res['password'] ?? '', 'error' => null];
}

// âââ HUMO to'lov âââââââââââââââââââââââââââââââââââââââââââââââââ
function humo_create(int $uid, float $amount): array {
    $ch = curl_init(SHOP_API . '?action=create_order');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['shop_id' => SHOP_ID, 'shop_key' => SHOP_KEY, 'amount' => $amount, 'user_id' => (string)$uid]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return ($res ? json_decode($res, true) : null) ?? ['ok' => false, 'error' => 'Javob yo\'q'];
}

function humo_check(int $order_id): ?array {
    $url = SHOP_API . '?' . http_build_query(['action' => 'check', 'order_id' => $order_id, 'shop_id' => SHOP_ID, 'shop_key' => SHOP_KEY]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = $res ? json_decode($res, true) : null;
    return $d['data'] ?? null;
}

// âââ Session (FSM o'rniga fayl asosida) âââââââââââââââââââââââââ
function session_file(int $uid): string {
    return sys_get_temp_dir() . "/tgbot_session_{$uid}.json";
}

function get_session(int $uid): array {
    $f = session_file($uid);
    if (!file_exists($f)) return [];
    return json_decode(file_get_contents($f), true) ?? [];
}

function set_session(int $uid, array $data): void {
    file_put_contents(session_file($uid), json_encode($data));
}

function clear_session(int $uid): void {
    $f = session_file($uid);
    if (file_exists($f)) unlink($f);
}

function update_session(int $uid, array $merge): void {
    $s = get_session($uid);
    set_session($uid, array_merge($s, $merge));
}

// âââ Klaviaturalar âââââââââââââââââââââââââââââââââââââââââââââââ
function kb_main(bool $stars = true, bool $premium = true, bool $gift = true, bool $nomer = true): array {
    $rows = [];
    if ($stars)   $rows[] = [['text' => 'â­ Telegram Stars sotib olish']];
    if ($premium) $rows[] = [['text' => 'ð Telegram Premium sotib olish']];
    if ($gift)    $rows[] = [['text' => 'ð Telegram Gift yuborish']];
    if ($nomer)   $rows[] = [['text' => 'ð Telegram nomer olish']];
    $rows[] = [['text' => 'ð¦ Buyurtmalarim'], ['text' => 'ð¤ Profilim']];
    $rows[] = [['text' => 'â¹ï¸ Yordam']];
    return ['keyboard' => $rows, 'resize_keyboard' => true];
}

function kb_admin(): array {
    return ['keyboard' => [
        [['text' => 'ð Statistika']],
        [['text' => 'â­ Stars narxlari'], ['text' => 'ð Premium narxlari']],
        [['text' => 'ð Gift narxlari'],  ['text' => 'ð Nomer narxlari']],
        [['text' => 'ð¢ Majburiy obuna'], ['text' => 'âï¸ Bot sozlamalari']],
        [['text' => 'ð£ Xabar yuborish'], ['text' => 'ð° Hyperpin']],
        [['text' => 'ð Asosiy menyu']],
    ], 'resize_keyboard' => true];
}

function kb_inline(array $rows): array {
    return ['inline_keyboard' => $rows];
}

function kb_stars(array $pkgs): array {
    $rows = [];
    foreach ($pkgs as $i => $p)
        $rows[] = [['text' => $p['stars'] . ' Stars â ' . fmt($p['price']) . " so'm", 'callback_data' => "stars_buy:{$i}"]];
    $rows[] = [['text' => "âï¸ O'zim yozaman", 'callback_data' => 'stars_custom']];
    $rows[] = [['text' => 'ð Orqaga', 'callback_data' => 'back_main']];
    return kb_inline($rows);
}

function kb_premium(array $pkgs): array {
    $rows = [];
    foreach ($pkgs as $i => $p)
        $rows[] = [['text' => $p['label'] . ' â ' . fmt($p['price']) . " so'm", 'callback_data' => "premium_buy:{$i}"]];
    $rows[] = [['text' => 'ð Orqaga', 'callback_data' => 'back_main']];
    return kb_inline($rows);
}

function kb_gift(array $pkgs): array {
    $rows = [];
    foreach ($pkgs as $i => $p)
        $rows[] = [['text' => $p['name'] . ' â ' . fmt($p['price']) . " so'm", 'callback_data' => "gift_buy:{$i}"]];
    $rows[] = [['text' => 'ð Orqaga', 'callback_data' => 'back_main']];
    return kb_inline($rows);
}

function kb_payment(int $oid, string $card, int $amount): array {
    $card_clean = str_replace(' ', '', $card);
    return kb_inline([
        [['text' => "ð {$card}", 'callback_data' => "copy_card:{$card_clean}"]],
        [['text' => 'ð° ' . fmt($amount) . " so'm", 'callback_data' => "copy_sum:{$amount}"]],
        [['text' => "â To'lov qildim", 'callback_data' => "check_pay:{$oid}"]],
        [['text' => 'â Bekor qilish',  'callback_data' => "cancel_pay:{$oid}"]],
    ]);
}

function kb_channels(array $channels): array {
    $rows = array_map(fn($ch) => [['text' => "ð¢ {$ch['channel_title']}", 'url' => $ch['channel_link']]], $channels);
    $rows[] = [['text' => "â Obuna bo'ldim", 'callback_data' => 'check_sub']];
    return kb_inline($rows);
}

function kb_fixsim_countries(array $countries, int $page): array {
    $per = 10; $total = count($countries);
    $pages = max(1, (int)ceil($total / $per));
    $page  = max(0, min($page, $pages - 1));
    $chunk = array_slice($countries, $page * $per, $per);
    $rows  = [];
    foreach ($chunk as $c) {
        $code = $c['country_code'] ?? '';
        $name = $c['country_name'] ?? $code;
        $price = (int)($c['price_uzs'] ?? 0);
        if (!$code || $price <= 0) continue;
        $rows[] = [['text' => "{$name} â " . fmt($price) . " so'm", 'callback_data' => "fs_buy:{$code}:" . substr($name, 0, 30)]];
    }
    $nav = [];
    if ($page > 0) $nav[] = ['text' => 'â¬ï¸ Oldingi', 'callback_data' => 'fs_page:' . ($page - 1)];
    $nav[] = ['text' => ($page + 1) . "/{$pages}", 'callback_data' => 'none'];
    if (($page + 1) * $per < $total) $nav[] = ['text' => 'Keyingi â¡ï¸', 'callback_data' => 'fs_page:' . ($page + 1)];
    if ($nav) $rows[] = $nav;
    $rows[] = [['text' => 'ð TOP 10', 'callback_data' => 'fs_top10'], ['text' => 'ð° Arzonlar', 'callback_data' => 'fs_cheap']];
    $rows[] = [['text' => 'ð Orqaga', 'callback_data' => 'back_main']];
    return kb_inline($rows);
}

function kb_fixsim_list(array $countries, string $back = 'fs_start'): array {
    $rows = [];
    foreach ($countries as $c) {
        $code = $c['country_code'] ?? ''; $name = $c['country_name'] ?? $code; $price = (int)($c['price_uzs'] ?? 0);
        if (!$code || $price <= 0) continue;
        $rows[] = [['text' => "{$name} â " . fmt($price) . " so'm", 'callback_data' => "fs_buy:{$code}:" . substr($name, 0, 30)]];
    }
    $rows[] = [['text' => 'ð Orqaga', 'callback_data' => $back]];
    return kb_inline($rows);
}

// âââ Admin klaviaturalar âââââââââââââââââââââââââââââââââââââââââ
function kb_admin_pkg(array $pkgs, string $prefix, string $name_key, string $val_key, string $add_cb, string $del_cb): array {
    $rows = [];
    foreach ($pkgs as $i => $p)
        $rows[] = [['text' => $p[$name_key] . ' â ' . fmt($p[$val_key]) . " so'm", 'callback_data' => "edit_{$prefix}:{$i}"]];
    $rows[] = [['text' => 'â Yangi paket', 'callback_data' => $add_cb], ['text' => "ð O'chirish", 'callback_data' => $del_cb]];
    $rows[] = [['text' => 'ð Admin panel', 'callback_data' => 'admin_back']];
    return kb_inline($rows);
}

function kb_del_pkg(array $pkgs, string $prefix, string $label_key): array {
    $rows = array_map(fn($i, $p) => [['text' => "â {$p[$label_key]}", 'callback_data' => "del_{$prefix}:{$i}"]], array_keys($pkgs), $pkgs);
    $rows[] = [['text' => 'ð Orqaga', 'callback_data' => 'admin_back']];
    return kb_inline($rows);
}

function kb_admin_channels(array $channels): array {
    $rows = array_map(fn($ch) => [['text' => "â {$ch['channel_title']}", 'callback_data' => "del_channel:{$ch['id']}"]], $channels);
    $rows[] = [['text' => "â Kanal qo'shish", 'callback_data' => 'add_channel']];
    $rows[] = [['text' => 'ð Admin panel', 'callback_data' => 'admin_back']];
    return kb_inline($rows);
}

function kb_admin_settings(array $s, string $welcome): array {
    $on = fn($v) => $v === '1' ? 'â Yoqiq' : "â O'chiq";
    return kb_inline([
        [['text' => 'â­ Stars: ' . $on($s['stars_enabled']),   'callback_data' => 'toggle_stars']],
        [['text' => 'ð Premium: ' . $on($s['premium_enabled']), 'callback_data' => 'toggle_premium']],
        [['text' => 'ð Gift: ' . $on($s['gift_enabled']),     'callback_data' => 'toggle_gift']],
        [['text' => 'ð Nomer: ' . $on($s['nomer_enabled']),   'callback_data' => 'toggle_nomer']],
        [['text' => "âï¸ Xush kelish xabarini o'zgartirish",   'callback_data' => 'edit_welcome']],
    ]);
}

function kb_nomer_overrides(array $overrides, array $countries): array {
    $name_map = [];
    foreach ($countries as $c) $name_map[(string)($c['country_code'] ?? '')] = $c['country_name'] ?? '';
    $rows = [];
    foreach ($overrides as $code => $price) {
        $name = $name_map[$code] ?? $code;
        $rows[] = [
            ['text' => "âï¸ {$name} â " . fmt($price) . " so'm", 'callback_data' => "nomer_edit:{$code}"],
            ['text' => 'ð', 'callback_data' => "nomer_del:{$code}"],
        ];
    }
    $rows[] = [['text' => 'â Yangi narx belgilash', 'callback_data' => 'nomer_add']];
    $rows[] = [['text' => 'ð Admin panel', 'callback_data' => 'admin_back']];
    return kb_inline($rows);
}

// âââ Obuna tekshiruv âââââââââââââââââââââââââââââââââââââââââââââ
function check_subscriptions(int $uid): array {
    $not_sub = [];
    foreach (get_forced_channels() as $ch) {
        $m = get_chat_member($ch['channel_id'], $uid);
        if (!$m || in_array($m['status'], ['left', 'kicked', 'banned'])) $not_sub[] = $ch;
    }
    return $not_sub;
}

// âââ To'lov yaratish âââââââââââââââââââââââââââââââââââââââââââââ
function create_payment(int $uid, float $price, string $type, int $amount, int $msg_id, int $chat_id, array $extra = []): ?int {
    $wait = send_message($chat_id, 'â³ To\'lov yaratilmoqda...');
    $res = humo_create($uid, $price);
    if (!$res || !($res['ok'] ?? false)) {
        $err = $res['error'] ?? 'Server xatosi';
        edit_message_text($chat_id, $wait['result']['message_id'], "â <b>Xato:</b> {$err}\n\nQaytadan /start bosing.");
        clear_session($uid);
        return null;
    }
    $d    = $res['data'];
    $oid  = (int)$d['order_id'];
    $fp   = (int)$d['amount'];
    $card = $d['card_number'];
    $recipient  = $extra['recipient'] ?? '';
    $extra_data = json_encode(array_diff_key($extra, ['recipient' => '']));
    create_order($uid, $oid, $type, $amount, $fp, $recipient, $extra_data);
    update_session($uid, array_merge(['order_id' => $oid, 'recipient' => $recipient], $extra));

    $plus = (($d['extra_sum'] ?? 0) > 0) ? "\n\nâ ï¸ Farqlash uchun <b>+{$d['extra_sum']} so'm</b> qo'shildi." : '';
    edit_message_text($chat_id, $wait['result']['message_id'],
        "ð° <b>To'lov</b>\n\n"
        . "ð¦ Karta: <code>{$card}</code>\n"
        . "ð° Summa: <b>" . fmt($fp) . " so'm</b>\n\n"
        . "ð 1ï¸â£ Karta raqamini nusxa oling\n"
        . "2ï¸â£ Aniq summani o'tkazing\n"
        . "3ï¸â£ Â«â To'lov qildimÂ» tugmasini bosing\n\n"
        . "â³ Muddat: <b>10 daqiqa</b>{$plus}",
        ['reply_markup' => kb_payment($oid, $card, $fp)]
    );
    return $oid;
}

// âââ Webhook handler âââââââââââââââââââââââââââââââââââââââââââââ
function handle_update(array $upd): void {
    if (isset($upd['message'])) {
        handle_message($upd['message']);
    } elseif (isset($upd['callback_query'])) {
        handle_callback($upd['callback_query']);
    }
}

function handle_message(array $msg): void {
    $uid      = $msg['from']['id'];
    $chat_id  = $msg['chat']['id'];
    $text     = $msg['text'] ?? '';
    $username = $msg['from']['username'] ?? '';
    $fullname = ($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? '');
    $fullname = trim($fullname);
    $mid      = $msg['message_id'];

    register_user($uid, $username, $fullname);

    $sess  = get_session($uid);
    $state = $sess['state'] ?? '';

    // /cancel
    if ($text === '/cancel' || mb_strtolower($text) === 'bekor') {
        clear_session($uid);
        send_message($chat_id, "â Bekor qilindi. /start bosing.");
        return;
    }

    // FSM holatlari
    if ($state) {
        handle_state($uid, $chat_id, $text, $state, $sess, $msg);
        return;
    }

    // /start
    if ($text === '/start') {
        clear_session($uid);
        if ($uid === ADMIN_ID) {
            send_message($chat_id, "ð Xush kelibsiz, Admin!\n\nAdmin panelga: /admin", ['reply_markup' => kb_main()]);
            return;
        }
        $not_sub = check_subscriptions($uid);
        if ($not_sub) {
            send_message($chat_id, "â ï¸ <b>Botdan foydalanish uchun kanallarga obuna bo'ling:</b>", ['reply_markup' => kb_channels($not_sub)]);
            return;
        }
        $welcome = get_setting('welcome_text', 'Xush kelibsiz!');
        $s = get_enabled();
        send_message($chat_id, "ð {$welcome}", ['reply_markup' => kb_main($s[0], $s[1], $s[2], $s[3])]);
        return;
    }

    // /admin
    if ($text === '/admin' && $uid === ADMIN_ID) {
        send_message($chat_id, 'ð <b>Admin Panel</b>', ['reply_markup' => kb_admin()]);
        return;
    }

    // Admin tugmalari
    if ($uid === ADMIN_ID) {
        handle_admin_text($uid, $chat_id, $text, $sess);
        return;
    }

    // Foydalanuvchi tugmalari
    handle_user_text($uid, $chat_id, $text, $sess, $fullname, $username);
}

function get_enabled(): array {
    return [
        get_setting('stars_enabled',   '1') === '1',
        get_setting('premium_enabled', '1') === '1',
        get_setting('gift_enabled',    '1') === '1',
        get_setting('nomer_enabled',   '1') === '1',
    ];
}

function handle_user_text(int $uid, int $chat_id, string $text, array $sess, string $fullname, string $username): void {
    $not_sub = check_subscriptions($uid);
    if ($not_sub) {
        send_message($chat_id, "â ï¸ Botdan foydalanish uchun kanallarga obuna bo'ling:", ['reply_markup' => kb_channels($not_sub)]);
        return;
    }
    switch ($text) {
        case 'â­ Telegram Stars sotib olish':
            if (get_setting('stars_enabled', '1') !== '1') { send_message($chat_id, 'â­ Stars xizmati vaqtincha to\'xtatilgan.'); return; }
            update_session($uid, ['state' => 'stars_recipient']);
            send_message($chat_id, "â­ <b>Telegram Stars</b>\n\nð¤ Qabul qiluvchining <b>\@username</b> ini kiriting:\n\n<i>Masalan: \@durav</i>\n\nBekor qilish: /cancel");
            break;
        case 'ð Telegram Premium sotib olish':
            if (get_setting('premium_enabled', '1') !== '1') { send_message($chat_id, 'ð Premium xizmati vaqtincha to\'xtatilgan.'); return; }
            update_session($uid, ['state' => 'premium_recipient']);
            send_message($chat_id, "ð <b>Telegram Premium</b>\n\nð¤ Premium kimga faollashtirilsin?\n<b>\@username</b> sini kiriting:\n\nBekor qilish: /cancel");
            break;
        case 'ð Telegram Gift yuborish':
            if (get_setting('gift_enabled', '1') !== '1') { send_message($chat_id, 'ð Gift xizmati vaqtincha to\'xtatilgan.'); return; }
            update_session($uid, ['state' => 'gift_recipient']);
            send_message($chat_id, "ð <b>Telegram Gift</b>\n\nð¤ Qabul qiluvchining <b>\@username</b> sini kiriting:\n\nBekor qilish: /cancel");
            break;
        case 'ð Telegram nomer olish':
            if (get_setting('nomer_enabled', '1') !== '1') { send_message($chat_id, 'ð Nomer xizmati vaqtincha to\'xtatilgan.'); return; }
            send_message($chat_id,
                "<b>ð¶ Tayyor Telegram akkauntlar</b>\n\n<b>ð Ishlash tartibi:</b>\n\n<blockquote>"
                . "1ï¸â£ Bot sizga raqam beradi.\n"
                . "2ï¸â£ Shu raqam bilan Telegramga kiring â rasmiy ko'k ilovadan foydalanmang!\n"
                . "3ï¸â£ Telegram kod so'raganda <b>SMS kodni olish</b> tugmasini bosing.\n"
                . "4ï¸â£ 1â3 daqiqada kirish kodi va 2 bosqichli parol beriladi.\n\n"
                . "â ï¸ Raqamni olganingizdan so'ng uni bekor qilish imkonsiz!\n"
                . "ð« Spam yoki noqonuniy maqsadlarda ishlatmang!"
                . "</blockquote>\n\nâ O'qib chiqdingizmi? <b>Tushundim</b> tugmasini bosing.",
                ['reply_markup' => kb_inline([[['text' => 'â Tushundim', 'callback_data' => 'fs_start']], [['text' => 'ð Orqaga', 'callback_data' => 'back_main']]])]
            );
            break;
        case 'ð¦ Buyurtmalarim':
            $orders = get_user_orders($uid);
            if (!$orders) { send_message($chat_id, 'ð¦ Sizda hali buyurtma yo\'q.'); return; }
            $labels = ['stars' => 'â­ Stars', 'premium' => 'ð Premium', 'gift' => 'ð Gift', 'nomer' => 'ð Nomer'];
            $lines  = ["ð¦ <b>So'nggi buyurtmalar:</b>\n"];
            foreach ($orders as $o) {
                $e = order_status_emoji($o['status']);
                $t = $labels[$o['type']] ?? $o['type'];
                $a = match($o['type']) {
                    'stars'   => $o['amount'] . ' Stars',
                    'premium' => $o['amount'] . ' oy',
                    'gift'    => $o['amount'] . ' stars',
                    default   => (string)$o['amount'],
                };
                $d = substr($o['created_at'], 0, 10);
                $lines[] = "{$e} #{$o['order_id']} | {$t} | {$a}\n   ð° " . fmt($o['price']) . " so'm | {$d}\n";
            }
            send_message($chat_id, implode("\n", $lines));
            break;
        case 'ð¤ Profilim':
            $user   = get_user($uid);
            $joined = $user ? substr($user['joined_at'], 0, 10) : 'â';
            send_message($chat_id,
                "ð¤ <b>Profilingiz</b>\n\nð ID: <code>{$uid}</code>\nð¤ Ism: {$fullname}\nð Ro'yxatdan o'tgan: {$joined}\n\nð° Balans: <b>" . fmt($user['balance'] ?? 0) . " so'm</b>"
            );
            break;
        case 'â¹ï¸ Yordam':
            send_message($chat_id,
                "â¹ï¸ <b>Yordam</b>\n\nBu bot orqali Telegram xizmatlarini qulay sotib olasiz:\n\n"
                . "â­ <b>Stars</b> â istalgan akkauntga yuboriladi\n"
                . "ð <b>Premium</b> â username orqali avtomatik faollashadi\n"
                . "ð <b>Gift</b> â do'stingizga sovg'a yuboring\n"
                . "ð <b>Nomer</b> â oldindan ro'yxatdan o'tkazilgan akkauntlar\n\n"
                . "ð³ <b>To'lov:</b> HUMO karta orqali avtomatik\n\nâ Muammo bo'lsa admin bilan bog'laning."
            );
            break;
        default:
            $s = get_enabled();
            send_message($chat_id, get_setting('welcome_text', 'Xush kelibsiz!'), ['reply_markup' => kb_main(...$s)]);
    }
}

function handle_state(int $uid, int $chat_id, string $text, string $state, array $sess, array $msg): void {
    switch ($state) {
        // ââ Stars ââ
        case 'stars_recipient':
            if (!str_starts_with($text, '@')) {
                send_message($chat_id, "â ï¸ Faqat <b>\@username</b> ko'rinishida kiriting.\nBekor qilish: /cancel");
                return;
            }
            update_session($uid, ['state' => 'stars_choose', 'recipient' => $text]);
            send_message($chat_id, "â Qabul qiluvchi: <b>{$text}</b>\n\nâ­ <b>Paket tanlang:</b>", ['reply_markup' => kb_stars(get_packages('stars'))]);
            break;

        case 'stars_custom':
            $n = (int)$text;
            if (!ctype_digit(str_replace(' ', '', $text)) || $n < 50 || $n > 1000000) {
                send_message($chat_id, "â ï¸ 50 dan 1 000 000 gacha raqam kiriting.");
                return;
            }
            $pkgs = get_packages('stars');
            $pps  = $pkgs ? round($pkgs[0]['price'] / $pkgs[0]['stars']) : 280;
            $price = $n * $pps;
            $recipient = $sess['recipient'] ?? (string)$uid;
            update_session($uid, ['state' => 'stars_pay', 'stars' => $n]);
            create_payment($uid, $price, 'stars', $n, $msg['message_id'], $chat_id, ['recipient' => $recipient, 'stars' => $n]);
            break;

        // ââ Premium ââ
        case 'premium_recipient':
            if (!str_starts_with($text, '@')) {
                send_message($chat_id, "â ï¸ Noto'g'ri format!\n\@username ko'rinishida kiriting.");
                return;
            }
            update_session($uid, ['state' => 'premium_choose', 'recipient' => $text]);
            send_message($chat_id, "â Qabul qiluvchi: <b>{$text}</b>\n\nð <b>Muddatni tanlang:</b>", ['reply_markup' => kb_premium(get_packages('premium'))]);
            break;

        // ââ Gift ââ
        case 'gift_recipient':
            if (!str_starts_with($text, '@')) {
                send_message($chat_id, "â ï¸ Noto'g'ri format!\n\@username ko'rinishida kiriting.");
                return;
            }
            update_session($uid, ['state' => 'gift_choose', 'recipient' => $text]);
            send_message($chat_id, "â Qabul qiluvchi: <b>{$text}</b>\n\nð <b>Gift tanlang:</b>", ['reply_markup' => kb_gift(get_packages('gift'))]);
            break;

        // ââ Admin: Broadcast ââ
        case 'admin_broadcast':
            if ($uid !== ADMIN_ID) { clear_session($uid); return; }
            clear_session($uid);
            $ids = get_all_user_ids();
            $sent = $fail = 0;
            foreach ($ids as $id) {
                $r = send_message((int)$id, $text);
                if ($r && $r['ok']) $sent++; else $fail++;
                usleep(50000);
            }
            send_message($chat_id, "â <b>Broadcast yakunlandi!</b>\n\nð¤ Yuborildi: <b>{$sent}</b>\nâ Muvaffaqiyatsiz: <b>{$fail}</b>\nð Jami: <b>" . count($ids) . "</b>");
            break;

        // ââ Admin: Welcome ââ
        case 'admin_edit_welcome':
            if ($uid !== ADMIN_ID) { clear_session($uid); return; }
            set_setting('welcome_text', trim($text));
            clear_session($uid);
            send_message($chat_id, "â Xush kelish matni yangilandi!");
            break;

        // ââ Admin: Stars paket ââ
        case 'admin_add_stars_value':
            if (!ctype_digit($text)) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            update_session($uid, ['state' => 'admin_add_stars_price', 'new_value' => (int)$text]);
            send_message($chat_id, "ð° {$text} Stars uchun narx (so'm)?");
            break;
        case 'admin_add_stars_price':
            if (!ctype_digit(str_replace(' ', '', $text))) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            $pkgs = get_packages('stars');
            $pkgs[] = ['stars' => $sess['new_value'], 'price' => (int)str_replace(' ', '', $text)];
            usort($pkgs, fn($a, $b) => $a['stars'] <=> $b['stars']);
            set_packages('stars', $pkgs);
            clear_session($uid);
            send_message($chat_id, "â Yangi Stars paketi qo'shildi!", ['reply_markup' => kb_admin_pkg($pkgs, 'stars', 'stars', 'price', 'add_stars_pkg', 'del_stars_menu')]);
            break;

        // ââ Admin: Premium paket ââ
        case 'admin_add_premium_value':
            if (!ctype_digit($text)) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            update_session($uid, ['state' => 'admin_add_premium_label', 'new_value' => (int)$text]);
            send_message($chat_id, "ð {$text} oy uchun yorliq kiriting (masalan: '2 oy'):");
            break;
        case 'admin_add_premium_label':
            update_session($uid, ['state' => 'admin_add_premium_price', 'new_label' => trim($text)]);
            send_message($chat_id, "ð° Narxi (so'm)?");
            break;
        case 'admin_add_premium_price':
            if (!ctype_digit(str_replace(' ', '', $text))) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            $pkgs = get_packages('premium');
            $pkgs[] = ['months' => $sess['new_value'], 'label' => $sess['new_label'], 'price' => (int)str_replace(' ', '', $text)];
            usort($pkgs, fn($a, $b) => $a['months'] <=> $b['months']);
            set_packages('premium', $pkgs);
            clear_session($uid);
            send_message($chat_id, "â Yangi Premium paketi qo'shildi!", ['reply_markup' => kb_admin_pkg($pkgs, 'premium', 'label', 'price', 'add_premium_pkg', 'del_premium_menu')]);
            break;

        // ââ Admin: Gift paket ââ
        case 'admin_add_gift_id':
            update_session($uid, ['state' => 'admin_add_gift_name', 'new_gift_id' => trim($text)]);
            send_message($chat_id, "ð Gift nomini kiriting:");
            break;
        case 'admin_add_gift_name':
            update_session($uid, ['state' => 'admin_add_gift_stars', 'new_gift_name' => trim($text)]);
            send_message($chat_id, "â­ Gift stars_cost (1â100):");
            break;
        case 'admin_add_gift_stars':
            $n = (int)$text;
            if (!ctype_digit($text) || $n < 1 || $n > 100) { send_message($chat_id, 'â ï¸ 1 dan 100 gacha raqam kiriting.'); return; }
            update_session($uid, ['state' => 'admin_add_gift_price', 'new_stars_cost' => $n]);
            send_message($chat_id, "ð° Narxi (so'm)?");
            break;
        case 'admin_add_gift_price':
            if (!ctype_digit(str_replace(' ', '', $text))) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            $pkgs = get_packages('gift');
            $pkgs[] = ['gift_id' => $sess['new_gift_id'], 'name' => $sess['new_gift_name'], 'stars_cost' => $sess['new_stars_cost'], 'price' => (int)str_replace(' ', '', $text)];
            set_packages('gift', $pkgs);
            clear_session($uid);
            send_message($chat_id, "â Yangi Gift qo'shildi!", ['reply_markup' => kb_admin_pkg($pkgs, 'gift', 'name', 'price', 'add_gift_pkg', 'del_gift_menu')]);
            break;

        // ââ Admin: narx tahrirlash ââ
        case 'admin_edit_price':
            if (!ctype_digit(str_replace(' ', '', $text))) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            $type = $sess['pkg_type'] ?? 'stars';
            $idx  = (int)($sess['idx'] ?? 0);
            $pkgs = get_packages($type);
            $pkgs[$idx]['price'] = (int)str_replace(' ', '', $text);
            set_packages($type, $pkgs);
            clear_session($uid);
            $kbs = [
                'stars'   => fn($p) => kb_admin_pkg($p, 'stars',   'stars', 'price', 'add_stars_pkg',   'del_stars_menu'),
                'premium' => fn($p) => kb_admin_pkg($p, 'premium', 'label', 'price', 'add_premium_pkg', 'del_premium_menu'),
                'gift'    => fn($p) => kb_admin_pkg($p, 'gift',    'name',  'price', 'add_gift_pkg',    'del_gift_menu'),
            ];
            send_message($chat_id, "â Narx yangilandi!", ['reply_markup' => ($kbs[$type] ?? fn($p) => [])($pkgs)]);
            break;

        // ââ Admin: kanal qo'shish ââ
        case 'admin_add_ch_id':
            update_session($uid, ['state' => 'admin_add_ch_title', 'new_ch_id' => trim($text)]);
            send_message($chat_id, "ð Kanal nomini kiriting:");
            break;
        case 'admin_add_ch_title':
            update_session($uid, ['state' => 'admin_add_ch_link', 'new_ch_title' => trim($text)]);
            send_message($chat_id, "ð Kanal linkini kiriting (masalan: https://t.me/kanal):");
            break;
        case 'admin_add_ch_link':
            add_forced_channel($sess['new_ch_id'], $sess['new_ch_title'], trim($text));
            clear_session($uid);
            $channels = get_forced_channels();
            send_message($chat_id, "â Kanal qo'shildi!", ['reply_markup' => kb_admin_channels($channels)]);
            break;

        // ââ Admin: Nomer narx ââ
        case 'admin_nomer_set_price':
            if (!ctype_digit(str_replace(' ', '', $text))) { send_message($chat_id, 'Faqat raqam kiriting!'); return; }
            $code = $sess['nomer_code'] ?? '';
            if ($code) set_nomer_override($code, (int)str_replace(' ', '', $text));
            clear_session($uid);
            send_message($chat_id, "â Narx yangilandi!");
            break;

        default:
            clear_session($uid);
    }
}

// âââ Admin matn tugmalari âââââââââââââââââââââââââââââââââââââââââ
function handle_admin_text(int $uid, int $chat_id, string $text, array $sess): void {
    switch ($text) {
        case 'ð Asosiy menyu':
            clear_session($uid);
            $s = get_enabled();
            send_message($chat_id, "Asosiy menyu:", ['reply_markup' => kb_main(...$s)]);
            break;
        case 'ð Statistika':
            $s = get_stats();
            send_message($chat_id,
                "ð <b>Statistika</b>\n\n"
                . "ð¥ Jami foydalanuvchilar: <b>{$s['users']}</b>\n"
                . "ð Bugun qo'shilgan: <b>{$s['new_today']}</b>\n\n"
                . "â Muvaffaqiyatli buyurtmalar: <b>{$s['paid']}</b>\n"
                . "â³ Kutilayotgan: <b>{$s['pending']}</b>\n"
                . "ð° Jami daromad: <b>" . fmt($s['revenue']) . " so'm</b>"
            );
            break;
        case 'â­ Stars narxlari':
            $pkgs = get_packages('stars');
            send_message($chat_id, "â­ <b>Stars paketlari:</b>", ['reply_markup' => kb_admin_pkg($pkgs, 'stars', 'stars', 'price', 'add_stars_pkg', 'del_stars_menu')]);
            break;
        case 'ð Premium narxlari':
            $pkgs = get_packages('premium');
            send_message($chat_id, "ð <b>Premium paketlari:</b>", ['reply_markup' => kb_admin_pkg($pkgs, 'premium', 'label', 'price', 'add_premium_pkg', 'del_premium_menu')]);
            break;
        case 'ð Gift narxlari':
            $pkgs = get_packages('gift');
            send_message($chat_id, "ð <b>Gift paketlari:</b>", ['reply_markup' => kb_admin_pkg($pkgs, 'gift', 'name', 'price', 'add_gift_pkg', 'del_gift_menu')]);
            break;
        case 'ð Nomer narxlari':
            $ov = get_nomer_overrides();
            $countries = fixsim_get_countries();
            send_message($chat_id,
                "ð <b>Nomer narxlari</b>\n\nFixSIM API narxlari asosida ko'rsatiladi.\nâï¸ O'zgartirilgan davlatlar: <b>" . count($ov) . "</b> ta",
                ['reply_markup' => kb_nomer_overrides($ov, $countries)]
            );
            break;
        case 'ð¢ Majburiy obuna':
            $channels = get_forced_channels();
            send_message($chat_id, "ð¢ <b>Majburiy obuna kanallari:</b>", ['reply_markup' => kb_admin_channels($channels)]);
            break;
        case 'âï¸ Bot sozlamalari':
            $s = ['stars_enabled' => get_setting('stars_enabled','1'), 'premium_enabled' => get_setting('premium_enabled','1'), 'gift_enabled' => get_setting('gift_enabled','1'), 'nomer_enabled' => get_setting('nomer_enabled','1')];
            $welcome = get_setting('welcome_text', 'Xush kelibsiz!');
            $on = fn($v) => $v === '1' ? 'â Yoqiq' : "â O'chiq";
            send_message($chat_id,
                "âï¸ <b>Bot sozlamalari</b>\n\nâ­ Stars: {$on($s['stars_enabled'])}\nð Premium: {$on($s['premium_enabled'])}\nð Gift: {$on($s['gift_enabled'])}\nð Nomer: {$on($s['nomer_enabled'])}\n\nð Xush kelish matni:\n<i>{$welcome}</i>",
                ['reply_markup' => kb_admin_settings($s, $welcome)]
            );
            break;
        case 'ð£ Xabar yuborish':
            update_session($uid, ['state' => 'admin_broadcast']);
            send_message($chat_id, "ð£ <b>Broadcast</b>\n\nBarcha foydalanuvchilarga yuboriladigan xabarni kiriting.\nHTML formatlash qo'llab-quvvatlanadi.\n\nBekor qilish: /cancel");
            break;
        case 'ð° Hyperpin':
            $bal  = hyperpin_balance();
            $dep  = hyperpin_deposit();
            $text = '';
            if ($bal['success'] ?? false) {
                $text .= "ð° <b>Balans:</b> <code>{$bal['balance']} TON</code>\nð¤ <b>Foydalanuvchi:</b> @{$bal['username']}\n";
            } else {
                $text .= "â Balans xatosi: " . ($bal['error'] ?? 'Noma\'lum') . "\n";
            }
            if ($dep['address'] ?? '') $text .= "\nð¥ <b>Depozit manzil:</b> <code>{$dep['address']}</code>";
            send_message($chat_id, $text ?: "Ma'lumot yo'q.");
            break;
        default:
            handle_user_text($uid, $chat_id, $text, $sess, '', '');
    }
}

// âââ Callback handler âââââââââââââââââââââââââââââââââââââââââââââ
function handle_callback(array $cb): void {
    $uid     = $cb['from']['id'];
    $chat_id = $cb['message']['chat']['id'];
    $mid     = $cb['message']['message_id'];
    $cbid    = $cb['id'];
    $data    = $cb['data'];
    $from    = $cb['from'];
    $sess    = get_session($uid);

    // Obuna tekshiruv
    if ($data === 'check_sub') {
        $not_sub = check_subscriptions($uid);
        if ($not_sub) { answer_callback($cbid, "â ï¸ Hali barcha kanallarga obuna bo'lmadingiz!", true); return; }
        delete_message($chat_id, $mid);
        $s = get_enabled();
        $welcome = get_setting('welcome_text', 'Xush kelibsiz!');
        send_message($chat_id, "â Rahmat!\n\nð {$welcome}", ['reply_markup' => kb_main(...$s)]);
        answer_callback($cbid);
        return;
    }

    if ($data === 'back_main') {
        clear_session($uid);
        try { delete_message($chat_id, $mid); } catch (Exception $e) {}
        answer_callback($cbid);
        return;
    }

    if ($data === 'none') { answer_callback($cbid); return; }

    // Stars
    if (str_starts_with($data, 'stars_buy:')) {
        $idx  = (int)explode(':', $data)[1];
        $pkgs = get_packages('stars');
        if (!isset($pkgs[$idx])) { answer_callback($cbid, 'Paket topilmadi!', true); return; }
        $p         = $pkgs[$idx];
        $recipient = $sess['recipient'] ?? (string)$uid;
        update_session($uid, ['state' => 'stars_pay', 'stars' => $p['stars']]);
        create_payment($uid, $p['price'], 'stars', $p['stars'], $mid, $chat_id, ['recipient' => $recipient, 'stars' => $p['stars']]);
        answer_callback($cbid);
        return;
    }

    if ($data === 'stars_custom') {
        update_session($uid, ['state' => 'stars_custom']);
        send_message($chat_id, "âï¸ Nechta Stars xohlaysiz?\n\n<i>Minimal: 50 | Maksimal: 1 000 000</i>\n\nBekor qilish: /cancel");
        answer_callback($cbid);
        return;
    }

    // Premium
    if (str_starts_with($data, 'premium_buy:')) {
        $idx  = (int)explode(':', $data)[1];
        $pkgs = get_packages('premium');
        if (!isset($pkgs[$idx])) { answer_callback($cbid, 'Paket topilmadi!', true); return; }
        $p         = $pkgs[$idx];
        $recipient = $sess['recipient'] ?? '';
        if (!$recipient) { answer_callback($cbid, 'â Avval @username kiriting!', true); return; }
        update_session($uid, ['state' => 'premium_pay', 'months' => $p['months']]);
        create_payment($uid, $p['price'], 'premium', $p['months'], $mid, $chat_id, ['recipient' => $recipient, 'months' => $p['months']]);
        answer_callback($cbid);
        return;
    }

    // Gift
    if (str_starts_with($data, 'gift_buy:')) {
        $idx  = (int)explode(':', $data)[1];
        $pkgs = get_packages('gift');
        if (!isset($pkgs[$idx])) { answer_callback($cbid, 'Gift topilmadi!', true); return; }
        $p         = $pkgs[$idx];
        $recipient = $sess['recipient'] ?? (string)$uid;
        update_session($uid, ['state' => 'gift_pay', 'gift_idx' => $idx]);
        create_payment($uid, $p['price'], 'gift', $p['stars_cost'], $mid, $chat_id, ['recipient' => $recipient, 'gift_idx' => $idx, 'gift_name' => $p['name']]);
        answer_callback($cbid);
        return;
    }

    // FixSIM
    if ($data === 'fs_start') {
        edit_message_text($chat_id, $mid, "<b>â³ Davlatlar yuklanmoqda...</b>");
        $countries = get_countries_with_overrides();
        if (!$countries) {
            edit_message_text($chat_id, $mid, "<b>â Davlatlar yuklanmadi.</b>",
                ['reply_markup' => kb_inline([[['text' => 'ð Qayta urinish', 'callback_data' => 'fs_start']], [['text' => 'ð Orqaga', 'callback_data' => 'back_main']]])]);
            answer_callback($cbid); return;
        }
        edit_message_text($chat_id, $mid, "<b>ð Mavjud davlatlar ro'yxati:</b>\n\nKerakli davlatni tanlang:", ['reply_markup' => kb_fixsim_countries($countries, 0)]);
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'fs_page:')) {
        $page      = (int)explode(':', $data)[1];
        $countries = get_countries_with_overrides();
        edit_message_text($chat_id, $mid, "<b>ð Mavjud davlatlar ro'yxati:</b>\n\nKerakli davlatni tanlang:", ['reply_markup' => kb_fixsim_countries($countries, $page)]);
        answer_callback($cbid);
        return;
    }

    if ($data === 'fs_top10') {
        $countries = get_countries_with_overrides();
        edit_message_text($chat_id, $mid, "<b>ð TOP 10 mashhur davlatlar:</b>", ['reply_markup' => kb_fixsim_list(array_slice($countries, 0, 10), 'fs_start')]);
        answer_callback($cbid);
        return;
    }

    if ($data === 'fs_cheap') {
        $countries = get_countries_with_overrides();
        usort($countries, fn($a, $b) => (float)($a['price_uzs'] ?? 0) <=> (float)($b['price_uzs'] ?? 0));
        edit_message_text($chat_id, $mid, "<b>ð° Eng arzon 10 ta davlat:</b>", ['reply_markup' => kb_fixsim_list(array_slice($countries, 0, 10), 'fs_start')]);
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'fs_buy:')) {
        $parts  = explode(':', $data, 3);
        $c_code = $parts[1] ?? '';
        $c_name = $parts[2] ?? $c_code;
        $price  = 0;
        foreach (get_countries_with_overrides() as $c) {
            if ((string)($c['country_code'] ?? '') === $c_code) { $price = (int)($c['price_uzs'] ?? 0); break; }
        }
        if ($price <= 0) { answer_callback($cbid, "â Narx aniqlanmadi.", true); return; }
        edit_message_text($chat_id, $mid,
            "<b>ð Buyurtmangizni tasdiqlang</b>\n\n<blockquote>ð Davlat: <b>{$c_name}</b>\nðµ Narxi: <code>" . fmt($price) . "</code> so'm</blockquote>\n\n"
            . "<blockquote>â ï¸ Raqamni Telegramning rasmiy ilovasidan faollashtirmang!\nâ Sinovdan o'tgan, 2 bosqichli parolli raqam beriladi.</blockquote>\n\n"
            . "Tasdiqlash uchun <b>â Sotib olish</b> tugmasini bosing.",
            ['reply_markup' => kb_inline([[['text' => 'â Sotib olish', 'callback_data' => "fs_confirm:{$c_code}:" . substr($c_name, 0, 30)]], [['text' => 'â Bekor qilish', 'callback_data' => 'fs_start']]])]
        );
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'fs_confirm:')) {
        $parts  = explode(':', $data, 3);
        $c_code = $parts[1] ?? '';
        $c_name = $parts[2] ?? $c_code;
        $price  = 0;
        foreach (get_countries_with_overrides() as $c) {
            if ((string)($c['country_code'] ?? '') === $c_code) { $price = (int)($c['price_uzs'] ?? 0); break; }
        }
        if ($price <= 0) { answer_callback($cbid, "â Narx aniqlanmadi", true); return; }
        update_session($uid, ['state' => 'nomer_pay']);
        create_payment($uid, $price, 'nomer', $price, $mid, $chat_id, ['country_code' => $c_code, 'country_name' => $c_name]);
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'fs_sms:')) {
        $hash = explode(':', $data, 2)[1] ?? '';
        if (!$hash) { answer_callback($cbid, 'â Xato ma\'lumot', true); return; }
        if (!fixsim_flood_check($uid, $hash)) { answer_callback($cbid, 'â³ Iltimos, 30 soniya kuting', true); return; }
        $res = fixsim_get_sms($hash);
        if ($res['sms']) {
            $t = "<b>ð SMS Kod keldi!</b>\n\n<blockquote>ð¢ Kirish kodi: <code>{$res['sms']}</code>";
            if ($res['password']) $t .= "\nð 2 Bosqichli parol: <code>{$res['password']}</code>";
            $t .= "</blockquote>";
            send_message($chat_id, $t);
        } else {
            answer_callback($cbid, 'â³ SMS hali kelmadi. 30 soniyadan so\'ng qayta bosing.', true);
            edit_message_text($chat_id, $mid,
                "<b>â³ SMS hali kelmadi.</b>\n\n<blockquote>Biroz kuting va qayta tekshiring. Odatda 1â3 daqiqa ichida keladi.</blockquote>",
                ['reply_markup' => kb_inline([[['text' => 'ð Qayta tekshirish', 'callback_data' => "fs_sms:{$hash}"]]])]
            );
        }
        answer_callback($cbid);
        return;
    }

    // ââ To'lovni tekshirish ââ
    if (str_starts_with($data, 'check_pay:')) {
        $oid = (int)explode(':', $data)[1];
        $d   = humo_check($oid);
        if (!$d) { answer_callback($cbid, "â ï¸ Server javob bermadi. Qaytadan bosing.", true); return; }
        $status = $d['status'] ?? '';
        $secs   = (int)($d['seconds_left'] ?? 0);

        if ($status === 'paid') {
            if (!try_claim_order($oid)) { answer_callback($cbid, "â ï¸ Bu buyurtma allaqachon qayta ishlanmoqda.", true); return; }
            $order = get_order($oid);
            if (!$order) { answer_callback($cbid, 'Buyurtma topilmadi!', true); return; }
            edit_message_text($chat_id, $mid, 'â³ To\'lov tasdiqlandi! Mahsulot yuborilmoqda...');

            $recipient  = $order['recipient'] ?? '';
            $extra      = json_decode($order['extra_data'] ?? '{}', true) ?? [];

            if ($order['type'] === 'stars') {
                $stars    = (int)$order['amount'];
                $username = ltrim($recipient ?: (string)$uid, '@');
                $res      = hyperpin_buy_stars($username, $stars);
                if ($res['success'] ?? false) {
                    update_order_status($oid, 'completed');
                    edit_message_text($chat_id, $mid,
                        "â <b>Muvaffaqiyatli!</b>\n\nð¤ Qabul qiluvchi: <b>@{$username}</b>\nâ­ <b>{$stars} Telegram Stars</b> yuborildi!\nð§¾ Buyurtma: <code>#{$oid}</code>"
                    );
                    // ð¢ Kanalga xabar
                    notify_channel_order(array_merge($order, ['order_id' => $oid]), $from);
                } else {
                    $err = $res['error'] ?? 'Noma\'lum xato';
                    edit_message_text($chat_id, $mid, "â To'lov qabul qilindi!\nâ­ Stars tez orada yuboriladi.\nð§¾ Buyurtma: <code>#{$oid}</code>");
                    notify_admin("Stars yuborilmadi!", "ð³ Xaridor: <code>{$uid}</code>\nð¤ Qabul: <b>@{$username}</b>\nâ­ Miqdor: <b>{$stars}</b>\nð§¾ Order: <code>#{$oid}</code>\nâ ï¸ Xato: <code>{$err}</code>");
                }

            } elseif ($order['type'] === 'premium') {
                $months   = (int)$order['amount'];
                $username = ltrim($recipient, '@');
                if (!$username) { edit_message_text($chat_id, $mid, "â Premium uchun \@username topilmadi.\nAdmin bilan bog'laning."); return; }
                $res = hyperpin_buy_premium($username, $months);
                if ($res['success'] ?? false) {
                    update_order_status($oid, 'completed');
                    edit_message_text($chat_id, $mid,
                        "â <b>Muvaffaqiyatli!</b>\n\nð¤ Qabul: <b>@{$username}</b>\nð <b>{$months} oy Telegram Premium</b> faollashtirildi!\nð§¾ Buyurtma: <code>#{$oid}</code>"
                    );
                    notify_channel_order(array_merge($order, ['order_id' => $oid]), $from);
                } else {
                    $err = $res['error'] ?? 'Noma\'lum xato';
                    edit_message_text($chat_id, $mid, "â To'lov qabul qilindi!\nð Premium tez orada faollashadi.\nð§¾ Buyurtma: <code>#{$oid}</code>");
                    notify_admin("Premium faollashmadi!", "ð³ Xaridor: <code>{$uid}</code>\nð¤ Qabul: <b>@{$username}</b>\nð Muddat: <b>{$months} oy</b>\nð§¾ Order: <code>#{$oid}</code>\nâ ï¸ Xato: <code>{$err}</code>");
                }

            } elseif ($order['type'] === 'gift') {
                $gift_idx  = (int)($extra['gift_idx'] ?? 0);
                $pkgs      = get_packages('gift');
                $username  = ltrim($recipient ?: (string)$uid, '@');
                if (isset($pkgs[$gift_idx])) {
                    $p   = $pkgs[$gift_idx];
                    $res = hyperpin_buy_gift($username, $p['gift_id'], $p['stars_cost']);
                    if ($res['success'] ?? false) {
                        update_order_status($oid, 'completed');
                        edit_message_text($chat_id, $mid,
                            "â <b>Muvaffaqiyatli!</b>\n\nð¤ Qabul: <b>@{$username}</b>\nð <b>{$p['name']}</b> yuborildi!\nð§¾ Buyurtma: <code>#{$oid}</code>"
                        );
                        notify_channel_order(array_merge($order, ['order_id' => $oid]), $from);
                    } else {
                        $err = $res['error'] ?? 'Noma\'lum xato';
                        edit_message_text($chat_id, $mid, "â To'lov qabul qilindi!\nð Gift tez orada yuboriladi.\nð§¾ Buyurtma: <code>#{$oid}</code>");
                        notify_admin("Gift yuborilmadi!", "ð³ Xaridor: <code>{$uid}</code>\nð¤ Qabul: <b>@{$username}</b>\nð {$p['name']}\nð§¾ Order: <code>#{$oid}</code>\nâ ï¸ Xato: <code>{$err}</code>");
                    }
                } else {
                    edit_message_text($chat_id, $mid, "â To'lov qabul qilindi!\nð Gift tez orada yuboriladi.\nð§¾ Buyurtma: <code>#{$oid}</code>");
                }

            } elseif ($order['type'] === 'nomer') {
                $c_code = $extra['country_code'] ?? '';
                $c_name = $extra['country_name'] ?? $c_code;
                if (!$c_code) {
                    edit_message_text($chat_id, $mid, "â Davlat ma'lumotlari topilmadi. Admin bilan bog'laning.");
                    notify_admin("Nomer: country_code yo'q!", "ð¤ User: <code>{$uid}</code>\nð§¾ Order: <code>#{$oid}</code>");
                    return;
                }
                $result = fixsim_buy_number($c_code);
                if ($result['error']) {
                    edit_message_text($chat_id, $mid, "<b>âï¸ To'lov qabul qilindi, lekin raqam qolmagan.</b>\n\nAdmin bilan bog'laning â puliz qaytariladi.");
                    notify_admin("Nomer ajratilmadi (to'lov o'tdi)!", "ð¤ User: <code>{$uid}</code>\nð Davlat: <b>{$c_name}</b>\nð° To'lov: <b>" . fmt($order['price']) . " so'm</b>\nð§¾ Order: <code>#{$oid}</code>\nâ ï¸ Xato: <code>{$result['error']}</code>");
                    return;
                }
                $phone = $result['phone'];
                $hash  = $result['hash'];
                update_order_status($oid, 'completed');
                edit_message_text($chat_id, $mid, "â <b>To'lov tasdiqlandi, raqam ajratildi!</b>");
                send_message($chat_id,
                    "<b>â Raqam muvaffaqiyatli olindi!</b>\n\n<blockquote>"
                    . "ð Raqamingiz: <code>{$phone}</code>\n"
                    . "ð Davlat: {$c_name}\n"
                    . "ðµ Narxi: <code>" . fmt($order['price']) . "</code> so'm"
                    . "</blockquote>\n\n<blockquote>"
                    . "â ï¸ Bu raqam bilan Telegramning rasmiy ilovasidan kirmang!\n"
                    . "ð± Nicegram, Plus Messenger kabi norasmiy ilovalardan foydalaning.\n"
                    . "ð¡ Kodni olish uchun quyidagi tugmani bosing."
                    . "</blockquote>",
                    ['reply_markup' => kb_inline([[['text' => 'ð¢ SMS kodni olish', 'callback_data' => "fs_sms:{$hash}"]]])]
                );
                // ð¢ Kanalga nomer xabarnomasi
                notify_channel_order(array_merge($order, ['order_id' => $oid]), $from);
            }
            clear_session($uid);

        } elseif (in_array($status, ['expired', 'cancelled']) || $secs <= 0) {
            update_order_status($oid, 'expired');
            answer_callback($cbid, "â° Muddat tugadi. Qaytadan urinib ko'ring.", true);
            edit_message_text($chat_id, $mid, "â° <b>To'lov muddati tugadi.</b>\n\nQaytadan /start bosing.", ['reply_markup' => null]);
            clear_session($uid);
        } else {
            $m = intdiv($secs, 60); $s = $secs % 60;
            $t = $m ? "â³ Hali tasdiqlanmadi. ~{$m} daqiqa {$s} soniya qoldi." : "â³ Hali tasdiqlanmadi. ~{$s} soniya qoldi.";
            answer_callback($cbid, $t, true);
        }
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'cancel_pay:')) {
        $oid = (int)explode(':', $data)[1];
        update_order_status($oid, 'cancelled');
        clear_session($uid);
        edit_message_text($chat_id, $mid, "â To'lov bekor qilindi.");
        answer_callback($cbid);
        return;
    }

    // ââ Admin callback-lar ââ
    if ($uid !== ADMIN_ID) { answer_callback($cbid); return; }

    // Toggle
    foreach (['toggle_stars' => ['stars_enabled', 'Stars'], 'toggle_premium' => ['premium_enabled', 'Premium'], 'toggle_gift' => ['gift_enabled', 'Gift'], 'toggle_nomer' => ['nomer_enabled', 'Nomer xizmati']] as $cb_key => [$setting, $label]) {
        if ($data === $cb_key) {
            $cur = get_setting($setting, '1');
            $new = $cur === '1' ? '0' : '1';
            set_setting($setting, $new);
            $state_text = $new === '1' ? 'yoqildi â' : "o'chirildi â";
            answer_callback($cbid, "{$label} {$state_text}", true);
            try { delete_message($chat_id, $mid); } catch (Exception $e) {}
            return;
        }
    }

    if ($data === 'edit_welcome') {
        update_session($uid, ['state' => 'admin_edit_welcome']);
        send_message($chat_id, "âï¸ Yangi xush kelish xabarini kiriting:");
        answer_callback($cbid);
        return;
    }

    if ($data === 'admin_back') {
        clear_session($uid);
        try { delete_message($chat_id, $mid); } catch (Exception $e) {}
        answer_callback($cbid);
        return;
    }

    // Majburiy obuna
    if ($data === 'add_channel') {
        update_session($uid, ['state' => 'admin_add_ch_id']);
        send_message($chat_id, "ð¢ Kanal ID sini kiriting (masalan: @mening_kanal yoki -1001234567890):");
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'del_channel:')) {
        $id = (int)explode(':', $data)[1];
        remove_forced_channel($id);
        $channels = get_forced_channels();
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_admin_channels($channels)]);
        answer_callback($cbid, "â O'chirildi.");
        return;
    }

    // Stars paket
    if (str_starts_with($data, 'edit_stars:')) {
        $idx = (int)explode(':', $data)[1];
        $pkgs = get_packages('stars');
        update_session($uid, ['state' => 'admin_edit_price', 'idx' => $idx, 'pkg_type' => 'stars']);
        send_message($chat_id, "â­ <b>{$pkgs[$idx]['stars']} Stars</b> â hozirgi: <b>" . fmt($pkgs[$idx]['price']) . " so'm</b>\n\nYangi narx (so'm):");
        answer_callback($cbid);
        return;
    }

    if ($data === 'add_stars_pkg') {
        update_session($uid, ['state' => 'admin_add_stars_value']);
        send_message($chat_id, "â­ Yangi paket uchun nechta Stars? (masalan: 300)");
        answer_callback($cbid);
        return;
    }

    if ($data === 'del_stars_menu') {
        $pkgs = get_packages('stars');
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_del_pkg($pkgs, 'stars', 'stars')]);
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'del_stars:')) {
        $idx = (int)explode(':', $data)[1];
        $pkgs = get_packages('stars');
        if (count($pkgs) <= 1) { answer_callback($cbid, 'Kamida 1 ta paket qolishi kerak!', true); return; }
        $removed = array_splice($pkgs, $idx, 1)[0];
        set_packages('stars', $pkgs);
        answer_callback($cbid, "â {$removed['stars']} Stars o'chirildi.");
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_admin_pkg($pkgs, 'stars', 'stars', 'price', 'add_stars_pkg', 'del_stars_menu')]);
        return;
    }

    // Premium paket
    if (str_starts_with($data, 'edit_premium:')) {
        $idx = (int)explode(':', $data)[1];
        $pkgs = get_packages('premium');
        update_session($uid, ['state' => 'admin_edit_price', 'idx' => $idx, 'pkg_type' => 'premium']);
        send_message($chat_id, "ð <b>{$pkgs[$idx]['label']}</b> â hozirgi: <b>" . fmt($pkgs[$idx]['price']) . " so'm</b>\n\nYangi narx (so'm):");
        answer_callback($cbid);
        return;
    }

    if ($data === 'add_premium_pkg') {
        update_session($uid, ['state' => 'admin_add_premium_value']);
        send_message($chat_id, "ð Necha oy uchun paket? (masalan: 2)");
        answer_callback($cbid);
        return;
    }

    if ($data === 'del_premium_menu') {
        $pkgs = get_packages('premium');
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_del_pkg($pkgs, 'premium', 'label')]);
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'del_premium:')) {
        $idx = (int)explode(':', $data)[1];
        $pkgs = get_packages('premium');
        if (count($pkgs) <= 1) { answer_callback($cbid, 'Kamida 1 ta paket qolishi kerak!', true); return; }
        $removed = array_splice($pkgs, $idx, 1)[0];
        set_packages('premium', $pkgs);
        answer_callback($cbid, "â {$removed['label']} o'chirildi.");
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_admin_pkg($pkgs, 'premium', 'label', 'price', 'add_premium_pkg', 'del_premium_menu')]);
        return;
    }

    // Gift paket
    if (str_starts_with($data, 'edit_gift:')) {
        $idx = (int)explode(':', $data)[1];
        $pkgs = get_packages('gift');
        update_session($uid, ['state' => 'admin_edit_price', 'idx' => $idx, 'pkg_type' => 'gift']);
        send_message($chat_id, "ð <b>{$pkgs[$idx]['name']}</b> â hozirgi: <b>" . fmt($pkgs[$idx]['price']) . " so'm</b>\n\nYangi narx (so'm):");
        answer_callback($cbid);
        return;
    }

    if ($data === 'add_gift_pkg') {
        update_session($uid, ['state' => 'admin_add_gift_id']);
        send_message($chat_id, "ð Gift ID sini kiriting (masalan: gift_select:0:15):");
        answer_callback($cbid);
        return;
    }

    if ($data === 'del_gift_menu') {
        $pkgs = get_packages('gift');
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_del_pkg($pkgs, 'gift', 'name')]);
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'del_gift:')) {
        $idx = (int)explode(':', $data)[1];
        $pkgs = get_packages('gift');
        if (count($pkgs) <= 1) { answer_callback($cbid, 'Kamida 1 ta paket qolishi kerak!', true); return; }
        $removed = array_splice($pkgs, $idx, 1)[0];
        set_packages('gift', $pkgs);
        answer_callback($cbid, "â {$removed['name']} o'chirildi.");
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_admin_pkg($pkgs, 'gift', 'name', 'price', 'add_gift_pkg', 'del_gift_menu')]);
        return;
    }

    // Nomer narx override
    if (str_starts_with($data, 'nomer_edit:')) {
        $code = explode(':', $data)[1];
        update_session($uid, ['state' => 'admin_nomer_set_price', 'nomer_code' => $code]);
        send_message($chat_id, "âï¸ <b>{$code}</b> uchun yangi narx (so'm):");
        answer_callback($cbid);
        return;
    }

    if (str_starts_with($data, 'nomer_del:')) {
        $code = explode(':', $data)[1];
        remove_nomer_override($code);
        $ov  = get_nomer_overrides();
        $c   = fixsim_get_countries();
        tg('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $mid, 'reply_markup' => kb_nomer_overrides($ov, $c)]);
        answer_callback($cbid, "â O'chirildi.");
        return;
    }

    if ($data === 'nomer_add') {
        $countries = get_countries_with_overrides();
        edit_message_text($chat_id, $mid, "<b>ð Davlat tanlang:</b>", ['reply_markup' => kb_fixsim_countries($countries, 0)]);
        answer_callback($cbid);
        return;
    }

    if ($data === 'admin_nomer_back') {
        $ov  = get_nomer_overrides();
        $c   = fixsim_get_countries();
        edit_message_text($chat_id, $mid, "ð <b>Nomer narxlari</b>", ['reply_markup' => kb_nomer_overrides($ov, $c)]);
        answer_callback($cbid);
        return;
    }

    answer_callback($cbid);
}

// âââ Kirish nuqtasi ââââââââââââââââââââââââââââââââââââââââââââââ
init_db();
$input = file_get_contents('php://input');
$update = json_decode($input, true);
if ($update) handle_update($update);
