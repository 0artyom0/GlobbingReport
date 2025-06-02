<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartProcessActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'field',
        'updated_by',
        'updated_date',
        'element_id',
        'old_value',
        'new_value',
        'entity_type_id',
    ];
}
