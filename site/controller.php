<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/**
 * TJField Controller class
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsController extends JControllerLegacy
{
	/**
	 * The return URL.
	 *
	 * @var    mixed
	 * @since  1.4
	 */
	protected $returnURL;

	/**
	 * Constructor
	 *
	 * @since 1.4
	 */
	public function __construct()
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';
		$this->returnURL = JURI::root();

		parent::__construct();
	}

	/**
	 * Fuction to get download media file
	 *
	 * @return object
	 */
	public function getMedia()
	{
		$app = JFactory::getApplication();
		$jinput = $app->input;

		// Here, fpht means file encoded path
		$encodedFileName = $jinput->get('fpht', '', 'STRING');
		$decodedFileName = base64_decode($encodedFileName);

		$client = $jinput->get('client', '', 'STRING');

		$client = explode('.', $client);

		$file_extension = strtolower(substr(strrchr($decodedFileName, "."), 1));

		$mediaLocal = TJMediaStorageLocal::getInstance();

		$ctype = $mediaLocal->getMime($file_extension);

		$type = explode('/', $ctype);

		$decodedPath = 'media/' . $client[0] . '/' . $client[1] . '/' . $type[0] . '/' . $decodedFileName;

		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldValuesTable = JTable::getInstance('fieldsvalue', 'TjfieldsTable', array('dbo', $db));
		$tjFieldFieldValuesTable->load(array('id' => $jinput->get('id', '', 'INT')));

		// Subform File field Id for checking autherization for specific field under subform
		$subformFileFieldId = $jinput->get('subFormFileFieldId', '', 'INT');

		if ($tjFieldFieldValuesTable->id)
		{
			$user = JFactory::getUser();

			if ($subformFileFieldId)
			{
				$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $subformFileFieldId);
			}
			else
			{
				$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $tjFieldFieldValuesTable->field_id);
			}

			// Allow to view own data
			if ($tjFieldFieldValuesTable->user_id != null && ($user->id == $tjFieldFieldValuesTable->user_id))
			{
				$canView = true;
			}

			if ($canView)
			{
				$down_status = $mediaLocal->downloadMedia($decodedPath, '', '', 0);

				if ($down_status === 2)
				{
					$app->enqueueMessage(JText::_('COM_TJFIELDS_FILE_NOT_FOUND'), 'error');
					$app->redirect($this->returnURL);
				}

				return;
			}
			else
			{
				$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->redirect($this->returnURL);
			}
		}
		else
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect($this->returnURL);
		}

		jexit();
	}
}
