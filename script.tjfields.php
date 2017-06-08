<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.controller');

/**
 * script
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       1.0
 */
class Com_TjfieldsInstallerScript
{
	// Used to identify new install or update
	private $componentStatus = "install";

	private $installation_queue = array(
		'modules' => array(
			'site' => array(
					'mod_tjfields_search' => array('tj-filters-mod-pos', 1)
						)
		)
	);

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   string      $type    install, update or discover_update
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function postflight($type, $parent)
	{
		// Install subextensions
		$status = $this->_installSubextensions($parent);

		$msgBox = array();

		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			$document = JFactory::getDocument();
			$document->addStyleSheet(JUri::root() . '/media/techjoomla_strapper/css/bootstrap.min.css');
		}
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	private function _installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		$status = new JObject;
		$status->modules = array();

		// Modules installation
		if (count($this->installation_queue['modules']))
		{
			foreach ($this->installation_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Install the module
						if (empty($folder))
						{
							$folder = 'site';
						}

						$path = "$src/modules/$folder/$module";

						if (!is_dir($path))
						{
							$path = "$src/modules/$folder/mod_$module";
						}

						if (!is_dir($path))
						{
							$path = "$src/modules/$module";
						}

						if (!is_dir($path))
						{
							$path = "$src/modules/mod_$module";
						}

						if (!is_dir($path))
						{
							$fortest = '';

							// Continue;
						}

						// Was the module already installed?
						$sql = $db->getQuery(true)
							->select('COUNT(*)')
							->from('#__modules')
							->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
						$db->setQuery($sql);

						$count = $db->loadResult();

						$installer = new JInstaller;
						$result = $installer->install($path);

						$status->modules[] = array(
							'name' => $module,
							'client' => $folder,
							'result' => $result,
							'status' => $modulePreferences[1]
						);

						// Modify where it's published and its published state
						if (!$count)
						{
							// A. Position and state
							list($modulePosition, $modulePublished) = $modulePreferences;

							if ($modulePosition == 'cpanel')
							{
								$modulePosition = 'icon';
							}

							$sql = $db->getQuery(true)
								->update($db->qn('#__modules'))
								->set($db->qn('position') . ' = ' . $db->q($modulePosition))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));

							if ($modulePublished)
							{
								$sql->set($db->qn('published') . ' = ' . $db->q('1'));
							}

							$db->setQuery($sql);
							$db->query();

							// B. Change the ordering of back-end modules to 1 + max ordering
							if ($folder == 'admin')
							{
								$query = $db->getQuery(true);
								$query->select('MAX(' . $db->qn('ordering') . ')')
									->from($db->qn('#__modules'))
									->where($db->qn('position') . '=' . $db->q($modulePosition));
								$db->setQuery($query);
								$position = $db->loadResult();
								$position++;

								$query = $db->getQuery(true);
								$query->update($db->qn('#__modules'))
									->set($db->qn('ordering') . ' = ' . $db->q($position))
									->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
								$db->setQuery($query);
								$db->query();
							}

							// C. Link to all pages
							$query = $db->getQuery(true);
							$query->select('id')->from($db->qn('#__modules'))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
							$db->setQuery($query);
							$moduleid = $db->loadResult();

							$query = $db->getQuery(true);
							$query->select('*')->from($db->qn('#__modules_menu'))
								->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
							$db->setQuery($query);
							$assignments = $db->loadObjectList();
							$isAssigned = !empty($assignments);

							if (!$isAssigned)
							{
								$o = (object) array(
									'moduleid'	=> $moduleid,
									'menuid'	=> 0
								);
								$db->insertObject('#__modules_menu', $o);
							}
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function install($parent)
	{
		$this->installSqlFiles($parent);
	}

	/**
	 * Runs on post installation
	 *
	 * @param   STRING  $status  status
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	private function _renderPostUninstallation($status, $parent)
	{
		?>
		<?php $rows = 0;?>
		<h2><?php echo JText::_('TJ-Fields Uninstallation Status'); ?></h2>
		<table class="adminlist">
			<thead>
				<tr>
					<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
					<th width="30%"><?php echo JText::_('Status'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="row0">
					<td class="key" colspan="2"><?php echo 'TjFields ' . JText::_('Component'); ?></td>
					<td><strong style="color: green"><?php echo JText::_('Removed'); ?></strong></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}

	/**
	 * method to update the component
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$this->componentStatus = "update";
		$this->installSqlFiles($parent);
		$this->fix_db_on_update();
	}

	/**
	 * method to fix database on update
	 *
	 * @return void
	 */
	public function fix_db_on_update()
	{
		$db = JFactory::getDbo();

		$field_array = array();
		$query = "SHOW COLUMNS FROM `#__tjfields_fields`";
		$db->setQuery($query);
		$columns = $db->loadobjectlist();

		for ($i = 0; $i < count($columns); $i++)
		{
			$field_array[] = $columns[$i]->Field;
		}

		if (!in_array('filterable', $field_array))
		{
			$query = "ALTER TABLE `#__tjfields_fields`
						ADD COLUMN `filterable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - For not filterable field. 1 for filterable field'";
			$db->setQuery($query);

			if (!$db->execute() )
			{
				echo $img_ERROR . JText::_('Unable to Alter #__tjfields_fields table. (While adding filterable column )') . $BR;
				echo $db->getErrorMsg();

				return false;
			}
		}

		if (!in_array('asset_id', $field_array))
		{
			$query = "ALTER TABLE `#__tjfields_fields`
						ADD COLUMN `asset_id` int(10) DEFAULT '0'";
			$db->setQuery($query);

			if (!$db->execute() )
			{
				echo $img_ERROR . JText::_('Unable to Alter #__tjfields_fields table. (While adding asset_id column )') . $BR;
				echo $db->getErrorMsg();

				return false;
			}
		}

		if (!in_array('showonlist', $field_array))
		{
			$query = "ALTER TABLE `#__tjfields_fields` ADD COLUMN `showonlist` tinyint(1) NOT NULL DEFAULT '0'";
			$db->setQuery($query);

			if (!$db->execute())
			{
				echo $img_ERROR . JText::_('Unable to Alter #__tjfields_fields table. (While adding filterable showonlist )') . $BR;
				echo $db->getErrorMsg();

				return false;
			}
		}

		$query = "CREATE TABLE IF NOT EXISTS `#__tjfields_category_mapping` (
				  `id` INT(11) NOT NULL AUTO_INCREMENT,
				  `field_id` INT(11) NOT NULL,
				  `category_id` INT(11) NOT NULL COMMENT 'CATEGORY ID FROM JOOMLA CATEGORY TABLE FOR CLIENTS EG CLIENT=COM_QUICK2CART.PRODUCT',
				  PRIMARY KEY (`id`)
				)DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		$db->setQuery($query);
		$db->execute();

		$db = JFactory::getDbo();

		$field_array = array();
		$query = "SHOW COLUMNS FROM `#__tjfields_fields_value`";
		$db->setQuery($query);
		$columns = $db->loadobjectlist();

		for ($i = 0; $i < count($columns); $i++)
		{
			$field_array[] = $columns[$i]->Field;
		}

		if (!in_array('option_id', $field_array))
		{
			$query = "ALTER TABLE `#__tjfields_fields_value`
						ADD COLUMN `option_id` int(11) DEFAULT NULL";
			$db->setQuery($query);

			if (!$db->execute())
			{
				echo $img_ERROR . JText::_('Unable to Alter #__tjfields_fields_value table. (While adding option_id column )') . $BR;
				echo $db->getErrorMsg();

				return false;
			}
		}

		// Add params column in tjfields_fields table to store fields attributes - added in v1.4
		$this->addparamsColumn();

		// Add title column in tjfields_fields table to store fields title - added in v1.4
		$this->addTitleColumn();
	}

	/**
	 * method to add title column
	 *
	 * @return void
	 */
	public function addTitleColumn()
	{
		$field_array = array();
		$query = "SHOW COLUMNS FROM `#__tjfields_fields`";
		$db = JFactory::getDbo();
		$db->setQuery($query);
		$columns = $db->loadobjectlist();

		for ($i = 0; $i < count($columns); $i++)
		{
			$field_array[] = $columns[$i]->Field;
		}

		if (!in_array('title', $field_array))
		{
			$query = "ALTER TABLE `#__tjfields_fields` ADD COLUMN `title` varchar(255) NOT NULL after `core`";
			$db->setQuery($query);

			if (!$db->execute())
			{
				echo $img_ERROR . JText::_('Unable to Alter #__tjfields_fields table. (While adding title column )') . $BR;
				echo $db->getErrorMsg();

				return false;
			}
			else
			{
				$query = $db->getQuery(true);
				$query->select('*');
				$query->from($db->quoteName('#__tjfields_fields'));
				$db->setQuery($query);
				$fields = $db->loadObjectList();

				foreach ($fields as $field)
				{
					$field->title = $field->label;

					JFactory::getDbo()->updateObject('#__tjfields_fields', $field, 'id', true);
				}
			}
		}
	}

	/**
	 * method to add params column
	 *
	 * @return void
	 */
	public function addparamsColumn()
	{
		$field_array = array();
		$query = "SHOW COLUMNS FROM `#__tjfields_fields`";
		$db = JFactory::getDbo();
		$db->setQuery($query);
		$columns = $db->loadobjectlist();

		for ($i = 0; $i < count($columns); $i++)
		{
			$field_array[] = $columns[$i]->Field;
		}

		if (!in_array('params', $field_array))
		{
			$query = "ALTER TABLE `#__tjfields_fields` ADD COLUMN `params` text COMMENT 'stores fields extra attributes in json format'";
			$db->setQuery($query);

			if (!$db->execute())
			{
				echo $img_ERROR . JText::_('Unable to Alter #__tjfields_fields table. (While adding params column )') . $BR;
				echo $db->getErrorMsg();

				return false;
			}
			else
			{
				$query = $db->getQuery(true);
				$query->select('*');
				$query->from('#__tjfields_fields');
				$db->setQuery($query);
				$fields = $db->loadObjectList();

				$param = array();

				foreach ($fields as $field)
				{
					if (!empty($field->min))
					{
						$param['min'] = $field->min;
					}

					if (!empty($field->max))
					{
						$param['max'] = $field->max;
					}

					if (!empty($field->rows))
					{
						$param['rows'] = $field->rows;
					}

					if (!empty($field->cols))
					{
						$param['cols'] = $field->cols;
					}

					if (!empty($field->format))
					{
						$param['format'] = $field->format;
					}

					if (!empty($field->default_value))
					{
						$param['default'] = $field->default_value;
					}

					if (!empty($field->placeholder))
					{
						$param['placeholder'] = $field->placeholder;
					}

					$field->params = json_encode($param);

					JFactory::getDbo()->updateObject('#__tjfields_fields', $field, 'id', true);
				}

				$deleteColumn = array("min", "max", "rows", "cols", "format", "default_value", "placeholder");

				foreach ($deleteColumn as $pm)
				{
					$query = "ALTER TABLE `#__tjfields_fields` DROP COLUMN " . $pm;

					$db->setQuery($query);

					if (!$db->execute())
					{
						echo $img_ERROR . JText::_('Unable to delete column ') . $pm;
						echo $db->getErrorMsg();

						return false;
					}
				}
			}
		}
	}

	/**
	 * method to table columns
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function installSqlFiles($parent)
	{
		$db = JFactory::getDBO();

		// Install country table(#__tj_country) if it does not exists
		$check = $this->checkTableExists('tj_country');

		if (!$check)
		{
			// Lets create the table
			$this->runSQL($parent, 'country.sql');
		}
		else
		{
			$newColumns = array('id', 'country', 'country_3_code', 'country_code', 'country_jtext', 'ordering');
			$oldColumns = $this->getColumns('#__tj_country');

			$dropTableFlag = 0;

			foreach ($newColumns as $column)
			{
				if (! in_array($column, $oldColumns))
				{
					$dropTableFlag = 1;
					break;
				}
			}

			if ($dropTableFlag)
			{
				// Backup old table
				$backup = $this->renameTable('#__tj_country', '#__tj_country_backup');

				if ($backup)
				{
					// Lets create the table with new structure
					$this->runSQL($parent, 'country.sql');
				}
			}
		}

		// Install region table(#__tj_region) if it does not exists
		$check = $this->checkTableExists('tj_region');

		if (!$check)
		{
			// Lets create the table
			$this->runSQL($parent, 'region.sql');
		}
		else
		{
			$newColumns = array('id', 'country_id', 'region_3_code', 'region_code', 'region', 'region_jtext', 'ordering');
			$oldColumns = $this->getColumns('#__tj_region');

			$dropTableFlag = 0;

			foreach ($newColumns as $column)
			{
				if (! in_array($column, $oldColumns))
				{
					$dropTableFlag = 1;
					break;
				}
			}

			if ($dropTableFlag)
			{
				// Backup old table
				$backup = $this->renameTable('#__tj_region', '#__tj_region_backup');

				if ($backup)
				{
					// Lets create the table with new structure
					$this->runSQL($parent, 'region.sql');
				}
			}
		}

		// Install city table(#__tj_city) if it does not exists
		$check = $this->checkTableExists('tj_city');

		if (!$check)
		{
			// Lets create the table
			$this->runSQL($parent, 'city.sql');
		}
		else
		{
			$newColumns = array('id', 'city', 'country_id', 'region_id', 'city_jtext', 'zip', 'ordering');
			$oldColumns = $this->getColumns('#__tj_city');

			$dropTableFlag = 0;

			foreach ($newColumns as $column)
			{
				if (! in_array($column, $oldColumns))
				{
					$dropTableFlag = 1;
					break;
				}
			}

			if ($dropTableFlag)
			{
				// Backup old table
				$backup = $this->renameTable('#__tj_city', '#__tj_city_backup');

				if ($backup)
				{
					// Lets create the table with new structure
					$this->runSQL($parent, 'city.sql');
				}
			}
		}
	}

	/**
	 * method to check if table exists
	 *
	 * @param   STRING  $table  existing name
	 *
	 * @return void
	 */
	public function checkTableExists($table)
	{
		$db = JFactory::getDBO();
		$config = JFactory::getConfig();

		if (JVERSION >= '3.0')
		{
			$dbname = $config->get('db');
			$dbprefix = $config->get('dbprefix');
		}
		else
		{
			$dbname = $config->getValue('config.db');
			$dbprefix = $config->getvalue('config.dbprefix');
		}

		$query = " SELECT table_name
		 FROM information_schema.tables
		 WHERE table_schema='" . $dbname . "'
		 AND table_name='" . $dbprefix . $table . "'";

		$db->setQuery($query);
		$check = $db->loadResult();

		if ($check)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * method to table columns
	 *
	 * @param   STRING  $table  existing name
	 *
	 * @return void
	 */
	public function getColumns($table)
	{
		$db = JFactory::getDBO();

		$field_array = array();
		$query = "SHOW COLUMNS FROM " . $table;
		$db->setQuery($query);
		$columns = $db->loadobjectlist();

		for ($i = 0; $i < count($columns); $i++)
		{
			$columns_array[] = $columns[$i]->Field;
		}

		return $columns_array;
	}

	/**
	 * method to rename table
	 *
	 * @param   STRING  $table     existing name
	 * @param   STRING  $newTable  updated name
	 *
	 * @return void
	 */
	public function renameTable($table, $newTable)
	{
		$db = JFactory::getDBO();
		$query = "RENAME TABLE `" . $table . "` TO `" . $newTable . '_' . date('d-m-Y_H:m:s') . "`";
		$db->setQuery($query);

		if ($db->query())
		{
			return true;
		}

		return false;
	}

	/**
	 * method to execute sql file
	 *
	 * @param   STRING  $parent   parent
	 * @param   STRING  $sqlfile  sql file
	 *
	 * @return void
	 */
	public function runSQL($parent,$sqlfile)
	{
		$db = JFactory::getDBO();

		// Obviously you may have to change the path and name if your installation SQL file ;)
		if (method_exists($parent, 'extension_root'))
		{
			$sqlfile = $parent->getPath('extension_root') . '/administrator/sql/' . $sqlfile;
		}
		else
		{
			$sqlfile = $parent->getParent()->getPath('extension_root') . '/sql/' . $sqlfile;
		}

		// Don't modify below this line
		$buffer = file_get_contents($sqlfile);

		if ($buffer !== false)
		{
			jimport('joomla.installer.helper');
			$queries = JInstallerHelper::splitSql($buffer);

			if (count($queries) != 0)
			{
				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '' && $query{0} != '#')
					{
						$db->setQuery($query);

						if (!$db->query())
						{
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));

							return false;
						}
					}
				}
			}
		}
	}
}
