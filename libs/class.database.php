<?php
/**
 * database functionality
 */
namespace dbview\database;
class databaseManager {
    // some base stuff
    protected $dsn = "";
    protected $con = null;
    protected $_livecon = null;
    protected $database = "";

    /**
     * base class constructor
     *
     * @param object $config contains the app configuration
     */
    public function __construct(object $config){
        // con stuff
        $this->database = (string) $config->database;
        $this->dsn = (string) $config->protocol.":".$config->hostname.",".$config->port;
        $this->con = array(
            "Database" => $this->database,
            "UID" => (string) $config->username,
            "PWD" => (string) $config->password,
            "CharacterSet" => "UTF-8",
            "MultipleActiveResultSets" => TRUE,
            "ConnectionPooling" => TRUE,
        );
        $this->_livecon = \sqlsrv_connect($this->dsn, $this->con);

        if (!$this->_livecon) {
            die(print_r(\sqlsrv_errors(), true));
        }
    }

    /**
     * get all data off a table
     *
     * @param string $tablename name of the table to grab the data off
     * @return mixed array of resulting rows
     */
    public function getAllFromTable(string $tablename) {
        $tsql = "SELECT * FROM [".$tablename."]";
        $res = \sqlsrv_query($this->_livecon, $tsql);
        if ($this->checkConnectionResults($res)) {
            $output = array();
            while ($row = \sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                // in our usecase, only non objects or non arrays make sense on edit
                $rowdata = null;
                foreach ($row AS $outrow => $outvalue) {
                    if (!is_object($outvalue) && !is_array($outvalue)) {
                        $rowdata[$outrow] = $outvalue;
                    }
                }
                $output[] = $rowdata;
            }
            return $output;
        } else {
            die(print_r(\sqlsrv_errors(), true));
        }
    }

    /**
     * check the connection resource
     *
     * @param [type] $results the resource to test
     * @return boolean if the resource is valid or not
     */
    protected function checkConnectionResults($results) {
        if (!is_resource($results)) {
            return \sqlsrv_errors();
        } else {
            return true;
        }
    }
}