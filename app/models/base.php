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
		$dbTimeData = $this->query('SELECT NOW()');
		return $dbTimeData[0][0]['NOW()'];
	}
	
}
?>