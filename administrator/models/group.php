<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjfield
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  2016  Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Methods supporting a list of Tjfields records.
 *
 * @since  1.6
 *
 */

class TjfieldsModelGroup extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJFIELDS';

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'Group', $prefix = 'TjfieldsTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional ordering field.
	 * @param   boolean  $loadData  An optional direction (asc|desc).
	 *
	 * @return  JForm    $form      A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjfields.group', 'group', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_tjfields.edit.group.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  $item  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable  $table  A JTable object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tjfields_groups');
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $post  The form data.
	 *
	 * @return   mixed		The user id on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function save($post)
	{
		$table = $this->getTable();
		$data = $post->get('jform', '', 'ARRAY');
		$input = JFactory::getApplication()->input;

		// Set group title as group label
		if (!empty($data['name']))
		{
			$data['title'] = $data['name'];
		}

		if ($input->get('task') == 'save2copy')
		{
			unset($data['id']);
			$name = explode("(", $data['name']);
			$name = trim($name['0']);
			$name = str_replace("`", "", $name);
			$db = JFactory::getDbo();
			$query = 'SELECT a.*'
			. ' FROM #__tjfields_groups AS a'
			. " WHERE  a.name LIKE '" . $db->escape($name) . "%'"
			. " AND  a.client LIKE '" . $db->escape($data['client']) . "'";
			$db->setQuery($query);
			$posts = $db->loadAssocList();
			$postsCount = count($posts) + 1;
			$data['name'] = $name . ' (' . $postsCount . ')';
			$data['created_by'] = JFactory::getUser()->id;
		}

		if ($table->save($data) === true)
		{
			$id = $table->id;

			return $id;
		}
		else
		{
			return false;
		}
	}
}
