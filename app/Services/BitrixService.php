<?php

namespace App\Services;

use App\Models\SmartProcess;
use Carbon\Carbon;
use CRest;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

require_once(base_path('app/Bitrix/in_hook/crest.php'));

class BitrixService
{

    public function getItems($order, $filters, $columns, $typeId): array
    {
        $params = [];
        $params['entityTypeId'] = $typeId;
        $params['order'] = $order;
        $params['filter'] = $filters;
        $params['select'] = $columns;
        $batchParam = [];

        $total = CRest::call('crm.item.list', $params)['total'];

        for ($i = 0; $i < $total; $i += 50) {
            $params['start'] = $i;
            $batchParam[$i] = [
                'method' => 'crm.item.list',
                'params' => $params,
            ];
        }

        $result = [];
        $batchCount = ceil(count($batchParam) / 50);

        for ($i = 0; $i < $batchCount; $i++) {
            $batchParams = array_slice($batchParam, $i * 50, 50, true);

            $batchResult = Crest::callBatch($batchParams)['result']['result'] ?? [];

            foreach ($batchResult as $value) {
                $result = [...$result, ...$value['items']];
            }
        }

        return $result;
    }

    public function getDeals($order, $filters, $columns): array
    {
        $params = [];
        $params['order'] = $order;
        $params['filter'] = $filters;
        $params['select'] = $columns;

        $batchParam = [];

        $total = CRest::call('crm.deal.list', $params)['total'];

        for ($i = 0; $i < $total; $i += 50) {
            $params['start'] = $i;
            $batchParam[$i] = [
                'method' => 'crm.deal.list',
                'params' => $params,
            ];
        }

        return $this->getTotalData($batchParam);
    }

    public function getLeads($order, $filters, $columns)
    {
        $params = [];
        $params['order'] = $order;
        $params['filter'] = $filters;
        $params['select'] = $columns;

        $batchParam = [];

        $total = CRest::call('crm.lead.list', $params)['total'];

        for ($i = 0; $i < $total; $i += 50) {
            $params['start'] = $i;
            $batchParam[$i] = [
                'method' => 'crm.lead.list',
                'params' => $params,
            ];
        }

        return $this->getTotalData($batchParam);
    }

    /**
     * @param array $batchParam
     * @return array
     */
    public function getTotalData(array $batchParam, $isSmart = false): array
    {
        $result = [];
        $batchCount = ceil(count($batchParam) / 50);

        for ($i = 0; $i < $batchCount; $i++) {
            $batchParams = array_slice($batchParam, $i * 50, 50, true);

            $batchResult = Crest::callBatch($batchParams)['result']['result'] ?? [];

            foreach ($batchResult as $value) {
                if ($isSmart) {
                    $value = $value['items'];
                }
                $result = [...$result, ...$value];
            }
        }

        return $result;
    }

    public function getTotal($method, $order, $filters, $columns, $typeId = null)
    {
        $params = [];
        $params['order'] = $order;
        $params['filter'] = $filters;
        $params['select'] = $columns;

        if ($method == 'crm.item.list') {
            $params = [
                'entityTypeId' => $typeId
            ];
        }

        return CRest::call($method, $params)['total'];
    }

    public function getSmartProcess($elementId, $entityTypeId)
    {
        $result = CRest::call('crm.item.get', [
            'id' => $elementId,
            'entityTypeId' => $entityTypeId,
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['item'];
        }

        return null;
    }

    public function getTicket($elementId)
    {
        $result = CRest::call('crm.item.get', [
            'id' => $elementId,
            'entityTypeId' => SmartProcess::TYPE_TICKET,
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['item'];
        }

        return null;
    }

    public function getActivity($elementId)
    {
        $result = CRest::call('crm.item.get', [
            'id' => $elementId,
            'entityTypeId' => 177,
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['item'];
        }

        return null;
    }

    public function getSchedule($id)
    {
        if (!$id) {
            return $this->getDefaultSchedule();
        }

        $result = CRest::call('timeman.schedule.get', [
                'id' => $id
            ]
        );

        if ($result['result'] ?? null) {
            return $result['result']['SHIFTS'][0];
        }

        return $this->getDefaultSchedule();
    }

    public function getUser($id)
    {
        $result = CRest::call('user.get', [
                'id' => $id
            ]
        );

        if ($result['result'] ?? null) {
            return $result['result'][0];
        }

        return null;
    }

    public function updateSmartProcess($smartProcessId, $entityTypeId, $params)
    {
        $smartProcess = $this->getSmartProcess($smartProcessId, $entityTypeId);

        $toUpdate = false;

        foreach ($params as $key => $value) {
            if ($smartProcess[$key] != $value) {
                $toUpdate = true;
                break;
            }
        }

        if ($toUpdate) {
            return CRest::call('crm.item.update', [
                    'entityTypeId' => $entityTypeId,
                    'id' => $smartProcessId,
                    'fields' => $params
                ]
            );
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getNewDueDate($oldDate, $schedule, $hoursToAdd, array $holidays = [])
    {
        $minutesToAdd = $hoursToAdd * 60;

        $workStartSeconds = $schedule['WORK_TIME_START'] ?? 36000;
        $workEndSeconds = $schedule['WORK_TIME_END'] ?? 68400;

        $workStartHour = floor($workStartSeconds / 3600);
        $workStartMin = floor(($workStartSeconds % 3600) / 60);

        $workEndHour = floor($workEndSeconds / 3600);
        $workEndMin = floor(($workEndSeconds % 3600) / 60);
        $workingDays = str_split($schedule['WORK_DAYS'] ?? '12345');

        $holidays = array_map(fn($h) => Carbon::parse($h)->toDateString(), $holidays);
        $date = Carbon::parse($oldDate)->timezone('Asia/Yerevan');

        while ($minutesToAdd > 0) {
            if (
                !in_array($date->format('N'), $workingDays) ||
                in_array($date->toDateString(), $holidays)
            ) {
                $date->addDay()->setTime($workStartHour, $workStartMin);
                continue;
            }

            $workDayStart = $date->copy()->setTime($workStartHour, $workStartMin);
            $workDayEnd = $date->copy()->setTime($workEndHour, $workEndMin);

            if ($date->lt($workDayStart)) {
                $date = $workDayStart->copy();
            } elseif ($date->gte($workDayEnd)) {
                $date->addDay()->setTime($workStartHour, $workStartMin);
                continue;
            }

            $availableMinutesToday = $date->diffInMinutes($workDayEnd);

            if ($minutesToAdd <= $availableMinutesToday) {
                $date->addMinutes($minutesToAdd);
                break;
            } else {
                $minutesToAdd -= $availableMinutesToday;
                $date->addDay()->setTime($workStartHour, $workStartMin);
            }
        }

        $finalTime = $date->format('H:i:s');
        while (
            !in_array($date->format('N'), $workingDays) ||
            in_array($date->toDateString(), $holidays)
        ) {
            $date->addDay()->setTimeFromTimeString($finalTime);
        }

        return $date->toIso8601String();
    }

    public function getScheduleIdByDepartment($departmentIds)
    {
        $client = new Client();
        foreach ($departmentIds as $departmentId) {
            $response = $client->get('https://projects.globbing.com/machApps/getScheduleId.php', [
                'query' => [
                    'token' => 'j46cPxWH1O37JXlTYFaPbjnZbsIeth',
                    'department_id' => $departmentId
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            if ($data['SCHEDULE_ID'] ?? null) {
                foreach ($data['SCHEDULE_ID'] as $scheduleId) {
                    $schedule = $this->getSchedule($scheduleId);
                    if ($schedule['ID'] != 9999) {
                        return $scheduleId;
                    }
                }
            }
        }

        return null;
    }

    public function getScheduleIdByUser($userId)
    {
        $client = new Client();

        $response = $client->get('https://projects.globbing.com/machApps/getScheduleId.php', [
            'query' => [
                'token' => 'j46cPxWH1O37JXlTYFaPbjnZbsIeth',
                'user_id' => $userId
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if ($data['SCHEDULE_ID'] ?? null) {
            foreach ($data['SCHEDULE_ID'] as $scheduleId) {
                $schedule = $this->getSchedule($scheduleId);
                if ($schedule['ID'] != 9999) {
                    return $scheduleId;
                }
            }
        }

        return null;
    }

    public function getDefaultSchedule(): array
    {
        return [
            'ID' => 9999,
            'NAME' => "",
            'BREAK_DURATION' => 3600,
            'WORK_TIME_START' => 36000, // 10:00
            'WORK_TIME_END' => 68400,   // 19:00
            'WORK_DAYS' => "12345",
            'SCHEDULE_ID' => 6,
            'DELETED' => false
        ];
    }

    public function createSmartProcess($entityTypeId, $data, $categoryId = null)
    {
        if (isset($categoryId)) {
            $data['categoryId'] = $categoryId;
        }

        return CRest::call(
            'crm.item.add',
            [
                'entityTypeId' => $entityTypeId,
                'fields' => $data
            ]
        );
    }

    public function getDefaultDataForSmartProcess($data): array
    {
        $params['ufCrm6_1734682286829'] = $data['payload']['customFields']['UZ communication reason'] ?? null;
        $params['ufCrm6_1734685351754'] = $data['payload']['customFields']['UZ Communication Sub reason'] ?? null;
        $params['ufCrm6_1737458814956'] = $data['payload']['customFields']['Tracking number'] ?? null;
        $params['ufCrm6_1737536900550'] = $data['payload']['customFields']['Resolution reason'] ?? null;
        $params['ufCrm6_1737458798396'] = $data['payload']['customFields']['Parcel ID'] ?? null;
        $params['ufCrm6_1734680885162'] = $data['payload']['customFields']['KZ Communication Sub reason'] ?? null;
        $params['ufCrm6_1734679393381'] = $data['payload']['customFields']['KZ Communication Reason'] ?? null;
        $params['ufCrm6_1737458761332'] = $data['payload']['customFields']['Amount'] ?? null;
        $params['ufCrm6_1734527515478'] = $data['payload']['customFields']['AM Communication Sub Reason'] ?? null;
        $params['ufCrm6_1734527505976'] = $data['payload']['customFields']['AM Communication Reason'] ?? null;
        $params['ufCrm6_1734527447743'] = $data['payload']['customFields']['Ticket Type'] ?? null;
        $params['ufCrm6_1737458611036'] = $data['payload']['webUrl'] ?? null;
        $params['ufCrm6_1734527827434'] = $data['payload']['dueDate'] ?? null;
        $params['ufCrm6_1734527426292'] = $data['payload']['classification'] ?? null;
        $params['ufCrm6_1734527462516'] = $data['payload']['description'] ? strip_tags($data['payload']['description']) : null;
        $params['ufCrm6_1745236160015'] = $data['payload']['id'] ?? null;

        return $params;
    }

    public function getUserByEmail($email)
    {
        $result = CRest::call('user.get', [
            'filter' => [
                'EMAIL' => $email
            ]
        ]);

        if ($result['result'] ?? null) {
            return $result['result'][0];
        }

        return null;
    }

    public function getCompanyByEmail($email)
    {
        $result = CRest::call('crm.duplicate.findbycomm', [
            'entity_type' => "COMPANY",
            'type' => 'EMAIL',
            'values' => [$email],
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['COMPANY'][0];
        }

        return null;
    }

    public function getCategoryId($data): ?int
    {
        $categoryId = null;

        if ($data['payload']['departmentId'] == '823602000000379099') {
            if ($data['classification'] == 'Problem') {
                $categoryId = 16;
            } elseif ($data['payload']['classification'] == 'Conversation') {
                $categoryId = 12;
            }
        } elseif ($data['payload']['departmentId'] == '823602000000411099') {
            if ($data['payload']['classification'] == 'Problem') {
                $categoryId = 17;
            } elseif ($data['payload']['classification'] == 'Conversation') {
                $categoryId = 13;
            }
        } elseif ($data['payload']['departmentId'] == '823602000469553551') {
            $categoryId = 18;
        } elseif ($data['payload']['departmentId'] == '823602000000006907') {
            if ($data['payload']['classification'] == 'Problem') {
                $categoryId = 14;
            } elseif ($data['payload']['classification'] == 'Conversation') {
                $categoryId = 9;
            }
        } else {
            if ($data['payload']['classification'] == 'Problem') {
                $categoryId = 14;
            } elseif ($data['payload']['classification'] == 'Conversation') {
                $categoryId = 9;
            }
        }

        return $categoryId;
    }

    public function getSmartProcessByZohoId($entityTypeId, $zohoId)
    {
        $result = CRest::call('crm.item.list', [
            'entityTypeId' => $entityTypeId,
            'filter' => [
                'ufCrm6_1745236160015' => $zohoId
            ]
        ]);

        if ($result['result'] ?? null) {
            if (!empty($result['result']['items'])) {
                return $result['result']['items'][0];
            }
        }

        Log::info(['Smart Process Not Found - Zoho id: ' => $zohoId]);

        return null;
    }

    public function addComment($smartProcessId, $entityTypeId, $comment, $authorId): void
    {
        $entityType = "DYNAMIC_$entityTypeId";

        CRest::call(
            'crm.timeline.comment.add',
            [
                'fields' => [
                    'ENTITY_ID' => $smartProcessId,
                    'ENTITY_TYPE' => $entityType,
                    'COMMENT' => $comment,
                    'AUTHOR_ID' => $authorId,
                ]
            ]
        );
    }

    public function getFixedScheduleId($stageId): ?int
    {
        return match ($stageId) {
            'DT136_14:UC_3NPL4T', 'DT136_18:UC_JLB9V7', 'DT136_17:UC_70GLXM', 'DT136_16:UC_7ELI19', 'DT136_30:UC_VE1QUU' => 26,
            'DT136_14:UC_SX13DE', 'DT136_18:UC_HQ4E6T', 'DT136_30:UC_4PVMZE' => 27,
            'DT136_14:UC_ZWRR8H', 'DT136_18:UC_WV70ON', ' DT136_30:UC_CD7ZDY' => 28,
            'DT136_14:UC_6MZFJ3', 'DT136_18:UC_X61381' => 29,
            'DT136_14:UC_2C7YI5', 'DT136_18:UC_L63WNF' => 30,
            'DT136_14:UC_IKU9MO', 'DT136_18:UC_XFO9EY' => 31,
            'DT136_14:UC_A1XBE2', 'DT136_18:UC_UYYT7N', 'DT136_17:UC_SR1D9T', 'DT136_16:UC_KZ8I21' => 32,
            'DT136_14:UC_CJZHB5', 'DT136_18:UC_W2JE9T' => 33,
            'DT136_14:UC_D0U8BV', 'DT136_18:UC_S5B12P', 'DT136_17:UC_6LGE1M', 'DT136_16:UC_Z6WWOX', 'DT1040_22:CLIENT', 'DT136_30:UC_H3FZJ3' => 34,
            'DT136_17:UC_FY10JH', 'DT136_16:UC_HU332O' => 35,
            'DT136_17:UC_4UWF1C', 'DT136_16:UC_HSLT7R' => 36,
            'DT136_17:UC_Y72Z2X', 'DT136_16:UC_TLERGE' => 37,
            'DT136_17:UC_ZU4OWS', 'DT136_16:UC_VXODCU' => 38,
            'DT136_17:UC_96DKSB', 'DT136_16:UC_1VHFS7' => 39,
            'DT136_17:UC_RV7AL8', 'DT136_16:UC_0NSPL8' => 40,
            default => null,
        };
    }

    public function getHolidays($exclusions): array
    {
        $currentYear = Carbon::today()->year;
        $holidays = [];
        if ($exclusions[$currentYear] ?? null) {
            foreach ($exclusions[$currentYear] as $month => $days) {
                foreach (array_keys($days) as $day) {
                    $holidays[] = Carbon::parse("$currentYear-$month-$day");
                }
            }
        }
        return $holidays;
    }

    public function getExclusions($id)
    {
        if (!$id) {
            return [];
        }

        $result = CRest::call('timeman.schedule.get', [
                'id' => $id
            ]
        );

        if ($result['result'] ?? null) {
            return !empty($result['result']['CALENDAR']['EXCLUSIONS'])
                ? $result['result']['CALENDAR']['EXCLUSIONS']
                : [];
        }

        return [];
    }

    public function getLead($id)
    {
        $result = CRest::call('crm.lead.get', [
            'ID' => $id
        ]);

        if ($result['result'] ?? null) {
            return $result['result'];
        }

        return null;
    }

    public function getContactByEmail($email)
    {
        $result = CRest::call('crm.duplicate.findbycomm', [
            'entity_type' => "CONTACT",
            'type' => 'EMAIL',
            'values' => [$email],
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['CONTACT'][0];
        }

        return null;
    }

    public function createContact($email)
    {
        return CRest::call(
            'crm.contact.add',
            [
                'FIELDS' => [
                    'EMAIL' => [
                        [
                            'VALUE' => $email,
                            'VALUE_TYPE' => 'MAILING',
                        ],
                    ],
                ]
            ]
        )['result'];
    }

    public function createContactWithPhone($phone)
    {
        return CRest::call(
            'crm.contact.add',
            [
                'FIELDS' => [
                    'PHONE' => [
                        [
                            'VALUE' => $phone,
                            'VALUE_TYPE' => 'MAILING',
                        ],
                    ],
                ]
            ]
        )['result'];
    }

    public function assignElementToLead($lead, $elementId, $key): void
    {
        if ($lead[$key] != $elementId) {
            Crest::call('crm.lead.update', [
                'id' => $lead['ID'],
                'fields' => [
                    $key => $elementId,
                ]
            ]);
        }
    }

    public function getSmartProcessInfo($sourceId): array
    {
        $entityTypeId = null;
        $categoryId = null;

        switch ($sourceId) {
            case 'UC_146HB0':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_AM;
                break;

            case 'UC_TBRNZZ':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_KZ;

                break;
            case 'UC_SESMBQ':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_UZ;

                break;
            case 'UC_ZCMAQL':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = 32;
                break;
            case 'UC_B0GW1L':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_BUSINESS_ARMENIA;
                break;
            case 'UC_5KV3GR':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = 33;
                break;
            case 'UC_3LMZ5Y':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = 34;
                break;
        }

        return [
            'entityTypeId' => $entityTypeId,
            'categoryId' => $categoryId,
        ];
    }

    public function getSmartProcessInfoForSocialMessages($sourceId): array
    {
        $entityTypeId = null;
        $categoryId = null;

        switch ($sourceId) {
            case '6|C2D_1GT_CONNECTOR':
            case 'UC_JOCFZN':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_AM;
                break;

            case '14|5AAC45CD-0933':
            case '17|87C33B0B-F772':
            case '5|C2D_1GT_CONNECTOR':
            case '9202AAB6':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_KZ;

                break;
            case '24|725AC8CD-164F':
            case '3|FACEBOOK':
            case '4|FBINSTAGRAMDIRECT':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_UZ;

                break;
            case '23|1A0501FB-8E08':
            case 'UC_HRUQN5':
            case 'UC_U210J7':
                $entityTypeId = SmartProcess::TYPE_TICKET;
                $categoryId = SmartProcess::CAT_COMMUNICATION_XXX;
                break;
        }

        return [
            'entityTypeId' => $entityTypeId,
            'categoryId' => $categoryId,
        ];
    }

    public function changeLeadStage($lead, $stage)
    {
        if ($lead['STATUS_ID'] != $stage) {
            return Crest::call('crm.lead.update', [
                'id' => $lead['ID'],
                'fields' => [
                    'STATUS_ID' => $stage,
                ]
            ]);
        }
    }

    public function getTicketsForUndone(): array
    {
        $startTime = Carbon::yesterday()->startOfDay()->addMinute()->toDateTimeString();
        $endTime = Carbon::yesterday()->setTime(15, 59)->toDateTimeString();

        $filters = [
            '!=ufCrm6_1734527827434' => $startTime,
            '<=ufCrm6_1734527827434' => $endTime,
            '=categoryId' => [
                SmartProcess::CAT_AM_TROUBLE,
                SmartProcess::CAT_KZ_TROUBLE,
                SmartProcess::CAT_UZ_TROUBLE,
                SmartProcess::CAT_ABLY_TROUBLE,
                SmartProcess::CAT_XXX_TROUBLE,
                SmartProcess::CAT_BUSINESS_ARMENIA
            ],
            '=stageId' => [
                SmartProcess::AM_TROUBLE_OPEN,
                SmartProcess::AM_TROUBLE_IN_PROGRESS,
                SmartProcess::AM_TROUBLE_WAITING_FOR_CUSTOMER,
                SmartProcess::AM_TROUBLE_NO_ANSWER,
                SmartProcess::AM_TROUBLE_FOLLOW_UP,

                SmartProcess::KZ_TROUBLE_OPEN,
                SmartProcess::KZ_TROUBLE_IN_PROGRESS,
                SmartProcess::KZ_TROUBLE_WAITING_FOR_CUSTOMER,
                SmartProcess::KZ_TROUBLE_NO_ANSWER,
                SmartProcess::KZ_TROUBLE_FOLLOW_UP,

                SmartProcess::UZ_TROUBLE_OPEN,
                SmartProcess::UZ_TROUBLE_IN_PROGRESS,
                SmartProcess::UZ_TROUBLE_WAITING_FOR_CUSTOMER,
                SmartProcess::UZ_TROUBLE_NO_ANSWER,
                SmartProcess::UZ_TROUBLE_FOLLOW_UP,

                SmartProcess::ABLY_TROUBLE_OPEN,
                SmartProcess::ABLY_TROUBLE_IN_PROGRESS,
                SmartProcess::ABLY_TROUBLE_WAITING_FOR_CUSTOMER,
                SmartProcess::ABLY_TROUBLE_NO_ANSWER,
                SmartProcess::ABLY_TROUBLE_FOLLOW_UP,

                SmartProcess::BUSINESS_ARMENIA_OPEN,
                SmartProcess::BUSINESS_ARMENIA_IN_PROGRESS,
                SmartProcess::BUSINESS_ARMENIA_WAITING_FOR_CUSTOMER,

                SmartProcess::XXX_OPEN
            ]
        ];

        $select = [
            'id',
            'ufCrm6_1734527827434',
            'stageId',
            'ufCrm6_1753874960515',
            'assignedById',
        ];

        $params = [];
        $params['filter'] = $filters;
        $params['select'] = $select;
        $params['entityTypeId'] = 136;

        $batchParam = [];

        $total = CRest::call('crm.item.list', $params)['total'];

        for ($i = 0; $i < $total; $i += 50) {
            $params['start'] = $i;
            $batchParam[$i] = [
                'method' => 'crm.item.list',
                'params' => $params,
            ];
        }

        return $this->getTotalData($batchParam, true);
    }

    public function getActivitiesForUndone(): array
    {
        $startTime = Carbon::yesterday()->startOfDay()->addMinute()->toDateTimeString();
        $endTime = Carbon::yesterday()->setTime(15, 59)->toDateTimeString();

        $filters = [
            '>ufCrm7_1740770891' => $startTime,
            '<=ufCrm7_1740770891' => $endTime,
            '!=stageId' => ['DT177_27:SUCCESS', 'DT177_27:FAIL']
        ];

        $select = [
            'id',
            'ufCrm7_1740770891',
            'ufCrm7_1753881696167',
            'assignedById',
        ];

        $params = [];
        $params['filter'] = $filters;
        $params['select'] = $select;
        $params['entityTypeId'] = 177;

        $batchParam = [];

        $total = CRest::call('crm.item.list', $params)['total'];

        for ($i = 0; $i < $total; $i += 50) {
            $params['start'] = $i;
            $batchParam[$i] = [
                'method' => 'crm.item.list',
                'params' => $params,
            ];
        }

        return $this->getTotalData($batchParam, true);
    }

    public function createActivityForTicket($ticket, $catId)
    {
        return CRest::call(
            'crm.item.add',
            [
                'entityTypeId' => 177,
                'fields' => [
                    'categoryId' => $catId,
                    'stageId' => 'DT177_26:SUCCESS',
                    'assignedById' => $ticket['assignedById'],
                    'ufCrm7_1739528027403' => $ticket['id'],
                    'parentId136' => $ticket['id'],
                ]
            ]
        );
    }

    public function createActivityForActivity($ticket, $catId)
    {
        return CRest::call(
            'crm.item.add',
            [
                'entityTypeId' => SmartProcess::TYPE_ACTIVITY,
                'fields' => [
                    'categoryId' => $catId,
                    'stageId' => 'DT177_26:SUCCESS',
                    'assignedById' => $ticket['assignedById'],
                    'ufCrm7_1744976546599' => $ticket['Id'],
                ]
            ]
        );
    }

    public function incrementOverdueCount($smartProcessId, $entityTypeId): void
    {
        $smartProcess = $this->getSmartProcess($smartProcessId, $entityTypeId);

        $currentCount = $smartProcess['ufCrm6_1751613519033'] ?? 0;
        $count = $currentCount + 1;

        $this->updateSmartProcess($smartProcessId, $entityTypeId, ['ufCrm6_1751613519033' => $count]);
    }

    public function getSmartProcessBizProcesses($entityTypeId, $smartProcessId): array
    {
        $documentId = "DYNAMIC_{$entityTypeId}_$smartProcessId";

        $result = CRest::call('bizproc.workflow.instances', [
                'filter' => [
                    'DOCUMENT_ID' => $documentId,
                ],
            ]
        );

        if (!empty($result['result'])) {
            return $result['result'];
        }

        return [];
    }

    public function killBizProcesses($bizProcesses)
    {
        foreach ($bizProcesses as $bizProcess) {
            return CRest::call(
                'bizproc.workflow.terminate',
                [
                    'ID' => $bizProcess['ID'],
                    'STATUS' => 'Terminated by rest app.'
                ]
            );
        }
    }

    public function getContactByPhone($phone)
    {
        $result = CRest::call('crm.duplicate.findbycomm', [
            'entity_type' => "CONTACT",
            'type' => 'PHONE',
            'values' => [$phone],
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['CONTACT'][0];
        }

        return null;
    }

    public function getCompanyByPhone($phone)
    {
        $result = CRest::call('crm.duplicate.findbycomm', [
            'entity_type' => "COMPANY",
            'type' => 'PHONE',
            'values' => [$phone],
        ]);

        if ($result['result'] ?? null) {
            return $result['result']['COMPANY'][0];
        }

        return null;
    }
}
