<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('serves an existing file from the public disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/example.txt', 'hello world');

    $response = $this->get('/media/avatars/example.txt');

    $response->assertStatus(200);
    expect($response->streamedContent())->toBe('hello world');
});

test('sets a long-lived cache header, since filenames are unique per upload', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/example.txt', 'hello world');

    $response = $this->get('/media/avatars/example.txt');

    // Symfony's ResponseHeaderBag canonicalizes Cache-Control directives into
    // its own (alphabetical) order, regardless of the order we set them in.
    $response->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
});

test('returns 404 for a file that does not exist', function () {
    Storage::fake('public');

    $response = $this->get('/media/avatars/does-not-exist.jpg');

    $response->assertStatus(404);
});

test('rejects a path traversal attempt', function () {
    Storage::fake('public');

    $response = $this->get('/media/../../.env');

    $response->assertStatus(404);
});

test('an uploaded profile photo is actually retrievable through the media route', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $uploadResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/admin/profile', [
            '_method' => 'PUT',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $uploadResponse->assertStatus(200);

    // Storage::fake() always serves URLs under the conventional /storage/
    // prefix for test isolation, regardless of the real "public" disk's
    // configured url — so build the expected media route from the stored
    // path itself (not avatar_url) to test what production actually does.
    $storedPath = $admin->fresh()->avatar;
    expect($storedPath)->not->toBeNull();

    $this->get("/media/{$storedPath}")->assertStatus(200);
});

test('the real (non-faked) public disk generates /media/ urls', function () {
    expect(Storage::disk('public')->url('avatars/example.jpg'))->toContain('/media/avatars/example.jpg');
});
