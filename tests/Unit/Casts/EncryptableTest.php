<?php

declare(strict_types=1);

use App\Casts\Encryptable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    // Ensure the application key is set for encryption.
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
});

it('encrypts a plaintext string on set', function (): void {
    $cast  = new Encryptable;
    $model = Mockery::mock(Model::class);

    $ciphertext = $cast->set($model, 'field', 'my secret value', []);

    expect($ciphertext)
        ->not->toBeNull()
        ->not->toBe('my secret value');

    // The ciphertext must be a valid Laravel-encrypted string.
    expect(Crypt::decryptString($ciphertext))->toBe('my secret value');
});

it('decrypts a ciphertext string on get', function (): void {
    $cast       = new Encryptable;
    $model      = Mockery::mock(Model::class);
    $ciphertext = Crypt::encryptString('my secret value');

    $plaintext = $cast->get($model, 'field', $ciphertext, []);

    expect($plaintext)->toBe('my secret value');
});

it('returns null on set when value is null', function (): void {
    $cast  = new Encryptable;
    $model = Mockery::mock(Model::class);

    expect($cast->set($model, 'field', null, []))->toBeNull();
});

it('returns null on get when value is null', function (): void {
    $cast  = new Encryptable;
    $model = Mockery::mock(Model::class);

    expect($cast->get($model, 'field', null, []))->toBeNull();
});

it('roundtrips empty string through encrypt and decrypt', function (): void {
    $cast  = new Encryptable;
    $model = Mockery::mock(Model::class);

    $ciphertext = $cast->set($model, 'field', '', []);
    $plaintext  = $cast->get($model, 'field', $ciphertext, []);

    expect($plaintext)->toBe('');
});
