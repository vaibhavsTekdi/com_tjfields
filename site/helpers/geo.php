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
		$this->_tjlang = JFactory::getLanguage();
		$this->_tjlang->load('tjgeo.countries', JPATH_SITE, null, false, true);
		$this->_tjlang->load('tjgeo.regions', JPATH_SITE, null, false, true);
		$this->_db = JFactory::getDbo();
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
		$query = $this->_db->getQuery(true);
		$query->select('country, country_jtext');
		$query->from('#__tj_country');
		$query->where('id = ' . $countryId);

		$this->_db->setQuery($query);

		$country = $this->_db->loadObject();

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
		if ($this->_tjlang->hasKey(strtoupper($countryJtext)))
		{
			return JText::_($countryJtext);
		}
		else if ($countryJtext !== '')
		{
			return null;
		}
	}

	/**
	 * Gives country list.
	 *
	 * @since   2.2
	 * @return   countryList
	 */
	public function getCountryList($component_nm = "")
	{
		$query = $this->_db->getQuery(true);
		$query->select("`id`, `country`,`country_jtext`")
		->from('#__tj_country');

		if ($component_nm)
		{
			$query->where($component_nm . "=1");
		}

		$query->order($this->_db->escape('ordering ASC'));
		$this->_db->setQuery((string) $query);
		return $this->_db->loadAssocList();
	}

	function getRegionList($country_id,$component_nm="")
	{
		$this->_db = JFactory::getDBO();
		$query = $this->_db->getQuery(true);
		$query->select("id AS region_id, region");
		$query->from('#__tj_region');
		$query->where('country_id='.$this->_db->quote($country_id));

		if ($component_nm)
		{
			$query->where($component_nm . "=1");
		}

		$this->_db->setQuery((string)$query);
		return $this->_db->loadAssocList();
	}


	function getRegionListFromCountryID($countryId)
	{
		if (is_numeric($countryId))
		{
			$query="SELECT r.id,r.region FROM #__tj_region AS r LEFT JOIN #__tj_country as c
					ON r.country_id=c.id where c.id=\"".$countryId."\"";
			$this->_db->setQuery($query);
			$rows = $this->_db->loadAssocList();
			return $rows;
		}
	}
	/**
	 * Method gives region name ( for current language if exist) from  region Id.
	 *
	 * @param   string  $regionId .
	 *
	 * @since   1.1
	 * @return   Region name;
	 */
	public function getRegionNameFromId($regionId)
	{
		$query = $this->_db->getQuery(true);
	 	$query->select('region, region_jtext');
		$query->from('#__tj_region');
		$query->where('id = ' . $regionId);
		$this->_db->setQuery($query);
		$res = $this->_db->loadObject();

		// Get jtext value.
		$jtext = $this->getRegionJText($res->region_jtext);

		if ($jtext)
		{
			return $jtext;
		}
		else
		{
			return $res->region;
		}
	}

	/**
	 * Method gives region name in current  language if exist.
	 *
	 * @param   string  $regionId .
	 *
	 * @since   1.1
	 * @return   Region name;
	 */
	public function getRegionJText($jtext)
	{
		if ($this->_tjlang->hasKey(strtoupper($jtext)))
		{
			return JText::_($jtext);
		}
		else if ($jtext !== '')
		{
			return null;
		}
	}
}
