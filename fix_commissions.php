<?php
$root = dirname(__DIR__);
require_once $root . '/app/config.php';
require_once $root . '/app/database.php';
require_once $root . '/app/lib/Commission.php';

header('Content-Type: text/plain; charset=utf-8');

// Находим заказы с оплаченным bid, но без комиссии
$stmt = $pdo->query("
    SELECT o.id, o.price, b.carrier_id, b.price as bid_price
    FROM orders o
    JOIN bids b ON b.order_id = o.id AND b.status = 'accepted'
    WHERE o.carrier_payout = 0
      AND o.status IN ('accepted', 'pickup', 'loaded', 'in_transit', 'arrived', 'unloaded', 'completed')
");

$fixed = 0;
foreach ($stmt->fetchAll() as $row) {
    $price = (float)($row['bid_price'] > 0 ? $row['bid_price'] : $row['price']);
    $calc = Commission::calculate($price, (int)$row['carrier_id']);

    $pdo->prepare("
        UPDATE orders
        SET price = ?,
            commission_rate = ?,
            commission_amount = ?,
            carrier_payout = ?
        WHERE id = ?
    ")->execute([
        $price,
        $calc['commission_rate'],
        $calc['commission_amount'],
        $calc['carrier_payout'],
        $row['id'],
    ]);

    echo "Fixed order #{$row['id']}: price={$price}, rate={$calc['commission_rate']}, commission={$calc['commission_amount']}, payout={$calc['carrier_payout']}\n";
    $fixed++;
}

echo "\nTotal fixed: $fixed\n";