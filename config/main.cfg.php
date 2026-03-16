<?php
$aConfig = array(
    //title of project
    'project_title' => 'Dragon MVC',
    'project_email' => '',
    
    //default controller and method
    'defaultController' => '',
    'defaultMethod' => '',

    'mysql' => [
        'user' => '',
        'password' => '',
        'dbName' => '',
        'host' => 'localhost',
        'encoding' => 'utf8'
    ],
);

if (IS_WORKSPACE) {
    $aConfig['graphdb']['logHandler'] = function (string $query, array $params = [], array $statistics = []) {
        $st = '';
        foreach (array_filter($statistics) as $key => $value) {
            $st .= '<b>' . $key . ':</b> ' . $value . '<br>';
        }

        \core\Debug::query($query, array_slice(debug_backtrace(2), 2), [
            'params' => !empty($params) ? '<pre>' . print_r($params, true) . '</pre>' : '',
            'stats' => !empty($st) ? '<pre>' . $st . '</pre>' : '',
        ]);
    };
}
