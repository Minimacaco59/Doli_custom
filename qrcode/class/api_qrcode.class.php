<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2023 SuperAdmin <favre.alex@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

dol_include_once('/qrcode/class/qrcode.class.php');

function callAPI($url, $headers, $data) {
    $curl = curl_init();

    // Configuration de l'URL de l'API
    curl_setopt($curl, CURLOPT_URL, $url);

    // Spécification de la méthode HTTP (POST)
    curl_setopt($curl, CURLOPT_POST, true);

    // Spécification des en-têtes HTTP
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    // Spécification des données à envoyer
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    
    // Désactiver la vérification du certificat SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    // Pour récupérer la réponse de l'API au lieu de l'afficher directement
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Exécution de la requête et récupération de la réponse
    $response = curl_exec($curl);

    // Gestion des erreurs
    if ($response === false) {
        $error = curl_error($curl);
        curl_close($curl);
        return "Erreur lors de l'appel à l'API : " . $error;
    }

    curl_close($curl);

    // Retourne la réponse de l'API
    return $response;
}

function sendEmail($recipient, $message, $qrcodeData)
{
    global $conf, $langs, $mysoc;
    global $dolibarr_main_url_root;
    $dir=DOL_DATA_ROOT.'/Qrcode';
    $tdir=$dir. '/temp';
    dol_mkdir($tdir);

    require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
    $message .= '<img src="data:image/png;base64,' . $qrcodeData . '">';
    $msgishtml = 1;
    $subject = '[' . $mysoc->name . '] ' . $langs->transnoentitiesnoconv("SubjectNewPassword", $appli);

    // Créer une instance de CMailFile
    $mailfile = new CMailFile(
        $subject,
        $recipient,
        "favre.alex@gmail.com",
        $message,
        array(),
        array(),
        array(),
        '',
        '',
        0,
        $msgishtml,
        '',
        ''
    );

    
    if ($mailfile->sendfile()) {
        return 1;
    } else {
        $error = $langs->trans("ErrorFailedToSendPassword") . ' ' . $mailfile->error;
        return $error;
    }
}





/**
 * \file    qrcode/class/api_qrcode.class.php
 * \ingroup qrcode
 * \brief   File for API management of qrcode.
 */

/**
 * API class for qrcode qrcode
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class QrcodeApi extends DolibarrApi
{
    /**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'name',
		'api_key',
		'email'
	); 
	
	/**
	 * @var Qrcode $qrcode {@type Qrcode}
	 */
	public $qrcode;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->qrcode = new Qrcode($this->db);
	}

	/**
	 * Create qrcode object
	 *
	 * Exemple: { "name": "Toto", "api_key": "dolapikey", "email": "toto@gmail.com" }
	 * 
	 * @param array $request_data   Request datas
	 * 
	 *
	 * @throws RestException
	 *
	 * @url	POST qrcodes/
	 */
	public function post($request_data)
	{
        
	    $parametres = array();

    foreach ($request_data as $key => $value) {
        $parametres[$key] = $value;
    }
    
    $token = $this->_encodage($request_data);
    $code=$this->_qrcodegeneration($token);
    
    $imagepath=$this->_creatimage($code);
    $recipientEmail = $request_data['email'];
    $messageText = 'Hello, voici votre Qrcode de connexion !';
    
    $result=sendEmail($recipientEmail, $messageText, $code);
    
    return $result;
	
	}
    
    private function _creatimage($code)
    {
        $dir=DOL_DATA_ROOT.'/Qrcode';
        $tdir=$dir. '/temp';
        dol_mkdir($tdir);
        
        // Spécifiez le chemin et le nom de fichier pour le nouveau fichier
        $filePath = $tdir.'/nouveau.png';

        // Créez le fichier à partir des données binaires
        $file = fopen($filePath, 'wb');
        fwrite($file, $code);
        if ($file) {
            if (fwrite($file, $imageData) !== false) {
                return  $filePath;
            } else {
                return 'Erreur lors de l écriture des données dans le fichier.';
            }
            fclose($file);
        } else {
            return 'Erreur lors de l ouverture du fichier.';
        }
        
    }
    
    private function _encodage($request_data)
    {
    $secretKey = 'mspr_dolib@arr_edgar_edgar_lynda_pierre_alexandre';
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    $payload = [
        'nom' => $request_data['name'],
        'dolapikey' => $request_data['api_key']
    ];  
    // Encodage de l'header et du payload en base64
    $encodedHeader = base64_encode(json_encode($header));
    $encodedPayload = base64_encode(json_encode($payload));
    // Création de la signature du token
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secretKey, true);
    $encodedSignature = base64_encode($signature);
    // Création du token complet
    $token = $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
    $token= str_replace(array('='), '',$token);
    return $token;
    }

    private function _qrcodegeneration($token)
    {
    $url = 'https://qrtiger.com/api/qr/static';

    $headers = array(
    'Accept: application/json',
    'Authorization: Bearer a7f27ca0-0314-11ee-a34d-5918d3ce8d08',
    'Content-Type: application/json'
    );

    $data = array(
        "size" => 500,
        "colorDark" => "rgb(133,118,85)",
        "logo" => "https://media.qrtiger.com/images/2023/06/kawa_15.png",
        "eye_outer" => "eyeOuter9",
        "eye_inner" => "eyeInner9",
        "qrData" => "pattern4",
        "backgroundColor" => "rgb(255,255,255)",
        "transparentBkg" => false,
        "qrCategory" => "text",
        "text" => $token
    );

    $data = json_encode($data); // Convertir les données en JSON

    $response = callAPI($url, $headers, $data);
    $qr=json_decode($response);
    $qrcode=$qr->data;


    return $qrcode;
    
    }
}