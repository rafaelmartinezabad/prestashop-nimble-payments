<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *     @author    PrestaShop SA <contact@prestashop.com>
 *     @copyright 2007-2016 PrestaShop SA
 *     @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_1_0($object)
{  
    $id_hook_action_oder_status = Hook::getIdByName('actionOrderStatusPostUpdate');
    $module_action = Hook::getModulesFromHook($id_hook_action_oder_status, $object->id);
    if (count($module_action)) {
        $object->unregisterHook('actionOrderStatusPostUpdate');
    }
    
    $id_hook_display_top = Hook::getIdByName('displayTop');
    $module_display = Hook::getModulesFromHook($id_hook_display_top, $object->id);
    if (! count($module_display)) {
        $object->registerHook('displayTop');
    }
    
    $object->installTab();
    $object->checkCredentials();
    
    return ($object->registerHook('adminOrder')
         && $object->registerHook('actionAdminLoginControllerSetMedia')
         && $object->registerHook('displayBackOfficeHeader')
         && $object->registerHook('dashboardZoneOne'));
}