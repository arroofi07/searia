<?php

use App\Models\ActivityLog;
use App\Support\SensitiveData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scrubs password and proof fields from audit payloads', function () {
    $scrubbed = SensitiveData::scrub([
        'email' => 'a@b.c',
        'password' => 'secret',
        'proof_path' => 'invoices/1/proof.jpg',
        'nested' => ['password_confirmation' => 'x', 'status' => 'ok'],
    ]);

    expect($scrubbed['password'])->toBe('[redacted]')
        ->and($scrubbed['proof_path'])->toBe('[redacted]')
        ->and($scrubbed['nested']['password_confirmation'])->toBe('[redacted]')
        ->and($scrubbed['nested']['status'])->toBe('ok')
        ->and($scrubbed['email'])->toBe('a@b.c');
});

it('refuses to update or delete activity log rows', function () {
    $user = \App\Models\User::factory()->panitia()->create();
    $log = ActivityLog::query()->create([
        'user_id' => $user->id,
        'action' => 'test.action',
        'subject_type' => \App\Models\User::class,
        'subject_id' => $user->id,
        'old_values' => ['password' => 'should-redact'],
        'new_values' => ['name' => 'A'],
    ]);

    expect($log->fresh()->old_values['password'])->toBe('[redacted]');

    expect(fn () => $log->update(['action' => 'hacked']))
        ->toThrow(RuntimeException::class);

    expect(fn () => $log->delete())
        ->toThrow(RuntimeException::class);
});
