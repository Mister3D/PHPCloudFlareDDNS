<?php

// API Key (Se trouve dans les parametres du compte)
$param['api_key'] = 'c12d3c45f6d7891e011b12d1314151a6e1e71';

// Origin CA Key (Se trouve dans les parametres du compte)
$param['ca_key'] = 'v1.0-......';

// Adresse mail du compte
$param['mail'] = 'machine@bidule.com';

// Parametres des domaines
// zone_id se trouve dans l'Overview de votre domaine
$param['dommain'][0]['nom'] = 'domaine.fr';
$param['dommain'][0]['type'] = 'A';
$param['dommain'][0]['zone_id'] = '12345678b2beabc593a0031ce72ddeed';

$param['dommain'][1]['nom'] = 'lol.domaine.fr';
$param['dommain'][1]['type'] = 'A';
$param['dommain'][1]['zone_id'] = '12345678b2beabc593a0031ce72ddeed';

$param['dommain'][2]['nom'] = 'mdr.domaine.fr';
$param['dommain'][2]['type'] = 'A';
$param['dommain'][2]['zone_id'] = '12345678b2beabc593a0031ce72ddeed';

$ip_actuelle = file_get_contents('https://api.ipify.org/');
echo 'Votre adresse IP actuelle : '.$ip_actuelle.PHP_EOL;

if(file_get_contents(__DIR__.'/last.ip') == $ip_actuelle) {
	echo 'Adresse IP inchangee, pas de mise a jour.'.PHP_EOL;
} else {
	foreach($param['dommain'] as $cleDomaine => $paramDomaine) {
		echo 'Le zone_id de '.$paramDomaine['nom'].' est-il valide ? ';
			
		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'header'=>  "X-Auth-Key: ".$param['api_key']."\r\n"
				. "X-Auth-Email: " . $param['mail'] . "\r\n"
				. "Content-Type: application/json"."\r\n"
			)
		);
		$context = stream_context_create($opts);
		$fp = fopen('https://api.cloudflare.com/client/v4/zones/'.$paramDomaine['zone_id'].'/dns_records', 'r', false, $context);

		$contents = null;
		while(!feof($fp)) {
			$contents .= fread($fp, 8192);
		}
		fclose($fp);

		$reponse = json_decode($contents);

		// Detecte s'il y a une erreur
		if($reponse->success == true) {
			echo "\e[0;32m".'OUI'."\e[0m".PHP_EOL;
			
			echo ' L\'enregistrement DNS existe-t-il dans la zone ? ';
			foreach($reponse->result as $cleRecord => $infoRecord) {
				if($infoRecord->name == $paramDomaine['nom'] && $infoRecord->type == $paramDomaine['type']) {
					$paramDomaine['id'] = $infoRecord->id;
					$paramDomaine['ip'] = $infoRecord->content;
				}
			}
			if(isset($paramDomaine['id'])) {
				echo "\e[0;32m".'OUI'."\e[0m".PHP_EOL;
				echo ' Mise a jour de l\'enregistrement : ';
				
				if($paramDomaine['ip'] == $ip_actuelle) {
					echo "\e[1;33m".'DEJA A JOUR'."\e[0m".PHP_EOL;
				} else {
					$arr = array(
						'type' => $paramDomaine['type'],
						'name' => $paramDomaine['nom'],
						'content' => $ip_actuelle,
						'ttl' => 120,
						'proxied' => false
					);

					$dataEncoder = json_encode($arr);

					$opts = array(
					  'http'=>array(
						'method'=> "PUT",
						'header'=>  "X-Auth-Email: " . $param['mail'] . "\r\n" 
								  . "X-Auth-Key: " . $param['api_key'] . "\r\n"
								  . "Content-Type: application/json\r\n"
								  . "Content-Length: " . strlen($dataEncoder) . "\r\n",
						'content' => $dataEncoder
					  )
					);

					$context = stream_context_create($opts);

					$fp = fopen('https://api.cloudflare.com/client/v4/zones/'.$paramDomaine['zone_id'].'/dns_records/'.$paramDomaine['id'], 'r', false, $context);
					
					$contents = null;
					while(!feof($fp)) {
						$contents .= fread($fp, 8192);
					}
					fclose($fp);

					$reponseUpdate = json_decode($contents);
					
					if($reponseUpdate->success == true) {
						echo "\e[0;32m".'REUSSIE'."\e[0m".PHP_EOL;
					} else {
						echo "\e[0;31m".'ECHEC'."\e[0m".PHP_EOL;
					}
				}
			} else {
				echo "\e[0;31m".'NON'."\e[0m".PHP_EOL;
			}
		} else {		
			echo "\e[0;31m".'NON'."\e[0m".PHP_EOL;
		}		
	}
}

?>