<?php
// ============================================================
// MoMo Payment Helper — creates a payment request & returns payUrl
// Usage: createMoMoPayment($orderId, $amount, $orderInfo, $extraData)
// ============================================================

require_once __DIR__ . '/../config/momo_config.php';

/**
 * Create a MoMo payment request and return the response array.
 * On success, $result['payUrl'] contains the redirect URL.
 *
 * @param string $orderId   Unique order identifier (will be echoed back in IPN)
 * @param int    $amount    Amount in VND (integer, min 1000)
 * @param string $orderInfo Human-readable order description
 * @param string $extraData Base64-encoded JSON with extra context (type, id)
 * @return array            Decoded MoMo API response
 */
function createMoMoPayment(string $orderId, int $amount, string $orderInfo, string $extraData = ''): array
{
    $partnerCode = MOMO_PARTNER_CODE;
    $accessKey   = MOMO_ACCESS_KEY;
    $secretKey   = MOMO_SECRET_KEY;
    $endpoint    = MOMO_ENDPOINT;
    $redirectUrl = MOMO_REDIRECT_URL;
    $ipnUrl      = MOMO_IPN_URL;
    $requestId   = $orderId . '_' . time();
    $requestType = 'captureWallet';

    // Build signature string (alphabetical key order as per MoMo docs)
    $rawHash = "accessKey=$accessKey"
             . "&amount=$amount"
             . "&extraData=$extraData"
             . "&ipnUrl=$ipnUrl"
             . "&orderId=$orderId"
             . "&orderInfo=$orderInfo"
             . "&partnerCode=$partnerCode"
             . "&redirectUrl=$redirectUrl"
             . "&requestId=$requestId"
             . "&requestType=$requestType";

    $signature = hash_hmac('sha256', $rawHash, $secretKey);

    $body = json_encode([
        'partnerCode' => $partnerCode,
        'partnerName' => 'Gym Manager',
        'storeId'     => 'GymManagerStore',
        'requestId'   => $requestId,
        'amount'      => $amount,
        'orderId'     => $orderId,
        'orderInfo'   => $orderInfo,
        'redirectUrl' => $redirectUrl,
        'ipnUrl'      => $ipnUrl,
        'lang'        => 'vi',
        'extraData'   => $extraData,
        'requestType' => $requestType,
        'signature'   => $signature,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS    => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER    => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ],
        CURLOPT_SSL_VERIFYPEER => false, // for localhost dev; enable in production
        CURLOPT_TIMEOUT        => 15,
    ]);

    $result = curl_exec($ch);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("MoMo cURL error: $err");
        return ['resultCode' => -1, 'message' => $err];
    }

    return json_decode($result, true) ?? ['resultCode' => -1, 'message' => 'Invalid JSON response'];
}

/**
 * Verify MoMo IPN/Return signature.
 * Call this in momo_ipn.php and momo_return.php.
 */
function verifyMoMoSignature(array $data): bool
{
    $accessKey = MOMO_ACCESS_KEY;
    $secretKey = MOMO_SECRET_KEY;

    $rawHash = "accessKey=$accessKey"
             . "&amount={$data['amount']}"
             . "&extraData={$data['extraData']}"
             . "&message={$data['message']}"
             . "&orderId={$data['orderId']}"
             . "&orderInfo={$data['orderInfo']}"
             . "&orderType={$data['orderType']}"
             . "&partnerCode={$data['partnerCode']}"
             . "&payType={$data['payType']}"
             . "&requestId={$data['requestId']}"
             . "&responseTime={$data['responseTime']}"
             . "&resultCode={$data['resultCode']}"
             . "&transId={$data['transId']}";

    $expected = hash_hmac('sha256', $rawHash, $secretKey);
    return hash_equals($expected, $data['signature'] ?? '');
}
