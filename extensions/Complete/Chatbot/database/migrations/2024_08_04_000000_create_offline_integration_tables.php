<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optimized local intelligence memory with relevance scoring
        Schema::create('local_intelligence_memories', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('user_id');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->text('message');
            $table->string('action')->nullable();
            $table->decimal('confidence', 3, 2)->default(0.0);
            $table->json('entities')->nullable();
            $table->decimal('relevance_score', 3, 2)->default(0.5);
            $table->timestamp('relevance_recalculated_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'conversation_id']);
            $table->index(['tenant_id', 'relevance_score']);
            $table->index('created_at');
        });

        // WorkCore offline operation queue
        Schema::create('workcore_offline_queue', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('user_id');
            $table->string('action_type');
            $table->json('payload');
            $table->enum('status', [
                'pending', 'executing', 'executed', 'awaiting-approval', 'synced', 'failed'
            ])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // WorkCore sync conflict resolution
        Schema::create('workcore_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('conflict_type');
            $table->json('offline_data');
            $table->json('platform_data');
            $table->enum('status', ['unresolved', 'resolved'])->default('unresolved');
            $table->json('resolution')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });

        // AIAgent offline workflow executions
        Schema::create('aiagent_offline_executions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('workflow_id');
            $table->json('initial_context');
            $table->json('execution_result');
            $table->enum('status', [
                'queued', 'executing', 'completed', 'failed'
            ])->default('queued');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'workflow_id']);
            $table->index(['status']);
        });

        // WorkCore embedded tables
        Schema::create('workcore_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->default('individual');
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'phone']);
        });

        Schema::create('workcore_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'synced'])->default('draft');
            $table->date('due_date')->nullable();
            $table->json('items')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id']);
        });

        Schema::create('workcore_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_date');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'synced'])->default('scheduled');
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['scheduled_date']);
        });

        Schema::create('workcore_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('job_id')->nullable();
            $table->dateTime('assigned_at');
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'synced'])->default('assigned');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['job_id']);
        });

        Schema::create('workcore_properties', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('location')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id']);
        });

        Schema::create('workcore_staff', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->json('skills')->nullable();
            $table->decimal('availability_score', 3, 2)->default(1.0);
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_intelligence_memories');
        Schema::dropIfExists('workcore_offline_queue');
        Schema::dropIfExists('workcore_sync_conflicts');
        Schema::dropIfExists('aiagent_offline_executions');
        Schema::dropIfExists('workcore_contacts');
        Schema::dropIfExists('workcore_invoices');
        Schema::dropIfExists('workcore_jobs');
        Schema::dropIfExists('workcore_assignments');
        Schema::dropIfExists('workcore_properties');
        Schema::dropIfExists('workcore_staff');
    }
};
