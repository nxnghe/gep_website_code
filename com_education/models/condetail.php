<?php
/**
 * @version		01
 * @package		Gep.Site
 * @subpackage	com_education
 * @author Nguyen Xuan Nghe - nxnghe@gmail.com
 * @copyright	Copyright (C) 2013 Prime Labo Technology. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');
jimport('joomla.application.categories');

/**
 * Joomprosubs Component Joomprosub Model
 *
 * @package		Joomla.Site
 * @subpackage	com_joomprosubs
 */
class EducationModelCondetail extends JModelList
{
	/**
	 * Category items data
	 *
	 * @var array
	 */
	protected $_item = null;

	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title', 'a.title',
				'g.title', 'group_title',
				'duration', 'a.duration'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		$user = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select required fields from the categories.
		$query->select($this->getState('list.select', 'a.*'));
		$query->select('g.title as group_title');
		$query->from($db->quoteName('#__joompro_subscriptions').' AS a');

		// Join on groups to get title of group
		$query->join('LEFT', $db->quoteName('#__usergroups').' AS g ON a.group_id = g.id');
		$query->where('a.access IN ('.$groups.')');

		// Filter by category.
		if ($categoryId = $this->getState('category.id')) {
			$query->where('a.catid = '.(int) $categoryId);
			$query->join('LEFT', $db->quoteName('#__categories').' AS c ON c.id = a.catid');
			$query->where('c.access IN ('.$groups.')');

			//Filter by published category
			$cpublished = $this->getState('filter.c.published');
			if (is_numeric($cpublished)) {
				$query->where('c.published = '.(int) $cpublished);
			}
		}

		// Filter by state
		$state = $this->getState('filter.state');
		if (is_numeric($state)) {
			$query->where('a.published = '.(int) $state);
		}

		// Filter by search
		if ($this->getState('list.filter') != '') {
			$filter = JString::strtolower($this->getState('list.filter'));
			$filter = $db->quote('%'.$filter.'%', true);
			$query->where('a.title LIKE ' . $filter);
		}

		// Filter by start and end dates.
		$nullDate = $db->quote($db->getNullDate());
		$nowDate = $db->quote(JFactory::getDate()->toMySQL());

		if ($this->getState('filter.publish_date')){
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}

		// Add the list ordering clause.
		$query->order($db->getEscaped($this->getState('list.ordering', 'a.title')).
			' '.$db->getEscaped($this->getState('list.direction', 'ASC')));

		var_dump($query);exit;
		return $query;
	}
	function getcandidates($rc) {
		 $mainframe  = JFactory::getApplication();
		 $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $rc, 'int');
		 $limitstart = JRequest::getVar('limitstart', 0, '', 'int');

		 // In case limit has been changed, adjust it
		 $limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		 $this->setState('limit', $limit);
		 $this->setState('limitstart', $limitstart);

         $db     = JFactory::getDBO();
         $query  =  "SELECT * FROM #__candidatessimple WHERE published=1";

         $db->setQuery( $query, $limitstart, $limit );
         $output = $db->loadObjectList();
         return($output);
	}
	function getDetail($id) {

         $db     = JFactory::getDBO();
         $query  =  "SELECT * FROM #__students WHERE published= 1 AND id =$id";
         $db->setQuery($query);
         $output = $db->loadObjectList();
         return($output);
	}
	function getTeacherProfile($id) {

         $db     = JFactory::getDBO();
         $query  =  "SELECT * FROM #__teachers WHERE published= 1 AND id =$id";
         $db->setQuery($query);
         $output = $db->loadObjectList();
         return($output);
	}
	function updateTeacherView($userId){
		$db =& JFactory::getDBO();

			//echo "i am here";

			//echo $curYear;exit;
			$view = $this->checkupdateTeacherView($userId) + 1;

			if( $view > 1 ){
				 $query = 'UPDATE #__teachers SET'
	            	. '`view`='.$db->Quote($view)
	            	. ' WHERE `id` ='.$db->Quote($userId)
	            	;
			}else{
            //@todo escape code
	         $query = 'UPDATE #__teachers SET'
	            	. ' `view`= 1'
	            	. ' WHERE `id` ='.$db->Quote($userId)
	            	;
	        }
	       // echo $query;
            $db->setQuery($query);
            $db->query();
            if ($db->getErrorNum())
            {
                JError::raiseError(500, $db->stderr());
            }
	}
	function checkupdateTeacherView($userId){
		$db	=& JFactory::getDBO();

			$query = 'SELECT view'
			. ' FROM '.$db->nameQuote( '#__teachers' )
			. ' WHERE id = '.$userId;
			$db->setQuery( $query);
			$row = $db->loadObject();
			$view = $row->view;
			//echo $id;exit;
			return (int)$view;

	}

	function updateView($userId){
		$db =& JFactory::getDBO();

			//echo "i am here";

			//echo $curYear;exit;
			$view = $this->checkupdateCanView($userId) + 1;

			if( $view > 1 ){
				 $query = 'UPDATE #__students SET'
	            	. '`view`='.$db->Quote($view)
	            	. ' WHERE `id` ='.$db->Quote($userId)
	            	;
			}else{
            //@todo escape code
	         $query = 'UPDATE #__students SET'
	            	. ' `view`= 1'
	            	. ' WHERE `id` ='.$db->Quote($userId)
	            	;
	        }
	       // echo $query;
            $db->setQuery($query);
            $db->query();
            if ($db->getErrorNum())
            {
                JError::raiseError(500, $db->stderr());
            }
	}
	function checkupdateCanView($userId){
		$db	=& JFactory::getDBO();

			$query = 'SELECT view'
			. ' FROM '.$db->nameQuote( '#__students' )
			. ' WHERE id = '.$userId;
			$db->setQuery( $query);
			$row = $db->loadObject();
			$view = $row->view;
			//echo $id;exit;
			return (int)$view;

	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();
		$params	= JComponentHelper::getParams('com_joomprosubs');

		// List state information
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$limitstart = JRequest::getInt('limitstart', 0, '');
		$this->setState('list.start', $limitstart);

		$orderCol	= JRequest::getCmd('filter_order', 'title');
		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'ordering';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder	=  JRequest::getCmd('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$this->setState('list.filter', JRequest::getString('filter-search'));

		$id = JRequest::getInt('id', 0);
		$this->setState('category.id', $id);

		$user = JFactory::getUser();
		if ((!$user->authorise('core.edit.state', 'com_joomprosubs')) &&  (!$user->authorise('core.edit', 'com_joomprosubs'))){
			// limit to published for people who can't edit or edit.state.
			$this->setState('filter.state',	1);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}
	}

	/**
	 * Method to get category data for the current category
	 *
	 * @param	int		An optional ID
	 *
	 * @return	object
	 */
	public function getCategory()
	{
		//echo "I am h";
		if(!is_object($this->_item))
		{
			$categories = JCategories::getInstance('Joomprosubs');
			$this->_item = $categories->get($this->getState('category.id', 'root'));
		}

		return $this->_item;
	}
} // end of class