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

    const TYPE_TICKET = 136;
    const TYPE_ACTIVITY = 177;
    const TYPE_CHILD_TICKET = 1040;

    const CAT_COMMUNICATION_AM = '9';
    const CAT_COMMUNICATION_KZ = '12';
    const CAT_COMMUNICATION_UZ = '13';
    const CAT_COMMUNICATION_XXX = '37';

    const CAT_AM_TROUBLE = '14';
    const CAT_KZ_TROUBLE = '16';
    const CAT_UZ_TROUBLE = '17';
    const CAT_ABLY_TROUBLE = '18';
    const CAT_XXX_TROUBLE = '36';
    const CAT_BUSINESS_ARMENIA = '30';

    const CAT_UNDONE = 27;
    const CAT_OVERDUE = 26;

    const AM_TROUBLE_OPEN = 'DT136_14:NEW';
    const AM_TROUBLE_IN_PROGRESS = 'DT136_14:PREPARATION';
    const AM_TROUBLE_WAITING_FOR_CUSTOMER = 'DT136_14:UC_IFNSU8';
    const AM_TROUBLE_NO_ANSWER = 'DT136_14:UC_K0NLHD';
    const AM_TROUBLE_FOLLOW_UP = 'DT136_14:UC_URLCVA';
    const KZ_TROUBLE_OPEN = 'DT136_16:NEW';
    const KZ_TROUBLE_IN_PROGRESS = 'DT136_16:PREPARATION';
    const KZ_TROUBLE_WAITING_FOR_CUSTOMER = 'DT136_16:CLIENT';
    const KZ_TROUBLE_NO_ANSWER = 'DT136_16:UC_PHVTS5';
    const KZ_TROUBLE_FOLLOW_UP = 'DT136_16:UC_T5BDVA';
    const UZ_TROUBLE_OPEN = 'DT136_17:NEW';
    const UZ_TROUBLE_IN_PROGRESS = 'DT136_17:PREPARATION';
    const UZ_TROUBLE_WAITING_FOR_CUSTOMER = 'DT136_17:CLIENT';
    const UZ_TROUBLE_NO_ANSWER = 'DT136_17:UC_ZVPHVD';
    const UZ_TROUBLE_FOLLOW_UP = 'DT136_17:UC_MEMKD8';
    const ABLY_TROUBLE_OPEN = 'DT136_18:NEW';
    const ABLY_TROUBLE_IN_PROGRESS = 'DT136_18:PREPARATION';
    const ABLY_TROUBLE_WAITING_FOR_CUSTOMER = 'DT136_18:CLIENT';
    const ABLY_TROUBLE_NO_ANSWER = 'DT136_18:UC_4NJ3UO';
    const ABLY_TROUBLE_FOLLOW_UP = 'DT136_18:UC_28XZFS';
    const BUSINESS_ARMENIA_OPEN = 'DT136_30:NEW';
    const BUSINESS_ARMENIA_IN_PROGRESS = 'DT136_30:PREPARATION';
    const BUSINESS_ARMENIA_WAITING_FOR_CUSTOMER = 'DT136_30:CLIENT';
    const XXX_OPEN = 'DT136_36:NEW';
}
