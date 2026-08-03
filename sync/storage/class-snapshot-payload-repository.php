<?php

namespace BlackPrint\Commerce\Sync\Storage;

use wpdb;
use BlackPrint\Commerce\Sync\Contracts\SnapshotPayloadRepositoryInterface;

defined('ABSPATH') || exit;

class SnapshotPayloadRepository implements SnapshotPayloadRepositoryInterface
{
    public function __construct(
        private wpdb $db
    ) {
    }

    private function table(): string
    {
        return $this->db->prefix . 'bp_snapshot_payloads';
    }

    /**
     * Save payload.
     */
public function save(
    string $snapshotUuid,
    array $payload
): void {

    $json = wp_json_encode($payload);

    $compressed = gzencode($json, 9);

    $encoded = base64_encode($compressed);

    $this->db->replace(

        $this->table(),

        [

            'snapshot_uuid' => $snapshotUuid,

            'payload' => $encoded,

            'payload_size' => strlen($compressed),

            'compression' => 'gzip',

            'created_at' => current_time(
                'mysql',
                true
            ),

        ]

    );
}

    /**
     * Find payload.
     */
    public function find(
        string $snapshotUuid
    ): ?array {

        $payload = $this->db->get_var(

            $this->db->prepare(

                sprintf(

                    'SELECT payload
                     FROM %s
                     WHERE snapshot_uuid = %%s',

                    $this->table()

                ),

                $snapshotUuid

            )

        );

        if ($payload === null) {
            return null;
        }

        $decoded = base64_decode($payload);

$uncompressed = gzdecode($decoded);

if ($uncompressed === false) {
    return null;
}

return json_decode(
    $uncompressed,
    true
);
    }

    /**
     * Delete payload.
     */
    public function delete(
        string $snapshotUuid
    ): void {

        $this->db->delete(

            $this->table(),

            [

                'snapshot_uuid' => $snapshotUuid,

            ]

        );
    }
}