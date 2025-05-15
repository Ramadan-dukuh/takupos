<?php
include "koneksi.php";

// Nonaktifkan tampilan error di output CSV
ini_set('display_errors', 0);
error_reporting(0);

// Ambil parameter filter dari URL
$search = $_GET['search'] ?? '';
$search_column = $_GET['search_column'] ?? '';
$date_filter = $_GET['date'] ?? '';

$where_clauses = [];

if (!empty($date_filter)) {
    if ($date_filter == 'today') {
        $where_clauses[] = "DATE(t.created_at) = CURDATE()";
    } elseif ($date_filter == 'yesterday') {
        $where_clauses[] = "DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($date_filter == 'week') {
        $where_clauses[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_filter == 'month') {
        $where_clauses[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
}

$allowed_columns = ['id', 'user_id', 'payment_type'];
if (!empty($search) && in_array($search_column, $allowed_columns)) {
    $where_clauses[] = "t.$search_column LIKE '%" . $kon->real_escape_string($search) . "%'";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$query = "
    SELECT 
        t.id AS transaction_id,
        t.user_id,
        t.payment_type,
        t.created_at,
        t.updated_at,
        (
            SELECT SUM(td.quantity)
            FROM transaction_details td
            WHERE td.transaction_id = t.id
        ) AS total_items
    FROM transactions t
    $where_sql
    ORDER BY t.created_at DESC
";

$result = $kon->query($query);

// Set header agar file diunduh sebagai CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename=transaction_report.csv');
echo "\xEF\xBB\xBF"; // Tambahkan BOM agar Excel membaca UTF-8 dengan benar

$output = fopen('php://output', 'w');

// Tulis header kolom
fputcsv($output, ['No', 'Transaction ID', 'User ID', 'Payment Type', 'Total Items', 'Created At', 'Updated At'], ';');

// Tulis data baris per baris
$no = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['transaction_id'],
        $row['user_id'],
        ucwords(str_replace('_', ' ', $row['payment_type'])),
        $row['total_items'] ?? 0,
        date('Y-m-d H:i', strtotime($row['created_at'])),
        date('Y-m-d H:i', strtotime($row['updated_at']))
    ], ';'); // Delimiter titik koma
}

fclose($output);
exit;
?>
