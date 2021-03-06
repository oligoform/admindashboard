<?php

/**
 * This file is part of a maexware solutions module.
 *
 * This maexware solutions module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This maexware solutions module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with maexware solutions AdminDashboard modul.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      https://www.maexware-solutions.de
 * @copyright (C) maexware solutions GmbH 2018
 */

namespace maexware\AdminDashboard\Extensions\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

class mxAdminDashboard extends mxAdminDashboard_parent{

    /*
     * render
     * -----------------------------------------------------------------------------------------------------------------
     */
    public function render(){

        $myconfig = Registry::getConfig();

        // Orders Chart
        if ($myconfig->getConfigParam("mxAdminDashboard_orders") == '1') {
            $this->_aViewData['orderCharts'] = $this->getOrderOverview('m');
        } else {
            $this->_aViewData['orderCharts'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_payments") == '1') {
            $this->_aViewData['orderPayments'] = $this->getPaymentUsage();
        } else {
            $this->_aViewData['orderPayments'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_customerAcountTypes") == '1') {
            $this->_aViewData['userAccounts'] = $this->getUserAcounts();
        } else {
            $this->_aViewData['userAccounts'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_customerNewsletters") == '1') {
            $this->_aViewData['userNewsletterAccounts'] = $this->getOptInNewsletters();
        } else {
            $this->_aViewData['userNewsletterAccounts'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_articleTopAll") == '1') {
            $this->_aViewData['articlesTopseller'] = $this->getTopSellerArticles(10,false);
        } else {
            $this->_aViewData['articlesTopseller'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_articleTopActive") == '1') {
            $this->_aViewData['articlesTopsellerOnlyActive'] = $this->getTopSellerArticles(10,true);
        } else {
            $this->_aViewData['articlesTopsellerOnlyActive'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_topsellerCategories") == '1') {
            $this->_aViewData['topCats'] = $this->getCatsFromTopseller();
        } else {
            $this->_aViewData['topCats'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_customerBought") == '1') {
            $this->_aViewData['customerBought'] = $this->getCustomerBoughtDatas();
        } else {
            $this->_aViewData['customerBought'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_orderValues") == '1') {
            // orders today
            $oOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
            $oLang = \OxidEsales\Eshop\Core\Registry::getLang();

            $dSum = $oOrder->getOrderSum(true);

            $this->_aViewData["ordersum"] = $oLang->formatCurrency($dSum);
            $this->_aViewData["ordercnt"] = $oOrder->getOrderCnt(true);
            // ALL orders

            $dSum = $oOrder->getOrderSum();
            $this->_aViewData["ordertotalsum"] = $oLang->formatCurrency($dSum);
            $this->_aViewData["ordertotalcnt"] = $oOrder->getOrderCnt();
            $this->_aViewData["afolder"] = $this->getConfig()->getConfigParam('aOrderfolder');
            $this->_aViewData["alangs"] = $oLang->getLanguageNames();

            $this->_aViewData["aOrderVals"] = $this->getOrderValues();
            $this->_aViewData["iOrderMonthCount"] = $this->getOrderCountFromMonth();
        } else {
            $this->_aViewData['aOrderVals'] = 'DONTSHOW';
        }

        $this->_aViewData['aMessage'] = $this->_doStartUpChecks();

        if ($myconfig->getConfigParam("mxAdminDashboard_ordersStorno") == '1') {
            $this->_aViewData['aQualityOrders'] = $this->getQualityValues4Orders();
        } else {
            $this->_aViewData['aQualityOrders'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_ordersState") == '1') {
            $this->_aViewData['aOrderFolderStates'] = $this->getFolderStatesFromOrders();
        } else {
            $this->_aViewData['aOrderFolderStates'] = 'DONTSHOW';
        }

        if ($myconfig->getConfigParam("mxAdminDashboard_articleInfos") == '1') {
            $this->_aViewData['aArticleOnlyDatas'] = $this->getArticleOnlyDatas();
        } else {
            $this->_aViewData['aArticleOnlyDatas'] = 'DONTSHOW';
        }

        if (Registry::getConfig()->getRequestParameter("fnc") == 'changeOrderChartView') {
            return 'mxOrderChart.tpl';
        }

        $activeClass= Registry::getConfig()->getRequestParameter("item");
        if ($activeClass === 'mxAdminDashboard.tpl') {
            $this->_sThisTemplate = 'mxAdminDashboard.tpl';
        }
        $ret = parent::render();
        return $ret;
    }

    /*
     * getOrderOverview
     * -----------------------------------------------------------------------------------------------------------------
     * generate order datas for chart
     */
    public function getOrderOverview($sIntval = 'm',$sMonth = null, $sYear = null, $sDateBegin = null, $sDateEnd = null) {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $iActShopId = $this->getConfig()->getShopId();

        $aReturn = array();

        switch ($sIntval) {

            case 'm':
                if ($sYear == null) {$sYear  = date('Y');}
                $sMonth = date('m');
                $sDays = cal_days_in_month(CAL_GREGORIAN, $sMonth, $sYear);

                $sSql = "SELECT COUNT(oxorderdate) as ordercount, EXTRACT(DAY FROM oxorderdate) AS horizontitem, DATE(oxorderdate) AS date FROM oxorder WHERE EXTRACT(YEAR_MONTH FROM oxorderdate) = $sYear$sMonth AND oxshopid = $iActShopId GROUP BY DATE(oxorderdate) ORDER BY oxorderdate;";
                $aResult = $oDB->getAll($sSql);
                $sSql = "SELECT MAX(count) as maxcount FROM (SELECT COUNT(oxorderdate) as count, DATE(oxorderdate) AS date FROM oxorder WHERE  EXTRACT(YEAR_MONTH FROM oxorderdate) = $sYear$sMonth AND oxshopid = $iActShopId GROUP BY DATE(oxorderdate)) as tmp;";
                $aMaxResult = $oDB->getAll($sSql);
                $aReturn['year'] = $sYear;
                $aReturn['month'] = $sMonth;
                $aReturn['timestamp'] = '01.'.$sMonth.'.'.$sYear;
                $aReturn['horizont'] = $sDays;
                $aReturn['maxCount'] = $aMaxResult[0]['maxcount'];
                $aReturn['optionTitle'] = $sMonth;
                $aReturn['result'] = $aResult;

                return $aReturn;
            case 'y':
                if ($sYear == null) {$sYear  = date('Y');}
                $sSql = "SELECT MONTH(oxorderdate) as horizontitem, COUNT(oxorderdate) as ordercount FROM oxorder WHERE oxorderdate >= '$sYear-01-01' AND oxshopid = $iActShopId GROUP BY MONTH(oxorderdate);";
                $aResult = $oDB->getAll($sSql);

                $sSql = "SELECT MAX(ordercount) as maxcount FROM (SELECT MONTH(oxorderdate) as month, COUNT(oxorderdate) as ordercount FROM oxorder WHERE oxorderdate >= '$sYear-01-01' AND oxshopid = $iActShopId GROUP BY MONTH(oxorderdate)) as tmp;";
                $aMaxResult = $oDB->getAll($sSql);
                $aReturn['year'] = $sYear;
                $aReturn['month'] = $sMonth;
                $aReturn['horizont'] = 12;
                $aReturn['maxCount'] = $aMaxResult[0]['maxcount'];
                $aReturn['optionTitle'] = $sYear;
                $aReturn['result'] = $aResult;
                return $aReturn;
                break;
            case 'cm':
                $sDays = cal_days_in_month(CAL_GREGORIAN, $sMonth, $sYear);

                $sSql = "SELECT COUNT(oxorderdate) as ordercount, EXTRACT(DAY FROM oxorderdate) AS horizontitem, DATE(oxorderdate) AS date FROM oxorder WHERE EXTRACT(YEAR_MONTH FROM oxorderdate) = $sYear$sMonth GROUP BY DATE(oxorderdate) ORDER BY oxorderdate ;";
                $aResult = $oDB->getAll($sSql);

                $sSql = "SELECT MAX(count) as maxcount FROM (SELECT COUNT(oxorderdate) as count, DATE(oxorderdate) AS date FROM oxorder WHERE  EXTRACT(YEAR_MONTH FROM oxorderdate) = $sYear$sMonth GROUP BY DATE(oxorderdate)) as tmp;";
                $aMaxResult = $oDB->getAll($sSql);
                $aReturn['year'] = $sYear;
                $aReturn['month'] = $sMonth;
                $aReturn['timestamp'] = '01.'.$sMonth.'.'.$sYear;
                $aReturn['horizont'] = $sDays;
                $aReturn['maxCount'] = $aMaxResult[0]['maxcount'];
                $aReturn['optionTitle'] = $sMonth;
                $aReturn['result'] = $aResult;
                return $aReturn;
            case 'cy':
                $sYearTo = intval($sYear)+1;
                $sSql = "SELECT MONTH(oxorderdate) as horizontitem, COUNT(oxorderdate) as ordercount FROM oxorder WHERE oxorderdate >= '$sYear-01-01' AND oxorderdate <= '$sYearTo-01-01' AND oxshopid = $iActShopId GROUP BY MONTH(oxorderdate);";
                $aResult = $oDB->getAll($sSql);
                $sSql = "SELECT MAX(ordercount) as maxcount FROM (SELECT MONTH(oxorderdate) as month, COUNT(oxorderdate) as ordercount FROM oxorder WHERE oxorderdate >= '$sYear-01-01' AND oxorderdate <= '$sYearTo-01-01' AND oxshopid = $iActShopId GROUP BY MONTH(oxorderdate)) as tmp;";
                $aMaxResult = $oDB->getAll($sSql);
                $aReturn['year'] = $sYear;
                $aReturn['horizont'] = 13;
                $aReturn['maxCount'] = $aMaxResult[0]['maxcount'];
                $aReturn['optionTitle'] = $sYear;
                $aReturn['result'] = $aResult;
                return $aReturn;
                break;
        }
    }

    /*
     * changeOrderChartView
     * -----------------------------------------------------------------------------------------------------------------
     * update order datas for chart
     */
    public function changeOrderChartView() {
        $sOption    = Registry::getConfig()->getRequestEscapedParameter("option");
        $sNav       = Registry::getConfig()->getRequestEscapedParameter("nav");
        $sActMonth  = Registry::getConfig()->getRequestEscapedParameter("actMonth");
        $sActYear   = Registry::getConfig()->getRequestEscapedParameter("actYear");
        if ($sActYear == null) {
            $sActYear   = Registry::getConfig()->getRequestEscapedParameter("year");
        }

        if ($sOption == 'm') {
            if ($sNav == null) {
                $aResult = $this->getOrderOverview($sOption);
            } elseif ($sNav == 'prev') {
                $iActMonth = intval($sActMonth);
                $iActMonth--;
                if ($iActMonth < 1) {
                    $iActMonth = 12;
                    $sActYear--;
                }
                for ($a = 1; $a < 12; $a++)
                    $iActMonth = sprintf("%02d",$iActMonth);

                $aResult = $this->getOrderOverview('cm',$iActMonth,$sActYear);
            } elseif ($sNav == 'next') {
                $iActMonth = intval($sActMonth);
                $iActMonth++;
                if ($iActMonth > 12) {
                    $iActMonth = 1;
                    $sActYear++;
                }
                for ($a = 1; $a < 12; $a++)
                    $iActMonth = sprintf("%02d",$iActMonth);

                $aResult = $this->getOrderOverview('cm',$iActMonth,$sActYear);
            }
        } elseif ($sOption == 'y') {
            if ($sNav == null) {
                $aResult = $this->getOrderOverview($sOption);
            } elseif ($sNav == 'prev') {
                $sActYear = intval($sActYear);
                $sActYear--;
                $aResult = $this->getOrderOverview('cy',null,$sActYear);
            } elseif ($sNav == 'next') {
                $sActYear = intval($sActYear);
                $sActYear++;
                $aResult = $this->getOrderOverview('cy',null,$sActYear);
            }
        }

        $this->_aViewData['horizont']       = $aResult['horizont'];
        $this->_aViewData['maxCount']       = $aResult['maxCount'];
        $this->_aViewData['option']         = $sOption;
        $this->_aViewData['optionTitle']    = $aResult['optionTitle'];
        $this->_aViewData['year']           = $sActYear;
        $this->_aViewData['result']         = $aResult['result'];
    }

    /*
     * getPaymentUsage
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getPaymentUsage() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $iActShopId = $this->getConfig()->getShopId();

        $sSQL = "
          SELECT op.oxdesc, COUNT(oo.oxpaymentid) as paymentcount
            FROM oxorder as oo
            LEFT JOIN oxpayments as op
              ON oo.oxpaymenttype = op.oxid
            WHERE op.oxdesc != '' AND oxshopid = $iActShopId
            GROUP BY oo.oxpaymenttype
            ORDER BY paymentcount DESC;
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult;
    }

    /*
     * getUserAcounts
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getUserAcounts() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $iActShopId = $this->getConfig()->getShopId();
        $sSQL = "
          SELECT (SELECT COUNT(oxid) FROM oxuser WHERE oxpassword = '' AND oxshopid = $iActShopId) as noAccount,(SELECT COUNT(oxid) FROM oxuser WHERE oxpassword != ''  AND oxshopid = $iActShopId) as Account, (SELECT COUNT(oxid) FROM oxuser WHERE oxrights = 'malladmin'  AND oxshopid = $iActShopId) as Admin FROM oxuser GROUP BY noAccount;
        ";
        $aResult = $oDB->getAll($sSQL);

        return $aResult[0];
    }

    /*
     * getOptInNewsletters
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getOptInNewsletters() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $iActShopId = $this->getConfig()->getShopId();
        $sSQL = "
          SELECT
            COUNT(ons.oxid) as optinconfirmed,
            COUNT(ons2.oxid) as optin,
            (SELECT COUNT(oxid) FROM oxuser WHERE oxshopid = $iActShopId) as accounts

            FROM oxuser as ouser

            LEFT JOIN oxnewssubscribed ons
            ON ons.oxuserid = ouser.oxid
            AND ons.oxdboptin = 1

            LEFT JOIN oxnewssubscribed ons2
            ON ons2.oxuserid = ouser.oxid
            AND ons2.oxdboptin = 2

            WHERE ouser.oxshopid = $iActShopId
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0];
    }

    /*
     * getTopSellerArticles
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getTopSellerArticles($iCount, $blActive) {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $iActShopId = $this->getConfig()->getShopId();

        $sSQL = "
            SELECT SUM(oxamount) as oxamount, ooa.oxartid, ooa.oxtitle
            FROM oxorderarticles ooa
            INNER JOIN oxarticles oa
                ON oa.oxid = ooa.oxartid";
        if ($blActive === true) {
            $sSQL .= "
                AND oa.oxactive = 1
            ";
        }
        $sSQL.= "
            INNER JOIN oxorder oo
                ON oo.oxid = ooa.oxorderid
                AND oo.oxshopid = ".$iActShopId."
            GROUP BY ooa.oxartid
            ORDER BY oxamount DESC
        ";
        if ($iCount > 0) {
            $sSQL .= "
               LIMIT ".$iCount.";
            ";
        }

        $aResult = $oDB->getAll($sSQL);
        return $aResult;
    }

    /*
     * getCatsFromTopseller
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getCatsFromTopseller() {

        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sSQL = "
            SELECT
                oxtitle, COUNT(oxtitle) as iSellCounter
                FROM (
                    SELECT
                        oc.oxtitle,
                        ooa.oxamount,
                        ooa.oxartid,
                        ooa.oxorderid
                    FROM oxobject2category o2c
                    INNER JOIN oxcategories oc
                        ON o2c.oxcatnid = oc.oxid
                    LEFT JOIN oxorderarticles ooa
                        ON ooa.oxartid = o2c.oxobjectid
                    WHERE oxamount != ''
                    GROUP BY oc.oxtitle, ooa.oxartid
                ) as alist
                GROUP BY oxtitle
                ORDER BY iSellCounter DESC
                LIMIT 10
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult;
    }

    /*
     * getOrderValues
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getOrderValues() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $sSQL = "
          SELECT MAX(oxtotalordersum) as maxval, MIN(oxtotalordersum) as minval, AVG(oxtotalordersum) as avgval FROM oxorder
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0];
    }

    /*
     * getOrderCountFromMonth
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getOrderCountFromMonth() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $sFirstDay = date("Y-m-01");
        $sLastDay = date("Y-m-t");

        $sSQL = "
            SELECT COUNT(*) as count FROM oxorder WHERE oxorderdate >= '".$sFirstDay."' AND oxorderdate <= '".$sLastDay."';
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0]['count'];
    }

    /*
     * getQualityValues4Orders
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getQualityValues4Orders() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $sSQL = "
            SELECT (SELECT COUNT(*)
            FROM oxorder WHERE oxstorno = 1) as storno,
            (SELECT COUNT(*)
            FROM oxorder WHERE oxstorno = 0) as nostorno
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0];
    }

    /*
     * getFolderStatesFromOrders
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getFolderStatesFromOrders() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sSQL = "
            SELECT
            (SELECT COUNT(*) FROM oxorder WHERE oxfolder = 'ORDERFOLDER_NEW') as oxnew,
            (SELECT COUNT(*) FROM oxorder WHERE oxfolder = 'ORDERFOLDER_PROBLEMS') as oxproblems,
            (SELECT COUNT(*) FROM oxorder WHERE oxfolder = 'ORDERFOLDER_FINISHED') as oxfinished
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0];
    }

    /*
     * getArticleOnlyDatas
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getArticleOnlyDatas() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sSQL = "
            SELECT
            (SELECT COUNT(*) FROM oxarticles) as oxarticlesall,
            (SELECT COUNT(*) FROM oxarticles WHERE oxparentid = '') as oxarticlesparents,
            (SELECT COUNT(*) FROM oxarticles WHERE oxparentid != '') as oxarticlesvariants,
            (SELECT COUNT(*) FROM oxarticles WHERE oxactive = '1') as oxarticlesactive
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0];
    }

    /*
     * getArticleOnlyDatas
     * -----------------------------------------------------------------------------------------------------------------
     *
     */
    public function getCustomerBoughtDatas() {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sSQL = "
            SELECT
                (SELECT COUNT(ou.oxid) FROM oxuser as ou WHERE ou.oxpassword != '') as registeredUser,
                (SELECT COUNT(ou.oxid) FROM oxuser as ou WHERE ou.oxpassword != '' AND ou.oxid IN ( SELECT oxuserid FROM oxorder )) as registeredUserOrders,
                (SELECT COUNT(ou.oxid) FROM oxuser as ou WHERE ou.oxpassword = '') as guestUser,
                (SELECT COUNT(ou.oxid) FROM oxuser as ou WHERE ou.oxpassword = '' AND ou.oxid IN ( SELECT oxuserid FROM oxorder )) as guestUserOrders;
        ";

        $aResult = $oDB->getAll($sSQL);
        return $aResult[0];
    }
}