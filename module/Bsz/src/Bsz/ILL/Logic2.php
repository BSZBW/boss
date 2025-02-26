<?php

namespace Bsz\ILL;

use Bsz\Exception;
use Bsz\RecordDriver\SolrMarc;
use Laminas\Config\Config;

class Logic2
{

    const FORMAT_EJOURNAL = 'Ejournal';
    const FORMAT_JOURNAL = 'Journal';
    const FORMAT_EBOOK = 'Ebook';
    const FORMAT_BOOK = 'Book';
    const FORMAT_MONOSERIAL = 'MonoSerial';
    const FORMAT_ARTICLE = 'Article';
    const FORMAT_UNDEFINED = 'Undefined';

    protected $config;

    /**
     * @var SolrMarc
     */
    protected $driver;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var Holding
     */
    protected $holding;
    /**
     * @var array
     */
    protected $localIsils;
    protected $swbppns = [];
    protected $parallelppns = [];
    protected $linklabels = [];
    protected $messages = [];
    protected $libraries = [];

    protected $ppnMessages = [];
    /**
     * @var array
     */
    protected $status;

    /**
     * @param Config $config
     * @param Holding $holding
     * @param array $isils
     */
    public function __construct(Config $config, $isils = [])
    {
        $this->config = $config;
        $this->localIsils = $isils;
        $this->localIsils[] = 'LFER';
    }

    /**
     * Each instance of this class can be used for many RecordDriver instances,
     * but not at the same time.
     *
     * @param SolrMarc $driver
     */
    public function attachDriver(SolrMarc $driver)
    {
        $this->driver = $driver;
        $this->format = $this->getFormat();
        $this->status = [];
        $this->swbppns = [];
        $this->parallelppns = [];
        $this->linklabels = [];
        $this->ppnMessages = [];
    }

    /**
     * Map the driver formats to more simple ILL formats
     * @return string
     * @throws Exception
     */
    private function getFormat()
    {
        $format = static::FORMAT_UNDEFINED;

        if (null === $this->driver) {
            throw new Exception('No driver set. Please attach a driver before use. ');
        }

        if ($this->driver->isElectronic()) {
            if ($this->driver->isJournal() || $this->driver->isNewspaper()) {
                $format = static::FORMAT_EJOURNAL;
            } elseif ($this->driver->isElectronicBook()) {
                $format = static::FORMAT_EBOOK;
            }
        } else {
            // Print items
            if ($this->driver->isMonographicSerial()) {
                $format = static::FORMAT_MONOSERIAL;
            } elseif ($this->driver->isPhysicalBook()) {
                $format = static::FORMAT_BOOK;
            } elseif ($this->driver->isArticle()) {
                $format = static::FORMAT_ARTICLE;
            } elseif ($this->driver->isJournal() ||
                $this->driver->isNewsPaper()
            ) {
                $format = static::FORMAT_JOURNAL;
            }
        }
        return $format;
    }

    /**
     * @param Holding $holding
     */
    public function attachHoldings(Holding $holding)
    {
        $this->holding = $holding;
    }

    public function getStatus()
    {

    }
}