<?php

namespace Database\Seeders;

use App\Models\Reading;
use Illuminate\Database\Seeder;

class ReadingSeeder extends Seeder
{
    /**
     * Seed one sample reading (same shape the scraper writes) so the API can be
     * exercised locally without a populated MongoDB Atlas cluster.
     */
    public function run()
    {
        $dateRaw = date('Y-m-d') . ' 00:00:00';

        if (Reading::where('date_raw', $dateRaw)->exists()) {
            return;
        }

        Reading::create([
            'title' => 'Lecturas de hoy (sample)',
            'date_title' => date('d/m/Y'),
            'date_raw' => $dateRaw,
            'lecturas' => [
                [
                    'title' => 'Primera lectura',
                    'content' => 'Sample first reading content.',
                    'first_line' => 'Lectura del libro...',
                    'last_line' => 'Palabra de Dios.',
                ],
                [
                    'title' => 'Salmo',
                    'content' => 'Sample psalm content.',
                    'first_line' => 'Salmo responsorial',
                    'psalm' => 'R. Sample response.',
                ],
                [
                    'title' => 'Evangelio de hoy',
                    'content' => 'Sample gospel content.',
                    'first_line' => 'Lectura del santo Evangelio...',
                    'last_line' => 'Palabra del Señor.',
                ],
            ],
        ]);
    }
}
