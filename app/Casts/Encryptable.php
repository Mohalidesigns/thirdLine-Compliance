<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Field-level encryption cast for PII and sensitive columns.
 *
 * -------------------------------------------------------------------------
 * DESIGN CONTRACT — DO NOT CHANGE THE METHOD SIGNATURES WITHOUT AN ADR
 * -------------------------------------------------------------------------
 * This cast wraps Laravel's built-in symmetric encryption (AES-256-CBC via
 * the APP_KEY) for MVP. The call-site interface is intentionally identical
 * to what a KMS-envelope implementation will need:
 *
 *   - get(): accepts raw DB bytes, returns plaintext string.
 *   - set(): accepts plaintext string, returns ciphertext for DB storage.
 *
 * When we replace this with KMS DEK wrapping (ADR-018) before go-live, the
 * only change is the body of get() and set(). Every model property annotated
 * with `Encryptable::class` in its $casts array continues to work without
 * modification.
 *
 * The stored format is the output of Crypt::encryptString() — a base64-encoded
 * JSON envelope containing iv, value, and mac. In the KMS variant this
 * envelope will carry an encrypted DEK and key-version instead of mac.
 * -------------------------------------------------------------------------
 *
 * @implements CastsAttributes<string|null, string|null>
 */
final class Encryptable implements CastsAttributes
{
    /**
     * Decrypt the value coming out of the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::decryptString((string) $value);
    }

    /**
     * Encrypt the value before writing it to the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
