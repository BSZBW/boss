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
namespace VuFindTest\UrlShortener;

use Exception;
use PHPUnit\Framework\TestCase;
use VuFind\Db\Table\Shortlinks;
use VuFind\UrlShortener\Database;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Adapter\Driver\DriverInterface;

/**
 * "Database" URL shortener test.
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class DatabaseTest extends TestCase
{
    /**
     * Get the object to test.
     *
     * @param  object $table Database table object/mock
     *
     * @return Database
     */
    public function getShortener($table)
    {
        return new Database('http://foo', $table, 'RAnD0mVuFindSa!t');
    }

    /**
     * Get the mock table object.
     *
     * @param  array $methods Methods to mock.
     *
     * @return object
     */
    public function getMockTable($methods)
    {
        return $this->getMockBuilder(Shortlinks::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * Test that the shortener works correctly under "happy path."
     *
     * @return void
     *
     * @throws Exception
     */
    public function testShortener()
    {
        $connection = $this->getMockBuilder(ConnectionInterface::class)
            ->setMethods(
                [
                    'beginTransaction', 'commit', 'connect', 'getResource',
                    'isConnected', 'getCurrentSchema', 'disconnect', 'rollback',
                    'execute', 'getLastGeneratedValue'
                ]
            )->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $driver = $this->getMockBuilder(DriverInterface::class)
            ->setMethods(
                [
                    'getConnection', 'getDatabasePlatformName', 'checkEnvironment',
                    'createStatement', 'createResult', 'getPrepareType',
                    'formatParameterName', 'getLastGeneratedValue'
                ]
            )->disableOriginalConstructor()
            ->getMock();
        $driver->expects($this->once())->method('getConnection')
            ->will($this->returnValue($connection));
        $adapter = $this->getMockBuilder(Adapter::class)
            ->setMethods(['getDriver'])
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->once())->method('getDriver')
            ->will($this->returnValue($driver));
        $table = $this->getMockTable(['insert', 'select', 'getAdapter']);
        $table->expects($this->once())->method('insert')
            ->with($this->equalTo(['path' => '/bar', 'hash' => 'a1e7812e2']));
        $table->expects($this->once())->method('getAdapter')
            ->will($this->returnValue($adapter));
        $mockResults = $this->getMockBuilder(ResultSet::class)
            ->setMethods(['count', 'current'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResults->expects($this->once())->method('count')
            ->will($this->returnValue(0));
        $table->expects($this->once())->method('select')
            ->with($this->equalTo(['hash' => 'a1e7812e2']))
            ->will($this->returnValue($mockResults));
        $db = $this->getShortener($table);
        $this->assertEquals('http://foo/short/a1e7812e2', $db->shorten('http://foo/bar'));
    }

    /**
     * Test that resolve is supported.
     *
     * @return void
     *
     * @throws Exception
     */
    public function testResolution()
    {
        $table = $this->getMockTable(['select']);
        $mockResults = $this->getMockBuilder(ResultSet::class)
            ->setMethods(['count', 'current'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResults->expects($this->once())->method('count')
            ->will($this->returnValue(1));
        $mockResults->expects($this->once())->method('current')
            ->will($this->returnValue(['path' => '/bar', 'hash' => '8ef580184']));
        $table->expects($this->once())->method('select')
            ->with($this->equalTo(['hash' => '8ef580184']))
            ->will($this->returnValue($mockResults));
        $db = $this->getShortener($table);
        $this->assertEquals('http://foo/bar', $db->resolve('8ef580184'));
    }

    /**
     * Test that resolve errors correctly when given bad input
     *
     * @return void
     *
     * @throws Exception
     */
    public function testResolutionOfBadInput()
    {
        $this->expectExceptionMessage('Shortlink could not be resolved: abcd12?');

        $table = $this->getMockTable(['select']);
        $mockResults = $this->getMockBuilder(ResultSet::class)
            ->setMethods(['count'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResults->expects($this->once())->method('count')
            ->will($this->returnValue(0));
        $table->expects($this->once())->method('select')
            ->with($this->equalTo(['hash' => 'abcd12?']))
            ->will($this->returnValue($mockResults));
        $db = $this->getShortener($table);
        $db->resolve('abcd12?');
    }

    /**
     * Test that resolve errors correctly when given bad input
     *
     * @return void
     *
     * @throws Exception
     */
    public function testResolutionOfOldIds()
    {
        $table = $this->getMockTable(['select']);
        $mockResults = $this->getMockBuilder(ResultSet::class)
            ->setMethods(['count', 'current'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResults->expects($this->once())->method('count')
            ->will($this->returnValue(1));
        $mockResults->expects($this->once())->method('current')
            ->will($this->returnValue(['path' => '/bar', 'hash' => 'A']));
        $table->expects($this->once())->method('select')
            ->with($this->equalTo(['hash' => 'A']))
            ->will($this->returnValue($mockResults));
        $db = $this->getShortener($table);
        $this->assertEquals('http://foo/bar', $db->resolve('A'));
    }
}
