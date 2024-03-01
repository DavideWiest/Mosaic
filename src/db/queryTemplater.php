<?php
class queryTemplater
{
    private $tableConf;
    private $tableName;
    public $columnNames;
    public $idIdentifier;
    private $queryTemplates = array();

    public function __construct($tableName, $tableConf) {
        $this.$tableConf = $tableConf;
        $this.$tableName = $tableName;

        foreach (["insert", "update", ""] as $fileName) {
            $this->queryTemplates[$fileName] = ReadFile($fileName + ".sql");
        }
        $this.$columnNames = explode("\n", ReadFile("columnNames.txt"));
        $this.$idIdentifier = $this->columnNames[0];
        $columnNameIdentifiers = array_map(function ($x) { return "`$x`"; }, $this->columnNames).join(", ");
        $valueIdentifiers = array_map(function($x) { return "[value-$x]"; }, $this->$columnNames).join(", ");
        $this->queryTemplates["insert"] = "INSERT INTO `$tableName` ($columnNameIdentifiers) VALUES ($valueIdentifiers)";
        $this->queryTemplates["update"] = "UPDATE `$tableName` SET `[column]` = [value] WHERE `$idIdentifier` = [value-Id]";
        $this->queryTemplates["select"] = "SELECT [columns] FROM `$tableName` WHERE `[condition]`";
        $this->queryTemplates["delete"] = "DELETE FROM `$tableName` WHERE `$idIdentifier` = [value-Id]"; 
    }

    public function GetInsert($kvDict) {
        $query = $this->queryTemplates["insert"];
        foreach ($kvDict as $key => $value) {
            $query = $this->ReplaceIdentifier($query, "value", $key, $value);
        }
        return $query;
    }

    public function GetUpdate($id, $column, $value, $isString = false) {
        $query = $this->queryTemplates["update"];
        $query = $this->ReplaceTypelessIdentifier($query, "column", $column);
        $query = $this->ReplaceTypelessIdentifier($query, "value", $isString ? "'$value'" : $value);
        $query = $this->ReplaceIdentifier($query, "value", "Id", $id);
        return $query;
    }

    public function GetSelect($condition, $limit = 0, $orderBy = "") {
        $query = $this->queryTemplates["select"];
        $query = $this->ReplaceTypelessIdentifier($query, "condition", $condition);
        if ($limit > 0) {
            $query += " LIMIT " + $limit;
        }
        if ($orderBy != "") {
            $query += " ORDER BY " + $orderBy;
        }
        return $query;
    }

    public function GetSelectById($id, $limit = 0, $orderBy = "") {
        $query = $this->queryTemplates["select"];
        $query = $this->ReplaceTypelessIdentifier($query, "oondition", "`" + $this->idIdentifier + "` = " + $id);
        if ($limit > 0) {
            $query += " LIMIT " + $limit;
        }
        if ($orderBy != "") {
            $query += " ORDER BY " + $orderBy;
        }
        return $query;
    }

    public function GetDelete($id) {
        $query = $this->queryTemplates["delete"];
        $query = $this->ReplaceIdentifier($query, "value", "Id", $id);
        return $query;
    }

    private function ReadFile($relFileName) {
        $fileLocation = $this->tableConf->tableDataDir + "/" + $this->tableName + "/" + $relFileName;
        $file = fopen($fileLocation, "r") or die("Unable to open file at: " + $fileLocation);
        $fileContent = fread($file,filesize($fileLocation));
        return $fileContent;
    }

    private function ReplaceIdentifier($string, $identifierType, $identifier, $value) {
        return str_replace("[" + $identifierType + "-" + $identifier + "]", $value, $string);
    }

    private function ReplaceTypelessIdentifier($string, $identifier, $value) {
        return str_replace("[" + $identifier + "]", $value, $string);
    }
}
?>