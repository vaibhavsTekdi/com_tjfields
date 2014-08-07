<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

defined('_JEXEC') or die;

class TjGeoHelper
{
	/**
	 * Toolbar name
	 *
	 * @var    string
	 */
	protected $_name = array();

	/**
	 * Stores the singleton instances of various TjGeoHelper.
	 *
	 * @var    JToolbar
	 * @since  2.5
	 */
	protected static $instances = array();

	/**
	 * Constructor
	 *
	 * @param   string  $name  The TjGeoHelper name.
	 *
	 * @since   1.5
	 */
	public function __construct($name = 'TjGeoHelper')
	{
		$this->_name = $name;

		// Load lang file for countries
		$this->_country_lang = JFactory::getLanguage();
		$this->_country_lang->load('tjgeo.countries', JPATH_SITE, null, false, true);
	}

	/**
	 * Returns the global JToolbar object, only creating it if it
	 * doesn't already exist.
	 *
	 * @param   string  $name  The name of the TjGeoHelper.
	 *
	 * @return  JToolbar  The JToolbar object.
	 *
	 * @since   1.5
	 */
	public static function getInstance($name = 'TjGeoHelper')
	{
		if (empty(self::$instances[$name]))
		{
			self::$instances[$name] = new TjGeoHelper($name);
		}

		return self::$instances[$name];
	}

	public function getCountryNameFromId($countryId)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$query->select('country, country_jtext');
		$query->from('#__tj_country');
		$query->where('id = ' . $countryId);

		$db->setQuery($query);

		$country = $db->loadObject();

		$countryName = $this->getCountryJText($country->country_jtext);

		if ($countryName)
		{
			return $countryName;
		}
		else
		{
			return $country->country;
		}
	}

	public function getCountryJText($countryJtext)
	{
		if ($this->_country_lang->hasKey(strtoupper($countryJtext)))
		{
			return JText::_($countryJtext);
		}
		else if ($countryJtext !== '')
		{
			return null;
		}
	}
}
