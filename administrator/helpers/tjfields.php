<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);


// No direct access
defined('_JEXEC') or die;
use Joomla\String\StringHelper;

/**
 * Helper class for tjfields
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsHelper extends JHelperContent
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   STRING  $view  view name
	 *
	 * @return null
	 */
	public static function addSubmenu($view = '')
	{
		$input = JFactory::getApplication()->input;
		$full_client = $input->get('client', '', 'STRING');
		$full_client = explode('.', $full_client);

		// Eg com_jticketing
		$component = $full_client[0];
		$eName = str_replace('com_', '', $component);
		$file = JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (file_exists($file))
		{
			require_once $file;

			$prefix = ucfirst(str_replace('com_', '', $component));
			$cName = $prefix . 'Helper';

			if (class_exists($cName))
			{
				if (is_callable(array($cName, 'addSubmenu')))
				{
					$lang = JFactory::getLanguage();

					// Loading language file from the administrator/language directory then
					// Loading language file from the administrator/components/*extension*/language directory
					$lang->load($component, JPATH_BASE, null, false, false)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, false)
					|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), $lang->getDefault(), false, false);

					// Call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
					call_user_func(array($cName, 'addSubmenu'), $view . (isset($section) ? '.' . $section : ''));
				}
			}
		}
	}

	/** Gets a list of the actions that can be performed.
	 *
	 * @param   string   $component  The component name.
	 *
	 * @param   string   $section    The access section name.
	 *
	 * @param   integer  $id         The item ID.
	 *
	 * @return JObject
	 *
	 * @since 3.2
	 */
	public static function getActions($component = 'com_tjfields', $section = '', $id = '')
	{
		$result = parent::getActions($component, $section, $id);

		return $result;
	}

	/**
	 * Check if the name is unique
	 *
	 * @param   STRING  $data_unique_name  field name
	 *
	 * @return true or false
	 */
	public function checkIfUniqueName($data_unique_name)
	{
		$db = JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('count(name) FROM #__tjfields_fields');
		$query->where('name="' . $data_unique_name . '"');
		$db->setQuery($query);
		$is_unique = $db->loadResult();

		return $is_unique;
	}

	/**
	 * This function appaned ID to the name and replace it in DB
	 *
	 * @param   STRING  $data_same_name  field name
	 * @param   INT     $id              field id
	 *
	 * @return true or false
	 */
	public function changeNameIfNotUnique($data_same_name,$id)
	{
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->update('#__tjfields_fields');
		$query->set('name="' . $data_same_name . '_' . $id . '"');

		$query->where('id=' . $id);
		$db->setQuery($query);

		if (!$db->execute())
		{
			$stderr = $db->stderr();
			echo $app->enqueueMessage($stderr, 'error');
		}

		return true;
	}

	/**
	 * This function genarate XML on each saving of field.
	 *
	 * @param   OBJECT  $data  all data to save in xml
	 *
	 * @return  BOOLEAN
	 *
	 * @since 1.0
	 */
	public function generateXml($data)
	{
		$client = $data['client'];
		$input = JFactory::getApplication()->input;

		$client = explode(".", $client);
		$extension = $client[0];

		if (!empty($extension))
		{
			$db     = JFactory::getDbo();
			$query  = "SELECT DISTINCT id as category_id FROM #__categories where extension='" . $extension . "'";

			$db->setQuery($query);
			$categorys = $db->loadAssocList();
		}

		// For unmapped categorys - start
		$db     = JFactory::getDbo();
		$query  = 'SELECT f.*,g.name as group_name FROM
		#__tjfields_fields as f
		LEFT JOIN #__tjfields_groups as g
		ON g.id = f.group_id WHERE NOT EXISTS (select * FROM #__tjfields_category_mapping AS cm where f.id=cm.field_id)
		AND f.client="' . $data['client'] . '" AND f.state=1 AND g.state = 1
		ORDER BY f.ordering';

		$db->setQuery($query);
		$unmappedFields = $db->loadObjectList();

		if (!empty($unmappedFields))
		{
			$this->createXml($data, $unmappedFields);
		}
		else
		{
			// Delete Universal fields file if there are not any unmapped categorys
			$filePathFrontend = JPATH_SITE . '/components/' . $extension . '/models/forms/' .
			$data['client_type'] . 'form_extra.xml';
			$content  = '';

			$filePathBackend = JPATH_SITE . DS . 'administrator/components/' .
			$extension . '/models/forms/' .
			$data['client_type'] . '_extra.xml';

			if (JFile::exists($filePathFrontend))
			{
				JFile::delete($filePathFrontend);
			}

			if (JFile::exists($filePathBackend))
			{
				JFile::delete($filePathBackend);
			}
		}

		// For unmapped categorys - end
		if (!empty($categorys))
		{
			foreach ($categorys as $category)
			{
				// Join
				$db     = JFactory::getDbo();
				$query  = 'SELECT f.*,g.name as group_name FROM
				#__tjfields_fields as f
				LEFT JOIN #__tjfields_groups as g
				ON g.id = f.group_id LEFT JOIN #__tjfields_category_mapping as cm ON f.id=cm.field_id
				WHERE f.client="' . $data['client'] . '" AND cm.category_id="' . $category['category_id'] . '" AND f.state=1 AND g.state = 1
				ORDER BY f.ordering';

				$db->setQuery($query);
				$fields = $db->loadObjectList();

				$this->createXml($data, $fields, $category);
			}
		}
	}

	/**
	 * This function genarate XML on each saving of field.
	 *
	 * @param   OBJECT  $data      all data to save in xml
	 * @param   OBJECT  $fields    fields data
	 * @param   OBJECT  $category  category mapped to field
	 *
	 * @return  BOOLEAN
	 *
	 * @since 1.0
	 */
	public function createXml($data, $fields, $category = null)
	{
		$newXML = new SimpleXMLElement("<form></form>");

		$explodeForCom = explode(".", $data['client']);

		// Get backend XML file path
		if (!empty($category['category_id']))
		{
			$filePathBackend = JPATH_SITE . DS . 'administrator/components/' .
			$explodeForCom[0] . '/models/forms/' . $category['category_id'] .
			$data['client_type'] . '_extra.xml';
		}
		else
		{
			$filePathBackend = JPATH_SITE . DS . 'administrator/components/' .
			$explodeForCom[0] . '/models/forms/' .
			$data['client_type'] . '_extra.xml';
		}

		// Get frontend XML file path
		if (!empty($category['category_id']))
		{
			$filePathFrontend = JPATH_SITE . '/components/' . $explodeForCom[0] . '/models/forms/' .
			$category['category_id'] . $data['client_type'] . 'form_extra.xml';
			$content  = '';
		}
		else
		{
			$filePathFrontend = JPATH_SITE . '/components/' . $explodeForCom[0] . '/models/forms/' .
			$data['client_type'] . 'form_extra.xml';
			$content  = '';
		}

		if (!empty($fields))
		{
			$current_group = $fields[0]->group_id;
			$i = 0;
			$new_fieldset = $newXML->addChild('fieldset');
			$new_fieldset->addAttribute('name', $fields[0]->group_name);

			foreach ($fields as $f)
			{
				// Add fieldset as per group id
				if ($current_group != $f->group_id)
				{
					$new_fieldset = $newXML->addChild('fieldset');
					$new_fieldset->addAttribute('name', $f->group_name);
					$current_group = $f->group_id;
				}

				$f = $this->getOptionData($f);
				$field = $new_fieldset->addChild('field');
				$field->addAttribute('name', $f->name);

				// Need to change...
				$field->addAttribute('type', $f->type);
				$field->addAttribute('label', $f->label);
				$field->addAttribute('description', $f->description);

				if ($f->required == 1)
				{
					$field->addAttribute('required', 'true');
				}

				if ($f->readonly == 1)
				{
					$field->addAttribute('readonly', 'true');
				}

				$field->addAttribute('class', $f->validation_class);

				if (!empty($f->params))
				{
					$fieldAttribute = json_decode($f->params);

					foreach ($fieldAttribute as $attribute => $fieldparam)
					{
						if (!empty($fieldparam))
						{
							$field->addAttribute($attribute, $fieldparam);
						}
					}
				}

				// Add javascript
				if (isset($f->js_function))
				{
					$jsArray = $this->getJsArray($f->js_function);

					foreach ($jsArray as $js)
					{
						$field->addAttribute($js[0], $js[1]);
					}
				}

				$default_value = array();
				$value_string = '';

				// ADD option if present.
				if (isset($f->extra_options))
				{
					// Extra value for only Single select field // && $f->multiple == 'false')
					if ($f->type == 'list')
					{
						$fieldAttribute = json_decode($f->params);

						if ($fieldAttribute->multiple != 'true')
						{
							// Set Default blank Option
							$option = $field->addChild('option', '- ' . JText::_('COM_TJFIELDS_SELECT_OPTION') . " " . $f->label . ' -');
							$option->addAttribute('value', '');
						}
					}

					foreach ($f->extra_options as $f_option)
					{
						$option = $field->addChild('option', $f_option->options);
						$option->addAttribute('value', $f_option->value);

						if ($f_option->default_option == 1)
						{
							$default_value[] = $f_option->value;
						}
					}
				}
			}

			if (!JFile::exists($filePathFrontend))
			{
				JFile::write($filePathFrontend, $content);
			}

			// ->asXML();
			$newXML->asXML($filePathFrontend);

			$content  = '';

			if (!JFile::exists($filePathBackend))
			{
				JFile::write($filePathBackend, $content);
			}

			// ->asXML();
			$newXML->asXML($filePathBackend);
		}
		else
		{
			// Delete xml if no field present
			if (JFile::exists($filePathFrontend))
			{
				JFile::delete($filePathFrontend);
			}

			if (JFile::exists($filePathBackend))
			{
				JFile::delete($filePathBackend);
			}
		}
	}

	/**
	 *Method to add extra values of extra attribute
	 *
	 * @param   OBJECT  $data  data
	 *
	 * @return  OBJECT  $Data
	 *
	 * @since  1.0
	 */
	public function getOptionData($data)
	{
		if ($data->type == 'radio' || $data->type == 'single_select' || $data->type == 'multi_select')
		{
			// For field type single select and multi select field type in xml is 'list'
			if ($data->type == 'single_select' || $data->type == 'multi_select')
			{
				$data->type = "list";
			}

			$extra_options = $this->getOptions($data->id);
			$data->extra_options = $extra_options;
		}

		return $data;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   INT  $field_id  field id
	 *
	 * @return array of option for the particular field
	 */
	public function getFieldCategoryMapping($field_id)
	{
		$db = JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('category_id');
		$query->from('#__tjfields_category_mapping AS cm');
		$query->where('field_id=' . $field_id);
		$db->setQuery($query);
		$mapping = $db->loadColumn();

		return $mapping;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   INT  $field_id  field id
	 *
	 * @return array of option for the particular field
	 */
	public function getOptions($field_id)
	{
		$db = JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('id,options,default_option,value FROM #__tjfields_options');
		$query->where('field_id=' . $field_id);
		$db->setQuery($query);
		$extra_options = $db->loadObjectlist('id');

		// Print_r($extra_options); die('asdasd');
		return $extra_options;
	}

	/**
	 * Method to get JsArray
	 *
	 * @param   ARRAY  $jsarray  array of js function
	 *
	 * @return   array  js function array
	 */
	public function getJsArray($jsarray)
	{
		$jsarray = explode('||', $jsarray);

		// Remove the blank array element
		$jsarray_removed_blank_element = array_filter($jsarray);

		foreach ($jsarray_removed_blank_element as $eachjs)
		{
			$jsarray_final[] = explode('-', $eachjs);
		}

		return $jsarray_final;
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		JText::script('COM_TJFIELDS_LABEL_WHITESPACES_NOT_ALLOWED');
	}

	/**
	 * tjFileDelete .
	 *
	 * @param   Array  $data  file path.
	 *
	 * @return boolean|string
	 *
	 * @since	1.6
	 */
	public function deleteFile($data)
	{
		$user = JFactory::getUser();

		if (!$user->id)
		{
			return false;
		}

		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$fieldsValueTable = JTable::getInstance('Fieldsvalue', 'TjfieldsTable');

		$fieldsValueTable->load(array('id' => $data['valueId']));

		if ($data['isSubfromField'] == 1)
		{
			$subData = json_decode($fieldsValueTable->value);

			foreach ($subData as $value)
			{
				$subformData = (array) $value;

				if (in_array($data['filePath'], $subformData))
				{
					$fileUser = $fieldsValueTable->user_id;
				}
			}

			// Check for file field is of subform or ucmsubform
			if ($data['subfromFileFieldId'])
			{
				$fieldId = $data['subfromFileFieldId'];
			}
			else
			{
				$fieldId = $fieldsValueTable->field_id;
			}
		}
		else
		{
			if ($data['filePath'] === $fieldsValueTable->value)
			{
				$fileUser = $fieldsValueTable->user_id;
				$fieldId = $fieldsValueTable->field_id;
			}
		}

		$fileExtension = StringHelper::strtolower(StringHelper::substr(strrchr($data['filePath'], "."), 1));
		$localGetMime = TJMediaStorageLocal::getInstance();

		$ctype = $localGetMime->getMime($fileExtension);

		if (!empty($fileUser))
		{
			$canEdit = $user->authorise('core.field.editfieldvalue', 'com_tjfields.field.' . $fieldId);

			$canEditOwn = $user->authorise('core.field.editownfieldvalue', 'com_tjfields.field.' . $fieldId);

			if ($canEdit || (($user->id == $fileUser) && $canEditOwn))
			{
				$type = explode('/', $ctype);

				if ($type[0] === 'image')
				{
					$deleteData = array();
					$deleteData[] = JPATH_ROOT . $data['storagePath'] . '/' . $type[0] . '/' . $data['filePath'];

					$deleteData[] = JPATH_ROOT . $data['storagePath'] . '/' . $type[0] . '/S_' . $data['filePath'];
					$deleteData[] = JPATH_ROOT . $data['storagePath'] . '/' . $type[0] . '/M_' . $data['filePath'];
					$deleteData[] = JPATH_ROOT . $data['storagePath'] . '/' . $type[0] . '/L_' . $data['filePath'];

					foreach ($deleteData as $image)
					{
						if (JFile::exists($image))
						{
							JFile::delete($image);
						}
					}

					$deleted = 1;
				}
				else
				{
					if (!JFile::delete(JPATH_ROOT . $data['storagePath'] . '/' . $type[0] . '/' . $data['filePath']))
					{
						return false;
					}

					$deleted = 1;
				}

				if ($deleted == 1)
				{
					$db = JFactory::getDbo();
					$fields_obj = new stdClass;

					// Making value object if the field is under subform form subfrom
					if ($data['isSubfromField'] == 1)
					{
						foreach ($subData as $subformName => $value)
						{
							foreach ($value as $k => $v)
							{
								// Finding the particular index and making it null
								if ($v === $data['filePath'])
								{
									$subData->$subformName->$k = '';
								}
							}
						}

						$fields_obj->value = json_encode($subData);
					}
					else
					{
						$fields_obj->value = '';
					}

					$fields_obj->id = $fieldsValueTable->id;
					$db->updateObject('#__tjfields_fields_value', $fields_obj, 'id');

					return true;
				}

				return false;
			}

			return false;
		}

		return false;
	}
}
