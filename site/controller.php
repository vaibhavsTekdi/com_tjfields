<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
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
	 * Constructor
	 *
	 * @since 1.4
	 */
	public function __construct()
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

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
		$file_id = $jinput->get('fid', 0, 'INTEGER');

		// Get Field id from file id
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->quoteName('id') . ' = ' . $db->quote($file_id);
		$db->setQuery($query);
		$data = $db->loadObject();

		$user = JFactory::getUser();
		$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->id);

		// Allow to view own data
		if ($user->id == $data->user_id)
		{
			$canView = true;
		}

		if ($canView)
		{
			$tjfieldsHelper = new TjfieldsHelper;
			$filepath      = $tjfieldsHelper->getMediaPathFromId($file_id);

			// Download will start
			$down_status = $tjfieldsHelper->downloadMedia($filepath, '', '', 0);

			if ($down_status === 2)
			{
				$app->enqueueMessage(JText::_('COM_TJFIELDS_FILE_NOT_FOUND'), 'error');
				$app->redirect($uri);
			}

			return;
		}
		else
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect($uri);
		}

		jexit();
	}
}
