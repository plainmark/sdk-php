<?php

define('DEBUG', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use Plainmark\Plainmark;
use Plainmark\App;

$plainmark = new Plainmark('demo', 'demo');
$app = $plainmark->getApp('53e6924e05a75137258b45c4');

$score = $plainmark->getScore($app);
echo "The package has {$score['stars']} stars with {$score['score']}/{$score['max_score']} points\n";

echo "Annotation:\n";
foreach ($plainmark->getAnnotations($app) as $item)
	echo "  * {$item}\n";

