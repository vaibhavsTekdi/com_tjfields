<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Tjfields model.
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
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Field', $prefix = 'TjfieldsTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjfields.field', 'field', array('control' => 'jform', 'load_data' => $loadData));


		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
 	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_tjfields.edit.field.data', array());

		if (empty($data)) {
			$data = $this->getItem();
            //print_r($data); die('asdasd');
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		//print_r($pk); die('asdas');
		$input=JFactory::getApplication()->input;

		if ($item = parent::getItem($pk)) {

			//Do any procesing on fields here if needed
			if($input->get('id','','INT'))
			{
				$db=JFactory::getDbo();
				$query	= $db->getQuery(true);
				$query->select('opt.id,opt.options,opt.value,opt.default_option FROM #__tjfields_options as opt');
				$query->where('opt.field_id='.$input->get('id','','INT'));
				$db->setQuery($query);
				$option_name = $db->loadObjectlist();
				if($option_name)
				$item->fieldoption=$option_name;
				//print_r($item); die('asd');
			}
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id)) {

			// Set ordering to the last item if not set
			if (@$table->ordering === '') {
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tjfields_fields');
				$max = $db->loadResult();
				$table->ordering = $max+1;
			}

		}
	}

	public function save_option($post)
	{
		$table = $this->getTable();
		$data=$post->get('jform','','ARRAY');
		$input=JFactory::getApplication()->input;

		if ($input->get('task') == 'save2copy')
		{
			unset($data['id']);
			$data['label'] = trim($data['label']);
			$name = explode("(",$data['label']);
			$name =trim($name['0']);
			$name = str_replace("`","",$name);
			$db = JFactory::getDBO();
			$query = 'SELECT a.*'
			. ' FROM #__tjfields_fields AS a'
			. ' WHERE a.label LIKE ' . $db->quote($name.'%')
			. ' AND  a.client LIKE' . $db->quote($data['client'])
			. ' AND  a.group_id =' . $db->quote($data['group_id'].'%');

			$db->setQuery($query);
			$posts = $db->loadAssocList();
			$postsCount =count($posts)+1;
			$data['label'] = $name. ' ('.$postsCount.")";
			$data['created_by'] =JFactory::getUser()->id;
		}


		//add clint type in data as it is not present in jform
		$data['client_type'] = $post->get('client_type','','STRING');
		$data['saveOption']=0; // use later to store later.
		//remove extra value which are not needed to save in the fields table
		$TjfieldsHelper=new TjfieldsHelper();
		$data=$TjfieldsHelper->getFieldArrayFormatted($data);
		if ($table->save($data) === true)
		{
			$id=$table->id;
		}
		//check if name feild is unique
		$is_unique= $TjfieldsHelper->checkIfUniqueName($data['name']);
		//print_r($is_unique); die;
		if($is_unique > 1)
		{
			//append id to the name
			$change_name_if_same = $TjfieldsHelper->changeNameIfNotUnique($data['name'],$id);
		}


		//save javascript functions.
			$js = $post->get('tjfieldsJs','','ARRAY');
			if(!empty($js))
			{
				$jsfunctionSave = $this->jsfunctionSave($js,$id);
			}
		//end
		// if the field is inserted.
		if($id)
		{
			//print_r($post); die('asd');
			$options=$post->get('tjfields','','ARRAY');
			//print_r($options); die('asdasd11111');
			if($data['saveOption']==1)
			{
						//Firstly Delete Fields Options That are Removed
						$field_options = $TjfieldsHelper->getOptions($id);

						foreach($field_options as $fokey=>$fovalue)
						{
							if($fovalue->id)
							{
								$fields_in_DB[]=$fovalue->id;
							}

						}

						foreach($options as $key=>$value)
						{
							if($value['hiddenoptionid'])
							{
								$options_filled[]=$value['hiddenoptionid'];
							}

						}

						if($fields_in_DB)
						{
								$diff_ids=array_diff($fields_in_DB,$options_filled);
								if(!empty($diff_ids))
								{
									$this->delete_option($diff_ids);
								}
						}

				if(empty($options))
				{
					$this->delete_option($fields_in_DB);
				}
				else
				{
					//save option fields.
					foreach($options as $option)
					{
						if(!isset($option['hiddenoption']))
						$option['hiddenoption']=0;
						$obj = new stdClass();
						$obj->options=$option['optionname'];
						$obj->value=$option['optionvalue'];
						$obj->default_option=$option['hiddenoption'];
						$obj->field_id=$id;
						//if edit options
						if(isset($option['hiddenoptionid']) && !empty($option['hiddenoptionid']))
						{
							if($option['optionname']!='' && $option['optionvalue']!='')
							{
								$obj->id=$option['hiddenoptionid'];
								if(!$this->_db->updateObject('#__tjfields_options',$obj,'id'))
								{
									echo $this->_db->stderr();
									return false;
								}
							}
						}
						else
						{
							if($option['optionname']!='' && $option['optionvalue']!='')
								{
									$obj->id='';
									if(!$this->_db->insertObject( '#__tjfields_options',$obj,'id'))
									{
										echo $this->_db->stderr();
										return false;
									}
								}
						}
					}
				}
				//return true;
			}
			//create XML for the current client.
			$TjfieldsHelper->generateXml($data);
			return $id;
		}
		else
		return false;

	}

	//save js functions
	function jsfunctionSave($jsarray,$fieldid)
	{
					$obj = new stdClass();
					$obj->js_function = '';
					foreach($jsarray as $js)
					{
						if($js['jsoptions']!='' && $js['jsfunctionname']!='')
						{
							$obj->js_function .= $js['jsoptions'].'-'.$js['jsfunctionname'].'||';
						}

						$obj->id = $fieldid;
						//if edit options
					}

					if(!empty($obj->js_function))
						{

							if(!$this->_db->updateObject('#__tjfields_fields',$obj,'id'))
							{
								echo $this->_db->stderr();
								return false;
							}
						}
	}



	function delete_option($delete_ids)
	{
		$db = JFactory::getDBO();
		foreach($delete_ids as $key=>$value)
		{
			//echo $value;
			$query='DELETE FROM #__tjfields_options
				WHERE id = "'.$value.'"';
				$db->setQuery($query);
				if(!$db->execute())
				{
					echo $db->stderr();
					return false;
				}
		}

	}

}
