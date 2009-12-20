<?php
/**
* $extPaginator->key('Shout');
* echo $extPaginator->prev(' ← Previous');
* echo ' ';
* echo $extPaginator->next('Next →');
*/
class ExtPaginatorHelper extends AppHelper {
	
	/**
	 * Helper dependencies
	 *
	 * @var array
	 */
	var $helpers = array('Html', 'Ajax');
	
	/**
	 * Holds the default model for paged recordsets
	 *
	 * @var string
	 */
	var $key = null;
	
	var $defaults = array(
		'prefix' => 'paginate.',
	);
	
	/**
	 * Holds the runtime info
	 *
	 * @var string
	 */
	var $setup = false;
	
	function key($key) {
		$this->key = $key;
	}
	
	function prev($title = null, $key = null, $options = array()) {
		if ($title == null) {
			$title == __('< Previous', true);
		}
		$link = $this->_pagingLink('prev', $title, $key);
		return $link;
	}
	
	function next($title = null, $key = null, $options = array()) {
		if ($title == null) {
			$title == __('Next >', true);
		}
		$link = $this->_pagingLink('next', $title, $key);
		return $link;
	}
	
	function _pagingLink($direction, $title, $key = null) {
		$this->_setup();
		if ($key == null) {
			$key = $this->key;
		} 
		if (isset($this->params['extPaging'])) {
			$paging = $this->params['extPaging'][$key];
			$route = Router::parse($this->here);
			$namedArgs = $route['named'];
			$completeKey = $this->defaults['prefix'] . Inflector::tableize($key) . '.';
			$disabled = false;
			// Replace given page number by next/prev page if possible
			if ($direction == 'next' and $paging['page'] < $paging['pageCount']) {
				$namedArgs = array_merge($namedArgs, array(
					 $completeKey . 'page' => ($paging['page'] + 1)
				));
				$route['named'] = $namedArgs;
			} else if ($direction == 'prev' and $paging['page'] != 1) {
				$namedArgs = array_merge($namedArgs, array(
					 $completeKey . 'page' => ($paging['page'] - 1)
				));
				$route['named'] = $namedArgs;
			} else {
				$disabled = true;
			}
		} else {
			return '';
		}
		if (!$disabled) {
			$url = $this->_urlByRoute($route);
			$link = $this->Html->link($title, $url);
		} else {
			$link = $title;
		}
		return $link;
	}
	
	function _urlByRoute($route) {
		$link = array(
			'controller' => $route['controller'],
			'action' => $route['action'],
			'plugin' => $route['plugin'],
		);
		$link = array_merge($link, $route['pass']);
		$link = array_merge($link, $route['named']);
		return $link;
	}
	
	function _setup() {
		if ($this->setup === false) {
			if ($this->key == null) {
				$this->key = $this->params['models'][0];
			}
		}
	}
	
}
?>
