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
        }
    }
    // Projects
    if ($page === 'projects' && !empty($_POST['name']) && !empty($_POST['client_id'])) {
        if (!empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE projects SET name = :name, client_id = :client_id WHERE id = :id");
            $stmt->execute([
                'name' => $_POST['name'],
                'client_id' => $_POST['client_id'],
                'id' => $_POST['edit_id']
            ]);
            $_SESSION['success_message'] = "Project succesvol bijgewerkt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (name, client_id) VALUES (:name, :client_id)");
            $stmt->execute(['name' => $_POST['name'], 'client_id' => $_POST['client_id']]);
        }
    }
    // Invoices
    if ($page === 'invoices' && !empty($_POST['client_id']) && !empty($_POST['amount'])) {
        if (!empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE invoices SET client_id = :client_id, amount = :amount WHERE id = :id");
            $stmt->execute([
                'client_id' => $_POST['client_id'],
                'amount' => $_POST['amount'],
                'id' => $_POST['edit_id']
            ]);
            $_SESSION['success_message'] = "Factuur succesvol bijgewerkt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO invoices (client_id, amount, date) VALUES (:client_id, :amount, CURDATE())");
            $stmt->execute(['client_id' => $_POST['client_id'], 'amount' => $_POST['amount']]);
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
        header("Location: ?page=clients");
        exit;
    }
    if ($page === 'projects') {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: ?page=projects");
        exit;
    }
    if ($page === 'invoices') {
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: ?page=invoices");
        exit;
    }
    if ($page === 'hours') {
        $stmt = $pdo->prepare("DELETE FROM hours WHERE id = ?");
        $stmt->execute([$_GET['id']]);
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
            width: 240px;
            background: var(--sidebar-bg);
            color: #fff;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 12px #0001;
            display: flex;
            flex-direction: column;
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }
        .sidebar h2 {
            text-align: center;
            padding: 2em 0 1em 0;
            font-size: 2em;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .sidebar a {
            color: #fff;
            display: block;
            padding: 1.1em 2em;
            text-decoration: none;
            border-left: 4px solid transparent;
            font-size: 1.08em;
            position: relative;
            overflow: hidden;
            transition: background 0.3s, border-color 0.3s, box-shadow 0.3s, color 0.3s;
            box-shadow: none;
        }
        .sidebar a:hover, .sidebar a:focus {
            background: var(--primary);
            color: #fff;
            border-left: 4px solid var(--primary-dark);
            box-shadow: 0 0 16px 2px var(--primary), 0 2px 16px 0 var(--primary-dark);
        }
        .sidebar a::after {
            display: none; /* Remove underline animation */
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
            transition: background 0.2s;
            position: relative;
            overflow: hidden;
        }
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
    </div>
    <div class="main">
        <?php if ($page === 'dashboard'): ?>
            <h1>Welkom terug!</h1>
            <div class="flex">
                <div class="card">
                    <h3>Facturen</h3>
                    <p><strong><?= count($invoices) ?></strong> openstaande facturen</p>
                    <a href="?page=invoices">Bekijk facturen</a>
                </div>
                <div class="card">
                    <h3>Uren</h3>
                    <p><strong><?= array_sum(array_column($hours, 'hours')) ?></strong> uren geregistreerd</p>
                    <a href="?page=hours">Bekijk uren</a>
                </div>
                <div class="card">
                    <h3>Klanten</h3>
                    <p><strong><?= count($clients) ?></strong> klanten</p>
                    <a href="?page=clients">Bekijk klanten</a>
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
                        <select name="client_id">
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $invoice['client_id'] ? 'selected' : '' ?>>
                                    <?= h($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Bedrag (€)
                        <input type="number" name="amount" step="0.01" value="<?= h($invoice['amount']) ?>" required>
                    </label>
                    <button type="submit">Opslaan</button>
                    <a href="?page=invoices" style="margin-left:1em;">Annuleren</a>
                </form>
            <?php else: ?>
                <form class="card" method="post" action="">
                    <h3>Nieuwe factuur</h3>
                    <label>Klant
                        <select name="client_id">
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Bedrag (€)
                        <input type="number" name="amount" step="0.01" required>
                    </label>
                    <button type="submit">Factuur aanmaken</button>
                </form>
            <?php endif; ?>
            <table>
                <tr><th>Factuurnr</th><th>Klant</th><th>Bedrag</th><th></th><th></th><th></th></tr>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= $inv['id'] ?></td>
                        <td><?= h($clientsById[$inv['client_id']]['name'] ?? 'Onbekend') ?></td>
                        <td>€<?= number_format($inv['amount'],2,',','.') ?></td>
                        <td><?= h($inv['date']) ?></td>
                        <td><a href="?page=invoices&action=edit&id=<?= $inv['id'] ?>">Bewerken</a></td>
                        <td>
                            <a href="?page=invoices&action=delete&id=<?= $inv['id'] ?>"
                               onclick="return confirm('Weet je zeker dat je deze factuur wilt verwijderen?');"
                               style="color:red;">Verwijderen</a>
                        </td>
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
            <table>
                <tr><th>Project</th><th>Datum</th><th>Uren</th><th></th><th></th></tr>
                <?php foreach ($hours as $h): ?>
                    <tr>
                        <td><?= h($projects[$h['project_id']-1]['name']) ?></td>
                        <td><?= h($h['date']) ?></td>
                        <td><?= h($h['hours']) ?></td>
                        <td><a href="?page=hours&action=edit&id=<?= $h['id'] ?>">Bewerken</a></td>
                        <td>
                            <a href="?page=hours&action=delete&id=<?= $h['id'] ?>"
                               onclick="return confirm('Weet je zeker dat je deze urenregel wilt verwijderen?');"
                               style="color:red;">Verwijderen</a>
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
            <table>
                <tr><th>Naam</th><th></th><th></th></tr>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td><a href="?page=clients&action=edit&id=<?= $c['id'] ?>">Bewerken</a></td>
                        <td>
                            <a href="?page=clients&action=delete&id=<?= $c['id'] ?>"
                               onclick="return confirm('Weet je zeker dat je deze klant wilt verwijderen?');"
                               style="color:red;">Verwijderen</a>
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
                    <button type="submit">Toevoegen</button>
                </form>
            <?php endif; ?>
            <table>
                <tr><th>Project</th><th>Klant</th><th></th><th></th></tr>
                <?php foreach ($projects as $p): ?>
                    <tr>
                        <td><?= h($p['name']) ?></td>
                        <td><?= h($clientsById[$p['client_id']]['name'] ?? 'Onbekend') ?></td>
                        <td><a href="?page=projects&action=edit&id=<?= $p['id'] ?>">Bewerken</a></td>
                        <td>
                            <a href="?page=projects&action=delete&id=<?= $p['id'] ?>"
                               onclick="return confirm('Weet je zeker dat je dit project wilt verwijderen?');"
                               style="color:red;">Verwijderen</a>
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
</body>
</html>