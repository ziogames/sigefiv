
<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/mi-cuenta');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/mi-cuenta')
        ->put('/mi-cuenta', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'seudonimo' => 'Test',
            'telefono' => '999999999',
            'dni' => '12345678',
            'direccion' => 'Lima',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/mi-cuenta');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('Test', $user->seudonimo);
    $this->assertSame('999999999', $user->telefono);
    $this->assertSame('12345678', $user->dni);
    $this->assertSame('Lima', $user->direccion);
});
