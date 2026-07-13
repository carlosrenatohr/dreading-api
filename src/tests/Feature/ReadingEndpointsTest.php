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
        $response->assertJsonStructure([['title', 'lecturas']]);
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
        $response->assertJsonFragment(['title' => 'Test reading']);
    }
}
