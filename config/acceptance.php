<?php

return [
    'PARAMS_LOCATION' => dirname(__DIR__, 2) . '/composer-config-plugin-output/acceptance.php',
    'YII2_CONFIG_LOCATION' => dirname(__DIR__) . '/tests/acceptance/config/suite.php',

    'COMMON_SUITE_LOCATION' => dirname(__DIR__) . '/tests/acceptance.suite.yml',
    'COMMON_TESTS_LOCATION' => dirname(__DIR__) . '/tests',

    'URL' => $params['hipanel.url'],
    'BROWSER' => 'chrome',
    'SELENIUM_HOST' => $params['tests.acceptance.selenium.host'],

    'client' => [
        'id' => null,
        'login' => 'hipanel_test_user',
        'password' => 'random',
    ],

    'seller' => [
        'id' => null,
        'login' => 'hipanel_test_reseller',
        'password' => 'random',
    ],

    'manager' => [
        'id' => null,
        'login' => 'hipanel_test_manager',
        'password' => 'random',
    ],

    'admin' => [
        'id' => null,
        'login' => 'hipanel_test_admin',
        'password' => 'random',
    ],
];
