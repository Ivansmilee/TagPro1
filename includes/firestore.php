
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;

class Firestore
{
    private string $projectId;
    private string $credentialsPath = 'C:/tagpro-secrets/firebase-credentials.json';

    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    public function __construct()
    {
        $this->projectId = getenv('FIREBASE_PROJECT_ID')
            ?: 'tagpro-551a1';
    }

    private function loadCredentials(): array
    {
        $json = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');

        // En Vercel se utilizará la variable de entorno.
        // En XAMPP se conservará el archivo local.
        if ($json !== false && trim($json) !== '') {
            $credentials = json_decode($json, true);
        } else {
            $path = getenv('GOOGLE_APPLICATION_CREDENTIALS')
                ?: $this->credentialsPath;

            if (!is_file($path)) {
                throw new RuntimeException(
                    'No se encontraron las credenciales de Firebase.'
                );
            }

            $content = file_get_contents($path);
            $credentials = json_decode($content, true);
        }

        if (
            !is_array($credentials) ||
            empty($credentials['client_email']) ||
            empty($credentials['private_key'])
        ) {
            throw new RuntimeException(
                'Las credenciales de Firebase no son válidas.'
            );
        }

        return $credentials;
    }

    private function getAccessToken(): string
    {
        // Reutilizar el token mientras siga vigente.
        if (
            $this->accessToken !== null &&
            time() < $this->accessTokenExpiresAt - 60
        ) {
            return $this->accessToken;
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore'],
            $this->loadCredentials()
        );

        $token = $credentials->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new RuntimeException(
                'No se pudo obtener el token de Google.'
            );
        }

        $this->accessToken = $token['access_token'];

        $this->accessTokenExpiresAt =
            time() + (int) ($token['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    public function request(
        string $method,
        string $path,
        ?array $body = null
    ): array {

        $baseUrl =
            'https://firestore.googleapis.com/v1/projects/'
            . $this->projectId
            . '/databases/(default)/documents';

        $url = ($path === ':commit')
            ? $baseUrl . ':commit'
            : $baseUrl . '/' . ltrim($path, '/');

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 30
        ]);

        if ($body !== null) {
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($body, JSON_THROW_ON_ERROR)
            );
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Error de conexión: ' . $error
            );
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                'Firestore HTTP ' . $status . ': ' . $response
            );
        }

        return is_array($data) ? $data : [];
    }
}
