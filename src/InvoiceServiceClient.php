<?php

namespace FabiInvoiceServiceClient;

use TrueAuthSDK\TrueAuth;

class InvoiceServiceClient
{
    /**
     * URL base del servicio de Invoice.
     * 
     * @var string
     */
    private $invoiceEndpoint = "https://o3cfk2o5gd.execute-api.us-west-2.amazonaws.com";

    /**
     * Instancia del autenticador (TrueAuth SDK externo).
     *
     * @var TrueAuth
     */
    private $trueAuth;

    /**
     * Constructor.
     *
     * @param TrueAuth $trueAuth Instancia del autenticador.
     */
    public function __construct(TrueAuth $trueAuth)
    {
        $this->trueAuth = $trueAuth;
    }

    /**
     * Método para obtener una factura por su ID.
     *
     * @param string $invoiceId
     * @return array
     * @throws \Exception
     */
    public function getInvoice(string $invoiceId): array
    {
        // Genera el token usando la audiencia "TrueAPIKeyService"
        $token = $this->trueAuth->token('TrueAPIKeyService');

        $url = $this->invoiceEndpoint . "/invoice/" . urlencode($invoiceId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // headers for authentification
        $headers = [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Error al conectar con Invoice Service: " . $error);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar la respuesta: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Método para crear una nueva factura.
     *
     * @param array $data Datos de la factura a crear.
     * @return array
     * @throws \Exception
     */
    public function createInvoice(array $data): array
    {
        // Genera el token para la autenticación
        $token = $this->trueAuth->token('TrueAPIKeyService');

        $url = $this->invoiceEndpoint . "/invoice";
        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Headers for authentification
        $headers = [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Error al crear la factura: " . $error);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar la respuesta: " . json_last_error_msg());
        }

        return $decoded;
    }
}

