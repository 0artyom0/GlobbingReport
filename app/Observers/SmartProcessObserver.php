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
            $scheduleId = $bx->getScheduleIdByDepartment($departmentId) ?? $bx->getScheduleIdByUser($userId);
            $scheduleId = $fixedScheduleId ?? $scheduleId;

            $schedule = $bx->getSchedule($scheduleId);
            $exclusions = $bx->getExclusions($schedule);
            $holidays = $bx->getHolidays($exclusions);
            $oldDueDate = Carbon::now();

            if ($entityTypeId == SmartProcess::TYPE_TICKET) {
                switch ($smartProcess->stageId) {
                    case SmartProcess::AM_TROUBLE_OPEN:
                    case SmartProcess::UZ_TROUBLE_OPEN:
                    case SmartProcess::ABLY_TROUBLE_OPEN:

                    case SmartProcess::AM_TROUBLE_IN_PROGRESS:
                    case SmartProcess::KZ_TROUBLE_IN_PROGRESS:
                    case SmartProcess::UZ_TROUBLE_IN_PROGRESS:
                    case SmartProcess::ABLY_TROUBLE_IN_PROGRESS:

                    case SmartProcess::AM_TROUBLE_WAITING_FOR_CUSTOMER:
                    case SmartProcess::KZ_TROUBLE_WAITING_FOR_CUSTOMER:
                    case SmartProcess::UZ_TROUBLE_WAITING_FOR_CUSTOMER:
                    case SmartProcess::ABLY_TROUBLE_WAITING_FOR_CUSTOMER:

                    case 'DT136_9:PREPARATION':
                    case 'DT136_9:UC_CE7CSL':
                    case 'DT136_12:PREPARATION':
                    case 'DT136_12:UC_MWE4ZI':
                    case 'DT136_13:PREPARATION':
                    case 'DT136_13:NEW':
                    case 'DT136_12:NEW':
                    case 'DT136_9:NEW':
                    case 'DT136_13:UC_DHDLV5':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;

                    case SmartProcess::AM_TROUBLE_NO_ANSWER:
                    case SmartProcess::KZ_TROUBLE_NO_ANSWER:
                    case SmartProcess::UZ_TROUBLE_NO_ANSWER:
                    case SmartProcess::ABLY_TROUBLE_NO_ANSWER:

                    case SmartProcess::AM_TROUBLE_FOLLOW_UP:
                    case SmartProcess::KZ_TROUBLE_FOLLOW_UP:
                    case SmartProcess::UZ_TROUBLE_FOLLOW_UP:
                    case SmartProcess::ABLY_TROUBLE_FOLLOW_UP:

                    case 'DT136_32:NEW':
                    case 'DT136_33:NEW':
                    case SmartProcess::BUSINESS_ARMENIA_OPEN:
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;

                    case SmartProcess::KZ_TROUBLE_OPEN:
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 2, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;
                    default:
                        if ($fixedScheduleId) {
                            if (in_array($smartProcess->stageId, ['DT136_16:UC_7ELI19', 'DT136_16:UC_HSLT7R', 'DT136_16:UC_HU332O', 'DT136_16:UC_TLERGE', 'DT136_16:UC_VXODCU', 'DT136_16:UC_1VHFS7', 'DT136_16:UC_0NSPL8', 'DT136_16:UC_KZ8I21'])) {
                                $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                            } else {
                                $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                            }

                            $params['ufCrm6_1734527827434'] = $newDueDate;
                            $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        }
                }
            } elseif ($entityTypeId == SmartProcess::TYPE_CHILD_TICKET) {
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
            if ($entityTypeId == SmartProcess::TYPE_ACTIVITY) {
                if (
                    $smartProcess->stageId == 'DT177_10:NEW'
                    || $smartProcess->stageId == 'DT177_23:NEW'
                    || $smartProcess->stageId == 'DT177_24:NEW'
                ) {
                    $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                    $params['ufCrm7_1740770891'] = $newDueDate;
                    $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                } elseif ($smartProcess->stageId == 'DT177_20:NEW') {
                    if ($userId == '90' || $userId == '364' || $userId == '105') {
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 2, $holidays);
                    } else {
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                    }
                    $params['ufCrm7_1740770891'] = $newDueDate;
                    $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
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

            if ($entityTypeId == SmartProcess::TYPE_ACTIVITY) {
                $scheduleId = $bx->getScheduleIdByDepartment($departmentIds) ?? $bx->getScheduleIdByUser($userId);
                $schedule = $bx->getSchedule($scheduleId);
                if (
                    $smartProcess->stageId == 'DT177_10:NEW'
                    || $smartProcess->stageId == 'DT177_23:NEW'
                    || $smartProcess->stageId == 'DT177_24:NEW'
                ) {
                    $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                    $params['ufCrm7_1740770891'] = $newDueDate;
                    $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                } elseif ($smartProcess->stageId == 'DT177_20:NEW') {
                    if ($userId == '90' || $userId == '364' || $userId == '105') {
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 2, $holidays);
                    } else {
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                    }
                    $params['ufCrm7_1740770891'] = $newDueDate;
                    $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                }
            } elseif ($entityTypeId == SmartProcess::TYPE_TICKET) {
                switch ($smartProcess->stageId) {
                    case SmartProcess::AM_TROUBLE_OPEN:
                    case SmartProcess::UZ_TROUBLE_OPEN:
                    case SmartProcess::ABLY_TROUBLE_OPEN:

                    case SmartProcess::AM_TROUBLE_IN_PROGRESS:
                    case SmartProcess::KZ_TROUBLE_IN_PROGRESS:
                    case SmartProcess::UZ_TROUBLE_IN_PROGRESS:
                    case SmartProcess::ABLY_TROUBLE_IN_PROGRESS:

                    case SmartProcess::AM_TROUBLE_WAITING_FOR_CUSTOMER:
                    case SmartProcess::UZ_TROUBLE_WAITING_FOR_CUSTOMER:
                    case SmartProcess::ABLY_TROUBLE_WAITING_FOR_CUSTOMER:
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;

                    case SmartProcess::AM_TROUBLE_NO_ANSWER:
                    case SmartProcess::KZ_TROUBLE_NO_ANSWER:
                    case SmartProcess::UZ_TROUBLE_NO_ANSWER:
                    case SmartProcess::ABLY_TROUBLE_NO_ANSWER:

                    case SmartProcess::AM_TROUBLE_FOLLOW_UP:
                    case SmartProcess::KZ_TROUBLE_FOLLOW_UP:
                    case SmartProcess::UZ_TROUBLE_FOLLOW_UP:
                    case SmartProcess::ABLY_TROUBLE_FOLLOW_UP:

                    case 'DT136_32:NEW':
                    case 'DT136_33:NEW':
                    case 'DT136_30:NEW':

                    case 'DT136_16:NEW':
                    case 'DT136_16:CLIENT':
                        $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                        $params['ufCrm6_1734527827434'] = $newDueDate;
                        $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);

                        break;
                    default:
                        if ($fixedScheduleId) {
                            if (in_array($smartProcess->stageId, ['DT136_16:UC_7ELI19', 'DT136_16:UC_HSLT7R', 'DT136_16:UC_HU332O', 'DT136_16:UC_TLERGE', 'DT136_16:UC_VXODCU', 'DT136_16:UC_1VHFS7', 'DT136_16:UC_0NSPL8', 'DT136_16:UC_KZ8I21'])) {
                                $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 4, $holidays);
                            } else {
                                $newDueDate = $bx->getNewDueDate($oldDueDate, $schedule, 8, $holidays);
                            }

                            $params['ufCrm6_1734527827434'] = $newDueDate;
                            $bx->updateSmartProcess($smartProcess->bitrix_id, $smartProcess->entityTypeId, $params);
                        }
                }
            } elseif ($entityTypeId == SmartProcess::TYPE_CHILD_TICKET) {
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

    public function deleted(SmartProcess $smartProcess): void
    {
        SmartProcessActivity::where('element_id', $smartProcess->bitrix_id)
            ->where('entity_type_id', $smartProcess->entityTypeId)
            ->delete();
    }
}
