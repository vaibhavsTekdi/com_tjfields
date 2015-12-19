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
		$query->select('fg.name as tabname, fg.id as tabid, fv.field_id, fv.value FROM #__tjfields_fields_value as fv');
		$query->leftjoin('#__tjfields_fields as f ON f.id = fv.field_id');
		$query->leftjoin('#__tjfields_groups as fg ON fg.id = f.group_id');
		$query->where('f.state = 1');
		$query->where('fg.state = 1');
		$query->where('fv.content_id=' . $content_id . ' AND fv.client="' . $client . '" ' . $query_user_string);
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

			/* Commented for : deleting records if value is kept empty */
			// #if (!empty($fvalue))
			// {
				if (!is_array($fvalue))
				{
					$insert_obj->value = $fvalue;
				}
				else
				{
					if ($field_data->type == 'file' )
					{
						if ($if_edit_id)
						{
							// Get the field values
							$field_data_details = $this->getFieldValue($if_edit_id);
						}

						jimport('joomla.filesystem.folder');
						jimport('joomla.filesystem.file');

						// Get Files
						$input = JFactory::getApplication()->input;
						$filedata = $input->post->files->get('jform', '', 'array');

						// If any file exist in the post
						if (empty($filedata[$fname]['name']))
						{
							if ($if_edit_id)
							{
								$insert_obj->value = $field_data_details->value;
							}
						}
						else
						{
							$random_string = rand();
							$filename = JFile::makeSafe($filedata[$fname]['name']);

							// Cleans the name of the file by removing wierd characters
							$filename = strtolower($filename);
							$filename = preg_replace('/\s/', '_', $filename);
							$filename = $random_string . '_' . $filename;

							$folderpath = '/media/com_tjfields/';
							$path = JPATH_SITE . $folderpath;
							$src = $filedata[$fname]['tmp_name'];

							$dest = $path . $filename;

							// The file has successfully been uploaded :)
							if (JFile::upload($src, $dest))
							{
								// Folderpath to be stored in DB
								$fvalue = $folderpath . $filename;
								$insert_obj->value = $fvalue;

								// Check if file already exist
								if (!empty($field_data_details->value))
								{
									// Delete Existing file
									JFile::delete(JPATH_ROOT . $field_data_details->value);
								}
							}
							else
							{
								return false;
							}
						}
					}
					else
					{
						$insert_obj->value = json_encode($fvalue);
					}
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

				if (empty($insert_obj->value))
				{
					// Delete data
					$query = $db->getQuery(true);

					// Delete all custom keys for user 1001.
					$conditions = array(
						$db->quoteName('content_id') . ' = ' . $db->quote($insert_obj->content_id),
						$db->quoteName('field_id') . ' = ' . $db->quote($insert_obj->field_id),
						$db->quoteName('value') . ' = ""'
					);

					$query->delete($db->quoteName('#__tjfields_fields_value'));
					$query->where($conditions);

					$db->setQuery($query);

					$result = $db->execute();
				}
			// }
		}
	}

	/**
	 * Get field values
	 *
	 * @param   int  $fid  id of field
	 *
	 * @return  object
	 */
	public function getFieldValue($fid)
	{
		if (!empty($fid))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('a.*');
			$query->from('#__tjfields_fields_value as a');
			$query->where('id=' . $fid);
			$db->setQuery($query);
			$field_data = $db->loadObject();
		}

		return $field_data;
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
		$query->select('options,default_option,value FROM #__tjfields_options');
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
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('* FROM #__tjfields_fields');
		$query->where('client="' . $client . '"');
		$query->where('state=1');
		$db->setQuery($query);
		$universalAttendeeFields = $db->loadObjectlist();

		return $universalAttendeeFields;
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
}
