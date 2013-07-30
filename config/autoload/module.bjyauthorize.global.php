<?php
/**
 * BjyAuthorize Module (https://github.com/bjyoungblood/BjyAuthorize)
 *
 * @link https://github.com/bjyoungblood/BjyAuthorize for the canonical source repository
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'bjyauthorize' => array(
        'default_role'          => 'guest',
        'identity_provider'     => 'BjyAuthorize\Provider\Identity\AuthenticationIdentityProvider',
        'unauthorized_strategy' => 'BjyAuthorize\View\UnauthorizedStrategy',
        'role_providers'        => array(
            'BjyAuthorize\Provider\Role\ObjectRepositoryProvider' => array(
                'object_manager'    => 'doctrine.entitymanager.orm_default',
                'role_entity_class' => 'Secretary\Entity\Role'
            ),
        ),
        'guards' => array(
            'BjyAuthorize\Guard\Route' => array(
                array('route' => 'zfcuser', 'roles' => array('user')),
                array('route' => 'zfcuser/logout', 'roles' => array('user')),
                array('route' => 'zfcuser/changepassword', 'roles' => array('user')),
                array('route' => 'zfcuser/changeemail', 'roles' => array('user')),
                array('route' => 'zfcuser/login', 'roles' => array('guest')),
                array('route' => 'zfcuser/register', 'roles' => array('guest')),
                array('route' => 'home', 'roles' => array('guest', 'user')),
                array('route' => 'user-settings', 'roles' => array('user')),
                array('route' => 'secretary/default', 'roles' => array('user')),
                array('route' => 'secretary/note', 'roles' => array('keyuser')),
                array('route' => 'secretary/group', 'roles' => array('keyuser')),
            ),
        ),
        'resource_providers' => array(
            'BjyAuthorize\Provider\Resource\Config' => array(
                'dashboard' => array(),
                'key'       => array(),
                'notes'     => array(),
                'groups'    => array(),
            ),
        ),
        'rule_providers' => array(
            'BjyAuthorize\Provider\Rule\Config' => array(
                'allow' => array(
                    array(array('user'), 'dashboard', 'use'),
                    array(array('user'), 'key', 'use'),
                    array(array('keyuser'), 'notes', 'use'),
                    array(array('keyuser'), 'groups', 'use')
                ),
                'deny' => array(),
            ),
        ),
        'template'  => 'error/403',
    ),
    'view_manager' => array(
        'template_map' => array(
            'error/403' => __DIR__ . '/../../module/Secretary/view/error/403.phtml',
        ),
    ),
);
