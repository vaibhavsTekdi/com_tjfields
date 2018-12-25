<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

require_once JPATH_SITE . "/components/com_tjfields/filterFields.php";

/**
 * Item controller class.
 *
 * @since  1.4
 */
class TjfieldsControllerFields extends JControllerForm
{
	/**
	 * Delete File .
	 *
	 * @return boolean|string
	 *
	 * @since	1.6
	 */

	public function deleteFile()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app = JFactory::getApplication();
		$jinput = $app->input;
		$fieldData = new stdClass;

		$data = array();

		// Here, fpht means file encoded path
		$data['fileName'] = base64_decode($jinput->get('fileName', '', 'BASE64'));
		$data['valueId'] = base64_decode($jinput->get('valueId', '', 'BASE64'));
		$data['subformFileFieldId'] = $jinput->get('subformFileFieldId');
		$data['isSubformField'] = $jinput->get('isSubformField');

		// Get storage path to delete the file
		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldValuesTable = JTable::getInstance('fieldsvalue', 'TjfieldsTable');
		$tjFieldFieldValuesTable->load(array('id' => $data['valueId']));

		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldData->tjFieldFieldTable = JTable::getInstance('field', 'TjfieldsTable');

		if ($data['isSubformField'])
		{
			$fieldData->tjFieldFieldTable->load(array('id' => $data['subformFileFieldId']));
		}
		else
		{
			$fieldData->tjFieldFieldTable->load(array('id' => $tjFieldFieldValuesTable->field_id));
		}

		$tjFieldFieldTableParamData = json_decode($fieldData->tjFieldFieldTable->params);

		$data['storagePath'] = $tjFieldFieldTableParamData->uploadpath;
		$data['client'] = $fieldData->tjFieldFieldTable->client;

		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		$tjFieldsHelper = new TjfieldsHelper;
		$returnValue = $tjFieldsHelper->deleteFile($data);
		$msg = $returnValue ? JText::_('COM_TJFIELDS_FILE_DELETE_SUCCESS') : JText::_('COM_TJFIELDS_FILE_DELETE_ERROR');

		echo new JResponseJson($returnValue, $msg);
	}
}
