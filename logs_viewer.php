<?php
/**
 * logs_viewer.php
 * Admin page to view who is using the application.
 * Protect this page — add your own auth check below.
 */

// ─── BASIC PROTECTION ─────────────────────────────────────────────────────
// Replace with your own auth or include login2.php
$ADMIN_PASS = 'Djerba2026'; // <-- CHANGE THIS
session_start();
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: ?');
  exit;
}
if (isset($_POST['pass'])) {
  if ($_POST['pass'] === $ADMIN_PASS)
    $_SESSION['log_admin'] = true;
  else
    $authError = true;
}
if (empty($_SESSION['log_admin'])) {
  ?><!DOCTYPE html>
  <html lang="fr">

  <head>
    <meta charset="UTF-8">
    <title>Access Logs — Login</title>
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        background: #080c14;
        color: #e2e8f0;
        font-family: 'Courier New', monospace;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
      }

      .box {
        background: #0d1420;
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 12px;
        padding: 2rem;
        width: 320px;
      }

      h2 {
        font-size: 1rem;
        margin-bottom: 1.5rem;
        color: #00d4aa;
      }

      input {
        width: 100%;
        background: #111928;
        border: 1px solid rgba(255, 255, 255, .1);
        color: #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        font-family: inherit;
        margin-bottom: 1rem;
      }

      button {
        width: 100%;
        background: #00d4aa;
        color: #000;
        border: none;
        border-radius: 6px;
        padding: 8px;
        cursor: pointer;
        font-family: inherit;
        font-weight: 600;
      }

      .err {
        color: #ef4444;
        font-size: .8rem;
        margin-bottom: .75rem;
      }
    </style>
  </head>

  <body>
    <div class="box">
      <h2>🔐 Access Logs Admin</h2>
      <?php if (!empty($authError))
        echo "<div class='err'>Mot de passe incorrect</div>"; ?>
      <form method="POST">
        <input type="password" name="pass" placeholder="Mot de passe" autofocus>
        <button type="submit">Connexion</button>
      </form>
    </div>
  </body>

  </html>
  <?php exit;
} ?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Access Logs — Expert Gestion</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Mono:wght@300;400;500&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --bg: #080c14;
      --surface: #0d1420;
      --surface2: #111928;
      --border: rgba(255, 255, 255, .07);
      --border2: rgba(255, 255, 255, .13);
      --accent: #00d4aa;
      --accent2: #3b82f6;
      --accent3: #f59e0b;
      --accent4: #ef4444;
      --text: #e2e8f0;
      --muted: #64748b;
      --muted2: #94a3b8;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Mono', monospace;
      min-height: 100vh;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
      background: radial-gradient(ellipse 70% 40% at 10% 0%, rgba(0, 212, 170, .05) 0%, transparent 60%);
    }

    header {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(8, 12, 20, .9);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
      padding: 0 2rem;
      height: 58px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .logo {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent);
      box-shadow: 0 0 10px var(--accent);
    }

    .hright {
      display: flex;
      gap: .75rem;
      align-items: center;
    }

    .badge {
      background: var(--surface2);
      border: 1px solid var(--border2);
      border-radius: 6px;
      padding: 3px 10px;
      font-size: .7rem;
      color: var(--muted2);
    }

    .btn {
      padding: 5px 14px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-family: 'DM Mono', monospace;
      font-size: .75rem;
      font-weight: 500;
      transition: all .15s;
    }

    .btn-ghost {
      background: none;
      color: var(--muted2);
      border: 1px solid var(--border2);
    }

    .btn-ghost:hover {
      color: var(--text);
      background: var(--surface2);
    }

    .btn-danger {
      background: none;
      color: var(--accent4);
      border: 1px solid rgba(239, 68, 68, .3);
    }

    .btn-danger:hover {
      background: rgba(239, 68, 68, .08);
    }

    main {
      position: relative;
      z-index: 1;
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* ── Summary KPI cards ── */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .kpi {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 1.1rem;
      position: relative;
      overflow: hidden;
    }

    .kpi::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: var(--kpi-c, var(--accent));
    }

    .kpi-v {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1.6rem;
      line-height: 1;
      margin-bottom: .25rem;
    }

    .kpi-l {
      font-size: .68rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    /* ── Client cards ── */
    .clients-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
      gap: 1rem;
    }

    .client-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
    }

    .client-header {
      padding: 1rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      cursor: pointer;
      transition: background .15s;
    }

    .client-header:hover {
      background: var(--surface2);
    }

    .client-name {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .95rem;
    }

    .client-meta {
      display: flex;
      gap: .75rem;
      margin-top: .25rem;
      flex-wrap: wrap;
    }

    .cmeta {
      font-size: .68rem;
      color: var(--muted2);
    }

    .online-badge {
      font-size: .65rem;
      padding: 2px 8px;
      border-radius: 99px;
      border: 1px solid;
      font-weight: 500;
    }

    .online {
      color: var(--accent);
      border-color: rgba(0, 212, 170, .35);
      background: rgba(0, 212, 170, .08);
    }

    .offline {
      color: var(--muted);
      border-color: var(--border2);
      background: var(--surface2);
    }

    .chevron {
      color: var(--muted);
      font-size: .8rem;
      transition: transform .2s;
    }

    .open .chevron {
      transform: rotate(180deg);
    }

    /* ── Log entries table ── */
    .log-body {
      display: none;
      padding: 0;
    }

    .log-body.open {
      display: block;
    }

    .log-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .72rem;
    }

    .log-table th {
      padding: .5rem .875rem;
      text-align: left;
      font-size: .62rem;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--muted);
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
    }

    .log-table td {
      padding: .5rem .875rem;
      border-bottom: 1px solid var(--border);
      vertical-align: top;
    }

    .log-table tr:last-child td {
      border-bottom: none;
    }

    .log-table tr:hover td {
      background: rgba(255, 255, 255, .02);
    }

    .ip {
      color: var(--accent2);
      font-weight: 500;
    }

    .time {
      color: var(--muted2);
    }

    .ua {
      color: var(--muted);
      max-width: 260px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .recent-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 5px;
    }

    /* ── Filter bar ── */
    .filter-bar {
      display: flex;
      gap: .75rem;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
    }

    .finput {
      background: var(--surface2);
      border: 1px solid var(--border2);
      color: var(--text);
      border-radius: 6px;
      padding: 5px 10px;
      font-family: 'DM Mono', monospace;
      font-size: .78rem;
    }

    .finput:focus {
      outline: none;
      border-color: var(--accent);
    }

    .section-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .8rem;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--muted2);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .section-title span {
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background: var(--accent);
    }

    ::-webkit-scrollbar {
      width: 4px;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--border2);
      border-radius: 9px;
    }

    @media(max-width:700px) {
      .kpi-grid {
        grid-template-columns: 1fr 1fr;
      }

      .clients-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <?php
  // ─── LOAD clients.php TO RESOLVE CLIENT NAMES ────────────────────────────
// Structure: $users['KEY'] = ['password', 'idClient/folderName', 'groupe', 'nomCaisse', decimals?]
// Example:   $users['PM']  = ['0000', '3290871828', 'PROXI MARKET', 'Serveur']
  $users = [];
  $clientsFile = __DIR__ . '/clients.php';
  if (file_exists($clientsFile)) {
    include($clientsFile);
  }

  // Build lookup: folderName (index 1) → [groupe (2), nomCaisse (3), key]
  $clientMeta = []; // keyed by $p[1] (the folder / idClient value)
  foreach ($users as $key => $p) {
    $folderId = $p[1] ?? null;
    if ($folderId) {
      $clientMeta[$folderId] = [
        'key' => $key,
        'groupe' => $p[2] ?? '',
        'caisse' => $p[3] ?? '',
      ];
    }
  }

  // ─── READ ALL LOGS FROM DATABASE ──────────────────────────────────────────
  $clients = [];
  $totalLines = 0;
  $onlineCount = 0;
  $allIPs = [];
  $now = time();

  require_once __DIR__ . '/db.php';

  try {
    // Fetch logs from DB
    $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at ASC");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $totalLines++;
      $clientId = $row['client_id'];
      $allIPs[] = $row['ip'];

      if (!isset($clients[$clientId])) {
        $meta = $clientMeta[$clientId] ?? null;
        if (!$meta) {
          // Fallback to database "clients" table
          $stmtDb = $pdo->prepare("SELECT nom,caisse_id,id_client FROM clients WHERE caisse_id = ? LIMIT 1");
          $stmtDb->execute([$clientId]);
          $dbClient = $stmtDb->fetch(PDO::FETCH_ASSOC);
          if ($dbClient) {
            $meta = [
              'groupe' => $dbClient['nom'],
              'caisse' => $dbClient['caisse_id'],
              'key' => $dbClient['id_client']
            ];
          }
        }
        $clients[$clientId] = [
          'id' => $clientId,
          'groupe' => $meta ? $meta['groupe'] : '',
          'caisse' => $meta ? $meta['caisse'] : '',
          'key' => $meta ? $meta['key'] : '',
          'entries' => [],
          'count' => 0,
          'last' => null,
          'lastTime' => 0,
          'diffMin' => 0,
          'online' => false,
          'ips' => [],
        ];
      }

      // Parse GET parameters from URI for backwards compatibility with the viewer
      $parsedUri = parse_url($row['uri']);
      parse_str($parsedUri['query'] ?? '', $queryParams);

      $entry = [
        'datetime' => $row['created_at'],
        'ip' => $row['ip'],
        'client' => $row['client_id'],
        'dateD' => $queryParams['dateD'] ?? '-',
        'dateF' => $queryParams['dateF'] ?? '-',
        'ticket' => $queryParams['ticket'] ?? '-',
        'session' => '-',
        'method' => $row['method'],
        'uri' => $row['uri'],
        'ua' => $row['user_agent'],
        'referer' => '-',
      ];

      // Unshift so latest entries appear first
      array_unshift($clients[$clientId]['entries'], $entry);
      $clients[$clientId]['count']++;
    }

    // Process post-aggregation metrics
    foreach ($clients as &$c) {
      $lastEntry = $c['entries'][0] ?? null;
      $lastTime = $lastEntry ? strtotime($lastEntry['datetime']) : 0;
      $diffMin = ($now - $lastTime) / 60;
      $isOnline = $diffMin <= 30; // online = active in last 30 min
  
      if ($isOnline)
        $onlineCount++;

      $c['last'] = $lastEntry;
      $c['lastTime'] = $lastTime;
      $c['diffMin'] = $diffMin;
      $c['online'] = $isOnline;
      $c['ips'] = array_unique(array_column($c['entries'], 'ip'));
    }
    unset($c);

  } catch (\PDOException $e) {
    echo "<div style='color:red;text-align:center;padding:2rem'>Erreur DB : " . $e->getMessage() . "</div>";
  }

  // Sort by last activity
  uasort($clients, fn($a, $b) => $b['lastTime'] - $a['lastTime']);

  $uniqueIPs = count(array_unique($allIPs));
  $totalClients = count($clients);

  // ─── FILTER ───────────────────────────────────────────────────────────────
  $filterClient = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
  $filterOnline = isset($_GET['online']);
  if ($filterClient || $filterOnline) {
    $clients = array_filter($clients, function ($c) use ($filterClient, $filterOnline) {
      if ($filterOnline && !$c['online'])
        return false;
      if ($filterClient) {
        $haystack = strtolower($c['id'] . ' ' . $c['groupe'] . ' ' . $c['caisse'] . ' ' . $c['key']);
        if (strpos($haystack, $filterClient) === false)
          return false;
      }
      return true;
    });
  }

  function timeAgo($ts)
  {
    $diff = time() - $ts;
    if ($diff < 60)
      return "il y a {$diff}s";
    if ($diff < 3600)
      return "il y a " . round($diff / 60) . "min";
    if ($diff < 86400)
      return "il y a " . round($diff / 3600) . "h";
    return "il y a " . round($diff / 86400) . "j";
  }
  function deviceIcon($ua)
  {
    $ua = strtolower($ua);
    if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone'))
      return '📱';
    if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))
      return '📟';
    return '🖥';
  }
  ?>
  <header>
    <div class="logo">
      <div class="dot"></div> ACCESS LOGS <span style="color:var(--muted);font-weight:400">&nbsp;/&nbsp;Expert
        Gestion</span>
    </div>
    <div class="hright">
      <div class="badge"><?= date("d/m/Y H:i") ?></div>
      <a href="?logout"><button class="btn btn-danger">⏻ Logout</button></a>
    </div>
  </header>

  <main>
    <!-- KPIs -->
    <div class="kpi-grid" style="margin-bottom:1.5rem">
      <div class="kpi" style="--kpi-c:var(--accent)">
        <div class="kpi-v" style="color:var(--accent)"><?= $totalClients ?></div>
        <div class="kpi-l">Clients enregistrés</div>
      </div>
      <div class="kpi" style="--kpi-c:#22c55e">
        <div class="kpi-v" style="color:#22c55e"><?= $onlineCount ?></div>
        <div class="kpi-l">En ligne (30 min)</div>
      </div>
      <div class="kpi" style="--kpi-c:var(--accent2)">
        <div class="kpi-v" style="color:var(--accent2)"><?= $uniqueIPs ?></div>
        <div class="kpi-l">IPs uniques</div>
      </div>
      <div class="kpi" style="--kpi-c:var(--accent3)">
        <div class="kpi-v" style="color:var(--accent3)"><?= number_format($totalLines) ?></div>
        <div class="kpi-l">Accès totaux</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
        <input class="finput" type="text" name="q" placeholder="🔍 Rechercher client..."
          value="<?= htmlspecialchars($filterClient) ?>">
        <label style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;cursor:pointer">
          <input type="checkbox" name="online" <?= $filterOnline ? 'checked' : '' ?> style="accent-color:var(--accent)">
          En ligne seulement
        </label>
        <button class="btn btn-ghost" type="submit">Filtrer</button>
        <?php if ($filterClient || $filterOnline): ?>
          <a href="logs_viewer.php"><button class="btn btn-ghost" type="button">✕ Reset</button></a>
        <?php endif; ?>
      </form>
      <span style="margin-left:auto;font-size:.72rem;color:var(--muted)"><?= count($clients) ?> client(s)
        affiché(s)</span>
    </div>

    <!-- Client cards -->
    <div class="section-title"><span></span> Activité par client</div>
    <?php if (empty($clients)): ?>
      <div style="text-align:center;padding:4rem;color:var(--muted)">Aucun log trouvé dans la <code>base de données</code>
      </div>
    <?php else: ?>
      <div class="clients-grid">
        <?php foreach ($clients as $cid => $c):
          $last = $c['last'];
          $statusClass = $c['online'] ? 'online' : 'offline';
          $statusLabel = $c['online'] ? '● En ligne' : '○ Hors ligne';
          $diffLabel = $last ? timeAgo($c['lastTime']) : 'jamais';
          ?>
          <div class="client-card" id="card-<?= htmlspecialchars($cid) ?>">
            <div class="client-header" onclick="toggleCard('<?= htmlspecialchars($cid) ?>')">
              <div>
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                  <?php if ($c['groupe']): ?>
                    <div class="client-name"><?= htmlspecialchars($c['groupe']) ?></div>
                    <?php if ($c['caisse']): ?>
                      <span
                        style="font-size:.7rem;color:var(--accent3);background:rgba(245,158,11,.09);border:1px solid rgba(245,158,11,.25);border-radius:5px;padding:1px 7px"><?= htmlspecialchars($c['caisse']) ?></span>
                    <?php endif; ?>
                    <?php if ($c['key']): ?>
                      <span
                        style="font-size:.65rem;color:var(--muted);background:var(--surface2);border:1px solid var(--border2);border-radius:5px;padding:1px 7px"><?= htmlspecialchars($c['key']) ?></span>
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="client-name" style="color:var(--muted2)"><?= htmlspecialchars($cid) ?></div>
                    <span style="font-size:.65rem;color:var(--accent4)">⚠ introuvable (ni clients.php ni DB)</span>
                  <?php endif; ?>
                  <span class="online-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                </div>
                <div class="client-meta">
                  <?php if ($c['groupe']): ?>
                    <span class="cmeta" style="color:var(--muted);font-family:monospace">📁
                      <?= htmlspecialchars($cid) ?></span>
                  <?php endif; ?>
                  <span class="cmeta">🕐 <?= htmlspecialchars($diffLabel) ?></span>
                  <span class="cmeta">📋 <?= $c['count'] ?> accès</span>
                  <span class="cmeta">🌐 <?= count($c['ips']) ?> IP(s) :
                    <?= implode(', ', array_slice($c['ips'], 0, 3)) ?>     <?= count($c['ips']) > 3 ? '…' : '' ?></span>
                  <?php if ($last): ?>
                    <span class="cmeta"><?= deviceIcon($last['ua']) ?>
                      <?= htmlspecialchars(substr($last['ua'], 0, 50)) ?>…</span>
                  <?php endif; ?>
                </div>
              </div>
              <span class="chevron">▾</span>
            </div>
            <div class="log-body" id="body-<?= htmlspecialchars($cid) ?>">
              <table class="log-table">
                <thead>
                  <tr>
                    <th>Date / Heure</th>
                    <th>IP</th>
                    <th>Période consultée</th>
                    <th>Ticket</th>
                    <th>Navigateur</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($c['entries'], 0, 200) as $i => $e):
                    $age = time() - strtotime($e['datetime']);
                    $isRecent = $age < 1800;
                    $dotColor = $isRecent ? '#22c55e' : ($age < 86400 ? 'var(--accent3)' : 'var(--muted)');
                    ?>
                    <tr>
                      <td class="time">
                        <span class="recent-dot" style="background:<?= $dotColor ?>"></span>
                        <?= htmlspecialchars($e['datetime']) ?>
                        <br><span style="color:var(--muted);font-size:.65rem"><?= timeAgo(strtotime($e['datetime'])) ?></span>
                      </td>
                      <td class="ip"><?= htmlspecialchars($e['ip']) ?></td>
                      <td>
                        <?php if ($e['dateD'] === $e['dateF']): ?>
                          <span style="color:var(--muted2)"><?= htmlspecialchars($e['dateD']) ?></span>
                        <?php else: ?>
                          <span style="color:var(--muted2)"><?= htmlspecialchars($e['dateD']) ?> →
                            <?= htmlspecialchars($e['dateF']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?= $e['ticket'] !== '-' ? '<span style="color:var(--accent3)">#' . (int) $e['ticket'] . '</span>' : '<span style="color:var(--muted)">-</span>' ?>
                      </td>
                      <td class="ua" title="<?= htmlspecialchars($e['ua']) ?>">
                        <?= deviceIcon($e['ua']) ?>       <?= htmlspecialchars(substr($e['ua'], 0, 60)) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if ($c['count'] > 200): ?>
                    <tr>
                      <td colspan="5" style="text-align:center;color:var(--muted);padding:1rem">
                        … <?= $c['count'] - 200 ?> entrées plus anciennes non affichées
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script>
    function toggleCard(id) {
      const body = document.getElementById('body-' + id);
      const header = document.querySelector('#card-' + id + ' .client-header');
      const isOpen = body.classList.contains('open');
      body.classList.toggle('open', !isOpen);
      header.classList.toggle('open', !isOpen);
    }
    // Auto-open online clients
    document.querySelectorAll('.online-badge.online').forEach(el => {
      const card = el.closest('.client-card');
      if (card) {
        const id = card.id.replace('card-', '');
        toggleCard(id);
      }
    });
    // Auto-refresh every 60s
    setTimeout(() => location.reload(), 60000);
  </script>
</body>

</html>