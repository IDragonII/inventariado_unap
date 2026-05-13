<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = DB::table('users')->first();
if ($user) {
    echo "User: $user->name (ID: $user->id, DNI: $user->dni)\n\n";
    
    $pivots = DB::table('activo_user')->where('user_id', $user->id)->get();
    echo "Total pivots for this user: " . $pivots->count() . "\n\n";
    
    $nullCount = 0;
    $notNullCount = 0;
    foreach ($pivots as $p) {
        if ($p->item === null || $p->item === '') {
            $nullCount++;
        } else {
            $notNullCount++;
        }
    }
    echo "Con item null: $nullCount\n";
    echo "Con item no null: $notNullCount\n";
}
