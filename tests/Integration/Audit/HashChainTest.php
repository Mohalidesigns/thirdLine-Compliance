<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tests\Integration\PostgresTestCase;

/**
 * Audit hash-chain integrity tests.
 *
 * These tests REQUIRE a live PostgreSQL 17 instance with pgcrypto enabled and
 * all migrations applied. They cannot run against SQLite.
 *
 * Run standalone: php artisan test --filter=HashChainTest
 *
 * Each test is wrapped in a database transaction that rolls back after the test
 * completes, so it does not pollute the development database.
 */
uses(PostgresTestCase::class);

/**
 * Compute the expected SHA-256 hash via Postgres pgcrypto for a given row.
 *
 * This mirrors the trigger function exactly:
 *   digest(prev_hash || canonical_bytes, 'sha256')
 * where prev_hash is '\x'::BYTEA (empty) for the genesis row.
 *
 * @param  string  $prevHashHex  Hex-encoded previous hash; empty string for genesis.
 */
function computeExpectedHash(string $prevHashHex, object $row): string
{
    // Build the prev_hash expression — genesis is empty bytea ('\x'), not NULL.
    $prevBytesExpr = $prevHashHex === ''
        ? "'\x'::BYTEA"                            // empty bytea, matches trigger genesis
        : "decode('" . $prevHashHex . "', 'hex')"; // subsequent rows

    $result = DB::selectOne(
        "SELECT encode(
            digest(
                {$prevBytesExpr} ||
                convert_to(:tenant_id,    'UTF8') ||
                convert_to(:actor_id,     'UTF8') ||
                convert_to(:actor_type,   'UTF8') ||
                convert_to(:action,       'UTF8') ||
                convert_to(:subject_type, 'UTF8') ||
                convert_to(:subject_id,   'UTF8') ||
                convert_to(:context,      'UTF8') ||
                convert_to(:recorded_at,  'UTF8'),
                'sha256'
            ),
            'hex'
        ) AS expected_hash",
        [
            'tenant_id'    => (string) $row->tenant_id,
            'actor_id'     => $row->actor_id !== null ? (string) $row->actor_id : 'NULL',
            'actor_type'   => $row->actor_type ?? 'NULL',
            'action'       => $row->action,
            'subject_type' => $row->subject_type ?? 'NULL',
            'subject_id'   => $row->subject_id !== null ? (string) $row->subject_id : 'NULL',
            'context'      => $row->context,
            'recorded_at'  => $row->recorded_at,
        ]
    );

    return $result->expected_hash;
}

it('inserts 100 audit events with a valid hash chain', function (): void {
    $tenantId = 1;

    for ($i = 0; $i < 100; $i++) {
        DB::connection('pgsql_integration')->table('audit_events')->insert([
            'tenant_id'    => $tenantId,
            'actor_id'     => null,
            'actor_type'   => 'system',
            'action'       => "test.action.{$i}",
            'subject_type' => null,
            'subject_id'   => null,
            'context'      => json_encode(['seq' => $i]),
            'recorded_at'  => now()->toIso8601ZuluString(),
        ]);
    }

    $rows = DB::connection('pgsql_integration')
        ->table('audit_events')
        ->where('tenant_id', $tenantId)
        ->orderBy('id')
        ->get()
        ->toArray();

    expect($rows)->toHaveCount(100);

    // Genesis: first row's prev_hash in the trigger is '\x'::BYTEA (0 bytes).
    $prevHashHex = '';

    foreach ($rows as $index => $row) {
        $expectedHash = computeExpectedHash($prevHashHex, $row);

        $storedHash = DB::connection('pgsql_integration')->selectOne(
            "SELECT encode(this_hash, 'hex') AS hash FROM audit_events WHERE id = ?",
            [$row->id]
        );

        expect($storedHash->hash)
            ->toBe($expectedHash, "Hash mismatch at chain index {$index} (id={$row->id})");

        $prevHashHex = $storedHash->hash;
    }
});

it('refuses UPDATE on audit_events and raises an exception', function (): void {
    DB::connection('pgsql_integration')->table('audit_events')->insert([
        'tenant_id'   => 1,
        'actor_type'  => 'system',
        'action'      => 'test.immutability.update',
        'context'     => '{}',
        'recorded_at' => now()->toIso8601ZuluString(),
    ]);

    $id = DB::connection('pgsql_integration')
        ->table('audit_events')
        ->where('action', 'test.immutability.update')
        ->orderByDesc('id')
        ->value('id');

    expect(fn () => DB::connection('pgsql_integration')
        ->table('audit_events')
        ->where('id', $id)
        ->update(['action' => 'tampered'])
    )->toThrow(Exception::class);
});

it('refuses DELETE on audit_events and raises an exception', function (): void {
    DB::connection('pgsql_integration')->table('audit_events')->insert([
        'tenant_id'   => 1,
        'actor_type'  => 'system',
        'action'      => 'test.immutability.delete',
        'context'     => '{}',
        'recorded_at' => now()->toIso8601ZuluString(),
    ]);

    $id = DB::connection('pgsql_integration')
        ->table('audit_events')
        ->where('action', 'test.immutability.delete')
        ->orderByDesc('id')
        ->value('id');

    expect(fn () => DB::connection('pgsql_integration')
        ->table('audit_events')
        ->where('id', $id)
        ->delete()
    )->toThrow(Exception::class);
});

it('links each rows prev_hash to the prior rows this_hash', function (): void {
    $tenantId = 1;

    for ($i = 0; $i < 10; $i++) {
        DB::connection('pgsql_integration')->table('audit_events')->insert([
            'tenant_id'   => $tenantId,
            'actor_type'  => 'system',
            'action'      => "test.chain.link.{$i}",
            'context'     => '{}',
            'recorded_at' => now()->toIso8601ZuluString(),
        ]);
    }

    $rows = DB::connection('pgsql_integration')->select(
        "SELECT encode(prev_hash,'hex') AS prev_h, encode(this_hash,'hex') AS this_h
         FROM audit_events
         WHERE tenant_id = ? AND action LIKE 'test.chain.link.%'
         ORDER BY id",
        [$tenantId]
    );

    expect($rows)->toHaveCount(10);

    // row[i].prev_hash must equal row[i-1].this_hash for the chain to be valid.
    for ($i = 1; $i < count($rows); $i++) {
        expect($rows[$i]->prev_h)
            ->toBe($rows[$i - 1]->this_h, "Row {$i}: prev_hash does not match prior this_hash");
    }
});
