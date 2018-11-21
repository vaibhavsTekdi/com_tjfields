<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/**
 * Helper class for tjfields
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
	 * @return  string
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
	 * @return  array
	 */
	public function FetchDatavalue($data)
	{
		$content_id        = $data['content_id'];
		$client            = $data['client'];
		$query_user_string = '';

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('#__tjfields_fields_value.field_id, #__tjfields_fields_value.user_id, #__tjfields_fields.type,value FROM #__tjfields_fields_value ');
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
			if ($data->type == "radio" || $data->type == "single_select")
			{
				$fieldDataValue[$data->field_id] = new stdclass;
				$fieldDataValue[$data->field_id]->value[] = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}
			elseif ($data->type == "multi_select")
			{
				$fieldDataValue[$data->field_id]->value[] = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}
			else
			{
				$fieldDataValue[$data->field_id] = new stdclass;
				$fieldDataValue[$data->field_id]->value = $data->value;
				$fieldDataValue[$data->field_id]->field_id = $data->field_id;
			}

			$fieldDataValue[$data->field_id]->user_id = $data->user_id;
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
		$query->select($db->quoteName(array('id', 'type', 'name', 'label')));
		$query->from($db->quoteName('#__tjfields_fields'));

		if ($fname)
		{
			$query->where($db->quoteName('name') . ' = ' . $db->quote($fname));
		}
		else
		{
			$query->where($db->quoteName('id') . ' = ' . (int) $fid);
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
	 * @return  bool  Returns true if successful, and false otherwise.
	 */
	public function saveFieldsValue($data)
	{
		if (empty($data))
		{
			return false;
		}

		// Get field Id and field type.
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

		$singleSelectionFields = array('single_select', 'radio');
		$multipleSelectionFields = array('multi_select');
		$fieldsSubmitted = array();

		// Separating out the subform files data from files array
		foreach ($data['fieldsvalue'] as $k => $v)
		{
			$field_data = $this->getFieldData($k);

			if ($field_data->type === 'subform' || $field_data->type === 'ucmsubform')
			{
				$fileData = array();

				foreach ($data['fieldsvalue']['tjFieldFileField'] as $key => $value)
				{
						// Checking if the subform name is present as key of array in the files array, if present separate  the array
						if ($key === $field_data->name)
						{
							$fileData[$key] = $value;

							unset($data['fieldsvalue']['tjFieldFileField'][$key]);
						}
						else
						{
							$fileData[$key] = '';
						}
				}

				// Adding separated files array to respective subform data  by creating new variable filesData
				foreach ($v as $key => $value)
				{
					if (array_key_exists($key, $fileData[$k]))
					{
						$data['fieldsvalue'][$field_data->name][$key]['filesData'] = $fileData[$k][$key];
					}
					else
					{
						$data['fieldsvalue'][$field_data->name][$key]['filesData'] = '';
					}
				}
			}
		}

		// Values array will contain menu fields value.
		foreach ($data['fieldsvalue'] as $fname => $fvalue)
		{
			$field_data = array();

			if ($fname != 'tjFieldFileField')
			{
				$field_data = $this->getFieldData($fname);
				$insert_obj->field_id = $field_data->id;
				$fieldsSubmitted[] = $insert_obj->field_id;
			}

			// Field Data
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjfields_fields'));
			$query->where($db->quoteName('name') . ' = ' . $db->quote($fname));
			$db->setQuery($query);
			$file_field_data_check = $db->loadObject();

			if ($fname == 'tjFieldFileField')
			{
				foreach ($fvalue as $fieldName => $singleFile)
				{
					$file_field_data = $this->getFieldData($fieldName);
					$insert_obj_file->field_id = $file_field_data->id;

					if ($file_field_data->id)
					{
					if (!empty($singleFile))
					{
						if ($singleFile['error'] != 4)
						{
						JTable::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/tables");
						JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/models");
						$fieldModel = JModelLegacy::getInstance('Field', 'TjfieldsModel', array("ignore_request" => 1));

						$fieldId = (int) $file_field_data->id;
						$fieldItems = $fieldModel->getItem($fieldId);

						// Code for file size validation
						$acceptSize = $fieldItems->params['size'];

						// Upload path
						$client = explode('.', $insert_obj_file->client);
						$mimeType = explode('/', $singleFile['type']);
						$type = $mimeType[0];

						$mediaPath = JPATH_ROOT . '/media/' . $client[0] . '/' . $client[1] . '/' . $type;

						// Code for file type validation
						$acceptType = $fieldItems->params['accept'];

						// Configs for Media library
						$config = array();

						if (!empty($acceptType))
						{
							$localMime = TJMediaStorageLocal::getInstance();

							$validMIMEArray = explode(',', $acceptType);

							$validtype = array();

							foreach ($validMIMEArray as $mimeType)
							{
								$validtype[] = $localMime->getMime(strtolower(str_ireplace('.', '', $mimeType)));
							}

							$config['type'] = $validtype;
						}

						$config['uploadPath'] = $mediaPath;
						$config['size'] = $acceptSize;
						$config['saveData'] = '0';
						$media = TJMediaStorageLocal::getInstance($config);

						$returnData = $media->upload(array($singleFile));

						if ($returnData[0]['source'])
						{
							$existingFileRecordId = $this->checkRecordExistence($data, $file_field_data->id);

							$insert_obj_file->value = $returnData[0]['source'];

							if ($insert_obj_file->value)
							{
								if (!empty($existingFileRecordId))
								{
									$insert_obj_file->id = $existingFileRecordId;
									$db->updateObject('#__tjfields_fields_value', $insert_obj_file, 'id');
								}
								else
								{
									$insert_obj_file->id = '';
									$db->insertObject('#__tjfields_fields_value', $insert_obj_file, 'id');
								}
							}

							$fieldsSubmitted[] = $insert_obj_file->field_id;
						}
						else
						{
							return false;
						}
						}
					}
					}
				}
			}
			else
			{
				if (empty($file_field_data_check->accept))
				{
					// Check for duplicate entry
					$existingRecordId = $this->checkRecordExistence($data, $field_data->id);

					if (!empty($fvalue))
					{
						if ($field_data->type === 'subform' || $field_data->type === 'ucmsubform')
						{
							$this->saveSubformData($data, $fname, $field_data);
						}
						elseif (in_array($field_data->type, $multipleSelectionFields))
						{
							$this->saveMultiselectOptions($data, $fname, $field_data);
						}
						elseif (in_array($field_data->type, $singleSelectionFields))
						{
							$this->saveSingleSelectFieldValue($data, $fname, $field_data, $existingRecordId);
						}
						else
						{
							$insert_obj->value = $fvalue;

							if (!empty($existingRecordId))
							{
								$insert_obj->id = $existingRecordId;
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
								$db->quoteName('content_id') . ' = ' . (int) $data['content_id'],
								$db->quoteName('client') . " = " . $db->quote($data['client'])
							);
							$query = $db->getQuery(true);
							$query->delete($db->quoteName('#__tjfields_fields_value'));
							$query->where($conditions);
							$db->setQuery($query);
							$db->execute();
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
				$db->quoteName('content_id') . ' = ' . (int) $data['content_id'],
				$db->quoteName('client') . " = " . $db->quote($data['client'])
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
	 * @return  ARRAY|boolean
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
			$query->where($db->quoteName('content_id') . " = " . (int) $content_id);
			$query->where($db->quoteName('client') . " = " . $db->quote($client));
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
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select("id")
		->from("#__tjfields_options")
		->where($db->quoteName('field_id') . " = " . (int) $field_data->id)
		->where($db->quoteName('value') . " = " . $db->quote($currentFieldValue));
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
	 * @param   array  $postFieldData  Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $subformFname   Current subform field name
	 * @param   array  $field_data     field data
	 *
	 * @return  true
	 */
	public function saveSubformData($postFieldData, $subformFname, $field_data)
	{
		// Select all entries for __tjfields_fields_value
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->where($db->quoteName('content_id') . "=" . (int) $postFieldData['content_id']);
		$query->where($db->quoteName('field_id') . "=" . (int) $field_data->id);
		$query->where($db->quoteName('client') . "=" . $db->quote($postFieldData['client']));
		$db->setQuery($query);
		$dbFieldValue = $db->loadObjectList();

		$newFields = $postFieldData['fieldsvalue'];
		$subformField = $newFields[$subformFname];

		foreach ($subformField as $key => $value)
		{
		if (!empty($value['filesData']))
		{
				foreach ($value['filesData'] as $k => $v)
				{
				if (!empty($v['name']))
				{
				$file_field_data = $this->getFieldData($k);

				if (!empty($file_field_data))
				{
					JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_tjfields/models");
					$fieldModel = JModelLegacy::getInstance('Field', 'TjfieldsModel', array("ignore_request" => 1));

					$fieldId = (int) $file_field_data->id;
					$fieldItems = $fieldModel->getItem($fieldId);

					// Code for file type validation
					$acceptType = $fieldItems->params['accept'];
				}

				// Code for file size validation
				$acceptSize = $fieldItems->params['size'];

				// Upload path
				$client = explode('.', $postFieldData['client']);
				$mimeType = explode('/', $v['type']);
				$type = $mimeType[0];
				$mediaPath = JPATH_ROOT . '/media/' . $client[0] . '/' . $client[1] . '/' . $type;

				// Configs for Media library
				$config = array();

				if (!empty($acceptType))
				{
					$validMIMEArray = explode(',', $acceptType);

					$validtype = array();

					foreach ($validMIMEArray as $mimeType)
					{
						$localGetMime = TJMediaStorageLocal::getInstance();
						$validtype[] = $localGetMime->getMime(strtolower(str_ireplace('.', '', $mimeType)));
					}

					$config['type'] = $validtype;
				}

				$config['uploadPath'] = $mediaPath;
				$config['size'] = $acceptSize;
				$config['saveData'] = '0';

				$media = TJMediaStorageLocal::getInstance($config);

				$returnData = $media->upload(array($v));
				$subformField[$key][$k] = $returnData[0]['source'];

				unset($subformField[$key]['filesData']);
				}
				}
		}
		}

		if (!empty($dbFieldValue))
		{
			if (!empty($subformField))
			{
				$obj = new stdClass;
				$obj->field_id = $field_data->id;
				$obj->content_id = $postFieldData['content_id'];
				$obj->value = json_encode($subformField);
				$obj->client = $postFieldData['client'];
				$obj->user_id = JFactory::getUser()->id;

				$obj->id = $dbFieldValue[0]->id;
				$db->updateObject('#__tjfields_fields_value', $obj, 'id');
			}
		}
		else
		{
			$obj = new stdClass;
			$obj->field_id = $field_data->id;
			$obj->content_id = $postFieldData['content_id'];
			$obj->value = json_encode($subformField);
			$obj->client = $postFieldData['client'];
			$obj->user_id = JFactory::getUser()->id;

			$db = JFactory::getDbo();
			$db->insertObject('#__tjfields_fields_value', $obj, 'id');
		}

		return true;
	}

	/**
	 * check if the fields values are already store. so it means we need to edit the entry
	 *
	 * @param   array  $postFieldData     Post array which content (client, content_id, Fname, Fvalue, u_id)
	 * @param   array  $multiselectFname  Current multiselect field name
	 * @param   array  $field_data        field data
	 *
	 * @return  true
	 */
	public function saveMultiselectOptions($postFieldData, $multiselectFname, $field_data)
	{
		// Select all entries for __tjfields_fields_value
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tjfields_fields_value');
		$query->where($db->quoteName('content_id') . "=" . (int) $postFieldData['content_id']);
		$query->where($db->quoteName('field_id') . "=" . (int) $field_data->id);
		$query->where($db->quoteName('client') . "=" . $db->quote($postFieldData['client']));
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

			$query->select($db->quoteName('id'))
			->from($db->quoteName('#__tjfields_options'))
			->where($db->quoteName('field_id') . " = " . (int) $insert_obj->field_id)
			->where($db->quoteName('value') . " = " . $db->quote($insert_obj->value));
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

			$conditions = array(
				$db->quoteName('id') . ' IN (' . $db->quote($fieldValueEntryId) . ') '
			);

			$query->delete($db->quoteName('#__tjfields_fields_value'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->execute();
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
	public function checkRecordExistence($data, $field_id)
	{
		$content_id = (int) $data['content_id'];
		$client     = $data['client'];

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__tjfields_fields_value'));
		$query->where($db->quoteName('content_id') . ' = ' . (int) $content_id);
		$query->where($db->quoteName('client') . ' = ' . $db->quote($client));

		if (!empty($field_id))
		{
			$query->where($db->quoteName('field_id') . ' = ' . (int) $field_id);
		}

		$db->setQuery($query);
		$existingRecordId = $db->loadresult();

		return $existingRecordId;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $field_id      field if
	 * @param   string  $option_value  option value
	 *
	 * @return array Option for the particular field
	 */
	public function getOptions($field_id, $option_value = '')
	{
		if ($option_value != '')
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('options','default_option','value')));
			$query->from($db->quoteName('#__tjfields_options'));
			$query->where($db->quoteName('field_id') . ' = ' . (int) $field_id);

			$new_option_value = json_decode($option_value);

			if ($new_option_value != '')
			{
				if (is_array($new_option_value))
				{
					$option_value_string = implode(",", $db->quote($new_option_value));
					$query->where($db->quoteName('value') . 'IN (' . $option_value_string . ')');
				}
				else
				{
					$query->where($db->quoteName('value') . '=' . $db->quote($new_option_value));
				}
			}
			else
			{
				// Radio.
				$query->where($db->quoteName('value') . '=' . $db->quote($option_value));
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
	 * @param   string  $client  Get all fields based on client
	 *
	 * @return array|string
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
			$query->where($db->quoteName('f.client') . "=" . $db->quote($client));
			$query->where($db->quoteName('f.state') . " = 1");
			$db->setQuery($query);
			$universalFields = $db->loadObjectlist();
		}

		return $universalFields;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $client  Get all fields based on client
	 *
	 * @return object
	 */
	public function getCategorys($client)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName('#__categories'));
		$query->where($db->quoteName('extension') . ' = ' . $db->quote($client));
		$query->where($db->quoteName('published') . ' = 1');

		$db->setQuery($query);
		$categorysList = $db->loadObjectlist();

		return $categorysList;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @param   string  $client       Get all fields based on client
	 * @param   string  $category_id  Get all fields for selected category
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

			$query->where($db->quoteName('f.client') . "=" . $db->quote($client));
			$query->where($db->quoteName('f.filterable') . " = 1");
			$query->where($db->quoteName('f.state') . " = 1");
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

				$queryCat->where($db->quoteName('f.client') . "=" . $db->quote($client));
				$queryCat->where($db->quoteName('f.filterable') . " = 1");
				$queryCat->where($db->quoteName('f.state') . " = 1");
				$queryCat->where('fv.option_id IS NOT NULL');
				$queryCat->where("f.type IN ('single_select','multi_select', 'radio')");

				$queryCat->JOIN('INNER', '#__tjfields_category_mapping AS fcm ON fcm.field_id = f.id');
				$queryCat->where($db->quoteName('fcm.category_id') . " = " . $category_id);

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
		$fields_value_str = $jinput->get("tj_fields_value", '', "RAW");

		if ($fields_value_str)
		{
			$fields_value_str = explode(',', $fields_value_str);
			$fields_value_str = array_filter($fields_value_str, 'trim');
			$fields_value_str = implode(',', $fields_value_str);
		}

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
	 * @return array|boolean
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

	/**
	 * Fuction to get field id from file path
	 *
	 * @param   STRING  $filePath  media file path
	 *
	 * @return string|boolean
	 */
	public function getFileIdFromFilePath($filePath)
	{
		if (!empty($filePath))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__tjfields_fields_value'));
			$query->where($db->quoteName('value') . "=" . $db->quote($filePath));
			$db->setQuery($query);
			$mediaId = $db->loadResult();

			return $mediaId;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fuction to get file path from id
	 *
	 * @param   STRING  $mediaId  media id
	 *
	 * @return string|boolean
	 */
	public function getMediaPathFromId($mediaId)
	{
		if (!empty($mediaId))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('value'));
			$query->from($db->quoteName('#__tjfields_fields_value'));
			$query->where($db->quoteName('id') . "=" . (INT) $mediaId);
			$db->setQuery($query);
			$mediaPath = $db->loadResult();

			return $mediaPath;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get media URL.
	 *
	 * @param   STRING  $filePath       media file path
	 * @param   STRING  $extraUrlPrams  extra url params
	 *
	 * @return  string|boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function getMediaUrl($filePath, $extraUrlPrams = '')
	{
		if (!empty($filePath))
		{
			// If url extra param is present
			if (!empty($extraUrlPrams))
			{
				$extraUrlPrams = '&' . $extraUrlPrams;
			}

			// Here, fpht means file encoded path
			$encodedPath = base64_encode($filePath);
			$basePathLink = 'index.php?option=com_tjfields&task=getMedia&fpht=';
			$mediaURLlink = JUri::root() . substr(JRoute::_($basePathLink . $encodedPath . $extraUrlPrams), strlen(JUri::base(true)) + 1);

			return $mediaURLlink;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to convert file size from MB to Bytes.
	 *
	 * @param   int  $mb  file size in mb
	 *
	 * @return  int|bool  True on success.
	 *
	 * @since   3.2
	 */
	public function formatSizeUnits($mb)
	{
		if (!empty($mb))
		{
			// 1 Megabyte is equal to 1048576 bytes (binary)
			$bytes = $mb * 1048576;

			return $bytes;
		}
		else
		{
			return false;
		}
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
		$fields_value_table = JTable::getInstance('Fieldsvalue', 'TjfieldsTable');

		$fields_value_table->load(array('id' => $data['valueId']));

		$subData = new stdClass();
		$fieldId = 0;

		if ($data['isSubformField'] == 1)
		{
			$subData = json_decode($fields_value_table->value);

			foreach ($subData as $value)
			{
				$subformData = (array) $value;

				if (in_array($data['filePath'], $subformData))
				{
					$fileUser = $fields_value_table->user_id;
				}
			}

			// Check for file field is of subform or ucmsubform
			if ($data['subformFileFieldId'])
			{
				$fieldId = $data['subformFileFieldId'];
			}
			else
			{
				$fieldId = $fields_value_table->field_id;
			}
		}
		else
		{
			if ($data['filePath'] === $fields_value_table->value)
			{
				$fileUser = $fields_value_table->user_id;
				$fieldId = $fields_value_table->field_id;
			}
		}

		$file_extension = strtolower(substr(strrchr($data['filePath'], "."), 1));
		$localGetMime = TJMediaStorageLocal::getInstance();

		$ctype = $localGetMime->getMime($file_extension);

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
					if ($data['isSubformField'] == 1)
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

					$fields_obj->id = $fields_value_table->id;
					$db->updateObject('#__tjfields_fields_value', $fields_obj, 'id');

					return true;
				}

				return false;
			}

			return false;
		}

		return false;
	}

	/**
	 * This define the  language constant which you have use in js file.
	 *
	 * @since   1.0
	 * @return   null
	 */
	public static function getLanguageConstantForJs()
	{
		JText::script('COM_TJFIELDS_FILE_DELETE_CONFIRM');
		JText::script('COM_TJFIELDS_FILE_ERROR_MAX_SIZE');
	}
}
