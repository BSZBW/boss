<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\RecordDriver;

use Bsz\Exception;
use VuFind\RecordDriver\DefaultRecord;
use VuFind\RecordDriver\IlsAwareTrait;
use VuFind\RecordDriver\MarcAdvancedTrait;
use VuFind\RecordDriver\MarcReaderTrait;

/**
 * SolrMarc class for Findex records
 *
 * @author amzar
 */
class SolrFindexMarc extends SolrMarc implements Constants
{
    use IlsAwareTrait;
    use MarcReaderTrait;
    use MarcAdvancedTrait;
    use SubrecordTrait;
    use HelperTrait;
    use ContainerTrait;
    use MarcFormatTrait;
    use FivTrait;

    /**
     * Returns consortium
     * @return array
     * @throws Exception
     */
    public function getConsortium()
    {
        // determine network
        // GVK = GBV
        // SWB = SWB
        // ÖVK = GBV

        $consortium_unique = [];

        $consortium = DefaultRecord::tryMethod('getCollections');

        if ($consortium != null) {
            foreach ($consortium as $k => $con) {
                $mapped = $this->mainConfig->mapNetwork($con);
                if (!empty($mapped)) {
                    $consortium[$k] = $mapped;
                }
            }
            $consortium_unique = array_unique($consortium);
        }

        $string = implode(", ", $consortium_unique);
        return $string;
    }

    /**
     * Returns German library network shortcut.
     * @return string
     */
    public function getNetwork()
    {
        // TODO
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs() : array
    {
        //isbn = 020az:773z
        $isbn = array_merge(
// TODO welche Felder bei Findex
            $this->getFieldArray('020', ['a', 'z', '9'], false),
            $this->getFieldArray('773', ['z'])
        );
        return $isbn;
    }

    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISSNs() : array
    {
        // issn = 022a:440x:490x:730x:773x:776x:780x:785x
        $issn = array_merge(
// TODO, welche Felder bei Findex
            $this->getFieldArray('022', ['a']),
            $this->getFieldArray('029', ['a']),
            $this->getFieldArray('440', ['x']),
            $this->getFieldArray('490', ['x']),
            $this->getFieldArray('730', ['x']),
            $this->getFieldArray('773', ['x']),
            $this->getFieldArray('776', ['x']),
            $this->getFieldArray('780', ['x']),
            $this->getFieldArray('785', ['x'])
        );
        return $issn;
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs() : array
    {
        //url = 856u:555u

        $urls = [];
        $urlFields = array_merge($this->getMarcRecord()->getFields('856'),
                $this->getMarcRecord()->getFields('555'));
        foreach ($urlFields as $f) {
            $f instanceof File_MARC_Data_Field;
            $url = [];
            $sf = $f->getSubField('u');
            $ind1 = $f->getIndicator(1);
            $ind2 = $f->getIndicator(2);
            if (!$sf) {
                continue;
            }
            $url['url'] = $sf->getData();

            if (($sf = $f->getSubField('3')) && strlen($sf->getData()) > 2) {
                $url['desc'] = $sf->getData();
            } elseif (($sf = $f->getSubField('y'))) {
                $url['desc'] = $sf->getData();
            } elseif (($sf = $f->getSubField('n'))) {
                $url['desc'] = $sf->getData();
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'Online Access';
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'More Information';
            }
            $urls[] = $url;
        }
        return $urls;
    }



}
