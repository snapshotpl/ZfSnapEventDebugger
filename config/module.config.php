<?php

return array(
    'service_manager' => array(
        'invokables' => array(
            'ZfSnapEventDebugger\Collector' => 'ZfSnapEventDebugger\Collector',
        )
    ),
    'view_manager' => array(
        'template_map' => array(
            'zend-developer-tools/toolbar/event-listeners' => __DIR__ . '/../view/zend-developer-tools/toolbar/event-listeners.phtml',
        )
    ),
    'zenddevelopertools' => array(
        'profiler' => array(
            'collectors' => array(
                'ZfSnapEventDebugger\Collector' => 'ZfSnapEventDebugger\Collector',
            )
        ),
        'toolbar' => array(
            'enabled' => true,
            'entries' => array(
                'ZfSnapEventDebugger\Collector' => 'zend-developer-tools/toolbar/event-listeners',
            ),
        ),
    ),
);
