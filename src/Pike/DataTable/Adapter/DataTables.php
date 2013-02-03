<?php

namespace Pike\DataTable\Adapter;

use Pike\DataTable;
use Pike\DataTable\DataSource\DataSourceInterface;
use Zend\View\Model\JsonModel;

class DataTables extends AbstractAdapter implements AdapterInterface
{

    /**
     * @param  \Pike\DataTable $dataTable
     * @return string
     */
    public function render(DataTable $dataTable)
    {
        $content = <<<EOF
<script type="text/javascript">
$(document).ready(function () {
    $('#test').dataTable({
        iDisplayLength: %d,
        bProcessing: true,
        bServerSide: true,
        sAjaxSource: "",
        sServerMethod: "POST"
    });
});
</script>
EOF;
        return sprintf($content, $dataTable->getItemCountPerPage());
    }

    /**
     * 
     * 
     * @param  DataSourceInterface $dataSource
     * @return JsonModel
     */
    public function getResponse(DataTable $dataTable)
    {
        $params = $this->parameterBag->all();

        $offset = $params['iDisplayStart'];
        $limit = $params['iDisplayLength'];

        $dataSource = $dataTable->getDataSource();
        $count = count($dataSource);

        $items = $dataTable->getDataSource()->getItems($offset, $limit);

        $data = array();
        $data['sEcho'] = (int) $params['sEcho'];
        $data['iTotalRecords'] = $count;
        $data['iTotalDisplayRecords'] = $count;
        $data['aaData'] = array();

        foreach ($items as $item) {
            $data['aaData'][] = array_values($item);
        }

        return new JsonModel($data);
    }

}