<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$kernel = new Kernel('dev', true);
return new Application($kernel);
