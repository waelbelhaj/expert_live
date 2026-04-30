<?php
session_start();
// Superadmin check
if (!isset($_SESSION["user"]) || $_SESSION["user"][1] !== '*') {
    header('location: ./login2.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin - Expert Gestion</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/ccsv3.css?v=7">
    <style>
        :root {
            --color-primary: #0b458b;
        }

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            margin-top: 1rem;
        }

        .admin-table th {
            text-align: left;
            padding: 12px 16px;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .admin-table td {
            padding: 16px;
            background: var(--surface);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .admin-table tr td:first-child {
            border-left: 1px solid var(--border);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .admin-table tr td:last-child {
            border-right: 1px solid var(--border);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-paid {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .status-unpaid {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .status-locked {
            background: #333;
            color: #fff;
        }

        .usage-bar {
            width: 100px;
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }

        .usage-fill {
            height: 100%;
            background: var(--color-primary);
        }

        .usage-fill.warning {
            background: var(--color-warning);
        }

        .usage-fill.danger {
            background: var(--color-danger);
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            background: var(--surface2);
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 4px;
        }

        .action-btn:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .action-btn.btn-danger:hover {
            border-color: var(--color-danger);
            color: var(--color-danger);
        }

        .modal-form {
            display: grid;
            gap: 1rem;
            padding: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .formContainerPaiement {
            max-height: 53vh;
            overflow: auto;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            font-family: inherit;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            font-size: 0.9rem;
        }

        .tabs-header {
            display: flex;
            gap: 2rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .tab-link {
            padding: 1rem 0;
            color: var(--muted);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .tab-link.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }

        .view-section {
            display: none;
        }

        .view-section.active {
            display: block;
        }

        .payment-history-table {
            width: 100%;
            font-size: 0.85rem;
            border-collapse: collapse;
        }

        .payment-history-table th,
        .payment-history-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        /* Fixed Header/Footer Modal System */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--surface);
            border-radius: 20px;
            width: 100%;
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            padding: 0;
            height: 90vh;
        }

        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            background: var(--surface);
            margin-bottom: 0;
        }

        .modal-body {
            padding: 1rem;
            overflow-y: auto;
            flex-grow: 1;
            height: 70vh;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-shrink: 0;
            background: var(--surface2);
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(30px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <div class="logo-icon"></div>
            <span>Expert Gestion — Superadmin</span>
        </div>
        <div class="header-right">
            <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
            <a href="login2.php?logout" class="btn btn-ghost" style="color: var(--color-danger)">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <main style="display: block; padding: 2rem;">
        <div class="tabs-header">
            <div class="tab-link active" onclick="switchTab(this, 'dashboard')"><i class="fas fa-users"></i> Gestion
                Clients</div>
            <div class="tab-link" onclick="switchTab(this, 'stats')"><i class="fas fa-chart-line"></i> Statistiques
                Globales</div>
        </div>

        <!-- DASHBOARD VIEW -->
        <div id="view-dashboard" class="view-section active">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input"
                    placeholder="Rechercher un client (ID, Nom, Role)...">

                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-primary" id="btnNewClient">
                        <i class="fas fa-plus"></i> Nouveau Client
                    </button>
                    <button class="btn btn-success" id="btnGlobalPayment"
                        style="background: #10b981; border: none; color: white;">
                        <i class="fas fa-money-bill-wave"></i> Saisir Paiement
                    </button>
                </div>
            </div>

            <div id="clients-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Role / Caisse</th>
                            <th>Status (Mois en cours)</th>
                            <th>Connexions (Mois)</th>
                            <th>Note / Obs.</th>
                            <th style="text-align: right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientsTableBody">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- STATS VIEW -->
        <div id="view-stats" class="view-section">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Clients</div>
                    <div class="kpi-value" id="count-total">0</div>
                    <div class="kpi-sub">Comptes enregistrés</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Abonnements Payés</div>
                    <div class="kpi-value" id="count-paid" style="color: var(--color-success)">0</div>
                    <div class="kpi-sub" id="month-name">Ce mois-ci</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Comptes Verrouillés</div>
                    <div class="kpi-value" id="count-locked" style="color: var(--color-danger)">0</div>
                    <div class="kpi-sub">Accès suspendu</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Alertes Usage</div>
                    <div class="kpi-value" id="count-alerts" style="color: var(--color-warning)">0</div>
                    <div class="kpi-sub">> 50 connexions</div>
                </div>
                <div class="kpi-card"
                    style="background: linear-gradient(135deg, var(--color-primary) 0%, #000 100%); color: #fff;">
                    <div class="kpi-label" style="color: rgba(255,255,255,0.7)">CA Mensuel Est.</div>
                    <div class="kpi-value" id="stat-revenue" style="color: #fff;">0,000 Dt</div>
                    <div class="kpi-sub" style="color: rgba(255,255,255,0.7)">Basé sur abonnements</div>
                </div>
            </div>

            <div class="chart-card full" style="margin-top: 2rem;">
                <div class="chart-title">Évolution des revenus (Simulation)</div>
                <div
                    style="height: 300px; display: flex; align-items: center; justify-content: center; color: var(--muted)">
                    <i class="fas fa-chart-area fa-3x" style="opacity: 0.2"></i>
                    <p style="margin-left: 1rem">Module de graphique en cours de développement...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Client Modal -->
    <div class="modal-overlay" id="clientModal">
        <div class="modal" style="max-width: 650px">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Nouveau Client</div>
                <button class="modal-close" onclick="closeModal('clientModal')"><i class="fas fa-times"></i></button>
            </div>
            <form id="clientForm">
                <div class="modal-body">
                    <div class="formContainer">
                        <div class="modal-form" style="padding: 0;">
                            <input type="hidden" name="action" value="saveClient">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>ID Client (Unique)</label>
                                    <input type="text" name="id_client" required>
                                </div>
                                <div class="form-group">
                                    <label>Code PIN</label>
                                    <input type="text" name="code_pin" required maxlength="20">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nom de l'établissement</label>
                                <input type="text" name="nom" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>Role / Description</label>
                                    <input type="text" name="role">
                                </div>
                                <div class="form-group">
                                    <label>Caisse ID</label>
                                    <input type="number" name="caisse_id" value="1">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Notes / Observations</label>
                                <textarea name="notes" rows="6" placeholder="Notes privées sur le client..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('clientModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary" style="min-width: 150px;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal" style="max-width: 1000px">
            <div class="modal-header">
                <div class="modal-title" id="paymentModalTitle">Gestion des Paiements</div>
                <button class="modal-close" onclick="closeModal('paymentModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 350px 1fr; gap: 2.5rem;">
                    <!-- Record Form -->
                    <form id="paymentForm" class="modal-form"
                        style="padding: 0; border-right: 1px solid var(--border); padding-right: 2rem;">
                        <div class="formContainerPaiement">
                            <input type="hidden" name="action" value="addPayment">

                            <div class="form-group" id="clientSelectGroup">
                                <label>Sélectionner un Client</label>
                                <select name="idClient" id="paymentIdClient" required class="form-control">
                                    <option value="">-- Choisir un client --</option>
                                </select>
                            </div>

                            <div id="paymentClientStatic"
                                style="display: none; padding: 12px; background: var(--surface2); border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--border);">
                                <span
                                    style="font-size: 0.7rem; color: var(--muted); display: block; text-transform: uppercase; font-weight: 800; margin-bottom: 4px;">Client
                                    sélectionné</span>
                                <span id="paymentClientName"
                                    style="font-weight: 700; color: var(--color-primary); font-size: 1.1rem;"></span>
                            </div>

                            <div class="form-group">
                                <label>Type de période</label>
                                <select name="periodType" id="periodType">
                                    <option value="month">Mensuel</option>
                                    <option value="year">Annuel</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Période (Mois ou Année)</label>
                                <input type="month" name="periodValue" id="periodValueMonth" value="<?= date('Y-m') ?>">
                                <input type="number" name="periodValueYear" id="periodValueYear"
                                    value="<?= date('Y') ?>" style="display: none" min="2020" max="2050">
                            </div>
                            <div class="form-group">
                                <label>Montant (Dt)</label>
                                <input type="number" name="amount" step="0.001" required placeholder="0.000">
                            </div>
                            <div class="form-group">
                                <label>Mode de paiement</label>
                                <select name="method">
                                    <option value="Espèces">Espèces</option>
                                    <option value="Chèque">Chèque</option>
                                    <option value="Virement">Virement</option>
                                    <option value="Offre Premier Mois">Offre Premier Mois</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Note</label>
                                <input type="text" name="admin_notes" placeholder="Optionnel...">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"
                            style="margin-top: 1rem; width: 100%; height: 45px;">Enregistrer Paiement</button>
                    </form>

                    <!-- History -->
                    <div id="historySection">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h4 style="color: var(--text); font-weight: 700;">Historique des transactions</h4>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <label style="font-size: 0.75rem; font-weight: 700; color: var(--muted)">Mois :</label>
                                <select id="historyMonthFilter" class="form-control"
                                    style="width: 110px; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 600;">
                                    <option value="">Tous</option>
                                    <option value="-01" <?= date('m') == '01' ? 'selected' : '' ?>>Janvier</option>
                                    <option value="-02" <?= date('m') == '02' ? 'selected' : '' ?>>Février</option>
                                    <option value="-03" <?= date('m') == '03' ? 'selected' : '' ?>>Mars</option>
                                    <option value="-04" <?= date('m') == '04' ? 'selected' : '' ?>>Avril</option>
                                    <option value="-05" <?= date('m') == '05' ? 'selected' : '' ?>>Mai</option>
                                    <option value="-06" <?= date('m') == '06' ? 'selected' : '' ?>>Juin</option>
                                    <option value="-07" <?= date('m') == '07' ? 'selected' : '' ?>>Juillet</option>
                                    <option value="-08" <?= date('m') == '08' ? 'selected' : '' ?>>Août</option>
                                    <option value="-09" <?= date('m') == '09' ? 'selected' : '' ?>>Septembre</option>
                                    <option value="-10" <?= date('m') == '10' ? 'selected' : '' ?>>Octobre</option>
                                    <option value="-11" <?= date('m') == '11' ? 'selected' : '' ?>>Novembre</option>
                                    <option value="-12" <?= date('m') == '12' ? 'selected' : '' ?>>Décembre</option>
                                </select>
                                <label style="font-size: 0.75rem; font-weight: 700; color: var(--muted)">Année :</label>
                                <input type="number" id="historyYearFilter" class="form-control"
                                    style="width: 80px; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 600;"
                                    value="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div id="paymentHistoryContainer">
                            <table class="payment-history-table">
                                <thead style="position: sticky; top: 0; background: var(--surface); z-index: 10;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Période</th>
                                        <th>Montant</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentHistoryBody"></tbody>
                            </table>
                        </div>
                        <div id="historyPlaceholder"
                            style="padding: 60px 20px; text-align: center; color: var(--muted); border: 2px dashed var(--border); border-radius: 16px; margin-top: 1rem;">
                            <i class="fas fa-user-circle fa-4x"
                                style="opacity: 0.2; margin-bottom: 1rem; display: block;"></i>
                            <p style="font-weight: 500;">Veuillez sélectionner un client pour afficher ses données
                                financières.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('paymentModal')">Fermer</button>
            </div>
        </div>
    </div>

    <script>
        let clients = [];
        let currentHistoryId = null;

        function switchTab(el, tabId) {
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));

            el.classList.add('active');
            const target = document.getElementById(`view-${tabId}`);
            if (target) target.classList.add('active');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('active');
        }

        async function fetchClients() {
            try {
                const res = await fetch('api_superadmin.php?action=listClients');
                if (!res.ok) throw new Error('Network response was not ok');
                clients = await res.json();
                renderClients();
                updateKPIs();
                updateClientSelect();
            } catch (e) {
                console.error('Error fetching clients:', e);
            }
        }

        function updateClientSelect() {
            const select = document.getElementById('paymentIdClient');
            if (!select) return;
            const currentVal = select.value;
            select.innerHTML = '<option value="">-- Choisir un client --</option>';
            clients.sort((a, b) => (a.nom || '').localeCompare(b.nom || '')).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_client;
                opt.textContent = `${c.id_client} - ${c.nom}`;
                select.appendChild(opt);
            });
            select.value = currentVal;
        }

        function renderClients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const tbody = document.getElementById('clientsTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';

            const filtered = clients.filter(c => {
                const id = (c.id_client || '').toLowerCase();
                const nom = (c.nom || '').toLowerCase();
                const role = (c.role || '').toLowerCase();
                return id.includes(searchTerm) || nom.includes(searchTerm) || role.includes(searchTerm);
            });

            filtered.forEach(c => {
                const connections = parseInt(c.connections_count || 0);
                const pctUsage = Math.min((connections / 60) * 100, 100);
                const usageClass = connections > 50 ? 'danger' : (connections > 30 ? 'warning' : '');

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div style="font-weight: 700; color: var(--color-primary)">${escapeHtml(c.id_client)}</div>
                        <div style="font-size: 0.8rem; font-weight: 600">${escapeHtml(c.nom)}</div>
                    </td>
                    <td>
                        <div style="font-size: 0.8rem">${escapeHtml(c.role || '-')}</div>
                        <div style="font-size: 0.7rem; color: var(--muted)">Caisse #${c.caisse_id}</div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="status-pill ${c.is_paid ? 'status-paid' : 'status-unpaid'}">
                                ${c.is_paid ? 'Payé' : 'Non Payé'}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 0.8rem; font-weight: 600">${connections} / 60</div>
                        <div class="usage-bar"><div class="usage-fill ${usageClass}" style="width: ${pctUsage}%"></div></div>
                    </td>
                    <td style="max-width: 200px; font-size: 0.75rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(c.notes || '') || '<span style="opacity: 0.5">Aucune note</span>'}
                    </td>
                    <td style="text-align: right">
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.75rem; margin-right: 8px; border-radius: 8px;" onclick="openPaymentModal('${c.id_client}')">
                            <i class="fas fa-money-bill-wave"></i> Paiements
                        </button>
                        <button class="action-btn" title="${c.is_locked ? 'Déverrouiller' : 'Verrouiller'}" onclick="toggleLock('${c.id_client}', ${c.is_locked ? 0 : 1})">
                            <i class="fas ${c.is_locked ? 'fa-lock-open' : 'fa-lock'}" style="color: ${c.is_locked ? 'var(--color-success)' : 'var(--color-warning)'}"></i>
                        </button>
                        <button class="action-btn" title="Modifier" onclick="editClient('${c.id_client}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-danger" title="Supprimer" onclick="deleteClient('${c.id_client}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function updateKPIs() {
            try {
                document.getElementById('count-total').textContent = clients.length;
                document.getElementById('count-paid').textContent = clients.filter(c => c.is_paid == 1).length;
                document.getElementById('count-locked').textContent = clients.filter(c => c.is_locked == 1).length;
                document.getElementById('count-alerts').textContent = clients.filter(c => (c.connections_count || 0) > 50).length;

                const revenue = clients.filter(c => c.is_paid == 1).length * 30;
                document.getElementById('stat-revenue').textContent = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 3 }).format(revenue) + " Dt";

                const months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
                document.getElementById('month-name').textContent = months[new Date().getMonth()];
            } catch (e) { console.error('Error updating KPIs:', e); }
        }

        async function toggleLock(idClient, status) {
            const fd = new FormData();
            fd.append('action', 'toggleLock');
            fd.append('idClient', idClient);
            fd.append('status', status);
            await fetch('api_superadmin.php', { method: 'POST', body: fd });
            fetchClients();
        }

        async function deleteClient(idClient) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) return;
            const fd = new FormData();
            fd.append('action', 'deleteClient');
            fd.append('id_client', idClient);
            await fetch('api_superadmin.php', { method: 'POST', body: fd });
            fetchClients();
        }

        function editClient(idClient) {
            const c = clients.find(item => item.id_client === idClient);
            if (!c) return;

            document.getElementById('modalTitle').textContent = "Modifier la fiche client";
            const form = document.getElementById('clientForm');
            form.id_client.value = c.id_client;
            form.id_client.readOnly = true;
            form.code_pin.value = c.code_pin;
            form.nom.value = c.nom;
            form.role.value = c.role || '';
            form.caisse_id.value = c.caisse_id || 1;
            form.notes.value = c.notes || '';
            document.getElementById('clientModal').classList.add('active');
        }

        async function openPaymentModal(idClient = null) {
            const modal = document.getElementById('paymentModal');
            const form = document.getElementById('paymentForm');
            form.reset();
            togglePeriodInput();

            if (idClient) {
                currentHistoryId = idClient;
                const c = clients.find(item => item.id_client === idClient);
                if (!c) return;
                document.getElementById('paymentModalTitle').textContent = "Gestion des Paiements — " + c.id_client;
                document.getElementById('clientSelectGroup').style.display = 'none';
                document.getElementById('paymentClientStatic').style.display = 'block';
                document.getElementById('paymentClientName').textContent = c.nom;
                document.getElementById('paymentIdClient').value = idClient;
                document.getElementById('historySection').style.display = 'block';
                document.getElementById('historyPlaceholder').style.display = 'none';
                await fetchPaymentHistory(idClient);
            } else {
                currentHistoryId = null;
                document.getElementById('paymentModalTitle').textContent = "Saisir un Paiement Rapide";
                document.getElementById('clientSelectGroup').style.display = 'block';
                document.getElementById('paymentClientStatic').style.display = 'none';
                document.getElementById('paymentIdClient').value = '';
                document.getElementById('historySection').style.display = 'block';
                document.getElementById('historyPlaceholder').style.display = 'block';
                document.getElementById('paymentHistoryBody').innerHTML = '';
            }

            modal.classList.add('active');
        }

        async function fetchPaymentHistory(idClient) {
            const yearFilter = document.getElementById('historyYearFilter').value;
            const monthFilter = document.getElementById('historyMonthFilter').value;
            const res = await fetch(`api_superadmin.php?action=getPaymentHistory&idClient=${idClient}`);
            let history = await res.json();
            const tbody = document.getElementById('paymentHistoryBody');
            tbody.innerHTML = '';

            // Client-side filtering by year and month
            if (yearFilter || monthFilter) {
                history = history.filter(p => {
                    const matchYear = !yearFilter || p.period_value.includes(yearFilter) || p.payment_date.includes(yearFilter);
                    const matchMonth = !monthFilter || p.period_value.includes(monthFilter) || p.payment_date.includes(monthFilter);
                    return matchYear && matchMonth;
                });
            }

            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--muted); padding: 40px; font-style: italic;">Aucune transaction trouvée pour ' + (yearFilter || 'cette période') + '.</td></tr>';
            } else {
                history.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${new Date(p.payment_date).toLocaleDateString()}</td>
                        <td><span class="status-pill status-paid">${p.period_type === 'year' ? 'Année ' + p.period_value : p.period_value}</span></td>
                        <td style="font-family: 'IBM Plex Mono'; font-weight: 700;">${parseFloat(p.amount).toFixed(3)} Dt</td>
                        <td>${p.payment_method}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }

        function togglePeriodInput() {
            const type = document.getElementById('periodType').value;
            document.getElementById('periodValueMonth').style.display = type === 'month' ? 'block' : 'none';
            document.getElementById('periodValueYear').style.display = type === 'year' ? 'block' : 'none';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btnNewClient').addEventListener('click', () => {
                document.getElementById('modalTitle').textContent = "Créer un nouveau compte client";
                document.getElementById('clientForm').reset();
                document.querySelector('[name="id_client"]').readOnly = false;
                document.getElementById('clientModal').classList.add('active');
            });

            document.getElementById('btnGlobalPayment').addEventListener('click', () => openPaymentModal());

            document.getElementById('searchInput').addEventListener('input', renderClients);

            document.getElementById('periodType').addEventListener('change', togglePeriodInput);

            document.getElementById('historyYearFilter').addEventListener('input', () => {
                if (currentHistoryId) fetchPaymentHistory(currentHistoryId);
            });

            document.getElementById('historyMonthFilter').addEventListener('change', () => {
                if (currentHistoryId) fetchPaymentHistory(currentHistoryId);
            });

            document.getElementById('paymentIdClient').addEventListener('change', (e) => {
                const id = e.target.value;
                if (id) {
                    currentHistoryId = id;
                    document.getElementById('historyPlaceholder').style.display = 'none';
                    fetchPaymentHistory(id);
                } else {
                    currentHistoryId = null;
                    document.getElementById('historyPlaceholder').style.display = 'block';
                    document.getElementById('paymentHistoryBody').innerHTML = '';
                }
            });

            document.getElementById('clientForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                await fetch('api_superadmin.php', { method: 'POST', body: fd });
                closeModal('clientModal');
                fetchClients();
            });

            document.getElementById('paymentForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const type = fd.get('periodType');
                const val = type === 'month' ? fd.get('periodValue') : fd.get('periodValueYear');
                fd.append('periodValue', val);

                const res = await fetch('api_superadmin.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.success) {
                    const idClient = fd.get('idClient');
                    if (idClient) await fetchPaymentHistory(idClient);
                    fetchClients();
                    e.target.reset();
                    togglePeriodInput();
                }
            });

            const themeToggle = document.getElementById('themeToggle');
            themeToggle.onclick = () => {
                const html = document.documentElement;
                const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', newTheme);
                themeToggle.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            };

            fetchClients();
        });
    </script>
</body>

</html>