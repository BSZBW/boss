<?php
namespace Swissbib\RecordDriver;

use \VuFind\RecordDriver\Missing as VFMissing;

class Missing extends VFMissing implements SwissbibRecordDriver
{

    /**
     * Get short title
     * Override base method to assure a string and not an array
     *
     * @return    String
     */
    public function getTitle()
    {
        try {
            $title = parent::getTitle();
        } catch (\Exception $e ) {
            $title = $this->translate('Title not available');
        }

        if (is_array($title)) {
            $title = reset($title);
        }

        return $title;
    }



    /**
     * Get short title
     * Override base method to assure a string and not an array
     *
     * @return    String
     */
    public function getShortTitle()
    {
        $shortTitle = parent::getShortTitle();

        if (is_array($shortTitle)) {
            $shortTitle = reset($shortTitle);
        }

        return $shortTitle;
    }

    //GH
    //Missing Typ wird bei der Tag - Suche aus verschiedensten Kontexten aufgerufen (vor allem Helper)
    //@Oliver
    //moegliche Varianten
    //a) gib sinnvollere Wert zurück wie die von mir schnell hingeshriebenen
    //b) Erweiterung zu a) baue z.B. eine Loesung mit Interfaces die fuer von uns erstellten Treiber festlegen,
    //dass ein Minimum an Verhalten erforderlich ist
    //c) muss man mal nachdenken....

    public function getCorporationNames($asString = true)
    {
        return "";

    }

    public function getSecondaryAuthors($asString = true)
    {
        return "";

    }

    public function getPrimaryAuthor($asString = true)
    {
        return "";

    }

    public function getHostItemEntry()
    {
        return array();
    }

    public function getGroup()
    {
        return "";
    }

    public function getOnlineStatus()
    {
        return false;
    }

    public function getUnions()
    {
        return array();
    }

    public function getFormatsTranslated()
    {
        return "";
    }

    public function getFormatsRaw()
    {
        return parent::getFormats();
    }

    /**
     * Get alternative title
     *
     * @return array
     */
    public function getAltTitle()
    {
        // TODO: Implement getAltTitle() method.
    }

    /**
     * get Cartographic Mathematical Data
     *
     * @return string
     */
    public function getCartMathData()
    {
        // TODO: Implement getCartMathData() method.
    }

    /**
     * Get structured subject vocabularies from predefined fields
     * Extended version of getAllSubjectHeadings()
     *
     * $fieldIndexes contains keys of fields to check
     * $vocabConfigs contains checks for vocabulary detection
     *
     * $vocabConfigs:
     * - ind: Value for indicator 2 in tag
     * - field: sub field 2 in tag
     * - fieldsOnly: Only check for given field indexes
     * - detect: The vocabulary key is defined in sub field 2.
     *      Don't use the key in the config (only used for local)
     *
     * Expected result:
     * [
     *        gnd => [
     *            600 => [{},{},{},...]
     *            610 => [{},{},{},...]
     *            620 => [{},{},{},...]
     *        ],
     *    rero => [
     *            600 => [{},{},{},...]
     *            610 => [{},{},{},...]
     *            620 => [{},{},{},...]
     *        ]
     * ]
     * {} is an assoc array which contains the field data
     *
     * @param boolean $ignoreControlFields Ignore control fields 0 and 2
     *
     * @return array
     */
    public function getAllSubjectVocabularies($ignoreControlFields = false)
    {
        // TODO: Implement getAllSubjectVocabularies() method.
    }

    /**
     * @return boolean
     */
    public function displayHoldings()
    {
        // TODO: Implement displayHoldings() method.
    }

    /**
     * @return  string
     */
    public function getUniqueID() {
        $uniqueID = parent::getUniqueID();

        return empty($uniqueID) ? '' : $uniqueID;
    }
}
