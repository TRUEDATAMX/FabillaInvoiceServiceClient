<?php

namespace FabillaInvoiceServiceClient;

use TrueAuthSDK\TrueAuth;

class InvoiceServiceClient
{
    private $invoiceEndpoint;
    private $audience;
    private $trueAuth;

    public function __construct(
        ?string $invoiceEndpoint = null,
        ?string $sharedSecret = null,
        ?string $authEndpoint = null,
        ?string $serviceName = null,
        ?string $audience = null
    ) {
        $this->invoiceEndpoint = $invoiceEndpoint ?? "https://o3cfk2o5gd.execute-api.us-west-2.amazonaws.com";
        $this->audience = $audience ?? "FabillaInvoiceService";
        $sharedSecret = $sharedSecret ?? "mecorro2";
        $authEndpoint = $authEndpoint ?? "https://tdse-aphwg4drbmd6g0ev.mexicocentral-01.azurewebsites.net/m2m/auth/";
        $serviceName = $serviceName ?? "fabilla";
        $this->trueAuth = new TrueAuth($sharedSecret, $authEndpoint, $serviceName);
    }

    public function getInvoice(string $invoiceId): array
    {
        $token = $this->trueAuth->token($this->audience);
        $payload = json_encode(["id" => $invoiceId]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->invoiceEndpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            curl_close($ch);
            throw new \Exception("Error al conectar con Invoice Service: " . $error);
        }
        curl_close($ch);
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar la respuesta: " . json_last_error_msg());
        }
        return $decoded;
    }

    public function createInvoice(array $data): array
    {
        $token = $this->trueAuth->token($this->audience);
        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->invoiceEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            curl_close($ch);
            throw new \Exception("Error al crear la factura: " . $error);
        }
        curl_close($ch);
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar la respuesta: " . json_last_error_msg());
        }
        return $decoded;
    }
}
