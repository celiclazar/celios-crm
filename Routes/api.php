<?php

use Illuminate\Support\Facades\Route;
use Modules\Crm\Http\Controllers\Api\V1\CrmContactController;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', 'admin.access', 'module:crm'])
        ->prefix('admin')
        ->group(function () {
            Route::get('/crm/contacts', [CrmContactController::class, 'index']);
            Route::post('/crm/contacts', [CrmContactController::class, 'store']);
            Route::get('/crm/contacts/{contact}', [CrmContactController::class, 'show']);
            Route::put('/crm/contacts/{contact}', [CrmContactController::class, 'update']);
            Route::delete('/crm/contacts/{contact}', [CrmContactController::class, 'destroy']);
            Route::post('/crm/contacts/{contact}/notes', [CrmContactController::class, 'storeNote']);
        });
});
