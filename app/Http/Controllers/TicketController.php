<?php

namespace App\Http\Controllers;

use App\Services\BitrixService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): void
    {
        $data = $request->all();
        $data = $data[0];

        if (isset($data['eventType'])) {
            $bx = new BitrixService();

            switch ($data['eventType']) {
                case 'Ticket_Add':
                    $entityTypeId = 136;
                    $categoryId = $bx->getCategoryId($data);

                    $userEmail = !empty($data['payload']['assignee']['email'])
                        ? $data['payload']['assignee']['email']
                        : null;

                    $assignedUser = $userEmail ? $bx->getUserByEmail($userEmail) : null;
                    $responsibleId = $assignedUser ? $assignedUser['ID'] : '3';

                    $companyId = $bx->getCompanyByEmail($userEmail);

                    $params = $bx->getDefaultDataForSmartProcess($data);
                    $params['categoryId'] = $categoryId;
                    $params['assignedById'] = $responsibleId;

                    if ($companyId) {
                        $params['companyId'] = $companyId;
                    }

                    $params['SOURCE_ID'] = 'UC_AD7SAO';

                    $bx->createSmartProcess($entityTypeId, $params);

                    break;

                case 'Ticket_Update':
                    $entityTypeId = 136;
                    $categoryId = $bx->getCategoryId($data);

                    $userEmail = $data['payload']['assignee']['email'];
                    $assignedUser = $bx->getUserByEmail($userEmail);
                    $responsibleId = $assignedUser ? $assignedUser['ID'] : 3;
                    $companyId = $bx->getCompanyByEmail($userEmail);

                    $params = $bx->getDefaultDataForSmartProcess($data);
                    $params['categoryId'] = $categoryId;
                    $params['assignedById'] = $responsibleId;

                    if ($companyId) {
                        $params['companyId'] = $companyId;
                    }

                    $zohoId = $data['prevState']['id'];
                    $smartProcess = $bx->getSmartProcessByZohoId($entityTypeId, $zohoId);

                    if ($smartProcess) {
                        $smartProcessId = $smartProcess['id'];
                        $bx->updateSmartProcess($smartProcessId, $entityTypeId, $params);
                    }

                    break;

                case 'Task_Add':
                    $entityTypeId = 1040;
                    $parentEntityType = 136;

                    $responsibleId = $data['payload']['ownerId'] ?? 3;
                    $parentZohoId = $data['payload']['ticketId'];

                    $smartProcess = $bx->getSmartProcessByZohoId($parentEntityType, $parentZohoId);

                    if ($smartProcess) {
                        $companyId = $smartProcess['companyId'];
                        $smartProcessId = $smartProcess['id'];

                        $taskParams = [];

                        if ($companyId) {
                            $taskParams['companyId'] = $companyId;
                        }

                        $taskParams['parent136'] = $smartProcessId;
                        $taskParams['assignedById'] = $responsibleId;

                        $params['SOURCE_ID'] = 'UC_AD7SAO';
                        $bx->createSmartProcess($entityTypeId, $taskParams);

                        if ($data['payload']['status'] == 'Completed') {
                            $ticketParams = [
                                'stageId' => 'DT1040_22:SUCCESS'
                            ];

                            $bx->updateSmartProcess($smartProcessId, $parentEntityType, $ticketParams);
                        }
                    }

                    break;

                case 'Task_Update':
                    $entityTypeId = 1040;
                    $parentEntityType = 136;

                    $responsibleId = $data['payload']['ownerId'] ?? 3; //Khachatur
                    $parentZohoId = $data['payload']['ticketId'];
                    $taskZohoId = $data['payload']['id'];

                    $smartProcess = $bx->getSmartProcessByZohoId($parentEntityType, $parentZohoId);

                    if ($smartProcess) {
                        $task = $bx->getSmartProcessByZohoId($entityTypeId, $taskZohoId);

                        if ($task) {
                            $taskParams = [];

                            $companyId = $smartProcess['companyId'];

                            if ($companyId) {
                                $taskParams['companyId'] = $companyId;
                            }

                            $taskParams['assignedById'] = $responsibleId;
                            $bx->updateSmartProcess($task['id'], $entityTypeId, $taskParams);
                        }

                        if ($data['payload']['status'] == 'Completed') {
                            $ticketParams = [
                                'stageId' => 'DT1040_22:SUCCESS'
                            ];

                            $bx->updateSmartProcess($smartProcess['id'], $parentEntityType, $ticketParams);
                        }
                    }

                    break;

                case 'Ticket_Comment_Add':
                    $entityTypeId = 136;
                    $comment = $data['payload']['content'];
                    $commenterEmail = $data['payload']['commenter']['email'];
                    $user = $bx->getUserByEmail($commenterEmail);

                    $smartProcess = $bx->getSmartProcessByZohoId($entityTypeId, $data['payload']['ticketId']);

                    if ($user && $smartProcess) {
                        $commenterId = $user['ID'];
                        $cleanedComment = strip_tags($comment);

                        $bx->addComment($smartProcess['id'], $entityTypeId, $cleanedComment, $commenterId);
                    }
            }
        }
    }
}