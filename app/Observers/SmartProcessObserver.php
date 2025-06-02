<?php

namespace App\Observers;

use App\Models\SmartProcess;
use App\Models\SmartProcessActivity;
use App\Services\BitrixService;
use Carbon\Carbon;
use Exception;

class SmartProcessObserver
{
    /**
     * @throws Exception
     */
    public function created(SmartProcess $smartProcess): void
    {
        $bx = new BitrixService();

        if ($smartProcess->stageId) {
            $userId = $smartProcess->assignedById;
            $entityTypeId = $smartProcess->entityTypeId;
            $user = $bx->getUser($userId);
            $departmentId = !empty($user['UF_DEPARTMENT']) ? $user['UF_DEPARTMENT'] : [];
            $fixedScheduleId = $bx->getFixedScheduleId($smartProcess->stageId);
            $departmentId = [];
            $scheduleId = $bx->getScheduleIdByDepartment($departmentId) ?? $bx->getScheduleIdByUser(3);
            $scheduleId = $fixedScheduleId ?? $scheduleId;

            $schedule = $bx->getSchedule($scheduleId);
            $exclusions = $bx->getExclusions($schedule);
            $holidays = $bx->getHolidays($exclusions);
            $oldDueDate = Carbon::now();

            if ($entityTypeId == 136) {
                switch ($smartProcess->stageId) {
                    // Open
                    case 'DT136_14:NEW':
                    case 'DT136_16:NEW':
                    case 'DT136_17:NEW':
                    case 'DT136_18:NEW':

                        // In progress
                    case 'DT136_14:PREPARATION':
                    case 'DT136_16:PREPARATION':
                    case 'DT136_17:PREPARATION':
                    case 'DT136_18:PREPARATION':

                        // Waiting for customer
                    case 'DT136_14:UC_IFNSU8':
                    case 'DT136_16:CLIENT':
                    case 'DT136_17:CLIENT':
                    case 'DT136_18:CLIENT':

                    case 'DT136_9:PREPARATION':
                    case 'DT136_9:UC_CE7CSL':
                    case 'DT136_12:PREPARATION':
                    case 'DT136_12:UC_MWE4ZI':
                    case 'DT136_13:PREPARATION':
                    case 'DT136_13:UC_DHDLV5':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;

                    // Communication AM, Communication KZ, Communication UZ
                    case 'DT136_9:NEW':
                    case 'DT136_12:NEW':
                    case 'DT136_13:NEW':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 12, $holidays);
                        dd($newDueDate);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;

                    // No answered
                    case 'DT136_14:UC_K0NLHD':
                    case 'DT136_16:UC_PHVTS5':
                    case 'DT136_17:UC_ZVPHVD':
                    case 'DT136_18:UC_4NJ3UO':

                        // Follow Up
                    case 'DT136_14:UC_URLCVA':
                    case 'DT136_16:UC_T5BDVA':
                    case 'DT136_17:UC_MEMKD8':
                    case 'DT136_18:UC_28XZFS':

                    case 'DT136_32:NEW':
                    case 'DT136_33:NEW':
                    case 'DT136_30:NEW':

                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;
                    default:
                        if ($fixedScheduleId) {
                            $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                            $params['ufCrm6_1734527827434'] = $newDueDate;
                            $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        }
                }
            } elseif ($entityTypeId == 1040) {
                switch ($smartProcess->stageId) {
                    case 'DT1040_22:NEW':
                    case 'DT1040_22:PREPARATION':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                        $params['ufCrm9_1746797152'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        break;
                    default:
                        if ($fixedScheduleId) {
                            $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                            $params['ufCrm9_1746797152'] = $newDueDate;
                            $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        }
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function updated(SmartProcess $smartProcess): void
    {
        $bx = new BitrixService();
        $dirtyFields = $smartProcess->getDirty();

        foreach ($dirtyFields as $dirtyField => $newValue) {
            $oldValue = $smartProcess->getOriginal($dirtyField);

            if (
                $dirtyField == 'ufCrm6_1734527827434' ||
                $dirtyField == 'ufCrm7_1740770891' ||
                $dirtyField == 'ufCrm6_1741187664356' ||
                $dirtyField == 'stageId'
            ) {
                SmartProcessActivity::create([
                    'field' => $dirtyField,
                    'element_id' => $smartProcess->bitrix_id,
                    'entity_type_id' => $smartProcess->entityTypeId,
                    'updated_by' => $smartProcess->updatedBy,
                    'updated_date' => $smartProcess->updatedTime,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        }

        if ($smartProcess->isDirty('stageId')) {
            $userId = $smartProcess->assignedById;
            $entityTypeId = $smartProcess->entityTypeId;
            $user = $bx->getUser($userId);
            $departmentIds = !empty($user['UF_DEPARTMENT']) ? $user['UF_DEPARTMENT'] : [];
            $fixedScheduleId = $bx->getFixedScheduleId($smartProcess->stageId);

            $scheduleId = $bx->getScheduleIdByDepartment($departmentIds) ?? $bx->getScheduleIdByUser($userId);
            $scheduleId = $fixedScheduleId ?? $scheduleId;
            $schedule = $bx->getSchedule($scheduleId);
            $exclusions = $bx->getExclusions($scheduleId);
            $holidays = $bx->getHolidays($exclusions);
            $oldDueDate = Carbon::now();

            if ($entityTypeId == 177) {
                if (
                    $smartProcess->stageId == 'DT177_10:NEW'
                    || $smartProcess->stageId == 'DT177_20:NEW'
                    || $smartProcess->stageId == 'DT177_23:NEW'
                    || $smartProcess->stageId == 'DT177_24:NEW'
                ) {
                    $userId = $smartProcess->assignedById;
                    $user = $bx->getUser($userId);
                    $departmentId = !empty($user['UF_DEPARTMENT']) ? $user['UF_DEPARTMENT'][0] : null;
                    $scheduleId = $bx->getScheduleIdByDepartment($departmentIds) ?? $bx->getScheduleIdByUser($userId);
                    $schedule = $bx->getSchedule($scheduleId);
                    $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                    $params['ufCrm7_1740770891'] = $newDueDate;
                    $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                }
            } elseif ($entityTypeId == 136) {
                switch ($smartProcess->stageId) {
                    // Open
                    case 'DT136_14:NEW':
                    case 'DT136_16:NEW':
                    case 'DT136_17:NEW':
                    case 'DT136_18:NEW':

                        // In progress
                    case 'DT136_14:PREPARATION':
                    case 'DT136_16:PREPARATION':
                    case 'DT136_17:PREPARATION':
                    case 'DT136_18:PREPARATION':

                        // Waiting for customer
                    case 'DT136_14:UC_IFNSU8':
                    case 'DT136_16:CLIENT':
                    case 'DT136_17:CLIENT':
                    case 'DT136_18:CLIENT':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;

                    // No answered
                    case 'DT136_14:UC_K0NLHD':
                    case 'DT136_16:UC_PHVTS5':
                    case 'DT136_17:UC_ZVPHVD':
                    case 'DT136_18:UC_4NJ3UO':

                        // Follow Up
                    case 'DT136_14:UC_URLCVA':
                    case 'DT136_16:UC_T5BDVA':
                    case 'DT136_17:UC_MEMKD8':
                    case 'DT136_18:UC_28XZFS':

                    case 'DT136_32:NEW':
                    case 'DT136_33:NEW':
                    case 'DT136_30:NEW':

                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;
                    default:
                        if ($fixedScheduleId) {
                            $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                            $params['ufCrm6_1734527827434'] = $newDueDate;
                            $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        }
                }
            } elseif ($entityTypeId == 1040) {
                switch ($smartProcess->stageId) {
                    case 'DT1040_22:NEW':
                    case 'DT1040_22:PREPARATION':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                        $params['ufCrm9_1746797152'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        break;
                    default:
                        if ($fixedScheduleId) {
                            $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                            $params['ufCrm9_1746797152'] = $newDueDate;
                            $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        }
                }
            }
        }
    }

    public
    function deleted(SmartProcess $smartProcess): void
    {
        SmartProcessActivity::where('element_id', $smartProcess->bitrix_id)
            ->where('entity_type_id', $smartProcess->entityTypeId)
            ->delete();
    }
}
