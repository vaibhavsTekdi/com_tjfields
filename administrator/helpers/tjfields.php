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

/**
 * Tjfields helper.
 */
class TjfieldsHelper
{
	/**
	 * Configure the Linkbar.
	 */
	public static function addSubmenu($vName = '')
	{
		if(JVERSION >= '3.0')
		{
			JHtmlSidebar::addEntry(
				JText::_('COM_TJFIELDS_TITLE_FIELDS'),
				'index.php?option=com_tjfields&view=fields',
				$vName == 'fields'
			);
			JHtmlSidebar::addEntry(
				JText::_('COM_TJFIELDS_TITLE_GROUPS'),
				'index.php?option=com_tjfields&view=groups',
				$vName == 'groups'
			);
		}
		else
		{
			JSubMenuHelper::addEntry(
				JText::_('COM_TJFIELDS_TITLE_FIELDS'),
				'index.php?option=com_tjfields&view=fields',
				$vName == 'fields'
			);
			JSubMenuHelper::addEntry(
				JText::_('COM_TJFIELDS_TITLE_GROUPS'),
				'index.php?option=com_tjfields&view=groups',
				$vName == 'groups'
			);
		}

	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 * @since	1.6
	 */
	public static function getActions()
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		$assetName = 'com_tjfields';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);
		//die('asdasd');
		foreach ($actions as $action) {
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}


	/**
	 * Get the data that has to bo store for a particlular field..
	 * Extra data is make blank
	 *
	 * @return -- formated array...which do not contain extra value
	 */
	public function getFieldArrayFormatted($data)
	{
		//print_r($data); die('asdasdasd2324234');
		switch ($data['type'])
			{
				case	"radio":
				case 	"single_select":
				case 	"multi_select":
				case	"checkbox":
							$data['saveOption']=1;
							$data['min']='';
							$data['max']='';
							$data['format']='';
							$data['default_value']='';		//this value is only for text type areas...for slect list etc...it
						break;
				case	"editor":
				case	"file":
				case	"password":
							$data['min']='';
							$data['max']='';
							$data['format']='';
							$data['default_value']='';		//this value is only for text type areas...for slect list etc...it is saved later
							break;
				case	"text":
				case	"textarea":
				case	"email_field":
							$data['format']='';
							break;
				case	"calender":

							$data['min']='';
							$data['max']='';
							$data['default_value']='';
							break;
				case	"hidden":
							$data['min']='';
							$data['max']='';
							$data['format']='';
							break;

			}
			if($data['id']==0) //change the name only if the field is newly created....don't do on edit fields
			{
				$data_name=trim(preg_replace('/[^A-Za-z0-9\-\']/', '', $data['name']));  // escape apostraphe
				$client =  explode('.',$data['client']);
				$client = $client[0];
				$data_unique_name=$client.'_'.$data['client_type'].'_'.$data_name;
				$data['name']=$data_unique_name;
			}

			return $data;
	}

	/**
	 * Check if the name is unique
	 *
	 * @return true or false
	 */
	public function checkIfUniqueName($data_unique_name)
	{
		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('count(name) FROM #__tjfields_fields');
		$query->where('name="'.$data_unique_name.'"');
		$db->setQuery($query);
		$is_unique = $db->loadResult();
		return $is_unique;
	}

	/**
	 * This function appaned ID to the name and replace it in DB
	 *
	 * @return true or false
	 */
	public function changeNameIfNotUnique($data_same_name,$id)
	{
		$app = JFactory::getApplication();
		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->update('#__tjfields_fields');
		$query->set('name="'.$data_same_name.'_'.$id.'"');

		 $query->where('id='.$id);
		$db->setQuery($query);
		if(!$db->execute())
		{
			$stderr= $db->stderr();
			echo $app->enqueueMessage($stderr,'error');
		}
		return true;
	}

	/**
	 * This function genarate XML on each saving of field.
	 *
	 *
	 */
	public function generateXml($data)
	{
		//print_r($fields); die('asda');
		$db     = JFactory::getDbo();
		$query  = 'SELECT * FROM
		#__tjfields_fields
		WHERE client="'.$data['client'].'" AND state=1
		';

		$db->setQuery($query);
		$fields = $db->loadObjectList();
		$newXML = new SimpleXMLElement("<form></form>");
		//$newXML->addAttribute('newsPagePrefix', 'value goes here');
		$fieldset = $newXML->addChild('fieldset');


		foreach($fields as $f)
		{
			$f = $this->SwitchCaseForExtraAttribute($f);
			$field = $fieldset->addChild('field');
			$field->addAttribute('name', $f->name);
			//need to change...
			$field->addAttribute('type', $f->type);
			$field->addAttribute('label', $f->label);
			$field->addAttribute('description', $f->description);
			if($f->required==1)
			{
				$field->addAttribute('required','true');
			}

			if($f->readonly==1)
			{
				$field->addAttribute('readonly', 'true');
			}

			$field->addAttribute('class', $f->validation_class);

				//ADD option if present.
				if(isset($f->extra_options))
				{
					foreach($f->extra_options as $f_option)
					{
						$option = $field->addChild('option',$f_option->options);
						$option->addAttribute('value',$f_option->value);
						if($f_option->default_option==1)
						$option->addAttribute('selected','selected');
					}
				}

				//Add multiple attribute for multilist.
				if(isset($f->multiple))
				{
					$field->addAttribute('multiple',$f->multiple);
				}

				//Add mim max charcter attribute.
				if(isset($f->max) && !empty($f->max))
				{
					$field->addAttribute('maxlength',$f->max);
				}

				//Add deault value attribute.
				if(isset($f->default_value) && !empty($f->default_value))
				{
					$field->addAttribute('default',$f->default_value);
				}

				if(isset($f->textarea))
				{
					$field->addAttribute('rows',$f->rows);
					$field->addAttribute('cols',$f->cols);
				}
		}

		$filePath = JPATH_SITE . DS . 'components/com_jticketing/models/forms/'.$data['client_type'].'form_extra.xml';
		$content  = '';

		if(!JFile::exists($filePath))
		{
			JFile::write($filePath, $content);
		}
		$newXML->asXML($filePath);//->asXML();
		$filePath = JPATH_SITE . DS . 'administrator/components/com_jticketing/models/forms/'.$data['client_type'].'_extra.xml';
		$content  = '';

		if(!JFile::exists($filePath))
		{
			JFile::write($filePath, $content);
		}
		$newXML->asXML($filePath);//->asXML();
	}


	public function SwitchCaseForExtraAttribute($data)
	{
	//	print_r($data); die('asdasdasd');
		switch($data->type)
		{
				case	"text":
						//min max default
						break;
				case	"radio":
						//$data[0]->extra_options=1;
						$extra_options= $this->getOptions($data->id);
						$data->extra_options=$extra_options;
						//options default(from another table)
						break;
				case 	"single_select":
						$data->type='list';
						//$data[0]->extra_options=1;
						$extra_options= $this->getOptions($data->id);
						$data->extra_options=$extra_options;
						$data->multiple="false";
						//options default(from another table) multiple="false"
						break;
				case 	"multi_select":
						$data->type='list';
						//$data[0]->extra_options=1;
						$extra_options= $this->getOptions($data->id);
						$data->extra_options=$extra_options;
						$data->multiple="true";
						//options default(from another table) multiple="true"
						break;
				case	"hidden":
						break;
				case	"textarea":
						$data->textarea=1;
						break;
				case	"checkbox":
						//$data[0]->extra_options=1;
						$extra_options= $this->getOptions($data->id);
						$data->extra_options=$extra_options;
						break;
				case	"calender":
						break;
				case	"editor":
						break;
				case	"email_field":
						$data->type='email';
						break;
				case	"password":
						break;
				case	"file":
						break;
		}
		return $data;
	}

	/**
	 * Get option which are stored in field option table.
	 *
	 * @return array of option for the particular field
	 */
	public function getOptions($field_id)
	{
		$db=JFactory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('options,default_option,value FROM #__tjfields_options');
		$query->where('field_id='.$field_id);
		$db->setQuery($query);
		$extra_options = $db->loadObjectlist();
	//	print_r($extra_options); die('asdasd');
		return $extra_options;
	}












}
