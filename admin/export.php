<?php
declare(strict_types=1);

require __DIR__ . '/../includes/functions.php';
require_admin();

$rows = read_json('confirmations');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="confirmacoes-casamento.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'wb');
fputcsv($out, ['Nome', 'E-mail', 'Telefone', 'Presença', 'Adultos', 'Crianças', 'Presente', 'Observações', 'Recebido em'], ';');
foreach ($rows as $row) {
    fputcsv($out, [$row['name'] ?? '', $row['email'] ?? '', $row['phone'] ?? '', ($row['attendance'] ?? '') === 'sim' ? 'Sim' : 'Não', $row['adults'] ?? 0, $row['children'] ?? 0, $row['gift_name'] ?? '', $row['note'] ?? '', $row['created_at'] ?? ''], ';');
}
fclose($out);
exit;
