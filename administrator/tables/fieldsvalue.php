<?php

/**
 * @Version SVN: <svn_id>
 * @Package    TJ-Fields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Sections Table class
 *
 * @since  1.4
 *
 */
class TjfieldsTablefieldsvalue extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   type  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjfields_fields_value', 'id', $db);
	}
}
