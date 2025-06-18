<?php
require __DIR__ . '/vendor/autoload.php';

// Database connection (adjust as needed)
$pdo = new PDO("mysql:host=localhost;dbname=dashboard", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_GET['id'] ?? null;
if (!$id) exit('Geen factuur ID opgegeven.');

// Fetch invoice
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$invoice) exit('Factuur niet gevonden.');

// Fetch client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$invoice['client_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch project (if applicable)
$project = null;
if (!empty($invoice['project_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$invoice['project_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch invoice items from the database
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoice['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate items total
$items_total = 0;
foreach ($items as $item) {
    $discount = isset($item['discount']) ? $item['discount'] : 0;
    $line_total = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
    $items_total += $line_total;
}

// Calculate totals
$subtotal = 0;
foreach ($items as &$item) {
    $discount = isset($item['discount']) ? $item['discount'] : 0;
    $item['totaal'] = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
    $subtotal += $item['totaal'];
}
$btw = $subtotal * 0.21;
$total = $subtotal + $btw;
$invoice_total = $invoice['amount'];

// Combine invoice amount and items total
$combined_total = $invoice['amount'] + $items_total;
$btw = $combined_total * 0.21;
$total_to_pay = $combined_total + $btw;

// HTML template
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 11pt; }
.header-table { width: 100%; margin-bottom: 18px; table-layout: fixed; }
.header-table th { color: #ef4444; font-weight: bold; font-size: 11pt; text-align: left; padding-bottom: 3px; }
.header-table td { font-size: 10.5pt; padding-bottom: 3px; }
.header-table th, .header-table td { padding-right: 28px; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; table-layout: fixed; }
.items-table th { color: #ef4444; font-weight: bold; border-bottom: 1px solid #888; padding: 6px 4px 6px 0; font-size: 10.5pt; }
.items-table td { border-bottom: 1px solid #ddd; padding: 6px 4px 6px 0; font-size: 10.5pt; }
.items-table tr:last-child td { border-bottom: none; }
.items-table th.oms { width: 38%; }
.items-table th.aantal { width: 12%; }
.items-table th.stuks { width: 16%; }
.items-table th.korting { width: 14%; }
.items-table th.bedrag { width: 20%; }
.totals-table { width: 100%; margin-top: 30px; }
.totals-table td { font-size: 11pt; padding: 4px 0; }
.totals-table tr td:first-child { border: none; }
.totals-table tr td:last-child { text-align: right; }
.totals-table .total-label { font-weight: bold; }
.watermark {
    position: absolute;
    left: 60px;
    bottom: 60px;
    width: 70%;
    opacity: 0.13;
    z-index: -1;
}
</style>

<table class="header-table">
    <tr>
        <th style="width:19%;">Factuurdatum</th>
        <th style="width:19%;">Reparatiedatum</th>
        <th style="width:22%;">Factuurnummer</th>
        <th style="width:20%;">Kenteken</th>
        <th style="width:20%;">Merk</th>
    </tr>
    <tr>
        <td>' . htmlspecialchars($invoice['date']) . '</td>
        <td>' . htmlspecialchars($invoice['repair_date'] ?? '-') . '</td>
        <td>' . htmlspecialchars($invoice['number'] ?? '-') . '</td>
        <td>' . htmlspecialchars($invoice['license_plate'] ?? '-') . '</td>
        <td>' . htmlspecialchars($invoice['car_model'] ?? '-') . '</td>
    </tr>
</table>
<table class="items-table">
    <tr>
        <th class="oms">Omschrijving</th>
        <th class="aantal">Aantal</th>
        <th class="stuks">Stuksprijs</th>
        <th class="korting">Korting %</th>
        <th class="bedrag">Bedrag</th>
    </tr>';

foreach ($items as $item) {
    $discount = isset($item['discount']) ? $item['discount'] : 0;
    $totaal = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
    $html .= '<tr>
        <td>' . nl2br(htmlspecialchars($item['description'])) . '</td>
        <td>' . $item['quantity'] . '</td>
        <td>€ ' . number_format($item['unit_price'], 2, ',', '.') . '</td>
        <td>' . $discount . '</td>
        <td>€ ' . number_format($totaal, 2, ',', '.') . '</td>
    </tr>';
}

$html .= '</table>

<img src="car_watermark.png" class="watermark" />

<table class="totals-table">
    <tr>
        <td colspan="4">Totaal (factuurbedrag + extra items):</td>
        <td>€ ' . number_format($combined_total, 2, ',', '.') . '</td>
    </tr>
    <tr>
        <td colspan="4">BTW 21%:</td>
        <td>€ ' . number_format($btw, 2, ',', '.') . '</td>
    </tr>
    <tr>
        <td colspan="4"><strong>Totaal te betalen:</strong></td>
        <td><strong>€ ' . number_format($total_to_pay, 2, ',', '.') . '</strong></td>
    </tr>
</table>
';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 18,
    'margin_bottom' => 18,
    'margin_left' => 18,
    'margin_right' => 18,
]);
$mpdf->WriteHTML($html);
$mpdf->Output('factuur-'.$invoice['id'].'.pdf', 'I');