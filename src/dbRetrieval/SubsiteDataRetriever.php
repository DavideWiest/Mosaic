<?php
class SubsiteDataRetriever {
    private $tables;

    public function __construct($tables) {
        $this->tables = $tables;
    }

    public function AssignData($smarty, $subsiteId, $isCompactVerison, $userId) {
        if (!is_numeric($subsiteId) || (int)$subsiteId < 0) {
            $smarty->assign('NotFoundError', "Subsite id must be a number");
            return $smarty;
        }
        $subsitesWithId = $this->tables->subsite->SelectById($subsiteId);
        if (count($subsitesWithId) == 0) {
            $smarty->assign('NotFoundError', "No subsite with this id found");
            return $smarty;
        }
        return $this->AssignDataShared($smarty, $subsitesWithId[0], $isCompactVerison, $userId);
    }

    public function AssignDataByRoute($smarty, $userName, $subsiteRoute, $isCompactVerison) {
        $usersWithId = $this->tables->user->Select("UserName = '$userName'");
        if (count($usersWithId) == 0) {
            $smarty->assign('NotFoundError', "No user with this username found");
            return $smarty;
        }
        $userId = $usersWithId[0]["UserId"];

        $subsitesWithRoute = $this->tables->subsite->Select("UserId = '$userId' AND Route = '$subsiteRoute'");
        if (count($subsitesWithRoute) == 0) {
            $smarty->assign('NotFoundError', "No subsite with this route found");
            return $smarty;
        }
        return $this->AssignDataShared($smarty, $subsitesWithRoute[0], $isCompactVerison, $userId);
    }

    public function AssignDataByShortRoute($smarty, $subsiteShortRoute, $isCompactVerison, $userId) {
        $subsitesWithRoute = $this->tables->subsite->Select("ShortRoute = '$subsiteShortRoute'");
        if (count($subsitesWithRoute) == 0) {
            $smarty->assign('NotFoundError', "No subsite with this short route found");
            return $smarty;
        }
        $subsite = $subsitesWithRoute[0];
        $planperm = UserDataRetriever::GetPlanPermissions($this->tables, $subsite["UserId"]);
        if (!$planperm["ShortLinkOption"]) {
            $smarty->assign('NotFoundError', "No subsite with this short route found");
            return $smarty;
        }
        return $this->AssignDataShared($smarty, $subsite, $isCompactVerison, $userId);
    }

    private function AssignDataShared($smarty, $subsite, $isCompactVerison, $userId) {
        $smarty->assign('subsite', $subsite);
        $smarty->assign('isCompactVerison', $isCompactVerison);
        $smarty->assign('owner', $this->tables->user->SelectById($subsite["UserId"])[0]);
        $smarty->assign('isOwner', $subsite["UserId"] == $userId);

        $genericFragments = $this->GetGenericFragments($subsite["SubSiteId"]);

        $fragmentIds = array();
        $fragments = array();
        foreach ($genericFragments as $fragment) {
            $tableName = $fragment["ContentTableName"];
            $fragmentId = $fragment["ContentId"];
            $fragmentContentWithId = $this->tables->fragments->GetTableByName($tableName)->SelectById($fragmentId);
            
            // for whatever reason, duplicates are being returned from the query sometimes
            if (in_array($fragment["SubsiteContentFragmentId"], $fragmentIds)) {
                continue;
            }
            array_push($fragmentIds, $fragment["SubsiteContentFragmentId"]);
            
            if (count($fragmentContentWithId) == 0) {
                FragmentManager::HandleDeleteUnstableFragment($fragment["SubsiteContentFragmentId"], $this->tables);
                continue;
            }
            
            $fragmentContent = $fragmentContentWithId[0];
            
            $extraFragmentContent = $this->GetExtraFragmentContent($tableName, $fragmentId, $fragmentContent);
            $fragmentArr = array("Content" => $this->GetTemplatedFragment($tableName, $fragmentContent, $extraFragmentContent), "subsiteCf" => $fragment);
            array_push($fragments, $fragmentArr);
        }

        $smarty->assign('fragments', $fragments);
        $smarty->assign('subsiteColumnTypeData', $this->tables->subsite->GetColumnTypeData());
        return $smarty;
    }

    public function GetGenericFragments($subsiteId) {
        return $this->tables->subsitecf->Select("WebsiteId = $subsiteId", 0, "Position");
    }

    private function GetTemplatedFragment($tableName, $fragmentContent, $extraFragmentContent) {
        $count = 1;
        $templateName = strtolower($tableName);
        $templateName = str_replace("fragment", "", $templateName, $count);

        $smarty = new Smarty();
        $smarty->setTemplateDir('src/templates/fragments');
        $smarty->assign('fragmentContent', $fragmentContent);
        $smarty->assign('extraFragmentContent', $extraFragmentContent);
        
        // $log = new FileLogger("Logs/log.txt");
        // $log->Log("extra fragmentcontent: " . json_encode($extraFragmentContent));

        $smarty->assign("fragmentColumnTypeData", $this->tables->fragments->GetTableByName($tableName)->GetColumnTypeData());
        return $smarty->fetch("$templateName.tpl");
    }

    private function GetExtraFragmentContent($tableName, $fragmentId, $fragmentContent) {
        if ($tableName == "FragmentCredentials") {
            $userId = $fragmentContent["UserId"];
            return $this->tables->user->SelectById($userId)[0];
        }
        return array();
    }
}
?>