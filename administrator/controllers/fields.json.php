<?php
/**
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

JLoader::import('filterFields', JPATH_SITE . '/components/com_tjfields');
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Item controller class.
 *
 * @since  1.4
 */
class TjfieldsControllerFields extends FormController
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
		JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
		$app = JFactory::getApplication();
		$jinput = $app->input;

		$data = array();

		// Here, fpht means file encoded path
		$data['filePath'] = base64_decode($jinput->get('filePath', '', 'BASE64'));
		$data['valueId'] = base64_decode($jinput->get('valueId', '', 'BASE64'));
		$data['subfromFileFieldId'] = $jinput->get('subfromFileFieldId');
		$data['isSubfromField'] = $jinput->get('isSubfromField');
		$data['client'] = $jinput->get('client', '', 'STRING');

		$client = explode('.', $data['client']);

		$file_extension = strtolower(substr(strrchr($data['fileName'], "."), 1));

		$mediaLocal = TJMediaStorageLocal::getInstance();

		$ctype = $mediaLocal->getMime($file_extension);

		$type = explode('/', $ctype);

		$data['storagePath'] = '/media/' . $client[0] . '/' . $client[1];
		require_once JPATH_ADMINISTRATOR . '/components/com_tjfields/helpers/tjfields.php';

		$tjFieldsHelper = new TjfieldsHelper;
		$returnValue = $tjFieldsHelper->deleteFile($data);
		$msg = $returnValue ? JText::_('COM_TJFIELDS_FILE_DELETE_SUCCESS') : JText::_('COM_TJFIELDS_FILE_DELETE_ERROR');

		echo new JResponseJson($returnValue, $msg);
	}
}
