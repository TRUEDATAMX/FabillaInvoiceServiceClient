<?php

namespace TrueAuthSDK;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TrueAuth {

    private $trueSharedSecret;

    private $trueAuthenticationEndpoint;

    private $trueServiceName;

    public function __construct($trueSharedSecret, $trueAuthenticationEndpoint, $trueServiceName) {
        $this->trueSharedSecret = $trueSharedSecret;
        $this->trueAuthenticationEndpoint = $trueAuthenticationEndpoint;
        $this->trueServiceName = $trueServiceName;
    }

    public function token($audience) {
        $algorithm = "HS256";
        $headers = [
            "alg" => $algorithm,
            "typ" => "JWT",
            "kid" => $this->trueServiceName
        ];

        $payload = [
            "iss" => $this->trueServiceName,
            "aud" => $audience,
            "iat" => time(),
            "exp" => time() + (5 * 60)
        ];

        return JWT::encode($payload, $this->trueSharedSecret, $algorithm, $this->trueServiceName, $headers);
    }

    public function validate($token) {
        $headers = [
            "Authorization: Bearer " . $token,
            "Service: " . $this->trueServiceName
        ];
        return $this->validate_headers($headers);
    }

    public function validate_headers($headers) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->trueAuthenticationEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("An error occurred while making the request: " . $curlError);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode == 200) {
            return $decodedResponse;
        } elseif ($httpCode == 401) {
            throw new \Exception("Authentication failed: " . json_encode($decodedResponse));
        } elseif ($httpCode == 400) {
            throw new \Exception("Bad Request: " . json_encode($decodedResponse));
        } else {
            throw new \Exception("Unexpected status code: " . $httpCode);
        }
    }
}
