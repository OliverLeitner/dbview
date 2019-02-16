<?php
/**
 * defining some view stuff
 * all data manipulation goes here
 */
namespace dbview\views;

class ViewManager
{
    protected $dbhandler = null;
    protected $config = null;

    // internal filter types
    protected $filterTypes = ["enum", "table", "table_name"];

    // json field to filter on
    protected $filterField = "values";

    // keeping some config shorthands
    protected $tablenames = null;

    // data collection
    private $subdata = null;

    public function __construct($config, $dbhandler, $tableName)
    {
        $this->dbhandler = $dbhandler; // the database connection
        $this->config = $config; // json config array
        $this->table_name = $tableName; // current main data table
        $this->tablenames = $this->config["tables"]["table_names"]; // shorthand of json tablenames
    }

    /**
     * dropdown builder function
     * call this from views to get all the dropdowns
     *
     * @param mixed $fields a list of table fields to read from
     * @return mixed / false of collected data to put onto the maindata for output
     */
    public function buildDropDown($fields)
    {
        // read the fields array, and collect dropdown data
        if ($fields && $fields[0]) {
            array_map(function ($key, $value) {
                if ($value[$this->filterField]) {
                    $this->subdata[$key] = null;
                    $this->dropDownBuilder($key, $value);
                }
            }, array_keys($fields[0]), $fields[0]);
            // return the filtered info
            if (is_countable($this->subdata)) {
                return $this->subdata;
            }
        }
        return false;
    }

    /**
     * get a single dropdowns data ready for the collection
     * also separates if table source or enum source or funct source...
     * TODO: funct source
     *
     * @param string $key the table name to add the dropdown to values
     * @param mixed $value the properties of "values" to work on
     * @return void
     */
    protected function dropDownBuilder($key, $value)
    {
        array_map(function ($skey, $sval) use (&$key, &$value) {
            if ($skey === $this->filterTypes[2]) {
                $dbdata = $this->dbhandler->getAllFromTable(
                    $sval,
                    $this->tablenames[$sval]["table_fields"],
                );
                $this->dropDownFromDBDataBuilder($dbdata, $key, $sval);
            }
        }, array_keys($value[$this->filterField]), $value[$this->filterField]);
    }

    /**
     * building up the key->value pairs for each dropdown data line
     * based upon type: table (data from database table)
     *
     * @param mixed $dbdata the received dropdown result data
     * @param object $key the key value (html select value)
     * @param string $table_name the table name to get the data from
     * @return void
     */
    protected function dropDownFromDBDataBuilder($dbdata, $key, $tableName)
    {
        // the id field for html select option value
        $selFieldname = $this->tablenames[$tableName]["selector_field"];
        // cross values catcher
        $crossValues = [];
        array_walk($dbdata, function ($entry) use (&$crossValues, &$selFieldname) {
            if (is_array($entry) && (array_keys($entry)[0] === $selFieldname)) {
                $crossValues[array_values($entry)[0]] = array_values($entry)[1];
            }
        });
        // put everything together...
        $this->subdata[$key][$tableName] = $crossValues;
    }

    /**
     * trying to build a generic mapping funct for our usage
     *
     * @param mixed $keys max of two keys to be filled with data
     * @param mixed $array the data array we are mapping from
     * @param mixed $data the data we are appending our data to
     * @param string $param the array key to map the data to
     *
     * @return mixed the mapped array
     */
    protected function arrayMap1D($array = [], $data = [], $keys = [], $param = "")
    {
        $iterator = 0;
        $hasParam = false;
        array_map(function ($value) use (&$keys, &$iterator, &$data, &$param, &$hasParam) {
            // the default catcher
            $data[$iterator][$keys[0]] = $value;
            // predefine the keykeeper
            $kval = null;
            // if we got more than one keyelem to fill, we do so
            if ($keys[1] && is_countable($value)) {
                $kval = $value[array_key_first($value)];
                $data[$iterator][$keys[0]] = $kval;
                $data[$iterator][$keys[1]] = $value;
            }
            // if we got a param to append to, we use that
            if ($param != "") {
                $hasParam = true;
                $data[$param][$iterator][$keys[0]] = $value;
                if ($keys[1] && is_countable($value)) {
                    $data[$param][$iterator][$keys[0]] = $kval;
                    $data[$param][$iterator][$keys[1]] = $value;
                }
            }
            $iterator++;
        }, $array);
        // remove garbage from first run, if param is set
        if ($hasParam) {
            unset($data[0]);
        }
        return $data;
    }

    /**
     * trying to build a generic mapping funct for our usage
     * the 2d version
     *
     * @param mixed $keys max of two keys to be filled with data
     * @param mixed $array the data array we are mapping from
     * @param mixed $data the data we are appending our data to
     * @param string $param the array key to map the data to
     *
     * @return mixed the mapped array
     */
    protected function arrayMap2D($array = [], $data = [], $param = "")
    {
        $iterator = 0;
        array_map(function ($key, $value) use (&$iterator, &$data, &$param) {
            // the default catcher
            $data[$iterator][$key] = $value;
            // if we got a param to append to, we use that
            if ($param != "") {
                // some cleanup
                if (isset($data[0])) {
                    unset($data[0]);
                }
                if (isset($data[$iterator][$key])) {
                    unset($data[$iterator][$key]);
                }
                $data[$param][$iterator][$key] = $value;
            }
            $iterator++;
        }, array_keys($array), $array);
        return $data;
    }

    /**
     * collects all the crosstable data
     */
    protected function addSubData($tablefields, $subdata)
    {
        // prepping dropdowns
        $tableconfig = false;
        if ($tablefields) {
            $tableconfig = $tablefields[0];
            foreach ($tableconfig as $key => $value) {
                if ($value["values"] && is_array($subdata[$key])) {
                    $tableconfig[$key]["values"] = $this->arrayMap2D(
                        $subdata[$key],
                        $tableconfig[$key]["values"],
                        $key
                    );
                }
            }
        }
        return $tableconfig;
    }

    /**
     * adds crosstable data to output
     */
    protected function addCrossDataToMainData($mainkeeper, $tableconfig, $key, $subdata, $citer)
    {
        foreach ($tableconfig[$key]["values"] as $skey => $sval) {
            $type = $tableconfig[$key]["values"]["type"];
            if ($skey !== "type" && $skey !== "table_name") {
                $mainkeeper["metadata"][$citer]["values"][$skey] = null;
                if ($type !== "in_menu" && $type !== "selector_field" && $type !== "enum") {
                   $mainkeeper["metadata"][$citer]["values"] = $subdata[$key];
                }
                if ($type === "table" || $type === "enum") {
                    array_map(function ($sskey, $ssval) use (&$mainkeeper, &$skey, &$citer) {
                        $mainkeeper["metadata"][$citer]["values"][$skey][$sskey] = $ssval;
                    }, array_keys($sval), $sval);
                }
            }
        }
        return $mainkeeper;
    }

    /**
     * json main config for the output
     */
    public function dataToJson($tablefields, $maindata, $subdata)
    {
        // adding dropdowns to data handler
        $tableconfig = $this->addSubData($tablefields, $subdata);
        // metadata part of the json
        $mainkeeper = null;
        $citer = 0;
        foreach (array_keys($maindata[0]) as $key) {
            $mainkeeper["metadata"][$citer]["name"] = $key;
            // label condition
            $mainkeeper["metadata"][$citer]["label"] = $key;
            if ($tableconfig[$key]["label"]) {
                $mainkeeper["metadata"][$citer]["label"] = $tableconfig[$key]["label"];
            }
            $mainkeeper["metadata"][$citer]["datatype"] = "string";
            $mainkeeper["metadata"][$citer]["editable"] = true;
            if ($tableconfig && $tableconfig[$key]) {
                $mainkeeper["metadata"][$citer]["datatype"] = $tableconfig[$key]["datatype"];
                $mainkeeper["metadata"][$citer]["editable"] = $tableconfig[$key]["editable"];
            }
            if ($tableconfig && $tableconfig[$key] && $tableconfig[$key]["values"] !== false) {
                $mainkeeper["metadata"][$citer]["values"] = null;
                // add crosstabledata to output
                $mainkeeper = $this->addCrossDataToMainData($mainkeeper, $tableconfig, $key, $subdata, $citer);
            }
            $citer++;
        }
        // adding data to output
        $mainkeeper = $this->arrayMap1D($maindata, $mainkeeper, ["id","values"], "data");
        // return the built up json
        return $mainkeeper;
    }
}
