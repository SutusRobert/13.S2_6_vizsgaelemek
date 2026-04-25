<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryFeatureTest extends TestCase
{
    public function test_inventory_create_creates_default_household_if_missing(): void
    {
        $userId = $this->createUser(['full_name' => 'Peter Parker']);

        $this->withSession($this->loggedSession($userId, 'Peter Parker'))
            ->get('/inventory')
            ->assertOk();

        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseCount('household_members', 1);
    }

    public function test_inventory_list_creates_default_household_if_missing(): void
    {
        $userId = $this->createUser(['full_name' => 'Peter Parker']);

        $this->withSession($this->loggedSession($userId, 'Peter Parker'))
            ->get('/inventory/list')
            ->assertOk();

        $this->assertDatabaseHas('households', ['owner_id' => $userId]);
    }

    public function test_inventory_store_inserts_item(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId, 'Kitchen');
        $this->addHouseholdMember($householdId, $userId);

        $this->withSession($this->loggedSession($userId))
            ->post('/inventory', [
                'hid' => $householdId,
                'name' => 'Milk',
                'category' => 'Dairy',
                'location' => 'fridge',
                'quantity' => 2,
                'unit' => 'l',
            ])
            ->assertRedirect('/inventory/list?hid='.$householdId);

        $this->assertDatabaseHas('inventory_items', [
            'household_id' => $householdId,
            'name' => 'Milk',
            'location' => 'fridge',
        ]);
    }

    public function test_inventory_list_filters_by_search_term(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId);
        $this->addHouseholdMember($householdId, $userId);

        DB::table('inventory_items')->insert([
            [
                'household_id' => $householdId,
                'name' => 'Milk',
                'location' => 'fridge',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'household_id' => $householdId,
                'name' => 'Rice',
                'location' => 'pantry',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->withSession($this->loggedSession($userId))
            ->get('/inventory/list?hid='.$householdId.'&q=Milk')
            ->assertOk()
            ->assertSee('Milk')
            ->assertDontSee('Rice');
    }

    public function test_inventory_list_filters_by_location(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId);
        $this->addHouseholdMember($householdId, $userId);

        DB::table('inventory_items')->insert([
            [
                'household_id' => $householdId,
                'name' => 'Peas',
                'location' => 'freezer',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'household_id' => $householdId,
                'name' => 'Bread',
                'location' => 'pantry',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->withSession($this->loggedSession($userId))
            ->get('/inventory/list?hid='.$householdId.'&loc=freezer')
            ->assertOk()
            ->assertSee('Peas')
            ->assertDontSee('Bread');
    }

    public function test_inventory_update_action_updates_item(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId);
        $this->addHouseholdMember($householdId, $userId);
        $itemId = DB::table('inventory_items')->insertGetId([
            'household_id' => $householdId,
            'name' => 'Milk',
            'location' => 'fridge',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession($this->loggedSession($userId))
            ->post('/inventory/list', [
                'hid' => $householdId,
                'id' => $itemId,
                'action' => 'update',
                'location' => 'freezer',
                'quantity' => 3,
                'expires_at' => '2026-05-01',
            ])
            ->assertRedirect('/inventory/list?hid='.$householdId);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $itemId,
            'location' => 'freezer',
        ]);
    }

    public function test_inventory_delete_action_removes_item(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId);
        $this->addHouseholdMember($householdId, $userId);
        $itemId = DB::table('inventory_items')->insertGetId([
            'household_id' => $householdId,
            'name' => 'Milk',
            'location' => 'fridge',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession($this->loggedSession($userId))
            ->post('/inventory/list', [
                'hid' => $householdId,
                'id' => $itemId,
                'action' => 'delete',
            ])
            ->assertRedirect('/inventory/list?hid='.$householdId);

        $this->assertDatabaseMissing('inventory_items', ['id' => $itemId]);
    }

    public function test_inventory_delete_all_action_removes_household_items(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId);
        $this->addHouseholdMember($householdId, $userId);

        DB::table('inventory_items')->insert([
            [
                'household_id' => $householdId,
                'name' => 'Milk',
                'location' => 'fridge',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'household_id' => $householdId,
                'name' => 'Rice',
                'location' => 'pantry',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->withSession($this->loggedSession($userId))
            ->post('/inventory/list', [
                'hid' => $householdId,
                'action' => 'delete_all',
            ])
            ->assertRedirect('/inventory/list?hid='.$householdId);

        $this->assertDatabaseCount('inventory_items', 0);
    }
}
