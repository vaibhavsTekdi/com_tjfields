<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

if(JVERSION >= '3.0')
{
	JHtml::_('formbehavior.chosen', 'select');
}

$input = JFactory::getApplication()->input;
$fullClient = $input->get('client','','STRING');
$fullClient =  explode('.',$fullClient);

$client = $fullClient[0];
$clientType = $fullClient[1];

$link = JRoute::_('index.php?option=com_tjfields&view=field&layout=edit&id=0&client=' . $input->get('client', '', 'STRING'), false);

// Import helper for declaring language constant
JLoader::import('TjfieldsHelper', JUri::root().'administrator/components/com_tjfields/helpers/tjfields.php');
// Call helper function
TjfieldsHelper::getLanguageConstant();

// Default path for file upload field
$fileUploadDefaultPath = JPATH_SITE."/media/";

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_tjfields/assets/css/tjfields.css');
?>
<script type="text/javascript">
	var fileUploadDefaultPath = '<?php echo $fileUploadDefaultPath;?>';
	var client = '<?php echo $client;?>';
	var clientType = '<?php echo $clientType;?>';
	var invalidFormErrorMsg = '<?php echo $this->escape(JText::_('COM_TJFIELDS_INVALID_FORM')); ?>';
	var editFormlink = '<?php echo $link;?>';
</script>
<?php $document->addScript(JUri::root() . 'administrator/components/com_tjfields/assets/js/field.js'); ?>
<div class="techjoomla-bootstrap">
	<form action="<?php echo JRoute::_('index.php?option=com_tjfields&layout=edit&id='.(int) $this->item->id).'&client='.$input->get('client','','STRING'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="field-form" class="form-validate">
		<div class="techjoomla-bootstrap">
			<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_TJFIELDS_TITLE_FIELD', true)); ?>
			<div class="row-fluid">
				<div class="container1">
					<div class="span6 ">
						<fieldset class="adminform form-horizontal">
							<legend>
								<?php
									echo JText::_('COM_TJFIELDS_BASIC_FIELDS_VALUES');
								?>
							</legend>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
							</div>
							<?php echo $this->form->getInput('title');?>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('label'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('label'); ?>
									<span class="alert alert-info alert-help-inline span9 alert_no_margin">
									<?php echo JText::_('COM_TJFIELDS_LABEL_LANG_CONSTRAINT_ONE'); ?>
									<span class="alert-text-change">
									<?php echo JText::sprintf('COM_TJFIELDS_LABEL_LANG_CONSTRAINT_TWO', $client); ?>
									</span>
									</span>
								</div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('name'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
								<?php
									if (!empty($this->item->id))
									{
										?>
										<div class="controls">
											<input type="text" name="jform[type]" id="jform_type" value="<?php echo $this->item->type;?>" class="required" required="required" aria-required="true" aria-invalid="false" readonly="true"/>
										</div>
										<?php
									}
									else
									{
										?>
										<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
										<?php
									}
								?>
							</div>
							<div>
								<?php
									foreach ($this->form->getFieldsets('params') as $name => $fieldSet)
									{
										foreach ($this->form->getFieldset($name) as $field)
										{
											echo $field->renderField();
										}
									}
									echo $this->form->getInput('options');
									?>
							</div>
							<div class="control-group displaynone" id="option_div" >
								<div class="control-label"><?php echo $this->form->getLabel('fieldoption'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('fieldoption'); ?></div>
							</div>
						</fieldset>
						<div class="fileUploadAlert hide">
							<span class="alert alert-info alert-help-inline span9 alert_no_margin">
								<?php
									echo JText::_('COM_TJFIELDS_FORM_LBL_FILE_UPLOAD_PATH_NOTICE');
								?>
							</span>
						</div>
						<input type="hidden" name="jform[client]" value="<?php echo $input->get('client','','STRING'); ?>" />
					</div>
					<div class="span5 form-horizontal">
						<fieldset class="adminform form-horizontal">
							<legend>
								<?php
									echo JText::_('COM_TJFIELDS_EXTRA_FIELDS_VALUES');
									?>
							</legend>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('group_id'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('group_id'); ?></div>
							</div>
							<div class="control-group" >
								<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('required'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('required'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('readonly'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('readonly'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('showonlist'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('showonlist'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('category') ; ?></div>
								<div class="controls">
									<?php
										echo $this->form->getInput('category');?>
									<div style="clear:both" ></div>
									<span class="alert alert-warning alert-help-inline span9 alert_no_margin">
									<?php echo JText::_('COM_TJFIELDS_CATEGORY_NOTE'); ?>
									</span>
								</div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('filterable'); ?></div>
								<div class="controls">
									<?php echo $this->form->getInput('filterable'); ?>
									<div style="clear:both" ></div>
									<span class="alert alert-info alert-help-inline span9 alert_no_margin">
									<?php echo JText::_('COM_TJFIELDS_FILTERABLE_NOTE'); ?>
									</span>
								</div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('js_function'); ?></div>
								<div class="controls">
									<?php echo $this->form->getInput('js_function'); ?>
								</div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('validation_class'); ?></div>
								<div class="controls">
									<?php echo $this->form->getInput('validation_class'); ?>
									<div style="clear:both" ></div>
									<span class="alert alert-info alert-help-inline span9 alert_no_margin">
									<?php echo JText::_('COM_TJFIELDS_VALIDATION_CLASS_NOTE'); ?>
									</span>
								</div>
							</div>
						</fieldset>
					</div>
				</div>
				<!--</fieldset>-->
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php if (JFactory::getUser()->authorise('core.admin','com_tjfields')) : ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php endif; ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
			<input type="hidden" name="client_type" value="<?php echo $clientType;?>" />
			<input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
		<!--row fuild ends-->
</div>
<!--techjoomla ends-->
</form>
</div>
