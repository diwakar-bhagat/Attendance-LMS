<?php

namespace Tests\Feature\Security;

use App\Domain\Core\Models\Institution;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_see_users_from_another_institution()
    {
        // Setup Tenant A
        $institutionA = Institution::factory()->create(['name' => 'College A']);
        $adminA = User::factory()->create([
            'institution_id' => $institutionA->id,
            'role_id' => Role::where('name', 'admin')->first()->id ?? 1
        ]);
        User::factory()->count(5)->create(['institution_id' => $institutionA->id]);

        // Setup Tenant B
        $institutionB = Institution::factory()->create(['name' => 'College B']);
        User::factory()->count(5)->create(['institution_id' => $institutionB->id]);

        // Assert DB has 11 users (2 admins maybe + 10 normal users)
        $this->assertTrue(User::withoutGlobalScopes()->count() >= 10);

        // Login as Admin from Tenant A
        session(['institution_id' => $institutionA->id]);
        $this->actingAs($adminA);

        // Core Test: Does the TenantScope automatically filter the raw model query?
        $visibleUsers = User::all();

        // 6 expected: 1 admin + 5 users from College A
        $this->assertEquals(6, $visibleUsers->count());
        $this->assertEquals($institutionA->id, $visibleUsers->first()->institution_id);

        // Ensure absolutely no users from College B leaked in
        $leaked = $visibleUsers->where('institution_id', $institutionB->id)->count();
        $this->assertEquals(0, $leaked);
    }
}