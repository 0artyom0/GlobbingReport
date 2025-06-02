<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexActivityRequest;
use App\Models\SmartProcessActivity;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function index(IndexActivityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $innerToken = env('API_REQUEST_TOKEN');

        $entityTypeId = $data['entity_type_id'];
        $elementId = $data['element_id'];
        $token = $data['token'];

        if ($token !== $innerToken) {
            return response()->json([
                'data' => []
            ], 403);
        }

        $activities = SmartProcessActivity::where('entity_type_id', $entityTypeId)
            ->where('element_id', $elementId)
            ->get();

        return response()->json([
            'data' => $activities,
        ]);
    }
}
