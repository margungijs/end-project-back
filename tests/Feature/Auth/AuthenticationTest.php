<?php

use App\Models\User;

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertNoContent();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'name' => $user->name,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertNoContent();
});

test('user is assigned a valid session cookie on auth', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();

    $response = $this->get('/api/authenticated/user');
    $response->assertStatus(201);
});

test("protected routes aren't accessible without authentication", function () {
    $response = $this->get('/api/authenticated/user');
    $response->assertStatus(401);
});


