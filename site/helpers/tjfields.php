<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

defined('_JEXEC') or die;

class TjfieldsHelper
{
	public static function myFunction()
	{
		$result = 'Something';
		return $result;
	}


	/**
	 * Function used for renderring. fetching value
	 *
	 * @return - values for all the fields.
	 */
	public function FetchDatavalue($data)
	{
		$content_id = $data['content_id'];
		$client = $data['client'];
		$query_user_string='';

		if(isset($data['user_id']))
		{
			$user_id = $data['user_id'];
			$query_user_string = " AND user_id=".$user_id;
		}

		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('field_id,value FROM #__tjfields_fields_value');
		$query->where('content_id='.$content_id.' AND client="'.$client.'" '.$query_user_string);
		$db->setQuery($query);
		$field_data_value = $db->loadObjectlist();

		//check if the field type is list or radio (fields which have option)
		foreach($field_data_value as $fdata)
		{
			$field_data = $this->getFieldData('',$fdata->field_id);

			if($field_data->type=='single_select' || $field_data->type=='multi_select' || $field_data->type=='radio' || $field_data->type=='checkbox')
			{
				$extra_options= $this->getOptions($fdata->field_id,$fdata->value);
				$fdata->value=$extra_options;
			}

			$fdata->type=$field_data->type;
			$fdata->name=$field_data->name;
			$fdata->label=$field_data->label;
		}
		return $field_data_value;
	}

	/**
	 * Get field Id and type.
	 *
	 * @return object.
	 */
	public function getFieldData($fname='',$fid='')
	{
		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('id,type,name,label FROM #__tjfields_fields');
		if($fname)
		{
			$query->where('name="'.$fname.'"');
		}
		else
		{
			$query->where('id='.$fid);
		}
		$db->setQuery($query);
		$field_data = $db->loadObject();
		return $field_data;
	}

	/**
	 * Save fields.
	 * value pass to the function -- post array which content (client, content_id, Fname, Fvalue, u_id)
	 *
	 * Return true
	 */
	public function saveFieldsValue($data)
	{
		if(empty($data))
		{
			return false;
		}

		// Get field Id and field type.
		$db=JFactory::getDbo();
		$insert_obj = new stdClass();

		$insert_obj->content_id 			=$data['content_id'];
		$insert_obj->user_id	=	$data['user_id'];
		$insert_obj->email_id ='';
		$insert_obj->client =$data['client'];

		// values array will contain manu fields value.
		foreach($data['fieldsvalue'] as $fname=>$fvalue)
		{
			$field_data = $this->getFieldData($fname);
			$insert_obj->field_id 	=$field_data->id;
			//check for duplicate entry
			$if_edit_id=$this->checkForAlreadyexitsDetails($data,$field_data->id);
			if(!is_array($fvalue))
			{
				$insert_obj->value 		=$fvalue;
			}
			else
			{
				$insert_obj->value = json_encode($fvalue);
			}

			if($if_edit_id)
			{
				$insert_obj->id 			=$if_edit_id;
				$db->updateObject('#__tjfields_fields_value', $insert_obj,'id');
			}
			else
			{
				$insert_obj->id 			='';
				$db->insertObject('#__tjfields_fields_value', $insert_obj,'id');
			}
		}


	}


	/**
	 * check if the fields values are already store.
	 * so it means we need to edit the entry
	 *
	 * return true or false
	 */
	public function checkForAlreadyexitsDetails($data,$field_id)
	{
		$content_id= $data['content_id'];
		$client= $data['client'];
		$user_id=$data['user_id'];

		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('id FROM #__tjfields_fields_value');
		$query->where('content_id='.$content_id.' AND client="'.$client.'" AND user_id='.$user_id.' AND field_id='.$field_id);
		$db->setQuery($query);
		$is_edit = $db->loadresult();
		return $is_edit;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @return array of option for the particular field
	 */
	public function getOptions($field_id,$option_value='')
	{
		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('options,default_option,value FROM #__tjfields_options');
		$query->where('field_id='.$field_id);

		if(!empty($option_value))
		{
			$query->where('value='.$option_value);
		}

		$db->setQuery($query);
		$extra_options = $db->loadObjectlist();
		return $extra_options;
	}

	/**
	 * Get universal attendee fields
	 *
	 */
	public function getUniversalFields($client)
	{
		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('* FROM #__tjfields_fields');
		$query->where('client="'.$client.'"');
		$db->setQuery($query);
		$universalAttendeeFields = $db->loadObjectlist();
		return $universalAttendeeFields;
	}
}

