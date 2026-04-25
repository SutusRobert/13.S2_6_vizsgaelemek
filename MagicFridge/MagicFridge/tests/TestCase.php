<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithViews;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rebuildMagicFridgeTestSchema();
    }

    protected function rebuildMagicFridgeTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'message_reads',
            'shopping_list_items',
            'recipe_ingredients',
            'recipes',
            'messages',
            'inventory_items',
            'household_invites',
            'household_members',
            'households',
            'sessions',
            'password_reset_tokens',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('email_verify_token')->nullable();
            $table->rememberToken()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');
        });

        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('name');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id');
            $table->unsignedBigInteger('member_id');
            $table->string('role')->default('tag');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('household_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id');
            $table->unsignedBigInteger('invited_user_id');
            $table->unsignedBigInteger('invited_by_user_id');
            $table->string('status')->default('pending');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('responded_at')->nullable();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('location');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->boolean('expired_notified')->default(false);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('body');
            $table->string('link_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_id');
            $table->string('ingredient');
            $table->string('measure')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
        });

        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('household_id');
            $table->string('name');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_bought')->default(false);
            $table->timestamp('bought_at')->nullable();
            $table->unsignedBigInteger('bought_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('location')->nullable();
        });
    }

    protected function createUser(array $overrides = []): int
    {
        $id = DB::table('users')->insertGetId(array_merge([
            'name' => $overrides['name'] ?? 'Test User',
            'full_name' => $overrides['full_name'] ?? 'Test User',
            'email' => $overrides['email'] ?? ('user'.uniqid().'@example.com'),
            'email_verified_at' => $overrides['email_verified_at'] ?? now(),
            'password' => $overrides['password'] ?? Hash::make('secret123'),
            'email_verify_token' => $overrides['email_verify_token'] ?? null,
            'created_at' => $overrides['created_at'] ?? now(),
            'updated_at' => $overrides['updated_at'] ?? now(),
        ], $overrides));

        return (int) $id;
    }

    protected function createHousehold(int $ownerId, ?string $name = null): int
    {
        $id = DB::table('households')->insertGetId([
            'owner_id' => $ownerId,
            'name' => $name ?? 'Test Household',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $id;
    }

    protected function addHouseholdMember(int $householdId, int $memberId, string $role = 'tag'): int
    {
        $id = DB::table('household_members')->insertGetId([
            'household_id' => $householdId,
            'member_id' => $memberId,
            'role' => $role,
            'created_at' => now(),
        ]);

        return (int) $id;
    }

    protected function loggedSession(int $userId, string $fullName = 'Test User'): array
    {
        return [
            'user_id' => $userId,
            'full_name' => $fullName,
            'user_name' => $fullName,
        ];
    }
}
