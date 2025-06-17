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
        if (!empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE invoices SET client_id = :client_id, project_id = :project_id, amount = :amount, paid = :paid WHERE id = :id");
            $stmt->execute([
                'client_id' => $_POST['client_id'],
                'project_id' => $_POST['project_id'],
                'amount' => $_POST['amount'],
                'paid' => $paid,
                'id' => $_POST['edit_id']
            ]);
            $_SESSION['success_message'] = "Factuur succesvol bijgewerkt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO invoices (client_id, project_id, amount, date, paid) VALUES (:client_id, :project_id, :amount, CURDATE(), :paid)");
            $stmt->execute([
                'client_id' => $_POST['client_id'],
                'project_id' => $_POST['project_id'],
                'amount' => $_POST['amount'],
                'paid' => $paid
            ]);
            $_SESSION['success_message'] = "Factuur succesvol aangemaakt!";
        }
    }
    // Hours
    if ($page === 'hours' && !empty($_POST['project_id']) && !empty($_POST['date']) && !empty($_POST['hours'])) {
        if (!empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE hours SET project_id = :project_id, date = :date, hours = :hours WHERE id = :id");
            $stmt->execute([
                'project_id' => $_POST['project_id'],
                'date' => $_POST['date'],
                'hours' => $_POST['hours'],
                'id' => $_POST['edit_id']
            ]);
            $_SESSION['success_message'] = "Uren succesvol bijgewerkt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO hours (project_id, date, hours) VALUES (:project_id, :date, :hours)");
            $stmt->execute([
                'project_id' => $_POST['project_id'],
                'date' => $_POST['date'],
                'hours' => $_POST['hours']
            ]);
            $_SESSION['success_message'] = "Uur succesvol toegevoegd!";
        }
    }
    header("Location: ?page=$page");
    exit;
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
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #f6f8fa;
            --sidebar-bg: #18181b;
            --sidebar-active: #2563eb22;
            --card-bg: #fff;
            --border-radius: 12px;
            --shadow: 0 4px 24px #0002;
        }
        body {
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            margin: 0;
            background: var(--bg);
            color: #222;
        }
        .sidebar {
            width: 220px;
            background: #18181b;
            color: #fff;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 16px #0001;
            display: flex;
            flex-direction: column;
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
            overflow: hidden;
        }
        .sidebar h2 {
            text-align: center;
            padding: 2em 0 1em 0;
            font-size: 1.5em;
            letter-spacing: 1px;
            font-weight: 700;
            color: #fff;
            background: none;
            -webkit-background-clip: unset;
            -webkit-text-fill-color: unset;
            background-clip: unset;
        }
        .sidebar a {
            color: #e5e7eb;
            display: block;
            padding: 1.1em 2em;
            text-decoration: none;
            border-left: 4px solid transparent;
            font-size: 1.08em;
            position: relative;
            margin-bottom: 0.2em;
            border-radius: 0 16px 16px 0;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }
        .sidebar a.active, .sidebar a:hover, .sidebar a:focus {
            background: #23232a;
            color: #fff;
            border-left: 4px solid #2563eb;
        }
        .main {
            margin-left: 260px;
            padding: 3em 2em 2em 2em;
            min-height: 100vh;
        }
        .card {
            background: var(--card-bg);
            padding: 2em 1.5em;
            margin-bottom: 2em;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid #e5e7eb;
            animation: fadeInUp 0.6s cubic-bezier(.23,1.01,.32,1) both;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .card:hover {
            box-shadow: 0 12px 40px #2563eb33;
            transform: translateY(-6px) scale(1.02);
        }
        .flex {
            display: flex;
            gap: 2em;
            flex-wrap: wrap;
        }
        .flex > * {
            flex: 1 1 220px;
            min-width: 220px;
        }
        h1, h3 {
            margin-top: 0;
        }
        label {
            display: block;
            margin-top: 1.2em;
            font-weight: 500;
            transition: color 0.3s;
        }
        input, select {
            width: 100%;
            padding: 0.7em 1em;
            margin-top: 0.5em;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1em;
            background: #f9fafb;
            transition: border 0.2s, box-shadow 0.2s, background 0.3s;
        }
        input:focus, select:focus {
            background: #e8f0fe;
            box-shadow: 0 0 0 2px #2563eb55;
            outline: none;
        }
        button {
            margin-top: 1.5em;
            padding: 0.9em 2em;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            box-shadow: 0 2px 8px #2563eb22;
            transition: background 0.2s, box-shadow 0.2s, transform 0.18s cubic-bezier(.23,1.01,.32,1);
            position: relative;
            overflow: hidden;
            outline: none;
        }
        button:hover, button:focus {
            background: var(--primary-dark);
            box-shadow: 0 6px 24px #2563eb33;
            transform: scale(1.04) translateY(-2px);
        }
        button:active {
            transform: scale(0.98);
        }
        /* Ripple effect on click */
        button:active::after {
            content: "";
            position: absolute;
            left: 50%; top: 50%;
            width: 200%; height: 200%;
            background: rgba(37,99,235,0.15);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            animation: ripple 0.5s linear;
            pointer-events: none;
        }
        @keyframes ripple {
            to { transform: translate(-50%, -50%) scale(1); opacity: 0; }
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5em;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px #0001;
        }
        th, td {
            padding: 1em;
            border-bottom: 1px solid #f1f1f1;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #222;
        }
        tr:last-child td {
            border-bottom: none;
        }
        small {
            color: #888;
        }
        .card form {
        max-width: 500px;
        margin: 0 auto;
        }
        .card input,
        .card select {
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            margin-left: 0;
            margin-right: 0;
            display: block;
        }
        @media (max-width: 900px) {
            .flex {
                flex-direction: column;
            }
            .main {
                margin-left: 0;
                padding: 1em;
            }
            .sidebar {
                position: static;
                width: 100%;
                border-radius: 0;
                flex-direction: row;
                height: auto;
                box-shadow: none;
            }
            .sidebar h2 {
                display: none;
            }
            .sidebar a {
                padding: 1em 0.7em;
                font-size: 1em;
                border-left: none;
                border-bottom: 2px solid transparent;
            }
            .sidebar a:hover, .sidebar a:focus {
                border-bottom: 2px solid var(--primary);
                background: var(--sidebar-active);
            }
        }
        tr {
    animation: fadeInRow 0.5s cubic-bezier(.23,1.01,.32,1) both;
    transition: background 0.2s, box-shadow 0.2s;
}
tr:hover {
    background: #e8f0fe;
    box-shadow: 0 2px 8px #2563eb22;
    z-index: 1;
    position: relative;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes fadeInRow {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.popup-success {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: #22c55e;
    color: #fff;
    padding: 1em 2em;
    border-radius: 8px;
    box-shadow: 0 4px 24px #22c55e33;
    font-size: 1.1em;
    z-index: 9999;
    animation: popupFadeIn 0.5s, popupFadeOut 0.5s 2.5s forwards;
}
@keyframes popupFadeIn {
    from { opacity: 0; transform: translateX(-50%) translateY(-20px);}
    to   { opacity: 1; transform: translateX(-50%) translateY(0);}
}
@keyframes popupFadeOut {
    to { opacity: 0; transform: translateX(-50%) translateY(-20px);}
}
#confirmModal {
    display: none;
    opacity: 0;
    transition: opacity 0.2s;
}
#confirmModal[style*="display: flex"] {
    display: flex !important;
    opacity: 1;
}
.table-searchbar {
    display: flex;
    gap: 0.7em;
    align-items: center;
    margin-bottom: 1.5em;
    max-width: 500px;
}
.table-searchbar input[type="text"] {
    flex: 2 1 220px;
    padding: 0.7em 1em;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1em;
    background: #f9fafb;
    transition: box-shadow 0.2s, border 0.2s;
}
.table-searchbar input[type="text"]:focus {
    box-shadow: 0 0 0 2px #2563eb55;
    border-color: var(--primary);
    outline: none;
}
.table-searchbar select {
    flex: 1 1 120px;
    padding: 0.7em 1em;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1em;
    background: #f9fafb;
    transition: box-shadow 0.2s, border 0.2s;
}
.table-searchbar select:focus {
    box-shadow: 0 0 0 2px #2563eb55;
    border-color: var(--primary);
    outline: none;
}
.checkbox-inline {
    display: flex;
    align-items: center;
    gap: 0.6em;
    margin-top: 1.2em;
    font-weight: 500;
}
.checkbox-inline input[type="checkbox"] {
    width: 1.1em;
    height: 1.1em;
    accent-color: var(--primary);
    margin: 0;
}
.advanced-filters {
    background: var(--card-bg);
    padding: 1.5em 2em;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 2em;
}
.advanced-filters label {
    margin-bottom: 0.5em;
    font-weight: 500;
    color: #333;
}
.advanced-filters input,
.advanced-filters select {
    padding: 0.8em 1.2em;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1em;
    background: #f9fafb;
    transition: border 0.2s, box-shadow 0.2s;
}
.advanced-filters input:focus,
.advanced-filters select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px #2563eb55;
    outline: none;
}
body.dark-mode {
    background: #18181b !important;
}

body.dark-mode .main {
    background: transparent !important;
    /* No border, no shadow, keep it flat */
}

body.dark-mode .sidebar {
    background: #15161a !important;
    color: #fff !important;
    border-right: 1px solid #23232a !important;
}

body.dark-mode .card {
    background: #23232a !important;
    color: #f3f4f6 !important;
    border-color: #333 !important;
}

body.dark-mode table {
    background: #23232a !important;
    color: #f3f4f6 !important;
}

body.dark-mode th, body.dark-mode td {
    background: #23232a !important;
    color: #f3f4f6 !important;
}

body.dark-mode a {
    color: #60a5fa !important;
}

body.dark-mode input, 
body.dark-mode select, 
body.dark-mode textarea {
    background: #18181b !important;
    color: #f3f4f6 !important;
    border-color: #333 !important;
}

body.dark-mode .popup-success {
    background: #166534 !important;
    color: #fff !important;
}
body.dark-mode .advanced-filters label {
    color: #f3f4f6 !important;
}

body.dark-mode .advanced-filters input,
body.dark-mode .advanced-filters select {
    color: #f3f4f6 !important;
}

/* Placeholder color for dark mode */
body.dark-mode .advanced-filters input::placeholder {
    color: #a1a1aa !important;
    opacity: 1;
}
body.dark-mode h1 {
    color: #f3f4f6 !important;
}
body.dark-mode .modal-content {
    background: #23232a !important;
    color: #f3f4f6 !important;
    border-color: #333 !important;
}
body.dark-mode a.delete-link,
body.dark-mode a.delete-link:visited {
    color: #ef4444 !important;
    font-weight: 500;
    text-decoration: underline !important;
}
body.dark-mode input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
body.dark-mode canvas {
    background: #23232a !important;
}

/* Dashboard stat cards */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 2em;
    margin-bottom: 2em;
}
.stat-card-link {
    display: block;
    border-radius: 18px;
    text-decoration: none;
    height: 100%;
    transition: box-shadow 0.25s, transform 0.18s cubic-bezier(.23,1.01,.32,1);
}
.stat-card {
    min-height: 90px;
    height: 100%;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 24px #0001;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    padding: 1em 1em;
    transition: box-shadow 0.25s, transform 0.18s cubic-bezier(.23,1.01,.32,1), background 0.25s;
    position: relative;
    overflow: hidden;
    will-change: transform, box-shadow;
}
.stat-card-value {
    font-size: 2.2em;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.2em;
}
.stat-card-label {
    font-size: 1.1em;
    opacity: .85;
    margin-bottom: 0.7em;
}
.stat-card-badges {
    display: flex;
    gap: 0.5em;
    flex-wrap: wrap;
}
.stat-badge {
    border-radius: 6px;
    padding: 2px 10px 2px 8px;
    font-size: .98em;
    font-weight: 600;
    background: #f3f4f6;
    color: #222;
    transition: background 0.2s, color 0.2s;
}
.stat-badge-green {
    background: #22c55e22;
    color: #22c55e;
}
.stat-badge-red {
    background: #ef444422;
    color: #ef4444;
}
.stat-badge-dark {
    background: #18181b22;
    color: #18181b;
}
.stat-card-blue {
    color: #2563eb;
}
.stat-card-green {
    color: #22c55e;
}
.stat-card-dark {
    color: #18181b;
}
.stat-card-white {
    color: #222;
}
.stat-card-link .stat-card {
    cursor: pointer;
    animation: cardFadeIn 0.7s cubic-bezier(.23,1.01,.32,1);
}
@keyframes cardFadeIn {
    from { opacity: 0; transform: translateY(30px) scale(0.97);}
    to   { opacity: 1; transform: none;}
}
.stat-card-link:hover .stat-card,
.stat-card-link:focus .stat-card {
    box-shadow: 0 12px 40px #2563eb33, 0 2px 8px #22c55e22;
    transform: translateY(-6px) scale(1.04) rotate(-0.5deg);
    background: #f3f6ff;
}
.stat-card-link:active .stat-card {
    transform: scale(0.98);
    box-shadow: 0 2px 8px #2563eb22;
}
    </style>
</head>
<body>
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
            </div>
        </a>
        <a href="?page=projects" class="stat-card-link">
            <div class="stat-card stat-card-green">
                <div class="stat-card-value"><?= count($projects) ?></div>
                <div class="stat-card-label">Projecten</div>
                <div class="stat-card-badges">
                    <span class="stat-badge stat-badge-green"><?= count($ongoingProjects) ?> lopend</span>
                    <span class="stat-badge stat-badge-green"><?= count($finishedProjects) ?> afgerond</span>
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
            <span style="font-size:1.1em;color:#2563eb;font-weight:600;">
                Totaal <?= count($ongoingProjects) ?>
            </span>
        </div>
        <table>
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
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:2em;margin-bottom:2em;">
        <div class="stat-card" style="background:#2563eb;color:#fff;padding:2em 1.5em;border-radius:14px;box-shadow:0 4px 24px #2563eb22;display:flex;flex-direction:column;align-items:flex-start;">
            <div style="font-size:2.2em;font-weight:700;line-height:1;"><?= count($clients) ?></div>
            <div style="font-size:1.1em;opacity:.85;">Klanten</div>
        </div>
        <div class="stat-card" style="background:#22c55e;color:#fff;padding:2em 1.5em;border-radius:14px;box-shadow:0 4px 24px #22c55e22;display:flex;flex-direction:column;align-items:flex-start;">
            <div style="font-size:2.2em;font-weight:700;line-height:1;"><?= count($projects) ?></div>
            <div style="font-size:1.1em;opacity:.85;">Projecten</div>
            <div style="margin-top:.7em;font-size:.98em;">
                <span style="background:#fff2;border-radius:6px;padding:2px 8px 2px 6px;margin-right:6px;">
                    <span style="color:#fff;font-weight:600;"><?= count($ongoingProjects) ?></span> lopend
                </span>
                <span style="background:#fff2;border-radius:6px;padding:2px 8px 2px 6px;">
                    <span style="color:#fff;font-weight:600;"><?= count($finishedProjects) ?></span> afgerond
                </span>
            </div>
        </div>
        <div class="stat-card" style="background:#18181b;color:#fff;padding:2em 1.5em;border-radius:14px;box-shadow:0 4px 24px #0002;display:flex;flex-direction:column;align-items:flex-start;">
            <div style="font-size:2.2em;font-weight:700;line-height:1;"><?= array_sum(array_column($hours, 'hours')) ?></div>
            <div style="font-size:1.1em;opacity:.85;">Uren geregistreerd</div>
            <div style="margin-top:.7em;font-size:.98em;">
                <span style="background:#fff1;border-radius:6px;padding:2px 8px 2px 6px;margin-right:6px;">
                    <span style="color:#fff;font-weight:600;"><?= $hoursThisWeek ?></span> deze week
                </span>
                <span style="background:#fff1;border-radius:6px;padding:2px 8px 2px 6px;">
                    <span style="color:#fff;font-weight:600;"><?= $hoursToday ?></span> vandaag
                </span>
            </div>
        </div>
        <div class="stat-card" style="background:#fff;color:#222;padding:2em 1.5em;border-radius:14px;box-shadow:0 4px 24px #0002;display:flex;flex-direction:column;align-items:flex-start;">
            <div style="font-size:2.2em;font-weight:700;line-height:1;"><?= $totalInvoices ?></div>
            <div style="font-size:1.1em;opacity:.85;">Facturen</div>
            <div style="margin-top:.7em;font-size:.98em;">
                <span style="background:#22c55e22;color:#22c55e;font-weight:600;border-radius:6px;padding:2px 8px 2px 6px;margin-right:6px;"><?= $paidInvoices ?> betaald</span>
                <span style="background:#ef444422;color:#ef4444;font-weight:600;border-radius:6px;padding:2px 8px 2px 6px;"><?= $unpaidInvoices ?> open</span>
            </div>
        </div>
    </div>

    <div class="flex" style="gap:2em;flex-wrap:wrap;">
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Uren per maand</h3>
            <canvas id="hoursPerMonthChart" height="180"></canvas>
        </div>
        <div class="card" style="flex:1;min-width:320px;">
            <h3 style="margin-top:0;">Projectstatus</h3>
            <canvas id="projectStatusChart" height="180"></canvas>
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
                    <option value="2">Bedrag</option>
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

            <table>
                <tr><th>Factuurnr</th><th>Klant</th><th>Project</th><th>Bedrag</th><th>Datum</th><th>Status</th><th></th><th></th></tr>
                <?php foreach ($invoices as $inv): ?>
<tr>
    <td><?= h($inv['id']) ?></td>
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
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $hour['project_id'] ? 'selected' : '' ?>>
                                    <?= h($p['name']) ?>
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
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= h($p['name']) ?></option>
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
            <table>
                <tr><th>Project</th><th>Datum</th><th>Uren</th><th></th><th></th></tr>
                <?php foreach ($hours as $h): ?>
                    <tr>
                        <td><?= h($projectsById[$h['project_id']]['name'] ?? 'Onbekend') ?></td>
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
            <table>
                <tr><th>Naam</th><th></th><th></th></tr>
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
                </select>
            </div>
        </div>
    </div>
            <table>
                <tr><th>Project</th><th>Klant</th><th>Status</th><th></th><th></th></tr>
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

<script>
// Toggle dark mode
document.getElementById('darkModeToggle').onclick = function() {
    document.body.classList.toggle('dark-mode');
    if(document.body.classList.contains('dark-mode')) {
        localStorage.setItem('darkMode', '1');
        this.innerHTML = '☀️ Licht';
        this.style.color = '#f3f4f6';
        this.style.background = '#18181b';
    } else {
        localStorage.removeItem('darkMode');
        this.innerHTML = '🌙 Donker';
        this.style.color = '#f3f4f6';
        this.style.background = '#23232a';
    }
};
// On load, apply dark mode if set
if(localStorage.getItem('darkMode')) {
    document.body.classList.add('dark-mode');
    var btn = document.getElementById('darkModeToggle');
    btn.innerHTML = '☀️ Licht';
    btn.style.color = '#f3f4f6';
    btn.style.background = '#18181b';
}
</script>

<style>
body.dark-mode {
    background: #18181b !important;
}

body.dark-mode .main {
    background: transparent !important;
    /* No border, no shadow, keep it flat */
}

body.dark-mode .sidebar {
    background: #15161a !important;
    color: #fff !important;
    border-right: 1px solid #23232a !important;
}

body.dark-mode .card {
    background: #23232a !important;
    color: #f3f4f6 !important;
    border-color: #333 !important;
}

body.dark-mode table {
    background: #23232a !important;
    color: #f3f4f6 !important;
}

body.dark-mode th, body.dark-mode td {
    background: #23232a !important;
    color: #f3f4f6 !important;
}

body.dark-mode a {
    color: #60a5fa !important;
}

body.dark-mode input, 
body.dark-mode select, 
body.dark-mode textarea {
    background: #18181b !important;
    color: #f3f4f6 !important;
    border-color: #333 !important;
}

body.dark-mode .popup-success {
    background: #166534 !important;
    color: #fff !important;
}
body.dark-mode .advanced-filters label {
    color: #f3f4f6 !important;
}

body.dark-mode .advanced-filters input,
body.dark-mode .advanced-filters select {
    color: #f3f4f6 !important;
}

/* Placeholder color for dark mode */
body.dark-mode .advanced-filters input::placeholder {
    color: #a1a1aa !important;
    opacity: 1;
}
body.dark-mode h1 {
    color: #f3f4f6 !important;
}
body.dark-mode .modal-content {
    background: #23232a !important;
    color: #f3f4f6 !important;
    border-color: #333 !important;
}
body.dark-mode a.delete-link,
body.dark-mode a.delete-link:visited {
    color: #ef4444 !important;
    font-weight: 500;
    text-decoration: underline !important;
}
body.dark-mode input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
body.dark-mode canvas {
    background: #23232a !important;
}
</style>
<script>
document.querySelectorAll('.delete-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var modal = document.getElementById('confirmModal');
        modal.style.display = 'flex';
        modal.dataset.href = this.dataset.href;
    });
});
document.getElementById('confirmYes').onclick = function() {
    var modal = document.getElementById('confirmModal');
    window.location.href = modal.dataset.href;
};
document.getElementById('confirmNo').onclick = function() {
    document.getElementById('confirmModal').style.display = 'none';
};
document.getElementById('confirmNo').onclick = function() {
    document.getElementById('confirmModal').style.display = 'none';
};
// Remove these if you don't use the old filter bar anymore:
// document.getElementById('tableSearch').addEventListener('input', filterTable);
// document.getElementById('tableFilter').addEventListener('change', filterTable);
// document.getElementById('statusFilter').addEventListener('change', filterTable);


// Only add listeners if the element exists
if (document.getElementById('advSearch')) {
    document.getElementById('advSearch').addEventListener('input', filterTable);
}
// document.getElementById('tableFilter').addEventListener('change', filterTable);
// document.getElementById('statusFilter').addEventListener('change', filterTable);


// Only add listeners if the element exists
if (document.getElementById('advSearch')) {
    document.getElementById('advSearch').addEventListener('input', filterTable);
}
if (document.getElementById('advCol')) {
    document.getElementById('advCol').addEventListener('change', filterTable);
}
if (document.getElementById('advStatus')) {
    document.getElementById('advStatus').addEventListener('change', filterTable);
}
if (document.getElementById('advDateFrom')) {
    document.getElementById('advDateFrom').addEventListener('change', filterTable);
}
if (document.getElementById('advDateTo')) {
    document.getElementById('advDateTo').addEventListener('change', filterTable);
}

function filterTable() {
    var search = document.getElementById('advSearch').value.toLowerCase();
    var advCol = document.getElementById('advCol').value;
    var status = document.getElementById('advStatus') ? document.getElementById('advStatus').value : '';
    var dateFrom = document.getElementById('advDateFrom').value;
    var dateTo = document.getElementById('advDateTo').value;
    var table = document.querySelector('.main table');
    if (!table) return;
    var rows = table.querySelectorAll('tbody tr, tr');
    rows.forEach(function(row) {
        if (row.querySelectorAll('th').length) return; // skip header
        var cells = row.querySelectorAll('td');
        var show = true;

        // Search filter with column selection
        if (search) {
            if (advCol === 'all') {
                if (row.textContent.toLowerCase().indexOf(search) === -1) show = false;
            } else {
                var colIdx = parseInt(advCol, 10);
                if (!cells[colIdx] || cells[colIdx].textContent.toLowerCase().indexOf(search) === -1) show = false;
            }
        }
        // Status filter (only for invoices)
        if (document.getElementById('advStatus') && status) {
            var statusText = cells[4] ? cells[4].textContent.toLowerCase() : '';
            if (status === 'paid' && statusText.indexOf('betaald') === -1) show = false;
            if (status === 'unpaid' && statusText.indexOf('open') === -1) show = false;
        }
        // Date filters (try to match date column)
        // For invoices: date is col 3, for hours: col 1, for projects/clients: skip
        var dateCol = 3;
        if (<?php echo json_encode($page); ?> === 'hours') dateCol = 1;
        if (dateFrom && cells[dateCol]) {
            var rowDate = cells[dateCol].textContent.trim();
            if (rowDate && rowDate < dateFrom) show = false;
        }
        if (dateTo && cells[dateCol]) {
            var rowDate = cells[dateCol].textContent.trim();
            if (rowDate && rowDate > dateTo) show = false;
        }
        row.style.display = show ? '' : 'none';
    });
}
</script>
<script>
    var allProjects = <?= json_encode($projects) ?>;
    var selectedProjectId = <?= isset($invoice['project_id']) ? json_encode($invoice['project_id']) : 'null' ?>;


function updateProjectOptions() {
    var clientId = document.getElementById('clientSelect').value;
    var projectSelect = document.getElementById('projectSelect');
    var current = selectedProjectId;
    projectSelect.innerHTML = '<option value="">-- Kies project --</option>';
    allProjects.forEach(function(proj) {
        if (String(proj.client_id) === String(clientId)) {
            var opt = document.createElement('option');
            opt.value = proj.id;
            opt.textContent = proj.name;
            if (current && proj.id == current) {
                opt.selected = true;
            }
            projectSelect.appendChild(opt);
        }
    });
}

if (document.getElementById('clientSelect') && document.getElementById('projectSelect')) {
    document.getElementById('clientSelect').addEventListener('change', function() {
        selectedProjectId = null;
        updateProjectOptions();
    });
    updateProjectOptions();
}
</script>
<script>
document.querySelectorAll('form.card').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        // Only show confirmation if this is an edit form (has edit_id)
        if (form.querySelector('input[name="edit_id"]')) {
            e.preventDefault();
            var modal = document.getElementById('saveConfirmModal');
            modal.style.display = 'flex';

            document.getElementById('saveConfirmYes').onclick = function() {
                modal.style.display = 'none';
                form.submit();
            };
            document.getElementById('saveConfirmNo').onclick = function() {
                modal.style.display = 'none';
            };
        }
        // If not editing, allow normal submit (no modal)
    });
});
</script>
</body>
</html>