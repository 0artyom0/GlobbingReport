<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'bitrix_id',
        'entityTypeId',
        'ufCrm6_1734527827434',
        'ufCrm6_1741187664356',
        'ufCrm7_1740770891',
        'ufCrm7_1741187664356',
        'updatedBy',
        'updatedTime',
        'stageId',
        'assignedById',
    ];

    protected $casts = [
        'ufCrm6_1734679393381' => 'array',
        'ufCrm6_1734680885162' => 'array',
        'ufCrm6_1734682286829' => 'array',
        'ufCrm6_1734685351754' => 'array',
        'ufCrm6_1743763679779' => 'array',
        'ufCrm6_1744571958946' => 'array',
        'observers' => 'array',
        'contactIds' => 'array',
        'ufCrmProductIds' => 'array',
        'ufCrm7_1743763712024' => 'array',
    ];
}
