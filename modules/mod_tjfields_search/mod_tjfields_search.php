<?php
/**
 * @version    SVN: <svn_id>
 * @package    Quick2cart
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// No direct access.
defined('_JEXEC') or die();

$input = JFactory::getApplication()->input;

// LOAD LANGUAGE FILES
$doc = JFactory::getDocument();
$lang = JFactory::getLanguage();
$lang->load('mod_tjfields_search', JPATH_SITE);

// GETTING MODULE PARAMS
$url_cat_param_name       = $params->get('url_cat_param_name', '');
$client_type               = $params->get('client_type', '');
$category_type               = $params->get('category_type', '');

if (JFile::exists(JPATH_SITE . '/components/com_tjfields/tjfields.php'))
{
	$path = JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

	if (!class_exists('tjfieldsHelper'))
	{
		JLoader::register('tjfieldsHelper', $path);
		JLoader::load('tjfieldsHelper');
	}

	// Selected category
	$clientCatUrlParam = $params->get("url_cat_param_name", "prod_cat");
	$selectedCategory = $input->get($clientCatUrlParam, '');

	$tjfieldsHelper = new tjfieldsHelper;

	$fieldsArray = array();

	$fieldsCategorys = array();
	$defaultCategory = new stdclass;
	$defaultCategory->id = '';
	$defaultCategory->title = 'Select Category';
	$fieldsCategorys[] = $defaultCategory;
	$categoryList = $tjfieldsHelper->getCategorys($category_type);

	if (!empty($categoryList))
	{
		foreach ($categoryList as $category)
		{
			$fieldsCategorys[] = $category;
		}
	}

	// Universal field- for client - those field who doesn't mapped agaist category
	$fieldsArray['universal'] = $tjfieldsHelper->getUniversalFields($client_type);

	// Get client categorySpecific fields
	$fieldsArray['core'] = $tjfieldsHelper->getClientsCategoryFields($client_type, $selectedCategory);

	require JModuleHelper::getLayoutPath('mod_tjfields_search');
}
