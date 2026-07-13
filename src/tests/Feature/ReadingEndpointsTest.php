<?php

namespace Tests\Feature;

use App\Models\Reading;
use Tests\TestCase;

class ReadingEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Reading::truncate();

        Reading::create([
            'title' => 'Test reading',
            'date_title' => '13/07/2026',
            'date_raw' => date('Y-m-d').' 00:00:00',
            'lecturas' => [[
                'title' => 'Primera Lectura',
                'content' => '...',
                'first_line' => '...',
                'last_line' => 'Palabra de Dios',
            ]],
        ]);
    }

    protected function tearDown(): void
    {
        Reading::truncate();

        parent::tearDown();
    }

    public function test_last_returns_the_reading()
    {
        $response = $this->getJson('/api/v1/readings/last');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'title',
            'lecturas' => [
                ['title', 'content', 'first_line', 'last_line'],
            ],
        ]);
        $response->assertJsonFragment(['title' => 'Test reading']);
    }

    public function test_date_valid_returns_200()
    {
        $today = date('Y-m-d');

        $response = $this->getJson("/api/v1/readings/date/{$today}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [['title', 'lecturas']]]);
        $response->assertJsonFragment(['title' => 'Test reading']);
    }

    public function test_date_invalid_returns_422()
    {
        $response = $this->getJson('/api/v1/readings/date/not-a-date');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date');
    }

    public function test_today_returns_200()
    {
        $response = $this->getJson('/api/v1/readings/today');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'per_page', 'total']);
        $response->assertJsonFragment(['title' => 'Test reading']);
    }

    public function test_list_is_paginated()
    {
        for ($i = 0; $i < 2; $i++) {
            Reading::create([
                'title' => "Extra {$i}",
                'date_title' => '13/07/2026',
                'date_raw' => date('Y-m-d').' 00:00:00',
                'lecturas' => [],
            ]);
        }

        $response = $this->getJson('/api/v1/readings/today?per_page=2');

        $response->assertStatus(200);
        $response->assertJsonPath('per_page', 2);
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(3, $response->json('total'));
    }

    public function test_v2_redirects_to_v1()
    {
        $response = $this->get('/api/v2/readings');

        $response->assertStatus(301);
        $response->assertRedirect('/api/v1/readings');
    }

    public function test_endpoints_are_rate_limited()
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/readings/last')->assertStatus(200);
        }

        $this->getJson('/api/v1/readings/last')->assertStatus(429);
    }
}
