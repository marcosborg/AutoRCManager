<?php

namespace App\Support;

class WorkshopPrivacy
{
    public const ROLES = ['Aux. oficina', 'Chefe oficina'];
    public const VEHICLE_FIELDS = ['id', 'license', 'foreign_license', 'brand_id', 'model', 'version', 'year', 'month', 'license_date', 'color', 'fuel', 'kilometers', 'inspec_b', 'engine_displacement', 'transmission', 'general_state_id', 'workshop_state_id', 'storage_location', 'vehicle_manuals', 'cables', 'cables_2', 'key'];
    public const EDIT_FIELDS = ['license', 'foreign_license', 'brand_id', 'model', 'version', 'year', 'month', 'license_date', 'color', 'fuel', 'kilometers', 'inspec_b', 'engine_displacement', 'transmission', 'storage_location', 'vehicle_manuals', 'cables', 'cables_2', 'key'];

    public static function restricted($user = null): bool
    {
        $user ??= auth()->user();
        return $user && $user->roles->pluck('title')->intersect(self::ROLES)->isNotEmpty();
    }

    public static function commercialAbility(string $ability): bool
    {
        return preg_match('/^(sale_|suplier_|client_|lead_|chat_|ai_|account_|financial_|aquisition_|vehicle_(documents|import|trade_in|group|lot|financial)|depreciation_|appreciation_|audit_log_|vehicle_state_transfer_|stand_cash_|sale_closure_|user_management_|permission_|role_|user_)/', $ability)
            || in_array($ability, ['vehicle_create', 'vehicle_delete'], true);
    }

    public static function blockedRoute(string $name): bool
    {
        $name = preg_replace('/^(admin\.|api\.v1\.backoffice\.)/', '', $name);
        return preg_match('/^(supliers|clients|leads|chat-|ai-|accounts|account-|financial|sales|cash\.|dashboard$|approvals|vehicle-state-transfers|stand-cash-payment-approvals|sale-closure-approvals|vehicle-groups|vehicle-trade-ins|depreciations|appreciations|audit-logs|permissions|roles\.|users\.|import-configuration)/', $name)
            || preg_match('/^vehicles\.(documents|notes|timeline|suspended-sale|trade-ins|supplier-payments|generic-payments|client-payments|create|store$|destroy|massDestroy|deleted|restore)/', $name);
    }
}
