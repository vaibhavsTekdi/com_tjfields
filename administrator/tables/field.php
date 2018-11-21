<?php
/**
 * @package    Tjfields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Field Table class
 *
 * @since  1.1
 */
class TjfieldsTablefield extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjfields_fields', 'id', $db);
	}

	/**
	 * This function convert an array of JAccessRule objects into an rules array.
	 *
	 * @param   type  $jaccessrules  an arrao of JAccessRule objects.
	 *
	 * @return void
	 */
	private function JAccessRulestoArray($jaccessrules)
	{
		$rules = array();

		foreach ($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			foreach ($jaccess->getData() as $group => $allow)
			{
				$actions[$group] = (bool) $allow;
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return void
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if (property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		return parent::check();
	}

	/**
	 * Method to set the publishing state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.  If not
	 *                    set the instance property value is used.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return    boolean    True on success.
	 *
	 * @since    1.0.4
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		JArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array($this->$k);
			}
			else
			{
				// Nothing to set publishing state on, return false.
				$this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time'))
		{
			$checkin = '';
		}
		else
		{
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . (int) $userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery(
				'UPDATE `' . $this->_tbl . '`' .
				' SET `state` = ' . (int) $state .
				' WHERE (' . $where . ')' .
				$checkin
		);
		$this->_db->query();

		// Check for a database error.
		if ($this->_db->getErrorNum())
		{
			$this->setError($this->_db->getErrorMsg());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
		{
			// Checkin each row.
			foreach ($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->state = $state;
		}

		$this->setError('');

		return true;
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
	 * @return  void
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return 'com_tjfields.field.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   MIXED  $table  JTable object
	 * @param   MIXED  $id     id
	 *
	 * @return  integer
	 * 
	 * @since  1.1
	 */
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$assetId = null;

			if ($this->group_id)
			{
				JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
				$groupTable = JTable::getInstance('group','TjfieldsTable');
				$groupTable->load(array('id' => $this->group_id));
				$assetId = $groupTable->asset_id;
			}

		// Return the asset id if present else return the root
		if ($assetId)
		{
			return $assetId;
		}
		else
		{
			return parent::_getAssetParentId($table, $id);
		}
	}
}
