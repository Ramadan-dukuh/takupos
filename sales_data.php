<?php
include 'koneksi.php'; // koneksi database

function getSalesData($kon) {
    $data = array();
    
    // Today's sales
    $today_query = "SELECT 
                    COUNT(DISTINCT t.id) as sales, 
                    SUM(td.quantity * p.price) AS revenue
                FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                JOIN products p ON td.product_variant_id = p.id
                WHERE DATE(t.created_at) = CURDATE()";
    $today_result = $kon->query($today_query);
    $today_data = $today_result->fetch_assoc();
    $data['Today'] = array(
        'sales' => $today_data['sales'] ?? 0,
        'revenue' => $today_data['revenue'] ?? 0
    );
    
    // Yesterday's sales
    $yesterday_query = "SELECT 
                        COUNT(DISTINCT t.id) as sales, 
                        SUM(td.quantity * p.price) AS revenue
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN products p ON td.product_variant_id = p.id
                    WHERE DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $yesterday_result = $kon->query($yesterday_query);
    $yesterday_data = $yesterday_result->fetch_assoc();
    $data['Yesterday'] = array(
        'sales' => $yesterday_data['sales'] ?? 0,
        'revenue' => $yesterday_data['revenue'] ?? 0
    );
    
    // Last week's sales (last 7 days)
    $last_week_query = "SELECT 
                        COUNT(DISTINCT t.id) as sales, 
                        SUM(td.quantity * p.price) AS revenue
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN products p ON td.product_variant_id = p.id
                    WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    AND t.created_at < CURDATE()";
    $last_week_result = $kon->query($last_week_query);
    $last_week_data = $last_week_result->fetch_assoc();
    $data['Last Week'] = array(
        'sales' => $last_week_data['sales'] ?? 0,
        'revenue' => $last_week_data['revenue'] ?? 0
    );
    
    // Last month's sales
    $last_month_query = "SELECT 
                        COUNT(DISTINCT t.id) as sales, 
                        SUM(td.quantity * p.price) AS revenue
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN products p ON td.product_variant_id = p.id
                    WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND t.created_at < CURDATE()";
    $last_month_result = $kon->query($last_month_query);
    $last_month_data = $last_month_result->fetch_assoc();
    $data['Last Month'] = array(
        'sales' => $last_month_data['sales'] ?? 0,
        'revenue' => $last_month_data['revenue'] ?? 0
    );

    $data['Total'] = array(
        'sales' => $data['Today']['sales'] + $data['Yesterday']['sales'] + $data['Last Week']['sales'] + $data['Last Month']['sales'],
        'revenue' => $data['Today']['revenue'] + $data['Yesterday']['revenue'] + $data['Last Week']['revenue'] + $data['Last Month']['revenue']
    );

    $jumlah_transaksi = "SELECT COUNT(*) as total FROM transactions";
    $jumlah_transaksi_result = $kon->query($jumlah_transaksi);
    $jumlah_transaksi_row = $jumlah_transaksi_result->fetch_assoc();
    $data['Total']['transactions'] = $jumlah_transaksi_row['total'];
    
    return $data;
}

// Get sales data
$salesData = getSalesData($kon);

// Ubah jadi JSON
echo json_encode(array_map(function($key, $value) {
    return array(
        'name' => $key,
        'sales' => (int)$value['sales'],
        'revenue' => (float)$value['revenue']
    );
}, array_keys($salesData), array_values($salesData)));
?>
