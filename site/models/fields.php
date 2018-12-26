<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Tjfields model.
 *
 * @since  2.2
 */
class TjfieldsModelFields extends JModelList
{
	/**
	 * Function used for getting the storage path of file field
	 *
	 * @param   integer  $fieldValueId        field value id
	 *
	 * @param   integer  $subformFileFieldId  subform file field id
	 *
	 * @return  array
	 */
	public function getMediaStoragePath($fieldValueId, $subformFileFieldId='0')
	{
		$fieldData = new stdClass;

		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldValuesTable = JTable::getInstance('fieldsvalue', 'TjfieldsTable');
		$tjFieldFieldValuesTable->load(array('id' => $fieldValueId));

		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldData->tjFieldFieldTable = JTable::getInstance('field', 'TjfieldsTable');

		if ($subformFileFieldId)
		{
			$fieldData->tjFieldFieldTable->load(array('id' => $subformFileFieldId));
		}
		else
		{
			$fieldData->tjFieldFieldTable->load(array('id' => $tjFieldFieldValuesTable->field_id));
		}

		$fieldData->tjFieldFieldTable->field_id = $tjFieldFieldValuesTable->field_id;
		$fieldData->tjFieldFieldTable->user_id = $tjFieldFieldValuesTable->user_id;
		$fieldData->tjFieldFieldTable->fieldValueId = $tjFieldFieldValuesTable->id;

		return $fieldData;
	}
}
