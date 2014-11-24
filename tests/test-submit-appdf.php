<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Plainmark\Plainmark;
use Plainmark\App;

$plainmark = new Plainmark('demo', 'demo');
echo $plainmark->submitAppDF('example.com', 'mxplayer.appdf');
