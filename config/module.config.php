<?php

return array(
    'view_helpers' => array(
        'invokables' => array(
            'datatable' => 'Pike\DataTable\View\Helper\DataTableHelper',
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'dataTable/dataTables' => __DIR__ . '/../view/dataTable/dataTables.phtml',
        ),
    ),
);
