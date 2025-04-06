<?php
include('../../../inc/includes.php');

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

function insertSurveyData(array $data) {
    global $DB;

    //if (!empty($data['fabricant']) || !empty($data['date_start']) || !empty($data['date_end'])) {
        $count = countElementsInTable('glpi_plugin_warrantycheck_tickets', ['serial_number' => $data['serial_number']]);

        $serial =  $data['serial_number'];
        if ($count > 0 && !empty($data['tickets_id'])) {
            // Récupérer la liste actuelle des tickets
            if ($query = $DB->query("SELECT id, tickets_id FROM glpi_plugin_warrantycheck_tickets WHERE serial_number = '$serial'")->fetch_object()) {
            
                $existing_id      = $query->id;
                $existing_tickets = array_map('trim', explode(',', $query->tickets_id)); // ✅ bonne colonne
            
                // Vérifie si le nouveau ticket est déjà présent
                if (!in_array($data['tickets_id'], $existing_tickets)) {
                    // Ajout du nouveau ticket
                    $updated_tickets = implode(',', array_merge($existing_tickets, [$data['tickets_id']]));
            
                    $update_sql = "UPDATE glpi_plugin_warrantycheck_tickets SET tickets_id = ? WHERE id = ?";
                    $update_stmt = $DB->prepare($update_sql);
                    try {
                        $update_stmt->execute([$updated_tickets, $existing_id]);
                    } catch (Throwable $e) {
                        Toolbox::logDebug("PluginWarrantyCheck", "Update error: " . $e->getMessage());
                    }
                }
            }           
        } elseif ($count == 0) {
            // Insertion initiale si aucun serial_number trouvé
            $data['tickets_id']  = $data['tickets_id']  ?? 0;
            $data['fabricant']   = $data['fabricant']   ?? 'Inconnu';
            $data['model']       = $data['model']       ?? 'Inconnu';
            $data['date_start']  = $data['date_start']  ?? NULL;
            $data['date_end']    = $data['date_end']    ?? NULL;

            $fields       = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $values       = array_values($data);

            $sql = "INSERT INTO glpi_plugin_warrantycheck_tickets (" . implode(',', $fields) . ")
                    VALUES (" . implode(',', $placeholders) . ")";

            try {
                $stmt = $DB->prepare($sql);
                $stmt->execute($values);
            } catch (Throwable $e) {
                Toolbox::logDebug("PluginWarrantyCheck", "Insert error: " . $e->getMessage());
            }
        }
    //}
}

function select($serial, $Manufacturer){
    // Appeler la méthode appropriée en fonction du constructeur
    switch ($Manufacturer) {
        case 'HP':
            $checker = new HpWarrantyChecker();
            return $checker->getNormalized($serial);
        case 'Lenovo':
            return LenovoWarranty::getNormalized($serial);
        case 'Dell':
            $checker = new DellWarrantyChecker();
            return $checker->getNormalized($serial);
        case 'Dynabook':
            return DynabookWarranty::getNormalized($serial);
        case 'Terra':
            return WarrantyChecker_Terra::check($serial);
        // Ajoutez d'autres cas pour les constructeurs supplémentaires
    }

    return null;
}

function detectBrand($serial, $Manufacturer) {
    // Convertir le numéro de série en majuscules pour une comparaison insensible à la casse
    $serial = strtoupper($serial);
    $config = new PluginWarrantycheckConfig();
    
    if ($Manufacturer === NULL){
        // Convertir les chaînes de préfixes en tableaux
        $hpPrefixes = $config->Filtre_HP() ? explode(',', $config->Filtre_HP()) : [];
        $lenovoPrefixes = $config->Filtre_Lenovo() ? explode(',', $config->Filtre_Lenovo()) : [];
        $dellPrefixes = $config->Filtre_Dell() ? explode(',', $config->Filtre_Dell()) : [];
        $dynabookPrefixes = $config->Filtre_Dynabook() ? explode(',', $config->Filtre_Dynabook()) : [];
        $terraPrefixes = $config->Filtre_Terra() ? explode(',', $config->Filtre_Terra()) : [];

        // Tableau des préfixes associés à chaque constructeur
        $brandPrefixes = [
            'HP' => $hpPrefixes,
            'Lenovo' => $lenovoPrefixes,
            'Dell' => $dellPrefixes, 
            'Dynabook' => $dynabookPrefixes,
            'Terra' => $terraPrefixes,
        ];

        // Variable pour suivre le constructeur par défaut
        $defaultBrand = null;

        // Parcourir chaque constructeur et vérifier les préfixes
        foreach ($brandPrefixes as $brand => $prefixes) {
            foreach ($prefixes as $prefix) {
                // Vérifier si le numéro de série commence par le préfixe
                if (strpos($serial, $prefix) === 0) {
                    if ($prefix !== '') {
                        // Si un préfixe non vide correspond, utiliser ce constructeur
                        $_SESSION[$serial] = $brand;
                        return select($serial, $brand);
                    } else {
                        // Si un préfixe vide est trouvé, le marquer comme constructeur par défaut
                        $defaultBrand = $brand;
                    }
                }
            }
        }

        // Si aucun préfixe non vide ne correspond, utiliser le constructeur par défaut
        if ($defaultBrand !== null) {
            $_SESSION[$serial] = $defaultBrand;
            return select($serial, $defaultBrand);
        }

        return null;
    }else{
        $_SESSION[$serial] = $Manufacturer;
        return select($serial, $Manufacturer);
    }
}

class LenovoWarranty extends CommonDBTM {
    public static function getWarrantyInfo($serial) {
        $url = "https://pcsupport.lenovo.com/products/$serial/warranty";
        $html = @file_get_contents($url);
        if (!$html) return null;

        $json = stristr($html, 'window.ds_warranties');
        if (!$json) return null;

        $json = substr($json, strlen('window.ds_warranties || '));
        $json = strtok($json, ";");
        $data = json_decode($json, true);
        return $data;
    }

    public static function getNormalized($serial) {
        $data = self::getWarrantyInfo($serial);
        if (!$data || empty($data['BaseWarranties'][0])) return null;

        $base = $data['BaseWarranties'][0] ?? [];
        $ext  = $data['UpmaWarranties'][0] ?? [];

        $start = $ext['Start'] ?? $base['Start'] ?? '';
        $end = $ext['End'] ?? $base['End'] ?? '';
        $status = $ext['StatusV2'] ?? $base['StatusV2'] ?? $data['Status'] ?? 'Inconnu';

        $hasExtension = !empty($ext);

        $type = 'Depot';
        if (!empty($ext['DeliveryType']) && $ext['DeliveryType'] === 'on_site') {
            $type = 'Onsite';
        } elseif (!empty($base['DeliveryType']) && $base['DeliveryType'] === 'on_site') {
            $type = 'Onsite';
        }

        return [
            'fabricant' => 'Lenovo',
            'model' => $data['ProductName'] ?? 'LENOVO',
            'serial' => $data['Serial'] ?? '',
            'product_number' => $data['Mode'] ?? $data['MTM'] ?? '',
            'warranty_start' => $start,
            'warranty_end' => $end,
            'extended_warranty' => $hasExtension ? 'Oui' : 'Non',
            'warranty_type' => $type,
            'warranty_status' => $status,
            'country' => $data['ShipToCountry'] ?? $data['Country'] ?? '',
            'ship_date' => $data['Shiped'] ?? '',
            'description' => $ext['Description'] ?? $base['Description'] ?? ''
        ];
    }
}

class HpWarrantyChecker extends CommonDBTM {
    private $access_token;

    public function __construct() {
        $this->access_token = $this->getToken();
    }

    private function getToken() {
        $config = new PluginWarrantycheckConfig();
        $ClientID_HP = $config->ClientID_HP();
        $ClientSecret_HP = $config->ClientSecret_HP();

        $auth = base64_encode("{$ClientID_HP}:{$ClientSecret_HP}");
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://warranty.api.hp.com/oauth/v1/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic $auth",
                "Accept: application/json",
                "Content-Type: application/x-www-form-urlencoded",
                "User-Agent: PostmanRuntime/7.32.2",
                "Host: warranty.api.hp.com"
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (in_array($http_code, [502, 404])) {
            Session::addMessageAfterRedirect(__('Erreur : API HP non disponible (code '.$http_code.')', 'warrantycheck'), true, ERROR);
            return; // arrête immédiatement la classe
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    public function getNormalized($serial, $product = '', $country = 'FR') {
        if (!$this->access_token) return null;
        $jobId = $this->createJob($serial, $product, $country);
        if (!$jobId) return null;

        $start = microtime(true);
        $timeout = 5; // secondes
        
        do {
            sleep(1);
            $response = $this->getResults($jobId);
            $lastHttpCode = $response['code'] ?? null;
            $result = $response['data'];
        } while ((!$result || empty($result[0]['offers'])) && (microtime(true) - $start) < $timeout);
        
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!$result || empty($result[0]['offers'])) {
            if (!$is_ajax) {
                Session::addMessageAfterRedirect(
                    __('Erreur : les résultats HP ne sont pas disponibles. (code API erreur : '.$lastHttpCode.')', 'warrantycheck'),
                    true,
                    ERROR
                );
            }
            return null;
        }
        
        $offers = $result[0]['offers'];
        $product = $result[0]['product'];

        $best = array_reduce($offers, function($carry, $item) {
            if (!isset($item['serviceObligationLineItemEndDate'])) return $carry;
            return (!$carry || $item['serviceObligationLineItemEndDate'] > $carry['serviceObligationLineItemEndDate']) ? $item : $carry;
        }, null);
        
        $start = $best['serviceObligationLineItemStartDate'] ?? '';
        $end = $best['serviceObligationLineItemEndDate'] ?? '';
        $now = date('Y-m-d');
        $status = ($end && $end >= $now) ? 'Active' : 'Expired';
        $type = stripos($best['offerDescription'] ?? '', 'Onsite') !== false ? 'Onsite' : 'Offsite';

        return [
            'fabricant' => 'HP',
            'model' => $product['productDescription'] ?? '',
            'serial' => $product['serialNumber'] ?? '',
            'product_number' => $this->extractProductRef($product['productDescription'] ?? ''),
            'warranty_start' => $start,
            'warranty_end' => $end,
            'extended_warranty' => count($offers) > 1 ? 'Oui' : 'Non',
            'warranty_type' => $type,
            'warranty_status' => $status,
            'country' => $product['countryCode'] ?? '',
            'ship_date' => '',
            'description' => $best['offerDescription'] ?? ''
        ];
    }

    private function extractProductRef($desc) {
        if (preg_match('/\(([^)]+)\)/', $desc, $m)) return $m[1];
        return '';
    }

    private function createJob($serial, $product, $country) {
        $postData = [["sn" => $serial, "pn" => $product, "cc" => $country]];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://warranty.api.hp.com/productwarranty/v2/jobs",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "Content-Type: application/json",
                "Accept: application/json",
                "User-Agent: PostmanRuntime/7.32.2",
                "Host: warranty.api.hp.com"
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        if (in_array($http_code, [502, 404])) {
            Session::addMessageAfterRedirect(__('Erreur API HP : création du job impossible (code '.$http_code.')', 'warrantycheck'), true, ERROR);
            return null;
        }

        $data = json_decode($response, true);
        return $data['jobId'] ?? null;
    }

    private function getResults($jobId) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://warranty.api.hp.com/productwarranty/v2/jobs/$jobId/results",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "Content-Type: application/json",
                "Accept: application/json",
                "User-Agent: PostmanRuntime/7.32.2",
                "Host: warranty.api.hp.com"
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // On retourne le code HTTP + les données (ou null)
        return [
            'code' => $http_code,
            'data' => ($http_code === 200) ? json_decode($response, true) : null
        ];
    }
}

class DellWarrantyChecker extends CommonDBTM {
    private $token_url = 'https://apigtwb2c.us.dell.com/auth/oauth/v2/token';
    private $warranty_url = 'https://apigtwb2c.us.dell.com/PROD/sbil/eapi/v5/asset-entitlements';

    private function getAccessToken() {
        $config = new PluginWarrantycheckConfig();
        $ClientID_Dell = $config->ClientID_Dell();
        $ClientSecret_Dell = $config->ClientSecret_Dell();

        $data = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $ClientID_Dell,
            'client_secret' => $ClientSecret_Dell
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded",
                'content' => $data
            ]
        ]);
        $result = @file_get_contents($this->token_url, false, $context);
        $http_code = $this->getHttpResponseCode();

        if (in_array($http_code, [502, 404])) {
            Session::addMessageAfterRedirect(__('Erreur : API Dell non disponible (code '.$http_code.')', 'warrantycheck'), true, ERROR);
            return;
        }

        $response = json_decode($result, true);
        return $response['access_token'] ?? null;
    }

    public function getNormalized($serial) {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $url = $this->warranty_url . '?servicetags=' . urlencode($serial);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n"
            ]
        ]);
        $result = file_get_contents($url, false, $context);
        $data = json_decode($result, true);

        if (!is_array($data) || empty($data[0]['entitlements'])) return null;

        $product = $data[0];
        $ent = $product['entitlements'][0];

        $start = substr($ent['startDate'], 0, 10);
        $end = substr($ent['endDate'], 0, 10);
        $status = ($end >= date('Y-m-d')) ? 'Active' : 'Expired';
        $type = stripos($ent['serviceLevelDescription'], 'Onsite') !== false ? 'Onsite' : 'Offsite';

        if ($product['productLineDescription'] == ''){
            return null;
        }

        return [
            'fabricant' => 'Dell',
            'model' => $product['productLineDescription'] ?? '',
            'serial' => $product['serviceTag'] ?? '',
            'product_number' => $this->extractProductRef($product['systemDescription'] ?? '') ?? $product['productCode'] ?? '',
            'warranty_start' => $start,
            'warranty_end' => $end,
            'extended_warranty' => count($product['entitlements']) > 1 ? 'Oui' : 'Non',
            'warranty_type' => $type,
            'warranty_status' => $status,
            'country' => $product['countryCode'] ?? '',
            'ship_date' => $product['shipDate'] ?? '',
            'description' => $ent['serviceLevelDescription'] ?? ''
        ];
    }

    private function extractProductRef($desc) {
        if (preg_match('/\(([^)]+)\)/', $desc, $m)) return $m[1];
        return '';
    }

    private function getHttpResponseCode() {
        global $http_response_header;
        if (!isset($http_response_header) || !is_array($http_response_header)) return 0;
        foreach ($http_response_header as $header) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $header, $matches)) {
                return (int)$matches[1];
            }
        }
        return 0;
    }
}

class DynabookWarranty extends CommonDBTM {
    public static function getWarrantyInfo($serial) {
        $url = "https://support.dynabook.com/support/warrantyResults?sno=" . urlencode($serial);
        $response = @file_get_contents($url);
        if (!$response) return null;

        $data = json_decode($response, true);
        if (!isset($data['commonBean']) || empty($data['commonBean']['serialNumber'])) {
            return null;
        }

        return $data;
    }

    public static function getNormalized($serial) {
        $data = self::getWarrantyInfo($serial);
        if (!$data || empty($data['commonBean'])) return null;

        $bean = $data['commonBean'];
        $pgm  = $data['svcPgm'][0] ?? null;

        $start = substr($bean['customerPurchaseDate'] ?? '', 0, 10);
        $end   = substr($bean['warrantyExpiryDate'] ?? '', 0, 10);
        $status = $data['warranty'] ?? 'Inconnu';

        if($status == 'Warranty expired'){$status = 'Expired';}

        $hasExtension = !empty($pgm) && !empty($pgm['svcPgmName']);
        $type = ($bean['warrantyOnsite'] ?? '0') === '1' ? 'Onsite' : 'Depot';

        return [
            'fabricant' => 'Dynabook',
            'model' => $bean['modelName'] ?? 'DYNABOOK',
            'serial' => $bean['serialNumber'] ?? $serial,
            'product_number' => $bean['partNumber'] ?? '',
            'warranty_start' => $start,
            'warranty_end' => $end,
            'extended_warranty' => $hasExtension ? 'Oui' : 'Non',
            'warranty_type' => $type,
            'warranty_status' => $status,
            'country' => $bean['countryPurchased'] ?? $bean['countrySold'] ?? '',
            'ship_date' => substr($bean['shipDate'] ?? '', 0, 10),
            'description' => $pgm['svcPgmName'] ?? ''
        ];
    }
}

class WarrantyChecker_Terra {
    public static function check($serial)
    {
        $url = "https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=" . urlencode($serial);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true, // ✅ suivre les redirections
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$response) {
            return ['error' => 'Erreur lors de la requête vers le site Terra.'];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($response);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//table[@id='ctl00_ctl00_ctl00_SiteContent_SiteContent_SiteContent_DetailsViewProductInfo']//tr");

        $modele = '';
        $reference = '';
        $date_debut = '';
        $date_fin = '';
        $niveau_service = '';

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length == 2) {
                $label = trim($cells->item(0)->textContent);
                $value = trim($cells->item(1)->textContent);

                switch (true) {
                    case stripos($label, 'Code article') !== false:
                        $reference = $value;
                        break;
                    case stripos($label, 'Description') !== false:
                        $modele = $value;
                        break;
                    case stripos($label, 'D&eacute;but de la service') !== false:
                    case stripos($label, 'Début de la service') !== false:
                        $date_debut = $value;
                        break;
                    case stripos($label, 'Fin de service') !== false:
                        $date_fin = $value;
                        break;
                    case stripos($label, 'Descriptif de la service') !== false:
                        $niveau_service = $value;
                        break;
                }
            }
        }

        if (!$date_fin) {
            return null;
        }

        return [
            'fabricant' => 'Terra',
            'model' => $modele,
            'serial' => $serial,
            'product_number' => $reference,
            'warranty_start' => $date_debut,
            'warranty_end' => $date_fin,
            'extended_warranty' => 'Non', // Terra ne fournit pas cette info, on peut ajouter une logique plus tard
            'warranty_type' => $niveau_service,
            'warranty_status' => (strtotime($date_fin) >= time()) ? 'Active' : 'Expired', // Optionnel : on peut comparer avec date_fin si besoin
            'country' => 'FR',
            'ship_date' => '',
            'description' => $niveau_service
        ];
    }
}