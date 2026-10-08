<?php

require_once __DIR__ . '/send_order_email.php';

$testOrder = [
    'order_number'    => 'GB-' . date('Ymd') . '-8899',
    'customer_name'   => 'ผู้ทดสอบระบบ Grand Bake',
    'customer_email'  => 'hhddmg@gmail.com', // ส่งเข้าอีเมลร้านเพื่อทดสอบ
    'customer_phone'  => '081-234-5678',
    'customer_address'=> '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
    'payment_method'  => 'transfer', // หรือ 'cash'
    'items'           => [
        [
            'name'     => 'Strawberry Cream Cake',
            'price'    => 450,
            'quantity' => 2,
            'image'    => 'cake1.png'
        ],
        [
            'name'     => 'Chocolate Fudge Cake',
            'price'    => 490,
            'quantity' => 1,
            'image'    => 'cake2.png'
        ]
    ],
    'total_items'     => 3,
    'total_amount'    => 1390,
    'order_date'      => date('d/m/Y H:i น.')
];

echo "กำลังทดสอบส่งอีเมลคำสั่งซื้อสำเร็จ...\n";
$result = sendOrderConfirmationEmail($testOrder);

if ($result) {
    echo "ส่งอีเมลยืนยันคำสั่งซื้อสำเร็จเรียบร้อยแล้ว! ✅\n";
} else {
    echo "ส่งอีเมลล้มเหลว ❌ กรุณาตรวจสอบการตั้งค่า SMTP\n";
}
