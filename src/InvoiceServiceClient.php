<?php

namespace FabillaInvoiceServiceClient;

use TrueAuthSDK\TrueAuth;

class InvoiceServiceClient
{
    private $invoiceEndpoint;
    private $audience;
    private $trueAuth;

    /**
     * Constructor.
     *
     * Si solo se provee el endpoint, se utilizan valores por defecto:
     * - audiencia: "FabillaInvoiceService"
     * - trueAuth: se instancia usando variables de entorno.
     *
     * @param string      $invoiceEndpoint URL del servicio de Invoice.
     * @param string|null $audience        Audiencia (opcional). Por defecto: "FabillaInvoiceService".
     * @param TrueAuth|null $trueAuth      Instancia de TrueAuth (opcional). Se crea automáticamente si no se pasa.
     *
     * @throws \Exception Si las variables de entorno necesarias no están configuradas.
     */
    public function __construct(
        string $invoiceEndpoint,
        ?string $audience = null,
        ?TrueAuth $trueAuth = null
    ) {
        $this->invoiceEndpoint = $invoiceEndpoint;
        $this->audience = $audience ?? "FabillaInvoiceService";

        if ($trueAuth === null) {
            $sharedSecret = getenv('FABILLA_SHARED_SECRET');
            $authEndpoint = getenv('FABILLA_AUTH_ENDPOINT');
            $serviceName  = getenv('FABILLA_SERVICE_NAME');

            // Validar que las variables necesarias estén configuradas
            if (!$sharedSecret || !$authEndpoint || !$serviceName) {
                throw new \Exception("Faltan variables de entorno requeridas para TrueAuth.");
            }

            $this->trueAuth = new TrueAuth($sharedSecret, $authEndpoint, $serviceName);
        } else {
            $this->trueAuth = $trueAuth;
        }
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
