<?php

namespace App\Console\Commands;

use App\Models\SmartProcess;
use App\Services\BitrixService;
use Illuminate\Console\Command;

class OverdueSmartProcesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overdue-smarts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bx = new BitrixService();

        $tickets = $bx->getTicketsForUndone();

        foreach ($tickets as $ticket) {
            sleep(2);
            $createdActivity = $bx->createActivityForTicket($ticket, SmartProcess::CAT_OVERDUE);

            if ($createdActivity['result'] ?? null) {
                $createdActivityId = $createdActivity['result']['item']['id'];

                $updateData = [
                    'parentId177' => $createdActivityId
                ];

                $bx->updateSmartProcess($ticket['id'], SmartProcess::TYPE_TICKET, $updateData);
            }
        }

        $activities = $bx->getActivitiesForUndone();

        foreach ($activities as $activity) {
            sleep(1.5);
            $createdActivity = $bx->createActivityForActivity($activity, SmartProcess::CAT_OVERDUE);

            if ($createdActivity['result'] ?? null) {
                $createdActivityId = $createdActivity['result']['item']['id'];

                $updateData = ['parentId177' => $createdActivityId];

                $bx->updateSmartProcess($activity['id'], SmartProcess::TYPE_ACTIVITY, $updateData);
            }
        }
    }
}
