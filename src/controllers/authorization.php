<?php

namespace src\controllers;

class authorization {

    public $user = [];
    private $signing_key = "somerandomstringhothotheat";
    private $alg = 'sha512';
    private $expire_in_seconds = 60*60*24*30; // a month

    public function __construct()
    {
        header('Content-Type:application/json');   
    }

    public function login()
    {
        //  only post request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only POST request is accepted',
                    'mothod'    => $_SERVER['REQUEST_METHOD']
                ]
            );
            return;
        }

        //  must contain login & pass
        if (array_key_exists('login', $_POST) === false) 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'login field is not present'
                ]
            );
            return;
        }
        if (array_key_exists('password', $_POST) === false) 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'password field is not present'
                ]
            );
            return;
        }
        // read credentials file
        $credentials = json_decode(file_get_contents('../config/credentials.json'))->credentials;
        if ($credentials->login != $_POST['login'] || $credentials->password != $_POST['password']) 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'credentials are not correct'
                ]
            );
            return;
        }
        //  create jwt and return it
        $token = $this->gen_jwt(['logn' => $credentials->login, 'exp' => time() + $this->expire_in_seconds]);
        echo json_encode([
            'code'      => 200,
            'jwt'       => $token
        ]);
        return;
    }

    private function gen_jwt($payload):String
    {
        $header = [ 
            "alg" => $this->alg, 
            "typ" => "JWT" 
        ];
        $header = $this->base64_url_encode(json_encode($header));
        $payload = $this->base64_url_encode(json_encode($payload));
        $signature = $this->base64_url_encode(hash_hmac($this->alg, "$header.$payload", $this->signing_key, true));
        $jwt = "$header.$payload.$signature";
        return $jwt;    
    }

    public function base64_url_encode($text):String
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    public function check_auth()
    {
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) 
        {
            echo json_encode([
                'code'      => 403,
                'message'   => 'Token not present'
            ]);
            return;
        }
        
        if ($this->check_token()) 
        {
            echo json_encode([
                'code'      => 200,
                'message'   => 'Token valid'
            ]);
            return
        } 
        
        echo json_encode([
            'code'      => 403,
            'message'   => 'Token not valid'
        ]);
        return;
    }

    public function check_token()
    {
        $jwt = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        // split the jwt
        $token_parts = explode('.', $jwt);
        if (count($token_parts) != 3) 
        {
            return false;
        }

        $header = base64_decode($token_parts[0]);
        $payload = base64_decode($token_parts[1]);
        $signature_provided = $token_parts[2];

        $expiration = json_decode($payload)->exp;
        $is_token_expired = ($expiration - time()) < 0;

        // build a signature based on the header and payload using the secret
        $base64_url_header = $this->base64_url_encode($header);
        $base64_url_payload = $this->base64_url_encode($payload);
        $signature = hash_hmac($this->alg, $base64_url_header . "." . $base64_url_payload, $this->signing_key, true);
        $base64_url_signature = $this->base64_url_encode($signature);

        // verify it matches the signature provided in the jwt
        $is_signature_valid = ($base64_url_signature === $signature_provided);
        
        if (!$is_signature_valid || $is_token_expired) 
        {
            return FALSE;
        } else 
        {
            return TRUE;
        }
    }
    
}