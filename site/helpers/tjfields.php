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

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('#__tjfields_fields_value.field_id,#__tjfields_fields.type,value FROM #__tjfields_fields_value ');
		$query->join('INNER', $db->qn('#__tjfields_fields') . ' ON (' .
		$db->qn('#__tjfields_fields.id') . ' = ' . $db->qn('#__tjfields_fields_value.field_id') . ')');

		$query->where('#__tjfields_fields_value.content_id=' . $content_id);
		$query->where('#__tjfields_fields_value.client="' . $client . '" ' . $query_user_string);
		$query->where('#__tjfields_fields.state=' . $db->quote("1"));
		$db->setQuery($query);

		$field_data_value = $db->loadObjectlist();

		$fieldDataValue = array();

		foreach ($field_data_value as $k => $data)
		{
			if ($data->type == "radio" || $data->type == "single_select" || $data->type == "multi_select")
			{
				$fieldDataValue[$data->field_id]->value[] = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}
			else
			{
				$fieldDataValue[$data->field_id]->value = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}
		}

		// Check if the field type is list or radio (fields which have option)
		foreach ($fieldDataValue as $fdata)
		{
			$fieldData = $this->getFieldData('', $fdata->field_id);

			if (!empty($fieldData))
			{
				if ($fieldData->type == 'single_select' || $fieldData->type == 'multi_select' || $fieldData->type == 'radio')
				{
					$extra_options = $this->getOptions($fdata->field_id, json_encode($fdata->value));
					$fdata->value  = $extra_options;
				}

				$fdata->type  = $fieldData->type;
				$fdata->name  = $fieldData->name;
				$fdata->label = $fieldData->label;
			}
		}

		return $fieldDataValue;
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
		$query->select('id,type,name,label FROM #__tjfields_fields');

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

		$insert_obj_file = new stdClass;
		$insert_obj_file->content_id = $data['content_id'];
		$insert_obj_file->user_id    = $data['user_id'];
		$insert_obj_file->email_id   = '';
		$insert_obj_file->client     = $data['client'];

		$singleSelectionFields = array("single_select", "radio");
		$multipleSelectionFields = array("multi_select");
		$fieldsSubmitted = array();

		// Values array will contain menu fields value.
		foreach ($data['fieldsvalue'] as $fname => $fvalue)
		{
			$field_data           = $this->getFieldData($fname);
			$insert_obj->field_id = $field_data->id;
			$fieldsSubmitted[] = $insert_obj->field_id;

			// Added By KOMAL TEMP
			// Field Data
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('* FROM #__tjfields_fields');
			$query->where('name="' . $fname . '"');
			$db->setQuery($query);
			$file_field_data_check = $db->loadObject();

			if ($fname == 'tjFieldFileField')
			{
				foreach ($fvalue as $fieldName => $singleFile)
				{
					// Field Data
					$db    = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->select('* FROM #__tjfields_fields');
					$query->where('name="' . $fieldName . '"');
					$db->setQuery($query);
					$file_field_data = $db->loadObject();

					$insert_obj_file->field_id = $file_field_data->id;

					if (!empty($singleFile))
					{
						$filename = $this->uploadFile($singleFile, $insert_obj_file, $file_field_data);

						if ($filename)
						{
							$if_edit_file_id        = $this->checkForAlreadyexitsDetails($data, $file_field_data->id);

							$client = explode('.', $insert_obj_file->client);

							$insert_obj_file->value = '/media/' . $client[0] . '/' . $client[1] . '/' . $filename;

							if ($insert_obj_file->value)
							{
								if ($if_edit_file_id)
								{
									$insert_obj_file->id = $if_edit_file_id;
									$result = $db->updateObject('#__tjfields_fields_value', $insert_obj_file, 'id');
								}
								else
								{
									$insert_obj_file->id = '';
									$result = $db->insertObject('#__tjfields_fields_value', $insert_obj_file, 'id');
								}
							}
						}

						$fieldsSubmitted[] = $insert_obj_file->field_id;
					}
				}
			}
			else
			{
				if (empty($file_field_data_check->accept))
				{
					// Check for duplicate entry
					$if_edit_id = $this->checkForAlreadyexitsDetails($data, $field_data->id);

					if (!empty($fvalue))
					{
						if (in_array($field_data->type, $multipleSelectionFields))
						{
							$this->saveMultiselectOptions($data, $fname, $field_data);
						}
						elseif (in_array($field_data->type, $singleSelectionFields))
						{
							$this->saveSingleSelectFieldValue($data, $fname, $field_data, $if_edit_id);
						}
						else
						{
							$insert_obj->value = $fvalue;

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
					else
					{
						if (isset($field_data->id) && isset($data['content_id']))
						{
							// Delete entry is field is deselected
							$conditions = array(
								$db->quoteName('field_id') . ' = ' . $field_data->id,
								$db->quoteName('content_id') . ' = ' . $data['content_id'],
								$db->quoteName('client') . " = '" . $data['client'] . "'"
							);

							$query = $db->getQuery(true);

							$query->delete($db->quoteName('#__tjfields_fields_value'));
							$query->where($conditions);

							$db->setQuery($query);

							$result = $db->execute();
						}
					}
				}
			}
		}

		$fieldsSubmitted = array_filter($fieldsSubmitted);

		$unsubmittedFields = $this->getUnsubmittedFields($data['content_id'], $data['client'], $fieldsSubmitted);

		// Delete Values of unsubmitted fields
		foreach ($unsubmittedFields as $unsubmittedField)
		{
			$db = JFactory::getDbo();

			// Delete entry if field is deselected
			$conditions = array(
				$db->quoteName('field_id') . ' = ' . $unsubmittedField,
				$db->quoteName('content_id') . ' = ' . $data['content_id'],
				$db->quoteName('client') . " = '" . $data['client'] . "'"
			);

			$query = $db->getQuery(true);

			$query->delete($db->quoteName('#__tjfields_fields_value'));
			$query->where($conditions);

			$db->setQuery($query);

			$db->execute();
		}

		return true;
	}

	/**
	 * Function to get sunsubmitted fields value
	 *
	 * @param   INT     $content_id       content id
	 * @param   STRING  $client           client
	 * @param   ARRAY   $fieldsSubmitted  array of fields submitted
	 *
	 * @return  true
	 */
	public function getUnsubmittedFields($content_id, $client, $fieldsSubmitted)
	{
		if (!empty($content_id) && !empty($client))
		{
			// Field Data
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('field_id'));
			$query->from($db->quoteName('#__tjfields_fields_value'));
			$query->where($db->quoteName('content_id') . " = '" . $content_id . "'");
			$query->where($db->quoteName('client') . " = '" . $client . "'");
			$db->setQuery($query);
			$dataSavedFields = $db->loadColumn();

			$unsubmittedFields = array_diff($dataSavedFields, $fieldsSubmitted);

			return $unsubmittedFields;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Function to upload file
	 *
	 * @param   string  $singleFile       name of field
	 * @param   string  $insert_obj_file  file object
	 * @param   string  $file_field_data  data
	 *
	 * @return  object
	 */
	public function uploadFile($singleFile, $insert_obj_file, $file_field_data)
	{
		$app             = JFactory::getApplication();
		$user = JFactory::getUser();
		$user_id = $user->id;
		$username = $string = preg_replace('/\s+/', '', $user->name);

		$title = $singleFile['name'];
		$title = $string = preg_replace('/\s+/', '', $title);

		jimport('joomla.filesystem.file');

		// Check if the server found any error.
		$fileError = $singleFile['error'];
		$message   = '';

		if ($fileError > 0 && $fileError != 4)
		{
			switch ($fileError)
			{
				case 1:
					$message = JText::_('COM_TJFIELDS_FILE_UPLOAD_ERROR_SIZE_EXCEED');
					break;
				case 2:
					$message = JText::_('COM_TJFIELDS_FILE_UPLOAD_ERROR_SIZE_EXCEED1');
					break;
				case 3:
					$message = JText::_('COM_TJFIELDS_FILE_UPLOAD_ERROR_SIZE_EXCEED2');
					break;
			}

			if ($message != '')
			{
				$app->enqueueMessage($message, 'warning');

				return false;
			}
		}
		elseif ($fileError == 4)
		{
			if (isset($singleFile['name']))
			{
				$filename = $singleFile['name'];
			}
		}
		else
		{
			// Check for filesize
			$fileSize = $singleFile['size'];

			if ($fileSize > 15728640)
			{
				$app->enqueueMessage('This <strong>' . $title . '</strong> file size is greater than 15MB', 'warning');

				return false;
			}

			$okMIMETypes    = 'pdf,PDF,doc,DOC,docx,DOCX,xls,XLS,xlsx,XLSX,jpeg,JPEG,png,PNG,jpg,JPG';
			$validMIMEArray = explode(',', $okMIMETypes);

			$client = explode('.', $insert_obj_file->client);

			$filename  = JFile::stripExt($singleFile['name']);
			$extension = JFile::getExt($singleFile['name']);

			jimport('joomla.filesystem.file');
			$fileMime = JFile::getExt($singleFile['name']);

			if (!in_array($fileMime, $validMIMEArray))
			{
				$app->enqueueMessage('This <strong>' . $title . '</strong> file type is not allowed for file ' . $filename . '.' . $extension, 'warning');

				return false;
			}

			// Replace any special characters in the filename

			$random_string = rand();
			$filename = $client[1] . "_" . $username . "_" . $random_string;

			$filename = $filename . '.' . $extension;

			$uploadPath = JPATH_ROOT . '/media/' . $client[0] . '/' . $client[1] . '/' . $filename;

			$fileTemp = $singleFile['tmp_name'];

			if (!JFile::exists($uploadPath))
			{
				if (!JFile::upload($fileTemp, $uploadPath))
				{
					$app->enqueueMessage('Error moving file', 'warning');

					return false;
				}
			}

			return $filename;
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $postFieldData  Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $fieldName      Current multiselect field name
	 * @param   array  $field_data     field data
	 * @param   array  $updateId       Previous record id
	 *
	 * @return  array
	 */
	public function saveSingleSelectFieldValue($postFieldData, $fieldName, $field_data, $updateId = 0)
	{
		$currentFieldValue = $postFieldData['fieldsvalue'][$fieldName];
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$conditions = array($db->quoteName('id') . ' IN (' . $fieldValueEntryId . ') ');

		$query->select("id")
		->from("#__tjfields_options")
		->where("field_id = " . $field_data->id)
		->where("value = '" . $currentFieldValue . "'");
		$db->setQuery($query);

		$option_id = $db->loadResult();

		// Save field value
		$insert_obj = new stdClass;
		$insert_obj->field_id = $field_data->id;

		$insert_obj->content_id = $postFieldData['content_id'];
		$insert_obj->user_id    = $postFieldData['user_id'];
		$insert_obj->email_id   = '';
		$insert_obj->client     = $postFieldData['client'];
		$insert_obj->value = $currentFieldValue;
		$insert_obj->option_id = $option_id;

		if ($updateId)
		{
			$insert_obj->id = $updateId;
			$db->updateObject('#__tjfields_fields_value', $insert_obj, 'id');
		}
		else
		{
			$insert_obj->id = '';
			$db->insertObject('#__tjfields_fields_value', $insert_obj, 'id');
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $postFieldData     Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $multiselectFname  Current multiselect field name
	 * @param   array  $field_data        field data
	 *
	 * @return  array
	 */
	public function saveMultiselectOptions($postFieldData, $multiselectFname, $field_data)
	{
		// Select all entries for __tjfields_fields_value
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->where('content_id=' . $postFieldData['content_id']);
		$query->where('field_id=' . $field_data->id);
		$query->where('client="' . $postFieldData['client'] . '"');
		$db->setQuery($query);
		$dbFieldValue = $db->loadObjectList("id");

		$newFields = $postFieldData['fieldsvalue'];
		$multiselectField = $newFields[$multiselectFname];

		if (!empty($dbFieldValue))
		{
			// Check for update
			foreach ($dbFieldValue as $key => $dbField)
			{
				// Current field is present then remove from both list
				if (in_array($dbField->value, $multiselectField))
				{
					unset($dbFieldValue[$key]);
					$multiselectField = array_diff($multiselectField, array($dbField->value));
				}
			}

			// Now $dbFieldValue contains fields to delete. newField contain field to insert
			if (!empty($dbFieldValue))
			{
				$delFieldValueIdsArray = array_keys($dbFieldValue);
				$delFieldValueIds = implode(',', $delFieldValueIdsArray);

				$this->deleteFieldValueEntry($delFieldValueIds);
			}

			if (!empty($multiselectField))
			{
				foreach ($multiselectField as $fieldValue)
				{
					$obj = new stdClass;
					$obj->field_id = $field_data->id;
					$obj->content_id = $postFieldData['content_id'];
					$obj->value = $fieldValue;
					$obj->client = $postFieldData['client'];
					$obj->user_id = JFactory::getUser()->id;

					$this->addFieldValueEntry($obj);
				}
			}
		}
		else
		{
			// New: add all options
			foreach ($multiselectField as $fieldValue)
			{
				$obj = new stdClass;
				$obj->field_id = $field_data->id;
				$obj->content_id = $postFieldData['content_id'];
				$obj->value = $fieldValue;
				$obj->client = $postFieldData['client'];
				$obj->user_id = JFactory::getUser()->id;

				$this->addFieldValueEntry($obj);
			}
		}

		return true;
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   object  $insert_obj  Partially created object.
	 *
	 * @return  array
	 */
	public function addFieldValueEntry($insert_obj)
	{
		if (!empty($insert_obj))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$conditions = array($db->quoteName('id') . ' IN (' . $fieldValueEntryId . ') ');

			$query->select("id")
			->from("#__tjfields_options")
			->where("field_id = " . $insert_obj->field_id)
			->where("value = '" . $insert_obj->value . "'");
			$db->setQuery($query);

			$insert_obj->option_id = $db->loadResult();

			if (!empty($insert_obj->option_id))
			{
				// Insert into db
				$db = JFactory::getDbo();
				$db->insertObject('#__tjfields_fields_value', $insert_obj, 'id');
			}
		}
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $fieldValueEntryId  Ids to delete the entries from table #__tjfields_fields_value
	 *
	 * @return  array
	 */
	public function deleteFieldValueEntry($fieldValueEntryId)
	{
		if (!empty($fieldValueEntryId))
		{
			$db = JFactory::getDbo();

			$query = $db->getQuery(true);

			// Delete all custom keys for user 1001.
			$conditions = array(
				$db->quoteName('id') . ' IN (' . $fieldValueEntryId . ') '
			);

			$query->delete($db->quoteName('#__tjfields_fields_value'));
			$query->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();
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
		$query->where('content_id=' . $content_id . ' AND client="' . $client . '"');

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
		if ($option_value != '')
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('options,default_option,value FROM #__tjfields_options');
			$query->where('field_id=' . $field_id);

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

			$db->setQuery($query);
			$extra_options = $db->loadObjectlist();
		}
		else
		{
			$extra_options = array();
			$obj = new stdclass;
			$obj->id = '';
			$obj->options = '';
			$obj->default_option = '';
			$obj->value = '';

			$extra_options[] = $obj;
		}

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
			$query->select('DISTINCT * FROM #__tjfields_fields AS f');
			$query->where('NOT EXISTS (select * FROM #__tjfields_category_mapping AS cm where f.id=cm.field_id)');
			$query->where('f.client="' . $client . '"');
			$query->where('f.state=1');
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
	public function getFilterableFields($client, $category_id = '')
	{
		$coreFields = '';

		if (!empty($client))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT fv.option_id, f.id,f.name, f.label,fv.value,fo.options');
			$query->FROM("#__tjfields_fields AS f");
			$query->JOIN('INNER', '#__tjfields_fields_value AS fv ON fv.field_id = f.id');
			$query->JOIN('INNER', '#__tjfields_options AS fo ON fo.id = fv.option_id');

			$query->where('f.client="' . $client . '"');
			$query->where('f.filterable=1');
			$query->where('f.state=1');
			$query->where('fv.option_id IS NOT NULL');
			$query->where("f.type IN ('single_select','multi_select', 'radio')");

			// Doesn't have mapped any category
			$query->where('NOT EXISTS (select * FROM #__tjfields_category_mapping AS cm where f.id=cm.field_id)');

			$query->order('f.ordering');
			$db->setQuery($query);
			$coreFields = $db->loadObjectlist("option_id");
			$allFields = $coreFields;

			// Type cast value of category
			$category_id = (int) $category_id;

			// If category related field present
			if (!empty($category_id) && is_int($category_id))
			{
				$db    = JFactory::getDbo();
				$queryCat = $db->getQuery(true);
				$queryCat->select('DISTINCT fv.option_id, f.id,f.name, f.label,fv.value,fo.options');
				$queryCat->FROM("#__tjfields_fields AS f");
				$queryCat->JOIN('INNER', '#__tjfields_fields_value AS fv ON fv.field_id = f.id');
				$queryCat->JOIN('INNER', '#__tjfields_options AS fo ON fo.id = fv.option_id');

				$queryCat->where('f.client="' . $client . '"');
				$queryCat->where('f.filterable=1');
				$queryCat->where('f.state=1');
				$queryCat->where('fv.option_id IS NOT NULL');
				$queryCat->where("f.type IN ('single_select','multi_select', 'radio')");

				$queryCat->JOIN('INNER', '#__tjfields_category_mapping AS fcm ON fcm.field_id = f.id');
				$queryCat->where('fcm.category_id=' . $category_id);

				$queryCat->order('f.ordering');
				$db->setQuery($queryCat);
				$catFields = $db->loadObjectlist("option_id");

				// Check for duplication for worse
				if (!empty($catFields))
				{
					foreach ($catFields as $key => $cfield)
					{
						// Add element if not exist
						if (!array_key_exists($key, $allFields))
						{
							$allFields[$key] = $cfield;
						}
					}
				}
			}
		}

		return $allFields;
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
		$fieldOptions = array();

		if ($fields_value_str)
		{
			$fields_value_str = explode(',', $fields_value_str);
			$fieldOptions = $fields_value_str = array_filter($fields_value_str, 'trim');
			$fields_value_str = implode(',', $fields_value_str);
		}

		/*$data['fields_value'] =  array(19,14,17);*/
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Selected field value
		if (!empty($fields_value_str))
		{
			$TjfieldsHelper = new TjfieldsHelper;
			$fieldAndFieldOptionsList = $TjfieldsHelper->getFieldAndFieldOptionsList($fields_value_str);

			// If only one fields options are there then no need to join
			if (count($fieldAndFieldOptionsList) == 1)
			{
				foreach ($fieldAndFieldOptionsList as $fieldId => $fFieldAndFieldOptions)
				{
					if (!empty($fFieldAndFieldOptions))
					{
						$query->select('DISTINCT fv1.content_id');
						$query->from('#__tjfields_fields_value AS fv1');
						$query->where("fv1.option_id IN (" . $fFieldAndFieldOptions->optionsStr . ")");

						return $query;
					}
				}
			}
			else
			{
				$query->select('DISTINCT fv1.content_id');
				$fromFlag = 0;
				$i = 1;

				foreach ($fieldAndFieldOptionsList as $fieldId => $fFieldAndFieldOptions)
				{
					if (empty($fromFlag))
					{
						$query->from('#__tjfields_fields_value AS fv' . $i);
						$query->where("fv" . $i . ".option_id IN (" . $fFieldAndFieldOptions->optionsStr . ")");

						$fromFlag = 1;
					}
					else
					{
						$query->join('INNER', $db->qn('#__tjfields_fields_value') . ' AS fv' . $i . ' ON (' .
						$db->qn('fv' . $i . '.content_id') . ' = ' . $db->qn('fv' . ($i - 1 ) . '.content_id') . ')');
						$query->where("fv" . $i . ".option_id IN (" . $fFieldAndFieldOptions->optionsStr . ")");
					}

					$i++;
				}
			}

			$query->where('fv1.client="' . $client . '" ');

			return $query;
		}
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
		}
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   STRING  $options  Field's Option id's string
	 *
	 * @return object
	 */
	public function getFieldAndFieldOptionsList($options)
	{
		$fieldAndFieldOptionsList = array();

		if (!empty($options))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('field_id, GROUP_CONCAT( id ) AS optionsStr ');
			$query->FROM('#__tjfields_options as fo');
			$query->where('fo.id  IN  (' . $options . ')');
			$query->group('fo.field_id');

			$db->setQuery($query);
			$fieldAndFieldOptionsList = $db->loadObjectlist('field_id');
		}

		return $fieldAndFieldOptionsList;
	}

	/**
	 * Get filter results.
	 *
	 * @return string
	 */
	public function getFilterResults()
	{
		$db = JFactory::getDbo();
		$jinput  = JFactory::getApplication()->input;

		// Function will return -1 when no content found according to selected fields in filter
		$tjfieldIitem_ids = "-1";
		$tj_mod_filter_cat = $jinput->get("ModFilterCat", "prod_cat");
		$category_id = $jinput->get($tj_mod_filter_cat);
		$fields_value_str = $jinput->get("tj_fields_value", '', "RAW");

		if (!empty($fields_value_str))
		{
			$tjquery = $this->buildFilterModuleQuery();
			$db->setQuery($tjquery);
			$client_ids = $db->loadColumn();

			if (!empty($client_ids))
			{
				$tjfieldIitem_ids = implode(",", $client_ids);
			}

			// Return all the content ids which are matching the filters condition
			return $tjfieldIitem_ids;
		}
		else
		{
			// Return -2 when no filters are selected
			return '-2';
		}
	}

	/**
	 * Get fields for given client
	 *
	 * @param   STRING  $client  client
	 *
	 * @return object
	 */
	public function getClientFields($client)
	{
		if (!empty($client))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('name');
			$query->from('#__tjfields_fields');
			$query->quoteName('client') . ' = ' . $db->quote($client);
			$db->setQuery($query);
			$clientFields = $db->loadColumn();

			return $clientFields;
		}
		else
		{
			return false;
		}
	}
}
