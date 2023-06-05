<?php

namespace App\Interfaces;

interface ReadingInterface
{
    public function fromToday();
    public function fromDate($date);
    public function lastReading();
    public function fromLastMonth();
    public function fromLastWeek();
    public function fromLastDay();
}