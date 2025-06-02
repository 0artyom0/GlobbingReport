<?php

namespace App\Http\Controllers;

use App\Jobs\FilterDataJob;
use App\Models\FileCsv;
use App\Services\BitrixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class FilterDataController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        $typeID = $request->typeID;
        $columns = $request->input('select');
        $order = $request->input('order');
        $filters = $request->input('filter');
        $fileName = Str::random(10) . '_' . $type . '.csv';

        $fileCsv = FileCsv::create([
            'fileName' => $fileName,
            'status' => 'new',
        ]);

        $bx = new BitrixService();

        $method = null;

        switch ($type) {
            case 'item':
                $method = 'crm.item.list';
                break;
            case 'deal':
                $method = 'crm.deal.list';
                break;
            case 'lead':
                $method = 'crm.lead.list';
                break;
        }

        $start = microtime(true);

        $total = $bx->getTotal($method, $order, $filters, $columns, $typeID);

        $end = microtime(true);

        $processedTime = $end - $start;

        $avarageTime = round($total / 50 / 50 * $processedTime);

        FilterDataJob::dispatch($columns, $order, $filters, $fileName, $fileCsv, $type, $typeID);

        return response()->json([
            'success' => 'Data is being filtered',
            'fileName' => $fileName,
            'execution_time' => $avarageTime,
        ]);
    }

    public function exportData(Request $request)
    {
        $fileName = $request->fileName;
        if (!Storage::disk('public')->exists('/csvFile/' . $fileName)) {
            abort(404);
        }
        $url = public_path('/storage/csvFile/' . $fileName);

        $exportedFileName = date('Y_m_d') . '_data.csv';
        return Response::download($url, $exportedFileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
