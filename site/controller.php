<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class TjfieldsController extends JControllerLegacy
{

	/*
	 * client = com_quick2cart.products
	 * category_id =  0
	 * fields_value[] = 21 (red), 22(green)
	 *
	 * have to fetch fontent id
	 * */
	function filterData()
	{
		$jinput  = JFactory::getApplication()->input;
		$client = $jinput->get("client", "com_jgive.campaign");
		$category_id = $jinput->get("category_id", 21);
		$fields_value_str = $jinput->get("tj_fields_value", "19,14,17");

		//$data['fields_value'] =  array(19,14,17);
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Content id : clent specific id; eg  Quick2cart's product id
		$query->select('#__tjfields_fields_value.content_id');
		$query->from('#__tjfields_fields_value');
		$query->join('INNER', $db->qn('#__tjfields_fields') . ' ON (' .
		$db->qn('#__tjfields_fields.id') . ' = ' . $db->qn('#__tjfields_fields_value.field_id') . ')');

		// Selected field value
		if (!empty($fields_value_str))
		{
			$query->select('#__tjfields_fields_value.id');
			$query->where('#__tjfields_fields_value.id IN (' . $fields_value_str . ')' );
		}

		if (!empty($category_id))
		{
			$query->select('#__tjfields_category_mapping.category_id');
			$query->join('INNER', $db->qn('#__tjfields_category_mapping') . 'ON (' .
			$db->qn('#__tjfields_fields_value.field_id') . ' = ' . $db->qn('#__tjfields_category_mapping.field_id') . ')');
			$query->where('#__tjfields_category_mapping.category_id = ' . $category_id);
		}

		$query->where('#__tjfields_fields_value.client="' . $client . '" ' );
		$query->where('#__tjfields_fields.state=' . $db->quote("1"));
		$query->where('#__tjfields_fields.filterable=' . $db->quote("1"));

		$clientDetail = explode('.', $client);
		$component = $clientDetail[0];

		$this->mergeWithCompoentQuery($component, $query);

		//$db->setQuery($query);
		//$field_data_value = $db->loadObjectlist();

		print"<pre> die in tjfield controller"; print_r($field_data_value); die;
	}

	function mergeWithCompoentQuery($component, $query)
	{
		// Load Quick2cart helper class for js files.
		$path                = JPATH_SITE . "/components/com_quick2cart/helper.php";
		$comquick2cartHelper = self::loadClass($path, 'comquick2cartHelper');
		comquick2cartHelper::displayQuick2cartData($query);

		//~ // Load Quick2cart helper class for js files.
		//~ $path                = JPATH_SITE . "/components/com_jgive/helper.php";
		//~ $JgiveFrontendHelper = self::loadClass($path, 'JgiveFrontendHelper');
		//~ JgiveFrontendHelper::displayQuick2cartData($query);
	}

		/**
	 * This function to load class.
	 *
	 * @param   string  $path       Path of file.
	 * @param   string  $className  Class Name to load.
	 *
	 * @return  Object of provided class.
	 */
	public static function loadClass($path, $className)
	{
		if (!class_exists($className))
		{
			JLoader::register($className, $path);
			JLoader::load($className);
		}

		if (class_exists($className))
		{
			return new $className;
		}
		else
		{
			throw new RuntimeException(sprintf('Unable to load class: %s', $className));

			// JFactory::getApplication()->enqueueMessage(sprintf('Unable to load class: %s, $className), 'error');
		}
	}
}