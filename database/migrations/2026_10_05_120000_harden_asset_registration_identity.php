<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('assets')
            ->select('tenant_id', 'classification_id', 'acquisition_year', 'register_number', DB::raw('COUNT(*) AS duplicate_count'))
            ->whereNotNull('register_number')
            ->whereNotNull('acquisition_year')
            ->groupBy('tenant_id', 'classification_id', 'acquisition_year', 'register_number')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException('Duplicate NUP exists inside tenant/classification/year scope. Resolve legacy data before applying the asset identity constraint.');
        }

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX assets_nup_scope_unique
            ON assets (tenant_id, classification_id, acquisition_year, register_number)
            WHERE register_number IS NOT NULL AND acquisition_year IS NOT NULL
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION desatara_guard_asset_registration_identity() RETURNS trigger AS $$
            BEGIN
                IF OLD.register_number IS NOT NULL
                   AND (
                       NEW.register_number IS DISTINCT FROM OLD.register_number OR
                       NEW.acquisition_date IS DISTINCT FROM OLD.acquisition_date OR
                       NEW.acquisition_year IS DISTINCT FROM OLD.acquisition_year
                   )
                   AND current_setting('desatara.asset_history_write', true) IS DISTINCT FROM 'on' THEN
                    RAISE EXCEPTION 'issued asset registration identity must use the controlled history action' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER assets_registration_identity_guard
            BEFORE UPDATE OF register_number, acquisition_date, acquisition_year ON assets
            FOR EACH ROW EXECUTE FUNCTION desatara_guard_asset_registration_identity();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS assets_registration_identity_guard ON assets;
            DROP FUNCTION IF EXISTS desatara_guard_asset_registration_identity();
        SQL);
        DB::statement('DROP INDEX IF EXISTS assets_nup_scope_unique');
    }
};
