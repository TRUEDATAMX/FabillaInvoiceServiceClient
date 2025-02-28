<?php

namespace FabillaInvoiceServiceClient;

use TrueAuthSDK\TrueAuth;

class InvoiceServiceClient
{
    /**
     * URL base del servicio de Invoice (endpoint externo).
     * 
     * @var string
     */
    private $invoiceEndpoint = "https://o3cfk2o5gd.execute-api.us-west-2.amazonaws.com";

    /**
     * Instancia del autenticador (TrueAuth SDK, servicio externo).
     *
     * @var TrueAuth
     */
    private $trueAuth;

    /**
     * Constructor.
     *
     * @param TrueAuth $trueAuth Instancia del autenticador, ya configurado para conectarse al servicio externo de autenticación.
     */
    public function __construct(TrueAuth $trueAuth)
    {
        $this->trueAuth = $trueAuth;
    }

    /**
     * Obtiene una factura dado su ID.
     *
     * Para autenticar la petición, se genera un token JWT utilizando el TrueAuth SDK con la audiencia "TrueAPIKeyService".
     *
     * @param string $invoiceId
     * @return array La respuesta decodificada (normalmente, los datos de la factura)
     * @throws \Exception Si ocurre algún error en la conexión o al decodificar la respuesta.
     */
    public function getInvoice(string $invoiceId): array
    {
        // Se obtiene el token JWT usando el servicio de autenticación externo
        $token = $this->trueAuth->token('TrueAPIKeyService');

        // Construir la URL completa para obtener la factura
        $url = $this->invoiceEndpoint . "/invoice/" . urlencode($invoiceId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Se envían las cabeceras de autenticación y el tipo de contenido
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
     * Crea una nueva factura.
     *
     * Se genera el token JWT para autenticar la petición de creación. Los datos de la factura se envían en formato JSON.
     *
     * @param array $data Datos de la factura a crear.
     * @return array Respuesta decodificada del servicio de Invoice.
     * @throws \Exception Si ocurre algún error en la conexión o al decodificar la respuesta.
     */
    public function createInvoice(array $data): array
    {
        // Se obtiene el token JWT usando el servicio de autenticación externo
        $token = $this->trueAuth->token('TrueAPIKeyService');

        $url = $this->invoiceEndpoint . "/invoice";
        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Cabeceras para la petición: autenticación y contenido JSON
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
