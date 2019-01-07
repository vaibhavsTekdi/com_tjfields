<?php
/**
 * File field layout to display download link on detail page
 *
 * @copyright  Copyright (C) 2009 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// Load TJ Fields Helper
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
$TjfieldsHelper = new TjfieldsHelper;

// Get Field table
$fieldTableData = new stdClass;
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
$fieldTableData->tjFieldFieldTable = JTable::getInstance('field', 'TjfieldsTable');

// Get Field value table
$fieldValueTableData = new stdClass;
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$fieldValueTableData->fields_value_table = JTable::getInstance('Fieldsvalue', 'TjfieldsTable');

// Get displaydata parameters
$fieldValue = $displayData['fieldValue'];
$isSubformField = $displayData['isSubformField'];
$content_id = $displayData['content_id'];
$subformFieldId = $displayData['subformFieldId'];
$subformFileFieldName = $displayData['subformFileFieldName'];

$extraParamArray = array();

if ($isSubformField)
{
	$fieldValueTableData->fields_value_table->load(array('content_id' => $content_id, 'field_id' => $subformFieldId));
	$extraParamArray['id'] = $fieldValueTableData->fields_value_table->id;
	$fieldTableData->tjFieldFieldTable->load(array('name' => $subformFileFieldName));
	$extraParamArray['subFormFileFieldId'] = $fieldTableData->tjFieldFieldTable->id;
	$mediaLink = $TjfieldsHelper->getMediaUrl($fieldValue, $extraParamArray);
}
else
{
	$fieldValueTableData->fields_value_table->load(array('value' => $fieldValue));
	$extraParamArray['id'] = $fieldValueTableData->fields_value_table->id;
	$mediaLink = $TjfieldsHelper->getMediaUrl($fieldValue, $extraParamArray);
}
?>
<a href="<?php if(!empty($mediaLink)) echo $mediaLink else echo 'javascript:void(0);';?>">
	<?php echo JText::_("COM_TJFIELDS_FILE_DOWNLOAD");?>
</a>
