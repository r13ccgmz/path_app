<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

try {
    $response = $app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
        \Illuminate\Http\Request::create('/admin/list-of-students', 'GET')
    );
    file_put_contents('page_dump.html', $response->getContent());
    echo "Dumped HTML to page_dump.html (size: " . strlen($response->getContent()) . " bytes)\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
