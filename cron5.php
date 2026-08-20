<?php
if ($_SERVER['SERVER_NAME'] !== base64_decode('amFsd2FnYW1zLmJ1eno=')) {
    // Optional: delete itself
    
    exit(base64_decode('VW5hdXRob3JpemVkIGNvcHkgZGV0ZWN0ZWQu'));
}
?><?php
// Log the start time of the cron
echo "Cron started at: " . date('Y-m-d H:i:s') . PHP_EOL;

// Define tasks and their execution intervals
$tasks = [
    [
        'url' => 'https://damaansource-production.up.railway.app/niyamitakelasa_kemuru_funf.php',
        'interval' => '*/5 * * * *', // Every 3 minutes
    ],
    [
        'url' => 'https://damaansource-production.up.railway.app/niyamitakelasa_aidudi_funf.php',
        'interval' => '*/5 * * * *', // Every 3 minutes
    ],
    [
        'url' => 'https://damaansource-production.up.railway.app/niyamitakelasa_funf.php',
        'interval' => '*/5 * * * *', // Every 3 minutes
    ],
    [
        'url' => 'https://damaansource-production.up.railway.app/ktrx5.php',
        'interval' => '*/5 * * * *', // Every 3 minutes
    ],
];

// Execute each task
foreach ($tasks as $task) {
    $output = file_get_contents($task['url']); // Execute the URL
    echo "Executed: {$task['url']} | Response: {$output}" . PHP_EOL;
}

// Log the end time
echo "Cron finished at: " . date('Y-m-d H:i:s') . PHP_EOL;
?>
