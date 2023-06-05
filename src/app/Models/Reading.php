<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Reading extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'readings';
}
