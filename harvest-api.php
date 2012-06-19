<?php 
/**
  * Simple class to connect to Harvest time tracking api and get data
  *
  * LICENSE: Apache 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
  *
  * Author: jesse jack <jesse@thesourcespring.com>
  * Copyright: 2009 Source Spring, LLC
**/

if (!defined('DEBUG')) define('DEBUG',FALSE);

require_once('http_status.php');

class Harvest {

	var $username = '';
	var $password = '';
	var $host = '';
	var $ua = '';
	
	var $error_code = 0;
	var $error_text = '';
	var $error_detail = '';
	
	var $response = null;
	
	var $sent = '';
	var $received = '';
	
	function Harvest ($username, $password, $host) {
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->ua = "PHP/".phpversion()." CURL/".implode('_',curl_version());
	}
	
	function get_people () {
		return $this->get_many('people');
	}

	function get_person ($id) {
		return $this->get_one('people', array((int)$id));
	}

	function get_clients () {
		return $this->get_many('clients');
	}

	function get_client ($id) {
		return $this->get_one('clients', array((int)$id));
	}

	function get_projects () {
		return $this->get_many('projects');
	}

	function get_project ($id) {
		return $this->get_one('projects', array((int)$id));
	}

	function get_tasks () {
		return $this->get_many('tasks');
	}

	function get_task ($id) {
		return $this->get_one('tasks', array((int)$id));
	}

	function get_person_entries ($person_id, $from, $to) {
		return $this->get_many('people',array($person_id,'entries?from='.date('Ymd',$from)."&to=".date('Ymd',$to-1)));	
	}
	
	function get_project_entries ($project_id, $from, $to) {
		return $this->get_many('projects',array($project_id,'entries?from='.date('Ymd',$from)."&to=".date('Ymd',$to-1)));	
	}
	
	function get_many ($thing, $params = array()) {
		$results = $this->transmit(array_merge(array($thing),$params));
		$things = array();
		foreach($results->children() as $node) {
			$things[(string)$node->id] = $node;
		}
		$this->unhyphen($things);
		return $things;
	}

	function get_one ($thing, $params = array()) {
		$result = $this->transmit(array_merge(array($thing),$params));
		$this->unhyphen($result);
		return $result;
	}
	
	function transmit ($params) {
		$url = "http://{$this->host}/".implode('/',$params);
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_USERAGENT, $this->ua);
		curl_setopt($curl, CURLOPT_HTTPGET, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "{$this->username}:{$this->password}"); 
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Accept: application/xml',
			'Content-Type: application/xml'
		));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl);

		$this->sent = '/'.implode('/', $params);
		$this->received = $response;

		if (DEBUG) {
			echo "DEBUG: Queried {$url}\n";
		}
		$info = curl_getinfo($curl);
		$this->error_code = $info['http_code'];
		
		if ($this->error_code>=200 && $this->error_code<300) {
			$this->error_text = '';
			$response = new SimpleXMLElement($response);
		} else {
			$this->error_text = http_status_to_text($this->error_code);
			$response = new SimpleXMLElement("<error id=\"{$this->error_code}\">{$this->error_text}</error>");
		}

		$this->response = $response;
		
		return $response;
	}

	function unhyphen (&$obj) { // can't access objects with hyphens in them, so translate those to underscores
		if (is_array($obj)) {
			foreach($obj as $k => $v) {
				$this->unhyphen($obj["".$k]);
				$newk = preg_replace('/-/i','_',$k);
				if ($newk != $k) {
					$obj["".$newk] = $obj[$k];
					unset($obj["".$k]);
				}
			}
		} else if (is_object($obj)) {
			$vars = get_object_vars($obj);
			foreach($vars as $k => $v ) {
				$this->unhyphen($obj->{$k});
				$newk = "".preg_replace('/-/i','_',$k);
				if ($newk != $k) {
					$obj->{$newk} = $obj->{$k};
					unset($obj->{$k});
				}
			}	
		} else {
			$obj = (string)$obj;
		}
	}
}

?>

