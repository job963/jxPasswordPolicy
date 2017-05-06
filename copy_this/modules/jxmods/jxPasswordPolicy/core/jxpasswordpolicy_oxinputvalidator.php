<?php

/*
 *    This file is part of the module jxPasswordPolicy for OXID eShop Community Edition.
 *
 *    The module jxPasswordPolicy for OXID eShop Community Edition is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    The module jxPasswordPolicy for OXID eShop Community Edition is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      https://github.com/job963/jxPasswordPolicy
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @copyright (C) Joachim Barthel 2017
 *
 */

class jxpasswordpolicy_oxinputvalidator extends jxpasswordpolicy_oxinputvalidator_parent
{
/**
     * Checking if user password is fine. In case of error
     * exception is thrown
     *
     * @param oxUser $oUser         active user
     * @param string $sNewPass      new user password
     * @param string $sConfPass     retyped user password
     * @param bool   $blCheckLength option to check password length
     *
     * @return oxException|null
     */
    public function checkPassword($oUser, $sNewPass, $sConfPass, $blCheckLength = false)
    {
        $oConfig = oxRegistry::get('oxConfig');
        
    $sLogPath = $oConfig->getConfigParam("sShopDir") . '/log/';
    $fh = fopen($sLogPath.'jxmods.log', "a+");
    fputs($fh, 'checkPassword: '.$sNewPass."\n");
    //fputs($fh,'numCR:'.print_r($oUser,true)."\n");
    fclose($fh);
    
        $oxException = parent::checkPassword($oUser, $sNewPass, $sConfPass, $blCheckLength);
        if ($oxException) {
            return $oxException;
        }
        
        $iMinLength = $oConfig->getConfigParam('sJxPasswordPolicyMinLength');
        if ($blCheckLength && getStr()->strlen($sNewPass) < $iMinLength) {
            $oEx = oxNew('oxInputException');
            $oEx->setMessage(oxRegistry::getLang()->translateString('ERROR_MESSAGE_PASSWORD_TOO_SHORT'));

            return $this->_addValidationError("oxuser__oxpassword", $oEx);
        }
        
        $iScore = 0;
        if ($oConfig->getConfigParam('bJxPasswordPolicyUpperCase')) {
            if (preg_match('/[A-Z]/', $sNewPass)) {
                $iScore++;
            }
        }
        
        if ($oConfig->getConfigParam('bJxPasswordPolicyLowerCase')) {
            if (preg_match('/[a-z]/', $sNewPass)) {
                $iScore++;
            }
        }
        
        if ($oConfig->getConfigParam('bJxPasswordPolicyNumbers')) {
            if (preg_match('/[0-9]/', $sNewPass)) {
                $iScore++;
            }
        }
        
        if ($oConfig->getConfigParam('bJxPasswordPolicySpecialChars')) {
            if (preg_match('/[[^a-zA-z0-9]/', $sNewPass)) {
                $iScore++;
            }
        }
        
        if ($iScore < $oConfig->getConfigParam('sJxPasswordPolicyMinNumCharRules')) {
            $oEx = oxNew('oxInputException');
            $oEx->setMessage(oxRegistry::getLang()->translateString('JXPASSWORDPOLICY_ERROR_TO_LOW_SCORE'));

            return $this->_addValidationError("oxuser__oxpassword", $oEx);
        }
        
        if (!empty($oUser->oxuser__oxid)) {
            if ($oConfig->getConfigParam('bJxPasswordPolicyMustntContainEmail')) {
                preg_match_all("/(.*)@(.*)\.(.*)/", $oUser->oxuser__oxusername->rawValue, $aResult, PREG_SET_ORDER);
                //move one level higher
                $aResult = $aResult[0];
                //remove element [0]
                array_shift($aResult);
                
                foreach ($aResult as $key => $sEmailPart) {
                    if (strpos(strtoupper($sNewPass), strtoupper($sEmailPart)) !== false) {
                        $oEx = oxNew('oxInputException');
                        $oEx->setMessage(oxRegistry::getLang()->translateString('JXPASSWORDPOLICY_ERROR_CONTAINS_EMAIL'));

                        return $this->_addValidationError("oxuser__oxpassword", $oEx);
                    }
                }
            }
            
            if ($oConfig->getConfigParam('bJxPasswordPolicyMustntContainCustNo')) {
                if (strpos($sNewPass, $oUser->oxuser__oxcustnr->rawValue) !== false) {
                        $oEx = oxNew('oxInputException');
                        $oEx->setMessage(oxRegistry::getLang()->translateString('JXPASSWORDPOLICY_ERROR_CONTAINS_CUSTNO'));

                        return $this->_addValidationError("oxuser__oxpassword", $oEx);
                }
            }
            
            if ($oConfig->getConfigParam('bJxPasswordPolicyMustntContainName')) {
                if ((strpos(strtoupper($sNewPass), strtoupper($oUser->oxuser__oxfname->rawValue)) !== false) || (strpos(strtoupper($sNewPass), strtoupper($oUser->oxuser__oxlname->rawValue)) !== false)) {
                        $oEx = oxNew('oxInputException');
                        $oEx->setMessage(oxRegistry::getLang()->translateString('JXPASSWORDPOLICY_ERROR_CONTAINS_NAME'));

                        return $this->_addValidationError("oxuser__oxpassword", $oEx);
                }
                
            }
        } else {
            echo 'NEUER USER'.'<br>';
        }
        
        return $oxException;
    }
    
}