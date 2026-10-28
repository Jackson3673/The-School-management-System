<?php
// config/currency.php
// Centralized Global Currency Configuration & Helpers for Hope Academy

if (!defined('DEFAULT_CURRENCY_SYMBOL')) {
    define('DEFAULT_CURRENCY_SYMBOL', 'KSh');
}

if (!defined('DEFAULT_CURRENCY_CODE')) {
    define('DEFAULT_CURRENCY_CODE', 'KES');
}

if (!defined('DEFAULT_CURRENCY_POSITION')) {
    define('DEFAULT_CURRENCY_POSITION', 'before'); // 'before' or 'after'
}

/**
 * Ensures the system_settings table exists in MySQL and seeds default currency settings if empty.
 *
 * @param PDO $pdo
 * @return void
 */
function ensure_system_settings_table(PDO $pdo): void {
    static $ensured = false;
    if ($ensured) {
        return;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Insert defaults if not present
        $defaults = [
            'currency_symbol' => DEFAULT_CURRENCY_SYMBOL,
            'currency_code' => DEFAULT_CURRENCY_CODE,
            'currency_position' => DEFAULT_CURRENCY_POSITION
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaults as $key => $val) {
            $stmt->execute([$key, $val]);
        }
        $ensured = true;
    } catch (\Throwable $e) {
        // Fall back gracefully if table cannot be created right now
    }
}

/**
 * Get current system currency configuration.
 *
 * @param bool $refresh Force refresh from database
 * @return array
 */
function get_system_currency_config(bool $refresh = false): array {
    static $config = null;
    if ($config !== null && !$refresh) {
        return $config;
    }

    $symbol = DEFAULT_CURRENCY_SYMBOL;
    $code = DEFAULT_CURRENCY_CODE;
    $position = DEFAULT_CURRENCY_POSITION;

    // Check environment variable overrides if defined
    if (getenv('APP_CURRENCY')) {
        $symbol = getenv('APP_CURRENCY');
    }
    if (getenv('APP_CURRENCY_CODE')) {
        $code = getenv('APP_CURRENCY_CODE');
    }

    // Try reading from system_settings table if $pdo is available
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            ensure_system_settings_table($pdo);
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('currency_symbol', 'currency_code', 'currency_position')");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($rows)) {
                if (isset($rows['currency_symbol']) && trim($rows['currency_symbol']) !== '') {
                    $symbol = trim($rows['currency_symbol']);
                }
                if (isset($rows['currency_code']) && trim($rows['currency_code']) !== '') {
                    $code = trim($rows['currency_code']);
                }
                if (isset($rows['currency_position']) && in_array($rows['currency_position'], ['before', 'after'])) {
                    $position = $rows['currency_position'];
                }
            }
        } catch (\Throwable $e) {
            // Silently use defaults
        }
    }

    $config = [
        'symbol' => $symbol,
        'code' => $code,
        'position' => $position,
    ];

    return $config;
}

/**
 * Update system currency globally in database.
 *
 * @param string $symbol
 * @param string $code
 * @param string $position
 * @return bool
 */
function set_system_currency(string $symbol, string $code = 'KES', string $position = 'before'): bool {
    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return false;
    }

    try {
        ensure_system_settings_table($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        $stmt->execute(['currency_symbol', $symbol]);
        $stmt->execute(['currency_code', $code]);
        $stmt->execute(['currency_position', $position]);

        // Invalidate in-memory cache
        get_system_currency_config(true);
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Return current global currency symbol (e.g. 'KSh', '$', '€', '£').
 */
function get_currency_symbol(): string {
    $cfg = get_system_currency_config();
    return $cfg['symbol'];
}

/**
 * Return current global currency code (e.g. 'KES', 'USD', 'EUR').
 */
function get_currency_code(): string {
    $cfg = get_system_currency_config();
    return $cfg['code'];
}

/**
 * Return current currency symbol position ('before' or 'after').
 */
function get_currency_position(): string {
    $cfg = get_system_currency_config();
    return $cfg['position'];
}

/**
 * Format a number using the global system currency.
 *
 * @param float|int|string $amount The numerical amount.
 * @param int $decimals Decimal places (default 2).
 * @param bool $includeSymbol Include currency symbol.
 * @param string|null $customPrefix Optional sign or prefix (e.g., '+' or '-').
 * @return string
 */
function format_currency($amount, int $decimals = 2, bool $includeSymbol = true, ?string $customPrefix = null): string {
    $cfg = get_system_currency_config();
    $num = is_numeric($amount) ? (float)$amount : 0.0;
    $formatted = number_format(abs($num), $decimals);

    $sign = '';
    if ($customPrefix !== null) {
        $sign = $customPrefix;
    } elseif ($num < 0) {
        $sign = '-';
    }

    if (!$includeSymbol) {
        return ($sign !== '' ? $sign . ' ' : '') . $formatted;
    }

    if ($cfg['position'] === 'after') {
        return ($sign !== '' ? $sign . ' ' : '') . $formatted . ' ' . $cfg['symbol'];
    }

    // Default 'before' position: e.g., "KSh 5,000.00" or "+ KSh 5,000.00"
    return ($sign !== '' ? $sign . ' ' : '') . $cfg['symbol'] . ' ' . $formatted;
}
