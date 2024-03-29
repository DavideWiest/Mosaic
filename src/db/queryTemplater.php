<?php
class queryTemplater
{
    private $tableConf;
    private $tableName;
    private $columnData;
    private $columnNames;
    private $columnTypes;
    public $idIdentifier;
    private $queryTemplates = array();

    public function __construct($tableName, $tableConf) {
        $this->tableConf = $tableConf;
        $this->tableName = strtolower($tableName);
        if ($this->tableConf->tableNamePrefix != "") {
            $this->tableName = $this->tableConf->tableNamePrefix . $this->tableName;
        }
        
        $database = $this->tableConf->database;

        $this->columnData = explode("\n", $this->ReadFile("definition.csv"));
        // Remove last element, which is an empty string
        array_pop($this->columnData);
        $this->columnNames = array_map(function($x) { return explode(",", $x)[0]; }, $this->columnData);
        $this->columnTypes = array_combine($this->columnNames, array_map(function($x) { return explode(",", $x)[1]; }, $this->columnData));

        $this->idIdentifier = $this->columnNames[0];
        $columnNameIdentifiers = implode(", ", array_map(function ($x) { return "$x"; }, $this->columnNames));
        $valueIdentifiers = implode(", ", array_map(function($x) { return "[value-$x]"; }, $this->columnNames));
        $this->queryTemplates["insertGeneric"] = "INSERT INTO " . $database . "." . $this->tableName . " ([columns]) VALUES ([values])";
        $this->queryTemplates["insert"] = "INSERT INTO " . $database . "." . $this->tableName . " ($columnNameIdentifiers) VALUES ($valueIdentifiers)";
        $this->queryTemplates["overwrite"] = "UPDATE " . $database . "." . $this->tableName . " SET [overwrite] WHERE " . $this->idIdentifier . " = [value-Id]";
        $this->queryTemplates["update"] = "UPDATE " . $database . "." . $this->tableName . " SET [column] = [value] WHERE " . $this->idIdentifier . " = [value-Id]";
        $this->queryTemplates["select"] = "SELECT [columns] FROM " . $database . "." . $this->tableName . " WHERE [condition]";
        $this->queryTemplates["delete"] = "DELETE FROM " . $database . "." . $this->tableName . " WHERE " . $this->idIdentifier . " = [value-Id]"; 
    }

    public function GetColumnTypeData() {
        return $this->columnTypes;
    }

    public function ConvertToCvSetArray($kvArray) {
        $cvSets = array();
        foreach ($kvArray as $key => $value) {
            array_push($cvSets, new cvSet($key, $value, $this->columnTypes[$key]));
        }
        return $cvSets;
    }

    public function FilterForColumnNames($kvArray, $removeId = false) {
        $filteredDict = array();
        foreach ($kvArray as $key => $value) {
            if (in_array($key, $this->columnNames) && !($removeId && $key == $this->idIdentifier)) {
                $filteredDict[$key] = $value;
            }
        }
        return $filteredDict;
    }

    public function CheckStringLengthLimits($kvArray) {
        $exceedingColumns = array();
        foreach ($kvArray as $key => $value) {
            if ($this->columnTypes[$key] == "varchar" || $this->columnTypes[$key] == "char" || $this->columnTypes[$key] == "binary" || $this->columnTypes[$key] == "varbinary" || $this->columnTypes[$key] == "bit" || $this->columnTypes[$key] == "json" || $this->columnTypes[$key] == "enum" || $this->columnTypes[$key] == "set") {
                $length_limit = strtok(explode("(", $this->columnTypes[$key]), ")");
                if (strlen($value) > $length_limit) {
                    $exceedingColumns[] = $key;
                }
            }
        }
        return array(count($exceedingColumns) == 0, $exceedingColumns);
    }

    public function CheckFieldsNull($kvArray) {
        $nullFields = array();
        foreach ($kvArray as $key => $value) {
            if ($value == null) {
                $nullFields[] = $key;
            }
        }
        return array(count($nullFields) == 0, $nullFields);
    }

    public function GetCount() {
        return "SELECT COUNT(*) FROM " . $this->tableConf->database . "." . $this->tableName;
    }

    public function GetInsert($kvArray, $removeId) {
        if ($removeId) {
            $kvArray = array_filter($kvArray, function($x) { return $x->GetColumnPart() != $this->idIdentifier; });
        }
        // if not all columns are present, throw error
        $testKvArray = $kvArray;
        $this->ThrowIfAnyFieldsMissing($testKvArray);

        $query = $this->queryTemplates["insert"];
        foreach ($kvArray as $key => $value) {
            $query = $this->ReplaceIdentifier($query, "value", $key, $value);
        }
        return $query;
    }

    public function GetOverwriteWithCvSet($id, $updateSetArray) {
        // remove id to not cause any problems
        $updateSetArray = array_filter($updateSetArray, function($x) { return $x->GetColumnPart() != $this->idIdentifier; });

        // if not all columns are present, throw error
        $testKvArray = array_map(function($x) { return $x->column; }, $updateSetArray);
        $this->ThrowIfAnyFieldsMissing($testKvArray);
        
        $query = $this->queryTemplates["overwrite"];
        $query = $this->ReplaceIdentifier($query, "value", "Id", $id);
        $query = $this->ReplaceTypelessIdentifier($query, "overwrite", join(", ", array_map(function($x) { return $x->ToUpdateFragment(); }, $updateSetArray)));
        return $query;
    }

    public function GetInsertWithCvSet($updateSetArray, $removeId) {
        if ($removeId) {
            $updateSetArray = array_filter($updateSetArray, function($x) { return $x->GetColumnPart() != $this->idIdentifier; });
        }
        // if not all columns are present, throw error
        $testKvArray = array_map(function($x) { return $x->column; }, $updateSetArray);
        $this->ThrowIfAnyFieldsMissing($testKvArray);

        $query = $this->queryTemplates["insertGeneric"];
        $query = $this->ReplaceTypelessIdentifier($query, "columns", join(", ", array_map(function($x) { return $x->GetColumnPart(); }, $updateSetArray)));
        $query = $this->ReplaceTypelessIdentifier($query, "values", join(", ", array_map(function($x) { return $x->GetValuePart(); }, $updateSetArray)));
        return $query;
    }

    private function ThrowIfAnyFieldsMissing($testKvArray) {
        array_push($testKvArray, $this->idIdentifier); // for checking if all columns are present

        if (count(array_diff($this->columnNames, $testKvArray)) > 0) {
            $diff = array_diff($this->columnNames, $testKvArray);

            if (BusinessConstants::$HOSTMODE == "dev") {
                $logger = new FileLogger("Logs/log.txt");
                $logger->Log("Difference in keys of the update set and the table columns: ");
                $logger->Log("--- OLD: " . print_r($this->columnNames, true));
                $logger->Log("--- NEW: " . print_r($testKvArray, true));
                $logger->Log("--- DIFF: " . print_r($diff, true));
            }
            throw new Exception("Difference in keys of the update set and the table columns: " . "--- OLD: " . print_r($this->columnNames, true)  . "--- NEW: " . print_r($testKvArray, true) . "--- DIFF: " . print_r($diff, true) . "<br>");
        }
    }

    public function GetUpdate($id, $column, $value, $isString = false) {
        if (!in_array($column, $this->columnNames)) {
            return "";
        }
        $query = $this->queryTemplates["update"];
        $query = $this->ReplaceTypelessIdentifier($query, "column", $column);
        $query = $this->ReplaceTypelessIdentifier($query, "value", $isString ? "'$value'" : $value);
        $query = $this->ReplaceIdentifier($query, "value", "Id", $id);
        return $query;
    }

    public function GetSelect($condition, $limit = 0, $orderBy = "") {
        $query = $this->queryTemplates["select"];
        $query = $this->ReplaceTypelessIdentifier($query, "columns", "*");
        $query = $this->ReplaceTypelessIdentifier($query, "condition", $condition);
        if ($orderBy != "") {
            $query = $query . " ORDER BY " . $orderBy;
        }
        if ($limit > 0) {
            $query = $query . " LIMIT " . (string)$limit;
        }
        return $query;
    }
    
    public function GetSelectById($id, $limit = 0, $orderBy = "") {
        $query = $this->queryTemplates["select"];
        $query = $this->ReplaceTypelessIdentifier($query, "columns", "*");
        $query = $this->ReplaceTypelessIdentifier($query, "condition", $this->idIdentifier . " = " . (string)$id);
        if ($orderBy != "") {
            $query = $query . " ORDER BY " . $orderBy;
        }
        if ($limit > 0) {
            $query = $query . " LIMIT " . (string)$limit;
        }
        return $query;
    }

    public function GetDelete($id) {
        $query = $this->queryTemplates["delete"];
        $query = $this->ReplaceIdentifier($query, "value", "Id", $id);
        return $query;
    }

    private function ReadFile($relFileName) {
        $fileLocation = $this->tableConf->tableDataDir . "/" . $this->tableName . "/" . $relFileName;
        $file = fopen($fileLocation, "r") or die("Unable to open file at: " . $fileLocation);
        $fileContent = fread($file,filesize($fileLocation));
        return $fileContent;
    }

    private function ReplaceIdentifier($string, $identifierType, $identifier, $value) {
        return str_replace("[" . $identifierType . "-" . (string)$identifier . "]", $value, $string);
    }

    private function ReplaceTypelessIdentifier($string, $identifier, $value) {
        return str_replace("[" . (string)$identifier . "]", $value, $string);
    }
}
?>