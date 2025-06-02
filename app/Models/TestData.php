<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestData extends Model
{
    use HasFactory;

    protected $table='b_crm_deal';
    public $timestamps = false;
    protected $fillable=[
     'ID',
	'DATE_CREATE',
	'DATE_MODIFY',
	'CREATED_BY_ID',
	'MODIFY_BY_ID',
	'ASSIGNED_BY_ID',
	'OPENED',
	'LEAD_ID',
	'COMPANY_ID',
	'CONTACT_ID',
	'QUOTE_ID',
	'TITLE',
	'PRODUCT_ID',
	'CATEGORY_ID',
	'STAGE_ID',
	'STAGE_SEMANTIC_ID',
	'IS_NEW',
	'IS_RETURN_CUSTOMER',
	'IS_REPEATED_APPROACH',
	'CLOSED',
	'TYPE_ID',
	'OPPORTUNITY',
	'IS_MANUAL_OPPORTUNITY',
	'TAX_VALUE',
	'CURRENCY_ID',
	'OPPORTUNITY_ACCOUNT',
	'TAX_VALUE_ACCOUNT',
	'ACCOUNT_CURRENCY_ID',
	'PROBABILITY',
	'COMMENTS',
	'BEGINDATE',
	'CLOSEDATE',
	'EVENT_DATE',
	'EVENT_ID',
	'EVENT_DESCRIPTION',
	'EXCH_RATE',
	'LOCATION_ID',
	'WEBFORM_ID',
	'SOURCE_ID',
	'SOURCE_DESCRIPTION',
	'ORIGINATOR_ID',
	'ORIGIN_ID',
	'ADDITIONAL_INFO',
	'SEARCH_CONTENT',
	'ORDER_STAGE',
	'MOVED_BY_ID',
	'MOVED_TIME',
	'LAST_ACTIVITY_BY',
	'LAST_ACTIVITY_TIME',
     'STATUS_ID'
    ];


    public function utsCrmDeal()
    {
        return $this->hasMany(UtsCrmDeal::class,'VALUE_ID','ID');
    }
}
