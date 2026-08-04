<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main builder configuration table
        Schema::create('ext_chatbot_builder_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('chatbot_id')->nullable();
            $table->string('step_current')->default('configure'); // configure, customize, train, embed, channel
            $table->json('config')->nullable(); // title, bubble_message, welcome_message, instructions
            $table->json('customization')->nullable(); // feature toggles, settings
            $table->json('theme_settings')->nullable(); // colors, gradients, images
            $table->string('publish_status')->default('draft'); // draft, published, archived
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'chatbot_id']);
            $table->index('publish_status');
        });

        // Builder step history and progress tracking
        Schema::create('ext_chatbot_builder_steps', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('builder_config_id');
            $table->string('step_name'); // configure, customize, train, embed, channel
            $table->json('step_data')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('builder_config_id')
                ->references('id')
                ->on('ext_chatbot_builder_configs')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'builder_config_id']);
        });

        // Builder component library and templates
        Schema::create('ext_chatbot_builder_components', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('component_type'); // button, card, input, modal, header, etc.
            $table->string('component_name');
            $table->json('props')->nullable(); // component properties and defaults
            $table->text('html_template')->nullable(); // reusable HTML template
            $table->json('css_classes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'component_type']);
            $table->unique(['tenant_id', 'component_name']);
        });

        // Preview snapshots for builder
        Schema::create('ext_chatbot_builder_previews', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('builder_config_id');
            $table->string('device_type'); // mobile, tablet, desktop
            $table->json('preview_data')->nullable(); // snapshot of rendered preview
            $table->text('screenshot_url')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('builder_config_id')
                ->references('id')
                ->on('ext_chatbot_builder_configs')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'device_type']);
        });

        // Builder templates (reusable builder configurations)
        Schema::create('ext_chatbot_builder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('template_name');
            $table->string('template_category'); // support, sales, booking, shopping, custom
            $table->json('template_config')->nullable();
            $table->json('customization')->nullable();
            $table->json('theme_settings')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system_template')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'template_category']);
            $table->index('is_system_template');
        });

        // Channel configuration for publish step
        Schema::create('ext_chatbot_builder_channels', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('builder_config_id');
            $table->string('channel_type'); // whatsapp, telegram, facebook, website, custom
            $table->json('channel_config')->nullable(); // channel-specific settings
            $table->boolean('enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();

            $table->foreign('builder_config_id')
                ->references('id')
                ->on('ext_chatbot_builder_configs')
                ->onDelete('cascade');
            $table->unique(['tenant_id', 'builder_config_id', 'channel_type']);
        });

        // Builder activity audit trail
        Schema::create('ext_chatbot_builder_activity', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('user_id');
            $table->unsignedBigInteger('builder_config_id');
            $table->string('action'); // create, update, delete, publish, preview
            $table->string('target_step')->nullable(); // which step was modified
            $table->json('changes')->nullable(); // before/after values
            $table->timestamps();

            $table->foreign('builder_config_id')
                ->references('id')
                ->on('ext_chatbot_builder_configs')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'user_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_builder_activity');
        Schema::dropIfExists('ext_chatbot_builder_channels');
        Schema::dropIfExists('ext_chatbot_builder_templates');
        Schema::dropIfExists('ext_chatbot_builder_previews');
        Schema::dropIfExists('ext_chatbot_builder_components');
        Schema::dropIfExists('ext_chatbot_builder_steps');
        Schema::dropIfExists('ext_chatbot_builder_configs');
    }
};
