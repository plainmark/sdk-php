<?php

namespace Plainmark;

require_once 'app.php';

class Exception extends \Exception {
}

class Plainmark {

	/**
	* constructor
	*
	* Initializes the library
	*
	* @param string $login Plainmark account login
	* @param string $password Plainmark account password
	*/
	function __construct($login, $password) {
		$this->credentials = base64_encode("{$login}:{$password}");
	}
	
	/**
	* submit
	*
	* Sends an application for analysis to the Plainmark analytical engine.
	* This method implements the Plainmark API "Sending an application for
	* analysis" call (see API documentation for details)
	*
	* @param string $lang Language code of the fields, i.e. "en-US"
	* @param string $title Application title
	* @param string $description Application description
	* @param string $publisher Publisher name
	* @param string $apk Application package (APK) filename
	* @param string $category Application category
	* @param string $callback URL on the client side to be called after the
	*                         package processing is completed
	*
	* @return string Application id
	*/
	function submit($lang, $title, $description, $publisher, $apk,
			$category = null, $callback = null) {

		$args = array(
			'lang' => $lang,
			'title' => $title,
			'description' => $description,
			'publisher' => $publisher
		);

		if ($category)
			$args['category'] = $category;
		if ($callback)
			$args['callback'] = $callback;

		$file = file_get_contents($apk);
		if (!$file)
			throw new Exception("Can't read file {$apk}");

		$divider = '--------------------3jd3ft5r4pEREGEhewwue5jyhtq3t23y4y3t3';
		$encoded_data = $this->multipart_build_query($args, array(
			array(
				'name' => 'apk',
				'filename' => basename($apk),
				'content_type' => 'application/vnd.android.package-archive',
				'content' => $file
			)
		), $divider);

		$context  = stream_context_create(array('http' => array(
			'method' => 'POST',
			'header'=>
				"Authorization: Basic {$this->credentials}\r\n" .
				"Content-Type: multipart/form-data; boundary={$divider}\r\n",
			'content' => $encoded_data
		)));

		$json = file_get_contents($this->host . '/app', false, $context);
		if (!$json)
			throw new Exception("File submitting failed");

		$obj = json_decode($json);
		return $obj->id;

	}


	/**
	* submitAppDF
	*
	* Sends an application for analysis to the Plainmark analytical engine.
	* This method implements the Plainmark API "Sending an application for
	* analysis" call (see API documentation for details)
	*
	* @param string $publisher Publisher name
	* @param string $appdf Application Description File (AppDF) filename
	* @param string $callback URL on the client side to be called after the
	*                         package processing is completed
	*
	* @return string Application id
	*/
	function submitAppDF($publisher, $appdf, $callback = null) {

		$args = array(
			'publisher' => $publisher
		);
		if ($callback)
			$args['callback'] = $callback;

		$file = file_get_contents($appdf);
		if (!$file)
			throw new Exception("Can't read file {$appdf}");

		$divider = '--------------------3jd3ft5r4pEREGEhewwue5jyhtq3t23y4y3t3';
		$encoded_data = $this->multipart_build_query($args, array(
			array(
				'name' => 'appdf',
				'filename' => basename($appdf),
				'content_type' => 'application/zip',
				'content' => $file
			)
		), $divider);

		$context  = stream_context_create(array('http' => array(
			'method' => 'POST',
			'header'=>
				"Authorization: Basic {$this->credentials}\r\n" .
				"Content-Type: multipart/form-data; boundary={$divider}\r\n",
			'content' => $encoded_data
		)));

		$json = file_get_contents($this->host . '/app/appdf', false, $context);
		if (!$json)
			throw new Exception("File submitting failed");

		$obj = json_decode($json);
		return $obj->id;

	}
	
	/**
	* find
	*
	* Searches for an app in the Plainmark analytical engine. This method
	* implements the Plainmark API "Searching the application" call (see the
	* API documentation for details).
	*
	* @param string $package Package name
	*
	* @return string The first application id found
	*/
	function find($package) {

		$context  = stream_context_create(array('http' => array(
			'method' => 'GET',
			'header'=> "Authorization: Basic {$this->credentials}\r\n"
		)));

		$search_params = http_build_query(array('package_name' => $package));

		$json = file_get_contents($this->host . "/app?{$search_params}", false, $context);
		if (!$json)
			throw new Exception("The application is not found");

		$obj = json_decode($json);
		if (count($obj) == 0)
			throw new Exception("The application {$package} is not found");
		return $obj[0]->id;

	}

	/**
	* getApp
	* 
	* Retrieves app details from the Plainmark analytical engine. This method
	* implements the Plainmark API "Application details requesting" call (see the
	* API documentation for details).
	*
	* @param string $id Application id
	*
	* @return App Application analysis details
	*/
	function getApp($id) {

		$context  = stream_context_create(array('http' => array(
			'method' => 'GET',
			'header'=> "Authorization: Basic {$this->credentials}\r\n"
		)));

		$json = file_get_contents($this->host . "/app/{$id}", false, $context);
		if (!$json)
			throw new Exception("Can't retrieve application details #{$id}");
		return new App($json);

	}

	/**
	* getScore
	*
	* Calculates the score (i.e. quality) for an application.
	*
	* @param App $app Application analysis details
	*
	* @return array the associative array with the following keys:
	*     score Application score
	*     max_score Maximum score that can be achieved
	*     stars Application quality based on five-point rating
	*/
	function getScore(App $app) {

		$score = 0;
		$config = $this->getConfig();
		reset($config);

		foreach($config as $item) {
			if ($app->$item[Plainmark::COLUMN_FUNCTION]($item[Plainmark::COLUMN_ARGS]) == $item[Plainmark::COLUMN_VALUE]) {
				if (defined('DEBUG'))
					echo "{$item[Plainmark::COLUMN_FUNCTION]}({$item[Plainmark::COLUMN_ARGS]}) = {$item[Plainmark::COLUMN_VALUE]} ? {$item[Plainmark::COLUMN_SCORE]}\n";
				$score += (int)$item[Plainmark::COLUMN_SCORE];
			}
		}

		if ($score < 0)
			$score = 0;

		//$stars = (int) round($score / ($this->totalScore / 2));
		$stars = (int) round($score * 5 / $this->totalScore);
		if ($stars < 1)
			$stars = 1;

		return array(
			'score' => $score,
			'max_score' => $this->totalScore,
			'stars' => $stars
		);

	}
	
	/**
	* getAnnotation
	*
	* Builds text annotation (list of features) for an application
	*
	* @param App $app Application analysis details
	* @param string $category Filter by category
	* @param string $grouping Group annotations by type and return them as key-value array
	*
	* @return array List of features (human readable strings)
	*/
	function getAnnotations(App $app, $category = null, $grouping = false) {

		$annotations = array();
		$config = $this->getConfig();
		reset($config);

		foreach($config as $item) {
			if ($app->$item[Plainmark::COLUMN_FUNCTION]($item[Plainmark::COLUMN_ARGS]) == $item[Plainmark::COLUMN_VALUE] &&
					$item[Plainmark::COLUMN_USE] == 'TRUE' &&
					(!$category || $item[Plainmark::COLUMN_CATEGORY] == $category))
				if ($grouping)
					$annotations[$item[Plainmark::COLUMN_ARGS]][] = $item[Plainmark::COLUMN_TEXT];
				else
					$annotations[] = $item[Plainmark::COLUMN_TEXT];
		}

		return $annotations;

	}

	/**
	* getSDKDetails
	*
	* Builds a list of all SDKs used by an application
	*
	* @param App $app Application analysis details
	*
	* @return array List of SDKs. Each element contains an associative
	* array representing the SDK (see "Retrieving library details" in the API
	* documentation)
	*/
	function getSDKDetails(App $app) {

		$sdk_names = $app->getAllSDKUsed();

		$context  = stream_context_create(array('http' => array(
			'method' => 'GET',
			'header'=> "Authorization: Basic {$this->credentials}\r\n"
		)));

		$json = file_get_contents($this->host . '/lib', false, $context);
		if (!$json)
			throw new Exception("Can't retrieve library descriptions");

		$output = array();

		$libs = json_decode($json, true);
		foreach ($libs as $item) {
			if (in_array($item['name'], $sdk_names))
				$output[] = $item;
		}
		
		return $output;

	}

	/* private members */

	private function getConfig() {

		if ($this->_config)
			return $this->_config;

		$filename = __DIR__ . '/config.csv';
		$in = fopen($filename, 'r');
		if (!$in)
			throw new Exception("Can't open file {$filename}");

		while ($line = fgetcsv($in, null, "\t", '"')) {
			if (strlen($line[0]) > 0) {
				$this->_config[] = $line;
				$score = (int)$line[Plainmark::COLUMN_SCORE];
				if (($line[Plainmark::COLUMN_FUNCTION] == 'usesPersonalData' || $line[Plainmark::COLUMN_FUNCTION] == 'usesSDK') &&
					($line[Plainmark::COLUMN_VALUE] == '0'))
						$this->totalScore += $score;
			}
		}

		fclose($in);

		return $this->_config;

	}
	
	private function multipart_build_query($fields, $files = array(), $boundary = 'divider') {

		$retval = '';

		foreach ($fields as $key => $value) {
			$retval .= "--{$boundary}\r\n";
			$retval .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
			$retval .= "{$value}\r\n";
		}

		foreach ($files as $file) {
			$retval .= "--{$boundary}\r\n";
			$retval .= "Content-Disposition: form-data; name=\"{$file['name']}\"; filename=\"{$file['filename']}\"\r\n";
			$retval .= "Content-Type: {$file['content_type']}\r\n\r\n";
			$retval .= $file['content'] . "\r\n";
		}

		$retval .= "--{$boundary}--\r\n";

		return $retval;

	}

	public $host = 'http://v4.plainmark.com';
	private $credentials;
	private $_config;
	private $totalScore = 0;

	const COLUMN_FUNCTION = 0;
	const COLUMN_ARGS = 1;
	const COLUMN_VALUE = 2;
	const COLUMN_SCORE = 3;
	const COLUMN_CATEGORY = 4;
	const COLUMN_TEXT = 5;
	const COLUMN_USE = 6;

}

