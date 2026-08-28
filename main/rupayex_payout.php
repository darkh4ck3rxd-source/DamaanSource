<?php
/**
 * Rupayex payout integration shared helper.
 * Credentials are read only from Railway environment variables.
 * RUPAYEX_PAYOUT_ENABLED must equal "1" before any payout request is allowed.
 */

function rupayex_config(): array
{
    return [
        'base_url' => rtrim((string)(getenv('RUPAYEX_API_BASE_URL') ?: 'https://rupayex.net/api'), '/'),
        'token' => trim((string)(getenv('RUPAYEX_API_TOKEN') ?: '')),
        'enabled' => getenv('RUPAYEX_ENABLED') !== false
            ? trim((string)getenv('RUPAYEX_ENABLED')) === '1'
            : (trim((string)(getenv('RUPAYEX_DEPOSIT_ENABLED') ?: '0')) === '1' || trim((string)(getenv('RUPAYEX_PAYOUT_ENABLED') ?: '0')) === '1'),
        'timeout' => max(5, min(45, (int)(getenv('RUPAYEX_API_TIMEOUT') ?: 20))),
    ];
}

function rupayex_ensure_schema(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS rupayex_payouts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        withdrawal_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        amount DECIMAL(20,2) NOT NULL,
        method VARCHAR(10) NOT NULL,
        account_holder VARCHAR(150) NOT NULL,
        account_reference VARCHAR(255) NOT NULL,
        provider_payout_id VARCHAR(100) NULL,
        provider_status VARCHAR(30) NOT NULL DEFAULT 'NOT_SENT',
        provider_message VARCHAR(500) NULL,
        provider_response TEXT NULL,
        requested_by BIGINT NULL,
        requested_by_name VARCHAR(120) NULL,
        attempt_count INT NOT NULL DEFAULT 0,
        last_checked_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_rupayex_withdrawal (withdrawal_id),
        UNIQUE KEY uq_rupayex_provider_payout (provider_payout_id),
        KEY idx_rupayex_status (provider_status),
        KEY idx_rupayex_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$conn->query($sql)) {
        throw new RuntimeException('Could not prepare payout audit storage.');
    }
}

function rupayex_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function rupayex_method_and_account(array $withdrawal): array
{
    $type = (int)($withdrawal['madari'] ?? 0);
    if ($type === 2) {
        return [
            'method' => 'upi',
            'holder' => trim((string)($withdrawal['account_holder'] ?? '')),
            'account' => trim((string)($withdrawal['account_number'] ?? '')),
            'payload' => ['upi_id' => trim((string)($withdrawal['account_number'] ?? ''))],
        ];
    }
    if ($type === 1) {
        return [
            'method' => 'bank',
            'holder' => trim((string)($withdrawal['account_holder'] ?? '')),
            'account' => trim((string)($withdrawal['account_number'] ?? '')),
            'payload' => [
                'account_number' => trim((string)($withdrawal['account_number'] ?? '')),
                'ifsc_code' => trim((string)($withdrawal['ifsc_code'] ?? '')),
                'bank_name' => trim((string)($withdrawal['bank_name'] ?? '')),
            ],
        ];
    }
    throw new InvalidArgumentException('Rupayex supports only UPI and Bank Card withdrawals.');
}

function rupayex_load_withdrawal(mysqli $conn, int $withdrawalId): ?array
{
    $sql = "SELECT h.shonu, h.balakedara, h.motta, h.dharavahi, h.madari, h.sthiti,
                   s.mobile,
                   k.phalanubhavi AS account_holder,
                   k.khatesankhye AS account_number,
                   k.kod AS ifsc_code,
                   k.khatehesaru AS bank_name
            FROM hintegedukolli h
            LEFT JOIN shonu_subjects s ON s.id = h.balakedara
            LEFT JOIN khate k ON k.shonu = h.khateshonu
            WHERE h.shonu = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { throw new RuntimeException('Could not prepare withdrawal lookup.'); }
    $stmt->bind_param('i', $withdrawalId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function rupayex_audit_row(mysqli $conn, int $withdrawalId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM rupayex_payouts WHERE withdrawal_id = ? LIMIT 1');
    if (!$stmt) { throw new RuntimeException('Could not prepare payout audit lookup.'); }
    $stmt->bind_param('i', $withdrawalId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function rupayex_provider_request(array $payload, array $config, string $endpoint = '/create-payout'): array
{
    $ch = curl_init($config['base_url'] . $endpoint);
    if (!$ch) { throw new RuntimeException('Could not initialize payout request.'); }
    $body = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $curlError !== '') {
        throw new RuntimeException('Rupayex request failed: ' . substr($curlError ?: 'network error', 0, 300));
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Rupayex returned a non-JSON response (HTTP ' . $httpCode . ').');
    }
    return ['http_code' => $httpCode, 'body' => $decoded, 'raw' => substr($raw, 0, 8000)];
}

function rupayex_provider_status(string $payoutId, array $config): array
{
    $url = $config['base_url'] . '/payout-status?' . http_build_query(['user_token' => $config['token'], 'payout_id' => $payoutId], '', '&', PHP_QUERY_RFC3986);
    $ch = curl_init($url);
    if (!$ch) { throw new RuntimeException('Could not initialize payout status request.'); }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $curlError !== '') {
        throw new RuntimeException('Rupayex status request failed: ' . substr($curlError ?: 'network error', 0, 300));
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Rupayex returned a non-JSON status response (HTTP ' . $httpCode . ').');
    }
    return ['http_code' => $httpCode, 'body' => $decoded, 'raw' => substr($raw, 0, 8000)];
}

function rupayex_normalize_status(string $status): string
{
    $status = strtoupper(trim($status));
    if (in_array($status, ['SUCCESS', 'COMPLETED', 'PAID'], true)) { return 'SUCCESS'; }
    if (in_array($status, ['FAILED', 'FAILURE', 'REJECTED', 'CANCELLED'], true)) { return 'FAILED'; }
    if (in_array($status, ['PENDING', 'PROCESSING', 'INITIATED'], true)) { return 'PENDING'; }
    return $status !== '' ? substr($status, 0, 30) : 'UNKNOWN';
}

function rupayex_deposit_enabled(): bool
{
    $config = rupayex_config();
    return $config['enabled'] && trim((string)(getenv('RUPAYEX_DEPOSIT_ENABLED') ?: '0')) === '1' && $config['token'] !== '';
}

function rupayex_create_order(string $orderId, float $amount, string $customerMobile = '', string $remark = ''): array
{
    $config = rupayex_config();
    if (!rupayex_deposit_enabled()) {
        throw new RuntimeException('Rupayex deposit is not configured.');
    }
    $redirectUrl = rtrim((string)(getenv('RUPAYEX_REDIRECT_URL') ?: 'https://www.jalwagames.site/pay/rupayex_callback.php'), '/');
    $payload = [
        'user_token' => $config['token'],
        'amount' => number_format($amount, 2, '.', ''),
        'order_id' => $orderId,
        'redirect_url' => $redirectUrl,
    ];
    if ($customerMobile !== '') { $payload['customer_mobile'] = substr($customerMobile, 0, 30); }
    if ($remark !== '') { $payload['remark1'] = substr($remark, 0, 255); }
    return rupayex_provider_request($payload, $config, '/create-order');
}

function rupayex_ensure_deposit_schema(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS rupayex_orders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        local_order_id VARCHAR(100) NOT NULL,
        provider_order_id VARCHAR(100) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        amount DECIMAL(20,2) NOT NULL,
        provider_status VARCHAR(30) NOT NULL DEFAULT 'CREATED',
        utr VARCHAR(100) NULL,
        provider_response TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_rupayex_local_order (local_order_id),
        UNIQUE KEY uq_rupayex_provider_order (provider_order_id),
        KEY idx_rupayex_order_user (user_id),
        KEY idx_rupayex_order_status (provider_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$conn->query($sql)) {
        throw new RuntimeException('Could not prepare deposit-order audit storage.');
    }
}

function rupayex_order_status(string $orderId): array
{
    $config = rupayex_config();
    if (!rupayex_deposit_enabled()) {
        throw new RuntimeException('Rupayex deposit is not configured.');
    }
    $url = $config['base_url'] . '/order-status?' . http_build_query(['user_token' => $config['token'], 'order_id' => $orderId], '', '&', PHP_QUERY_RFC3986);
    $ch = curl_init($url);
    if (!$ch) { throw new RuntimeException('Could not initialize order status request.'); }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $curlError !== '') {
        throw new RuntimeException('Rupayex order-status request failed: ' . substr($curlError ?: 'network error', 0, 300));
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Rupayex returned a non-JSON order-status response (HTTP ' . $httpCode . ').');
    }
    return ['http_code' => $httpCode, 'body' => $decoded, 'raw' => substr($raw, 0, 8000)];
}

function rupayex_find_payment_url(array $response): string
{
    $candidates = [
        $response['payment_url'] ?? null, $response['paymentUrl'] ?? null,
        $response['pay_url'] ?? null, $response['payUrl'] ?? null,
        $response['checkout_url'] ?? null, $response['checkoutUrl'] ?? null,
        $response['url'] ?? null, $response['data']['payment_url'] ?? null,
        $response['data']['paymentUrl'] ?? null, $response['data']['pay_url'] ?? null,
        $response['data']['payUrl'] ?? null, $response['data']['checkout_url'] ?? null,
        $response['data']['checkoutUrl'] ?? null, $response['data']['url'] ?? null,
    ];
    foreach ($candidates as $candidate) {
        if (is_string($candidate) && preg_match('#^https?://#i', trim($candidate))) {
            return trim($candidate);
        }
    }
    return '';
}

function rupayex_credit_verified_order(mysqli $conn, string $orderId, string $providerUtr, string $providerStatus, float $verifiedAmount): array
{
    $status = rupayex_normalize_status($providerStatus);
    if ($status !== 'SUCCESS') {
        return ['credited' => false, 'status' => $status, 'message' => 'Payment is not successful.'];
    }
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT shonu, balakedara, motta, sthiti FROM thevani WHERE dharavahi = ? LIMIT 1 FOR UPDATE');
        if (!$stmt) { throw new RuntimeException('Could not prepare local order lookup.'); }
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        if (!$order) { throw new RuntimeException('Local deposit order not found.'); }
        $localAmount = round((float)$order['motta'], 2);
        if (abs($localAmount - round($verifiedAmount, 2)) > 0.009) {
            throw new RuntimeException('Verified payment amount does not match the local order.');
        }
        $audit = $conn->prepare("INSERT INTO rupayex_orders (local_order_id, provider_order_id, user_id, amount, provider_status, utr, provider_response, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE provider_status = VALUES(provider_status), utr = VALUES(utr), provider_response = VALUES(provider_response), updated_at = NOW()");
        if (!$audit) { throw new RuntimeException('Could not prepare payment audit update.'); }
        $providerResponse = json_encode(['status' => $status, 'utr' => $providerUtr], JSON_UNESCAPED_SLASHES);
        $providerStatusForDb = substr($status, 0, 30);
        $audit->bind_param('sssdsss', $orderId, $orderId, $order['balakedara'], $localAmount, $providerStatusForDb, $providerUtr, $providerResponse);
        if (!$audit->execute()) { throw new RuntimeException('Could not save payment audit.'); }
        $audit->close();
        if ((int)$order['sthiti'] === 0) {
            $wallet = $conn->prepare('UPDATE shonu_kaichila SET motta = ROUND(motta + ?, 2) WHERE balakedara = ?');
            if (!$wallet) { throw new RuntimeException('Could not prepare wallet credit.'); }
            $wallet->bind_param('di', $localAmount, $order['balakedara']);
            if (!$wallet->execute() || $wallet->affected_rows < 1) { throw new RuntimeException('Could not credit the user wallet.'); }
            $wallet->close();
            $mark = $conn->prepare("UPDATE thevani SET sthiti = '1', pavatiaidi = '2' WHERE dharavahi = ? AND sthiti = '0'");
            if (!$mark) { throw new RuntimeException('Could not prepare local order completion.'); }
            $mark->bind_param('s', $orderId);
            if (!$mark->execute()) { throw new RuntimeException('Could not mark local order complete.'); }
            $mark->close();
            $credited = true;
        } else {
            $credited = false;
        }
        $conn->commit();
        return ['credited' => $credited, 'status' => 'SUCCESS', 'message' => $credited ? 'Deposit credited.' : 'Deposit was already credited.'];
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}
