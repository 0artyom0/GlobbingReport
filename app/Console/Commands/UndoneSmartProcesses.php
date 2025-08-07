<?php

namespace App\Console\Commands;

use App\Models\SmartProcess;
use App\Services\BitrixService;
use Illuminate\Console\Command;

class UndoneSmartProcesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'undone-smarts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $bx = new BitrixService();
        $tickets = $bx->getTicketsForUndone();

        $tickets = collect($tickets)->filter(function ($ticket) {
            return $ticket['ufCrm6_1753874960515'] != '' || $ticket['ufCrm6_1753874960515'] != null;
        });

        foreach ($tickets as $ticket) {
            $createdActivity = $bx->createActivityForTicket($ticket, SmartProcess::CAT_UNDONE);

            if ($createdActivity['result'] ?? null) {
                $createdActivityId = $createdActivity['result']['item']['id'];

                $updateData = [
                    'parentId177' => $createdActivityId,
                ];

                $bx->updateSmartProcess($ticket['id'], SmartProcess::TYPE_TICKET, $updateData);
            }
        }

        $activities = $bx->getActivitiesForUndone();

        $activities = collect($activities)->filter(function ($activity) {
            return $activity['ufCrm7_1753881696167'] != null || $activity['ufCrm7_1753881696167'] != '';
        });

        foreach ($activities as $activity) {
            $createdActivity = $bx->createActivityForActivity($activity, SmartProcess::CAT_UNDONE);
            if ($createdActivity['result'] ?? null) {
                $createdActivityId = $createdActivity['result']['item']['id'];

                $updateData = [
                    'parentId177' => $createdActivityId,
                ];

                $bx->updateSmartProcess($activity['id'], SmartProcess::TYPE_ACTIVITY, $updateData);
            }
        }
    }
}
