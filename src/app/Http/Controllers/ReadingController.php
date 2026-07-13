<?php

namespace App\Http\Controllers;

use App\Models\Reading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Interfaces\ReadingInterface;

class ReadingController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE = 100;

    private ReadingInterface $readingInterface;

    public function __construct(ReadingInterface $readingInterface)
    {
        $this->readingInterface = $readingInterface;
    }

    public function from_last_month()
    {
        $readings = $this->readingInterface->fromLastMonth($this->perPage());
        return response()->json($readings);
    }

    public function from_last_week()
    {
        $readings = $this->readingInterface->fromLastWeek($this->perPage());
        return response()->json($readings);
    }

    public function from_last_day()
    {
        $readings = $this->readingInterface->fromLastDay($this->perPage());
        return response()->json($readings);
    }

    public function from_today()
    {
        $readings = $this->readingInterface->fromToday($this->perPage());
        return response()->json($readings);
    }

    public function from_date($date)
    {
        $validator = Validator::make(['date' => $date], [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid date. Expected format: Y-m-d (e.g. 2026-07-13).',
                'errors' => $validator->errors(),
            ], 422);
        }

        $readings = $this->readingInterface->fromDate($date, $this->perPage());
        return response()->json($readings);
    }

    public function last_reading()
    {
        $reading = $this->readingInterface->lastReading();
        return response()->json($reading);
    }

    // -- Clamp the ?per_page query param to a sane range.
    private function perPage(): int
    {
        return min(max((int) request('per_page', self::DEFAULT_PER_PAGE), 1), self::MAX_PER_PAGE);
    }
}
