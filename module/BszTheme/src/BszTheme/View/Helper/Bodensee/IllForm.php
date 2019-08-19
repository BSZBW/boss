<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace BszTheme\View\Helper\Bodensee;

use Zend\View\Helper\AbstractHelper,
    Bsz\RecordDriver\SolrMarc;
;

/**
 * View helper for ill form
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class IllForm extends AbstractHelper
{

    const STATUS_NOT_SENT = -1;
    const STATUS_SENT_SUCCESS = 0;
    const STATUS_SENT_FAILURE = 1;

    protected $driver;
    protected $status;
    protected $params;
    
    public function __construct($params) 
    {
        $this->params = $params;
    }

    /**
     * 
     * @param boolean $status
     * @param SolrMarc $driver
     * @return \Bsz\View\Helper\IllForm
     */
    public function __invoke($status, $driver)
    {
        if ($driver instanceof SolrMarc) {
            $this->driver = $driver;
        }
        if ($status === true) {
            $this->status = static::STATUS_SENT_SUCCESS;
        } elseif ($status === false) {
            $this->status = static::STATUS_SENT_FAILURE;
        } else {
            $this->status = static::STATUS_NOT_SENT;
        }
        return $this;
    }

    /**
     * Determines which panels are open
     * @param string $this->status
     * @return boolean
     */
    public function isVisibleCopies()
    {
        $return = falsE;
        if ($this->status === static::STATUS_NOT_SENT && isset($this->driver)) {
            // form not yet submitted - open panels according to content
            $article = $this->driver->tryMethod('isArticle');
            $ebook = $this->driver->tryMethod('isEBook');
            $journal = $this->driver->tryMethod('isJournal');
            
            if ($article || $journal || $ebook) {
                $return =  true;
            }
      
        } 
        return $return;
    }

    /**
     * Get a text by given keyword
     * @param string $key
     * @return string
     */
    public function getText($key)
    {
        $texts = [
            'title' => '',
            'subtitle' => '',
            'hint' => '',
        ];
        switch ($this->status) {
            case static::STATUS_SENT_SUCCESS:
                $texts['headline'] = $this->view->transEsc('request_submit_ok');
                break;
            case static::STATUS_SENT_FAILURE:
                $texts['headline'] = $this->view->transEsc('request_submit_failure');
                break;
            default: $texts['headline'] = $this->view->transEsc('request_submit_text');
        }
        if (isset($this->driver)) {
            $article = $this->driver->tryMethod('isArticle');
            $ebook = $this->driver->tryMethod('isEBook');
            $journal = $this->driver->tryMethod('isJournal');

            if ($article) {
                $texts['title'] = $this->driver->getContainerTitle();
                $texts['subtitle'] = '';
                $texts['hint'] = $this->view->transEsc('help_paper');
            } elseif ($journal) {
                $texts['title'] = $this->driver->getShortTitle();
                $texts['subtitle'] = $this->driver->getSubTitle();
                $texts['hint'] = $this->view->transEsc('help_paper');                
            } elseif ($ebook) {
                $texts['title'] = $this->driver->getShortTitle();
                $texts['subtitle'] = $this->driver->getSubtitle();
                $texts['hint'] = $this->view->transEsc('help_ebooks');
            } else {
                $texts['title'] = $this->driver->getShortTitle();
                $texts['subtitle'] = $this->driver->getSubtitle();
                $texts['hint'] = $this->view->transEsc('help_paper');
            }
        }
        return $texts[$key];
    }
    
    public function renderBibliographicFields() {
        if ($this->driver !== null) {
            if ($this->driver->isArticle()){
                return $this->renderBibliographicFieldsArticle();
            } else if ($this->driver->isJournal() 
                    || $this->driver->isNewspaper()) {
                return $this->renderBibliographicFieldsJournal();            
            } if ($this->driver->isBook() || $this->driver->isEBook()) {
                return $this->renderBibliographicFieldsBook();
            }            
        } else {
            return $this->renderBibliographicFieldsFreeForm();
        }
    }

    /**
     * 
     * @return string|html
     */
    protected function renderBibliographicFieldsBook()
    {
        $authors = $this->getFromDriver('getAllAuthorsShort');        
        
        // the first is the label, the second the fieldname, third the value
        // arrays in value are allowed, they are imploded later        
        $fields = [
            ['Author', 'Verfasser', $authors],
            ['Title', 'Titel', $this->getText('title'), '', true],
            ['Subtitle', 'Untertitel', $this->getText('subtitle')],
//                ['Edition', 'Auflage', $this->driver->getEdition()],
            ['Edition', 'Auflage'],
            ['Publisher', 'Verlag', $this->getFromDriver('getPublishers')],
            ['Publication_Place', 'EOrt', $this->getFromDriver('getPlacesOfPublication')],
            ['Year of Publication', 'EJahr', $this->getFromDriver('getPublicationDates'), '', true, 'ILL::error_year'],
            ['VolumeTitle', 'BandTitel', $this->getFromDriver('getVolume')],
            ['Volume', 'Band'],
            ['ISBN', 'Isbn', $this->getFromDriver('getCleanISBN')],
        ];
        return $this->renderFormFields($fields);
        
    }
    /**
     * Renders Bibliographic Fields in form 
     * @return string|html
     */
    protected function renderBibliographicFieldsArticle()
    {
        // the first is the label, the second the fieldname, third the value
        // arrays in value are allowed, they are imploded later
        // most fields here are populated from the containg record
        $container = $this->driver->getContainer();
        $container = array_shift($container);
        $author = '';
        $title = $this->getFromDriver('getContainerTitle', $this->driver);
        // author printed only if container is a monography. 
        if ($this->driver->isContainerMonography() && $container !== null) {
            $author = $this->getFromDriver('getSecondaryAuthorsShort', $container);  
            $title = $this->getFromDriver('getShortTitle', $container);
        } 
        $fields = [
            ['Author', 'Verfasser', $author],
            ['Title', 'Titel', $title,'', true],
            ['Subtitle', 'Untertitel', $this->getFromDriver('getSubtitle', $container)],
            ['Edition', 'Auflage', $this->getFromDriver('getEditions', $container)],
            ['Publisher', 'Verlag', $this->getFromDriver('getPublishers', $container)],
            ['Publication_Place', 'EOrt', $this->getFromDriver('getPlacesOfPublication', $container)],
            // we must get year and issue from the actual driver object
            ['Year of Publication', 'EJahr', $this->getFromDriver('getPublicationDates'), '', true, 'ILL::error_year'],
            ['Issue', 'Heft', $this->getFromDriver('getContainerIssue')],
        ];
        if ($this->driver->isContainerMonography()) {
            array_push($fields, ['ISBN', 'Isbn', $this->getFromDriver('getCleanISBN', $container)]);
        } else {
            array_push($fields, ['ISSN', 'Issn', $this->getFromDriver('getCleanISSN', $container)]);            
        }
        
        return $this->renderFormFields($fields);
    }
    /**
     * Renders Bibliographic Fields in form 
     * @return string|html
     */
    protected function renderBibliographicFieldsJournal()
    {
        // the first is the label, the second the fieldname, third the value
        // arrays in value are allowed, they are imploded later
        $fields = [
//            ['Author', 'Verfasser', $this->getFromDriver('getPrimaryAuthor')],
            ['Journal Title', 'Titel', $this->getText('title')],
            ['Subtitle', 'Untertitel', $this->getText('subtitle')],
            ['Publisher', 'Verlag', $this->getFromDriver('getPublishers')],
            ['Publication_Place', 'EOrt', $this->getFromDriver('getPlacesOfPublication')],
            ['storage_retrieval_request_year', 'Jahrgang', '', '', true, 'ILL::error_year'],
            ['Issue', 'Heft'],
            ['Volume', 'Band'],
            ['ISSN', 'Issn', $this->getFromDriver('getCleanISSN')],
        ];
        return $this->renderFormFields($fields);        
    }
    
    protected function renderBibliographicFieldsFreeForm() 
    {
        $fields = [
            ['Title', 'Titel', '', '', true],
            ['Subtitle', 'Untertitel', ''],
            ['Author', 'Verfasser'],
            ['Edition', 'Auflage'],            
            ['Publisher', 'Verlag', ''],
            ['Year/Volume', 'Jahr', '', '', true, 'ILL::error_year'],
            ['Publication_Place', 'EOrt', ''],
            ['VolumeTitle', 'BandTitel'],
            ['Volume', 'Band'],
            ['Issue', 'Heft'],
            ['ISSN', 'Issn', ''],
            ['ISBN', 'Isbn', ''],
        ];
        return $this->renderFormFields($fields);        
        
    }
    

    /**
     * Render copy section
     * @return string|html
     */
    public function renderCopies() 
    {
        if (isset($this->driver) && $this->driver->isJournal()) {
            $fields = [
                ['article author', 'AufsatzAutor', '', '', true],
                ['article title', 'AufsatzTitel', '', '', true],
                ['pages', 'Seitenangabe', '', '', true, 'ILL::error_pages']
            ];              
            if (isset($this->driver) && $this->driver->isContainerMonography()) {
                $fields[] = ['storage_retrieval_request_volume', 'Jahrgang', '', 'ILL::placeholder_article', false, 'ILL::error_year' ];
            }
        } elseif (isset($this->driver) && $this->driver->isArticle()) {
            $fields = [
                ['article author', 'AufsatzAutor', $this->getFromDriver('getPrimaryAuthor'), '', true],
                ['article title', 'AufsatzTitel', $this->getFromDriver('getTitle'), '', true],
                ['storage_retrieval_request_year', 'Jahrgang', $this->getFromDriver('getPublicationDates'),'',  true, 'ILL::error_year'],
                ['pages', 'Seitenangabe', $this->getFromDriver('getContainerPages'),'',  true, 'ILL::error_pages'],
            ];              
        } elseif (isset($this->driver) && $this->driver->isBook()) {
            $fields = [
                ['article author', 'AufsatzAutor', ''],
                ['article title', 'AufsatzTitel', ''],
                ['pages', 'Seitenangabe', '', '', false, 'ILL::error_pages'],                
            ];              
        } else {
            $fields = [
                ['article author', 'AufsatzAutor', ''],
                ['article title', 'AufsatzTitel', ''],
                ['pages', 'Seitenangabe', '', '', false, 'ILL::error_pages'],
            ];  
            
        }
        
      
        return $this->renderFormFields($fields);        
    }

    /**
     * Render a single form group
     * @param array $fields
     * @return string|html
     */
    protected function renderFormFields($fields)            
    {
        $html = '';
        foreach ($fields as $field) {
            // Fill up missing fields with null
            $null = array_fill(0, 7, null);
            $field = array_replace($null, $field);
            // if form was already sent, take values from params array
            if (array_key_exists($field[1], $this->params)) {
                  $field[2] = $this->params[$field[1]];
            }
            $html .= $this->renderInput($this->numericToAssoc($field));
        }     
        return $html;
    }
    
        /**
     * Renders a single Input
     * 
     * @param string $label
     * @param string $name
     * @param string $value
     * @param string $placeholder
     * @param booleanr $required
     * @param string $error
     * @param string $type only types that are similar to text inputs 
     * @return string
     */
    public function renderInput($params) {
        return $this->view->partial('Helpers/ill/form-group', [
            'label' => $params['label'], 
            'name' => $params['name'],
            'value' => $params['value'],
            'required' => isset($params['required']) ? (bool)$params['required'] : false,
            'error' => $params['error'],
            'placeholder' => $params['placeholder'],
            'type' => $params['type'],
        ]);
    }

    /**
     * Get a value from record driver or an empty string if driver is null
     * 
     * @param string $method
     * @param RecordDriver $driver
     * @param array $default
     * 
     * @return string
     */
    protected function getFromDriver($method, $driver = null, $default = [])
    {
        if ($driver === null) {
            $driver = $this->driver;
        }
        $result = '';
        if (isset($driver) && method_exists($driver, $method)) {
            $result = $driver->tryMethod($method);

            if (is_array($result)) {      
                $result = implode('; ', $result);
            }        
            
            if ($method == 'getContainerPages' && empty($result)) {
                $result = $driver->getContainerRaw();
            }
        } elseif (array_key_exists($method, $default)) {
            $result = $default[$method];
        }
        return $result;
    }
    
    protected function numericToAssoc($numericFields) 
    {
        $assocFields = [];
        foreach($numericFields as $key => $field) {
            switch($key) {
                case 0: $assoc = 'label';
                    break;
                case 1: $assoc = 'name';
                    break;
                case 2: $assoc = 'value';
                    break;
                case 3: $assoc = 'placeholder';
                    break;
                case 4: $assoc = 'required';
                    break;
                case 5: $assoc = 'error';
                    break;
                case 6: $assoc = 'type';
                    break;                
            }
            $assocFields[$assoc] = $field;
        }
        return $assocFields;
    }
    
    public function maxlength($name) {
        $maxlength = [
            'Band'      => 119,
            'BandTitel' => 1000,
            'Seitenangabe' => 50,
            'Bemerkung'    => 1500            
        ];
        if (array_key_exists($name, $maxlength)) {
            return 'maxlength="'.$maxlength[$name].'"';
        }
        return '';
    }


}
