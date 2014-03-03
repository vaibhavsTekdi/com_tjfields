<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */


// no direct access
defined('_JEXEC') or die;
if(!defined('DS'))
{
	define('DS',DIRECTORY_SEPARATOR);

}
// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_tjfields'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

$document=JFactory::getDocument();
include_once JPATH_ROOT.DS.'media'.DS.'techjoomla_strapper'.DS.'strapper.php';
TjAkeebaStrapper::bootstrap();


//include helper file
$helperPath= dirname(__FILE__).DS.'helpers'.DS.'tjfields.php';
if(!class_exists('TjfieldsHelper'))
{
	JLoader::register('TjfieldsHelper',$helperPath);
	JLoader::load('TjfieldsHelper');
}

$controller	= JControllerLegacy::getInstance('Tjfields');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
