<?php
/////////////////////// API d'insertion de tickets et dépenses pour Expert Gestion POS ////////////////////
///// =======================> dashboard.php //////////
file_put_contents(__DIR__ . '/debug_pos.txt', date('Y-m-d H:i:s') . " - NEW REQUEST\n" . print_r($_REQUEST, true) . "\n", FILE_APPEND);

require_once __DIR__ . '/db.php';

$action = isset($_REQUEST['action']) ? (int)$_REQUEST['action'] : 0;
$idClient = isset($_REQUEST['idSte']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_REQUEST['idSte']) : 'ERROR';

if ($action <= 0 || $idClient === 'ERROR') {
    file_put_contents(__DIR__ . '/debug_pos.txt', "ERREUR: Paramètres invalides. Action: $action, idClient: $idClient\n", FILE_APPEND);
    die("Paramètres invalides");
}

switch ($action) {
    case 1: // Ajout / Modification Ticket
        $ticketIdStr = $_REQUEST['ticket'] ?? '';
        if (!$ticketIdStr || !isset($_REQUEST['d'])) {
            file_put_contents(__DIR__ . '/debug_pos.txt', "ERREUR: Données du ticket manquantes! ticket=$ticketIdStr, isset(d)=".isset($_REQUEST['d'])."\n", FILE_APPEND);
            die("Données du ticket manquantes");
        }
        
        $d_str = $_REQUEST['d'] ?? $_REQUEST['data'] ?? '';
        
        // CORRECTION MAJEURE: Si la trame envoyée par Windev est en UTF-16 ou corrompue,
        // elle contient des espaces ou des caractères nuls (\0) invisibles entre chaque caractère normal.
        $d_str = str_replace("\0", "", $d_str); // Nettoie les bytes nuls (UTF-16 bug)
        
        // Certains Proxies/IIS convertissent les \0 Null Bytes en vrais espaces ' ' (0x20).
        // Le JSON " { " est invalide. On vérifie si la trame est URLEncoded:
        if (strpos($d_str, '%') !== false) {
             // S'il y a des espaces parasites dans un format url-encoded (ex: % 7 B au lieu de %7B)
             $d_str = str_replace(' ', '', $d_str);
             $d_str = urldecode($d_str);
        }

        $ticketData = json_decode($d_str, true);
        
        // Fallbacks historiques
        if (!$ticketData) {
            $d_str = stripslashes($d_str);
            $ticketData = json_decode($d_str, true);
        }
        
        if (!$ticketData) {
            $ticketData = json_decode(mb_convert_encoding($d_str, 'UTF-8', 'ISO-8859-1'), true);
        }
        if (!$ticketData) {
            file_put_contents(__DIR__ . '/debug_pos.txt', "ERREUR: Decodage JSON impossible sur la chaine d_str.\n", FILE_APPEND);
            die("Erreur de décodage JSON");
        }
        
        file_put_contents(__DIR__ . '/debug_pos.txt', "JSON OK! Parsing data...\n", FILE_APPEND);

        $num_ticket    = (int)($ticketData['num'] ?? $ticketIdStr);
        $total         = (float)($ticketData['Total'] ?? 0);
        $vendeur       = $ticketData['vendeur'] ?? 'Inconnu';
        $type_paiement = $ticketData['Type_payements'] ?? ($ticketData['Type_payement'] ?? '0');
        $deleted       = (isset($ticketData['supprimer']) && (int)$ticketData['supprimer'] === 1) ? 1 : 0;
        
        // Extraction caisse_nom depuis TotalYello si présent
        $caisse_nom = '';
        if (isset($ticketData['TotalYello']) && is_string($ticketData['TotalYello'])) {
            $ty = urldec_local($ticketData['TotalYello']);
            // Regex plus flexible pour attraper le nom après "Caisse :"
            if (preg_match('/Caisse\s*[:\-]\s*([^\|]+)/i', $ty, $m)) {
                $caisse_nom = trim($m[1]);
            } else {
                $caisse_nom = $ty;
            }
        }
        if (!$caisse_nom) {
            $caisse_nom = urldec_local($ticketData['nomCaisse'] ?? ($ticketData['CaisseNom'] ?? ''));
        }
        
        $id_cloture    = (isset($ticketData['cloture']) && is_array($ticketData['cloture'])) ? (int)$ticketData['cloture'][0] : 0;
        $historique    = $ticketData['Historique'] ?? '';
        
        // Formatter la date du ticket depuis l'Historique YYYYMMDDHHmmss
        $date_ticket = date('Y-m-d H:i:s');
        if (strlen($historique) >= 14) {
            $date_ticket = substr($historique, 0, 4) . '-' . substr($historique, 4, 2) . '-' . substr($historique, 6, 2) . ' ' . 
                           substr($historique, 8, 2) . ':' . substr($historique, 10, 2) . ':' . substr($historique, 12, 2);
        }

        try {
            // Lancer une transaction pour assurer que le ticket et les détails s'insèrent correctement
            $pdo->beginTransaction();

            // Supprimer l'ancienne version s'il s'agit d'une modification/réinsertion (au lieu d'Update complet)
            $delStmt = $pdo->prepare("DELETE FROM tickets WHERE client_id = ? AND num_ticket = ?");
            $delStmt->execute([$idClient, $num_ticket]);

            // Insérer le ticket
            $stmt = $pdo->prepare("
                INSERT INTO tickets (client_id, num_ticket, date_ticket, vendeur, type_paiement, total, deleted, id_cloture, historique_id, caisse_nom)
                VALUES (:client, :num, :dt, :vendeur, :paiement, :total, :del, :cloture, :hist, :caisse)
            ");
            $stmt->execute([
                ':client'   => $idClient,
                ':num'      => $num_ticket,
                ':dt'       => $date_ticket,
                ':vendeur'  => urldec_local($vendeur),
                ':paiement' => urldec_local($type_paiement),
                ':total'    => $total,
                ':del'      => $deleted,
                ':cloture'  => $id_cloture,
                ':hist'     => $historique,
                ':caisse'   => $caisse_nom
            ]);
            
            $newTicketId = $pdo->lastInsertId();

            // Insérer les produits
            if (isset($ticketData['data']) && is_array($ticketData['data'])) {
                $stmtDet = $pdo->prepare("
                    INSERT INTO ticket_details (ticket_id, nom_produit, famille, qte, prix_u, prix_u_remise, tva, remise)
                    VALUES (:tid, :nom, :fam, :qte, :pu, :pu_rem, :tva, :remise)
                ");

                foreach ($ticketData['data'] as $k => $p) {
                    $stmtDet->execute([
                        ':tid'    => $newTicketId,
                        ':nom'    => urldec_local($p['nom'] ?? 'Produit Inconnu'),
                        ':fam'    => urldec_local($p['Famille'] ?? ''),
                        ':qte'    => (float)($p['qte'] ?? 1),
                        ':pu'     => (float)($p['prix_u'] ?? 0),
                        ':pu_rem' => (float)($p['prix_u_remise'] ?? 0),
                        ':tva'    => (float)($p['tva'] ?? 0),
                        ':remise' => (float)($p['remise'] ?? 0)
                    ]);
                }
            }
            
            // Gerer la cloture separemment
            if ($id_cloture > 0 && is_array($ticketData['cloture']) && count($ticketData['cloture']) >= 4) {
                $clot_total = (float)$ticketData['cloture'][1];
                $clot_fin   = $ticketData['cloture'][2];
                $clot_deb   = $ticketData['cloture'][3];
                
                // Utilisation de INSERT IGNORE (ou ON DUPLICATE KEY UPDATE) pour éviter les doublons sur num_cloture + client
                $stmtCloture = $pdo->prepare("
                    INSERT INTO clotures (client_id, num, valeur, date_debut, date_fin)
                    VALUES (:client, :num, :val, :deb, :fin)
                    ON DUPLICATE KEY UPDATE 
                        valeur = :val2, date_debut = :deb2, date_fin = :fin2
                ");
                $stmtCloture->execute([
                    ':client' => $idClient,
                    ':num'    => $id_cloture,
                    ':val'    => $clot_total,
                    ':deb'    => $clot_deb,
                    ':fin'    => $clot_fin,
                    ':val2'   => $clot_total,
                    ':deb2'   => $clot_deb,
                    ':fin2'   => $clot_fin
                ]);
            }

            $pdo->commit();
            file_put_contents(__DIR__ . '/debug_pos.txt', "SUCCESS! Inséré ticket $newTicketId\n", FILE_APPEND);
            echo "OK: $newTicketId";

        } catch (\PDOException $e) {
            $pdo->rollBack();
            file_put_contents(__DIR__ . '/debug_pos.txt', "EXCEPTION BDD Action 1: " . $e->getMessage() . "\n", FILE_APPEND);
            die("DB Error Action 1");
        }
        break;

    case 2: // Suppression d'un ticket (Marquer comme supprimé)
        $ticketIdStr = $_REQUEST['ticket'] ?? '';
        if (!$ticketIdStr) die("Ticket missing");

        try {
            $stmt = $pdo->prepare("UPDATE tickets SET deleted = 1 WHERE client_id = ? AND num_ticket = ?");
            $stmt->execute([$idClient, (int)$ticketIdStr]);
            echo "DELETED: $ticketIdStr";
        } catch (\PDOException $e) {
            error_log("DB API Action 2 Error: " . $e->getMessage());
            die("DB Error Action 2");
        }
        break;

    case 3: // Modification -> Actuellement non implementé par la POS sous le code 3, mais le 1 l'écrase
    case 4: // Dépenses
        $depTypeStr = $_REQUEST['dep'] ?? '';
        $datas = $_REQUEST; // l'ancien code sauvegardait l'array en JSON

        try {
            // L'old code stockait les depenses sous un ID.
            // $_REQUEST est un tableau. Il faut parser chaque ligne de depense (action==4)
            // Dans logs_viewer, le json contenait un array avec: [id_dep, nom, qte, type_depense, montant]

            // Si la POS envoie une string 'depense' ou un array 'depense'
            // Ex: $datas = json_encode($_REQUEST); $data = explode('"depense":', $datas);
            $depenseDataRaw = $_REQUEST['depense'] ?? null;
            
            if ($depenseDataRaw && is_string($depenseDataRaw)) {
                $depenseDataRaw = stripslashes($depenseDataRaw);
                $dData = json_decode($depenseDataRaw, true);
            } else {
                $dData = $depenseDataRaw; 
            }

            if (is_array($dData)) {
                $stmt = $pdo->prepare("
                    INSERT INTO depenses (client_id, date_depense, type_depense, nom, qte, total)
                    VALUES (:client, :dt, :type, :nom, :qte, :total)
                ");
                
                $dt = date('Y-m-d H:i:s');
                foreach ($dData as $row) {
                    // Structure typique: [ 0 => 'id', 1 => 'nom', 2 => 'qte', 3 => 'utilisateur/produit', 4 => 'montant/total' ]
                    if (is_array($row) && count($row) >= 5) {
                        $stmt->execute([
                            ':client' => $idClient,
                            ':dt'     => $dt,
                            ':type'   => $row[3],
                            ':nom'    => $row[1],
                            ':qte'    => (float)$row[2],
                            ':total'  => (float)$row[4]
                        ]);
                    }
                }
            }
            echo "DEPENSES SAVED";
        } catch (\PDOException $e) {
            error_log("DB API Action 4 Error: " . $e->getMessage());
            die("DB Error Action 4");
        }
        break;

    default:
        die("Invalid Action");
}

// Helper local pour décoder les caractères URL de la Caisse POS
function urldec_local($url) {
    if (!is_string($url)) return '';
    $map = ["%20"=>" ","%26"=>"&","%EF"=>"ï","%E9"=>"é","%E8"=>"è","%EA"=>"ê",
            "%E0"=>"à","%E2"=>"â","%E7"=>"ç","%F1"=>"ñ","%F4"=>"ô","%C9"=>"É",
            "%2B"=>"+","%CF"=>"Ï","%8C"=>"Œ","%7B"=>"{","%7D"=>"}","%92"=>"'",
            "%9C"=>"œ","%B0"=>"°","%EE"=>"î"];
    return str_replace(array_keys($map), array_values($map), $url);
}
