<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ext_migration_projects')) {
            Schema::create('ext_migration_projects', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('name', 191);
                $table->string('source_type', 80)->nullable();
                $table->string('source_label', 191)->nullable();
                $table->string('target_label', 191)->default('Titan Zero');
                $table->string('status', 40)->default('draft');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->json('settings')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique('uuid', 'mig_projects_uuid_uq');
                $table->index(['company_id', 'status'], 'mig_projects_company_status_ix');
                $table->index(['company_id', 'user_id'], 'mig_projects_company_user_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_connections')) {
            Schema::create('ext_migration_connections', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('connector_key', 100);
                $table->string('name', 191);
                $table->string('direction', 20)->default('source');
                $table->text('credential_reference')->nullable();
                $table->json('settings')->nullable();
                $table->string('status', 40)->default('untested');
                $table->timestamp('last_tested_at')->nullable();
                $table->timestamps();
                $table->unique('uuid', 'mig_connections_uuid_uq');
                $table->unique(['company_id', 'project_id', 'name'], 'mig_connections_name_uq');
                $table->index(['company_id', 'connector_key'], 'mig_connections_connector_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_entity_plans')) {
            Schema::create('ext_migration_entity_plans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('project_id');
                $table->string('source_entity', 191);
                $table->string('target_entity', 191);
                $table->unsignedInteger('sequence')->default(100);
                $table->boolean('enabled')->default(true);
                $table->string('strategy', 40)->default('upsert');
                $table->json('dependency_keys')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'project_id', 'source_entity', 'target_entity'], 'mig_entity_plans_pair_uq');
                $table->index(['company_id', 'project_id', 'sequence'], 'mig_entity_plans_sequence_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_mappings')) {
            Schema::create('ext_migration_mappings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('entity_plan_id');
                $table->string('source_field', 191);
                $table->string('target_field', 191);
                $table->string('transform_key', 100)->nullable();
                $table->json('transform_config')->nullable();
                $table->boolean('required')->default(false);
                $table->text('default_value')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'entity_plan_id'], 'mig_mappings_plan_ix');
                $table->index(['company_id', 'target_field'], 'mig_mappings_target_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_runs')) {
            Schema::create('ext_migration_runs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('state', 40)->default('draft');
                $table->string('mode', 20)->default('dry_run');
                $table->string('idempotency_key', 191)->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->json('counters')->nullable();
                $table->json('options')->nullable();
                $table->text('failure_summary')->nullable();
                $table->timestamps();
                $table->unique('uuid', 'mig_runs_uuid_uq');
                $table->unique(['company_id', 'idempotency_key'], 'mig_runs_idempotency_uq');
                $table->index(['company_id', 'project_id', 'state'], 'mig_runs_project_state_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_steps')) {
            Schema::create('ext_migration_steps', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('run_id');
                $table->unsignedBigInteger('entity_plan_id')->nullable();
                $table->unsignedInteger('sequence')->default(100);
                $table->string('state', 40)->default('queued');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('counters')->nullable();
                $table->text('error_summary')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'run_id', 'sequence'], 'mig_steps_sequence_uq');
                $table->index(['company_id', 'run_id', 'state'], 'mig_steps_run_state_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_checkpoints')) {
            Schema::create('ext_migration_checkpoints', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('run_id');
                $table->unsignedBigInteger('step_id')->nullable();
                $table->string('checkpoint_key', 191);
                $table->json('cursor')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'run_id', 'checkpoint_key'], 'mig_checkpoints_key_uq');
                $table->index(['company_id', 'run_id'], 'mig_checkpoints_run_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_external_ids')) {
            Schema::create('ext_migration_external_ids', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('entity_plan_id')->nullable();
                $table->string('source_type', 120);
                $table->string('source_id', 191);
                $table->string('target_type', 120);
                $table->string('target_id', 191);
                $table->string('checksum', 128)->nullable();
                $table->timestamp('migrated_at')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'project_id', 'source_type', 'source_id'], 'mig_external_ids_source_uq');
                $table->index(['company_id', 'target_type', 'target_id'], 'mig_external_ids_target_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_record_results')) {
            Schema::create('ext_migration_record_results', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('run_id');
                $table->unsignedBigInteger('step_id')->nullable();
                $table->string('source_id', 191);
                $table->string('target_id', 191)->nullable();
                $table->string('status', 40);
                $table->string('checksum', 128)->nullable();
                $table->string('payload_hash', 128)->nullable();
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'run_id', 'status'], 'mig_record_results_status_ix');
                $table->index(['company_id', 'source_id'], 'mig_record_results_source_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_conflicts')) {
            Schema::create('ext_migration_conflicts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('run_id');
                $table->unsignedBigInteger('entity_plan_id')->nullable();
                $table->string('source_id', 191)->nullable();
                $table->string('target_id', 191)->nullable();
                $table->string('type', 80);
                $table->string('status', 40)->default('open');
                $table->json('source_snapshot')->nullable();
                $table->json('target_snapshot')->nullable();
                $table->json('resolution')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'run_id', 'status'], 'mig_conflicts_run_status_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_failures')) {
            Schema::create('ext_migration_failures', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('run_id');
                $table->unsignedBigInteger('step_id')->nullable();
                $table->string('source_id', 191)->nullable();
                $table->string('error_code', 100)->nullable();
                $table->text('error_message');
                $table->string('exception_class', 191)->nullable();
                $table->json('context')->nullable();
                $table->boolean('retryable')->default(false);
                $table->unsignedInteger('attempts')->default(1);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'run_id', 'retryable'], 'mig_failures_run_retry_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_artifacts')) {
            Schema::create('ext_migration_artifacts', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('run_id')->nullable();
                $table->string('type', 80);
                $table->string('disk', 80)->default('local');
                $table->string('path', 500);
                $table->string('mime_type', 191)->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('checksum', 128)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->unique('uuid', 'mig_artifacts_uuid_uq');
                $table->index(['company_id', 'run_id', 'type'], 'mig_artifacts_run_type_ix');
            });
        }

        if (! Schema::hasTable('ext_migration_templates')) {
            Schema::create('ext_migration_templates', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('name', 191);
                $table->string('slug', 120);
                $table->string('connector_key', 100)->nullable();
                $table->json('mapping_schema');
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->unique('uuid', 'mig_templates_uuid_uq');
                $table->unique(['company_id', 'slug'], 'mig_templates_slug_uq');
            });
        }

        if (! Schema::hasTable('ext_migration_audit_events')) {
            Schema::create('ext_migration_audit_events', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('run_id')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('event', 120);
                $table->string('subject_type', 191)->nullable();
                $table->string('subject_id', 191)->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->unique('uuid', 'mig_audit_events_uuid_uq');
                $table->index(['company_id', 'event', 'created_at'], 'mig_audit_events_event_ix');
                $table->index(['company_id', 'run_id'], 'mig_audit_events_run_ix');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_migration_audit_events');
        Schema::dropIfExists('ext_migration_templates');
        Schema::dropIfExists('ext_migration_artifacts');
        Schema::dropIfExists('ext_migration_failures');
        Schema::dropIfExists('ext_migration_conflicts');
        Schema::dropIfExists('ext_migration_record_results');
        Schema::dropIfExists('ext_migration_external_ids');
        Schema::dropIfExists('ext_migration_checkpoints');
        Schema::dropIfExists('ext_migration_steps');
        Schema::dropIfExists('ext_migration_runs');
        Schema::dropIfExists('ext_migration_mappings');
        Schema::dropIfExists('ext_migration_entity_plans');
        Schema::dropIfExists('ext_migration_connections');
        Schema::dropIfExists('ext_migration_projects');
    }
};
