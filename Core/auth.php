<?php

require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;


class AuthJWT
{
    private static $secret_key = 'SmJf#$!Gwp,qMLt&Jz=67';
    private static $secret_client = 'Dns#,L2c**z1685k:-]6spXp5!t3C9';
    private static $encrypt = ['HS256'];
    private static $aud = null;
    
    public static function SignIn($data)
    {
        $time = time();
        
        $token = array(
            'exp' => $time + (3600*36),
            'aud' => self::Aud(),
            'data' => $data
        );
        # valida con que llave se autentica
        $sec = ( isset($data['client']) ) ? self::$secret_client : self::$secret_key; 
        return JWT::encode($token, $sec);
    }
    
    public static function Check($token)
    {
        if(empty($token))
        {
            throw new Exception("Invalid token supplied.");
        }
        
        $decode = JWT::decode(
            $token,
            self::$secret_key,
            self::$encrypt
        );
        
        if($decode->aud !== self::Aud())
        {
            throw new Exception("Invalid user logged in.");
        }
    }
    
    // la excepcion no esta controlada aun como debe ser
    public static function GetData($token,$isClient=false)
    {
        $decode = array();
        try {
            $sec = ($isClient) ? self::$secret_client : self::$secret_key; 
            $decode = JWT::decode(
                            $token,
                            $sec,
                            self::$encrypt
                        )->data;
        } catch (Exception $e) {
            $decode = array('id'=>null,'vld_tk'=>null);
        }

        return $decode;
        
    }
    
    private static function Aud()
    {
        $aud = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $aud = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $aud = $_SERVER['REMOTE_ADDR'];
        }
        
        $aud .= @$_SERVER['HTTP_USER_AGENT'];
        $aud .= gethostname();
        
        return sha1($aud);
    }
}