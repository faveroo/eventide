<?php

namespace App\Http\Controllers\Api;

use App\Data\Monitoring\EventAcceptedData;
use App\Data\Monitoring\EventInputData;
use App\Services\EventIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends MonitoringController
{
    public function store(Request $request, EventIngestor $ingestor): JsonResponse
    {
        $project = $this->project($request);
        abort_if(strlen($request->getContent()) > 1048576, 413, 'Event payload exceeds 1 MiB.');
        if ($request->hasHeader('Idempotency-Key')) {
            $request->merge(['external_id' => $request->header('Idempotency-Key')]);
        }
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:128'],
            'type' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.:-]+$/'],
            'severity' => ['sometimes', 'in:debug,info,warning,error,critical'],
            'message' => ['nullable', 'string', 'max:10000'],
            'payload' => ['sometimes', 'array'],
            'occurred_at' => ['sometimes', 'date'],
        ]);
        $event = $ingestor->ingest($project, EventInputData::from($data));

        return response()->json(['data' => new EventAcceptedData($event->id)], 202);
    }
}
