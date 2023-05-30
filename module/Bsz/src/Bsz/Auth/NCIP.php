<?php
/**
 * LDAP authentication class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
namespace Bsz\Auth;

use Bsz\Config\Libraries;
use VuFind\Exception\Auth as AuthException;
use DOMImplementation;
use SimpleXMLElement;
use VuFind\Auth\AbstractBase;

/**
 * LDAP authentication class
 *
 * @category VuFind
 * @package  Authentication
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
class NCIP extends AbstractBase
{
    const SUCCESS = 'Success';
    const AUTHENTICATION_FAILED = 'User Authentication Failed';
    const ACCESS_DENIED = 'User Access Denied';

    private $_ncip;
    private $_service;

    public $usernameType = 'Barcode Id';
    public $passwordType = 'PIN';
    public $userElementTypes = array( 'NameInformation',
        'UserAddressInformation',
        'ValidToDate',
        'BlockOrTrap',
        'UserLanguage',
        'UserPrivilege');

    public $connectTimeout = 10;
    public $timeout = 30;


    /**
     * @var Bsz\Config\Library;
     */
    protected $library;
    protected $isil;

    /**
     * @param \Zend\Session\ManagerInterface $sessionManager
     * @param Libraries $libraries
     * @param $isils
     */
    public function __construct(
        \Zend\Session\ManagerInterface $sessionManager,
        Libraries $libraries,
        $isils
    ) {
        $this->sessionManager = $sessionManager;
        $this->library = $libraries->getFirstActive($isils);
        $isil = array_shift($isils);
        $this->isil = $isil;
    }
    /**
     * Validate configuration parameters.  This is a support method for getConfig(),
     * so the configuration MUST be accessed using $this->config; do not call
     * $this->getConfig() from within this method!
     *
     * @throws AuthException
     * @return void
     */
    protected function validateConfig()
    {
        // Check for missing parameters:
        $requiredParams = ['host_production', 'host_test', 'fromAgencyId', 'toAgencyId'
        ];
        foreach ($requiredParams as $param) {
            $param .= ':'.$this->isil;
            if (!isset($this->config->NCIP->$param)
                || empty($this->config->NCIP->$param)
            ) {
                throw new AuthException(
                    "One or more NCIP parameters are missing. Check your config.ini!"
                );
            }
        }
    }

    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Zend\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request)
    {
        $username = trim($request->getPost()->get('username'));
        $password = trim($request->getPost()->get('password'));
        if ($username == '' || $password == '') {
            throw new AuthException('authentication_error_blank');
        }

        $message = $this->lookupUserMessage($username, $password);
        $response = $this->sendRequest($message);
        $result = $this->parseLookupUserResponse($response);
        if ($result['status'] === self::SUCCESS) {
            $user = $this->generateUser($result);
            return $user;
        } else {
            throw new AuthException('authentication_error_denied');
        }
    }

    /**
     * Prepare message to send to NCIP endpoint.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return string in xml format.
     */
    protected function lookupUserMessage($username, $password)
    {
        $this->ncipMessageDocument('LookupUser');
        $this->initiationHeaderNode();
        if ($password === null) {
            $this->uniqueUserIdNode($username);
        } else {
            $this->authenticationInputNodes($username, $password);
        }
        $this->userElementTypeNodes();

        return $this->_ncip->saveXML();
    }

    /**
     * Build a User object from details obtained via NCIP.
     *
     * @param array $result parsed result
     *
     * @return \VuFind\Db\Table\UserRow Object representing logged-in user.
     */
    protected function generateUser($result)
    {
        $myPrivilege = null;
        $user = $this->getUserTable()->getByUsername($result["uniqueUserId"]);
        // write userPrivilege in home_library of the user object
        $myUserPrivilege = explode(";", $result["userPrivilegeDescription"]);
        foreach ($myUserPrivilege as $group) {
            $mygroups=explode(":", $group);
            if (isset($mygroups[0]) && $mygroups[0]=="Statistik") {
                $myPrivilege=$mygroups[1];
            } else {
                $myPrivilege=null;
            }
        }
        $user->home_library = $myPrivilege;

        // Update the user in the database, then return it to the caller:
        $user->save();
        return $user;
    }

    /**
     * Add service elements to the ncip variable.
     *
     * @param string $service Service
     *
     * @return void
     */
    protected function ncipMessageDocument($service)
    {
        $domImpl = new DOMImplementation();
        $docType = $domImpl->createDocumentType(
            'NCIPMessage',
            '-//NISO//NCIP DTD Version 1.0//EN',
            'http://www.niso.org/ncip/v1_0/imp1/dtd/ncip_v1_0.dtd'
        );
        $this->_ncip = $domImpl->createDocument('', '', $docType);
        $this->_ncip->encoding = 'UTF-8';
        $this->_ncip->formatOutput = false;

        $this->_service = $this->_ncip->createElement($service);

        $ncipMessage = $this->_ncip->createElement('NCIPMessage');
        $ncipMessage->setAttribute('version', '1.0');
        $ncipMessage->appendChild($this->_service);

        $this->_ncip->appendChild($ncipMessage);
    }

    /**
     * Add agency to the header for the service variable.
     *
     * @return void
     */
    protected function initiationHeaderNode()
    {
        $fromAgencyIdNode = $this->_ncip->createElement('FromAgencyId');
        $fromAgencyIdNode->appendChild($this->schemeValueNode('UniqueAgencyId', $this->config->NCIP->{'fromAgencyId:'.$this->isil}));
        $toAgencyIdNode = $this->_ncip->createElement('ToAgencyId');
        $toAgencyIdNode->appendChild($this->schemeValueNode('UniqueAgencyId', $this->config->NCIP->{'toAgencyId:'.$this->isil}));

        $initiationHeaderNode = $this->_ncip->createElement('InitiationHeader');
        $initiationHeaderNode->appendChild($fromAgencyIdNode);
        $initiationHeaderNode->appendChild($toAgencyIdNode);

        $this->_service->appendChild($initiationHeaderNode);
    }

    /**
     * Add uniqueUserid to the service variable.
     *
     * @param string $username Username
     *
     * @return void
     */
    protected function uniqueUserIdNode($username)
    {
        $uniqueAgencyIdNode = $this->schemeValueNode('UniqueAgencyId', 'OG');

        $userIdentifierValueNode = $this->_ncip->createElement('UserIdentifierValue');
        $userIdentifierValueNode->nodeValue = htmlspecialchars($username, ENT_NOQUOTES, 'UTF-8');

        $uniqueUserIdNode = $this->_ncip->createElement('UniqueUserId');
        $uniqueUserIdNode->appendChild($uniqueAgencyIdNode);
        $uniqueUserIdNode->appendChild($userIdentifierValueNode);

        $this->_service->appendChild($uniqueUserIdNode);
    }

    /**
     * Set authenitcationInputeNodes.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return void
     */
    protected function authenticationInputNodes($username, $password)
    {
        $this->authenticationInputNode($username, $this->usernameType);
        $this->authenticationInputNode($password, $this->passwordType);
    }

    /**
     * Add single authenitcationInputeNode to the service variable.
     *
     * @param string $inputData Input data
     * @param string $inputType Input type
     *
     * @return void
     */
    protected function authenticationInputNode($inputData, $inputType)
    {
        $aDataFormatType = $this->schemeValueNode('AuthenticationDataFormatType', 'text');

        $aInputData = $this->_ncip->createElement('AuthenticationInputData');
        $aInputData->nodeValue = htmlspecialchars($inputData, ENT_NOQUOTES, 'UTF-8');

        $aInputType = $this->schemeValueNode('AuthenticationInputType', $inputType);

        $aInputNode = $this->_ncip->createElement('AuthenticationInput');
        $aInputNode->appendChild($aDataFormatType);
        $aInputNode->appendChild($aInputData);
        $aInputNode->appendChild($aInputType);

        $this->_service->appendChild($aInputNode);
    }

    /**
     * Set userElementTypeNodes to the service variable.
     *
     * @return void
     */
    protected function userElementTypeNodes()
    {
        foreach ($this->userElementTypes as $userElementType) {
            $this->_service->appendChild($this->schemeValueNode('UserElementType', $userElementType));
        }
    }

    /**
     * Build single node.
     *
     * @param string $nodeName Node name
     * @param string $value    Value for the node.
     *
     * @return Object node.
     */
    protected function schemeValueNode($nodeName, $value)
    {
        $schemeNode = $this->_ncip->createElement('Scheme');
        $valueNode = $this->_ncip->createElement('Value');
        $valueNode->nodeValue = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');

        $schemeValueNode = $this->_ncip->createElement($nodeName);
        $schemeValueNode->appendChild($schemeNode);
        $schemeValueNode->appendChild($valueNode);

        return $schemeValueNode;
    }


    /**
     * Send message via curl.
     *
     * @param string $message Message
     *
     * @return Object response.
     */
    protected function sendRequest($message)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('Curl extension missing.');
        }
        if (!is_int($this->connectTimeout) || $this->connectTimeout <= 0) {
            throw new Exception('NCIPConnection.connectTimeout invalid');
        }
        if (!is_int($this->timeout) || $this->timeout <= 0) {
            throw new Exception('NCIPConnection.timeout invalid');
        }

        $this->_curlHandle = curl_init();
        if ($this->_curlHandle === false) {
            curl_error($this->_curlHandle);
        }
        if (curl_setopt($this->_curlHandle, CURLOPT_POST, 1) === false) {
            curl_error($this->_curlHandle);
        }
        if ($this->library->isLive()) {
            $url = $this->config->NCIP->{'url_production:'.$this->isil};
        } else {
            $url = $this->config->NCIP->{'url_test:'.$this->isil};
        }

        if (curl_setopt($this->_curlHandle, CURLOPT_URL, $url) === false
            || curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, 1) === false
            || curl_setopt($this->_curlHandle, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout) === false
            || curl_setopt($this->_curlHandle, CURLOPT_TIMEOUT, $this->timeout) === false
        ) {
            curl_error($this->_curlHandle);
        }
        if (curl_setopt($this->_curlHandle, CURLOPT_POST, 1) === false
            || curl_setopt($this->_curlHandle, CURLOPT_POSTFIELDS, $message) === false
        ) {
            curl_error($this->_curlHandle);
        }

        $contents = curl_exec($this->_curlHandle);
        if ($contents === false) {
            curl_error($this->_curlHandle);
        }
        curl_close($this->_curlHandle);

        return $contents;
    }

    /**
     * Parse the ncip response.
     *
     * @param string $response Response from the ncip api.
     *
     * @return Object result.
     */
    protected function parseLookupUserResponse($response)
    {
        try {
            $xml = new SimpleXmlElement($response);
        } catch (Exception $e) {
            error_log('failed to parse ncip response: ' . $response);
            return false;
        }
        $result = array(
            'xml' => $response
        );

        if (! isset($xml->LookupUserResponse)) {
            error_log('no LookupUserResponse in ncip response: ' . $response);
            return false;
        }
        $lur = $xml->LookupUserResponse;
        if (isset($lur->Problem)) {
            $problem = $lur->Problem;
            if (! isset($problem->ProcessingError) || ! isset($problem->ProcessingError->ProcessingErrorType)
                || ! isset($problem->ProcessingError->ProcessingErrorType->Value)
                || ! isset($problem->ProcessingError->ProcessingErrorElement)
                || ! isset($problem->ProcessingError->ProcessingErrorElement->ElementName) || ! in_array(
                    $problem->ProcessingError->ProcessingErrorElement->ElementName,
                    array(
                        'AuthenticationInputData',
                        'UserIdentifierValue'
                    )
                ) || ! isset($problem->ProcessingError->ProcessingErrorElement->ProcessingErrorValue)
            ) {
                error_log('unknown problem format in ncip response: ' . $response);
                return false;
            }
            $error = (string) $problem->ProcessingError->ProcessingErrorType->Value;
            if ($error === self::AUTHENTICATION_FAILED) {
                $result['status'] = self::AUTHENTICATION_FAILED;
            } elseif ($error = self::ACCESS_DENIED) {
                $result['status'] = self::ACCESS_DENIED;
            } else {
                error_log('unknown error in ncip response: ' . $error);
                return false;
            }
            $result['useridentifier'] = (string) $problem->ProcessingError->ProcessingErrorElement->ProcessingErrorValue;
        } else {
            if (! isset($lur->UniqueUserId) || ! isset($lur->UniqueUserId->UserIdentifierValue)) {
                error_log('no UniqueUserId UserIdentifierValue in ncip response: ' . $response);
                return false;
            }
            $result['status'] = self::SUCCESS;
            $result['uniqueUserId'] = (string) $lur->UniqueUserId->UserIdentifierValue;

            if (isset($lur->LoanedItemsCount)) {
                if (! isset($lur->LoanedItemsCount->LoanedItemCountValue)) {
                    error_log('no LoanedItemCountValue in ncip response: ' . $response);
                    return false;
                }
                $result['loanedItemsCount'] = (string) $lur->LoanedItemsCount->LoanedItemCountValue;
            }

            if (isset($lur->UserOptionalFields)) {
                $optional = $lur->UserOptionalFields;

                if (isset($optional->NameInformation) && isset($optional->NameInformation->PersonalNameInformation)
                    && isset($optional->NameInformation->PersonalNameInformation->UnstructuredPersonalUserName)
                ) {
                    $result['nameInformation'] = (string) $optional->NameInformation->PersonalNameInformation->UnstructuredPersonalUserName;
                } else {
                    error_log('no UnstructuredPersonalUserName in ncip response: ' . $response);
                }

                if (isset($optional->UserLanguage) && isset($optional->UserLanguage->Value)) {
                    $result['userLanguage'] = (string) $optional->UserLanguage->Value;
                } else {
                    error_log('no UserLanguage in ncip response: ' . $response);
                }

                if (isset($optional->UserPrivilege)) {
                    $privilege = $optional->UserPrivilege;

                    if (isset($privilege->AgencyUserPrivilegeType) && isset($privilege->AgencyUserPrivilegeType->Value)) {
                        $result['agencyUserPrivilegeType'] = (string) $privilege->AgencyUserPrivilegeType->Value;
                    } else {
                        error_log('no AgencyUserPrivilegeType in ncip response: ' . $response);
                    }

                    if (isset($privilege->ValidToDate)) {
                        $result['validToDate'] = (string) $privilege->ValidToDate;
                    } else {
                        error_log('no ValidToDate in ncip response: ' . $response);
                    }

                    if (isset($privilege->UserPrivilegeDescription)) {
                        $result['userPrivilegeDescription'] = (string) $privilege->UserPrivilegeDescription;
                    } else {
                        error_log('no UserPrivilegeDescription in ncip response: ' . $response);
                    }
                } else {
                    error_log('no UserPrivilege in ncip response: ' . $response);
                }

                if (isset($optional->BlockOrTrap)) {
                    $result['blockOrTrap'] = array();
                    foreach ($optional->BlockOrTrap as $bot) {
                        if (isset($bot->BlockOrTrapType) && isset($bot->BlockOrTrapType->Value)) {
                            $result['blockOrTrap'][] = (string) $bot->BlockOrTrapType->Value;
                        } else {
                            error_log('no BlockOrTrapType in ncip response: ' . $response);
                        }
                    }
                }
            }
        }
        return $result;
    }
}
