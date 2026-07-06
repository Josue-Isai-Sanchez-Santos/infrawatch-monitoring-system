<?php

use App\Http\Controllers\Api\AgentMetricController;
use Illuminate\Support\Facades\Route;

Route::post('/agent/metrics', [AgentMetricController::class, 'store']);
