<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Plainmark\Plainmark;
use Plainmark\App;

$plainmark = new Plainmark('demo', 'demo');
echo $plainmark->submit('en-US', 'Example company', 'Do some example job', 'example.com', '/home/lab/example.com.apk');
