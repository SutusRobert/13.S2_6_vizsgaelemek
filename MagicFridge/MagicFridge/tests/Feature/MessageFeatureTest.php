<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageFeatureTest extends TestCase
{
    public function test_messages_index_renders_direct_messages(): void
    {
        $userId = $this->createUser();

        DB::table('messages')->insert([
            'user_id' => $userId,
            'title' => 'Reminder',
            'body' => 'Check the inventory',
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->withSession($this->loggedSession($userId))
            ->get('/messages')
            ->assertOk()
            ->assertSee('Reminder');
    }

    public function test_messages_mark_read_updates_own_message(): void
    {
        $userId = $this->createUser();
        $messageId = DB::table('messages')->insertGetId([
            'user_id' => $userId,
            'title' => 'Reminder',
            'body' => 'Check the inventory',
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->withSession($this->loggedSession($userId))
            ->from('/messages')
            ->post('/messages/read', ['id' => $messageId])
            ->assertRedirect('/messages');

        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'is_read' => 1,
        ]);
    }

    public function test_messages_delete_removes_own_message(): void
    {
        $userId = $this->createUser();
        $messageId = DB::table('messages')->insertGetId([
            'user_id' => $userId,
            'title' => 'Reminder',
            'body' => 'Check the inventory',
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->withSession($this->loggedSession($userId))
            ->from('/messages')
            ->post('/messages/delete', ['id' => $messageId])
            ->assertRedirect('/messages');

        $this->assertDatabaseMissing('messages', ['id' => $messageId]);
    }

    public function test_messages_accept_invite_adds_membership_and_marks_message_read(): void
    {
        $ownerId = $this->createUser(['email' => 'owner@example.com']);
        $inviteeId = $this->createUser(['email' => 'invitee@example.com']);
        $householdId = $this->createHousehold($ownerId, 'Shared Home');
        $this->addHouseholdMember($householdId, $ownerId);

        $inviteId = DB::table('household_invites')->insertGetId([
            'household_id' => $householdId,
            'invited_user_id' => $inviteeId,
            'invited_by_user_id' => $ownerId,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $messageId = DB::table('messages')->insertGetId([
            'user_id' => $inviteeId,
            'title' => 'Household invitation',
            'body' => 'Join us',
            'link_url' => 'invite:'.$inviteId,
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->withSession($this->loggedSession($inviteeId))
            ->from('/messages')
            ->post('/messages/respond', [
                'id' => $messageId,
                'action' => 'accept',
            ])
            ->assertRedirect('/messages');

        $this->assertDatabaseHas('household_invites', [
            'id' => $inviteId,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('household_members', [
            'household_id' => $householdId,
            'member_id' => $inviteeId,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'is_read' => 1,
        ]);
    }

    public function test_messages_decline_invite_updates_status_and_marks_message_read(): void
    {
        $ownerId = $this->createUser(['email' => 'owner@example.com']);
        $inviteeId = $this->createUser(['email' => 'invitee@example.com']);
        $householdId = $this->createHousehold($ownerId, 'Shared Home');
        $this->addHouseholdMember($householdId, $ownerId);

        $inviteId = DB::table('household_invites')->insertGetId([
            'household_id' => $householdId,
            'invited_user_id' => $inviteeId,
            'invited_by_user_id' => $ownerId,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $messageId = DB::table('messages')->insertGetId([
            'user_id' => $inviteeId,
            'title' => 'Household invitation',
            'body' => 'Join us',
            'link_url' => 'invite:'.$inviteId,
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->withSession($this->loggedSession($inviteeId))
            ->from('/messages')
            ->post('/messages/respond', [
                'id' => $messageId,
                'action' => 'decline',
            ])
            ->assertRedirect('/messages');

        $this->assertDatabaseHas('household_invites', [
            'id' => $inviteId,
            'status' => 'declined',
        ]);
        $this->assertDatabaseMissing('household_members', [
            'household_id' => $householdId,
            'member_id' => $inviteeId,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'is_read' => 1,
        ]);
    }
}
