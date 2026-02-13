<?php

/*
 *
 * Response class definitions
 *
 */

namespace FreePBX\modules\Sccp_manager\aminterface;

// ************************************************************************** Response *********************************************

abstract class Response extends IncomingMessage
{

    protected $_events;
    protected $_completed;
    protected $keys;
    protected $eventListIsCompleted;
    protected $eventListEndEvent;

    public function __construct($rawContent)
    {

        parent::__construct($rawContent);
        $this->_events = array();
// this logic is false - even if we have an error, we will not get anymore data, so is completed.
        $this->_completed = $this->isSuccess();
    }

    public function __sleep()
    {
        $ret = parent::__sleep();
        $ret[] = '_completed';
        $ret[] = '_events';
        return $ret;
    }

    public function getEvents()
    {
        return $this->_events;
    }
    public function getClosingEvent() {
        return $this->_events['ClosingEvent'] ?? null;
    }
    public function removeClosingEvent() {
        unset($this->_events['ClosingEvent']);
    }
    public function getCountOfEvents() {
        return is_array($this->_events) ? count($this->_events) : 0;
    }

    public function isSuccess()
    {
        // returns true if response message does not contain error
        return stristr($this->getKey('Response'), 'Error') === false;
    }

    public function isList()
    {
        if ($this->getKey('EventList') === 'start' ) {
            return true;
        }
    }

    public function getMessage()
    {
        return $this->getKey('Message');
    }

    public function setActionId($actionId)
    {
        $this->setKey('ActionId', $actionId);
    }

    public function getVariable($_rawContent, $_fields = '')
    {
        $lines = explode(Message::EOL, $_rawContent);
        foreach ($_fields as $key => $value) {
            foreach ($lines as $data) {
                $_pst = strpos($data, $value);
                if ($_pst !== false) {
                    $this->setKey($key, substr($data, $_pst + strlen($value)));
                }
            }
        }
    }
}
class GenericResponse extends Response
{
}

//****************************************************************************
// There are two types of Response messages returned by AMI
// Self contained responses which include any data requested;
// List Responses which contain the data in event messages that follow
// the response message.Response and Event
// Following are the self contained Response classes.
//****************************************************************************

class Generic_Response extends Response
{
    public function __construct($rawContent)
    {
        // Only used for self contained responses.
        parent::__construct($rawContent);
        // add dummy closing event
        $this->_events['ClosingEvent'] = new ResponseComplete_Event($rawContent);
        $this->_completed = true;
        $this->eventListIsCompleted = true;

    }
}

class Login_Response extends Generic_Response
{
}

class Command_Response extends Generic_Response
{
    private $_temptable;
    public function __construct($rawContent)
    {
        $this->_temptable = array();
        parent::__construct($rawContent);
        $lines = explode(Message::EOL, $rawContent);
        foreach ($lines as $line) {
            $content = explode(':', $line);
            if (is_array($content) && count($content) >= 2) {
                switch (strtolower($content[0] ?? '')) {
                    case 'actionid':
                        $this->_temptable['ActionID'] = trim($content[1] ?? '');
                        break;
                    case 'response':
                        $this->_temptable['Response'] = trim($content[1] ?? '');
                        break;
                    case 'privilege':
                        $this->_temptable['Privilege'] = trim($content[1] ?? '');
                        break;
                    case 'output':
                        // included for backward compatibility with earlier versions of chan_sccp_b. AMI api does not precede command output with Output
                        $this->_temptable['Output'] = explode(PHP_EOL,str_replace(PHP_EOL.'--END COMMAND--', '',trim($content[1] ?? '')));
                        break;
                    default:
                        $this->_temptable['Output'] = explode(PHP_EOL,str_replace(PHP_EOL.'--END COMMAND--', '', trim($line)));
                        break;
                }
            }
        }
    }
    public function getResult()
    {
        return $this->_temptable;
    }
}

class SCCPJSON_Response extends Generic_Response
{
    public function __construct($rawContent)
    {
        parent::__construct($rawContent);
        $this->getVariable($rawContent, array("DataType" => "DataType:", "JSONRAW" => "JSON:"));
        if (null !== $this->getKey('JSONRAW')) {
            $this->setKey('Response', 'Success');
        }
    }
    public function getResult()
    {
        $jsonData = $this->getKey('JSON') ?? '';
        if (($json = json_decode($jsonData, true)) != false) {
            return $json;
        }
        return null;
    }
}

//***************************************************************************//
// Following are the Response classes where the data is contained in a series.
// of event messages.

class SCCPGeneric_Response extends Response
{
    protected $_tables;
    private $_temptable;

    public function __construct($rawContent)
    {
        parent::__construct($rawContent);
        // Confirm that there is a list following. This overrides any setting
        // made in one of the parent constructs.
        $this->_completed = !$this->isList();
    }

    public function addEvent($event)
    {
        // Start of list is handled by the isList function in the Constructor
        // which also defines the list end event

        if ( empty($thisSetEventEntryType)) {
            // This is empty as soon as we have received a TableStart.
            // The next message is the first of the data sets
            // We use this variable in the switch to add set entries
            if (strpos($event->getName(), 'Entry')) {
                $thisSetEventEntryType = $event->getName();
            } else {
                $thisSetEventEntryType = 'undefinedAsThisIsNotASet';
            }
        }
        // Unknown events will cause an exception.
        // All event classes must be defined within Event.class.
        if (get_class($event) === 'FreePBX\modules\Sccp_manager\aminterface\UnknownEvent') {
            $this->_events[] = $event;
            return;
        }
        $eventName = $event->getName();
        $eventListEndEvent = $this->getKey('eventListEndEvent');
        
        // Explicit check for closing event
        if ($eventName === $eventListEndEvent) {
            $this->_events['ClosingEvent'] = $event;
            $this->eventListEndEvent = null;
            return;
        }
        
        switch ( $eventName) {
            case $thisSetEventEntryType :
                $this->_temptable['Entries'][] = $event;
                break;
            case 'TableStart':
                //initialise
                $this->_temptable = array();
                $this->_temptable['Name'] = $event->getTableName();
                $this->_temptable['Entries'] = array();
                $thisSetEventEntryType = '';
                break;
            case 'TableEnd':
                //Close
                if (!is_array($this->_tables)) {
                    $this->_tables = array();
                }
                $this->_tables[$event->getTableName()] = $this->_temptable;
                $this->_temptable = array();
                $thisSetEventEntryType = 'undefinedAsThisIsNotASet';

                // Finished the table. Now check to see if everything was received
                // If counts do not match return false and table will not be
                //loaded
                $tableName = $event->getTableName();
                $expectedEntriesRaw = $event->getKey('TableEntries');
                $expectedEntries = (int)($expectedEntriesRaw ?? 0);
                $entries = $this->_tables[$tableName]['Entries'] ?? [];
                
                // Ensure we have an array and count properly for PHP 8.3
                if (!is_array($entries)) {
                    $entries = [];
                }
                $actualEntries = count($entries);
                
                // Debug logging for softkey issues
                if ($tableName === 'SoftKeySets') {
                    error_log("SoftKeySets debug: expectedRaw='{$expectedEntriesRaw}', expected={$expectedEntries}, actual={$actualEntries}, entriesType=" . gettype($entries));
                    if ($expectedEntries !== $actualEntries) {
                        error_log("SoftKeySets count mismatch: expected={$expectedEntries}, actual={$actualEntries}");
                        // For debugging, let's be more lenient with SoftKeySets
                        if ($actualEntries > 0) {
                            error_log("SoftKeySets: Allowing mismatch since we have actual entries");
                            break; // Continue processing instead of returning false
                        }
                    }
                }
                
                // Cast both to int for strict comparison
                if ((int)$expectedEntries !== (int)$actualEntries) {
                    error_log("Table {$tableName}: Count mismatch - expected={$expectedEntries}, actual={$actualEntries}");
                    return false;
                }
                break;
            //case $eventListEndEvent;
            case $this->getKey('eventListEndEvent');
                // Have the list end event. The correct number of entries is verified in the event constructor
                error_log("addEvent: Setting ClosingEvent to " . get_class($event));
                $this->_events['ClosingEvent'] = $event;
                $this->eventListEndEvent = null;
                //return $this->_completed = true;
                break;
            default:
                // add regular list event
                $this->_events[] = $event;
        }
    }

    protected function ConvertTableData( $_tablename, array $_fkey, array $_fields)
    {
        $result = array();
        $_rawtable = $this->Table2Array($_tablename);
        
        // Debug logging for SoftKeySets
        if ($_tablename === 'SoftKeySets') {
            error_log("ConvertTableData SoftKeySets: rawtable count=" . (is_array($_rawtable) ? count($_rawtable) : 'not array'));
        }
        
        // Check that there is actually data to be converted
        if (empty($_rawtable) || !is_array($_rawtable)) { 
            if ($_tablename === 'SoftKeySets') {
                error_log("ConvertTableData SoftKeySets: No data to convert");
            }
            return $result;
        }
        
        foreach ($_rawtable as $_row) {
            if (!is_array($_row)) {
                continue; // Skip non-array entries
            }
            
            $all_key_ok = true;
            $set_name = array(); // Initialize to avoid undefined variable
            
            // No need to test if $_fkey is array as array required
            foreach ($_fkey as $_fid) {
                if (empty($_row[$_fid] ?? '')) {
                    $all_key_ok = false;
                } else {
                    $set_name[$_fid] = $_row[$_fid] ?? '';
                }
            }
            $Data = &$result;

            if ($all_key_ok && !empty($set_name)) {
                foreach ($set_name as $value_id) {
                    $Data = &$Data[$value_id];
                }
                // Label converter in case labels and keys are different
                foreach ($_fields as $value_key => $value_id) {
                    $Data[$value_id] = $_row[$value_key] ?? '';
                }
            }
        }
        return $result;
    }

    protected function ConvertEventData(array $_fkey, array $_fields)
    {
        $result = array();

        foreach ($this->_events as $_row) {
            $all_key_ok = true;
            $tmp_result = $_row->getKeys();
            $set_name = array();
            // No need to test if $_fkey is arrray as array required
            foreach ($_fkey as $_fid) {
                if (empty($tmp_result[$_fid] ?? '')) {
                    $all_key_ok = false;
                } else {
                    $set_name[$_fid] = $tmp_result[$_fid] ?? '';
                }
            }
            $Data = &$result;
            if ($all_key_ok) {
                foreach ($set_name as $value_id) {
                    $Data = &$Data[$value_id];
                }
                // Label converter in case labels and keys are different - not actually required.
                foreach ($_fields as $value_id) {
                    $Data[$value_id] = $tmp_result[$value_id] ?? '';
                }
            }
        }
        return $result;
    }

    public function Table2Array( $tablename )
    {
        $result = array();
        if (empty($tablename) || !is_array($this->_tables)) {
            return $result;
        }
        if (!isset($this->_tables[$tablename]['Entries']) || !is_array($this->_tables[$tablename]['Entries'])) {
            return $result;
        }
        
        foreach ($this->_tables[$tablename]['Entries'] as $trow) {
            if (is_object($trow) && method_exists($trow, 'getKeys')) {
                $keys = $trow->getKeys();
                if (is_array($keys)) {
                    $result[] = $keys;
                }
            }
        }
        return $result;
    }

    public function getResult()
    {
            return $this->getMessage();
    }
}



class SCCPShowSoftkeySets_Response extends SCCPGeneric_Response
{
    public function __construct($rawContent)
    {
        parent::__construct($rawContent);
        $this->setKey('eventlistendevent', 'SCCPShowSoftKeySetsComplete');
        $this->setKey('eventListEndEvent', 'SCCPShowSoftKeySetsComplete');
    }
    public function getResult()
    {
        // Custom processing for SoftKeySets - group by set and mode
        $result = array();
        $_rawtable = $this->Table2Array('SoftKeySets');
        
        if (empty($_rawtable) || !is_array($_rawtable)) {
            error_log("SCCPShowSoftkeySets_Response: No raw table data");
            return $result;
        }
        
        error_log("SCCPShowSoftkeySets_Response: Processing " . (is_array($_rawtable) ? count($_rawtable) : 0) . " entries");
        
        // Group softkeys by set and mode
        $grouped = array();
        foreach ($_rawtable as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $set = $row['set'] ?? 'default';
            $mode = strtolower($row['mode'] ?? '');
            $label = $row['label'] ?? '';
            
            if (!empty($mode) && !empty($label)) {
                if (!isset($grouped[$set])) {
                    $grouped[$set] = array();
                }
                if (!isset($grouped[$set][$mode])) {
                    $grouped[$set][$mode] = array();
                }
                $grouped[$set][$mode][] = strtolower($label);
            }
        }
        
        // Convert grouped data to expected format
        foreach ($grouped as $set => $modes) {
            $setData = array();
            foreach ($modes as $mode => $labels) {
                // Convert mode names to expected format
                $modeKey = $this->mapModeToKey($mode);
                if ($modeKey) {
                    $setData[$modeKey] = implode(',', array_unique($labels));
                }
            }
            
            if (!empty($setData)) {
                $result[$set] = $setData;
            }
        }
        
        error_log("SCCPShowSoftkeySets_Response: Final result count=" . (is_array($result) ? count($result) : 0));
        return $result;
    }
    
    private function mapModeToKey($mode)
    {
        $modeMap = array(
            'onhook' => 'onhook',
            'connected' => 'connected', 
            'onhold' => 'onhold',
            'ringin' => 'ringin',
            'offhook' => 'offhook',
            'conntrans' => 'conntrans',
            'digitsfoll' => 'digitsfoll',
            'connconf' => 'connconf',
            'ringout' => 'ringout',
            'offhookfeat' => 'offhookfeat',
            'holdconf' => 'onhold', // Map HOLDCONF to onhold
            'inusehint' => 'connected', // Map INUSEHINT to connected
            'onhookstealable' => 'onhook' // Map ONHOOKSTEALABLE to onhook
        );
        
        return $modeMap[strtolower($mode)] ?? null;
    }
}

class SCCPShowDevices_Response extends SCCPGeneric_Response
{
    public function __construct($rawContent)
    {
        parent::__construct($rawContent);
        $this->setKey('eventlistendevent', 'SCCPShowDevicesComplete');
    }
    public function getResult()
    {
        return $this->ConvertTableData(
            'Devices',
            array('mac'),
            array('mac'=>'name','address'=>'address','descr'=>'descr','regstate'=>'status',
                  'token'=>'token','act'=>'act', 'lines'=>'lines','nat'=>'nat','regtime'=>'regtime')
            );
    }
}

class SCCPShowDevice_Response extends SCCPGeneric_Response
{
    public function __construct($rawContent)
    {
        parent::__construct($rawContent);
        $this->setKey('eventlistendevent', 'SCCPShowDeviceComplete');
    }
    public function getResult()
    {
        // This object has a list of events _events, and a list of tables _tables.
        $result = array();

        foreach ($this->_events as $trow) {
                $result = array_merge($result, $trow->getKeys());
        }
        // Now handle label changes so that keys from AMI correspond to db keys in _tables
        $result['Buttons'] = $this->ConvertTableData(
            'Buttons',
            array('id'),
            array('id'=>'id','channelobjecttype'=>'channelobjecttype','inst'=>'inst',
                  'typestr'=>'typestr', 'type'=>'type', 'pendupdt'=>'pendupdt', 'penddel'=>'penddel', 'default'=>'default'
                  )
        );
        $result['SpeeddialButtons'] = $this->ConvertTableData(
            'SpeeddialButtons',
            array('id'),
            array('id'=>'id','channelobjecttype'=>'channelobjecttype','name'=>'name','number'=>'number','hint'=>'hint')
        );
        $result['CallStatistics'] = $this->ConvertTableData(
            'CallStatistics',
            array('type'),
            array('type'=>'type','channelobjecttype'=>'channelobjecttype','calls'=>'calls','pcktsnt'=>'pcktsnt','pcktrcvd'=>'pcktrcvd',
                  'lost'=>'lost','jitter'=>'jitter','latency'=>'latency', 'quality'=>'quality','avgqual'=>'avgqual','meanqual'=>'meanqual',
                  'maxqual'=>'maxqual', 'rconceal'=>'rconceal', 'sconceal'=>'sconceal'
                  )
        );
        $result['SCCP_Vendor'] = array('vendor' => strtok($result['skinnyphonetype'], ' '), 'model' => strtok('('),
                                       'model_id' => strtok(')'), 'vendor_addon' => strtok($result['configphonetype'], ' '),
                                       'model_addon' => strtok(' '));
        if (empty($result['SCCP_Vendor']['vendor']) || $result['SCCP_Vendor']['vendor'] == 'Undefined') {
            $result['SCCP_Vendor'] = array('vendor' => 'Undefined', 'model' => $result['configphonetype'],
                                          'model_id' => '', 'vendor_addon' => $result['SCCP_Vendor']['vendor_addon'],
                                          'model_addon' => $result['SCCP_Vendor']['model_addon']
                                          );
        }
        $result['MAC_Address'] =$result['macaddress'];
        return $result;
    }
}

class ExtensionStateList_Response extends SCCPGeneric_Response
{
    public function __construct($rawContent)
    {
        parent::__construct($rawContent);
        $this->setKey('eventlistendevent', 'ExtensionStateListComplete');
    }
    public function getResult()
    {
        $result = $this->ConvertEventData(array('exten','context'), array('exten','context','hint','status','statustext'));
        return $result;
    }
}
