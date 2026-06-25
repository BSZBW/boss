<?php
/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 */
namespace Bsz\RecordDriver\Response;

/**
 * Class encapsulating publication details.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class PublicationDetails
{
    /**
     * Place of publication
     *
     * @var string
     */
    protected $place;

    /**
     * Name of publisher
     *
     * @var string
     */
    protected $name;

    /**
     * Date of publication
     *
     * @var string
     */
    protected $date;

    /**
     * Constructor
     *
     * @param string $place Place of publication
     * @param string $name  Name of publisher
     * @param string $date  Date of publication
     */
    public function __construct($place, $name, $date)
    {
        $this->place = static::replaceDelimiters($place);
        $this->name = static::replaceDelimiters($name);
        $this->date = static::replaceDelimiters($date, ".?");
    }

    /**
     * Get place of publication
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Get name of publisher
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get date of publication
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Represent object as a string
     *
     * @return string
     */
    public function __toString()
    {
        $retval = [
            $this->getName() .
            $this->getName() && $this->getPlace() ? ' : ' : '',
            $this->getPlace(),
            ($this->getPlace() || $this->getName()) && $this->getDate() ? ', ' : '',
            $this->getDate()
        ];
        return implode('', $retval);
    }

    /**
     * @param string $input
     * @param string $chars
     *
     * @return string
     */
    private static function replaceDelimiters($input, string $chars = '') : string
    {
        $retval = '';
        if (is_string($input)) {
            $retval = preg_replace('/[\[\]]/m', '', $input);
            $pattern = "/\s?[:," . $chars . "]\s?$/";
            $retval = preg_replace($pattern, '', $retval);
            // remove braces
        }
        return $retval;
    }
}
