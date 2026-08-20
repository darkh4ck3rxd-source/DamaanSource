<?php
if ($_SERVER['SERVER_NAME'] !== base64_decode('amFsd2FnYW1zLmJ1eno=')) {
    // Optional: delete itself

    exit(base64_decode('VW5hdXRob3JpemVkIGNvcHkgZGV0ZWN0ZWQu'));
}
?>

<?php
// Log the start time of the cron
echo "Cron started at: " . date('Y-m-d H:i:s') . PHP_EOL;

// Define tasks and their execution intervals
$tasks = [
    [
        'url' => 'https://damaansource-production.up.railway.app/niyamitakelasa_kemuru.php',
        'interval' => '* * * * *', // Every minute
    ],
    [
        'url' => 'https://damaansource-production.up.railway.app/niyamitakelasa_aidudi.php',
        'interval' => '* * * * *', // Every  minutes
    ], 
    [
        'url' => 'https://damaansource-production.up.railway.app/niyamitakelasa.php',
        'interval' => '* * * * *', // Every minute
    ],
    [
        'url' => 'https://damaansource-production.up.railway.app/ktrx.php',
        'interval' => '* * * * *', // Every minute
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
