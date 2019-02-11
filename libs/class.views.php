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
     * @param [type] $fields a list of table fields to read from
     * @return array / false of collected data to put onto the maindata for output
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
     * @param [type] $key the table name to add the dropdown to values
     * @param [type] $value the properties of "values" to work on
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
     * @param [type] $dbdata the received dropdown result data
     * @param [type] $key the key value (html select value)
     * @param [type] $table_name the table name to get the data from
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
     */
    protected function arrayMap($array, $data)
    {
        $iterator = 0;
        array_map(function ($key, $val) use (&$data) {
            $data[$iterator]["key"] = $key;
            $data[$iterator]["value"] = $val;
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
                    array_map(
                        function ($crosskey, $crossvalues) use (&$tableconfig, &$key) {
                            if (is_array($crossvalues)) {
                                $tableconfig[$key]["values"][$crosskey] = $crossvalues;
                            }
                        },
                        array_keys($subdata[$key]),
                        $subdata[$key]
                    );
                }
            }
        }
        return $tableconfig;
    }

    /**
     * adds all the data to the output
     */
    protected function addDataToMain($mainkeeper, $maindata)
    {
        $iter = 0;
        array_map(function ($entry) use (&$mainkeeper, &$iter) {
            $mainkeeper["data"][$iter]["id"] = $entry[array_key_first($entry)];
            $mainkeeper["data"][$iter]["values"] = $entry;
            $iter++;
        }, $maindata);
        return $mainkeeper;
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
        $mainkeeper = array();
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
        $mainkeeper = $this->addDataToMain($mainkeeper, $maindata);

        // return the built up json
        return $mainkeeper;
    }
}
