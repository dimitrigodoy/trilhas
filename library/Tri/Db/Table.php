<?php
class Tri_Db_Table extends Zend_Db_Table
{
    /**
     * table validators
     *
     * example
     * 	array(
     * 		'id' => array( 'Int' , 'NotEmpty' )
     *  );
     *
     * @access public
     * @var array
     */
    protected $_validators = array();

    /**
     * table filters
     *
     * example
     * 	array(
     * 		'id' => array( 'Int' )
     *  );
     *
     * @access public
     * @var array
     */
    protected $_filters = array();

    /**
     * Get all validators
     *
     * @return array
     */
    public function getValidators()
    {
        if (empty($this->_validators)) {
            $this->setupValidators();
        }
        return $this->_validators;
    }

    /**
     * Get all filters
     *
     * @return array
     */
    public function getFilters()
    {
        if (empty($this->_filters)) {
            $this->setupFilters();
        }
        return $this->_filters;
    }

    /**
     * Create validator's array
     *
     * @return void
     */
    public function setupValidators()
    {
        $this->_validators = array();
        $resultValidators  = $this->info();

        foreach ($resultValidators["metadata"] as $key => $value) {
            $this->_validators[$key] = array();
            switch ($value['DATA_TYPE']) {
                case 'bpchar':
                case 'varchar':
                case 'char':
                case 'text':
                    if ($value['LENGTH'] > -1) {
                        $this->_validators[$key][] = array('StringLength', false, array(0, $value[ 'LENGTH' ]));
                    }
                    break;
                case 'int':
                case 'int8':
                case 'int4':
                case 'int2':
                case 'bigint':
                case 'smallint':
                case 'bigint':
                case 'integer':
                    $this->_validators[$key][] = 'Digits';
                    break;
                case 'numeric':
                    $this->_validators[$key][] = 'Float';
                    break;
                case 'timestamp':
                case 'date':
                case 'blob':
                case 'bytea':
                case 'time':
                    break;
                default:
                    throw new Exception('Tipo de dado não existe! ' . $value['DATA_TYPE']);
                    break;
            }

            if (!$value['NULLABLE'] && !$value['PRIMARY']) {
                $this->_validators[$key][] = 'NotEmpty';
            }
        }
    }

    /**
     * Create filter's array
     *
     * @return void
     */
    public function setupFilters()
    {
        $this->_filters = array();
        $resultFilters  = $this->info();

        foreach ($resultFilters["metadata"] as $key => $value) {
            $this->_filters[$key] = array();
            switch ($value['DATA_TYPE']) {
                case 'bpchar':
                case 'varchar':
                case 'char':
                case 'text':
                    $this->_filters[$key][] = 'StringTrim';
                    break;
                case 'int':
                case 'int8':
                case 'int4':
                case 'int2':
                case 'bigint':
                case 'smallint':
                case 'numeric':
                case 'bigint':
                case 'integer':
                    $this->_filters[$key][] = 'Int';
                    break;
                case 'numeric':
                    $this->_filters[$key][] = array('LocalizedToNormalized', array('locale'=>"pt_BR"));
                    break;
                case 'timestamp':
                case 'time':
                case 'date':
                    $this->_filters[$key][] = 'Date';
                    break;
               case 'blob':
               case 'bytea':
                   break;
                default:
                    throw new Exception('Tipo de dado não existe!'.$value['DATA_TYPE']);
                    break;
            }

            if (strstr($value['COLUMN_NAME'], 'cnpj')
                || strstr($value['COLUMN_NAME'], 'cpf')) {
                $this->_filters[$key][] = 'Digits';
            }
        }
    }

    /**
     * Add validation to validator's array
     *
     * @return void
     */
    public function addValidators($field, $validators)
    {
        if (empty($this->_validators)) {
            $this->setupValidators();
        }

        $validators = (array) $validators;

        foreach($validators as $value) {
            $this->_validators[$field][] = $value;
        }
    }

    /**
     * Add filter to filter's array
     *
     * @return void
     */
    public function addFilters($field, $filters)
    {
        if (empty($this->_filters)) {
            $this->setupFilters();
        }

        $filters = (array) $filters;

        foreach($filters as $value) {
            $this->_filters[$field][] = $value;
        }
    }

    /**
     * Remove validation from validator's array
     *
     * @return void
     */
    public function removeValidators($field, $validators)
    {
        if (empty($this->_validators)) {
            $this->setupValidators();
        }

        $position = array_search($validators, $this->_validators[$field]);
        if (is_numeric($position)) {
            unset($this->_validators[$field][$position]);
        }
    }

    /**
     * Remove filter from filter's array
     *
     * @return void
     */
    public function removeFilters($field, $filters)
    {
        if (empty($this->_filters)) {
            $this->setupFilters();
        }

        $position = array_search($validators, $this->_validators[$field]);
        if (is_numeric($position)) {
            unset($this->_filters[$field][$position]);
        }
    }

    /**
     * @param  string                            $key
     * @param  string                            $value
     * @param  string|array|Zend_Db_Table_Select $where
     * @param  string|array                      $order
     * @param  integer                           $count
     * @param  integer                           $offset
     * @return mixed[]
     */
    public function fetchPairs($key = 'id', $value = 'name', $where = null, $order = null, $count = null, $offset = null)
    {
        // check input
        $translate = Zend_Registry::get('Zend_Translate');

        if (is_null($value)) {
            $value = $key;
        }
        if (is_null($order)) {
            $order = $value;
        }

        $rowset = $this->fetchAll($where, $order, $count, $offset);
        foreach ($rowset as $row) {
            if ($row->{$key} && $row->{$value}) {
                $data[$row->{$key}] = $translate->_($row->{$value});
            }
        }
        return $data;
    }

    /**
     * Support method for fetching rows.
     *
     * @param  Zend_Db_Table_Select $select  query options.
     * @return array An array containing the row results in FETCH_ASSOC mode.
     */
    protected function _fetch(Zend_Db_Table_Select $select)
    {
        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
            $id    = sha1($select->__toString());

            if ($cache->test($id)) {
                return $cache->load($id);
            }
        }

        $stmt = $this->_db->query($select);
        $data = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        if (isset($cache)) {
            $cache->save($data, $id, array($this->_name));
        }
        return $data;
    }

    public function clearCache()
    {
        if (Zend_Registry::isRegistered('cache')) {
            $mode = Zend_Cache::CLEANING_MODE_MATCHING_TAG;
            Zend_Registry::get('cache')->clean($mode, array($this->_name));
        }
    }

    public function insert(array $data)
    {
        $this->clearCache();
        return parent::insert($data);
    }

    public function update(array $data, $where)
    {
        $this->clearCache();
        return parent::update($data, $where);
    }

    public function delete($where)
    {
        $this->clearCache();
        return parent::delete($where);
    }
}
