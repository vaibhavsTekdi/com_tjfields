<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
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
// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_tjfields/assets/css/tjfields.css');
$input = JFactory::getApplication()->input;
?>
<script type="text/javascript">

	techjoomla.jQuery( document ).ready(function(){

			var field_type=techjoomla.jQuery('#jform_type').val();
			show_option_div(field_type);
			//if edit ..make name field readonly
			var field_id=techjoomla.jQuery('#jform_id').val();
			if(field_id!=0)
			{
				techjoomla.jQuery('#jform_name').attr('readonly',true);
			}

		});

	Joomla.submitbutton = function(task)
	{
		//alert(task);
		if(task == 'field.cancel'){
			Joomla.submitform(task, document.getElementById('field-form'));
		}
		else{

			if (task != 'field.cancel' && document.formvalidator.isValid(document.id('field-form'))) {

				Joomla.submitform(task, document.getElementById('field-form'));
			}
			else {
				alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}

	function show_option_div(field_value)
	{

			switch (field_value)
			{
				case	"radio":
				case 	"single_select":
				case 	"multi_select":
				case	"checkbox":
							techjoomla.jQuery('#option_div').show();
							techjoomla.jQuery('#option_min_char').hide();
							techjoomla.jQuery('#option_max_char').hide();
							techjoomla.jQuery('#date_format').hide();
							techjoomla.jQuery('#default_value_text').hide();
							break;
				case	"text":
				case	"textarea":
				case	"email_field":
							techjoomla.jQuery('#option_div').hide();
							techjoomla.jQuery('#option_min_char').show();
							techjoomla.jQuery('#option_max_char').show();
							techjoomla.jQuery('#date_format').hide();
							techjoomla.jQuery('#default_value_text').show();

							if(field_value == "textarea")
							{
								techjoomla.jQuery('#textarea_rows').show();
								techjoomla.jQuery('#textarea_cols').show();
								//techjoomla.jQuery('#textarea_cols').addClass('required');
								techjoomla.jQuery('.textarea_inputs').children().attr('required','required');
							}

							break;
				case	"calender":
				case	"editor":
				case	"file":
				case	"hidden":
							techjoomla.jQuery('#option_div').hide();
							techjoomla.jQuery('#option_min_char').hide();
							techjoomla.jQuery('#option_max_char').hide();
							techjoomla.jQuery('#date_format').hide();
							techjoomla.jQuery('#default_value_text').hide();
							if(field_value == "calender")
							{
								techjoomla.jQuery('#date_format').show();
							}
							else if(field_value == "hidden")
							{
								techjoomla.jQuery('#default_value_text').show();
							}
							break;

			}


	}

</script>

<div class="techjoomla-bootstrap">
	<form action="<?php echo JRoute::_('index.php?option=com_tjfields&layout=edit&id='.(int) $this->item->id).'&client='.$input->get('client','','STRING'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="field-form" class="form-validate">
		<div class="techjoomla-bootstrap">
		<div class="row-fluid">
			<fieldset class="adminform">
			<div class="container1">
				<div class="span6 form-horizontal">


					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('client_type'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('client_type'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('label'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('label'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('name'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
					</div>
					<div class="control-group displaynone" id="option_div" >
						<div class="control-label"><?php echo $this->form->getLabel('fieldoption'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('fieldoption'); ?></div>
					</div>
					<div class="control-group displaynone" id="date_format" >
						<div class="control-label"><?php echo $this->form->getLabel('format'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('format'); ?></div>
					</div>
					<div class="control-group displaynone" id="option_min_char">
						<div class="control-label"><?php echo $this->form->getLabel('min'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('min'); ?></div>
					</div>
					<div class="control-group displaynone" id="option_max_char">
						<div class="control-label"><?php echo $this->form->getLabel('max'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('max'); ?></div>
					</div>
					<div class="control-group displaynone" id="textarea_rows">
						<div class="control-label"><?php echo $this->form->getLabel('rows'); ?></div>
						<div class="controls textarea_inputs"><?php echo $this->form->getInput('rows'); ?></div>
					</div>
					<div class="control-group displaynone" id="textarea_cols">
						<div class="control-label"><?php echo $this->form->getLabel('cols'); ?></div>
						<div class="controls textarea_inputs"><?php echo $this->form->getInput('cols'); ?></div>
					</div>
					<div class="control-group displaynone" id="default_value_text">
						<div class="control-label"><?php echo $this->form->getLabel('default_value'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('default_value'); ?></div>
					</div>


				<input type="hidden" name="jform[client]" value="<?php echo $input->get('client','','STRING'); ?>" />
				</div>
				<div class="span5 form-horizontal">


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
						<div class="control-label"><?php echo $this->form->getLabel('placeholder'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('placeholder'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('js_function'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('js_function'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('validation_class'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('validation_class'); ?></div>
					</div>
				</div>
			</div>
			</fieldset>

			<!--</fieldset>-->
		</div>

		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>

		</div><!--row fuild ends-->
		</div><!--techjoomla ends-->
	</form>
</div>
