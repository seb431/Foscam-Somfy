<?php

class Foscam {

    private $url;
    private $user;
    private $password;

    /*
     * Constructeur
     */
    function __construct($config = array()) {
        
        // Remplissage de la config
        $this->url = $config['url'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        
    }
    
    /*
     * Envoi d'une commande à la caméra
     */
    public function cmd($cmd, $params = array(), $returnType = 'xml'){

        // Construction de l'url avec les paramètres
        if(count($params) > 0){
            foreach($params as $name => $value){
                $cmd .= '&'.$name.'='.$value;
            }
        }
        $url = $this->url.'?'.urlencode('cmd='.$cmd.'&usr='.$this->user.'&pwd='.$this->password);

                
        // Appel à l'API
        $content = file_get_contents($url);
        
        
        
        // Si format XML, on vérifie que le retour est bon et on renvoie un tableau des données
        if($returnType == 'xml'){
            
           // On vérifie si on est bien sur une réponse XML
            if(preg_match('/<result>0<\/result>/', $content)){
                
                $xml = simplexml_load_string($content);
                $json = json_encode($xml);
                $array = json_decode($json,TRUE);
            
                return $array;

            }
            else {
                return false;
            }         
        }
        
        // Si pas au format XML,  on renvoie les données brutes
        else {
            return $content;
        }
        
    }
}