<?php

namespace Pike\DataTable\Adapter;

use Pike\DataTable;
use Pike\DataTable\DataSource\DataSourceInterface;
use Zend\View\Model\JsonModel;

class DataTables extends AbstractAdapter implements AdapterInterface
{

    /**
     * Number of rows to display on a single page when using pagination
     *
     * @var integer
     */
    protected $displayLength = 10;

    /**
     * @var string
     * @see http://datatables.net/release-datatables/examples/basic_init/dom.html
     */
    protected $dom = '<"top"iflp<"clear">>rt<"bottom"iflp<"clear">>';

    /**
     * When enabled DataTables will not make a request to the server for the
     * first page draw - rather it will use the data already on the
     * page (no sorting etc will be applied to it), thus saving on an
     * XHR at load time.
     * 
     * @var integer 
     */
    protected $deferLoading;

    /**
     * @return string
     */
    public function render(DataTable $dataTable)
    {
        $options = array(
            'iDisplayLength' => $this->displayLength,
            'sDom' => $this->dom,
            'bProcessing' => 'true',
            'bServerSide' => 'true',
            'sAjaxSource' => '',
            'sServerMethod' => 'POST'
        );

        if ($this->deferLoading) {
            $options['iDeferLoading'] = $this->deferLoading;
        }

        $this->viewModel->setVariable('options', $options);
        return $this->viewModel;
    }

    /**
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
        $items = $dataSource->getItems($offset, $limit);

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

    /**
     * @return integer
     */
    public function getDisplayLength()
    {
        return $this->displayLength;
    }

    /**
     * @param integer $displayLength
     */
    public function setDisplayLength($displayLength)
    {
        $this->displayLength = $displayLength;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dataTables';
    }

}