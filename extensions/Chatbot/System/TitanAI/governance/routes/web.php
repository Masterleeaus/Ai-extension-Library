<?php

use App\Extensions\TitanAIGovernance\System\Http\Controllers\GovernanceApprovalController;
use App\Extensions\TitanAIGovernance\System\Http\Controllers\GovernanceRollbackController;
use App\Extensions\TitanAIGovernance\System\Http\Controllers\GovernanceConfigurationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('titan-ai/governance')->name('titan-ai.governance.')->group(function (): void {
    Route::get('/configuration', [GovernanceConfigurationController::class, 'index'])->name('configuration.index');
    Route::get('/approvals', [GovernanceApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{approvalId}/approve', [GovernanceApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{approvalId}/reject', [GovernanceApprovalController::class, 'reject'])->name('approvals.reject');
    Route::post('/receipts/{receiptId}/rollback', [GovernanceRollbackController::class, 'store'])->name('receipts.rollback');
});
