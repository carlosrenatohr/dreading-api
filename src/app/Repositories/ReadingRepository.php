<?php

namespace App\Repositories;

use App\Models\Reading;
use App\Interfaces\ReadingInterface;

class ReadingRepository implements ReadingInterface
{
    public function fromToday(int $perPage)
    {
        $today = now()->today()->format('Y-m-d');
        return Reading::where('date_raw', 'LIKE', "%{$today}%")->paginate($perPage);
    }

    public function fromDate($date, int $perPage)
    {
        $selected_date = date('Y-m-d', strtotime($date));
        return Reading::where('date_raw', 'LIKE', "%{$selected_date}%")->paginate($perPage);
    }

    public function lastReading()
    {
        return Reading::orderByDesc('date_raw')->first();
    }

    public function fromLastMonth(int $perPage)
    {
        return Reading::where('date_raw', '>=', now()->subMonth())->paginate($perPage);
    }

    public function fromLastWeek(int $perPage)
    {
        return Reading::where('date_raw', '>=', now()->subWeek())->paginate($perPage);
    }

    public function fromLastDay(int $perPage)
    {
        return Reading::where('date_raw', '>=', now()->subDay())->paginate($perPage);
    }
}
