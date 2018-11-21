<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

JLoader::register('TjfieldsHelper', JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php');
JLoader::load('TjfieldsHelper');
TjfieldsHelper::getLanguageConstantForJs();

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$controller	= JControllerLegacy::getInstance('Tjfields');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
