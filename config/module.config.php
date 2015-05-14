<?php

return array(
    'service_manager' => array(
        'invokables' => array(
            'eventListeners' => 'ZfSnapEventDebugger\Collector',
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
                'eventListeners' => 'eventListeners',
            )
        ),
        'toolbar' => array(
            'enabled' => 'true',
            'entries' => array(
                'eventListeners' => 'zend-developer-tools/toolbar/event-listeners',
            ),
        ),
    ),
);
