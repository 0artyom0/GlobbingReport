<?php

namespace App\Http\Controllers;

use App\Jobs\FilterDataJob;
use App\Models\FileCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CsvController extends Controller
{
    public function generateCsv($columns, $order, $filters, $type, $typeID): JsonResponse
    {
        $fileName = 'export_' . time() . '.csv';
        
        // Create and save FileCsv record
        $fileCsv = new FileCsv();
        $fileCsv->name = $fileName;
        $fileCsv->status = 'processing';
        $fileCsv->save();

        // Dispatch the job
        FilterDataJob::dispatch($columns, $order, $filters, $fileName, $fileCsv, $type, $typeID);

        // Get the average execution time from cache (if available)
        $avgExecutionTime = Cache::get('queueTime', 0);
        $estimatedWaitTime = Cache::get('queueWaitTime', 0);
        
        return response()->json([
            'message' => 'CSV generation started',
            'file_id' => $fileCsv->id,
            'estimated_total_time' => $avgExecutionTime + $estimatedWaitTime,
            'estimated_queue_wait' => $estimatedWaitTime,
            'estimated_processing_time' => $avgExecutionTime,
        ]);
    }
} 