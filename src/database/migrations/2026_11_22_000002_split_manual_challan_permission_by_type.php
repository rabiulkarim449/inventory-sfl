<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The one "Manual Challan" permission row becomes one row per challan type
 * (inv_mc_{type}) plus "Closing Summary" (inv_closing_summary). Every role
 * keeps what it had: its old ticks are copied to each new row (Closing
 * Summary has no Approve). The same is done for the Developer-hidden list.
 */
return new class extends Migration
{
    private const OLD = 'inv_manual_challan';

    private const TYPES = ['shipment', 'inventory_return', 'marketing', 'pp_sample'];

    private const SUMMARY = 'inv_closing_summary';

    public function up(): void
    {
        $this->eachRole(function (array $perms) {
            if (! isset($perms[self::OLD]) || ! is_array($perms[self::OLD])) {
                return null;
            }
            $old = $perms[self::OLD];
            unset($perms[self::OLD]);
            foreach (self::TYPES as $type) {
                $perms['inv_mc_' . $type] = $old;
            }
            $perms[self::SUMMARY] = array_diff_key($old, ['approve' => true]);

            return $perms;
        });
    }

    public function down(): void
    {
        $this->eachRole(function (array $perms) {
            $keys = [...array_map(fn ($t) => 'inv_mc_' . $t, self::TYPES), self::SUMMARY];
            $merged = [];
            foreach ($keys as $key) {
                if (isset($perms[$key]) && is_array($perms[$key])) {
                    $merged += $perms[$key];
                }
                unset($perms[$key]);
            }
            if ($merged === []) {
                return null;
            }
            $perms[self::OLD] = $merged;

            return $perms;
        });
    }

    /** Runs $change on every role's permission JSON and on the Developer-hidden list; null = leave as is. */
    private function eachRole(callable $change): void
    {
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'permission')) {
            foreach (DB::table('permissions')->whereNotNull('permission')->get(['id', 'permission']) as $role) {
                $perms = json_decode($role->permission, true);
                if (is_array($perms) && ($new = $change($perms)) !== null) {
                    DB::table('permissions')->where('id', $role->id)->update(['permission' => json_encode($new)]);
                }
            }
        }

        if (Schema::hasTable('generals') && Schema::hasColumn('generals', 'hidden_permission_modules')) {
            foreach (DB::table('generals')->whereNotNull('hidden_permission_modules')->get(['id', 'hidden_permission_modules']) as $g) {
                $hidden = json_decode($g->hidden_permission_modules, true);
                if (is_array($hidden) && ($new = $change($hidden)) !== null) {
                    DB::table('generals')->where('id', $g->id)->update(['hidden_permission_modules' => json_encode($new)]);
                }
            }
        }
    }
};
