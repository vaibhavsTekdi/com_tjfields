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
	public static function addSubmenu($view = '')
	{
		/*
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
		*/
		$input = JFactory::getApplication()->input;
		$full_client = $input->get('client','','STRING');
		$full_client =  explode('.',$full_client);
		$component = $full_client[0]; //eg com_jticketing
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
					// loading language file from the administrator/language directory then
					// loading language file from the administrator/components/*extension*/language directory
					$lang->load($component, JPATH_BASE, null, false, false)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, false)
					|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), $lang->getDefault(), false, false);

					//call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
					call_user_func(array($cName, 'addSubmenu'), $view . (isset($section) ? '.' . $section : ''));
				}
			}
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
				case	"calendar":

							$data['min']='';
							$data['max']='';
							$data['default_value']='';
							//$data['format'] = $this->getDateFormat($data['format']);
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
		$db     = JFactory::getDbo();
		$query  = 'SELECT f.*,g.name as group_name FROM
		#__tjfields_fields as f
		LEFT JOIN #__tjfields_groups as g
		ON g.id = f.group_id
		WHERE f.client="'.$data['client'].'" AND f.state=1 AND g.state = 1
		ORDER BY g.ordering
		';

		$db->setQuery($query);
		$fields = $db->loadObjectList();
		$newXML = new SimpleXMLElement("<form></form>");
		//$newXML->addAttribute('newsPagePrefix', 'value goes here');


		$current_group = $fields[0]->group_id;
		$i = 0;
		$new_fieldset = $newXML->addChild('fieldset');
		$new_fieldset->addAttribute('name', $fields[0]->group_name);
		foreach($fields as $f)
		{
			//add fieldset as per group id
			if($current_group != $f->group_id)
			{

				$new_fieldset = $newXML->addChild('fieldset');
				$new_fieldset->addAttribute('name', $f->group_name);

				$current_group = $f->group_id;
			}

			$f = $this->SwitchCaseForExtraAttribute($f);
			$field = $new_fieldset->addChild('field');
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

				//add javascript
				if(isset($f->js_function))
				{
					$jsArray = $this->getJsArray($f->js_function);
					foreach($jsArray as $js)
					{
						$field->addAttribute($js[0],$js[1]);
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

				if($f->type == 'calendar')
				{
					$f->format = $this->getDateFormat($f->format);
					$field->addAttribute('format',$f->format);
				}
		}

		$filePathFrontend = JPATH_SITE . DS . 'components/com_jticketing/models/forms/'.$data['client_type'].'form_extra.xml';
		$content  = '';

		if(!JFile::exists($filePathFrontend))
		{
			JFile::write($filePathFrontend, $content);
		}
		$newXML->asXML($filePathFrontend);//->asXML();
		$filePathBackend = JPATH_SITE . DS . 'administrator/components/com_jticketing/models/forms/'.$data['client_type'].'_extra.xml';
		$content  = '';

		if(!JFile::exists($filePathBackend))
		{
			JFile::write($filePathBackend, $content);
		}
		$newXML->asXML($filePathBackend);//->asXML();




		//delete xml if no field present
		if(empty($fields))
		{
			if(JFile::exists($filePathFrontend))
			{
				JFile::delete($filePathFrontend);
			}
			if(JFile::exists($filePathBackend))
			{
				JFile::delete($filePathBackend);
			}
		}

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
				case	"calendar":
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
		$query->select('id,options,default_option,value FROM #__tjfields_options');
		$query->where('field_id='.$field_id);
		$db->setQuery($query);
		$extra_options = $db->loadObjectlist('id');
	//	print_r($extra_options); die('asdasd');
		return $extra_options;
	}

	/**
	 *
	 *
	 */
	public function getDateFormat($format)
	{

		if ($format == 1)
		{
			return "%d-%m-%Y";
		}
		else if (($format == 2))
		{
			return "%m-%d-%Y";
		}
		else if ($format == 3)
		{
			return "%Y-%d-%m";
		}
		else
		{
			return "%Y-%m-%d";
		}
	}



	function getJsArray($jsarray)
	{

		//$jsarray contains --    onclick-getfunction()||onchange-getfunction2()||
		$jsarray = explode('||', $jsarray);
		//now we get array[0] = onclick-getfunction()
		//array[1] = onchange-getfunction2()
		//array[2] = '';
		//remove the blank array element
		$jsarray_removed_blank_element = array_filter($jsarray);

		foreach($jsarray_removed_blank_element as $eachjs)
		{
			$jsarray_final[] = explode('-', $eachjs);
		}
		return $jsarray_final;
	}






}
