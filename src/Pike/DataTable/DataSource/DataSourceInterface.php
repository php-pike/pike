<?php

namespace Pike\DataTable\DataSource;

interface DataSourceInterface
{

    /**
     * Returns the items for the specified offset and limit
     * 
     * @param  integer $offset
     * @param  integer $limit
     * @return array
     */
    public function getItems($offset, $limit);

    /**
     * Returns a list of fields
     * 
     * @return array
     */
    public function getFields();
}