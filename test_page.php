<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

error_reporting(E_ALL);
ini_set('display_errors', '1');

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Finding a user to authenticate...\n";
$user = User::first();
if (!$user) {
    echo "No user found in the database!\n";
    exit(1);
}
echo "Authenticating as: {$user->email}\n";
Auth::login($user);

echo "Simulating request to /admin/list-of-students...\n";
try {
    $response = $app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
        \Illuminate\Http\Request::create('/admin/list-of-students', 'GET')
    );
    echo "Response status code: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 400) {
        echo "Error response body snippet:\n";
        echo substr($response->getContent(), 0, 1000) . "\n";
    } else {
        echo "Page rendered successfully!\n";
        // Check if the script for chart.js is in the HTML
        $content = $response->getContent();
        if (strpos($content, 'chart.js') !== false) {
            echo "Found chart.js script in the response!\n";
        } else {
            echo "WARNING: chart.js script NOT found in response!\n";
        }
        
        // Check if widgets are present in the response
        if (strpos($content, 'fi-section-overview') !== false) {
            echo "Found StudentOverviewWidget in the HTML!\n";
        } else {
            echo "WARNING: StudentOverviewWidget NOT found in HTML!\n";
        }
        if (strpos($content, 'fi-section-demographics') !== false) {
            echo "Found StudentDemographicsChartsWidget in the HTML!\n";
        } else {
            echo "WARNING: StudentDemographicsChartsWidget NOT found in HTML!\n";
        }
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
