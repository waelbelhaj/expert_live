<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include("logger.php");
date_default_timezone_set('Etc/GMT');
setlocale(LC_ALL, ['fr', 'fra', 'fr_FR']);

// ─── AUTH & CONFIG ─────────────────────────────────────────────────────────
include("login2.php");
include("clients.php"); // Fichier des clients à la racine pour avoir $users

// ─── PARAMS RESTENT EN PHP POUR INITIALISER LA VUE ─────────────────────────
$idClient = trim($_GET['idClient'] ?? '');
if (!$idClient)
  die("OAuth3.0 ERROR");

$dateD_str = $_GET['dateD'] ?? date("Y-m-d");
$timeD_str = $_GET['timeD'] ?? "00:00";
$dateF_str = $_GET['dateF'] ?? date("Y-m-d");
$timeF_str = $_GET['timeF'] ?? "23:59";

// Déterminer le nom du groupe pour l'interface
$nomGroupe = "Inconnu";
if (isset($_SESSION["user"][2]) && $_SESSION["user"][2] !== "*") {
  $nomGroupe = $_SESSION["user"][2];
} else {
  foreach ($users as $u => $p) {
    if ($p[1] == $idClient) {
      $nomGroupe = $p[2];
      break;
    }
  }
}

// Fallback: If still unknown, check the database (important for dynamically added clients)
if ($nomGroupe === "Inconnu" || $nomGroupe === "") {
  try {
    require_once __DIR__ . '/db.php';
    $stmt = $pdo->prepare("SELECT nom FROM clients WHERE id_client = ? OR caisse_id = ? LIMIT 1");
    $stmt->execute([$idClient, $idClient]);
    $dbClient = $stmt->fetch();
    if ($dbClient) {
      $nomGroupe = $dbClient['nom'];
    }
  } catch (Exception $e) {
    // Silently fail and keep "Inconnu"
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expert Gestion POS — Dashboard v3 — <?= htmlspecialchars($nomGroupe) ?></title>

  <!-- Fonts & Icons -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>

  <link rel="stylesheet" href="assets/ccsv3.css?v=4">

  <style>
    /* Loader css for AJAX */
    #loader {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      color: white;
      transition: opacity 0.3s;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: var(--color-primary);
      animation: spin 1s ease-in-out infinite;
      margin-bottom: 20px;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .clotures-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .clotures-table th,
    .clotures-table td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid var(--border);
      color: var(--text);
    }

    .clotures-table th {
      background: var(--bg-card);
      font-weight: 600;
    }
  </style>
</head>

<body>
  <!-- AJAX LOADER -->
  <div id="loader">
    <div class="spinner"></div>
    <h3>Chargement en direct depuis la base de données...</h3>
  </div>

  <header>
    <div class="logo">
      <div class="logo-icon"></div>
      <span>Expert Gestion v3 - <?php echo htmlspecialchars($nomGroupe); ?></span>
    </div>
    </div>
    <div class="header-right">
      <button class="app-grid-toggle" onclick="toggleAppGrid()" style="margin-right:15px">
        <i class="fas fa-th-large"></i>
        <span>Modules & Apps</span>
      </button>
      <!-- License Info Badges -->
      <div id="license-badges" style="display: flex; gap: 10px; margin-left: 15px;">
        <div id="license-status-badge" class="date-badge"
          style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
          <i class="fas fa-key"></i>
          <span id="license-status-text" style="font-size: 0.75rem; font-weight: 700;">Licence...</span>
        </div>
        <div class="date-badge" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
          <i class="fas fa-plug"></i>
          <span id="license-conn-text" style="font-size: 0.75rem; font-weight: 700;">-- / 60</span>
        </div>
        <div class="date-badge" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
          <i class="fas fa-hourglass-half"></i>
          <span id="license-next-text" style="font-size: 0.75rem; font-weight: 700;">Délai: --</span>
        </div>
      </div>
      <div class="status-badge" id="dbStatusBadge">
        <span>Connecté</span>
      </div>
      <a href="./login2.php?logout" class="btn btn-ghost"
        style="color: var(--color-danger); padding: 5px 10px; border-radius: 6px; text-decoration: none;"
        title="Se déconnecter">
        <i class="fas fa-sign-out-alt"></i>
      </a>
      <button class="theme-toggle" id="themeToggle" title="Toggle dark/light mode">
        <i class="fas fa-moon"></i>
      </button>
    </div>
  </header>

  <div class="filter-bar">
    <div class="filter-tabs">
      <div class="filter-tab active" data-target="tab-interval">Intervalle de temps</div>
      <div class="filter-tab" data-target="tab-cloture">Par clôture</div>
    </div>

    <div style="flex: 1"></div>

    <div id="view-indicator"
      style="display: none; align-items: center; gap: 10px; color: var(--color-primary); font-weight: 700; text-transform: uppercase; font-size: 0.8rem;">
      <i class="fas fa-layer-group"></i>
      <span id="current-view-name">Vue: Dashboard</span>
    </div>

    <form id="filterForm" onsubmit="event.preventDefault(); fetchDashboardData();">
      <input type="hidden" name="idClient" value="<?= htmlspecialchars($idClient) ?>">
      <input type="hidden" name="filterType" id="filterType" value="interval">

      <!-- Section Intervalle -->
      <div id="tab-interval" class="filter-section active">
        <input type="date" class="filter-input" id="dateD_filter" name="dateD"
          value="<?= htmlspecialchars($dateD_str) ?>">
        <input type="time" class="filter-input" id="timeD_filter" name="timeD"
          value="<?= htmlspecialchars($timeD_str) ?>">
        <span class="filter-sep">au</span>
        <input type="date" class="filter-input" id="dateF_filter" name="dateF"
          value="<?= htmlspecialchars($dateF_str) ?>">
        <input type="time" class="filter-input" id="timeF_filter" name="timeF"
          value="<?= htmlspecialchars($timeF_str) ?>">
      </div>

      <!-- Section Clôture -->
      <div id="tab-cloture" class="filter-section">
        <select name="cloture_month" id="cloture_month" class="filter-input month-sel"></select>
        <select name="cloture_year" id="cloture_year" class="filter-input year-sel"></select>
        <select name="cloture_id" id="cloture_select" class="cloture-select"></select>
      </div>

      <button type="submit" class="btn btn-primary" id="applyFiltersBtn">
        <i class="fas fa-sync-alt"></i> Appliquer
      </button>
    </form>
  </div>

  <main>
    <div class="content-area">
      <!-- KPI Grid -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label"><i class="fas fa-coins"></i> Chiffre d'affaires</div>
          <div class="kpi-value" id="stat_total">€0,00</div>
          <div class="kpi-sub" id="stat_count">0 tickets</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label"><i class="fas fa-chart-line"></i> Panier moyen</div>
          <div class="kpi-value" id="stat_avg">€0,00</div>
          <div class="kpi-sub">Min/Max</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label"><i class="fas fa-tag"></i> Remises appliquées</div>
          <div class="kpi-value" id="stat_remise">€0,00</div>
          <div class="kpi-sub" id="stat_remise_pct">0%</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label"><i class="fas fa-bolt"></i> Charge (1h)</div>
          <div class="kpi-value" id="stat_hourly_tickets">0</div>
          <div class="kpi-sub" id="stat_hourly_status"
            style="margin-top: 0.5rem; padding: 0.5rem 0; border-radius: 4px; text-align: center; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">
            Normal</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label"><i class="fas fa-trash"></i> Annulés</div>
          <div class="kpi-value" id="stat_deleted">0</div>
          <div class="kpi-sub" id="stat_deleted_val">€0,00</div>
        </div>
        <div class="kpi-card"
          style="background: linear-gradient(135deg, var(--color-primary) 0%, #000 100%); color: #fff;">
          <div class="kpi-label" style="color: rgba(255,255,255,0.8)"><i class="fas fa-trophy"></i> Record du Mois</div>
          <div class="kpi-value" id="stat_max_ticket" style="color: #fff;">0,000 Dt</div>
          <div id="stat_max_ticket_details"
            style="font-size: 0.75rem; opacity: 0.9; margin-top: 5px; line-height: 1.4;">
            <div id="stat_max_ticket_num">Ticket #0</div>
            <div id="stat_max_ticket_client" style="font-style: italic;">...</div>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="charts-grid">
        <div class="chart-card full">
          <div class="chart-title">Comparaison Ventes Journalières (Mois vs Mois Précédent)</div>
          <div class="chart-container" style="height: 300px;">
            <canvas id="dailyChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-title">Ventes par heure</div>
          <div class="chart-container">
            <canvas id="hourlyChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-title">Répartition par paiement</div>
          <div class="chart-container">
            <canvas id="paymentChart"></canvas>
          </div>
        </div>
        <div class="chart-card full">
          <div class="chart-title">Top 10 articles</div>
          <div class="chart-container">
            <canvas id="productsChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Tickets Section -->
      <div style="margin-top: 2rem;">
        <div class="section-title" id="toggleTickets"
          onclick="var t=document.getElementById('ticketsList'); t.style.display=t.style.display==='none'?'block':'none';"
          style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
          <span>
            <i class="fas fa-receipt"></i> Derniers tickets (Live)
            <span id="ticketsCountBadge"
              style="background:var(--color-primary); color:white; font-size:0.75rem; padding: 2px 8px; border-radius: 12px; margin-left: 8px;">0.</span>
          </span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="tickets-list" id="ticketsList" style="display: none;">
          <div class="empty">
            <i class="fas fa-inbox"></i>
            <p>Aucun ticket à afficher</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-section">
        <div class="sidebar-section-title">Par Caisse</div>
        <div id="caisseList"></div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">Top Vendeurs</div>
        <div id="vendeurList"></div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">Top Articles</div>
        <div id="bestSelList"></div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">Ventes par Famille</div>
        <div id="familleList"></div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">Moyens de paiement</div>
        <div id="paymentList"></div>
      </div>

      <div class="net-card">
        <div class="net-row">
          <span>Total brut:</span>
          <strong id="net_total">0,000 Dt</strong>
        </div>
        <div class="net-row">
          <span>Dépenses/Avances:</span>
          <strong id="net_expenses">0,000 Dt</strong>
        </div>
        <div class="net-total">
          Net Réel
          <span id="net_value">0,000 Dt</span>
        </div>
      </div>
    </div>

    <!-- VIEW CONTAINER (For App-like views) -->
    <div class="view-container" id="appViewContainer">
      <!-- Dashboard / Overview -->
      <div class="view-section" id="view-overview">
        <!-- This will clones/moves content dynamically if needed, 
                 but for simplicity we'll just toggle visibility of main content -->
      </div>

      <!-- Tickets View -->
      <div class="view-section" id="view-tickets">
        <div class="section-title"><i class="fas fa-receipt"></i> Historique complet des Tickets</div>
        <div id="ticketsList-view" class="tickets-list"></div>
      </div>

      <!-- Caisses View -->
      <div class="view-section" id="view-caisses">
        <div class="section-title"><i class="fas fa-cash-register"></i> Performance détaillée par Caisse</div>
        <div class="charts-grid" style="margin-bottom: 2rem;">
          <div class="chart-card full"><canvas id="caisseChart-view" style="height: 300px;"></canvas></div>
        </div>
        <div id="caisseList-view" class="view-grid"></div>
      </div>

      <!-- Vendeurs View -->
      <div class="view-section" id="view-vendeurs">
        <div class="section-title"><i class="fas fa-users"></i> Performance par Vendeur</div>
        <div id="vendeurList-view" class="view-grid"></div>
      </div>

      <!-- Familles View -->
      <div class="view-section" id="view-familles">
        <div class="section-title"><i class="fas fa-boxes"></i> Analyse par Famille de Produits</div>
        <div class="charts-grid" style="margin-bottom: 2rem;">
          <div class="chart-card full"><canvas id="familleChart-view" style="height: 300px;"></canvas></div>
        </div>
        <div id="familleList-view" class="view-grid"></div>
      </div>

      <!-- Articles View -->
      <div class="view-section" id="view-articles">
        <div class="section-title"><i class="fas fa-tag"></i> Top 50 des Articles les plus vendus</div>
        <div id="bestSelList-view" class="view-grid"></div>
      </div>

      <!-- Paiements View -->
      <div class="view-section" id="view-paiements">
        <div class="section-title"><i class="fas fa-credit-card"></i> Répartition des Moyens de Paiement</div>
        <div class="charts-grid" style="margin-bottom: 2rem;">
          <div class="chart-card full"><canvas id="paymentChart-view" style="height: 300px;"></canvas></div>
        </div>
        <div id="paymentList-view" class="view-grid"></div>
      </div>

      <!-- Clotures View -->
      <div class="view-section" id="view-clotures">
        <div class="section-title"><i class="fas fa-lock"></i> Journal des Clôtures de Caisse</div>
        <div id="modalCloturesContent-view"></div>
      </div>
    </div>
  </main>

  <!-- APP GRID OVERLAY -->
  <div class="app-grid-container" id="appGrid">
    <div class="grid-overlay-header">
      <h2>Expert Apps</h2>
      <p>Sélectionnez un module pour voir les détails</p>
    </div>
    <div class="app-grid">
      <div class="app-item" onclick="switchView('overview')">
        <div class="app-icon"><i class="fas fa-chart-pie"></i></div>
        <div class="app-label">Dashboard</div>
      </div>
      <div class="app-item" onclick="switchView('tickets')">
        <div class="app-icon"><i class="fas fa-receipt"></i></div>
        <div class="app-label">Tickets</div>
      </div>
      <div class="app-item" onclick="switchView('caisses')">
        <div class="app-icon"><i class="fas fa-cash-register"></i></div>
        <div class="app-label">Caisses</div>
      </div>
      <div class="app-item" onclick="switchView('vendeurs')">
        <div class="app-icon"><i class="fas fa-users"></i></div>
        <div class="app-label">Vendeurs</div>
      </div>
      <div class="app-item" onclick="switchView('articles')">
        <div class="app-icon"><i class="fas fa-tag"></i></div>
        <div class="app-label">Articles</div>
      </div>
      <div class="app-item" onclick="switchView('familles')">
        <div class="app-icon"><i class="fas fa-boxes"></i></div>
        <div class="app-label">Familles</div>
      </div>
      <div class="app-item" onclick="switchView('paiements')">
        <div class="app-icon"><i class="fas fa-credit-card"></i></div>
        <div class="app-label">Paiement</div>
      </div>
      <div class="app-item" onclick="switchView('clotures')">
        <div class="app-icon"><i class="fas fa-history"></i></div>
        <div class="app-label">Clôtures</div>
      </div>
    </div>

    <button class="btn btn-ghost" onclick="toggleAppGrid()"
      style="margin-top: 50px; border-radius: 50px; padding: 12px 30px;">
      <i class="fas fa-times"></i> Fermer
    </button>
  </div>

  <!-- FLOATING HOME BUTTON -->
  <button class="floating-home-btn" id="homeBtn" onclick="toggleAppGrid()" title="Retour au menu">
    <i class="fas fa-th-large"></i>
  </button>

  <!-- Modal Ticket -->
  <div class="modal-overlay" id="ticketModal">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title">
          <i class="fas fa-receipt"></i> Détails du ticket
        </div>
        <button class="modal-close" onclick="closeModal('ticketModal')">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div id="modalContent"></div>
    </div>
  </div>

  <!-- Modal Clotures (NEW) -->
  <div class="modal-overlay" id="cloturesModal">
    <div class="modal" style="max-width: 600px;">
      <div class="modal-header">
        <div class="modal-title">
          <i class="fas fa-lock"></i> Historique des Clôtures
        </div>
        <button class="modal-close" onclick="closeModal('cloturesModal')">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div id="modalCloturesContent" style="max-height: 400px; overflow-y: auto;">
        <p style="text-align:center; padding:20px; color:var(--muted)">Chargement...</p>
      </div>
    </div>
  </div>


  <!-- MVC CONTROLLER (JS) -->
  <script>
    // Globals
    let stats = {};
    let tickets = [];
    let clotures = [];
    let myCharts = {};

    // Theme Management
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    const currentTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon();

    themeToggle.addEventListener('click', () => {
      const newTheme = htmlElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      htmlElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateThemeIcon();
      if (myCharts.hourly) { destroyCharts(); initCharts(); } // Refresh colors
    });

    function updateThemeIcon() {
      themeToggle.querySelector('i').className = htmlElement.getAttribute('data-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    // Filter Tabs Logic
    document.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.filter-section').forEach(s => s.classList.remove('active'));

        tab.classList.add('active');
        const target = tab.getAttribute('data-target');
        document.getElementById(target).classList.add('active');
        document.getElementById('filterType').value = target === 'tab-interval' ? 'interval' : 'cloture';
      });
    });

    // Populate and initialize date selectors
    const monthSelect = document.getElementById('cloture_month');
    const yearSelect = document.getElementById('cloture_year');
    const currentMonth = new Date().getMonth() + 1;
    const currentYear = new Date().getFullYear();
    const monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];

    monthNames.forEach((name, i) => {
      let opt = document.createElement('option');
      opt.value = i + 1;
      opt.textContent = name;
      if (i + 1 == currentMonth) opt.selected = true;
      monthSelect.appendChild(opt);
    });

    for (let y = currentYear; y >= currentYear - 5; y--) {
      let opt = document.createElement('option');
      opt.value = y;
      opt.textContent = y;
      if (y == currentYear) opt.selected = true;
      yearSelect.appendChild(opt);
    }

    [monthSelect, yearSelect].forEach(el => el.addEventListener('change', () => fetchDashboardData()));

    // Tools
    function formatCurrency(value) {
      if (isNaN(value)) value = 0;
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3,
      }).format(value) + " Dt"; // Adaptez la devise ici si besoin
    }

    /* ----------------------------------------------------------------------
       FETCH API DASHBOARD (Controller)
       ---------------------------------------------------------------------- */
    async function fetchDashboardData() {
      document.getElementById('loader').style.display = 'flex';

      try {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData).toString();
        // L'API est à la racine, donc api_dashboard.php par rapport au dossier tmpData
        const res = await fetch(`api_dashboard.php?${params}`);
        if (!res.ok) throw new Error("HTTP Status " + res.status);

        const data = await res.json();

        if (data.error) {
          alert("Erreur API : " + data.error);
          return;
        }

        stats = data.stats;
        tickets = data.tickets;
        clotures = data.clotures;

        // Mise à jour du sélecteur de clôtures
        const clotSelect = document.getElementById('cloture_select');
        const currentVal = clotSelect.value;
        clotSelect.innerHTML = '<option value="">-- Sélectionner une clôture --</option>' +
          clotures.map(c => `<option value="${c.id}" ${currentVal == c.id ? 'selected' : ''}>${c.caisse} #${c.num} (${c.date_fin}) - ${formatCurrency(c.valeur)}</option>`).join('');

        // Mettre à jour l'interface
        updateKPIs();
        renderTickets();
        renderSidebar();

        destroyCharts();
        initCharts();

        if (data.license) {
          updateLicenseUI(data.license);
        }

      } catch (e) {
        console.error(e);
        alert("Erreur lors de la récupération SQL : " + e.message);
      } finally {
        setTimeout(() => { document.getElementById('loader').style.display = 'none'; }, 300);
      }
    }

    /* ----------------------------------------------------------------------
       UPDATE VIEW (Views)
       ---------------------------------------------------------------------- */
    function updateLicenseUI(license) {
      const statusText = document.getElementById('license-status-text');
      const statusBadge = document.getElementById('license-status-badge');
      const connText = document.getElementById('license-conn-text');
      const nextText = document.getElementById('license-next-text');

      if (license.is_paid) {
        statusText.textContent = "Licence: Active";
        statusBadge.style.borderColor = "#10b981";
        statusBadge.style.color = "#10b981";
      } else {
        statusText.textContent = "Licence: Impayé";
        statusBadge.style.borderColor = "#ef4444";
        statusBadge.style.color = "#ef4444";
      }

      connText.textContent = `${license.connections_remaining} Conn. rest.`;
      nextText.textContent = `Prochain: ${license.next_payment}`;
    }

    function updateKPIs() {
      document.getElementById('stat_total').textContent = formatCurrency(stats.total);
      document.getElementById('stat_count').textContent = `${stats.count} tickets`;

      const avg = stats.count > 0 ? stats.total / stats.count : 0;
      document.getElementById('stat_avg').textContent = formatCurrency(avg);

      document.getElementById('stat_remise').textContent = formatCurrency(stats.totalRemise);
      const remisePct = stats.total > 0 ? ((stats.totalRemise / stats.total) * 100).toFixed(1) : 0;
      document.getElementById('stat_remise_pct').textContent = `${remisePct}%`;

      document.getElementById('stat_deleted').textContent = stats.countDeleted;
      document.getElementById('stat_deleted_val').textContent = formatCurrency(stats.totalDeleted);

      // Hourly charge indicator
      const hourlyTickets = stats.ticketsLastHour || 0;
      document.getElementById('stat_hourly_tickets').textContent = hourlyTickets;

      const statusBadge = document.getElementById('stat_hourly_status');
      let status = 'normal';
      let statusText = 'Normal';

      if (hourlyTickets > 50) {
        status = 'critical'; statusText = '⚠️ Critique';
      } else if (hourlyTickets > 30) {
        status = 'warning'; statusText = '⚡ Élevé';
      }

      statusBadge.className = `status-${status}`;
      statusBadge.textContent = statusText;

      // Record Ticket
      if (stats.ticketMaxRecord) {
        document.getElementById('stat_max_ticket').textContent = formatCurrency(stats.ticketMaxRecord.total);
        document.getElementById('stat_max_ticket_num').textContent = `N#${stats.ticketMaxRecord.num} (${stats.ticketMaxRecord.date})`;
        document.getElementById('stat_max_ticket_client').innerHTML = `
          Par: <b>${stats.ticketMaxRecord.vendeur}</b><br>
          Caisse: <b>${stats.ticketMaxRecord.caisse}</b><br>
          Client: <b>${stats.ticketMaxRecord.client || 'Passant'}</b>
        `;
      } else {
        document.getElementById('stat_max_ticket').textContent = "0,000 Dt";
        document.getElementById('stat_max_ticket_num').textContent = "Aucun record ce mois";
        document.getElementById('stat_max_ticket_client').textContent = "";
      }

      // Net
      const totalExpenses = stats.depenses.avances + stats.depenses.consommables;
      const net = stats.total - totalExpenses;
      document.getElementById('net_total').textContent = formatCurrency(stats.total);
      document.getElementById('net_expenses').textContent = formatCurrency(totalExpenses);
      document.getElementById('net_value').textContent = formatCurrency(net);
    }

    function renderTickets() {
      const container = document.getElementById('ticketsList');
      const containerView = document.getElementById('ticketsList-view');
      const badge = document.getElementById('ticketsCountBadge');

      if (badge) badge.textContent = tickets ? tickets.length + ' trouvé(s)' : '0 trouvé(s)';

      if (!tickets || tickets.length === 0) {
        const emptyHtml = '<div class="empty"><i class="fas fa-inbox"></i><p>Aucun ticket sur la période.</p></div>';
        container.innerHTML = emptyHtml;
        if (containerView) containerView.innerHTML = emptyHtml;
        return;
      }

      const html = tickets.map((t, idx) => `
        <div class="ticket-row ${t.deleted ? 'deleted' : ''}" onclick="showTicketModal(${idx})">
          <div class="ticket-num">#${t.num}</div>
          <div class="ticket-details" style="flex:1">
            <div class="ticket-vendeur" style="display:flex; justify-content:space-between;">
              <span>${t.vendeur} <span style="font-size:0.7rem; color:var(--color-primary); margin-left:4px;">(${t.articles || 0} art.)</span></span>
              <span style="font-size: 0.75rem; color: var(--muted); font-weight:normal;">HT: ${formatCurrency(t.total_ht || 0)} &nbsp;|&nbsp; TVA: ${formatCurrency(t.total_tva || 0)}</span>
            </div>
            <div class="ticket-time">${t.date}</div>
          </div>
          <div class="ticket-pay-badge pay-${t.paiement.toLowerCase().replace(/\\s+/g, '')}">${t.paiement}</div>
          <div class="ticket-pay-badge">${t.caisse}</div>
          <div class="ticket-total" style="display:flex; flex-direction:column; text-align:right;">
             <span>${formatCurrency(t.total)}</span>
             <span style="font-size:0.65rem; color:var(--muted); font-weight:normal; margin-top:1px;">TTC</span>
          </div>
        </div>
      `).join('');

      container.innerHTML = html;
      if (containerView) containerView.innerHTML = html;
    }

    function renderSidebar() {
      const renderRank = (dataTotal, dataCount, suffix, limit = 7) => {
        return Object.entries(dataTotal || {}).sort((a, b) => b[1] - a[1]).slice(0, limit).map(([name, total], idx) => {
          const maxTotal = Math.max(...Object.values(dataTotal || { 0: 1 }));
          const pct = Math.min((total / (maxTotal || 1)) * 100, 100);
          const subtxt = dataCount ? (dataCount[name] + " " + suffix) : formatCurrency(total);
          return `
            <div class="ranking-item">
              <div class="rank-num">${idx + 1}</div>
              <div class="rank-info">
                <div class="rank-name">${name}</div>
                <div class="rank-value">${subtxt}</div>
                <div class="rank-bar"><div class="rank-bar-fill" style="width: ${pct}%"></div></div>
              </div>
            </div>`;
        }).join('');
      };

      // Sidebar original
      document.getElementById('caisseList').innerHTML = renderRank(stats.steTotal, stats.steNbr, "tickets", 7);
      document.getElementById('vendeurList').innerHTML = renderRank(stats.vendeurTotal, stats.vendeurNbr, "ventes", 7);
      document.getElementById('familleList').innerHTML = renderRank(stats.familles, null, "", 7);

      // Best sellers (Top 5) sidebar
      const renderBestSellers = (limit = 5) => {
        return Object.entries(stats.bestSel || {}).sort((a, b) => b[1] - a[1]).slice(0, limit).map(([name, qty], idx) => {
          const total = stats.bestSelTotal[name] || 0;
          const maxQty = Math.max(...Object.values(stats.bestSel || { 0: 1 }));
          const pct = Math.min((qty / maxQty) * 100, 100);
          return `
                <div class="ranking-item">
                  <div class="rank-num">${idx + 1}</div>
                  <div class="rank-info">
                    <div class="rank-name">${name}</div>
                    <div class="rank-value">${qty.toFixed(0)} × ${formatCurrency(total)}</div>
                    <div class="rank-bar"><div class="rank-bar-fill" style="width: ${pct}%"></div></div>
                  </div>
                </div>`;
        }).join('');
      };

      document.getElementById('bestSelList').innerHTML = renderBestSellers(5);

      // Payment sidebar
      const renderPayments = () => {
        return Object.entries(stats.totalPay || {}).map(([method, data]) => `
              <div class="ranking-item">
                <strong style="color: var(--color-primary); font-size: 0.8rem;">${method}</strong>
                <div style="flex: 1;"></div>
                <div style="font-size: 0.8rem; color: var(--text); text-align: right;">
                  ${formatCurrency(data.total)} <span style="color:var(--muted)">(${data.count})</span>
                </div>
              </div>
          `).join('');
      };
      document.getElementById('paymentList').innerHTML = renderPayments();

      // Update Detailed Views if active
      if (document.body.getAttribute('data-view-active')) {
        document.getElementById('caisseList-view').innerHTML = renderRank(stats.steTotal, stats.steNbr, "tickets", 20);
        document.getElementById('vendeurList-view').innerHTML = renderRank(stats.vendeurTotal, stats.vendeurNbr, "ventes", 20);
        document.getElementById('familleList-view').innerHTML = renderRank(stats.familles, null, "", 30);
        document.getElementById('bestSelList-view').innerHTML = renderBestSellers(50);
        document.getElementById('paymentList-view').innerHTML = renderPayments();

        initViewCharts();
      }
    }

    /* ----------------------------------------------------------------------
       CHARTS
       ---------------------------------------------------------------------- */
    const chartColors = {
      primary: 'rgb(59, 130, 246)', success: 'rgb(16, 185, 129)', warning: 'rgb(245, 158, 11)',
      danger: 'rgb(239, 68, 68)', accent: 'rgb(139, 92, 246)', info: 'rgb(6, 182, 212)'
    };

    function destroyCharts() {
      if (myCharts.hourly) myCharts.hourly.destroy();
      if (myCharts.payment) myCharts.payment.destroy();
      if (myCharts.products) myCharts.products.destroy();
      if (myCharts.daily) myCharts.daily.destroy();
    }

    function initCharts() {
      const ctx1 = document.getElementById('hourlyChart').getContext('2d');
      myCharts.hourly = new Chart(ctx1, {
        type: 'line',
        data: {
          labels: Array.from({ length: 24 }, (_, i) => `${i}h`),
          datasets: [{
            label: 'Ventes',
            data: Object.values(stats.hourly || {}),
            borderColor: chartColors.primary,
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true, tension: 0.4, pointRadius: 2, pointHoverRadius: 5
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true } }
        }
      });

      const ctx2 = document.getElementById('paymentChart').getContext('2d');
      const paymentLabels = Object.keys(stats.totalPay || {});
      const paymentData = Object.values(stats.totalPay || {}).map(p => p.total);
      myCharts.payment = new Chart(ctx2, {
        type: 'doughnut',
        data: {
          labels: paymentLabels,
          datasets: [{
            data: paymentData,
            backgroundColor: [chartColors.primary, chartColors.success, chartColors.warning, chartColors.danger, chartColors.accent, chartColors.info],
            borderWidth: 0
          }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
      });

      const ctx3 = document.getElementById('productsChart').getContext('2d');
      const topProducts = Object.entries(stats.bestSel || {}).sort((a, b) => b[1] - a[1]).slice(0, 10);
      myCharts.products = new Chart(ctx3, {
        type: 'bar',
        data: {
          labels: topProducts.map(p => p[0].substring(0, 15)),
          datasets: [{
            label: 'Qté', data: topProducts.map(p => p[1]),
            backgroundColor: chartColors.primary, borderRadius: 4
          }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
      });

      const ctx4 = document.getElementById('dailyChart').getContext('2d');
      myCharts.daily = new Chart(ctx4, {
        type: 'line',
        data: {
          labels: Array.from({ length: 31 }, (_, i) => i + 1),
          datasets: [
            {
              label: 'Mois en cours',
              data: stats.dailyCurrent || [],
              borderColor: chartColors.primary,
              backgroundColor: 'rgba(59, 130, 246, 0.2)',
              fill: true, tension: 0.3
            },
            {
              label: 'Mois précédent',
              data: stats.dailyPrevious || [],
              borderColor: '#94a3b8',
              borderDash: [5, 5],
              backgroundColor: 'transparent',
              fill: false, tension: 0.3
            },
            {
              label: "Même mois de l'année précédente",
              data: stats.dailyLastYear || [],
              borderColor: chartColors.warning || '#f59e0b',
              borderDash: [2, 2],
              backgroundColor: 'transparent',
              fill: false, tension: 0.3
            }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { tooltip: { mode: 'index', intersect: false } },
          scales: { y: { beginAtZero: true } }
        }
      });

      if (document.body.getAttribute('data-view-active')) initViewCharts();
    }

    function initViewCharts() {
      // Destroy existing view charts
      if (myCharts.view_caisse) myCharts.view_caisse.destroy();
      if (myCharts.view_famille) myCharts.view_famille.destroy();
      if (myCharts.view_payment) myCharts.view_payment.destroy();

      const ctxCaisse = document.getElementById('caisseChart-view');
      if (ctxCaisse) {
        const labels = Object.keys(stats.steTotal || {});
        const data = Object.values(stats.steTotal || {});
        myCharts.view_caisse = new Chart(ctxCaisse, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{ label: 'CA par Caisse', data: data, backgroundColor: chartColors.primary }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });
      }

      const ctxFamille = document.getElementById('familleChart-view');
      if (ctxFamille) {
        const labels = Object.keys(stats.familles || {});
        const data = Object.values(stats.familles || {});
        myCharts.view_famille = new Chart(ctxFamille, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: [chartColors.primary, chartColors.success, chartColors.warning, chartColors.danger, chartColors.accent] }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
        });
      }

      const ctxPay = document.getElementById('paymentChart-view');
      if (ctxPay) {
        const labels = Object.keys(stats.totalPay || {});
        const data = Object.values(stats.totalPay || {}).map(p => p.total);
        myCharts.view_payment = new Chart(ctxPay, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: [chartColors.primary, chartColors.success, chartColors.warning, chartColors.danger, chartColors.accent] }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });
      }
    }

    /* ----------------------------------------------------------------------
       MODALS 
       ---------------------------------------------------------------------- */
    function showTicketModal(idx) {
      const ticket = tickets[idx];
      let html = `
        <div style="margin-bottom: 1.5rem;">
          <p style="color: var(--muted); margin-bottom: 0.5rem;">Ticket #${ticket.num} — ${ticket.date}</p>
          <p style="font-size: 0.9rem;"><strong>${ticket.caisse}</strong> • ${ticket.vendeur} • Paiement: <b>${ticket.paiement}</b></p>
        </div>
        <table class="clotures-table">
          <thead><tr><th>Article</th><th style="text-align:right">Qté</th><th style="text-align:right">Prix HT</th><th style="text-align:right">Remise</th><th style="text-align:right">Total TTC</th></tr></thead>
          <tbody>
            ${ticket.produits.map(p => `<tr><td>${p.nom}</td><td style="text-align:right">${p.qte}</td><td style="text-align:right">${formatCurrency(p.prix_ht)}</td><td style="text-align:right">${p.remise > 0 ? formatCurrency(p.remise) : '-'}</td><td style="text-align:right; font-weight: 500;">${formatCurrency(p.total)}</td></tr>`).join('')}
          </tbody>
          <tfoot><tr><td colspan="4"><b>TOTAL</b></td><td style="text-align:right; font-weight:bold; color:var(--color-primary)">${formatCurrency(ticket.total)}</td></tr></tfoot>
        </table>`;
      document.getElementById('modalContent').innerHTML = html;
      document.getElementById('ticketModal').classList.add('open');
    }

    function showCloturesModal() {
      let html = '';
      if (!clotures || clotures.length === 0) {
        html = '<p style="text-align:center; padding:20px; color:var(--muted)">Aucun historique de clôture trouvé pour cette période.</p>';
      } else {
        html = `
            <table class="clotures-table">
              <thead><tr><th>Caisse</th><th>N°</th><th>Montant</th><th>Début</th><th>Fin</th></tr></thead>
              <tbody>
                ${clotures.map(c => `<tr>
                    <td><b>${c.caisse}</b></td>
                    <td>#${c.num}</td>
                    <td style="font-weight:bold;color:var(--color-primary)">${formatCurrency(c.valeur)}</td>
                    <td style="font-size:0.8rem">${c.date_debut}</td>
                    <td style="font-size:0.8rem">${c.date_fin}</td>
                </tr>`).join('')}
              </tbody>
            </table>`;
      }
      document.getElementById('modalCloturesContent').innerHTML = html;
      document.getElementById('cloturesModal').classList.add('open');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    /* --- NEW NAVIGATION LOGIC --- */
    function toggleAppGrid() {
      const grid = document.getElementById('appGrid');
      grid.classList.toggle('active');
    }

    function switchView(viewId) {
      // Toggle body attribute to hide/show main content or view container
      if (viewId === 'overview') {
        document.body.removeAttribute('data-view-active');
        document.getElementById('view-indicator').style.display = 'none';
        document.getElementById('homeBtn').classList.remove('visible');
      } else {
        document.body.setAttribute('data-view-active', 'true');
        document.getElementById('view-indicator').style.display = 'flex';
        document.getElementById('current-view-name').textContent = 'Vue: ' + viewId;
        document.getElementById('homeBtn').classList.add('visible');

        // Activate specific view section
        document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById('view-' + viewId);
        if (target) target.classList.add('active');
      }

      // Sync contents to view if needed (mostly just copying innerHTML for now)
      syncViews();

      // Close grid
      toggleAppGrid();

      // Scroll to top
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function syncViews() {
      // Tickets logic already handled in renderTickets
      // Sidebar logic already handled in renderSidebar

      // Ensure clotures are visible
      if (document.getElementById('view-clotures').classList.contains('active')) {
        showCloturesInView();
      }

      // Re-init charts if needed
      initViewCharts();
    }

    function showCloturesInView() {
      let html = '';
      if (!clotures || clotures.length === 0) {
        html = '<p style="text-align:center; padding:20px; color:var(--muted)">Aucun historique de clôture trouvé.</p>';
      } else {
        html = `
                <table class="clotures-table">
                  <thead><tr><th>Caisse</th><th>N°</th><th>Montant</th><th>Début</th><th>Fin</th></tr></thead>
                  <tbody>
                    ${clotures.map(c => `<tr>
                        <td><b>${c.caisse}</b></td>
                        <td>#${c.num}</td>
                        <td style="font-weight:bold;color:var(--color-primary)">${formatCurrency(c.valeur)}</td>
                        <td style="font-size:0.8rem">${c.date_debut}</td>
                        <td style="font-size:0.8rem">${c.date_fin}</td>
                    </tr>`).join('')}
                  </tbody>
                </table>`;
      }
      document.getElementById('modalCloturesContent-view').innerHTML = html;
    }

    // Wrap the existing render functions to also sync views
    const originalRenderTickets = renderTickets;
    renderTickets = function () {
      originalRenderTickets();
      if (document.body.getAttribute('data-view-active')) syncViews();
    };

    const originalRenderSidebar = renderSidebar;
    renderSidebar = function () {
      originalRenderSidebar();
      if (document.body.getAttribute('data-view-active')) syncViews();
    };

    // Init Fetching
    window.onload = () => {
      fetchDashboardData();
      // Optionnel : Actualisation toutes les 2 minutes (120000ms)
      setInterval(fetchDashboardData, 120000);
    };

  </script>
</body>

</html>