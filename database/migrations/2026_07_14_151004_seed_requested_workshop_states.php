<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('workshop_states')) {
            return;
        }

        foreach ([
            'Vendida' => 'Vendidos',
            'Entregue' => 'Entregues',
            'Reclamação do stand' => 'Reclamações stand',
            'Check-up' => 'Viaturas check up',
        ] as $from => $to) {
            DB::table('workshop_states')->where('name', $from)->update([
                'name' => $to,
                'updated_at' => now(),
            ]);
        }

        $states = [
            'Vendidos',
            'Entregues',
            'Reclamações stand',
            'Revisão agendada',
            'Viaturas check up',
            'Importados para check up',
            'Viaturas para reparar',
            'Viaturas para esta semana',
            'Higienizar',
            'Stand',
            'Rent',
            'URGENTE',
            'Pintura exterior',
            'Revisão clientes',
            'Pendentes',
            'Peritagens',
            'Avarias rent',
            'Reparações rent',
            'Reparações clientes',
        ];

        foreach ($states as $position => $name) {
            $existingStateId = DB::table('workshop_states')->where('name', $name)->value('id');
            if ($existingStateId) {
                DB::table('workshop_states')->where('id', $existingStateId)->update([
                    'position' => $position + 1,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('workshop_states')->insert([
                'name' => $name,
                'position' => $position + 1,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('workshop_states')
            ->whereNotIn('name', $states)
            ->where('position', '<=', count($states))
            ->increment('position', count($states));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('workshop_states')) {
            return;
        }

        foreach ([
            'Vendidos' => 'Vendida',
            'Entregues' => 'Entregue',
            'Reclamações stand' => 'Reclamação do stand',
            'Viaturas check up' => 'Check-up',
        ] as $from => $to) {
            DB::table('workshop_states')->where('name', $from)->update([
                'name' => $to,
                'updated_at' => now(),
            ]);
        }
    }
};
