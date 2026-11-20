
<?php

require_once __DIR__ . '/firestore.php';

class FirestoreSessionHandler implements SessionHandlerInterface
{
    private Firestore $firestore;
    private string $collection = 'php_sessions';

    public function __construct()
    {
        $this->firestore = new Firestore();
    }

    private function documentId(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $document = $this->firestore->request(
                'GET',
                $this->collection . '/' . $this->documentId($id)
            );

            $fields = $document['fields'] ?? [];

            $expires = $fields['expires_at']['timestampValue'] ?? '';
            $data = $fields['data']['stringValue'] ?? '';

            if (!$expires || strtotime($expires) <= time()) {
                $this->destroy($id);
                return '';
            }

            $decoded = base64_decode($data, true);

            return $decoded === false ? '' : $decoded;

        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Firestore HTTP 404:') === 0) {
                return '';
            }

            error_log('Error leyendo sesión Firestore: ' . $e->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $now = time();
            $expires = $now + (int) ini_get('session.gc_maxlifetime');

            $document = [
                'fields' => [
                    'data' => [
                        'stringValue' => base64_encode($data)
                    ],
                    'expires_at' => [
                        'timestampValue' => gmdate(
                            'Y-m-d\TH:i:s\Z',
                            $expires
                        )
                    ],
                    'updated_at' => [
                        'timestampValue' => gmdate(
                            'Y-m-d\TH:i:s\Z',
                            $now
                        )
                    ]
                ]
            ];

            $this->firestore->request(
                'PATCH',
                $this->collection . '/' . $this->documentId($id),
                $document
            );

            return true;

        } catch (Throwable $e) {
            error_log('Error guardando sesión Firestore: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->firestore->request(
                'DELETE',
                $this->collection . '/' . $this->documentId($id)
            );

            return true;

        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Firestore HTTP 404:') === 0) {
                return true;
            }

            error_log('Error eliminando sesión Firestore: ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        // Las sesiones vencidas se eliminan al consultarlas.
        // La limpieza general se configurará mediante TTL de Firestore.
        return 0;
    }
}
