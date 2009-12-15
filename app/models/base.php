<?php
/**
* Table'less basic model
* License: http://www.gnu.org/licenses/lgpl.html
* Copyright: Jonas Hartmann
* 
*/
class Base extends AppModel {
	
	var $name = 'Base';
	var $useTable = false;
	
	/**
	* Returns current time of the DBMS
	* 
	* From your controller context:
	* debug(ClassRegistry::init('Base')->dbTime());
	* 
	*/
	function dbTime() {
		$timeData = $this->query('SELECT NOW()');
		return $timeData[0][0]['NOW()'];
	}
	
}
?>