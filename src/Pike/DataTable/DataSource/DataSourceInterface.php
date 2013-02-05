<?php

namespace Pike\DataTable\DataSource;

interface DataSourceInterface
{

    /**
     * @param integer $offset
     * @param integer $limit
     */
    public function getItems($offset, $limit);
}