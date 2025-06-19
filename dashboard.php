<?php

try {
  $pdo = new PDO("mysql:host=localhost;dbname=dashboard", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
  exit;
}

session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Simple router

$page = $_GET['page'] ?? 'dashboard';

function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }

function generateUniqueFactuurnummer($pdo) {
    do {
        $number = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE number = ?");
        $stmt->execute([$number]);
        $exists = $stmt->fetchColumn();
    } while ($exists);
    return $number;
}

// Handle form submissions (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clients
    if ($page === 'clients' && !empty($_POST['name'])) {
        if (!empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE clients SET name = :name WHERE id = :id");
            $stmt->execute(['name' => $_POST['name'], 'id' => $_POST['edit_id']]);
            $_SESSION['success_message'] = "Klant succesvol bijgewerkt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (:name)");
            $stmt->execute(['name' => $_POST['name']]);
            $_SESSION['success_message'] = "Klant succesvol toegevoegd!";
        }
    }
    // Projects
    if ($page === 'projects' && !empty($_POST['name']) && !empty($_POST['client_id'])) {
        if (!empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE projects SET name = :name, client_id = :client_id, status = :status WHERE id = :id");
            $stmt->execute([
                'name' => $_POST['name'],
                'client_id' => $_POST['client_id'],
                'status' => $_POST['status'],
                'id' => $_POST['edit_id']
            ]);
            $_SESSION['success_message'] = "Project succesvol bijgewerkt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (name, client_id, status) VALUES (:name, :client_id, :status)");
            $stmt->execute([
                'name' => $_POST['name'],
                'client_id' => $_POST['client_id'],
                'status' => $_POST['status']
            ]);
            $_SESSION['success_message'] = "Project succesvol toegevoegd!";
        }
    }
    // Invoices
if ($page === 'invoices' && !empty($_POST['client_id']) && !empty($_POST['amount'])) {
    $paid = isset($_POST['paid']) ? 1 : 0;
    $repair_date = $_POST['repair_date'] ?? null;
    $license_plate = $_POST['license_plate'] ?? '';
    $car_model = $_POST['car_model'] ?? '';
    if (!empty($_POST['edit_id'])) {
        $stmt = $pdo->prepare("UPDATE invoices SET client_id = :client_id, project_id = :project_id, amount = :amount, paid = :paid, repair_date = :repair_date, license_plate = :license_plate, car_model = :car_model, number = :number WHERE id = :id");
        $stmt->execute([
            'client_id' => $_POST['client_id'],
            'project_id' => $_POST['project_id'],
            'amount' => $_POST['amount'],
            'number' => $_POST['number'], // Use the posted number for edit
            'paid' => $paid,
            'repair_date' => $repair_date,
            'license_plate' => $license_plate,
            'car_model' => $car_model,
            'id' => $_POST['edit_id']
        ]);
        $_SESSION['success_message'] = "Factuur succesvol bijgewerkt!";
    } else {
        $factuurnummer = generateUniqueFactuurnummer($pdo);
        $stmt = $pdo->prepare("INSERT INTO invoices (client_id, project_id, amount, date, paid, repair_date, license_plate, car_model, number) VALUES (:client_id, :project_id, :amount, CURDATE(), :paid, :repair_date, :license_plate, :car_model, :number)");
        $stmt->execute([
            'client_id' => $_POST['client_id'],
            'project_id' => $_POST['project_id'],
            'amount' => $_POST['amount'],
            'number' => $factuurnummer,
            'paid' => $paid,
            'repair_date' => $repair_date,
            'license_plate' => $license_plate,
            'car_model' => $car_model
        ]);
        $_SESSION['success_message'] = "Factuur succesvol aangemaakt!";
    }
}
    // Hours
if ($page === 'hours' && !empty($_POST['date']) && !empty($_POST['hours'])) {
    $project_id = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
    $client_id = !empty($_POST['client_id']) ? $_POST['client_id'] : null;

    if (!empty($_POST['edit_id'])) {
        $stmt = $pdo->prepare("UPDATE hours SET project_id = :project_id, client_id = :client_id, date = :date, hours = :hours WHERE id = :id");
        $stmt->execute([
            'project_id' => $project_id,
            'client_id' => $client_id,
            'date' => $_POST['date'],
            'hours' => $_POST['hours'],
            'id' => $_POST['edit_id']
        ]);
        $_SESSION['success_message'] = "Uren succesvol bijgewerkt!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO hours (project_id, client_id, date, hours) VALUES (:project_id, :client_id, :date, :hours)");
        $stmt->execute([
            'project_id' => $project_id,
            'client_id' => $client_id,
            'date' => $_POST['date'],
            'hours' => $_POST['hours']
        ]);
        $_SESSION['success_message'] = "Uur succesvol toegevoegd!";
    }
    header("Location: ?page=$page");
    exit;
}
}

// Handle delete actions with confirmation
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete' && is_numeric($_GET['id'])) {
    if ($page === 'clients') {
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['success_message'] = "Klant succesvol verwijderd!";
        header("Location: ?page=clients");
        exit;
    }
    if ($page === 'projects') {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['success_message'] = "Project succesvol verwijderd!";
        header("Location: ?page=projects");
        exit;
    }
    if ($page === 'invoices') {
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['success_message'] = "Factuur succesvol verwijderd!";
        header("Location: ?page=invoices");
        exit;
    }
    if ($page === 'hours') {
        $stmt = $pdo->prepare("DELETE FROM hours WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['success_message'] = "Uur succesvol verwijderd!";
        header("Location: ?page=hours");
        exit;
    }
}

// Fetch data from database
$clients = $pdo->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query("SELECT * FROM projects")->fetchAll(PDO::FETCH_ASSOC);
$invoices = $pdo->query("SELECT * FROM invoices")->fetchAll(PDO::FETCH_ASSOC);
$hours = $pdo->query("SELECT * FROM hours")->fetchAll(PDO::FETCH_ASSOC);

$clientsById = [];
foreach ($clients as $c) {
    $clientsById[$c['id']] = $c;
}
$projectsById = [];
foreach ($projects as $p) {
    $projectsById[$p['id']] = $p;
}

// Calculate today's and this week's hours
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

$hoursToday = 0;
$hoursThisWeek = 0;
foreach ($hours as $h) {
    if ($h['date'] === $today) {
        $hoursToday += $h['hours'];
    }
    if ($h['date'] >= $weekStart && $h['date'] <= $weekEnd) {
        $hoursThisWeek += $h['hours'];
    }
}

// --- Advanced statistics for stats page ---

// Project status counts
$ongoingProjects = array_filter($projects, fn($p) => ($p['status'] ?? 'ongoing') === 'ongoing');
$finishedProjects = array_filter($projects, fn($p) => ($p['status'] ?? 'ongoing') === 'finished');

// Invoice stats
$totalInvoices = count($invoices);
$paidInvoices = count(array_filter($invoices, fn($inv) => !empty($inv['paid'])));
$unpaidInvoices = $totalInvoices - $paidInvoices;
$totalInvoiced = array_sum(array_column($invoices, 'amount'));
$avgInvoice = $totalInvoices ? $totalInvoiced / $totalInvoices : 0;
$maxInvoice = $totalInvoices ? max(array_column($invoices, 'amount')) : 0;

// Hours per project
$projectHours = [];
foreach ($projects as $p) $projectHours[$p['id']] = 0;
foreach ($hours as $h) if (isset($projectHours[$h['project_id']])) $projectHours[$h['project_id']] += $h['hours'];
arsort($projectHours);
$topProjects = array_slice($projectHours, 0, 5, true);

// Hours per month (last 6 months)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[$m] = 0;
}
foreach ($hours as $h) {
    $m = substr($h['date'], 0, 7);
    if (isset($months[$m])) $months[$m] += $h['hours'];
}

// Top clients by project count
$clientProjectCounts = [];
foreach ($projects as $p) {
    $cid = $p['client_id'];
    $clientProjectCounts[$cid] = ($clientProjectCounts[$cid] ?? 0) + 1;
}
arsort($clientProjectCounts);
$topClients = array_slice($clientProjectCounts, 0, 5, true);

// Unpaid invoices by client
$unpaidByClient = [];
foreach ($invoices as $inv) {
    if (empty($inv['paid'])) {
        $cid = $inv['client_id'];
        $unpaidByClient[$cid] = ($unpaidByClient[$cid] ?? 0) + $inv['amount'];
    }
}
arsort($unpaidByClient);

// Project completion rate
$totalProjects = count($projects);
$finished = count($finishedProjects);
$completionRate = $totalProjects ? round(($finished / $totalProjects) * 100) : 0;

// Recent activity (last 5 hour entries)
$recentHours = array_slice(array_reverse($hours), 0, 5);

$currentMonth = date('Y-m');
$monthHours = 0;
foreach ($hours as $h) {
    if (strpos($h['date'], $currentMonth) === 0) $monthHours += $h['hours'];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Zelfstandig Monteur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body data-page="<?= $page ?>">
<?php if (!empty($_SESSION['success_message'])): ?>
    <div class="popup-success"><?= h($_SESSION['success_message']) ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="?page=dashboard">Overzicht</a>
        <a href="?page=invoices">Facturen</a>
        <a href="?page=hours">Uren</a>
        <a href="?page=clients">Klanten</a>
        <a href="?page=projects">Projecten</a>
        <a href="?page=stats">Statistieken</a>
    </div>
    <div class="main">
        <?php if ($page === 'dashboard'): ?>
            <h1 style="margin-bottom:1.5em;">Welkom terug!</h1>
            <div class="stats-grid dashboard-cards">
        <a href="?page=clients" class="stat-card-link">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-value"><?= count($clients) ?></div>
                <div class="stat-card-label">Klanten</div>
                <div style="margin-top:0.5em;font-size:0.98em;">
                    <span style="background:#2563eb22;color:#2563eb;font-weight:600;border-radius:6px;padding:2px 8px 2px 6px;margin-right:6px;">
                        <?= count($ongoingProjects) ?> lopende projecten
                    </span>
                </div>
            </div>
        </a>
        <a href="?page=hours" class="stat-card-link">
            <div class="stat-card stat-card-dark">
                <div class="stat-card-value"><?= array_sum(array_column($hours, 'hours')) ?></div>
                <div class="stat-card-label">Uren geregistreerd</div>
                <div class="stat-card-badges">
                    <span class="stat-badge stat-badge-dark"><?= $hoursThisWeek ?> deze week</span>
                    <span class="stat-badge stat-badge-dark"><?= $hoursToday ?> vandaag</span>
                </div>
            </div>
        </a>
        <a href="?page=invoices" class="stat-card-link">
            <div class="stat-card stat-card-white">
                <div class="stat-card-value"><?= $totalInvoices ?></div>
                <div class="stat-card-label">Facturen</div>
                <div class="stat-card-badges">
                    <span class="stat-badge stat-badge-green"><?= $paidInvoices ?> betaald</span>
                    <span class="stat-badge stat-badge-red"><?= $unpaidInvoices ?> open</span>
                </div>
            </div>
        </a>
    </div>

    <div class="card" style="margin-top:2em;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;">Projecten overzicht</h3>
        </div>
<table id="dashboardProjectsTable">
            <tr>
                <th>Project</th>
                <th>Klant</th>
                <th>Uren</th>
                <th>Datum</th>
                <th>Status</th>
            </tr>
            <?php foreach ($projects as $p): if (($p['status'] ?? 'ongoing') !== 'ongoing') continue;
                $projectHours = 0;
                $dates = [];
                foreach ($hours as $h) {
                    if ($h['project_id'] == $p['id']) {
                        $projectHours += $h['hours'];
                        if (!empty($h['date'])) $dates[] = $h['date'];
                    }
                }
                $firstDate = $dates ? min($dates) : null;
            ?>
            <tr>
                <td><?= h($p['name']) ?></td>
                <td><?= h($clientsById[$p['client_id']]['name'] ?? 'Onbekend') ?></td>
                <td><?= $projectHours ?></td>
                <td><?= $firstDate ? date('d-m-Y', strtotime($firstDate)) : '-' ?></td>
                <td>
                    <span style="color:#2563eb;font-weight:600;">Lopend</span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="?page=projects" style="display:inline-block;margin-top:1em;">Bekijk alle projecten</a>
    </div>
        <?php elseif ($page === 'stats'): ?>
            <h1 style="margin-bottom:1.5em;">📊 Statistieken</h1>
    <div class="flex" style="gap:2em;flex-wrap:wrap;">
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Uren per maand</h3>
            <canvas id="hoursPerMonthChart" height="180"></canvas>
        </div>
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Projectstatus</h3>
            <canvas id="projectStatusChart" style="max-width:400px;max-height:260px;display:block;margin:0 auto;" width="400" height="260"></canvas>
            <div style="margin-top:1.2em;">
                <span style="display:inline-block;width:14px;height:14px;background:#2563eb;border-radius:3px;margin-right:6px;vertical-align:middle;"></span>
                Lopend: <strong><?= count($ongoingProjects) ?></strong>
                <span style="display:inline-block;width:14px;height:14px;background:#22c55e;border-radius:3px;margin:0 6px 0 18px;vertical-align:middle;"></span>
                Afgerond: <strong><?= count($finishedProjects) ?></strong>
            </div>
        </div>
    </div>

    <div class="flex" style="gap:2em;flex-wrap:wrap;margin-top:2em;">
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Top 5 projecten (uren)</h3>
            <ol style="padding-left:1.2em;margin:0;">
                <?php foreach ($topProjects as $pid => $hcount): ?>
                    <li style="margin-bottom:.5em;">
                        <span style="font-weight:600;"><?= h($projectsById[$pid]['name'] ?? 'Onbekend') ?></span>
                        <span style="color:#2563eb;font-weight:500;">(<?= $hcount ?> uur)</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Top 5 klanten (projecten)</h3>
            <ol style="padding-left:1.2em;margin:0;">
                <?php foreach ($topClients as $cid => $count): ?>
                    <li style="margin-bottom:.5em;">
                        <span style="font-weight:600;"><?= h($clientsById[$cid]['name'] ?? 'Onbekend') ?></span>
                        <span style="color:#22c55e;font-weight:500;">(<?= $count ?> projecten)</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Facturatie</h3>
            <ul style="list-style:none;padding:0;margin:0;">
                <li>Totaal gefactureerd: <strong>€<?= number_format($totalInvoiced, 2, ',', '.') ?></strong></li>
                <li>Gemiddelde factuur: <strong>€<?= number_format($avgInvoice, 2, ',', '.') ?></strong></li>
                <li>Grootste factuur: <strong>€<?= number_format($maxInvoice, 2, ',', '.') ?></strong></li>
            </ul>
            <h4 style="margin-top:1.5em;margin-bottom:.7em;">Openstaand per klant</h4>
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach ($unpaidByClient as $cid => $amount): ?>
                    <li>
                        <span style="font-weight:600;"><?= h($clientsById[$cid]['name'] ?? 'Onbekend') ?>:</span>
                        <span style="color:#ef4444;font-weight:500;">€<?= number_format($amount, 2, ',', '.') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Recente activiteit</h3>
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach ($recentHours as $h): ?>
                    <li>
                        <?= date('d-m-Y', strtotime($h['date'])) ?>: 
                        <span style="font-weight:600;"><?= h($projectsById[$h['project_id']]['name'] ?? 'Onbekend project') ?></span>, 
                        <span style="color:#2563eb;font-weight:500;"><?= $h['hours'] ?> uur</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <h4 style="margin-top:1.5em;margin-bottom:.7em;">Uren deze maand</h4>
            <p style="margin:0;"><strong><?= $monthHours ?></strong> uur geregistreerd in <?= date('F Y') ?></p>
        </div>
    </div>
        <?php elseif ($page === 'invoices'): ?>
            <h1>Facturen</h1>
            <?php if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit' && is_numeric($_GET['id'])):
                $editInvoice = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
                $editInvoice->execute([$_GET['id']]);
                $invoice = $editInvoice->fetch();
            ?>
                <form class="card" method="post" action="">
                    <h3>Factuur bewerken</h3>
                    <input type="hidden" name="edit_id" value="<?= $invoice['id'] ?>">
                    <label>Klant
<select name="client_id" id="clientSelect" required>
    <?php foreach ($clients as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (!empty($invoice['client_id']) && $invoice['client_id'] == $c['id']) ? 'selected' : '' ?>>
            <?= h($c['name']) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </label>
                    <label>Project
    <select name="project_id" id="projectSelect" required>
        <option value="">-- Kies project --</option>
        <!-- Options will be filled by JS -->
    </select>
</label>
<label>Reparatiedatum
    <input type="date" name="repair_date" value="<?= h($invoice['repair_date'] ?? '') ?>">
</label>
<label>Kenteken
    <input type="text" name="license_plate" value="<?= h($invoice['license_plate'] ?? '') ?>">
</label>
<label>Merk
    <input type="text" name="car_model" value="<?= h($invoice['car_model'] ?? '') ?>">
</label>
                    <label>Bedrag (€)
                        <input type="number" name="amount" step="0.01" value="<?= h($invoice['amount']) ?>" required>
                    </label>
                    <label class="checkbox-inline">
    <input type="checkbox" name="paid" value="1" <?= !empty($invoice['paid']) ? 'checked' : '' ?>>
    <span>Factuur is betaald</span>
</label>
                    <button type="submit">Opslaan</button>
                    <a href="?page=invoices" style="margin-left:1em;">Annuleren</a>
                </form>
            <?php else: ?>
                <form class="card" method="post" action="">
                    <h3>Nieuwe factuur</h3>
                    <label>Klant
<select name="client_id" id="clientSelect" required>
    <?php foreach ($clients as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (!empty($invoice['client_id']) && $invoice['client_id'] == $c['id']) ? 'selected' : '' ?>>
            <?= h($c['name']) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </label>
                    <label>Project
    <select name="project_id" id="projectSelect" required>
        <option value="">-- Kies project --</option>
        <!-- Options will be filled by JS -->
    </select>
</label>
<label>Reparatiedatum
    <input type="date" name="repair_date" value="<?= h($invoice['repair_date'] ?? '') ?>">
</label>
<label>Kenteken
    <input type="text" name="license_plate" value="<?= h($invoice['license_plate'] ?? '') ?>">
</label>
<label>Merk
    <input type="text" name="car_model" value="<?= h($invoice['car_model'] ?? '') ?>">
</label>
                    <label>Bedrag (€)
                        <input type="number" name="amount" step="0.01" required>
                    </label>
                    <button type="submit">Factuur aanmaken</button>
                </form>
            <?php endif; ?>

            <div class="advanced-filters card" style="margin-bottom:2em; padding:1em 1.5em;">
        <div style="display:flex; gap:1.5em; flex-wrap:wrap;">
            <div style="display:flex; flex-direction:column; min-width:160px;">
                <label for="advSearch" style="margin-bottom:0.2em;">Zoeken</label>
                <input id="advSearch" type="text" placeholder="..." autocomplete="off">
            </div>
            <div style="display:flex; flex-direction:column; min-width:120px;">
                <label for="advCol" style="margin-bottom:0.2em;">Kolom</label>
   <select id="advCol">
    <option value="all">Alle</option>
    <option value="0">Factuurnr</option>
    <option value="1">Klant</option>
    <option value="2">Project</option>
    <option value="3">Bedrag</option>
</select>
            </div>
            <div style="display:flex; flex-direction:column; min-width:120px;">
                <label for="advStatus" style="margin-bottom:0.2em;">Status</label>
                <select id="advStatus">
                    <option value="">Alle</option>
                    <option value="unpaid">Open</option>
                    <option value="paid">Betaald</option>
                </select>
            </div>
            <div style="display:flex; flex-direction:column; min-width:120px;">
                <label for="advDateFrom" style="margin-bottom:0.2em;">Datum vanaf</label>
                <input id="advDateFrom" type="date">
            </div>
            <div style="display:flex; flex-direction:column; min-width:120px;">
                <label for="advDateTo" style="margin-bottom:0.2em;">Datum t/m</label>
                <input id="advDateTo" type="date">
            </div>
        </div>
    </div>

<table id="invoicesTable">
                <tr>
    <th><span class="sortable" data-col="0">Factuurnr</span></th>
    <th><span class="sortable" data-col="1">Klant</span></th>
    <th><span class="sortable" data-col="2">Project</span></th>
    <th><span class="sortable" data-col="3">Bedrag</span></th>
    <th><span class="sortable" data-col="4">Datum</span></th>
    <th><span class="sortable" data-col="5">Status</span></th>
    <th></th><th></th>
<th colspan="1" style="text-align:right;">
  <label class="sort-switch">
    <input type="checkbox" class="sort-order-toggle" checked>
    <span class="slider"></span>
    <span class="switch-label switch-label-left">Nieuwst</span>
    <span class="switch-label switch-label-right">Oudst</span>
  </label>
</th>
</tr>
                <?php foreach ($invoices as $inv): ?>
<tr>
    <td><?= h($inv['number'] ?? $inv['id']) ?></td>
    <td><?= h($clientsById[$inv['client_id']]['name'] ?? 'Onbekend') ?></td>
    <td>
        <?php
            if (!empty($inv['project_id']) && isset($projectsById[$inv['project_id']])) {
                echo h($projectsById[$inv['project_id']]['name']);
            } else {
                echo '-';
            }
        ?>
    </td>
    <td>€<?= number_format($inv['amount'], 2, ',', '.') ?></td>
    <td><?= h($inv['date']) ?></td>
    <td>
        <?php if (!empty($inv['paid'])): ?>
            <span style="color:#22c55e;font-weight:600;">Betaald</span>
        <?php else: ?>
            <span style="color:#ef4444;font-weight:600;">Open</span>
        <?php endif; ?>
    </td>
    <td>
    <a href="generate_invoice.php?id=<?= $inv['id'] ?>" target="_blank" style="color:green;">Download PDF</a>
</td>
    <td><a href="?page=invoices&action=edit&id=<?= $inv['id'] ?>" style="color:blue;">Bewerken</a></td>
    <td><a href="#" class="delete-link" data-href="?page=invoices&action=delete&id=<?= $inv['id'] ?>" style="color:red;">Verwijderen</a></td>
</tr>
<?php endforeach; ?>
            </table>
            <?php elseif ($page === 'hours'): ?>
    <h1>Urenregistratie</h1>
    <?php if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit' && is_numeric($_GET['id'])):
        $editHour = $pdo->prepare("SELECT * FROM hours WHERE id = ?");
        $editHour->execute([$_GET['id']]);
        $hour = $editHour->fetch();
    ?>
        <form class="card" method="post" action="">
            <h3>Uur bewerken</h3>
            <input type="hidden" name="edit_id" value="<?= $hour['id'] ?>">
            <label>Project
                <select name="project_id">
                    <option value="">-- Geen project --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $hour['project_id'] ? 'selected' : '' ?>>
                            <?= h($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Klant
                <select name="client_id">
                    <option value="">-- Geen klant --</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $hour['client_id'] ? 'selected' : '' ?>>
                            <?= h($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Datum
                <input type="date" name="date" value="<?= h($hour['date']) ?>" required>
            </label>
            <label>Uren
                <input type="number" name="hours" step="0.25" value="<?= number_format((float)$hour['hours'], 2, '.', '') ?>" required>
            </label>
            <button type="submit">Opslaan</button>
            <a href="?page=hours" style="margin-left:1em;">Annuleren</a>
        </form>
    <?php else: ?>
        <form class="card" method="post" action="">
            <h3>Uur toevoegen</h3>
            <label>Project
                <select name="project_id">
                    <option value="">-- Geen project --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= h($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Klant
                <select name="client_id">
                    <option value="">-- Geen klant --</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Datum
                <input type="date" name="date" required>
            </label>
            <label>Uren
                <input type="number" name="hours" step="0.25" required>
            </label>
            <button type="submit">Toevoegen</button>
        </form>
    <?php endif; ?>
<div class="advanced-filters card" style="margin-bottom:2em; padding:1em 1.5em;">
    <div style="display:flex; gap:1.5em; flex-wrap:wrap;">
        <div style="display:flex; flex-direction:column; min-width:160px;">
            <label for="advSearch" style="margin-bottom:0.2em;">Zoeken</label>
            <input id="advSearch" type="text" placeholder="..." autocomplete="off">
        </div>
        <div style="display:flex; flex-direction:column; min-width:120px;">
            <label for="advCol" style="margin-bottom:0.2em;">Kolom</label>
            <select id="advCol">
                <option value="all">Alle</option>
                <option value="0">Project</option>
                <option value="1">Klant</option>
                <option value="2">Uren</option>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; min-width:120px;">
            <label for="advDateFrom" style="margin-bottom:0.2em;">Datum vanaf</label>
            <input id="advDateFrom" type="date">
        </div>
        <div style="display:flex; flex-direction:column; min-width:120px;">
            <label for="advDateTo" style="margin-bottom:0.2em;">Datum t/m</label>
            <input id="advDateTo" type="date">
        </div>
    </div>
</div>
<table id="hoursTable">
<tr>
    <th><span class="sortable" data-col="0">Project</span></th>
    <th><span class="sortable" data-col="1">Klant</span></th>
    <th><span class="sortable" data-col="2">Datum</span></th>
    <th><span class="sortable" data-col="3">Uren</span></th>
    <th></th>
    <th colspan="1" style="text-align:right;">
      <label class="sort-switch">
        <input type="checkbox" class="sort-order-toggle" checked>
        <span class="slider"></span>
        <span class="switch-label switch-label-left">Nieuwst</span>
        <span class="switch-label switch-label-right">Oudst</span>
      </label>
    </th>
</tr>
<?php foreach ($hours as $h): ?>
    <tr>
        <td><?= h($projectsById[$h['project_id']]['name'] ?? 'Onbekend') ?></td>
        <td>
            <?php
                // Show client from project if project is set, otherwise from client_id
                if (!empty($h['project_id']) && isset($projectsById[$h['project_id']])) {
                    $clientId = $projectsById[$h['project_id']]['client_id'] ?? null;
                    echo h($clientsById[$clientId]['name'] ?? 'Onbekend');
                } elseif (!empty($h['client_id'])) {
                    echo h($clientsById[$h['client_id']]['name'] ?? 'Onbekend');
                } else {
                    echo 'Onbekend';
                }
            ?>
        </td>
        <td><?= h($h['date']) ?></td>
        <td><?= h($h['hours']) ?></td>
        <td><a href="?page=hours&action=edit&id=<?= $h['id'] ?>">Bewerken</a></td>
        <td>
            <a href="#" class="delete-link" data-href="?page=hours&action=delete&id=<?= $h['id'] ?>" style="color:red;">Verwijderen</a>
        </td>
    </tr>
<?php endforeach; ?>
</table>
        <?php elseif ($page === 'clients'): ?>
            <h1>Klanten</h1>
            <?php if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit' && is_numeric($_GET['id'])):
                $editClient = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
                $editClient->execute([$_GET['id']]);
                $client = $editClient->fetch();
            ?>
                <form class="card" method="post" action="">
                    <h3>Klant bewerken</h3>
                    <input type="hidden" name="edit_id" value="<?= $client['id'] ?>">
                    <label>Naam
                        <input type="text" name="name" value="<?= h($client['name']) ?>" required>
                    </label>
                    <button type="submit">Opslaan</button>
                    <a href="?page=clients" style="margin-left:1em;">Annuleren</a>
                </form>
            <?php else: ?>
                <form class="card" method="post" action="">
                    <h3>Nieuwe klant</h3>
                    <label>Naam
                        <input type="text" name="name" required>
                    </label>
                    <button type="submit">Toevoegen</button>
                </form>
            <?php endif; ?>
            <div class="advanced-filters card" style="margin-bottom:2em; padding:1em 1.5em;">
        <div style="display:flex; gap:1.5em; flex-wrap:wrap;">
            <div style="display:flex; flex-direction:column; min-width:160px;">
                <label for="advSearch" style="margin-bottom:0.2em;">Zoeken</label>
                <input id="advSearch" type="text" placeholder="..." autocomplete="off">
            </div>
            <div style="display:flex; flex-direction:column; min-width:120px;">
                <label for="advCol" style="margin-bottom:0.2em;">Kolom</label>
                <select id="advCol">
                    <option value="all">Alle</option>
                    <option value="0">Naam</option>
                </select>
            </div>
        </div>
    </div>
<table id="clientsTable">
             <tr>
    <th><span class="sortable" data-col="0">Naam</span></th>
    <th></th><th colspan="1" style="text-align:right;">
  <label class="sort-switch">
    <input type="checkbox" class="sort-order-toggle" checked>
    <span class="slider"></span>
    <span class="switch-label switch-label-left">Nieuwst</span>
    <span class="switch-label switch-label-right">Oudst</span>
  </label>
</th>
</tr>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td><a href="?page=clients&action=edit&id=<?= $c['id'] ?>">Bewerken</a></td>
                        <td>  
                        <a href="#" class="delete-link" data-href="?page=clients&action=delete&id=<?= $c['id'] ?>" style="color:red;">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif ($page === 'projects'): ?>
            <h1>Projecten</h1>
            <?php if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit' && is_numeric($_GET['id'])):
                $editProject = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
                $editProject->execute([$_GET['id']]);
                $project = $editProject->fetch();
            ?>
                <form class="card" method="post" action="">
                    <h3>Project bewerken</h3>
                    <input type="hidden" name="edit_id" value="<?= $project['id'] ?>">
                    <label>Klant
                        <select name="client_id">
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $project['client_id'] ? 'selected' : '' ?>>
                                    <?= h($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Projectnaam
                        <input type="text" name="name" value="<?= h($project['name']) ?>" required>
                    </label>
                    <label>Status
    <select name="status" required>
        <option value="ongoing" <?= (isset($project['status']) && $project['status'] === 'ongoing') ? 'selected' : '' ?>>Lopend</option>
        <option value="finished" <?= (isset($project['status']) && $project['status'] === 'finished') ? 'selected' : '' ?>>Afgerond</option>
    </select>
</label>
                    <button type="submit">Opslaan</button>
                    <a href="?page=projects" style="margin-left:1em;">Annuleren</a>
                </form>
            <?php else: ?>
                <form class="card" method="post" action="">
                    <h3>Nieuw project</h3>
                    <label>Klant
                        <select name="client_id">
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Projectnaam
                        <input type="text" name="name" required>
                    </label>
                    <label>Status
    <select name="status" required>
        <option value="ongoing">Lopend</option>
        <option value="finished">Afgerond</option>
    </select>
</label>
                    <button type="submit">Toevoegen</button>
                </form>
            <?php endif; ?>
<div class="advanced-filters card" style="margin-bottom:2em; padding:1em 1.5em;">
    <div style="display:flex; gap:1.5em; flex-wrap:wrap;">
        <div style="display:flex; flex-direction:column; min-width:160px;">
            <label for="advSearch" style="margin-bottom:0.2em;">Zoeken</label>
            <input id="advSearch" type="text" placeholder="..." autocomplete="off">
        </div>
        <div style="display:flex; flex-direction:column; min-width:120px;">
            <label for="advCol" style="margin-bottom:0.2em;">Kolom</label>
            <select id="advCol">
                <option value="all">Alle</option>
                <option value="0">Project</option>
                <option value="1">Klant</option>
                <option value="2">Status</option>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; min-width:120px;">
            <label for="advStatus" style="margin-bottom:0.2em;">Status</label>
            <select id="advStatus">
                <option value="">Alle</option>
                <option value="ongoing">Lopend</option>
                <option value="finished">Afgerond</option>
            </select>
        </div>
    </div>
</div>
<table id="projectsTable">
                <tr>
    <th><span class="sortable" data-col="0">Project</span></th>
    <th><span class="sortable" data-col="1">Klant</span></th>
    <th><span class="sortable" data-col="2">Status</span></th>
    <th></th><th colspan="1" style="text-align:right;">
  <label class="sort-switch">
    <input type="checkbox" class="sort-order-toggle" checked>
    <span class="slider"></span>
    <span class="switch-label switch-label-left">Nieuwst</span>
    <span class="switch-label switch-label-right">Oudst</span>
  </label>
</th>
</tr>
                <?php foreach ($projects as $p): ?>
                    <tr>
                        <td><?= h($p['name']) ?></td>
                        <td><?= h($clientsById[$p['client_id']]['name'] ?? 'Onbekend') ?></td>
                        <td>
                            <?php
                                if (($p['status'] ?? 'ongoing') === 'finished') {
                                    echo '<span style="color:#22c55e;font-weight:600;">Afgerond</span>';
                                } else {
                                    echo '<span style="color:#2563eb;font-weight:600;">Lopend</span>';
                                }
                            ?>
                        </td>
                        <td><a href="?page=projects&action=edit&id=<?= $p['id'] ?>">Bewerken</a></td>
                        <td>
                            <a href="#" class="delete-link" data-href="?page=projects&action=delete&id=<?= $p['id'] ?>" style="color:red;">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <div style="margin-top:2em;">
            <small>
                Slimme koppelingen: <a href="https://calendar.google.com/" target="_blank">Google Agenda</a> | <a href="https://drive.google.com/" target="_blank">Cloudopslag</a>
            </small>
        </div>
    </div>
<!-- Interactive Confirm Modal -->
<div id="confirmModal" style="display:none;position:fixed;z-index:99999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
  <div class="modal-content" style="padding:2em 2em 1.5em 2em;border-radius:12px;box-shadow:0 8px 32px #0002;max-width:90vw;min-width:260px;text-align:center;">
    <!-- Bigger Danger icon -->
    <div style="margin-bottom:1em;">
      <svg width="72" height="72" viewBox="0 0 72 72" fill="none">
        <circle cx="36" cy="36" r="34" fill="#fff" stroke="#ef4444" stroke-width="4"/>
        <path d="M36 20v22" stroke="#ef4444" stroke-width="6" stroke-linecap="round"/>
        <circle cx="36" cy="52" r="4" fill="#ef4444"/>
      </svg>
    </div>
    <div style="font-size:1.1em;margin-bottom:1.5em;">Weet u zeker dat u dit wilt verwijderen?</div>
    <div style="color:#ef4444;font-size:0.98em;margin-bottom:1.5em;">
      <strong>Let op:</strong> Dit kan <u>niet</u> ongedaan worden gemaakt.
    </div>
    <button id="confirmYes" style="background:#ef4444;color:#fff;padding:0.7em 2em;border:none;border-radius:6px;font-weight:600;cursor:pointer;margin-right:1em;">Ja, verwijderen</button>
    <button id="confirmNo" style="background:#e5e7eb;color:#222;padding:0.7em 2em;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Annuleren</button>
  </div>
</div>
<div id="saveConfirmModal" style="display:none;position:fixed;z-index:99999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
  <div class="modal-content" style="padding:2em 2em 1.5em 2em;border-radius:12px;box-shadow:0 8px 32px #0002;max-width:90vw;min-width:260px;text-align:center;">
    <div style="font-size:1.1em;margin-bottom:1.5em;">Weet u zeker dat u deze wijziging wilt opslaan?</div>
    <button id="saveConfirmYes" style="background:#2563eb;color:#fff;padding:0.7em 2em;border:none;border-radius:6px;font-weight:600;cursor:pointer;margin-right:1em;">Ja, opslaan</button>
    <button id="saveConfirmNo" style="background:#e5e7eb;color:#222;padding:0.7em 2em;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Annuleren</button>
  </div>
</div>
<button id="darkModeToggle" style="position:absolute;top:18px;right:18px;padding:0.5em 1.2em;border-radius:6px;border:none;background:#222;color:#fff;cursor:pointer;font-weight:600;">🌙 Donker</button>
<script src="dashboard.js"></script>
</body>
</html>
