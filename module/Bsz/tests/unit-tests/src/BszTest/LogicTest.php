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

namespace BszTest;

use Bsz\Exception;
use Bsz\ILL\Logic;
use BszTest\RecordDriver\SolrGviMarcTest;
use PHPUnit\Framework\TestCase;
use Laminas\Config\Config;

/**
 * Class LogicTest
 * @package  BszTest
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class LogicTest extends TestCase
{

    /**
     * @param string $methods
     * @param array $isils
     * @param string $ind
     *
     * @return Logic
     */
    private function getLogic($methods = '', $isils = [], $ind = 'a'): Logic
    {
        $methods = $methods ??
            "!Hebis8, !Free, !SerialOrCollection, ~JournalAvailable, Format, Indicator";
        $config = new Config([
            // we do not test anything that requires the Holding object, because it's
            // difficult to create here
            'Checks' => [
                'methods' => $methods
            ],
            'Book' => [
                'enabled' => true,
                'indicator' => [$ind]
            ]

        ]);
        $logic = new Logic($config, $isils);
        return $logic;
    }

    public function testBasicLogic()
    {
        $logic = $this->getLogic();
        $this->assertInstanceOf(Logic::class, $logic);
        $this->expectException(Exception::class);
        $logic->isAvailable();
    }

    public function testAlwaysTrueModifier()
    {
        $recordtest = new SolrGviMarcTest();
        $record = $recordtest->getSolrRecord('journal.json');
        // DE-16 has local holdings for this record
        $logic = $this->getLogic('~JournalAvailable', ['DE-16']);
        $logic->attachDriver($record);
        $this->assertTrue($logic->isAvailable());
        // DE-17 does not have local holdings
        $logic = $this->getLogic('~JournalAvailable', ['DE-17']);
        $logic->attachDriver($record);
        $this->assertTrue($logic->isAvailable());
    }

    /**
     *
     */
    public function testNegateModifier()
    {
        $recordtest = new SolrGviMarcTest();
        // record is a collection
        $record = $recordtest->getSolrRecord('brockhaus.json');
        $logic = $this->getLogic('!serialOrCollection', ['DE-16']);
        $logic->attachDriver($record);
        // should return false because of the negation
        $this->assertFalse($logic->isAvailable());

        // this record is a normal book, no collection or serial
        $record = $recordtest->getSolrRecord('repetitorium.json');
        $logic->attachDriver($record);
        // test returns true
        $this->assertTrue($logic->isAvailable());
    }

    /**
     * Test checks if there is any holding the the allowed indicator set
     */
    public function testIndicatorEvaluation()
    {
        $recordtest = new SolrGviMarcTest();
        $record = $recordtest->getSolrRecord('repetitorium.json');

        $logic = $this->getLogic('Indicator');
        $logic->attachDriver($record);
        $this->assertFalse($logic->isAvailable());

        $logic = $this->getLogic('Indicator', ['DE-3'], 'c');
        $logic->attachDriver($record);
        $this->assertTrue($logic->isAvailable());

        $logic = $this->getLogic('Indicator', ['DE-3'], 'd');
        $logic->attachDriver($record);
        $this->assertTrue($logic->isAvailable());


    }
}
