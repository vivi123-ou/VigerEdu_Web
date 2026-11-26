<?php

use App\Support\CrmPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vai trò & phân quyền CRM (đặc tả viger_crm mục 6.2): roles, permissions, role_permission + nhóm làm việc.
 * Nạp sẵn ma trận mặc định từ App\Support\CrmPermissions; sau đó quản trị sửa trên giao diện.
 * users.crm_role giữ key vai trò; 'staff' cũ => 'consultant'.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usersIdUnsigned = str_contains(
            strtolower(collect(Schema::getColumns('users'))->firstWhere('name', 'id')['type'] ?? ''),
            'unsigned'
        );

        Schema::create('crm_roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->string('scope', 10)->default('own');      // all | team | own
            $table->boolean('is_system')->default(false);     // true: không sửa quyền được (Super Admin)
            $table->timestamps();
        });

        Schema::create('crm_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label');
            $table->string('group', 60);
        });

        Schema::create('crm_role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('crm_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('crm_permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('crm_teams', function (Blueprint $table) use ($usersIdUnsigned) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->bigInteger('manager_id', unsigned: $usersIdUnsigned)->nullable();
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('crm_team_id')->nullable()->constrained('crm_teams')->nullOnDelete();
        });

        // ---- Nạp mặc định ----
        foreach (CrmPermissions::PERMISSIONS as $key => [$label, $group]) {
            DB::table('crm_permissions')->insert(['key' => $key, 'label' => $label, 'group' => $group]);
        }
        $permIds = DB::table('crm_permissions')->pluck('id', 'key');

        foreach (CrmPermissions::DEFAULT_ROLES as $key => $role) {
            $roleId = DB::table('crm_roles')->insertGetId([
                'key' => $key, 'name' => $role['name'], 'description' => $role['description'],
                'scope' => $role['scope'], 'is_system' => $role['system'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $perms = $role['permissions'] === '*' ? $permIds->keys()->all() : $role['permissions'];
            foreach ($perms as $p) {
                DB::table('crm_role_permission')->insert(['role_id' => $roleId, 'permission_id' => $permIds[$p]]);
            }
        }

        DB::table('users')->where('crm_role', 'staff')->update(['crm_role' => 'consultant']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crm_team_id');
        });
        Schema::dropIfExists('crm_teams');
        Schema::dropIfExists('crm_role_permission');
        Schema::dropIfExists('crm_permissions');
        Schema::dropIfExists('crm_roles');
    }
};
