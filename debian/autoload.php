<?php
// Debian autoloader for abraflexi-cashier
require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/Ease/autoload.php';
// PSR-4 autoloader for application classes
spl_autoload_register(function (string $class): void {
    if (strncmp('AbraFlexi\\Cash\\', $class, 16) === 0) {
        $file = '/usr/lib/abraflexi-cashier/AbraFlexi/Cash/' . str_replace('\\', '/', substr($class, 16)) . '.php';
        if (file_exists($file)) { require $file; }
    }
});
