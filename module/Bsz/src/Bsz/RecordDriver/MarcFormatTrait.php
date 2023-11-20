<?php
/*
 * Copyright 2021 (C) Bibliotheksservice-Zentrum Baden-
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace Bsz\RecordDriver;

use Bsz\Exception;

trait MarcFormatTrait
{
    use AdvancedMarcReaderTrait;

    protected $formatConfig;
    protected $formatConfigRda;

    /**
     * @param array $marc
     * @param array $rda
     */
    public function attachFormatConfig(array $marc = [], array $rda = [])
    {
        $this->formatConfig = $marc;
        $this->formatConfigRda = $rda;
    }

    /**
     * Evaluate marc format fields as configured in MarcFormats.yaml
     * @return string
     * @throws Exception
     */
    public function getFormatMarc()
    {
        foreach ($this->formatConfig as $format => $settings) {
            $results = [];

            foreach ($settings as $setting) {
                if (!isset($setting['field'])) {
                    throw new Exception('Marc format mappings must have a field entry. ');
                }

                $params = [];
                if (isset($setting['position'])) {
                    $params = [$setting['position']];
                } elseif (isset($setting['subfield'])) {
                    $params = [$setting['subfield']];
                }

                $method = 'get' . $setting['field'];

                $content = $this->tryMethod($method, $params);
                $results[] = $this->checkValue($content, $setting['value']);

                // Better performance, stop checking if first test failed
                if (end($results) == false) {
                    continue;
                } elseif (count($results) == count($settings) && !in_array(false, $results)) {
                    $format = preg_replace('/\d/', '', $format);
                    return $format;
                }
            }
        }
        return '';
    }

    /**
     * Recursive method to determine if a value matches the given strings
     *
     * @param $value
     * @param $allowedValues
     *
     * @return bool
     */
    protected function checkValue($value, $allowedValues)
    {
        $allowed = explode(', ', $allowedValues);
        if (is_array($value)) {
            $result = [];
            foreach ($value as $v) {
                $result[] = $this->checkValue($v, $allowedValues);
            }
            return in_array(true, $result);
        }
        foreach ($allowed as $a) {
            $a = str_replace(['/', '[', ']'], '', $a);
            $regex = '/^' . $a . '/i';
            if (preg_match($regex, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Evaluate RDA format fields as configured in MarcFormatsRDA.yaml
     * @return string
     * @throws Exception
     */
    public function getFormatRda()
    {
        foreach ($this->formatConfigRda as $format => $settings) {
            $results = [];

            foreach ($settings as $setting) {
                if (!isset($setting['method'])) {
                    throw new Exception('RDA format mappings must have a method entry. ');
                }

                $content = $this->tryMethod($setting['method']);
                $results[] = $this->checkValue($content, $setting['value']);

                // Better performance, stop checking if first test failed
                if (end($results) == false) {
                    continue;
                } elseif (count($results) == count($settings) && !in_array(false, $results)) {
                    $format = preg_replace('/\d/', '', $format);
                    return $format;
                }
            }
        }
        return '';
    }

    public function simplifyFormats(array $formats)
    {
        $formats = array_filter($formats);
        $formats = array_unique($formats);
        $formats = array_values($formats);

        if (count($formats) >= 3 && in_array('Book', $formats)) {
            $formats = $this->removeFromArray($formats, 'Book');
        }
        if (empty($formats)) {
            $formats[] = 'UnknownFormat';
        }
        sort($formats);
        return (array)$formats;
    }

    /**
     * Helper method to remove a value from an array
     *
     * @param array $array
     * @param string|int $remove
     *
     * @return array
     */
    protected function removeFromArray(array $array, $remove)
    {
        foreach ($array as $k => $v) {
            if ($v == $remove) {
                unset($array[$k]);
            }
        }
        return $array;
    }

    /**
     * Nach der Dokumentation des Fernleihportals
     * @return boolean
     */

    public function isArticle(): bool
    {
        // A = Aufsätze aus Monographien
        // B = Aufsätze aus Zeitschriften (wird aber wohl nicht genutzt))
        // (-> laut Gerlind nur a)
        $l7 = $this->getLeader(7);
        return ($l7 == 'a');
    }

    public function isVolume(): bool
    {
        $l7 = $this->getLeader(7);
        $l19 = $this->getLeader(19);
        return ($l7 == 'm') && (($l19 == 'b') || ($l19 == 'c'));
    }

    /**
     * Is this a book serie?
     * @return boolean
     */
    public function isMonographicSerial(): bool
    {
        $f008 = null;
        $f008_21 = '';
        $f008 = $this->get008(21);
        if ($this->isSerial() && $f008 == 'm') {
            return true;
        }
        return false;
    }

    /**
     * Get a specified position of 008 or empty string
     *
     * @param int $pos
     *
     * @return string
     */

    protected function get008(int $pos): string
    {
        $f008 = $this->getField("008");
        if (is_string($f008)) {
            if (($pos >= 0) && ($pos < strlen($f008))) {
                return strtolower($f008[$pos]);
            }
        }
        return '';
    }

    /**
     * General serial items. More exact is:
     * isJournal(), isNewspaper() isMonographicSerial()
     * @return boolean
     */
    public function isSerial(): bool
    {
        return 's' === $this->getLeader(7);
    }

    /**
     * Ist der Titel ein Buch, das schließt auch eBooks mit ein!
     * Wertet den Leader aus
     * @return boolean
     */
    public function isPrintBook()
    {
        $leader_7 = $this->getLeader(7);
        $f007 = $this->get007();

        if ($this->isPhysical() && $leader_7 == 'm' && preg_match('/^t/i', $f007)) {
            return true;
        }
        return false;
    }

    /**
     * Get all 007 fields
     *
     * @param string $pattern
     *
     * @return array
     */

    protected function get007($pattern = '/.*/'): array
    {
        $f007 = $this->getFields("007");
        $retval = [];
        foreach ($f007 as $field) {
            if(!is_string($field)) {
                continue;
            }
            $tmp = substr($field, 0, 2);
            if (preg_match($pattern, $tmp)) {
                $tmp = str_pad($tmp, 2, STR_PAD_RIGHT);
                $retval[] = strtolower($tmp);
            }
        }
        return $retval;
    }

    /**
     * Everything that is not electronical is automatically physical.
     * @return bool
     */
    public function isPhysical(): bool
    {
        return !$this->isElectronic();
    }

    /**
     * Is this record an electronic item
     * @return boolean
     */

    public function isElectronic(): bool
    {
        $f007 = $this->get007('/^cr/i');
        $f008 = $this->get008(23);
        $f338 = $this->getRdaCarrier();
        $f300 = $this->get300('a');


        if (count($f007) > 0 || $f008 === 'o' || $f338 == 'cr' || $f300 == '1 online resource') {
            return true;
        }
        return false;
    }

    /**
     * Get RDA content code (field 338)
     * @return array
     */

    protected function getRdaCarrier(): array
    {
        $sub = '';
        $fields = $this->getFields(338);
        $retval = [];

        foreach ($fields as $field) {
            if (is_array($field)) {
                $sub = $this->getSubfield($field, 'b');
                $retval[] = strtolower($sub);
            }
        }
        return $retval;
    }

    /**
     * @param string $subfield
     *
     * @return string
     */
    protected function get300($subfield = 'a'): string
    {
        $field = $this->getField(300);
        if (is_array($field)) {
             return $this->getSubfield($field, $subfield);
        }
        return '';
    }

    /**
     * Ist der Titel ein EBook?
     * Wertet die Felder 007/00, 007/01 und Leader 7 aus
     * @return boolean
     */
    public function isElectronicBook(): bool
    {
        $f007 = $this->get007('/^cr/i');
        $leader = $this->getLeader(7);

        if ($this->isElectronic() && $leader == 'm') {
            return true;
        }
        return false;
    }

    /**
     * Ist der Titel ein Buch, das schließt auch eBooks mit ein!
     * Wertet den Leader aus
     * @return boolean
     */
    public function isPhysicalBook(): bool
    {
        $f007 = $this->get007('/^t/i');
        $leader = $this->getLeader(7);

        if ($leader == 'm' && count($f007) > 0) {
            return true;
        }
        return false;
    }

    /**
     * is this a Journal, implies it's a serial
     * @return boolean
     */
    public function isJournal(): bool
    {
        $f008 = $this->get008(21);

        if ($this->isSerial() && $f008 == 'p') {
            return true;
        }
        return false;
    }

    /**
     * iIs this a Newspaper?
     * @return boolean
     */
    public function isNewspaper(): bool
    {
        $f008 = $this->get008(21);

        if ($this->isSerial() && $f008 == 'n') {
            return true;
        }
        return false;
    }

    /**
     * Determine  if a record is freely available.
     * Indicator 2 references to the record itself.
     * @return boolean
     */
    public function isFree(): bool
    {
        $f856 = $this->getFields(856);
        foreach ($f856 as $field) {
            if (is_array($field) && $field['i2'] == 0) {
                $sfz = strtolower($this->getSubfield($field, 'z'));
                return preg_match('/^kostenlos|kostenfrei$/i', $sfz);
            }
        }
        return false;
    }

    /**
     * GEt RDA carrier code (field 336)
     * @return array
     */

    protected function getRdaContent(): array
    {
        $fields = $this->getFields(336);
        $retval = [];

        foreach ($fields as $field) {
            if (is_array($field)) {
                $retval[] = strtolower($this->getSubfield($field, 'b'));
            }
        }
        return $retval;
    }

    /**
     * GEt RDA media code (field 337)
     * @return array
     */

    protected function getRdaMedia(): array
    {
        $sub = '';
        $fields = $this->getFields(337);
        $retval = [];

        foreach ($fields as $field) {
            if (is_array($field)) {
                $retval[] = strtolower($this->getSubfield($fields, 'b'));
            }
        }
        return $retval;
    }

    /**
     * @param $subfield
     *
     * @return array
     */
    protected function get500(string $subfield = 'a'): array
    {
        $fields = $this->getFields(500);
        $retval = [];

        foreach ($fields as $field) {
            if (is_array($field)) {
                $retval[] = strtolower($this->getSubfield($field, $subfield));
            }
        }
        return $retval;
    }
}
