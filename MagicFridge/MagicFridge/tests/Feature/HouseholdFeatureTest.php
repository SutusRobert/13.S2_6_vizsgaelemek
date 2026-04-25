<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HouseholdFeatureTest extends TestCase
{
    public function test_household_index_creates_default_household_for_user(): void
    {
        $userId = $this->createUser(['full_name' => 'Bruce Wayne']);

        $this->withSession($this->loggedSession($userId, 'Bruce Wayne'))
            ->get('/households')
            ->assertOk()
            ->assertSee('Bruce Wayne household');
    }

    public function test_household_invite_requires_email(): void
    {
        $userId = $this->createUser();
        $householdId = $this->createHousehold($userId);
        $this->addHouseholdMember($householdId, $userId);

        $this->withSession($this->loggedSession($userId))
            ->post('/households/invite', ['email' => ''])
            ->assertRedirect('/households')
            ->assertSessionHasErrors();
    }

    public function test_household_invite_creates_invite_and_message(): void
    {
        $ownerId = $this->createUser([
            'full_name' => 'Bruce Wayne',
            'email' => 'owner@example.com',
        ]);
        $guestId = $this->createUser([
            'full_name' => 'Clark Kent',
            'email' => 'guest@example.com',
        ]);
        $householdId = $this->createHousehold($ownerId, 'Wayne Manor');
        $this->addHouseholdMember($householdId, $ownerId);

        $this->withSession($this->loggedSession($ownerId, 'Bruce Wayne'))
            ->post('/households/invite', ['email' => 'guest@example.com'])
            ->assertRedirect('/households');

        $this->assertDatabaseHas('household_invites', [
            'household_id' => $householdId,
            'invited_user_id' => $guestId,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('messages', [
            'user_id' => $guestId,
            'title' => 'Household invitation',
        ]);
    }

    public function test_household_invite_rejects_self_invite(): void
    {
        $ownerId = $this->createUser(['email' => 'owner@example.com']);
        $householdId = $this->createHousehold($ownerId);
        $this->addHouseholdMember($householdId, $ownerId);

        $this->withSession($this->loggedSession($ownerId))
            ->post('/households/invite', ['email' => 'owner@example.com'])
            ->assertRedirect('/households')
            ->assertSessionHasErrors();
    }

    public function test_household_invite_rejects_existing_member(): void
    {
        $ownerId = $this->createUser(['email' => 'owner@example.com']);
        $memberId = $this->createUser(['email' => 'member@example.com']);
        $householdId = $this->createHousehold($ownerId);
        $this->addHouseholdMember($householdId, $ownerId);
        $this->addHouseholdMember($householdId, $memberId);

        $this->withSession($this->loggedSession($ownerId))
            ->post('/households/invite', ['email' => 'member@example.com'])
            ->assertRedirect('/households')
            ->assertSessionHasErrors();
    }

    public function test_household_toggle_role_switches_member_role_for_owner(): void
    {
        $ownerId = $this->createUser();
        $memberId = $this->createUser(['email' => 'member@example.com']);
        $householdId = $this->createHousehold($ownerId);
        $this->addHouseholdMember($householdId, $ownerId);
        $hmId = $this->addHouseholdMember($householdId, $memberId, 'tag');

        $this->withSession($this->loggedSession($ownerId))
            ->post('/households/toggle-role', ['hm_id' => $hmId])
            ->assertRedirect('/households');

        $this->assertDatabaseHas('household_members', [
            'id' => $hmId,
            'role' => 'alap felhasználó',
        ]);
    }

    public function test_household_toggle_role_rejects_non_owner(): void
    {
        $ownerId = $this->createUser(['email' => 'owner@example.com']);
        $memberId = $this->createUser(['email' => 'member@example.com']);
        $otherUserId = $this->createUser(['email' => 'other@example.com']);
        $householdId = $this->createHousehold($ownerId);
        $this->addHouseholdMember($householdId, $ownerId);
        $hmId = $this->addHouseholdMember($householdId, $memberId, 'tag');

        $this->withSession($this->loggedSession($otherUserId))
            ->post('/households/toggle-role', ['hm_id' => $hmId])
            ->assertRedirect('/households')
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('household_members', [
            'id' => $hmId,
            'role' => 'tag',
        ]);
    }
}
