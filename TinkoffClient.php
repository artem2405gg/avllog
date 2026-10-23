<?php
/**
 * TinkoffClient — HTTP-клиент для Tinkoff Kassa (v2 API)
 * 
 * Документация: https://www.tinkoff.ru/kassa/develop/api/
 * 
 * Алгоритм подписи (Token):
 *   1. Взять все скалярные поля запроса
 *   2. Исключить Token, Receipt, DATA, Shops
 *   3. Добавить поле Password = SecretKey
 *   4. Отсортировать по ключу (ksort)
 *   5. Конкатенировать ЗНАЧЕНИЯ (не ключи!)
 *   6. SHA-256 от получившейся строки
 * 
 * Использование:
 *   $client = new TinkoffClient();
 *   $result = $client->init(123, 1990.00, [
 *       'description' => 'Подписка Basic',
 *       'email' => 'user@example.com',
 *   ]);
 *   // → ['Success' => true, 'PaymentId' => '...', 'PaymentURL' => 'https://...']
 */

class TinkoffClient
{
    private string $terminalKey;
    private string $secretKey;
    private string $apiUrl;
    private string $notificationUrl;
    private string $successUrl;
    private string $failUrl;
    private string $taxation;
    private string $tax;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/payments.php';
        $t = $config['tinkoff'];

        $this->terminalKey     = $t['terminal_key'];
        $this->secretKey       = $t['secret_key'];
        $this->apiUrl          = rtrim($t['api_url'], '/') . '/';
        $this->notificationUrl = $t['notification_url'];
        $this->successUrl      = $t['success_url'];
        $this->failUrl         = $t['fail_url'];
        $this->taxation        = $t['taxation'];
        $this->tax             = $t['tax'];
    }

    /**
     * Создать подпись (Token) для запроса
     * 
     * ВАЖНО: Password добавляется В МАССИВ как поле перед сортировкой!
     * Это официальный алгоритм Tinkoff.
     */
    private function makeToken(array $data): string
    {
        // 1. Исключаем поля, которые НЕ участвуют в подписи
        unset(
            $data['Token'],
            $data['Receipt'],
            $data['DATA'],
            $data['Shops']
        );

        // 2. Оставляем только скалярные значения (не массивы, не пустые)
        $filtered = [];
        foreach ($data as $k => $v) {
            if (is_scalar($v) && $v !== '' && $v !== null && $v !== false) {
                $filtered[$k] = $v;
            }
        }

        // 3. Добавляем Password = SecretKey (ключевой момент!)
        $filtered['Password'] = $this->secretKey;

        // 4. Сортируем по ключу (по алфавиту)
        ksort($filtered);

        // 5. Конкатенируем ТОЛЬКО ЗНАЧЕНИЯ (не ключи!)
        $concat = '';
        foreach ($filtered as $v) {
            $concat .= $v;
        }

        // 6. SHA-256
        return hash('sha256', $concat);
    }

    /**
     * Универсальный POST к API Tinkoff
     */
    private function post(string $method, array $payload): array
    {
        $payload['TerminalKey'] = $this->terminalKey;
        $payload['Token']       = $this->makeToken($payload);

        $ch = curl_init($this->apiUrl . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Tinkoff HTTP error: ' . $err);
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            throw new RuntimeException('Некорректный ответ Tinkoff: ' . $response);
        }

        // Логируем для отладки
        $this->log($method, $payload, $json, $httpCode);

        return $json;
    }

    /**
     * Инициализация платежа
     * 
     * @param int   $orderId   Уникальный ID заказа (наш invoice.id)
     * @param float $amount    Сумма в рублях
     * @param array $options   Опции:
     *   - description: описание платежа
     *   - email: email для чека
     *   - phone: телефон для чека
     *   - customer_key: ID клиента
     *   - receipt: массив позиций чека
     *   - success_url, fail_url: кастомные URL возврата
     */
    public function init(int $orderId, float $amount, array $options = []): array
    {
        $payload = [
            'Amount'          => (int)round($amount * 100),  // в копейках
            'OrderId'         => (string)$orderId,
            'Description'     => $options['description'] ?? "Оплата заказа #{$orderId}",
            'NotificationURL' => $this->notificationUrl,
            'SuccessURL'      => $options['success_url'] ?? $this->successUrl,
            'FailURL'         => $options['fail_url']    ?? $this->failUrl,
        ];

        if (!empty($options['customer_key'])) {
            $payload['CustomerKey'] = (string)$options['customer_key'];
        }

        // Чек (для 54-ФЗ) — не участвует в подписи
        if (!empty($options['email']) || !empty($options['phone']) || !empty($options['receipt'])) {
            $payload['Receipt'] = [
                'Email'    => $options['email'] ?? null,
                'Phone'    => $options['phone'] ?? null,
                'Taxation' => $this->taxation,
                'Items'    => $options['receipt'] ?? [[
                    'Name'     => $options['description'] ?? "Оплата #{$orderId}",
                    'Price'    => (int)round($amount * 100),
                    'Quantity' => 1,
                    'Amount'   => (int)round($amount * 100),
                    'Tax'      => $this->tax,
                ]],
            ];
            if (empty($payload['Receipt']['Email'])) unset($payload['Receipt']['Email']);
            if (empty($payload['Receipt']['Phone'])) unset($payload['Receipt']['Phone']);
        }

        return $this->post('Init', $payload);
    }

    /**
     * Получить статус платежа
     */
    public function getState(string $paymentId): array
    {
        return $this->post('GetState', ['PaymentId' => $paymentId]);
    }

    /**
     * Подтвердить платёж (для двухстадийной оплаты)
     */
    public function confirm(string $paymentId, ?float $amount = null): array
    {
        $payload = ['PaymentId' => $paymentId];
        if ($amount !== null) {
            $payload['Amount'] = (int)round($amount * 100);
        }
        return $this->post('Confirm', $payload);
    }

    /**
     * Отменить платёж
     */
    public function cancel(string $paymentId): array
    {
        return $this->post('Cancel', ['PaymentId' => $paymentId]);
    }

    /**
     * Проверить подпись входящего webhook
     */
    public function verifyWebhook(array $data): bool
    {
        if (empty($data['Token'])) {
            return false;
        }
        $received = $data['Token'];
        $expected = $this->makeToken($data);
        return hash_equals($expected, $received);
    }

    /**
     * Логирование запросов (в файл)
     */
    private function log(string $method, array $request, array $response, int $httpCode): void
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $file = $logDir . '/tinkoff_' . date('Y-m-d') . '.log';

        $log = sprintf(
            "[%s] %s | HTTP %d\nREQ: %s\nRESP: %s\n---\n",
            date('Y-m-d H:i:s'),
            $method,
            $httpCode,
            json_encode($request, JSON_UNESCAPED_UNICODE),
            json_encode($response, JSON_UNESCAPED_UNICODE)
        );

        @file_put_contents($file, $log, FILE_APPEND);
    }
}