<?php

/**
 * @package    Com_Tjfields
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Sections Table class
 *
 * @since  1.0
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
