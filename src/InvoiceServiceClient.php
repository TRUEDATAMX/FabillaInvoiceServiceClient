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
     * Permite instanciar el cliente sin argumentos (constructor default) obteniendo los valores desde variables de entorno,
     *
     * @param string|null $invoiceEndpoint URL del servicio de Invoice. Si es null, se obtiene de la variable INVOICE_ENDPOINT.
     * @param string|null $audience        Audiencia para el token JWT. Por defecto "FabillaInvoiceService".
     * @param TrueAuth|null $trueAuth        Instancia de TrueAuth. Se crea automáticamente si es null usando las variables TRUE_SHARED_SECRET, TRUE_AUTHENTICATION_ENDPOINT y TRUE_SERVICE_NAME.
     *
     * @throws \Exception Si faltan variables de entorno necesarias para crear TrueAuth.
     */
    public function __construct(
        ?string $invoiceEndpoint = null,
        ?string $audience = null,
        ?TrueAuth $trueAuth = null
    ) {
        $this->invoiceEndpoint = $invoiceEndpoint ?? getenv('INVOICE_ENDPOINT');
        if (!$this->invoiceEndpoint) {
            throw new \Exception("La variable de entorno INVOICE_ENDPOINT no está definida.");
        }
        
        $this->audience = $audience ?? "FabillaInvoiceService";
        
        if ($trueAuth === null) {
            $sharedSecret = getenv('TRUE_SHARED_SECRET');
            $authEndpoint = getenv('TRUE_AUTHENTICATION_ENDPOINT');
            $serviceName  = getenv('TRUE_SERVICE_NAME');
            if (!$sharedSecret || !$authEndpoint || !$serviceName) {
                throw new \Exception("Faltan variables de entorno requeridas para TrueAuth (TRUE_SHARED_SECRET, TRUE_AUTHENTICATION_ENDPOINT, TRUE_SERVICE_NAME).");
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
