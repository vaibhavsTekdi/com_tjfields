<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

defined('JPATH_PLATFORM') or die;

/**
 * Form Field class for the Joomla Platform.
 * Provides an input field for files
 *
 * @link   http://www.w3.org/TR/html-markup/input.file.html#input.file
 * @since  11.1
 */
class JFormFieldFile extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'File';

	/**
	 * The accepted file type list.
	 *
	 * @var    mixed
	 * @since  3.2
	 */
	protected $accept;

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  3.6
	 */
	protected $layout = 'joomla.form.field.file';

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		switch ($name)
		{
			case 'accept':
				return $this->accept;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'accept':
				$this->accept = (string) $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->accept = (string) $this->element['accept'];
		}

		return $return;
	}

	/**
	 * Method to get the field input markup for the file field.
	 * Field attributes allow specification of a maximum file size and a string
	 * of accepted file extensions.
	 *
	 * @return  string  The field input markup.
	 *
	 * @note    The field does not include an upload mechanism.
	 * @see     JFormFieldMedia
	 * @since   11.1
	 */
	protected function getInput()
	{
		$layoutData = $this->getLayoutData();
		$html = $this->getRenderer($this->layout)->render($layoutData);

		// Load backend language file
		$lang = JFactory::getLanguage();
		$lang->load('com_tjfields', JPATH_SITE);

		if (!empty($layoutData["value"]))
		{
			$data = $this->buildData($layoutData);
			$html .= $data->html;

			if (!empty($data->mediaLink))
			{
				$html .= $this->canDownloadFile($data, $layoutData);
				$html .= $this->canDeleteFile($data, $layoutData);
			}

			$html .= '</div>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();
		$extraData = array(
			'accept'   => $this->accept,
			'multiple' => $this->multiple,
		);

		return array_merge($data, $extraData);
	}

	/**
	 * Method to required data for file.
	 *
	 * @param   array  $layoutData  layoutData
	 *
	 * @return  object
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function buildData($layoutData)
	{
		$tjFieldHelper = new TjfieldsHelper;
		$data = new stdClass;

		$app = JFactory::getApplication();
		$data->clientForm = $app->input->get('client', '', 'string');

		// Checking the field is from subfrom or not
		$formName = explode('.', $this->form->getName());
		$formValueId = $app->input->get('id', '', 'INT');
		$data->subFormFileFieldId = 0;
		$data->isSubformField = 0;
		$data->subformId = 0;

		if ($formName[0] === 'subform')
		{
			$data->isSubformField = 1;
			$formData = $tjFieldHelper->getFieldData(substr($formName[1], 0, -1));

			// Subform Id
			$data->subformId = $formData->id;
			$fileFieldData = $tjFieldHelper->getFieldData($layoutData['field']->fieldname);

			// File Field Id under subform
			$data->subFormFileFieldId = $fileFieldData->id;
		}

		$data->html .= '<input fileFieldId="' . $layoutData["id"] . '" type="hidden" name="'
		. $layoutData["name"] . '"' . 'id="' . $layoutData["id"] . '"' . 'value="' . $layoutData["value"] . '" />';
		$data->html .= '<div class="control-group">';

		$fileInfo = new SplFileInfo($layoutData["value"]);
		$data->extension = $fileInfo->getExtension();
		$data->mediaLink = $tjFieldHelper->getMediaUrl($layoutData["value"]);

		// Access based actions
		$data->user = JFactory::getUser();

		$db = JFactory::getDbo();
		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$data->tjFieldFieldTable = JTable::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$data->tjFieldFieldTable->load(array('name' => $layoutData['field']->fieldname));

		// Get Field value details
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$data->fields_value_table = JTable::getInstance('Fieldsvalue', 'TjfieldsTable');
		$data->fields_value_table->load(array('value' => $layoutData['value']));

		if ($data->isSubformField)
		{
			// Getting field value of subform file field using the content_id from url and subform_id which will be the field id
			$data->fields_value_table->load(array('content_id' => $formValueId, 'field_id' => $data->subformId));
		}
		else
		{
			$data->fields_value_table->load(array('value' => $layoutData['value']));
		}

		$extraParamArray = array();
		$extraParamArray['id'] = $data->fields_value_table->id;

		// Creating media link by check subform or not
		if ($data->isSubformField)
		{
			$extraParamArray['subFormFileFieldId'] = $data->subFormFileFieldId;
		}

		$data->mediaLink = $tjFieldHelper->getMediaUrl($layoutData["value"], $extraParamArray);

		return $data;
	}

	/**
	 * Method to download file.
	 *
	 * @param   object  $data        file data.
	 * @param   array   $layoutData  layoutData
	 *
	 * @return  string
	 *
	 * @since    1.5
	 */
	protected function canDownloadFile($data,$layoutData)
	{
		$canView = 0;
		$canDownload = 0;
		$user = JFactory::getUser();

		if ($data->user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $data->tjFieldFieldTable->group_id))
		{
			if ($data->isSubformField)
			{
				$canView = $data->user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->subFormFileFieldId);
			}
			else
			{
				$canView = $data->user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->id);
			}
		}

		if ($data->fields_value_table->user_id != null && $user->id == $data->fields_value_table->user_id)
		{
			$canDownload = true;
		}

		$downloadFile = '';

		if ($canView || $canDownload)
		{
			$downloadFile .= '<div> <a href="' . $data->mediaLink . '">' . JText::_("COM_TJFIELDS_FILE_DOWNLOAD") . '</a>';
		}

		return $downloadFile;
	}

	/**
	 * Method to delete file.
	 *
	 * @param   object  $data        file data.
	 * @param   array   $layoutData  layoutData
	 *
	 * @return  string
	 *
	 * @since    1.5
	 */
	protected function canDeleteFile($data,$layoutData)
	{
		$canEdit = 0;

		if ($data->user->authorise('core.field.editfieldvalue', 'com_tjfields.group.' . $data->tjFieldFieldTable->group_id))
		{
			$canEdit = $data->user->authorise('core.field.editfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->id);
		}

		$canEditOwn = 0;

		if ($data->user->authorise('core.field.editownfieldvalue', 'com_tjfields.group.' . $data->tjFieldFieldTable->group_id))
		{
			$canEditOwn = $data->user->authorise('core.field.editownfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->id);

			if ($canEditOwn && ($data->user->id != $data->fields_value_table->user_id))
			{
				$canEditOwn = 0;
			}
		}

		$deleteFiledata = '';

		if (!empty($data->mediaLink) && ($canEdit || $canEditOwn) && $layoutData['required'] == '' && $data->fields_value_table->id)
		{
			$deleteFiledata .= ' <span class="btn btn-remove"> <a id="remove_' . $layoutData["id"] . '" href="javascript:void(0);"
				onclick="deleteFile(\'' . base64_encode($layoutData["value"]) . '\',
				 \'' . $layoutData["id"] . '\', \'' . base64_encode($data->fields_value_table->id) . '\',
				  \'' . $data->subFormFileFieldId . '\',\'' . $data->isSubformField . '\');">'
				. JText::_("COM_TJFIELDS_FILE_DELETE") . '</a> </span>';
		}

		return $deleteFiledata;
	}
}
