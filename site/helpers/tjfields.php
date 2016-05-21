<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
/**
 * helper class for tjfields
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsHelper
{
	/**
	 * My function
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function myFunction()
	{
		$result = 'Something';

		return $result;
	}

	/**
	 * Function used for renderring. fetching value
	 *
	 * @param   array  $data  get data
	 *
	 * @return  void
	 */
	public function FetchDatavalue($data)
	{
		$content_id        = $data['content_id'];
		$client            = $data['client'];
		$query_user_string = '';

		if (isset($data['user_id']))
		{
			$user_id           = $data['user_id'];
			$query_user_string = " AND user_id=" . $user_id;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('#__tjfields_fields_value.field_id,value FROM #__tjfields_fields_value ');
		$query->join('INNER', $db->qn('#__tjfields_fields') . ' ON (' .
		$db->qn('#__tjfields_fields.id') . ' = ' . $db->qn('#__tjfields_fields_value.field_id') . ')');

		if (!empty($data['category_id']))
		{
			$query->join('INNER', $db->qn('#__tjfields_category_mapping') . 'ON (' .
			$db->qn('#__tjfields_category_mapping.field_id') . ' = ' . $db->qn('#__tjfields_fields.id') . ')');
			$query->where('#__tjfields_category_mapping.category_id = ' . $data['category_id']);
		}

		$query->where('#__tjfields_fields_value.content_id=' . $content_id);
		$query->where('#__tjfields_fields_value.client="' . $client . '" ' . $query_user_string);
		$query->where('#__tjfields_fields.state=' . $db->quote("1"));
		$db->setQuery($query);

		$field_data_value = $db->loadObjectlist();

		// Check if the field type is list or radio (fields which have option)
		foreach ($field_data_value as $fdata)
		{
			$fieldData = $this->getFieldData('', $fdata->field_id);

			if (!empty($fieldData))
			{
				if ($fieldData->type == 'single_select' || $fieldData->type == 'multi_select' || $fieldData->type == 'radio' || $fieldData->type == 'checkbox')
				{
					$extra_options = $this->getOptions($fdata->field_id, $fdata->value);
					$fdata->value  = $extra_options;
				}
				elseif ($fieldData->type == 'calendar')
				{
					// $format = $this->getDateFormat($fieldData->format);

					if ($fieldData->format == 1)
					{
						$fdata->value = JFactory::getDate($fdata->value)->Format('d-m-Y');
					}
					elseif (($fieldData->format == 2))
					{
						$fdata->value = JFactory::getDate($fdata->value)->Format('m-d-Y');
					}
					elseif ($fieldData->format == 3)
					{
						$fdata->value = JFactory::getDate($fdata->value)->Format('Y-d-m');
					}
					else
					{
						$fdata->value = JFactory::getDate($fdata->value)->Format('Y-m-d');
					}
				}

				$fdata->type  = $fieldData->type;
				$fdata->name  = $fieldData->name;
				$fdata->label = $fieldData->label;
			}
		}

		return $field_data_value;
	}

	/**
	 * Get field Id and type.
	 *
	 * @param   string  $fname  name of field
	 * @param   string  $fid    id of field
	 *
	 * @return  object
	 */
	public function getFieldData($fname = '', $fid = '')
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id,type,name,label,format FROM #__tjfields_fields');

		if ($fname)
		{
			$query->where('name="' . $fname . '"');
		}
		else
		{
			$query->where('id=' . $fid);
		}

		$db->setQuery($query);
		$field_data = $db->loadObject();

		return $field_data;
	}

	/**
	 * Save fields.
	 *
	 * @param   array  $data  Post array which content (client, content_id, Fname, Fvalue, u_id)
	 *
	 * @return  true
	 */
	public function saveFieldsValue($data)
	{
		if (empty($data))
		{
			return false;
		}

		// Get field Id and field type.
		$db         = JFactory::getDbo();
		$insert_obj = new stdClass;
		$insert_obj->content_id = $data['content_id'];
		$insert_obj->user_id    = $data['user_id'];
		$insert_obj->email_id   = '';
		$insert_obj->client     = $data['client'];

		// Values array will contain menu fields value.
		foreach ($data['fieldsvalue'] as $fname => $fvalue)
		{
			$field_data           = $this->getFieldData($fname);
			$insert_obj->field_id = $field_data->id;

			// Check for duplicate entry
			$if_edit_id           = $this->checkForAlreadyexitsDetails($data, $field_data->id);

			if (!empty($fvalue))
			{
				if (!is_array($fvalue))
				{
					$insert_obj->value = $fvalue;
				}
				else
				{
					$insert_obj->value = json_encode($fvalue);
				}

				if ($if_edit_id)
				{
					$insert_obj->id = $if_edit_id;
					$db->updateObject('#__tjfields_fields_value', $insert_obj, 'id');
				}
				else
				{
					$insert_obj->id = '';
					$db->insertObject('#__tjfields_fields_value', $insert_obj, 'id');
				}
			}
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $data      Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $field_id  id of field
	 *
	 * @return  array
	 */
	public function checkForAlreadyexitsDetails($data, $field_id)
	{
		$content_id = $data['content_id'];
		$client     = $data['client'];
		$user_id    = $data['user_id'];
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id FROM #__tjfields_fields_value');
		$query->where('content_id=' . $content_id . ' AND client="' . $client . '" AND user_id=' . $user_id);

		if ($field_id)
		{
			$query->where('field_id=' . $field_id);
		}

		$db->setQuery($query);
		$is_edit = $db->loadresult();

		return $is_edit;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   array  $field_id      field if
	 * @param   array  $option_value  option value
	 *
	 * @return array Option for the particular field
	 */
	public function getOptions($field_id, $option_value = '')
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id,options,default_option,value FROM #__tjfields_options');
		$query->where('field_id=' . $field_id);

		if ($option_value != '')
		{
			$new_option_value = json_decode($option_value);

			if ($new_option_value != '')
			{
				if (is_array($new_option_value))
				{
					$option_value_string = "'" . implode("','", $new_option_value) . "'";
					$query->where('value IN (' . $option_value_string . ')');
				}
				else
				{
					$query->where('value=' . $new_option_value);
				}
			}
			else
			{
				// Radio.
				$query->where('value=' . $db->quote($option_value));
			}
		}

		$db->setQuery($query);
		$extra_options = $db->loadObjectlist();

		return $extra_options;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   array  $client  Get all fields based on client
	 *
	 * @return object
	 */
	public function getUniversalFields($client)
	{
		$universalFields = "";

		if (!empty($client))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT f.id,f.label,f.name FROM #__tjfields_fields AS f');
			$query->where('NOT EXISTS (select * FROM #__tjfields_category_mapping AS cm where f.id=cm.field_id)');
			$query->where('f.client="' . $client . '"');
			$query->where('f.state=1');
			$query->where('f.filterable=1');
			$db->setQuery($query);
			$universalFields = $db->loadObjectlist();
		}

		return $universalFields;
	}

	// Added by ankush

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   array  $client  Get all fields based on client
	 *
	 * @return object
	 */
	public function getCategorys($client)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('* FROM #__categories');
		$query->where('extension="' . $client . '"');
		$query->where('published=1');
		$db->setQuery($query);
		$categorysList = $db->loadObjectlist();

		return $categorysList;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   array  $client       Get all fields based on client
	 * @param   array  $category_id  Get all fields for selected category
	 *
	 * @return object
	 */
	public function getClientsCategoryFields($client, $category_id = '')
	{
		$coreFields = '';

		if (!empty($client))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT f.id,f.label,f.name FROM #__tjfields_fields AS f');
			$query->JOIN('INNER', '`#__tjfields_category_mapping` AS cm ON f.id=cm.field_id');
			$query->where('f.client="' . $client . '"');
			$query->where('f.state=1');
			$query->where('f.filterable=1');

			if (!empty($category_id))
			{
				$query->where('cm.category_id=' . $category_id);
			}

			$db->setQuery($query);
			$coreFields = $db->loadObjectlist();
		}

		return $coreFields;
	}

	/**
	 * Get dete format
	 *
	 * @param   array  $format  format of date
	 *
	 * @return object
	 */
	public function getDateFormat($format)
	{
		if ($format == 1)
		{
			return "d/m/Y";
		}
		elseif (($format == 2))
		{
			return "m/d/Y";
		}
		elseif ($format == 3)
		{
			return "Y/d/m";
		}
		else
		{
			return "Y/m/d";
		}
	}

	/**
	 * Method buildFilterModuleQuery for client = com_quick2cart.products
	 *
	 * @return object
	 */
	public static function buildFilterModuleQuery()
	{
		$jinput  = JFactory::getApplication()->input;
		$client = $jinput->get("client");

		// Get parameter name in which you are sending category id
		$tj_mod_filter_cat = $jinput->get("tj_mod_filter_cat", "prod_cat");
		$category_id = $jinput->get($tj_mod_filter_cat);
		$fields_value_str = $jinput->get("tj_fields_value", '', "RAW");

		if ($fields_value_str)
		{
			$fields_value_str = explode(',', $fields_value_str);
			$fields_value_str = array_filter($fields_value_str, 'trim');
			$fields_value_str = implode(',', $fields_value_str);
		}

		/*$data['fields_value'] =  array(19,14,17);*/
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
			$query->join('INNER', $db->qn('#__tjfields_options') . 'ON (' .
			$db->qn('#__tjfields_options.field_id') . ' = ' . $db->qn('#__tjfields_fields_value.field_id') . ')');
			$query->where('#__tjfields_options.id IN (' . $fields_value_str . ')');
			$query->where('#__tjfields_options.value  = #__tjfields_fields_value.value ');
		}

		if (!empty($category_id))
		{
			$query->join('INNER', $db->qn('#__tjfields_category_mapping') . 'ON (' .
			$db->qn('#__tjfields_fields_value.field_id') . ' = ' . $db->qn('#__tjfields_category_mapping.field_id') . ')');
			$query->where('#__tjfields_category_mapping.category_id = ' . $category_id);
		}

		$query->where('#__tjfields_fields_value.client="' . $client . '" ');
		$query->where('#__tjfields_fields.state=' . $db->quote("1"));
		$query->where('#__tjfields_fields.filterable=' . $db->quote("1"));

		$filterableFields = array("'single_select'", "'radio'", "'multi_select'");
		$filterableFieldsStr = implode(",", $filterableFields);

		$query->where("#__tjfields_fields.type IN (" . $filterableFieldsStr . ")");

		return $query;

		$clientDetail = explode('.', $client);
		$component = $clientDetail[0];

		$this->mergeWithCompoentQuery($component, $query);
	}

	/**
	 * Method buildFilterModuleQuery for client = com_quick2cart.products
	 *
	 * @param   String  $component  Component name
	 * @param   String  $query      Query
	 *
	 * @return object
	 */
	public function mergeWithCompoentQuery($component, $query)
	{
		// Load Quick2cart helper class for js files.
		$path                = JPATH_SITE . "/components/com_quick2cart/helper.php";
		$comquick2cartHelper = self::loadClass($path, 'comquick2cartHelper');
		comquick2cartHelper::displayQuick2cartData($query);

		// Load Quick2cart helper class for js files.

		/*$path                = JPATH_SITE . "/components/com_jgive/helper.php";
		$JgiveFrontendHelper = self::loadClass($path, 'JgiveFrontendHelper');
		JgiveFrontendHelper::displayQuick2cartData($query);*/
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
