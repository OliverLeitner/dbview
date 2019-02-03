<?php
/**
 * defining some view stuff
 * all data manipulation goes here
 */
namespace dbview\views;

class viewManager
{
    protected $dbhandler = null;
    protected $config = null;

    // internal filter types
    protected $filterTypes = ["enum", "table", "table_name"];

    // json field to filter on
    protected $filterField = "values";

    // keeping some config shorthands
    protected $table_names = null;

    // data collection
    private $subdata = null;

    public function __construct($config, $dbhandler, $table_name)
    {
        $this->dbhandler = $dbhandler; // the database connection
        $this->config = $config; // json config array
        $this->table_name = $table_name; // current main data table
        $this->table_names = $this->config["tables"]["table_names"]; // shorthand of json tablenames
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
            foreach ($fields[0] as $key => $value) {
                if ($value[$this->filterField]) {
                    $this->subdata[$key] = null; // prepping the subarray to put the dropdown data on
                    $this->dropDownBuilder($key, $value);
                }
            }
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
        foreach ($value[$this->filterField] as $skey => $sval) {
            if ($skey === $this->filterTypes[2]) { // table_name
                $dbdata = $this->dbhandler->getAllFromTable(
                        $sval,
                        $this->table_names[$sval]["table_fields"]
                    );
                $this->dropDownFromDBDataBuilder($dbdata, $key, $sval);
            } // TODO: funct source logic appends here...
        }
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
    protected function dropDownFromDBDataBuilder($dbdata, $key, $table_name)
    {
        // the id field for html select option value
        $sel_fieldname = $this->table_names[$table_name]["selector_field"];
        // cross values catcher
        $cross_values = [];

        // shortest version of combination yet
        // use returns the filled vals back to the calling function, so to here...
        array_walk($dbdata, function ($entry) use (&$cross_values, &$sel_fieldname) {
            if (is_array($entry) && (array_keys($entry)[0] === $sel_fieldname)) {
                $cross_values[array_values($entry)[0]] = array_values($entry)[1];
            }
        });

        // put everything together...
        $this->subdata[$key][$table_name] = $cross_values;
    }
}
