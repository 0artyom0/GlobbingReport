<?php

namespace App\Jobs;

use App\Models\FileCsv;
use App\Services\BitrixService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FilterDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 12000;
    public $tries = 5;
    public $data;
    public $response;
    public $fileName;
    public $fileCsv;
    public $columns;
    public $order;
    public $filters;
    public $type;
    public $typeID;

    /**
     * Create a new job instance.
     */
    public function __construct($columns, $order, $filters, $fileName, $fileCsv, $type, $typeID)
    {
        $this->columns = $columns;
        $this->order = $order;
        $this->filters = $filters;
        $this->fileName = $fileName;
        $this->fileCsv = $fileCsv;
        $this->type = $type;
        $this->typeID = $typeID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bx = new BitrixService();

        if ($this->type == 'deal') {
            $entities = $bx->getDeals($this->order, $this->filters, $this->columns);

        } else if ($this->type == 'lead') {
            $entities = $bx->getLeads($this->order, $this->filters, $this->columns);

        } else if ($this->type == 'item') {
            $columnsMain = $this->columns;

            $items = $bx->getItems($this->order, $this->filters, $this->columns, $this->typeID);

            foreach ($items as $pos => $item) {
                $entities[$pos] = [];
                foreach ($columnsMain as $col) {
                    if (isset($item[$col])) {
                        $entities[$pos][$col] = $item[$col];
                    }
                }
            }
        }

        $filename = storage_path('/app/public/csvFile/' . $this->fileName);
        $handle = fopen($filename, 'w+');
        fputcsv($handle, $this->columns, ';', chr(127));

        if (!empty($entities)) {
            foreach ($entities as $data) {
                fputcsv($handle, (array)$data, ';', chr(127));
            }
        }

        fclose($handle);
        $csvFile = file_get_contents($filename);
        $csvFile = "\xEF\xBB\xBF" . $csvFile;

        file_put_contents($filename, $csvFile);
        $fileCsv = FileCsv::where('id', $this->fileCsv->id)->first();
        $fileCsv = FileCsv::find($this->fileCsv->id);


        if (!empty($fileCsv)) {
            $fileCsv->status = 'finish';
            $fileCsv->save();
        }

        DB::disconnect();
    }

    public function failed(?Throwable $exception): void
    {
        // Send user notification of failure, etc...
    }
}
