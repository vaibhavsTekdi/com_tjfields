<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.modellist');
jimport('joomla.filesystem.file');
jimport('joomla.database.table');

$lang = JFactory::getLanguage();
$lang->load('com_tjfields', JPATH_SITE);

/**
 * Methods supporting a list of regions records.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
trait TjfieldsFilterField
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional ordering field.
	 * @param   boolean  $loadData  An optional direction (asc|desc).
	 *
	 * @return  JForm|boolean    $form      A JForm object on success, false on failure
	 *
	 * @since   2.2
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm($data['client'], $data['view'], array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  $item  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
		}

		return $item;
	}

	/**
	 * Method to get the form for extra fields.
	 * This form file will be created by field manager.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   Array    $data      An optional array of data for the form to interogate.
	 * @param   Boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  array|boolean    A JForm    object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getFormObject($data = array(), $loadData = false)
	{
		// Check if form file is present.
		$category = !empty($data['category']) ? $data['category'] : '';
		$filePath = JPATH_SITE . '/components/' . $data['clientComponent'] . '/models/forms/' . $category . $data['view'] . 'form_extra.xml';
		$user = JFactory::getUser();

		$form = new stdclass;

		$formName = $data['client'] . "_extra" . $category;

		// Get the form.
		$form = $this->loadForm($formName, $filePath, array('control' => 'jform', 'load_data' => $loadData), true);

		// If category is specified then check if global fields are created and load respective XML
		if (!empty($category))
		{
			$path = JPATH_SITE . '/components/' . $data['clientComponent'] . '/models/forms/' . $data['view'] . 'form_extra.xml';

			// If category XML esists then add global fields XML in current JForm object else create new object of Global Fields
			if (!empty($form))
			{
				$form->loadFile($path, true, '/form/*');
			}
			else
			{
				$formName = $data['client'] . "_extra";
				$form = $this->loadForm($formName, $path, array('control' => 'jform', 'load_data' => $loadData), true);
			}
		}

		if (empty($form))
		{
			return false;
		}

		// Load form data for extra fields (needed for editing).
		$dataExtra = $this->loadFormDataExtra($data);

		// Bind the data for extra fields to this form.
		$form->bind($dataExtra);

		// Check for field level permissions - start
		$db = JFactory::getDbo();
		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = JTable::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldSets = $form->getFieldsets();
		$extraData = $this->getDataExtra($data);

		foreach ($fieldSets as $fieldset)
		{
			foreach ($form->getFieldset($fieldset->name) as $field)
			{
				$tjFieldFieldTable->load(array('name' => $field->fieldname));

				$canAdd = 0;

				if ($user->authorise('core.field.addfieldvalue', 'com_tjfields.group.' . $tjFieldFieldTable->group_id))
				{
					$canAdd = $user->authorise('core.field.addfieldvalue', 'com_tjfields.field.' . $tjFieldFieldTable->id);
				}

				$canEdit = 0;

				if ($user->authorise('core.field.editfieldvalue', 'com_tjfields.group.' . $tjFieldFieldTable->group_id))
				{
					$canEdit = $user->authorise('core.field.editfieldvalue', 'com_tjfields.field.' . $tjFieldFieldTable->id);
				}

				$canView = 0;

				if ($user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $tjFieldFieldTable->group_id))
				{
					$canView = $user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $tjFieldFieldTable->id);
				}

				$canEditOwn = 0;

				if ($user->authorise('core.field.editownfieldvalue', 'com_tjfields.group.' . $tjFieldFieldTable->group_id))
				{
					$canEditOwn = $user->authorise('core.field.editownfieldvalue', 'com_tjfields.field.' . $tjFieldFieldTable->id);
				}

				if ($data['layout'] == 'edit')
				{
					// If new record is added
					if (empty($data['content_id']))
					{
						if (!$canAdd)
						{
							$form->setFieldAttribute($field->fieldname, 'required', false);
							$form->setFieldAttribute($field->fieldname, 'class', 'hidden');
							$form->setFieldAttribute($field->fieldname, 'hidden', true);
						}
					}
					else
					{
						if ($canAdd)
						{
							if (!empty($extraData[$tjFieldFieldTable->id]))
							{
								$userId = $extraData[$tjFieldFieldTable->id]->user_id;
							}

							if (!$canEdit && ($user->id != $userId))
							{
								$form->setFieldAttribute($field->fieldname, 'readonly', true);
								$form->setFieldAttribute($field->fieldname, 'disabled', true);
							}

							if (!$canEditOwn && ($user->id == $userId))
							{
								$form->setFieldAttribute($field->fieldname, 'readonly', true);
								$form->setFieldAttribute($field->fieldname, 'disabled', true);
							}
						}
						else
						{
							$form->setFieldAttribute($field->fieldname, 'required', false);
							$form->setFieldAttribute($field->fieldname, 'class', 'hidden');
							$form->setFieldAttribute($field->fieldname, 'hidden', true);
						}
					}
				}
				else
				{
					$userId = 0;

					if (!empty($extraData[$tjFieldFieldTable->id]))
					{
						$userId = $extraData[$tjFieldFieldTable->id]->user_id;
					}

					// Allow to view own data
					if ($user->id == $userId)
					{
						$canView = true;
					}

					if (!$canView)
					{
						$form->removeField($field->fieldname);
					}
				}
			}
		}

		// Check for field level permissions - end

		return $form;
	}

	/**
	 * Method to get the form for extra fields.
	 * This form file will be created by field manager.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   Array    $data      An optional array of data for the form to interogate.
	 * @param   Boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm    A JForm    object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getFormExtra($data = array(), $loadData = false)
	{
		$form = new stdclass;

		// Call to extra fields
		if (!empty($data['category']))
		{
			$form = $this->getFormObject($data, $loadData);

			if (!$form)
			{
				unset($data['category']);
			}
		}

		$form = new stdclass;

		// Call to global extra fields
		$form = $this->getFormObject($data, $loadData);

		return $form;
	}

	/**
	 * Method to get the form for extra fields.
	 * This form file will be created by field manager.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array  $data  data
	 *
	 * @return  array|bool  array on success, flase on failure
	 *
	 * @since	1.6
	 */
	protected function loadFormDataExtra($data)
	{
		$dataExtra = $this->getDataExtraFields($data);

		return $dataExtra;
	}

	/**
	 * Method to get the data of extra form fields
	 * This form file will be created by field manager.
	 *
	 * @param   array  $data  data
	 * @param   INT    $id    Id of record
	 *
	 * @return  array|bool  array on success, flase on failure
	 *
	 * @since	1.6
	 */
	public function getDataExtraFields($data, $id = null)
	{
		$input = JFactory::getApplication()->input;
		$user = JFactory::getUser();

		// If id is not present in $data then check if it is available in JInput
		if (empty($id))
		{
			$id = (empty($data['content_id']))?$input->get('content_id', '', 'INT'):$data['content_id'];
		}

		if (empty($id))
		{
			return false;
		}

		$TjfieldsHelperPath = JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		if (!class_exists('TjfieldsHelper'))
		{
			JLoader::register('TjfieldsHelper', $TjfieldsHelperPath);
			JLoader::load('TjfieldsHelper');
		}

		$tjFieldsHelper = new TjfieldsHelper;

		$data['content_id']  = $id;
		$data['user_id']     = JFactory::getUser()->id;

		$extra_fields_data = $tjFieldsHelper->FetchDatavalue($data);

		$extra_fields_data_formatted = array();

		foreach ($extra_fields_data as $efd)
		{
			if (!is_array($efd->value))
			{
				$extra_fields_data_formatted[$efd->name] = $efd->value;
			}
			else
			{
				$temp = array();

				switch ($efd->type)
				{
					case 'multi_select':
						foreach ($efd->value as $option)
						{
							$temp[] = $option->value;
						}

						if (!empty($temp))
						{
							$extra_fields_data_formatted[$efd->name] = $temp;
						}

					break;

					case 'single_select':
						foreach ($efd->value as $option)
						{
							$extra_fields_data_formatted[$efd->name] = $option->value;
						}
					break;

					case 'radio':
					default:
						foreach ($efd->value as $option)
						{
							$extra_fields_data_formatted[$efd->name] = $option->value;
						}
					break;
				}
			}
		}

		$this->_item_extra_fields = $extra_fields_data_formatted;

		return $this->_item_extra_fields;
	}

	/**
	 * Method to validate the extraform data.
	 *
	 * Added by manoj.
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 * @since   12.2
	 */
	public function validateExtra($form, $data, $group = null)
	{
		$data = parent::validate($form, $data);

		return $data;
	}

	/**
	 * Method to get the extra fields information
	 *
	 * @param   array  $data  data
	 * @param   array  $id    Id of the record
	 *
	 * @return	array|boolean field data
	 *
	 * @since	1.8.5
	 */
	public function getDataExtra($data, $id = null)
	{
		if (empty($id))
		{
			$input = JFactory::getApplication()->input;
			$id = (empty($data['content_id'])) ? $input->get('content_id', '', 'INT') : $data['content_id'];
		}

		if (empty($id))
		{
			return false;
		}

		$TjfieldsHelperPath = JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		if (!class_exists('TjfieldsHelper'))
		{
			JLoader::register('TjfieldsHelper', $TjfieldsHelperPath);
			JLoader::load('TjfieldsHelper');
		}

		$tjFieldsHelper = new TjfieldsHelper;
		$data['content_id'] = $id;
		$extra_fields_data = $tjFieldsHelper->FetchDatavalue($data);

		return $extra_fields_data;
	}

	/**
	 * Method to save the extra fields data.
	 *
	 * @param   array  $data  data
	 *
	 * @return  boolean  A JForm object on success, false on failure
	 *
	 * @since  1.6
	 */
	public function saveExtraFields($data)
	{
		$TjfieldsHelperPath = JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		if (!class_exists('TjfieldsHelper'))
		{
			JLoader::register('TjfieldsHelper', $TjfieldsHelperPath);
			JLoader::load('TjfieldsHelper');
		}

		$tjFieldsHelper = new TjfieldsHelper;

		$data['user_id']     = JFactory::getUser()->id;

		$result = $tjFieldsHelper->saveFieldsValue($data);

		return $result;
	}

	/**
	 * Method to delete extra fields data.
	 *
	 * @param   INT     $content_id  content id
	 * @param   STRING  $client      client
	 *
	 * @return  boolean
	 *
	 * @since  1.6
	 */
	public function deleteExtraFieldsData($content_id, $client)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$conditions = array(
			$db->quoteName('content_id') . ' = ' . $content_id,
			$db->quoteName('client') . " = '" . $client . "'"
		);

		$query->delete($db->quoteName('#__tjfields_fields_value'));
		$query->where($conditions);

		$db->setQuery($query);

		$result = $db->execute();

		return $result;
	}

	/**
	 * This define the  language constant which you have use in js file.
	 *
	 * @since   1.0
	 * @return   null
	 */
	public static function getLanguage()
	{
		JText::script('COM_TJFIELDS_FILE_DELETE_CONFIRM');
		JText::script('COM_TJFIELDS_FILE_ERROR_MAX_SIZE');
		JText::script('COM_TJFIELDS_FILE_DELETE_SUCCESS');
	}
}
