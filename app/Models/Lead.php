<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $table='b_crm_lead';

    public function utsCrmLead()
    {
        return $this->hasMany(UtsCrmLead::class,'VALUE_ID','ID');
    }
}
