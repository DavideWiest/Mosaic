
<?php
class dbTable
{
    // Deklaration einer Eigenschaft
    public $tableName;
    public $tableConf;
    private $queryTemplater;
    public $idIdentifier;
    private $dbCon;

    public function __construct($tableName, $tableConf, $dbCon)
    {
        $this.$tableName = $tableName;
        $this.$tableConf = $tableConf;
        $this.$queryTemplater = new queryTemplater($tableName, $tableConf);
        $this.$idIdentifier = $queryTemplater->idIdentifier;
        $this.$dbCon = $dbCon;
    }

    public function Insert($columnValues) {
        $query = $this->queryTemplater->GetInsert($columnValues);
        return $this->returnFetchedResult($query);
    }

    public function Update($id, $column, $value, $isString = false) {
        $query = $this->queryTemplater->GetUpdate($id, $column, $value, $isString);
        return $this->returnFetchedResult($query);
    }

    public function SelectById($condition, $limit = 0, $orderBy = "") {
        $query = $this->queryTemplater->GetSelectById($condition, $limit, $orderBy);
        return $this->returnFetchedResult($query);
    }

    public function SelectAll($limit = 0, $orderBy = "") {
        $query = $this->queryTemplater->GetSelect("1", $limit, $orderBy);
        return $this->returnFetchedResult($query);
    }

    public function Select($condition, $limit = 0, $orderBy = "") {
        $query = $this->queryTemplater->GetSelect($condition, $limit, $orderBy);
        return $this->returnFetchedResult($query);
    }

    public function Delete($id) {
        $query = $this->queryTemplater->GetDelete($id);
        return $this->returnFetchedResult($query);
    }

    private function returnFetchedResult($query) {
        $result = $this->dbCon->Execute($query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
?>