<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjfields
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjfields records.
 *
 * @since  1.0.0
 */
class TjfieldsModelFields extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'label', 'a.label',
				'name', 'a.name',
				'type', 'a.type',
				'state', 'a.state',
				'required', 'a.required',
				'placeholder', 'a.placeholder',
				'created_by', 'a.created_by',
				'min', 'a.min',
				'max', 'a.max',
				'description', 'a.description',
				'js_function', 'a.js_function',
				'validation_class', 'a.validation_class',
				'ordering', 'a.ordering',
				'client', 'a.client',
				'group_id', 'g.group_id',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   int  $ordering   course_id
	 * @param   int  $direction  course_id
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Filtering field_type
		$this->setState('filter.type', $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_field_type',  '', 'string'));

		// Filtering field groups
		$this->setState('filter.group', $app->getUserStateFromRequest($this->context . 'filter.group', 'filter_group',  '', 'string'));

		// Load the parameters.
		$params = JComponentHelper::getParams('com_tjfields');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.type', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return	string		A store id.
	 *
	 * @since  1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$input = JFactory::getApplication()->input;
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select', 'a.*'
				)
		);

		$query->select('g.name as groupname');
		$query->from('`#__tjfields_fields` AS a');
		$query->leftjoin('`#__tjfields_groups` AS g ON g.id = a.group_id');

		// Join over the user field 'created_by'
		$query->select('created_by.name AS created_by');
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');
		$query->where('a.client="' . $input->get('client', '', 'STRING') . '"');

		// Filter by published state
		$published = $this->getState('filter.state');

		// Filter by field groups
		$group = $this->getState('filter.group');

		if (!empty($group))
		{
			$query->where('g.id = ' . $group);
		}

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.type LIKE ' . $search . ' )');
			}
		}

		// Filtering field_type
		$filter_field_type = $this->state->get("filter.type");

		if ($filter_field_type)
		{
			$query->where("a.type = '" . $db->escape($filter_field_type) . "'");
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get Items functions
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * To plublish and unpublish groups.
	 *
	 * @param   array    $items  A prefix for the store id.
	 * @param   integer  $state  A prefix for the store id.
	 *
	 * @return  void
	 */
	public function setItemState($items, $state)
	{
		$db = JFactory::getDBO();

		if (is_array($items))
		{
			foreach ($items as $id)
			{
				$db = JFactory::getDBO();
				$query = "UPDATE  #__tjfields_fields SET state = $state where id=" . $id;
				$db->setQuery($query);

				if (!$db->execute())
				{
						$this->setError($this->_db->getErrorMsg());

						return false;
				}
			}
		}

		// Clean cache
		return true;
	}

	/**
	 * To plublish and unpublish groups.
	 *
	 * @param   integer  $id  A prefix for the store id.
	 *
	 * @return  void
	 */
	public function deletefield($id)
	{
		if (count($id) > 1)
		{
			$group_to_delet = implode(',', $id);
			$db = JFactory::getDBO();
			$query = "DELETE FROM #__tjfields_fields where id IN (" . $group_to_delet . ")";
			$db->setQuery($query);

			if (!$db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}

			// Delete the fields value
			$query = "DELETE FROM #__tjfields_fields_value where field_id IN (" . $group_to_delet . ")";
			$db->setQuery($query);

			if (!$db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}

			// Delete the fields option value
			$query = "DELETE FROM #__tjfields_options where field_id IN (" . $group_to_delet . ")";
			$db->setQuery($query);

			if (!$db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}
		}
		else
		{
				$db = JFactory::getDBO();
				$query = "DELETE FROM #__tjfields_fields where id =" . $id[0];
				$db->setQuery($query);

				if (!$db->execute())
				{
						$this->setError($this->_db->getErrorMsg());

						return false;
				}

				// Delete the fields value
				$query = "DELETE FROM #__tjfields_fields_value where field_id =" . $id[0];
				$db->setQuery($query);

				if (!$db->execute())
				{
						$this->setError($this->_db->getErrorMsg());

						return false;
				}

				// Delete the fields option value
				$query = "DELETE FROM #__tjfields_options where field_id =" . $id[0];
				$db->setQuery($query);

				if (!$db->execute())
				{
						$this->setError($this->_db->getErrorMsg());

						return false;
				}
		}

				return true;
	}

	/**
	 * Function getGroup for getting all groups available.
	 *
	 * @return   array  $options  The select list arry with all the groups
	 */
	public function getGroup()
	{
		$db      = JFactory::getDBO();
		$options = array();

		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("#__tjfields_groups");
		$db->setQuery($query);
		$result    = $db->loadObjectList();
		$options[] = JHTML::_('select.option', '', JText::_('COM_TJFIELDS_SELECT_FIELD_GROUP'));

		foreach ($result as $group)
		{
			$options[] = JHTML::_('select.option', $group->id, $group->name);
		}

		return $options;
	}
}
