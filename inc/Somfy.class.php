<?php
class Somfy {
    
    // Paramètres spécifiques
    private $password;
    private $urlRoot;

    // Codes par défaut (que l'on peut changer en les passant en paramètre lors de l'instanciation de la classe)
    private $codes = array(
            'A1' => '5032', 'A2' => '7829', 'A3' => '1026', 'A4' => '0531', 'A5' => '0817',
            'B1' => '0831', 'B2' => '8374', 'B3' => '1739', 'B4' => '9407', 'B5' => '7003',
            'C1' => '3064', 'C2' => '3421', 'C3' => '2579', 'C4' => '9542', 'C5' => '0265',
            'D1' => '0594', 'D2' => '3675', 'D3' => '8449', 'D4' => '1998', 'D5' => '0213',
            'E1' => '5446', 'E2' => '5665', 'E3' => '8707', 'E4' => '7371', 'E5' => '4844',
            'F1' => '1555', 'F2' => '5212', 'F3' => '7626', 'F4' => '6537', 'F5' => '0585',
        );

    // Url à utiliser
    private $urls = array(
                'login' => 'fr/login.htm',
                'etat' => 'status.xml',
                'logout' => 'logout.htm',
                );

    // Fichier des cookies
    private $cookieFile = '/tmp/cookie.txt';
    
    // Session CURL
    private $ch;
    
    // Tableau pour le debugage
    public $debug = array();

        
    
    
    /*
     *  Constructeur : récupération des paramètres et instanciation de la session Curl
     */
    function __construct($config = array()) {
        
        // Remplissage de la config
        if(array_key_exists('url', $config)){
            $this->urlRoot = $config['url'];
        }

        if(array_key_exists('password', $config)){
            $this->password = $config['password'];
        }

        if(array_key_exists('codes', $config)){
            $this->codes = $config['codes'];
        }      
                
        
        // Suppression du fichier des cookies si existant
        @unlink($this->cookieFile);
		
		//init curl
		$this->ch = curl_init();


		//Handle cookies for the login
		curl_setopt( $this->ch, CURLOPT_COOKIESESSION, true );
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookieFile);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookieFile);

		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    
    
    
    /*
     * Login
     */
    public function login(){
   		$this->info('-- Récup clé -----------------------------');

        curl_setopt($this->ch, CURLOPT_URL, $this->getUrl('login'));
		$content = curl_exec($this->ch);
        $this->setDebug('recup_cle', $content);
        
        // Impossible de récupérer le code, on s'arrête là
        if(!preg_match('/<b>(.+)<\/b>/', $content, $matches)){
            $this->info('Récupération du code impossible.');            
            $this->info($matches);
           return false;
        }
        
        // Récupération du code ok
        else {

            // On vérifie que le code existe (ie n'est pas un code d'erreur)
            if(!array_key_exists($matches[1], $this->codes)){
                $this->info('code non reconnu : '.$matches[1]);
                return false;
            }
            
            // Le code existe
            else {
                
                $this->info('Clé récupérée');
                print_r($matches);
                
                $this->setDebug('recup_cle_matches', print_r($matches, true));
                
                $key = $this->codes[$matches[1]];

                
                // Login
                $this->info('-- Login -----------------------------');
                
                $postData = 'login=u&password='.$this->password.'&key='.$key.'&btn_login=Connexion';
                $this->setDebug('postdata', $postData);

                
                curl_setopt($this->ch, CURLOPT_POST, 1);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($this->ch, CURLOPT_URL, $this->getUrl('login'));
                $content = curl_exec($this->ch);
                $this->setDebug('login', $content);
                
                // On vérifie si on est sur la page d'accueil
                if(preg_match('/Bienvenue/', $content)){
                    $this->info('Login OK');
                    return true;
                }
                else {
                    $this->info('Login en erreur');
                    return false;
                }
            }
        }
    }

    
    
    /*
     * Récupération du status de l'alarme
     */
    public function getStatus(){
        // Récupération du status
        $this->info('-- Etat -----------------------------');
    
        curl_setopt($this->ch, CURLOPT_URL, $this->getUrl('etat'));
        $content = curl_exec($this->ch);        
        $this->setDebug('etat', $content);
        
        // On vérifie si on est bien sur la page status en xml
        if(preg_match('/<response>/', $content)){
            
            $this->info('Récup status OK');
            
            $xml = simplexml_load_string($content);
            $json = json_encode($xml);
            $array = json_decode($json,TRUE);
            $this->info($array);
        
            return $array;

        }
        else {
            $this->info('Récup status erreur');
            return false;
        } 
        
        
    }
    
    
    /*
     * Logout
     */
    public function logout(){        
        
        // logout
        $this->info('-- Logout -----------------------------');
        
        curl_setopt($this->ch, CURLOPT_URL, $this->getUrl('logout'));
        $content = curl_exec($this->ch);
        $this->setDebug('logout', $content);
        
        // On vérifie si on est revenu sur la page de login
        if(preg_match('/Utilisateur1/', $content)){
            $this->info('Logout OK');
            return true;
        }
        else {
            $this->info('Logout en erreur');
            return false;
        }        
        
    }
    
    /*
     * Génère l'url complète à appeler
     */
    private function getUrl($type){
        return $this->urlRoot.$this->urls[$type];
    }
    
    /*
     * Affiche un texte sur la sortie standard
     */
    private function info($text){
        print_r($text);
        echo "\n";
    }
    
    /*
     * Ajoute une info au tableau de debugage
     */
    private function setDebug($item, $text){
        $this->debug[$item] = $text;
    }

}