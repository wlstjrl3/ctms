<?php
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

$db = App\Core\App::getInstance()->db();
$res = $db->fetchAll('SELECT DISTINCT department FROM teachers');
echo "Distinct teacher departments:\n";
print_r($res);

$res2 = $db->fetchAll('SELECT DISTINCT edu_level FROM edu_schedule_new');
echo "\nDistinct schedule levels (edu_level):\n";
print_r($res2);
