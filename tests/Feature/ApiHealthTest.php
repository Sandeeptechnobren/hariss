<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Passport\Passport;

class ApiHealthTest extends TestCase
{
    public function test_items_list_api()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();

        Passport::actingAs($user);

        // $response = $this->getJson('/api/master/items/list');
        $response = $this->getJson(config('app.url').'/api/master/items/list');
        $response->assertStatus(200);
    }
}