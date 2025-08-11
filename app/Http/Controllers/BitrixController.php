<?php

namespace App\Http\Controllers;

use App\Models\SmartProcess;
use App\Services\BitrixService;
use Illuminate\Http\Request;

class BitrixController extends Controller
{
    public function smartProcess(Request $request): void
    {
        $bx = new BitrixService();

        $data = $request->all();
        $smartProcessId = $data['data']['FIELDS']['ID'];
        $entityTypeId = $data['data']['FIELDS']['ENTITY_TYPE_ID'];
        $data['event'] = 'ONCRMDYNAMICITEMUPDATE';
        $smartProcess = $bx->getSmartProcess($smartProcessId, $entityTypeId);

        if ($smartProcess) {
            $smartProcess['bitrix_id'] = $smartProcess['id'];
            unset($smartProcess['id']);
        }

        switch ($data['event']) {
            case 'ONCRMDYNAMICITEMADD':
                SmartProcess::create(
                    [
                        'bitrix_id' => $smartProcess['bitrix_id'],
                        'entityTypeId' => $entityTypeId,
                        'ufCrm6_1734527827434' => $smartProcess['ufCrm6_1734527827434'] ?? null, //136 DUE DATE
                        'ufCrm7_1740770891' => $smartProcess['ufCrm7_1740770891'] ?? null, // 1740770891
                        'ufCrm6_1741187664356' => $smartProcess['ufCrm6_1741187664356'] ?? null, //136/137 Overdue
                        'updatedBy' => $smartProcess['updatedBy'] ?? null,
                        'updatedTime' => $smartProcess['updatedTime'] ?? null,
                        'stageId' => $smartProcess['stageId'] ?? null,
                        'assignedById' => $smartProcess['assignedById'] ?? null,
                    ]
                );

                break;
            case 'ONCRMDYNAMICITEMUPDATE':
                $smart = SmartProcess::where('bitrix_id', $smartProcess['bitrix_id'])
                    ->where('entityTypeId', $entityTypeId)->first();
                if ($smart) {
                    $smart->update([
                        'ufCrm6_1734527827434' => $smartProcess['ufCrm6_1734527827434'] ?? null, //136 DUE DATE
                        'ufCrm6_1741187664356' => $smartProcess['ufCrm6_1741187664356'] ?? null, //136 DUE DATE
                        'ufCrm7_1740770891' => $smartProcess['ufCrm7_1740770891'] ?? null, // 1740770891 Overdue
                        'updatedBy' => $smartProcess['updatedBy'] ?? null,
                        'updatedTime' => $smartProcess['updatedTime'] ?? null,
                        'stageId' => $smartProcess['stageId'] ?? null,
                        'assignedById' => $smartProcess['assignedById'] ?? null,
                        'fields' => json_encode($smartProcess),
                    ]);
                } else {
                    SmartProcess::create(
                        [
                            'bitrix_id' => $smartProcess['bitrix_id'],
                            'entityTypeId' => $entityTypeId,
                            'ufCrm6_1734527827434' => $smartProcess['ufCrm6_1734527827434'] ?? null, //136 DUE DATE
                            'ufCrm7_1740770891' => $smartProcess['ufCrm7_1740770891'] ?? null, // 1740770891
                            'ufCrm6_1741187664356' => $smartProcess['ufCrm6_1741187664356'] ?? null, //136/137 Overdue
                            'updatedBy' => $smartProcess['updatedBy'] ?? null,
                            'stageId' => $smartProcess['stageId'] ?? null,
                            'updatedTime' => $smartProcess['updatedTime'] ?? null,
                            'assignedById' => $smartProcess['assignedById'] ?? null,
                        ]
                    );
                }

                break;
            case 'ONCRMDYNAMICITEMDELETE':
                $smart = SmartProcess::where('bitrix_id', $smartProcessId)
                    ->where('entityTypeId', $entityTypeId)->first();

                $smart->delete();

                break;
        }
    }

    public function lead(Request $request): void
    {
        $data = $request->all();
        $bx = new BitrixService();
//        $leadId = $data['data']['FIELDS']['ID'];
//        $leadId = '63587';
        $leadId = '62082';
        $lead = $bx->getLead($leadId);
//        $event = $data['event'];
        $event = 'ONCRMLEADADD';

        if ($event == 'ONCRMLEADADD') {
            $needSourceIds = [
                'UC_146HB0',
                'UC_TBRNZZ',
                'UC_SESMBQ',
                'UC_ZCMAQL',
                'UC_B0GW1L',
                'UC_5KV3GR',
                'UC_3LMZ5Y'
            ];

            $needSourceIdsForSocialMessages = [
                '14|5AAC45CD-0933',
                '17|87C33B0B-F772',
                '5|C2D_1GT_CONNECTOR',
                '9202AAB6',
                '6|C2D_1GT_CONNECTOR',
                'UC_JOCFZN',
                '24|725AC8CD-164F',
                '3|FACEBOOK',
                '4|FBINSTAGRAMDIRECT',
                '23|1A0501FB-8E08',
                'UC_HRUQN5',
                'UC_U210J7'
            ];

            if ($lead) {
                if (in_array($lead['SOURCE_ID'], $needSourceIds)) {
                    $email = !empty($lead['EMAIL']) ? $lead['EMAIL'][0]['VALUE'] : null;

                    if ($email) {
                        $contactId = $bx->getContactByEmail($email);
                        if (!$contactId) {
                            $contactId = $bx->createContact($email);
                        }

                        $companyId = $bx->getCompanyByEmail($email);

                        if ($contactId) {
                            $bx->assignElementToLead($lead, $contactId, 'CONTACT_ID');
                        }

                        if ($companyId) {
                            $bx->assignElementToLead($lead, $companyId, 'COMPANY_ID');
                        }

                        $smartProcessInfo = $bx->getSmartProcessInfo($lead['SOURCE_ID']);
                        $entityTypeId = $smartProcessInfo['entityTypeId'];
                        $categoryId = $smartProcessInfo['categoryId'];
                        $smartProcessData = [
                            'sourceId' => 'EMAIL',
                            'parentId1' => $leadId
                        ];

                        if ($contactId) {
                            $smartProcessData['contactId'] = $contactId;
                        }

                        if ($companyId) {
                            $smartProcessData['companyId'] = $companyId;
                        }

                        $bx->createSmartProcess($entityTypeId, $smartProcessData, $categoryId);
                        $bx->changeLeadStage($lead, 'JUNK');

                        $url = "https://projects.globbing.com/machApps/attachEmailToSmart.php?token=aDRu4mai9FamAJ4PKuLArI29RxqbNd&lead_id=" . $leadId;
                        file_get_contents($url);
                    }
                } else if (in_array($lead['SOURCE_ID'], $needSourceIdsForSocialMessages)) {
                    $phone = !empty($lead['PHONE']) ? $lead['PHONE'][0]['VALUE'] : null;

                    if ($phone) {
                        $contactId = $bx->getContactByPhone($phone);
                        if (!$contactId) {
                            $contactId = $bx->createContactWithPhone($phone);
                        }

                        $companyId = $bx->getCompanyByPhone($phone);

                        if ($contactId) {
                            $bx->assignElementToLead($lead, $contactId, 'CONTACT_ID');
                        }

                        if ($companyId) {
                            $bx->assignElementToLead($lead, $companyId, 'COMPANY_ID');
                        }


                        if ($contactId) {
                            $smartProcessData['contactId'] = $contactId;
                        }

                        if ($companyId) {
                            $smartProcessData['companyId'] = $companyId;
                        }
                    }


                    $smartProcessInfo = $bx->getSmartProcessInfoForSocialMessages($lead['SOURCE_ID']);
                    $entityTypeId = $smartProcessInfo['entityTypeId'];
                    $categoryId = $smartProcessInfo['categoryId'];
                    $smartProcessData = [
                        'sourceId' => $lead['SOURCE_ID'],
                        'parentId1' => $leadId
                    ];

                    $bx->createSmartProcess($entityTypeId, $smartProcessData, $categoryId);
                    $bx->changeLeadStage($lead, 'JUNK');
                }
            }
        }
    }

    public function smartStage(Request $request): void
    {
        $data = $request->all();
        $bx = new BitrixService();
        $smartId = explode('_', $data['document_id'][2])[2];
        $entityTypeId = explode('_', $data['document_id'][2])[1];

        $smartProcessBizProcesses = $bx->getSmartProcessBizProcesses($entityTypeId, $smartId);

        $bx->killBizProcesses($smartProcessBizProcesses);

        $categoriesToExcludeForCreatingActivity = [
            SmartProcess::CAT_COMMUNICATION_AM,
            SmartProcess::CAT_COMMUNICATION_KZ,
            SmartProcess::CAT_COMMUNICATION_UZ,
            SmartProcess::CAT_COMMUNICATION_XXX,
        ];

        if ($entityTypeId == SmartProcess::TYPE_TICKET) {
            $bxTicket = $bx->getTicket($smartId);

            if ($bxTicket) {
                if (!in_array($bxTicket['categoryId'], $categoriesToExcludeForCreatingActivity)) {
                    $createdActivity = $bx->createActivityForTicket($bxTicket, SmartProcess::CAT_OVERDUE);

                    if ($createdActivity['result'] ?? null) {
                        $createdActivityId = $createdActivity['result']['item']['id'];

                        $updateData = [
                            'parentId177' => $createdActivityId,
                        ];

                        $bx->updateSmartProcess($smartId, SmartProcess::TYPE_TICKET, $updateData);

                    }
                }
            }
        } elseif ($entityTypeId == SmartProcess::TYPE_ACTIVITY) {
            $bxActivity = $bx->getActivity($smartId);
            if ($bxActivity) {
                if (!in_array($bxActivity['categoryId'], $categoriesToExcludeForCreatingActivity)) {
                    $createdActivity = $bx->createActivityForActivity($bxActivity, SmartProcess::CAT_OVERDUE);

                    if ($createdActivity['result'] ?? null) {
                        $createdActivityId = $createdActivity['result']['item']['id'];

                        $updateData = [
                            'parentId177' => $createdActivityId,
                        ];

                        $bx->updateSmartProcess($smartId, SmartProcess::TYPE_ACTIVITY, $updateData);
                    }
                }
            }
        }

        $bx->incrementOverdueCount($smartId, $entityTypeId);
    }
}
