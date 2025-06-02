<?php

namespace App\Exports;

use App\Models\TempCrmDeal;
use App\Models\TestData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;

//class ExportData implements  FromQuery, WithChunkReading
class ExportData implements  FromQuery, WithChunkReading, ShouldQueue
//class ExportData implements  FromQuery, ShouldQueue
//class ExportData implements FromCollection, ShouldQueue
//class ExportData implements FromCollection
{
        use Exportable, Queueable;
//        use Exportable;
//    /**
//    * @return \Illuminate\Support\Collection
//    */
//    public function collection()
//    {
//        return collect(Cache::get('data'));
//    }

    public function query()
    {
        return TempCrmDeal::query();
    }
    public function chunkSize(): int
    {
        return 1000; // Adjust chunk size as needed
    }

}
