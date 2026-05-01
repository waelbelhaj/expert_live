<?php
/**
 * api_dashboard.php
 * API JSON pour le Dashboard (indexv3.php). 
 * Remplace l'ancien système de lecture de fichiers par de pures requêtes SQL.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/clients.php'; // Pour récupérer les infos du groupe/caisse (steNom())

// 1. Paramètres de filtrage
$idClient  = $_GET['idClient'] ?? null;
if (!$idClient) {
    echo json_encode(["error" => "Client manquant"]);
    exit;
}

$dateD_str = $_GET['dateD'] ?? date("Y-m-d");
$timeD_str = $_GET['timeD'] ?? "00:00";
$dateF_str = $_GET['dateF'] ?? date("Y-m-d");
$timeF_str = $_GET['timeF'] ?? "23:59";

$dtStart = $dateD_str . ' ' . $timeD_str . ':00';
$dtEnd   = $dateF_str . ' ' . $timeF_str . ':59';

// 1.1 Support spécifique au filtrage par clôture
if (isset($_GET['filterType']) && $_GET['filterType'] === 'cloture' && !empty($_GET['cloture_id'])) {
    $clotId = (int)$_GET['cloture_id'];
    $stmtC = $pdo->prepare("SELECT date_debut, date_fin FROM clotures WHERE id = ?");
    $stmtC->execute([$clotId]);
    $clot = $stmtC->fetch();
    if ($clot) {
        // On convertit le format YYYYMMDDHHIISS en YYYY-MM-DD HH:II:SS
        $ds = $clot['date_debut'];
        $df = $clot['date_fin'];
        if (strlen($ds) >= 14) {
            $dtStart = substr($ds, 0, 4).'-'.substr($ds, 4, 2).'-'.substr($ds, 6, 2).' '.substr($ds, 8, 2).':'.substr($ds, 10, 2).':'.substr($ds, 12, 2);
        }
        if (strlen($df) >= 14) {
            $dtEnd = substr($df, 0, 4).'-'.substr($df, 4, 2).'-'.substr($df, 6, 2).' '.substr($df, 8, 2).':'.substr($df, 10, 2).':'.substr($df, 12, 2);
        }
    }
}

$oneHourAgo = date("Y-m-d H:i:s", time() - 3600);

// Groupe d'IDs si le client est "Master"
$nomGroupe = "";
$idsGroupe = [];
foreach ($users as $u => $p) {
    if ($p[1] == $idClient) {
        $nomGroupe = $p[2];
        break;
    }
}
if ($nomGroupe) {
    foreach ($users as $u => $p) {
        if ($p[2] == $nomGroupe) {
            $idsGroupe[] = $p[1]; // String ID
            if (isset($p[4]) && !empty($p[4])) $idsGroupe[] = (string)$p[4]; // Numeric Caisse ID
        }
    }
} else {
    // Fallback for dynamically added clients
    try {
        $stmtFallback = $pdo->prepare("SELECT nom, id_client, caisse_id FROM clients WHERE id_client = ? OR caisse_id = ? LIMIT 1");
        $stmtFallback->execute([$idClient, $idClient]);
        $dbClient = $stmtFallback->fetch();
        if ($dbClient) {
            $nomGroupe = $dbClient['nom'];
            $idsGroupe[] = $dbClient['id_client'];
            if (!empty($dbClient['caisse_id'])) $idsGroupe[] = (string)$dbClient['caisse_id'];
        }
    } catch (Exception $e) {}
}

if (empty($idsGroupe)) {
    $idsGroupe[] = $idClient;
} else if (!in_array($idClient, $idsGroupe)) {
    $idsGroupe[] = $idClient;
}
$idsGroupe = array_unique($idsGroupe); // Remove duplicates

// Sécurisation de la clause IN (..., ..., ...)
$inIds = str_repeat('?,', count($idsGroupe) - 1) . '?';
$paramsDate = array_merge($idsGroupe, [$dtStart, $dtEnd]);

// --- STATS INITIALISATION ---
$stats = [
    "total"          => 0,
    "totalDeleted"   => 0,
    "totalRemise"    => 0,
    "count"          => 0,
    "countDeleted"   => 0,
    "ticketMax"      => 0,
    "ticketMin"      => 0, // Va être ecrasé, default SQL
    "steTotal"       => [],
    "steNbr"         => [],
    "vendeurTotal"   => [],
    "vendeurNbr"     => [],
    "bestSel"        => [],
    "bestSelTotal"   => [],
    "totalPay"       => [],
    "familles"       => [],
    "clients"        => [],
    "hourly"         => array_fill(0, 24, 0),
    "depenses"       => ["avances" => 0, "consommables" => 0],
    "avances"        => [],
    "consommables"   => [],
    "dec"            => 3,
    "ticketsLastHour"=> 0,
    "ticketMaxRecord" => null,
    "dailyCurrent"   => array_fill(1, 31, 0),
    "dailyPrevious"  => array_fill(1, 31, 0),
    "dailyLastYear"  => array_fill(1, 31, 0),
];

// Record ticket (This month for this group)
$startOfMonth = date("Y-m-01 00:00:00");
$endOfMonth   = date("Y-m-t 23:59:59");

$sqlMax = "SELECT total, num_ticket, date_ticket, vendeur, caisse_nom, client_id FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 ORDER BY total DESC LIMIT 1";
$stmtMax = $pdo->prepare($sqlMax);
$stmtMax->execute(array_merge($idsGroupe, [$startOfMonth, $endOfMonth]));
$rowMax = $stmtMax->fetch();
if ($rowMax) {
    $cnom = $rowMax['caisse_nom'] ?: '?';
    $clientNom = 'Inconnu';
    foreach ($users as $u => $p) { 
        if ($p[1] == $rowMax['client_id']) {
            $cnom = $p[3]; 
            $clientNom = $p[2];
        } 
    }
    $stats["ticketMaxRecord"] = [
        "total" => (float)$rowMax['total'],
        "num"   => $rowMax['num_ticket'],
        "date"  => date("d/m/Y", strtotime($rowMax['date_ticket'])),
        "vendeur" => $rowMax['vendeur'],
        "caisse"  => $cnom,
        "client"  => $clientNom
    ];
}

// Daily Comparison (Current month vs Previous month)
$startPrev = date("Y-m-01 00:00:00", strtotime("first day of last month"));
$endPrev   = date("Y-m-t 23:59:59", strtotime("last day of last month"));

// Current Month Days
$sqlDailyCur = "SELECT DAY(date_ticket) as d, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 GROUP BY DAY(date_ticket)";
$stmt = $pdo->prepare($sqlDailyCur);
$stmt->execute(array_merge($idsGroupe, [$startOfMonth, $endOfMonth]));
while($r = $stmt->fetch()) $stats["dailyCurrent"][(int)$r['d']] = (float)$r['s'];

// Previous Month Days
$sqlDailyPrev = "SELECT DAY(date_ticket) as d, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 GROUP BY DAY(date_ticket)";
$stmt = $pdo->prepare($sqlDailyPrev);
$stmt->execute(array_merge($idsGroupe, [$startPrev, $endPrev]));
while($r = $stmt->fetch()) $stats["dailyPrevious"][(int)$r['d']] = (float)$r['s'];

// Last Year Month
$startLastYear = date("Y-m-01 00:00:00", strtotime("-1 year"));
$endLastYear   = date("Y-m-t 23:59:59", strtotime("-1 year"));
$sqlDailyLY = "SELECT DAY(date_ticket) as d, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 GROUP BY DAY(date_ticket)";
$stmt = $pdo->prepare($sqlDailyLY);
$stmt->execute(array_merge($idsGroupe, [$startLastYear, $endLastYear]));
while($r = $stmt->fetch()) $stats["dailyLastYear"][(int)$r['d']] = (float)$r['s'];

// Convert to zero-indexed arrays for Chart.js
$stats["dailyCurrent"]  = array_values($stats["dailyCurrent"]);
$stats["dailyPrevious"] = array_values($stats["dailyPrevious"]);
$stats["dailyLastYear"] = array_values($stats["dailyLastYear"]);


// --- 2. RÉCUPÉRATION DES STATS GLOBALES (TICKETS NON SUPPRIMÉS) ---
$sqlStats = "
    SELECT 
        COUNT(id) AS qte_tickets,
        COALESCE(SUM(total), 0) AS ca_total,
        COALESCE(MAX(total), 0) AS max_t,
        COALESCE(MIN(total), 0) AS min_t,
        SUM(CASE WHEN date_ticket >= ? THEN 1 ELSE 0 END) AS tickets_1h
    FROM tickets
    WHERE client_id IN ($inIds)
      AND date_ticket BETWEEN ? AND ?
      AND deleted = 0
";
$pStats = array_merge([$oneHourAgo], $idsGroupe, [$dtStart, $dtEnd]);
$stmt = $pdo->prepare($sqlStats);
$stmt->execute($pStats);
$rowStats = $stmt->fetch();

$stats["total"]           = (float)$rowStats['ca_total'];
$stats["count"]           = (int)$rowStats['qte_tickets'];
$stats["ticketMax"]       = (float)$rowStats['max_t'];
$stats["ticketMin"]       = (float)$rowStats['min_t'];
$stats["ticketsLastHour"] = (int)$rowStats['tickets_1h'];

// Stats Supprimés
$sqlDel = "SELECT COUNT(id) as c, COALESCE(SUM(total),0) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 1";

$stmt = $pdo->prepare($sqlDel);
$stmt->execute($paramsDate);
$rowDel = $stmt->fetch();
$stats["countDeleted"] = (int)$rowDel['c'];
$stats["totalDeleted"] = (float)$rowDel['s'];

// Répartition par Caisses
$sqlCaisse = "SELECT MAX(caisse_nom) as caisse_nom, client_id, COUNT(id) as c, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 GROUP BY client_id";
$stmt = $pdo->prepare($sqlCaisse);
$stmt->execute($paramsDate);
while($r = $stmt->fetch()) {
    // Retrouver le vrai nom s'il n'est pas passé textuellement
    $cnom = $r['caisse_nom'] ?: '?';
    foreach ($users as $u => $p) { if ($p[1] == $r['client_id']) $cnom = $p[3]; }
    $stats["steTotal"][$cnom] = (float)$r['s'];
    $stats["steNbr"][$cnom]   = (int)$r['c'];
}

// Répartition par Vendeurs
$sqlVend = "SELECT vendeur, COUNT(id) as c, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 GROUP BY vendeur";
$stmt = $pdo->prepare($sqlVend);
$stmt->execute($paramsDate);
while($r = $stmt->fetch()) {
    $v = $r['vendeur'] ?: 'Inconnu';
    $stats["vendeurTotal"][$v] = (float)$r['s'];
    $stats["vendeurNbr"][$v]   = (int)$r['c'];
}

// Répartition par type paiement (SQL pur)
$sqlPay = "SELECT type_paiement, COUNT(id) as c, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND date_ticket BETWEEN ? AND ? AND deleted = 0 GROUP BY type_paiement";
$stmt = $pdo->prepare($sqlPay);
$stmt->execute($paramsDate);
$typePaiementMap = [
    1 => "Espèces", 2 => "Chèque", 3 => "Traite bancaire", 4 => "Carte bancaire",
    5 => "Ticket resto", 6 => "Offres", 7 => "Crédit", 8 => "Fidélité"
];
while($r = $stmt->fetch()) {
    $tpId = $r['type_paiement'];
    $tpName = (is_numeric($tpId) && isset($typePaiementMap[(int)$tpId])) ? $typePaiementMap[(int)$tpId] : $tpId;
    if (!$tpName) $tpName = "Espèces";
    if (!isset($stats["totalPay"][$tpName])) $stats["totalPay"][$tpName] = ["total"=>0,"count"=>0];
    $stats["totalPay"][$tpName]["total"] += (float)$r['s'];
    $stats["totalPay"][$tpName]["count"] += (int)$r['c'];
}

// Hourly (Heures de la journée) sur TOUTES les ventes (historique global)
$sqlHour = "SELECT HOUR(date_ticket) as h, SUM(total) as s FROM tickets WHERE client_id IN ($inIds) AND deleted = 0 GROUP BY HOUR(date_ticket)";
$stmt = $pdo->prepare($sqlHour);
$stmt->execute($idsGroupe);
while($r = $stmt->fetch()) {
    $stats["hourly"][(int)$r['h']] = (float)$r['s'];
}


// --- 3. DÉTAILS PRODUITS (Meilleures Ventes / Familles / Remises) ---
$sqlDet = "
    SELECT 
        d.nom_produit, d.famille, 
        SUM(d.qte) as sum_qte, 
        SUM((d.prix_u_remise * (1 + d.tva/100)) * d.qte) as sum_total,
        SUM((d.prix_u - d.prix_u_remise) * d.qte) as sum_remise
    FROM ticket_details d
    INNER JOIN tickets t ON t.id = d.ticket_id
    WHERE t.client_id IN ($inIds)
      AND t.date_ticket BETWEEN ? AND ?
      AND t.deleted = 0
    GROUP BY d.nom_produit, d.famille
";
$stmt = $pdo->prepare($sqlDet);
$stmt->execute($paramsDate);
while($r = $stmt->fetch()) {
    $nom = $r['nom_produit'];
    $fam = $r['famille'];
    if ($nom) {
        $stats["bestSel"][$nom]      = (float)$r['sum_qte'];
        $stats["bestSelTotal"][$nom] = (float)$r['sum_total'];
    }
    if ($fam) {
        $stats["familles"][$fam] = ($stats["familles"][$fam] ?? 0) + (float)$r['sum_total'];
    }
    $stats["totalRemise"] += (float)$r['sum_remise'];
}
arsort($stats["bestSel"]);
arsort($stats["familles"]);


// --- 4. DÉPENSES ---
$sqlDep = "SELECT * FROM depenses WHERE client_id IN ($inIds) AND date_depense BETWEEN ? AND ?";
$stmt = $pdo->prepare($sqlDep);
$stmt->execute(array_merge($idsGroupe, [$dtStart, $dtEnd]));
while($r = $stmt->fetch()) {
    if ($r['type_depense'] === 'utilisateur') {
        $stats["depenses"]["avances"] += (float)$r['total'];
        $stats["avances"][] = ["nom" => $r['nom'], "montant" => (float)$r['total']];
    } else {
        $stats["depenses"]["consommables"] += (float)$r['total'];
        $stats["consommables"][] = ["nom" => $r['nom'], "qte" => (float)$r['qte'], "montant" => (float)$r['total']];
    }
}

// --- 5. CLÔTURES (Filtrées par mois/année pour le sélecteur) ---
$clotures = [];
$clotMonth = $_GET['cloture_month'] ?? date('m');
$clotYear  = $_GET['cloture_year'] ?? date('Y');

// On cherche les clôtures dont la date_fin (YYYYMMDD...) commence par l'année et le mois choisis
$pattern = $clotYear . str_pad($clotMonth, 2, '0', STR_PAD_LEFT) . '%';
$sqlClot = "SELECT * FROM clotures WHERE client_id IN ($inIds) AND date_fin LIKE ? ORDER BY date_fin DESC";
$stmt = $pdo->prepare($sqlClot);
$stmt->execute(array_merge($idsGroupe, [$pattern]));

while($r = $stmt->fetch()) {
    // Determiner la caisse via users mapping
    $cnom = '?';
    foreach ($users as $u => $p) { if ($p[1] == $r['client_id']) $cnom = $p[3]; }
    
    $clotures[] = [
        "id"        => $r['id'],
        "caisse"    => $cnom,
        "num"       => $r['num'],
        "valeur"    => (float)$r['valeur'],
        "date_debut"=> formatClotureDate($r['date_debut']),
        "date_fin"  => formatClotureDate($r['date_fin'])
    ];
}

function formatClotureDate($str) {
    if (!$str || strlen($str) < 14) return $str;
    return substr($str, 6, 2).'/'.substr($str, 4, 2).'/'.substr($str, 0, 4).' '.substr($str, 8, 2).':'.substr($str, 10, 2);
}

// --- 6. TICKETS RÉCENTS (Liste des X derniers ou tous de la journée) ---
$tickets = [];
$sqlTick = "
    SELECT t.*, 
    (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'nom', d.nom_produit, 
                'qte', d.qte, 
                'prix_ht', d.prix_u_remise,
                'remise', d.remise,
                'prix_ttc', (d.prix_u_remise * (1 + (d.tva/100))),
                'total', ((d.prix_u_remise * (1 + (d.tva/100))) * d.qte)
            )
        ) FROM ticket_details d WHERE d.ticket_id = t.id
    ) AS prods_json,
    (SELECT SUM(qte) FROM ticket_details d WHERE d.ticket_id = t.id) AS count_articles,
    (SELECT SUM(prix_u_remise * qte) FROM ticket_details d WHERE d.ticket_id = t.id) AS total_ht,
    (SELECT SUM(((prix_u_remise * (1 + (tva/100))) - prix_u_remise) * qte) FROM ticket_details d WHERE d.ticket_id = t.id) AS total_tva
    FROM tickets t
    WHERE t.client_id IN ($inIds)
      AND t.date_ticket BETWEEN ? AND ?
    ORDER BY t.date_ticket DESC LIMIT 200
"; // Limite à 200 pour UI performance, mais la stat globale contient tout
$stmt = $pdo->prepare($sqlTick);
$stmt->execute($paramsDate);
while($row = $stmt->fetch()) {
    $cnom = $row['caisse_nom'] ?: '?';
    foreach ($users as $u => $p) { if ($p[1] == $row['client_id']) $cnom = $p[3]; }
    
    $tpId = $row['type_paiement'];
    $tpName = (is_numeric($tpId) && isset($typePaiementMap[(int)$tpId])) ? $typePaiementMap[(int)$tpId] : $tpId;

    $tickets[] = [
        "num"      => $row['num_ticket'],
        "time"     => date("H:i", strtotime($row['date_ticket'])),
        "date"     => date("d/m/Y H:i", strtotime($row['date_ticket'])),
        "caisse"   => $cnom,
        "vendeur"  => $row['vendeur'],
        "paiement" => $tpName ?: 'Espèces',
        "total"    => (float)$row['total'],
        "total_ht" => (float)$row['total_ht'],
        "total_tva"=> (float)$row['total_tva'],
        "articles" => (float)$row['count_articles'],
        "deleted"  => (bool)$row['deleted'],
        "produits" => json_decode($row['prods_json'] ?: '[]', true)
    ];
}

// --- 7. LICENSE & SUBSCRIPTION INFO ---
$license = [
    "is_paid" => false,
    "connections_remaining" => 60,
    "next_payment" => date('01/m/Y', strtotime('first day of next month'))
];

try {
    $stmtSub = $pdo->prepare("SELECT MAX(is_paid) as is_paid, SUM(connections_count) as connections_count FROM subscriptions WHERE client_id IN ($inIds) AND month = ?");
    $stmtSub->execute(array_merge($idsGroupe, [date('Y-m')]));
    $subInfo = $stmtSub->fetch();
    if ($subInfo && $subInfo['connections_count'] !== null) {
        $license["is_paid"] = (bool)$subInfo['is_paid'];
        $license["connections_remaining"] = max(0, 60 - (int)$subInfo['connections_count']);
    }
} catch (Exception $e) {}

$license['debug_ids'] = $idsGroupe;

// REPONSE FINALE
echo json_encode([
    "stats" => $stats,
    "tickets" => $tickets,
    "clotures" => $clotures,
    "license" => $license
]);
