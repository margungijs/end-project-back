<?php

use App\Models\User;

test('User can send friend requests', function () {
    $user = User::factory()->create();
    $user1 = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $response->assertStatus(204);

    $request = $this->post('/api/authenticated/friendAdd', [
        'friend_id' => $user1->id,
    ]);

    $request->assertStatus(204);
});

test('User can accept friend requests', function () {
    $user = User::factory()->create();
    $user1 = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $response->assertStatus(204);

    $request = $this->post('/api/authenticated/friendAdd', [
        'friend_id' => $user1->id,
    ]);

    $request->assertStatus(204);

    $this->post('/logout');

    $response = $this->post('/login', [
        'name' => $user1->name,
        'password' => 'password'
    ]);

    $response->assertStatus(204);

    $accept = $this->post('/api/authenticated/friendAccept', [
        'user_id' => $user->id,
    ]);

    $accept->assertStatus(204);
});

test('User cannot accept friend requests that arent sent to them', function () {
    $user = User::factory()->create();
    $user1 = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $response->assertStatus(204);

    $accept = $this->post('/api/authenticated/friendAccept', [
        'user_id' => $user1->id,
    ]);

    $accept->assertStatus(404);
});

test('User can decline friend requests', function () {
    $user = User::factory()->create();
    $user1 = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $response->assertStatus(204);

    $request = $this->post('/api/authenticated/friendAdd', [
        'friend_id' => $user1->id,
    ]);

    $request->assertStatus(204);

    $this->post('/logout');

    $response = $this->post('/login', [
        'name' => $user1->name,
        'password' => 'password'
    ]);

    $response->assertStatus(204);

    $decline = $this->post('/api/authenticated/removeFriend', [
        'user_id' => $user->id,
    ]);

    $decline->assertStatus(204);
});

test('User can remove a friend', function () {
    $user = User::factory()->create();
    $user1 = User::factory()->create();

    $response = $this->post('/login', [
        'name' => $user->name,
        'password' => 'password',
    ]);

    $response->assertStatus(204);

    $request = $this->post('/api/authenticated/friendAdd', [
        'friend_id' => $user1->id,
    ]);

    $request->assertStatus(204);

    $this->post('/logout');

    $response = $this->post('/login', [
        'name' => $user1->name,
        'password' => 'password'
    ]);

    $response->assertStatus(204);

    $accept = $this->post('/api/authenticated/friendAccept', [
        'user_id' => $user->id,
    ]);

    $accept->assertStatus(204);

    $remove = $this->post('/api/authenticated/removeFriend', [
        'user_id' => $user->id,
    ]);

    $remove->assertStatus(204);
});


