<?php

namespace Plainmark;

class App {

	/**
	* constructor
	*
	* The class is instantiated by the Plainmark class, so you doesn't
	* create an App instance by yourself.
	*
	* @param mixed $data the string (or the json object) representation
	* of the application details data
	*/
	function __construct($data) {
		if (is_object($data))
			$this->appData = json_decode(json_encode($data), true);
		else
			$this->appData = json_decode($data, true);
	}

	/**
	* getPersonalDataLeaks
	*
	* Retrieves IP addresses where personal data leaked.
	*
	* @param string $type type of personal data (see the API documentation,
	* personal_data->data_type) for possible values.
	*
	* @return array list of IP addresses
	*/
	function getPersonalDataLeaks($type) {
		foreach ($this->appData['personal_data'] as $item)
			if ($item['data_type'] == $type)
				return $item['destination'];
		return array();
	}

	/* for internal use */

	function getPersonalDataPolicy($type) {
		foreach ($this->appData['personal_data'] as $item)
			if ($item['data_type'] == $type)
				return $item['policy'];
		return null;
	}

	function getPersonalDataPolicyCompliance($type) {
		foreach ($this->appData['personal_data'] as $item)
			if ($item['data_type'] == $type)
				return $item['policy_compliance'];
		return null;
	}

	function usesPersonalData($arg) {
		if ($arg && !empty($arg)) {
			foreach ($this->appData['personal_data'] as $item)
				if ($item['data_type'] == $arg)
					return 1;
			return 0;
		} else
			return (count($this->appData['personal_data']) == 0) ? 0 : 1;
	}

	function getSDKUseType($arg) {
		$args = explode(',', $arg);
		foreach ($this->appData[$args[0]]['sdk'] as $item)
			if ($item['use_type'] == $args[1])
				return 1;
		return 0;
	}

	function isPermissionRequired($arg) {
		foreach ($this->appData['permissions'] as $item)
			if (!$item['required'])
				return 0;
		return 1;
	}

	function isPersonalDataRequired($arg) {
		foreach ($this->appData['personal_data'] as $item)
			if ($item['data_type'] == $arg)
				return $item['legitimacy'];
		return null;
	}

	function notifiedAboutSDK($arg) {
		if ($this->usesSDK($arg) == 0)
			return -1;
		return ($this->appData[$arg]['notification']) ? 1 : 0;
	}

	function usesSDK($arg) {
		return (count($this->appData[$arg]['sdk']) == 0) ? 0 : 1;
	}

	function getAllSDKUsed() {
		$output = array();
		$sdks = array_merge(
			$this->appData['advertising']['sdk'],
			$this->appData['payments']['sdk'],
			$this->appData['analytics']['sdk'],
			$this->appData['social']['sdk'],
			$this->appData['cloud']['sdk']
		);
		foreach ($sdks as $item)
			$output[] = $item['name'];
		return array_unique($output);
	}

	private $appData;
}
