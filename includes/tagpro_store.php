
<?php

require_once __DIR__ . '/firestore.php';

/**
 * Instancia única de conexión con Firestore.
 */
function tagpro_firestore(): Firestore
{
    static $instance = null;

    if ($instance === null) {
        $instance = new Firestore();
    }

    return $instance;
}

/**
 * Convierte valores de Firestore a valores PHP.
 */
function tagpro_decode_value(array $value)
{
    if (array_key_exists('stringValue', $value)) {
        return $value['stringValue'];
    }

    if (array_key_exists('integerValue', $value)) {
        return (int) $value['integerValue'];
    }

    if (array_key_exists('doubleValue', $value)) {
        return (float) $value['doubleValue'];
    }

    if (array_key_exists('booleanValue', $value)) {
        return $value['booleanValue'];
    }

    if (array_key_exists('timestampValue', $value)) {
        return $value['timestampValue'];
    }

    if (array_key_exists('nullValue', $value)) {
        return null;
    }

    if (isset($value['arrayValue']['values'])) {
        return array_map(
            'tagpro_decode_value',
            $value['arrayValue']['values']
        );
    }

    if (isset($value['mapValue']['fields'])) {
        return tagpro_decode_fields($value['mapValue']['fields']);
    }

    return null;
}

/**
 * Convierte los campos de un documento.
 */
function tagpro_decode_fields(array $fields): array
{
    $result = [];

    foreach ($fields as $name => $value) {
        $result[$name] = tagpro_decode_value($value);
    }

    return $result;
}

/**
 * Convierte datos PHP al formato de Firestore.
 */
function tagpro_encode_fields(array $data): array
{
    $fields = [];

    foreach ($data as $name => $value) {

        if ($value === null) {
            $fields[$name] = ['nullValue' => null];
        } elseif (in_array($name, ['id', 'scans'], true)) {
            $fields[$name] = [
                'integerValue' => (string) ((int) $value)
            ];
        } elseif (in_array($name, ['created_at', 'updated_at'], true)) {
            $date = new DateTimeImmutable(
                (string) $value,
                new DateTimeZone('America/Mexico_City')
            );

            $fields[$name] = [
                'timestampValue' => $date->format('Y-m-d\TH:i:sP')
            ];
        } elseif (is_bool($value)) {
            $fields[$name] = ['booleanValue' => $value];
        } elseif (is_int($value)) {
            $fields[$name] = ['integerValue' => (string) $value];
        } elseif (is_float($value)) {
            $fields[$name] = ['doubleValue' => $value];
        } else {
            $fields[$name] = ['stringValue' => (string) $value];
        }
    }

    return $fields;
}

/**
 * Convierte un documento completo a un arreglo PHP.
 */
function tagpro_decode_document(array $document): array
{
    return tagpro_decode_fields($document['fields'] ?? []);
}

/**
 * Consulta un documento por colección e identificador.
 */
function tagpro_get_document(string $collection, string $id): ?array
{
    try {
        $document = tagpro_firestore()->request(
            'GET',
            $collection . '/' . rawurlencode($id)
        );

        return tagpro_decode_document($document);

    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'Firestore HTTP 404:') === 0) {
            return null;
        }

        throw $e;
    }
}

/**
 * Consulta todos los documentos de una colección.
 */
function tagpro_list_documents(string $collection): array
{
    $result = [];
    $pageToken = null;

    do {
        $path = $collection . '?pageSize=1000';

        if ($pageToken !== null) {
            $path .= '&pageToken=' . urlencode($pageToken);
        }

        $response = tagpro_firestore()->request('GET', $path);

        foreach ($response['documents'] ?? [] as $document) {
            $row = tagpro_decode_document($document);

            $parts = explode('/', $document['name']);
            $row['_document_id'] = end($parts);

            $result[] = $row;
        }

        $pageToken = $response['nextPageToken'] ?? null;

    } while ($pageToken !== null);

    return $result;
}

/**
 * Crea o actualiza un documento con sus campos completos.
 */
function tagpro_save_document(
    string $collection,
    string $id,
    array $data
): array {
    $body = [
        'fields' => tagpro_encode_fields($data)
    ];

    return tagpro_firestore()->request(
        'PATCH',
        $collection . '/' . rawurlencode($id),
        $body
    );
}

/**
 * Elimina un documento.
 */
function tagpro_delete_document(string $collection, string $id): array
{
    return tagpro_firestore()->request(
        'DELETE',
        $collection . '/' . rawurlencode($id)
    );
}

/**
 * Incrementa atómicamente el contador de escaneos.
 */
function tagpro_increment_scans(string $code): array
{
    $documentPath =
        'projects/tagpro-551a1/databases/(default)/documents/qr_links/'
        . rawurlencode($code);

    return tagpro_firestore()->request('POST', ':commit', [
        'writes' => [
            [
                'transform' => [
                    'document' => $documentPath,
                    'fieldTransforms' => [
                        [
                            'fieldPath' => 'scans',
                            'increment' => [
                                'integerValue' => '1'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]);
}
