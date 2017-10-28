<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{

    public $table = 'cars';

    public $timestamps = false;

    public $fillable = [
        'uid',
        'year',
        'brand',
        'series',
        'fuel',
        'body',
        'model',
        'version',
        'data'
    ];

    public $casts = [
        'year' => 'integer',
        'data' => 'array'
    ];

}
