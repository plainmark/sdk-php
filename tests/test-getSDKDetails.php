<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Plainmark\Plainmark;
use Plainmark\App;

$plainmark = new Plainmark('demo', 'demo');
$app = $plainmark->getApp('536ff5aa05a7512a598b45ae');
var_dump($plainmark->getSDKDetails($app));
