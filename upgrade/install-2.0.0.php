<?php

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_2_0_0($object)
{
    $object->createOrderState('PENDING_NIMBLE','pending_nimble');
    $object->check_credentials_update();
    return ($object->registerHook('actionOrderStatusPostUpdate')
         && $object->registerHook('DisplayTop'));
}