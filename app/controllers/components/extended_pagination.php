<?php
/**
* 
* Current Usage:
* 
* $this->ExtPagination->count(
* 	'Shout', $this->Profile->Shout->find('count', $this->ExtPagination->filter('Shout'))
* );
* $list = $this->ExtPagination->paginate(
* 	'Shout', $this->Profile->Shout->find('all', $this->ExtPagination->filter('Shout'))
* );
* 
* 
* 
* See also: http://cakephp.lighthouseapp.com/projects/42648/tickets/102-support-for-multiple-pagination
* 
* Example pagination request: 
* http://www.domain.tld/profiles/view/4/paginate.shouts.sort:shout.create,desc;shout.hidden,asc/paginate.shouts.page:2/paginate.shouts.conditions:shout.date,2009-12-12%2012:12:00;shout.from_profile_id,5/paginate.buddies.page:3
* 
* ExtendedPaginationComponent::_buildPaginationOptions() result:
* Array
* (
*     [Shout] => Array
*         (
*             [sort] => Array
*                 (
*                     [Shout.create] => desc
*                     [Shout.hidden] => asc
*                 )
* 
*             [page] => 2
*             [conditions] => Array
*                 (
*                     [Shout.date] => 2009-12-12 12:12:00
*                     [Shout.from_profile_id] => 5
*                 )
* 
*         )
* 
*     [Buddy] => Array
*         (
*             [page] => 3
*         )
* 
* )
* 
*/
class ExtPaginationComponent extends Object {
	
	/**
	* Stores pagination information converted from passedArgs
	* 
	* @access public
	*/
	var $options = array();
	
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
	* @access public
	*/
	var $C;
	
	/**
	* Holds numbers of items to paginate
	* 
	* @access private
	*/
	var $_count = array();

	/**
	* Holds the paginations limits
	* 
	* @access private
	*/
	var $_limit = array();
	
	/**
	* Holds the pagiations offsets
	* 
	* @access private
	*/
	var $_offset = array();
	
	/**
	 * Initialize component
	 * Called before Controller::beforeFilter()
	 * 
	 * @param object $Controller Instantiating controller
	 * @param array $settings Component configuration
	 * @access public
	 */
	function initialize(&$Controller, $settings = array()) {
		$this->C =& $Controller;
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
		$this->options = $this->_getPassedArgsOptions($Controller->passedArgs);
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
	
	// http://www.sna.dev/profiles/view/4/paginate.shouts.sort:shout.created,desc;user.is_hidden,asc/paginate.shouts.page:2/paginate.shouts.conditions:shout.is_hidden,0/paginate.buddies.page:3
	function count($modelname, $findResult, $pageSize = null) {
		if ($pageSize == null) {
			$pageSize = $this->_settings['pageSize'];
		}
		$offset = '0';
		if (isset($this->options[$modelname]['page'])) {
			$offset = ($this->options[$modelname]['page'] - 1) * $pageSize;
		}
		$this->_offset[$modelname] = $offset;
		$this->_limit[$modelname] = $pageSize;
		$this->_count[$modelname] = ceil($findResult / $pageSize);
	}
	
	function paginate($modelname, $findResult) {
		if (!isset($this->_count[$modelname])) {
			trigger_error(
				sprintf('ExtPagination::count() must be called before ExtPagination::paginate()'),
				E_USER_WARNING);
			return false;
		} else {
			debug($findResult);
			unset($this->_offset[$modelname]);
			unset($this->_limit[$modelname]);
			unset($this->_count[$modelname]);
		}
		// TODO Setup Helper here
	}
	
	/**
	* Returns options for a given pagination alias, to be used with Model->find()
	* @param string Modelname
	* @param array Additional find options
	* @return array Model::find() options
	* @access public
	*/
	function filter($modelname, $options = array()) {
		// Collect touched modelfields
		$modelfields = array();
		if (isset($this->options[$modelname])) {
			if (isset($this->options[$modelname]['conditions'])) {
				foreach($this->options[$modelname]['conditions'] as $modelfield => $value) {
					list($currentModelname, $fieldname) = explode('.', $modelfield);
					$modelfields[$currentModelname][] = $fieldname;
				}
			}
			if (isset($this->options[$modelname]['sort'])) {
				foreach ($this->options[$modelname]['sort'] as $modelfield => $value) {
					list ($currentModelname, $fieldname) = explode('.', $modelfield);
					$modelfields[$currentModelname][] = $fieldname;
				}
			}
		}
		// Check if the models themselves and the modelfields exists
		foreach (array_keys($modelfields) as $currentModelname) {
			if (class_exists($currentModelname)) {
				$currentModel = ClassRegistry::init($currentModelname);
				// TODO: Check if the models exist in the find call context (assoc, containable, join)
				// else: huge SQL errors appear
				// http://www.domain.dev/profiles/view/4/paginate.shouts.sort:shout.created,desc;user.id,asc/paginate.shouts.page:2/paginate.shouts.conditions:shout.is_hidden,0;shout.from_profile_id,5/paginate.buddies.page:3
				// Also see current Controller::paginate()
				foreach ($modelfields[$currentModelname] as $index => $fieldname) {
					if (!isset($currentModel->_schema[$fieldname])) {
						unset($modelfields[$currentModelname][$index]);
						unset($this->options[$modelname]['sort'][$currentModelname . '.' . $fieldname]);
					}
				}
			} else {
				unset($modelfields[$currentModelname]);
				unset($this->options[$modelname]['sort'][$currentModelname . '.' . $fieldname]);
			}
		}
		
		// Merge additional conditions
		$paginateOptions = array();
		if (isset($this->options[$modelname]['conditions'])) {
			$paginateOptions['conditions'] = $this->options[$modelname]['conditions'];
		}
		if (isset($this->options[$modelname]['sort'])) {
			// TODO stringify? 'Model.fieldname ASC' instead of array('Model.fieldname' => 'asc)!)
			$paginateOptions['order'] = $this->options[$modelname]['sort'];
		}
		// Get limit from count();
		// Still broken, noone knows why though.
		if (isset($this->_limit[$modelname])) {
			$paginateOptions['limit'] = $this->_limit[$modelname];
		}
		if (isset($this->_offset[$modelname])) {
			$paginateOptions['offset'] = $this->_offset[$modelname];
		}
		if (!empty($options)) {
			$options = array_merge_recursive($paginateOptions, $options);
		} else {
			$options = $paginateOptions;
		}
		return $options;
	}
	
	/**
	* Transforms pagination relevant Controller::passedArgs
	* @return array Pagination filter
	* @access private
	*/
	function _getPassedArgsOptions($passedArgs) {
		$paginationParams = array();
		foreach ($passedArgs as $key => $value) {
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
	* @access private
	*/
	function _buildParam($key, $value) {
		// Filter paginator param prefix
		$key = explode($this->_settings['prefix'], $key);
		// Build param
		if (!isset($key[1])) {
			return false;
		}
		$key = explode('.', $key[1]); // use list
		$modelname = Inflector::classify($key[0]);
		$filter = $key[1];
		// Save param
		$param = array($modelname => array($filter => $value));
		return $param;
	}
	
	/**
	* @param string $values Looks like 'shout.create,desc;shout.hidden,asc' or '2' (for page)
	* @return array pagination value
	* @access private
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
	
}
?>