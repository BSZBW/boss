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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
namespace BszTest\RecordDriver;

use Bsz\Config\Client;
use Bsz\RecordDriver\SolrGviMarc;
use BszTest\ClientTest;
use PHPUnit\Framework\TestCase;

/**
 * Class SolrGviMarcTest
 * @package  BszTest\RecordDriver
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcTest extends TestCase
{

    /**
     * @param string $file
     *
     * @return SolrGviMarc
     */
    public function getSolrRecord($file = 'repetitorium.json'): SolrGviMarc
    {
        $config = $this->getClient();
        $record = new SolrGviMarc($config);
        $fixture = $this->loadRecordFixture($file);
        $record->setRawData($fixture['response']['docs'][0]);
        return $record;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected function getSolrRecords($file = '56records.json'): array
    {
        $config = $this->getClient();
        $fixture = $this->loadRecordFixture($file);
        $records = [];

        foreach ($fixture['response']['docs'] as $tmp) {
            $record = new SolrGviMarc($config);
            $record->setRawData($tmp);
            $records[] = $record;
        }
        return $records;
    }

    protected function getClient() : Client
    {
        $clienttest = new ClientTest();
        $config = $clienttest->getBasicConfig();
        return $clienttest->getClient($config);
    }

    /**
     * Load a fixture file.
     *
     * @param string $file File to load from fixture directory.
     *
     * @return array
     */
    protected function loadRecordFixture($file)
    {
        $path = APPLICATION_PATH.'/module/Bsz/tests/fixtures/solr/';
        return json_decode(
            file_get_contents(
                realpath(
                    $path.$file
                )
            ),
            true
        );
    }

    public function testFormatSimple()
    {
        $driver = $this->getSolrRecord();

        $yamlReader = new \VuFind\Config\YamlReader();
        $formatConfig = $yamlReader->get('MarcFormats.yaml');
        $driver->attachFormatConfig($formatConfig);

        $this->assertEquals($driver->getFormats(), ['Book']);
        $this->assertFalse($driver->isJournal());
        $this->assertFalse($driver->isArticle());
        $this->assertFalse($driver->isMonographicSerial());
        $this->assertFalse($driver->isElectronic());
        $this->assertFalse($driver->isFree());
        $this->assertFalse($driver->isNewspaper());
        $this->assertTrue($driver->isPhysicalBook());
    }

    public function testFormatExtended()
    {
        $driver = $this->getSolrRecord('repeated007.json');

        $yamlReader = new \VuFind\Config\YamlReader();
        $formatConfig = $yamlReader->get('MarcFormats.yaml');
        $formatConfigRda = $yamlReader->get('MarcFormatsRDA.yaml');
        $driver->attachFormatConfig($formatConfig, $formatConfigRda);
        $this->assertTrue($driver->isElectronic());
        $formatmarc = $driver->getFormatMarc();
        $formatrda = $driver->getFormatRda();
        $this->assertEquals($formatmarc, $formatrda);
    }

    /**
     * This test should warn if a format lost or deleted
     */
    public function testManyFormatMappings()
    {
        $drivers = $this->getSolrRecords();
        $differentFormats = [];
        foreach ($drivers as $driver) {
            $yamlReader = new \VuFind\Config\YamlReader();
            $formatConfig = $yamlReader->get('MarcFormats.yaml');
            $formatConfigRda = $yamlReader->get('MarcFormatsRDA.yaml');
            $driver->attachFormatConfig($formatConfig, $formatConfigRda);

            $formats = $driver->getFormats();
            $differentFormats = array_merge($formats, $differentFormats);
        }
        $differentFormats = array_unique($differentFormats);
        $this->assertEquals(count($differentFormats), 15);
    }

    /**
     * Test sorting and the removal of Book if there are
     * three entries
     */
    public function testSimplifyFormats()
    {
        $driver = $this->getSolrRecord('repeated007.json');
        $before = ['Book', 'Foo', 'Bar'];
        $after = $driver->simplifyFormats($before);
        $this->assertEquals(['Bar', 'Foo'], $after);

        $before = ['Book', 'Foo', 'Foo'];
        $after = $driver->simplifyFormats($before);
        $this->assertEquals(['Book', 'Foo'], $after);

        $before = [];
        $after = $driver->simplifyFormats($before);
        $this->assertEquals(['UnknownFormat'], $after);

    }




    public function testConsortium()
    {
        foreach ($this->getSolrRecords() as $driver) {
            $this->assertIsString($driver->getConsortium());
        }
    }

    public function testPublicationDetails()
    {
        foreach ($this->getSolrRecords() as $driver) {
            $publications = $driver->getPublicationDetails();

            foreach ($publications as $publication) {
                $place = $publication->getPlace();
                $year = $publication->getDate();
                $this->assertFalse(strpos($place, '['));

                // for multiple places, the year might be empty for the latter ones.
                if (!empty($year)) {
                    $this->assertMatchesRegularExpression('/\d{4}/', $year);
                }
            }
        }
    }

    public function testIsCollection()
    {
        $driver = $this->getSolrRecord('brockhaus.json');
        $this->assertTrue($driver->isCollection());
        $this->assertFalse($driver->isPart());
    }

    public function testIsPart()
    {
        $driver = $this->getSolrRecord('brockhaus_bd1.json');
        $this->assertTrue($driver->isPart());
        $this->assertFalse($driver->isCollection());
    }

    public function testIsMonoSerial()
    {
        $driver = $this->getSolrRecord('monoserial.json');
        $this->assertTrue($driver->isMonographicSerial());
        $this->assertFalse($driver->isCollection());
        $this->assertFalse($driver->isPart());
    }

    public function testLocalHoldings()
    {
        $clienttest = new ClientTest();
        $config = $clienttest->getBasicConfig();
        $config->Site->isil = 'DE-3';
        $record = new SolrGviMarc($config);
        $fixture = $this->loadRecordFixture('repetitorium.json');
        $record->setRawData($fixture['response']['docs'][0]);
        $holdings = $record->getLocalHoldings();
        $this->assertEquals(count($holdings), 2);
        foreach ($holdings as $holding) {
            $this->assertEquals($holding['isil'], 'DE-3');
        }
    }

    public function testContainerIds()
    {
        foreach ($this->getSolrRecords() as $driver) {
            $ids = $driver->getContainerIds();
            foreach ($ids as $id) {
                $this->assertNotRegExp('/\(DE-576\)/', $id);
                $this->assertNotRegExp('/\(DE-600\)/', $id);
                $this->assertMatchesRegularExpression('/\(DE-/', $id);
            }
        }
    }

    /**
     * this method uses date from 008, too.
     */
    public function testPublicationDates()
    {
        foreach ($this->getSolrRecords() as $driver) {
            $dates = $driver->getPublicationDates();
            foreach ($dates as $date) {
                $this->assertMatchesRegularExpression('/\d{4}/', $date);
            }
        }
    }

    public function testOpenUrl()
    {
        foreach ($this->getSolrRecords() as $driver) {
            $url = $driver->getOpenUrl();
            $this->assertStringContainsString('rft.genre', $url);
        }
    }

    public function testOriginalLanguage()
    {
        $driver = $this->getSolrRecords()[0];
        $oltitle = $driver->getOriginalLanguage('245', 'a');
        $this->assertNotEmpty($oltitle);
        $olfields = $driver->getOriginalLanguageArray([245 => ['a', 'b', 'c'], 264 => ['a', 'b', 'c']], ' + ');
        $this->assertIsArray($olfields);
        $this->assertEquals(count($olfields), 2);

        foreach ($olfields as $field) {
            $this->assertStringContainsString(' + ', $field);
        }
    }

    public function testField924NumericKeys()
    {
        $driver = $this->getSolrRecord();
        $f924 = $driver->getField924();
        $keys = array_keys($f924);
        foreach ($keys as $key) {
            $this->assertTrue(is_numeric($key));
        }
    }

    public function testField924CheckArrayContent()
    {
        $driver = $this->getSolrRecord();
        $f924 = $driver->getField924();

        foreach ($f924 as $field) {
            $this->assertTrue(array_key_exists('isil', $field));
            $this->assertTrue(strlen($field['ill_indicator']) == 1);
        }
    }

    public function testField924IsilFormat()
    {
        foreach ($this->getSolrRecords() as $driver) {
            $f924 = $driver->getField924();
            foreach ($f924 as $field) {
                $this->assertMatchesRegularExpression('/^DE-|^AT-|^LFER|^CH-/', $field['isil']);
            }
        }
    }

    public function testField924RepeatedSubfields()
    {
        $clienttest = new ClientTest();
        $config = $clienttest->getBasicConfig();
        $config->Site->isil = 'DE-N1';
        $record = new SolrGviMarc($config);
        $fixture = $this->loadRecordFixture('repeatedsubfields924.json');
        $record->setRawData($fixture['response']['docs'][0]);
        $localurls = $record->getLocalUrls();
        $this->assertEquals(count($localurls), 2);
        $holdings = $record->getLocalHoldings();
        $this->assertEquals(count($holdings), 1);
        $this->assertIsArray($holdings[0]['url']);
        $this->assertEquals($localurls[0]['label'], 'EZB');
        $this->assertEquals($localurls[1]['label'], 'Volltext');

        $config->Site->isil = 'DE-Fn1';
        $record = new SolrGviMarc($config);
        $fixture = $this->loadRecordFixture('repeatedsubfields924-DE-Fn1.json');
        $record->setRawData($fixture['response']['docs'][0]);
        $localurls = $record->getLocalUrls();
        $this->assertEquals(count($localurls), 1);
        $this->assertIsArray($localurls[0]['label']);
        $this->assertEquals(count($localurls[0]['label']), 2);
    }
}
