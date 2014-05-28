<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Plainmark\Plainmark;
use Plainmark\App;

$plainmark = new Plainmark('demo', 'demo');
$app = $plainmark->getApp('536ff5aa05a7512a598b45ae');

$score = $plainmark->getScore($app);
echo "The package has {$score['stars']} stars with {$score['score']}/{$score['max_score']} points\n";

echo "Annotation:\n";
foreach ($plainmark->getAnnotations($app) as $item)
	echo "  * {$item}\n";

