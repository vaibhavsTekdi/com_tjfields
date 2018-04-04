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
		$encodedFilePath = $jinput->get('fpht', '', 'STRING');
		$decodedPath = base64_decode($encodedFilePath);

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->where($db->quoteName('value') . " = " . $db->quote($decodedPath));
		$db->setQuery($query);
		$data = $db->loadObject();

		$user = JFactory::getUser();
		$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->field_id);

		// Allow to view own data
		if ($user->id == $data->user_id)
		{
			$canView = true;
		}

		if ($canView)
		{
			$tjfieldsHelper = new TjfieldsHelper;

			// Download will start
			$down_status = $tjfieldsHelper->downloadMedia($decodedPath, '', '', 0);

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

		jexit();
	}
}
