<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schema = Illuminate\Support\Facades\Schema::getFacadeRoot();

if (!$schema->hasColumn('users', 'email_verified_at')) {
    $schema->table('users', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->timestamp('email_verified_at')->nullable()->after('email');
    });
    echo "added email_verified_at\n";
} else {
    echo "email_verified_at exists\n";
}

if (!$schema->hasColumn('users', 'email_verify_token')) {
    $schema->table('users', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->string('email_verify_token', 64)->nullable()->unique()->after('email_verified_at');
    });
    echo "added email_verify_token\n";
} else {
    echo "email_verify_token exists\n";
}
