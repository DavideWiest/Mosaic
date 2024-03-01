<?php
class AccountDataRetriever {
    private $tables;

    public function __construct($dbCon) {
        $this->tables = new tableDefinitions($dbCon);
    }

    // editView-option
    public function AssignData($smarty, $userId, $allowedToEdit) {
        $smarty->assign('user', $this->tables->user->SelectById($userId));
        return $this->AssignDataShared($smarty, $userId, $allowedToEdit);
    }

    public function AssignDataByUsername($smarty, $userName, $allowedToEdit) {
        $user = $this->tables->user->Select("name = '$userName'");
        $smarty->assign('user', $user);
        return $this->AssignDataShared($smarty, $user["UserId"], $allowedToEdit);
    }
    
    private function AssignDataShared($smarty, $userId, $allowedToEdit) {
        $smarty->assign('allowedToEdit', $allowedToEdit);
        $smarty->assign('subsites', $this->tables->subsite->SelectById($userId));
        return $smarty;
    }

}
?>