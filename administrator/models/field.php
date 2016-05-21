<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Tjfields model
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsModelField extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJFIELDS';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'Field', $prefix = 'TjfieldsTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   Array    $data      Data An optional array of data for the form to interogate.
	 * @param   Boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return	JForm	A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjfields.field', 'field', array(
			'control' => 'jform',
			'load_data' => $loadData
		)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_tjfields.edit.field.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   Integer  $pk  Primary key.
	 *
	 * @return  Items
	 *
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		$input = JFactory::getApplication()->input;

		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
			if ($input->get('id', '', 'INT'))
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('opt.id,opt.options,opt.value,opt.default_option FROM #__tjfields_options as opt');
				$query->where('opt.field_id=' . $input->get('id', '', 'INT'));
				$db->setQuery($query);
				$option_name = $db->loadObjectlist();

				if ($option_name)
				{
					$item->fieldoption = $option_name;
				}
			}
		}

		return $item;
	}

	/**
	 * Method Prepare and sanitise the table prior to saving.
	 *
	 * @param   Array  $table  Table
	 *
	 * @return  Boolean
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tjfields_fields');
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method Save Option
	 *
	 * @param   Array  $post  Post
	 *
	 * @return  Boolean
	 *
	 * @since	1.6
	 */
	public function save_option($post)
	{
		$table = $this->getTable();
		$data  = $post->get('jform', '', 'ARRAY');
		$input = JFactory::getApplication()->input;

		if ($input->get('task') == 'save2copy')
		{
			unset($data['id']);
			$data['label'] = trim($data['label']);
			$name          = explode("(", $data['label']);
			$name          = trim($name['0']);
			$name          = str_replace("`", "", $name);
			$db            = JFactory::getDBO();
			$query         = 'SELECT a.*' . ' FROM #__tjfields_fields AS a' . ' WHERE a.label LIKE ' .
			$db->quote($name . '%') . ' AND  a.client LIKE' . $db->quote($data['client']) . ' AND  a.group_id =' .
			$db->quote($data['group_id'] . '%');

			$db->setQuery($query);
			$posts              = $db->loadAssocList();
			$postsCount         = count($posts) + 1;
			$data['label']      = $name . ' (' . $postsCount . ")";
			$data['created_by'] = JFactory::getUser()->id;
		}

		// Add clint type in data as it is not present in jform
		$data['client_type'] = $post->get('client_type', '', 'STRING');

		// Use later to store later.
		$data['saveOption']  = 0;

		// Remove extra value which are not needed to save in the fields table
		$TjfieldsHelper      = new TjfieldsHelper;
		$data                = $TjfieldsHelper->getFieldArrayFormatted($data);

		if ($table->save($data) === true)
		{
			$id = $table->id;
		}

		// Check if name feild is unique
		$is_unique = $TjfieldsHelper->checkIfUniqueName($data['name']);

		if ($is_unique > 1)
		{
			// Append id to the name
			$change_name_if_same = $TjfieldsHelper->changeNameIfNotUnique($data['name'], $id);
		}

		// Save javascript functions.
		$js = $post->get('tjfieldsJs', '', 'ARRAY');

		if (!empty($js))
		{
			$jsfunctionSave = $this->jsfunctionSave($js, $id);
		}

		// If the field is inserted.
		if ($id)
		{
			$options = $post->get('tjfields', '', 'ARRAY');

			if ($data['saveOption'] == 1)
			{
				// Firstly Delete Fields Options That are Removed
				$field_options = $TjfieldsHelper->getOptions($id);

				foreach ($field_options as $fokey => $fovalue)
				{
					if ($fovalue->id)
					{
						$fields_in_DB[] = $fovalue->id;
					}
				}

				foreach ($options as $key => $value)
				{
					if ($value['hiddenoptionid'])
					{
						$options_filled[] = $value['hiddenoptionid'];
					}
				}

				if ($fields_in_DB)
				{
					$diff_ids = array_diff($fields_in_DB, $options_filled);

					if (!empty($diff_ids))
					{
						$this->delete_option($diff_ids);
					}
				}

				if (empty($options))
				{
					$this->delete_option($fields_in_DB);
				}
				else
				{
					// Save option fields.
					foreach ($options as $option)
					{
						if (!isset($option['hiddenoption']))
						{
							$option['hiddenoption'] = 0;
						}

						$obj                 = new stdClass;
						$obj->options        = $option['optionname'];
						$obj->value          = $option['optionvalue'];
						$obj->default_option = $option['hiddenoption'];
						$obj->field_id       = $id;

						// If edit options
						if (isset($option['hiddenoptionid']) && !empty($option['hiddenoptionid']))
						{
							if ($option['optionname'] != '' && $option['optionvalue'] != '')
							{
								$obj->id = $option['hiddenoptionid'];

								if (!$this->_db->updateObject('#__tjfields_options', $obj, 'id'))
								{
									echo $this->_db->stderr();

									return false;
								}
							}
						}
						else
						{
							if ($option['optionname'] != '' && $option['optionvalue'] != '')
							{
								$obj->id = '';

								if (!$this->_db->insertObject('#__tjfields_options', $obj, 'id'))
								{
									echo $this->_db->stderr();

									return false;
								}
							}
						}
					}
				}
			}

			// Save/update field and category mapping
			$selectedCategories = !empty($data['category']) ? $data['category'] : array();

			if ($selectedCategories)
			{
				// 1 Fetch cat mapping for field from DB
				$DBcat_maping   = $TjfieldsHelper->getFieldCategoryMapping($id);
				$newlyAddedCats = array_diff($selectedCategories, $DBcat_maping);
				$deletedCats    = array_diff($DBcat_maping, $selectedCategories);

				if (!empty($deletedCats))
				{
					$this->deleteFieldCategoriesMapping($id, $deletedCats);
				}

				if (!empty($newlyAddedCats))
				{
					// Add newly added  category mapping
					foreach ($newlyAddedCats as $cat)
					{
						$obj              = new stdClass;
						$obj->field_id    = $id;
						$obj->category_id = $cat;

						if (!$this->_db->insertObject('#__tjfields_category_mapping', $obj, 'id'))
						{
							echo $this->_db->stderr();

							return false;
						}
					}
				}
				else
				{
					// Delete existing mapping
					$this->deleteFieldCategoriesMapping($id);
				}

			// Create XML for the current client.
			$TjfieldsHelper->generateXml($data);

			return $id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method jsfunctionSave
	 *
	 * @param   Array    $jsarray  Array
	 * @param   Integer  $fieldid  Id
	 *
	 * @return	JObject
	 *
	 * @since	1.6
	 */
	public function jsfunctionSave($jsarray, $fieldid)
	{
		$obj = new stdClass;
		$obj->js_function = '';

		foreach ($jsarray as $js)
		{
			if ($js['jsoptions'] != '' && $js['jsfunctionname'] != '')
			{
				$obj->js_function .= $js['jsoptions'] . '-' . $js['jsfunctionname'] . '||';
			}

			$obj->id = $fieldid;
		}

		if (!empty($obj->js_function))
		{
			if (!$this->_db->updateObject('#__tjfields_fields', $obj, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}
		}
	}

	/**
	 * Method for Delete Field Option
	 *
	 * @param   Integer  $delete_ids  Id
	 *
	 * @return	JObject
	 *
	 * @since	1.6
	 */
	public function delete_option($delete_ids)
	{
		$db = JFactory::getDBO();

		foreach ($delete_ids as $key => $value)
		{
			$query = 'DELETE FROM #__tjfields_options
				WHERE id = "' . $value . '"';
			$db->setQuery($query);

			if (!$db->execute())
			{
				echo $db->stderr();

				return false;
			}
		}
	}

	/**
	 * Method for Delete Field Categories Mapping
	 *
	 * @param   Integer  $field_id  Id
	 * @param   String   $cats      Category
	 *
	 * @return  Boolean
	 *
	 * @since	1.6
	 */
	public function deleteFieldCategoriesMapping($field_id, $cats = array())
	{
		$db = JFactory::getDBO();
		try
		{
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('field_id') . ' = ' . $field_id
			);

			if (!empty($cats))
			{
				$conditions[] = $db->quoteName('category_id') . ' IN (' . implode(",", $cats) . ")";
			}

			$query->delete($db->quoteName('#__tjfields_category_mapping'));
			$query->where($conditions);
			$db->setQuery($query);

			$result = $db->execute();
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return 0;
		}
	}
}
