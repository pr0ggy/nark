<?php

namespace Phaser;

define('PHASER_LIB', dirname(__FILE__).'/lib');

function requireModules(array $modulePaths) {
    foreach ($modulePaths as $path) {
        require PHASER_LIB."/{$path}.php";
    }
}

requireModules([
    'interface',
    'phaser.func'
]);
