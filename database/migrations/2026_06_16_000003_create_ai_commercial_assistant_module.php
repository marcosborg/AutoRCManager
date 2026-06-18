<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('company_name')->nullable();
            $table->string('commercial_phone')->nullable();
            $table->boolean('active')->default(true);
            $table->longText('system_prompt')->nullable();
            $table->longText('rules')->nullable();
            $table->longText('forbidden_topics')->nullable();
            $table->longText('allowed_topics')->nullable();
            $table->longText('escalation_rules')->nullable();
            $table->string('default_language')->default('pt-PT');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_training_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_id')->nullable()->constrained('ai_assistants')->nullOnDelete();
            $table->string('title');
            $table->string('type')->default('instruction');
            $table->longText('content');
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('chat_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('source')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('vehicle_reference')->nullable();
            $table->string('vehicle_title')->nullable();
            $table->string('vehicle_url')->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->decimal('monthly_payment', 12, 2)->nullable();
            $table->boolean('wants_financing')->default(false);
            $table->boolean('has_trade_in')->default(false);
            $table->string('trade_in_brand')->nullable();
            $table->string('trade_in_model')->nullable();
            $table->string('trade_in_year')->nullable();
            $table->string('trade_in_kms')->nullable();
            $table->text('trade_in_notes')->nullable();
            $table->string('urgency')->nullable();
            $table->string('priority')->default('low')->index();
            $table->string('status')->default('open')->index();
            $table->text('summary')->nullable();
            $table->longText('ai_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_id')->constrained('ai_assistants')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('chat_leads')->nullOnDelete();
            $table->foreignId('channel_id')->constrained('chat_channels')->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('customer_identifier')->nullable()->index();
            $table->string('customer_phone')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->boolean('human_takeover')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('sender');
            $table->longText('message');
            $table->string('external_id')->nullable()->index();
            $table->string('delivery_status')->default('sent')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $channels = [
            ['name' => 'WhatsApp', 'slug' => 'whatsapp'],
            ['name' => 'Facebook', 'slug' => 'facebook'],
            ['name' => 'Instagram', 'slug' => 'instagram'],
        ];

        foreach ($channels as $channel) {
            DB::table('chat_channels')->updateOrInsert(['slug' => $channel['slug']], array_merge($channel, [
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        DB::table('ai_assistants')->updateOrInsert(
            ['slug' => 'carsete'],
            [
                'name' => 'Assistente Comercial Car 7',
                'company_name' => config('ai_assistant.company_name', 'Car 7'),
                'commercial_phone' => config('ai_assistant.commercial_phone', '913203600'),
                'active' => true,
                'default_language' => 'pt-PT',
                'system_prompt' => "Está a falar com o assistente virtual da empresa.\n\nÉs um pré-vendedor digital de um stand automóvel. Responde em português de Portugal, de forma educada, profissional e direta.",
                'rules' => "Recolhe informação progressivamente. Não faças demasiadas perguntas de uma vez. Encaminha para comercial quando existir intenção clara de compra.",
                'allowed_topics' => "Venda de viaturas, viaturas disponíveis, retomas, financiamento, garantias, oficina, salvados, serviços da empresa e marcação de visitas.",
                'forbidden_topics' => "Não fales de assuntos fora do negócio automóvel. Não recomendes concorrentes, não avalies retomas, não dês valores de retoma, não negocies preços, não prometas aprovação de financiamento e não inventes informação.",
                'escalation_rules' => "Escalar para humano quando o cliente pedir comercial, não quiser IA, mostrar irritação, quiser negociar preço, avaliar retoma, reservar ou avançar com financiamento.",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissions = [
            'ai_assistant_access', 'ai_assistant_create', 'ai_assistant_edit', 'ai_assistant_show', 'ai_assistant_delete',
            'ai_training_content_access', 'ai_training_content_create', 'ai_training_content_edit', 'ai_training_content_show', 'ai_training_content_delete',
            'chat_lead_access', 'chat_lead_create', 'chat_lead_show', 'chat_lead_edit', 'chat_lead_delete',
            'chat_conversation_access', 'chat_conversation_show', 'chat_conversation_edit', 'chat_conversation_delete',
        ];

        $roleIds = DB::table('roles')->whereIn('title', ['Admin', 'Adm'])->pluck('id');

        foreach ($permissions as $title) {
            $permissionId = DB::table('permissions')->where('title', $title)->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('title', [
                'ai_assistant_access', 'ai_assistant_create', 'ai_assistant_edit', 'ai_assistant_show', 'ai_assistant_delete',
                'ai_training_content_access', 'ai_training_content_create', 'ai_training_content_edit', 'ai_training_content_show', 'ai_training_content_delete',
                'chat_lead_access', 'chat_lead_create', 'chat_lead_show', 'chat_lead_edit', 'chat_lead_delete',
                'chat_conversation_access', 'chat_conversation_show', 'chat_conversation_edit', 'chat_conversation_delete',
            ])
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_leads');
        Schema::dropIfExists('chat_channels');
        Schema::dropIfExists('ai_training_contents');
        Schema::dropIfExists('ai_assistants');
    }
};
