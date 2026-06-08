<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Testing StudentOverviewWidget with filter...\n";
try {
    $widget1 = new \App\Filament\Widgets\StudentOverviewWidget();
    $widget1->termFilter = ['1201', '1202'];
    $data1 = $widget1->getOverviewData();
    echo "StudentOverviewWidget data keys: " . implode(', ', array_keys($data1)) . "\n";
    echo "Total: " . ($data1['total'] ?? 'N/A') . "\n";
    echo "Active: " . ($data1['active'] ?? 'N/A') . "\n";
    echo "Graduated: " . ($data1['graduated'] ?? 'N/A') . "\n";
    echo "Chart data: count of labels=" . count($data1['chart']['labels']) . "\n";
    print_r($data1['topPrograms']->toArray());
} catch (\Exception $e) {
    echo "ERROR in StudentOverviewWidget: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

echo "\nTesting StudentDemographicsChartsWidget with filter...\n";
try {
    $widget2 = new \App\Filament\Widgets\StudentDemographicsChartsWidget();
    $widget2->termFilter = ['1201', '1202'];
    $data2 = $widget2->getDemographicsData();
    echo "StudentDemographicsChartsWidget data keys: " . implode(', ', array_keys($data2)) . "\n";
    print_r($data2);
} catch (\Exception $e) {
    echo "ERROR in StudentDemographicsChartsWidget: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
