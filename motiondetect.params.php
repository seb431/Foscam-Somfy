<?php

$config = array(
    'somfy' => array(
        'url' => 'http://<ip_alarme>/',
        'password' => '<code_utilisateur>',
        ),
        
    'foscam' => array(
        'url' => 'http://<ip_camera>:<port_camera>/cgi-bin/CGIProxy.fcgi',
        'user' => '<user_camera>',
        'password' => '<mot de passe camera>',
        ),
        
    'email' => '<mail_de_reception_des_alertes>',
	'nbErreurs' => 2,
    );