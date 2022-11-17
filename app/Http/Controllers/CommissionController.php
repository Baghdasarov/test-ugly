<?php

namespace App\Http\Controllers;

use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;

class CommissionController extends Controller
{
    public function __construct(public CommissionService $commissionService)
    {
    }

    public function calculateCommission(): JsonResponse
    {
        return response()->json($this->commissionService->calculateCommissions());
    }
}
