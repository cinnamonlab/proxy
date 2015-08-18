<?php

namespace Framework\Proxy;

use Framework\Response;
use CURLFile;
use Framework\Input;

class Proxy
{

    static function forward( $base_url ) {

        $url = $base_url . $_SERVER['REQUEST_URI'];
        $ch = curl_init( $url );


        if ( strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
            curl_setopt( $ch, CURLOPT_POST, true );

            $parameters = $_POST;

            foreach( $_FILES as $key => $file ) {
                $parameters[$key] =
                    new CURLFile( $file['tmp_name'], $file['type'], $file['name']);
            }

            curl_setopt( $ch, CURLOPT_POSTFIELDS, $parameters );
        } else {
            $parameters = array();
            $d = Input::getAllData();
            foreach( $d['parameters'] as $key => $value) 
                if ( is_string($value) ) $parameters[$key]=$value;

            foreach( $_FILES as $key => $file ) {
                $parameters[$key] =
                    new CURLFile( $file['tmp_name'], $file['type'], $file['name']);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        }


        $cookie = array();
        foreach ( $_COOKIE as $key => $value ) {
            $cookie[] = $key . '=' . $value;
        }

        $cookie = implode( '; ', $cookie );

        curl_setopt( $ch, CURLOPT_COOKIE, $cookie );

        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_HEADER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_HTTPHEADER,array("Expect:"));

        curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );

        list( $header, $content ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );

        $status = curl_getinfo( $ch );
        curl_close( $ch );

        $header_texts = preg_split( '/[\r\n]+/', $header );

        $response = (new Response())->setContent($content);

        foreach( $header_texts as $header_text ) {
            if ( preg_match("/^HTTP\/... ([0-9]*)/", $header_text, $match ) ) {
                $response->setCode($match[1]);
            } elseif ( preg_match("/^content-type: (.*)$/i", $header_text, $match) ) {
                $response->addHeader($header_text);
            }
        }

        return $response;
    }

}

