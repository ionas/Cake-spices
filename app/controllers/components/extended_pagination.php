<?php
class ExtendedPaginationComponent extends Object {
	
	/**
	* Default component configuration
	* 
	* @access private
	*/
	var $_settings = array(
		'prefix' => 'paginate.',
		'pageSize' => 10, // default number of items per page
	);
	
	/**
	* Reference to the parent controller object
	* 
	* @access private
	*/
	var $_Controller;
	
	/**
	 * Initialize component
	 * Called before Controller::beforeFilter()
	 * 
	 * @param object $Controller Instantiating controller
	 * @param array $settings Component configuration
	 * @access public
	 */
	function initialize(&$Controller, $settings = array()) {
		$this->_Controller =& $Controller;
		$this->_settings = array_merge($this->_settings, $settings);
	}
	
	/**
	 * Startup component
	 * Called after Controller::beforeFilter()
	 * 
	 * @param object $Controller Instantiating controller
	 * @access public
	 */
	function startup(&$Controller) {
		$paginationConditions = $this->_buildPaginationConditions();
		$this->log($paginationConditions, 'debug');
	}
	
	/**
	* Transforms pagination relevant Controller::passedArgs
	* @return array Pagination conditions
	*/
	function _buildPaginationConditions() {
		$params = $this->_Controller->passedArgs;
		$paginationParams = array();
		foreach ($params as $key => $value) {
			// If pagination relevant parameter
			if (strpos($key, $this->_settings['prefix']) === 0) {
				// Merge parameter into pagination conditions
				$paginationParams = array_merge_recursive($paginationParams, 
					$this->_buildParam($key, $this->_buildValues($value)));
			}
		}
		return $paginationParams;
	}
	
	/**
	* @param string $key Looks like 'paginate.shouts.sort'
	* @param mixed $value Either a single value or an array of key->values
	* @return array pagination parameter
	*/
	function _buildParam($key, $value) {
		// Filter prefix
		$key = explode($this->_settings['prefix'], $key);
		if (!isset($key[1])) {
			return false;
		}
		// Build param
		$key = $key[1];
		$key = explode('.', $key);
		if (!isset($key[1])) {
			return false;
		}
		// Save param
		$key[0] = Inflector::classify($key[0]);
		$param = array($key[0] => array($key[1] => $value));
		return $param;
	}
	
	/**
	* @param string $values Looks like 'shout.create,desc;shout.hidden,asc' or '2' (for page)
	* @return array pagination value
	*/
	function _buildValues($values) {
		// Split multiple values
		$values = explode(';', $values);
		foreach ($values as $key => $value) {
			// If key holds key,value (like pagination.comments.sort:comment.date,asc)
			if (strpos($value, ',') !== false) {
				$value = explode(',', $value);
				$value[0] = ucfirst(strtolower($value[0])); // 'Modelalias.fieldname'
				unset($values[$key]);
				$values[$value[0]] = $value[1];
			} else { // If key just holds a value (like pagination.comments.page:2)
				$values = $value;
			}
		}
		return $values;
	}
	
	/**
	* BeforeRender callback
	* Called after Controller::beforeRender()
	* 
	* @param object $Controller Instantiating controller
	* @access public
	*/
	function beforeRender(&$Controller) {
		
	}
	
	/**
	* BeforeRedirect callback
	* Called before Controller::redirect()
	* 
	* @param object $Controller Instantiating controller
	* @param mixed $url Url of type string or array
	* @param integer $status Http status
	* @param boolean $exit Stops script on redirect
	* @access public
	*/
	function beforeRedirect(&$Controller, $url, $status = null, $exit = true) {
		
	}
	
	/**
	* Component Shutdown
	* Called after Controller::render()
	* 
	* @param object $Controller Instantiating controller
	* @access public
	*/
	function shutdown(&$Controller) {
		
	}
}
?>