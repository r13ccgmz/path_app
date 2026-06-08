<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$schema = new Filament\Schemas\Schema(new App\Filament\Pages\StudentHistory());
$schema->columns([
    'md' => 2,
    'xl' => 3,
    '2xl' => 4,
]);
$schema->columns([
    'default' => 1,
    'sm' => 2,
    'md' => 2,
    'lg' => 2,
    'xl' => 2,
    '2xl' => 2,
]);

print_r($schema->getColumns());
echo "\n";
