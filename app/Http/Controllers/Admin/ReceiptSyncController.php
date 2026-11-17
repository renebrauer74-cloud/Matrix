<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\BuildReceiptSyncStateAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stand der Quittungs-App für den Abgleich – die App fragt beim Öffnen und alle 30 Sekunden nach.
 */
class ReceiptSyncController extends Controller
{
    public function __invoke(Request $request, BuildReceiptSyncStateAction $buildState): JsonResponse
    {
        return response()->json([
            ...$buildState->execute($request->query('version'), $request),
            'user' => ['id' => $request->user()->id, 'name' => $request->user()->name],
        ]);
    }
}
