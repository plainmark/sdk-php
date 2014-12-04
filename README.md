Plainmark SDK
=============

Plainmark SDK for PHP is a simple class that provides a wrapper for HTTP-based
RESTful interface to the [Plainmark analytical engine]( http://plainmark.com).

It allows to query Plainmark database for applications tested and to request
an application to be analyzed.

The SDK contains results interpretor - it allows to convert raw analytical
data into human-readable format: application's five-point rating and
its explanation (annotation).

Installation
------------

If using Composer, add our package to your `composer.json` and run `composer update`.

	"require": {
		"plainmark/sdk-php": "dev-master"
	}
	
Otherwise, you can simply include `plainmark.php` file into your source code.

Using of the library
--------------------

You can find examples on how to use the library in the `tests` folder.
Typical usage is:

### Preface
```
<?php

// when using Composer's autoload feature
require_once 'vendor/autoload.php';

// otherwise
require_once 'plainmark.php';

use Plainmark\Plainmark;
use Plainmark\App;
```

### Initializing
```
$plainmark = new Plainmark('your_login', 'your_password');
```

### Searching for an application
```
$id = $plainmark->find('com.android.example.package');
```

### Submit an application for analysis
To submit an apk file:
```
$id = $plainmark->submit('en-US', 'App Title', 'App description',
	'App Vendor', '/path/to/package.apk');
```
To submit [AppDF](http://www.onepf.org/appdf) file:
```
$id = $plainmark->submitAppDF('App Vendor', '/path/to/description.appdf');
```

### Retrieve app analysis details
```
$app = $plainmark->getApp($id);
```

### Download the APK
To download the APK file of the analyzed application:
```
$content = $plainmark->download($id);
```

### Print application's rating and annotation
```
$score = $plainmark->getScore($app);

echo "Rating: {$score['stars']} stars ({$score['score']}/{$score['max_score']}) points\n";

echo "Annotation:\n";
foreach ($plainmark->getAnnotations($app) as $item)
	echo "  * {$item}\n";
```

### Error handling
The library uses exceptions in case of errors - use try-catch when needed.

Tuning parameters
-----------------

You can customize interpretation of the app analysis. Use `config.csv` to change 
the analysis description and its score. 

`config.csv` is a tab delimited sheet file. The file contains a number of 
conditions, every condition is on one line. Every line defines a string in the analysis 
description and the number of points to add to the score calculation, 
should the condition in the given line be satisfied.

**PLEASE NEVER CHANGE THE FIELDS ONE, TWO and THREE**. These columns are 
used for the internal purposes only.

The rest of the fields are:

*	**Field 4**. Number of points used in the calculation of the score. 
*	**Field 5**. Rule Category. It's used to define whether to use or not the 
descriptive string while calling the function getAnnotations with a $category argument.  
*	**Field 6**. Annotation String. All the strings in lines where the 
condition is satisfied are added to the analysis description in order of 
top to bottom. Can be filtered with Rule Category and Annotation Use.
*	**Field 7**. Annotation Use. Defines whether to use this string while 
building the analysis description.

