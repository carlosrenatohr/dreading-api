<?php

namespace App\Repositories;

use App\Models\Reading;
use App\Interfaces\ReadingInterface;

class ReadingRepository implements ReadingInterface
{
    public function fromToday()
    {
        $today = now()->today()->format('Y-m-d');
        $reading = Reading::where('date_raw', 'LIKE', "%{$today}%")->get();
        return $reading;
    }

    public function fromDate($date)
    {
        $selected_date = date('Y-m-d', strtotime($date));
        $reading = Reading::where('date_raw', 'LIKE', "%{$selected_date}%")->get();;
        return $reading;
    }
    
    public function lastReading()
    {
        $reading = Reading::all()->last();
        return $reading;
    }

    public function fromLastMonth()
    {
        $readings = Reading::where('date_raw', '>=', now()->subMonth())->get();
        return $readings;
    }
    
    public function fromLastWeek()
    {
        $readings = Reading::where('date_raw', '>=', now()->subWeek())->get();
        return $readings;
    }

    public function fromLastDay()
    {
        $readings = Reading::where('date_raw', '>=', now()->subDay())->get();
        return $readings;
    }

    

}