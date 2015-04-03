#!/usr/bin/php -q
<?php

require_once('motiondetect.params.php');
require_once('inc/Somfy.class.php');
require_once('inc/Foscam.class.php');

$isEnable = -1;
$fichier_erreurs = '/tmp/somfyerreurs';


//************************************
// Récupération de l'état de l'alarme
//************************************

// On récupère l'état de l'alarme

$somfy = new Somfy($config['somfy']);

if( $somfy->login()){
    $status = $somfy->getStatus();
}

// Ne pas oublier de se déconnecter pour ne pas bloquer la session
$somfy->logout();



//**************************************
// Détermination de l'état de la caméra
//**************************************

// Si on a récupéré le statut de l'alarme, on détermine l'état de la caméra
if(is_array($status)){


	// On désactive la détection de mouvement si toutes les zones de l'alarme sont désactivées
    if($status['zone0'] == 'off' && $status['zone1'] == 'off' && $status['zone2'] == 'off'){
        $isEnable = 0;
    }
	else {
		$isEnable = 1;
	}

    // Suppression du fichier des erreurs pour RAZ compteur
	if(file_exists($fichier_erreurs)){
		unlink($fichier_erreurs);
	}
}


// Erreur de récupération du status
else {
    echo "Erreur sur le status\n";
	
	$erreurs = 0;
	if(file_exists($fichier_erreurs)){
		$erreurs = file_get_contents($fichier_erreurs);
	}

	$erreurs++;
    
    echo "nb erreurs : ".$erreurs." / ".$config['nbErreurs']."\n";

    // Si on dépasse le nombre d'erreurs, on active la caméra
	if($erreurs >= $config['nbErreurs']){
		$isEnable = 1;
	}
	
	file_put_contents($fichier_erreurs, $erreurs);
	
	mail($config['email'], '[motiondetect] Erreur '.$erreurs." / ".$config['nbErreurs'].' sur la récupération du status de l\'alarme', print_r($somfy->debug, true));
}


//**************************
// Paramétrage de la caméra 
//**************************

if($isEnable >= 0){


    echo "isEnable théorique : ".$isEnable."\n";


    // On récupère les paramètres de la détection de mouvement
    // (si on ne renvoie que le isEnable, on perd tous les autres paramètres)
    $foscam = new Foscam($config['foscam']);
    $mdParams = $foscam->cmd('getMotionDetectConfig');


    // Si l'état réel est différent de l'étant dans lequel il doit être, on le change
    if($isEnable != $mdParams['isEnable']){
        
        $mdParams['isEnable'] = $isEnable;
        
        $return = $foscam->cmd('setMotionDetectConfig', $mdParams);
        
        if(is_array($return)){
            echo "Changement de statut : ".$isEnable."\n";   
            mail($config['email'], '[motiondetect] Changement du status vers '.($isEnable == 1 ? 'enable' : 'disable'), 'Nous sommes le '.date('d/m/Y H:i:s'));
        }
    }
}
