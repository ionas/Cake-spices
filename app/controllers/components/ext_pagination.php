<?php
/**
* 
* Current Usage (In your Controller's Action):
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
* http://www.sna.dev/profiles/view/4/paginate.shouts.sort:shout.created,asc;user.is_hidden,asc/paginate.shouts.page:4/paginate.shouts.conditions:shout.is_hidden,0/paginate.buddies.page:3 
* ExtendedPaginationComponent::_passedArgsAsOptions() result:
* Array
* (
*     [Shout] => Array
*         (
*             [sort] => Array
*                 (
*                     [Shout.created] => asc
*                     [User.is_hidden] => asc
*                 )
* 
*             [page] => 4
*             [conditions] => Array
*                 (
*                     [Shout.is_hidden] => 0
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
	* Holds runtime count, limit, offset, pagesize
	* 
	* @access private
	*/
	var $_runtime = array();
	
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
		$this->options = $this->_passedArgsAsOptions($Controller->passedArgs);
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
	function count($paginateKey, $findResult, $pageSize = null) {
		if (!isset($this->options[$paginateKey]['page'])) {
			$this->options[$paginateKey]['page'] = 1;
		}
		if ($pageSize == null) {
			$pageSize = $this->_settings['pageSize'];
		}
		$offset = ($this->options[$paginateKey]['page'] - 1) * $pageSize;
		$this->_runtime[$paginateKey]['offset'] = $offset;
		$this->_runtime[$paginateKey]['limit'] = $pageSize;
		$this->_runtime[$paginateKey]['count'] = $findResult;
		$this->_runtime[$paginateKey]['pageCount'] = ceil($findResult / $pageSize);
	}
	
	function paginate($paginateKey, $findResult, $options = array()) {
		if (!isset($this->_runtime[$paginateKey])) {
			trigger_error(
				sprintf('ExtPagination::count() must be called before ExtPagination::paginate()'),
				E_USER_WARNING);
			return false;
		} else {
			$page = $this->options[$paginateKey]['page'];
			$count = $this->_runtime[$paginateKey]['count'];
			$limit = $this->_runtime[$paginateKey]['limit'];
			$pageCount = $this->_runtime[$paginateKey]['pageCount'];
			$paging = array(
				'page'		=> $page,
				'current'	=> count($findResult),
				'count'		=> $count,
				'prevPage'	=> ($page > 1),
				'nextPage'	=> ($count > ($page * $limit)),
				'pageCount'	=> $pageCount,
			);
			// Autoload ExtPaginatorHelper
			$this->C->params['extPaging'][$paginateKey] = $paging;
			if (!in_array('ExtPaginator', $this->C->helpers)
				&& !array_key_exists('ExtPaginator', $this->C->helpers)
			) {
				$this->C->helpers[] = 'ExtPaginator';
			}
			// Clean up component for next usage
			unset($this->_runtime[$paginateKey]);
			return $findResult;
		}
		// TODO Setup ExtPaginatingHelper here
	}
	
	/**
	* Returns options for a given pagination alias, to be used with Model->find()
	* @param string Modelname
	* @param array Additional find options
	* @return array Model::find() options
	* @access public
	*/
	function filter($paginateKey, $options = array(), $whiteList = array()) {
		// Collect touched modelfields
		$modelfields = array();
		if (isset($this->options[$paginateKey])) {
			if (isset($this->options[$paginateKey]['conditions'])) {
				foreach ($this->options[$paginateKey]['conditions'] as $modelfield => $value) {
					// TODO: treat $whiteLists
					list($currentModelname, $fieldname) = explode('.', $modelfield);
					$modelfields[$currentModelname][] = $fieldname;
				}
			}
			if (isset($this->options[$paginateKey]['sort'])) {
				foreach ($this->options[$paginateKey]['sort'] as $modelfield => $value) {
					list ($currentModelname, $fieldname) = explode('.', $modelfield);
					$modelfields[$currentModelname][] = $fieldname;
				}
			}
		}
		// Check if the models themselves and the modelfields exists. This is required because
		// the external user can specify the fields at free will
		foreach (array_keys($modelfields) as $currentModelname) {
			if (class_exists($currentModelname)) {
				$currentModel = ClassRegistry::init($currentModelname);
				// TODO: Check if the models exist in the find call context (assoc, containable, join)
					// Else: huge SQL errors!
					// Example: http://www.sna.dev/profiles/view/4/paginate.shouts.sort:shout.created,asc;shout.profile_id,desc;user.is_hidden,asc;user.id,asc/paginate.shouts.page:4/paginate.shouts.conditions:shout.is_hidden,0/paginate.buddies.page:3
					// Also see current Controller::paginate(
				
				// Check if the field exists in the given model
				foreach ($modelfields[$currentModelname] as $index => $fieldname) {
					if (!isset($currentModel->_schema[$fieldname])) {
						unset($this->options[$paginateKey]['sort'][$currentModelname . '.' . $fieldname]);
					}
				}
			} else {
				unset($this->options[$paginateKey]['sort'][$currentModelname . '.' . $fieldname]);
			}
		}
		
		// Merge additional conditions
		$paginateOptions = array();
		if (isset($this->options[$paginateKey]['conditions'])) {
			$paginateOptions['conditions'] = $this->options[$paginateKey]['conditions'];
		}
		if (isset($this->options[$paginateKey]['sort'])) {
			foreach ($this->options[$paginateKey]['sort'] as $key => $value) {
				$paginateOptions['order'][] = $key . ' ' . strtoupper($value);
			}
		}
		// Set limit, offset and alike (generated by ExtPagination::count())
		if (!empty($this->_runtime[$paginateKey])) {
			$paginateOptions['limit'] = $this->_runtime[$paginateKey]['limit'];
			$paginateOptions['offset'] = $this->_runtime[$paginateKey]['offset'];
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
	function _passedArgsAsOptions($passedArgs) {
		$paginationParams = array();
		foreach ($passedArgs as $key => $value) {
			// If pagination relevant parameter
			if (strpos($key, $this->_settings['prefix']) === 0) {
				// Merge parameter into pagination conditions
				$paginationParams = array_merge_recursive($paginationParams, 
					$this->_buildOption($key, $this->_buildOptionValues($value)));
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
	function _buildOption($key, $value) {
		// Filter all params for pagination passedArgs (for instance 'paginate.') prefix
		$key = explode($this->_settings['prefix'], $key);
		if (!isset($key[1])) {
			return null;
		}
		// Build param
		list($paginationKey, $filter) = explode('.', $key[1]);
		$param = array(Inflector::classify($paginationKey) => array($filter => $value));
		return $param;
	}
	
	/**
	* @param string $values Looks like 'shout.create,desc;shout.hidden,asc' or '2' (for page)
	* @return array pagination value
	* @access private
	*/
	function _buildOptionValues($values) {
		// Split multiple values
		$values = explode(';', $values);
		$options = null;
		foreach ($values as $key => $value) {
			// If if a value holds key+value, like 'pagination.comments.sort:comment.date,asc'
			if (strpos($value, ',') !== false) {
				list($subkey, $subvalue) = explode(',', $value);
				$subkey = ucfirst(strtolower($subkey)); // 'Modelalias.fieldname'
				$options[$subkey] = $subvalue;
			} else { // If key just holds a value (like pagination.comments.page:2)
				$options = $value;
			}
		}
		return $options;
	}
	
}
?>