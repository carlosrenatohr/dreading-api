<?php

namespace App\Interfaces;

interface ReadingInterface
{
    public function fromToday(int $perPage);
    public function fromDate($date, int $perPage);
    public function lastReading();
    public function fromLastMonth(int $perPage);
    public function fromLastWeek(int $perPage);
    public function fromLastDay(int $perPage);
}
