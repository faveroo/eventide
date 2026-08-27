<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $hidden = [
        'name',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'slug',
    ];
}
