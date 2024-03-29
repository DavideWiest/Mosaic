<?php
use Pecee\SimpleRouter\SimpleRouter;
require 'vendor/autoload.php';
// require 'vendor/smarty/smarty/libs/Smarty.class.php';
// require 'vendor\pecee\simple-router\src\Pecee\SimpleRouter\SimpleRouter.php';

foreach (glob("src/*.php") as $filename)
{
    require $filename;
}
foreach (glob("src/**/*.php") as $filename)
{
    require $filename;
}

// ---------- CREATE CLASSES ----------
$smarty = new Smarty();
$smarty->setTemplateDir('src/templates/');

$servername = "localhost";
$username = "root";
$password = "";
$dbCon = new dbConnection($servername, $username, $password);

$logger = new FileLogger("Logs/log.txt");

$sessionManager = new SessionManager();
$notifier = new Notifier($sessionManager);
$tables = new tableDefinitions($dbCon);

$frontDataRetriever = new FrontDataRetriever($tables);
$subsiteDataRetriever = new SubsiteDataRetriever($tables);
$subsiteEditDataRetriever = new SubsiteEditDataRetriever($tables);
$userDataRetriever = new UserDataRetriever($tables);
$pricingDataRetriever = new PricingDataRetriever($tables);
$createSubsiteDataRetriever = new CreateSubsiteDataRetriever($tables);

$userManager = new UserManager($tables);
$loginManager = new LoginManager($tables);
$subsiteManager = new SubsiteManager($tables);
$fragmentManager = new FragmentManager($tables);


// ---------- DEV EXTRA ----------
if (BusinessConstants::$HOSTMODE == "dev") {
    // $notifier->Post("You are in dev mode. This is a test", "info");
    // $smarty->assign("users", $tables->user->SelectAll());

    // for generic renderer
    $smarty->assign("userColumnTypeData", $tables->user->GetColumnTypeData());

    // $sessionManager->SetUserId(1);
}

// ---------- ASSIGN USER DATA ----------
if ($sessionManager->IsUserLoggedIn()) {
    $smarty->assign('maybeUsername', $tables->user->SelectById($sessionManager->GetUserId())[0]["Username"]);
} else {
    $smarty->assign('maybeUsername', "User");
}


// ---------- ASSIGN BASE DATA ----------
$smarty->assign('BusinessConstants', new BusinessConstants());
$smarty->assign('GenericRender', new GenericRender());
$smarty->assign('sessionManager', $sessionManager);

// ---------- GET ----------
// equivalent of select

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/', function() use ($frontDataRetriever, $pricingDataRetriever, $smarty, $sessionManager, $notifier, $logger) {
    if ($sessionManager->IsUserLoggedIn()) {
        Redirect('/a');
    }
    $smarty = $frontDataRetriever->AssignData($smarty);
    $smarty = $pricingDataRetriever->AssignData($smarty);
    DisplayTemplateOrNotFound($smarty, 'index.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/front', function() use ($frontDataRetriever, $smarty, $notifier, $pricingDataRetriever) {
    $smarty = $frontDataRetriever->AssignData($smarty);
    $smarty = $pricingDataRetriever->AssignData($smarty);
    DisplayTemplateOrNotFound($smarty, 'index.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/pricing', function() use ($frontDataRetriever, $pricingDataRetriever, $smarty, $notifier) {
    $smarty = $frontDataRetriever->AssignData($smarty);
    $smarty = $pricingDataRetriever->AssignData($smarty);
    DisplayTemplateOrNotFound($smarty, 'pricing.tpl', $notifier);
});

// user
SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/a', function() use ($userDataRetriever, $smarty, $sessionManager, $notifier, $logger) {
    if (!$sessionManager->IsUserLoggedIn()) {
        Redirect('/login');
    }
    $userId = $sessionManager->GetUserId();
    $smarty->assign("redirectToFront", true); // for the navbar (to show the front link instead of the user link
    $smarty = $userDataRetriever->AssignData($smarty, $userId, true, $userId);
    DisplayTemplateOrNotFound($smarty, 'user.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/login', function() use ($smarty, $sessionManager, $notifier, $logger) {
    $userId = $sessionManager->GetUserId();
    if ($sessionManager->IsUserLoggedIn()) {
        $logger->Log("user logged in - redirecting to /a");
        Redirect('/a');
    }
    DisplayTemplateOrNotFound($smarty, 'login.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/create/user', function() use ($smarty, $sessionManager, $notifier, $logger) {
    $logger->Log("create/user");
    if ($sessionManager->IsUserLoggedIn()) {
        $logger->Log("create/user: redirecting to /a");
        Redirect('/a');
    }
    DisplayTemplateOrNotFound($smarty, 'createUser.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/create/subsite', function() use ($smarty, $sessionManager, $notifier, $createSubsiteDataRetriever) {
    if (!$sessionManager->IsUserLoggedIn()) {
        Redirect('/login');
    }
    $smarty = $createSubsiteDataRetriever->AssignData($smarty, $sessionManager->GetUserId());
    DisplayTemplateOrNotFound($smarty, 'createSubsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/logout', function() use ($sessionManager, $notifier) {
    $sessionManager->LogOut();
    Redirect('/login');
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/u/{uname}', function($userName) use ($userDataRetriever, $smarty, $notifier, $sessionManager) {
    $smarty = $userDataRetriever->AssignDataByUsername($smarty, $userName, false, $sessionManager->GetUserId());
    DisplayTemplateOrNotFound($smarty, 'user.tpl', $notifier);
});

// product

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/s-id/{sid}', function($subsiteId) use ($notifier, $tables) {
    $subsitesWithId = $tables->subsite->SelectById($subsiteId);
    if (count($subsitesWithId) == 0) {
        $notifier->Post("No subsite with this id found", "error");
        Redirect('/');
    }
    $username = $tables->user->SelectById($subsitesWithId[0]["UserId"])[0]["Username"];
    Redirect("/u/" . $username . '/' . $subsitesWithId[0]["Route"]);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/s/{sroute}', function($subsiteShortRoute) use ($subsiteDataRetriever, $smarty, $notifier, $sessionManager) {
    $smarty = $subsiteDataRetriever->AssignDataByShortRoute($smarty, $subsiteShortRoute, false, $sessionManager->GetUserId());
    DisplayTemplateOrNotFound($smarty, 'subsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/s-compact/{sroute}', function($subsiteShortRoute) use ($subsiteDataRetriever, $smarty, $notifier, $sessionManager) {
    $smarty = $subsiteDataRetriever->AssignDataByShortRoute($smarty, $subsiteShortRoute, true, $sessionManager->GetUserId());
    DisplayTemplateOrNotFound($smarty, 'subsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/u/{uname}/{sroute}', function($userName, $subsiteRoute) use ($subsiteDataRetriever, $smarty, $notifier, $sessionManager) {
    $smarty = $subsiteDataRetriever->AssignDataByRoute($smarty, $userName, $subsiteRoute, false, $sessionManager->GetUserId());
    DisplayTemplateOrNotFound($smarty, 'subsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/edit/s/{sid}', function($subsiteId) use ($subsiteEditDataRetriever, $smarty, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        Redirect('/s-id/' . $subsiteId);
    }
    $smarty->assign("contentMaxWidth", "2000px"); // allow for horizontal layout
    $smarty = $subsiteEditDataRetriever->AssignData($smarty, $subsiteId, true, $userId);
    DisplayTemplateOrNotFound($smarty, 'subsiteEdit.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/edit/s/{sid}/create-f', function($subsiteId) use ($smarty, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('/edit/s/' . $subsiteId);
    }
    $smarty->assign("subsiteId", $subsiteId);
    DisplayTemplateOrNotFound($smarty, 'createFragment.tpl', $notifier);
});

// ---------- POST ----------

// user
SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/a', function() use ($userManager, $sessionManager, $smarty, $userDataRetriever, $notifier) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    list($updateSuccess, $notifier) = $userManager->HandleUpdate($userId, $notifier, $userId);

    $smarty = $userDataRetriever->AssignData($smarty, $userId, true, $sessionManager->GetUserId());
    DisplayTemplateOrNotFound($smarty, 'user.tpl', $notifier);
});

SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/login', function() use ($loginManager, $sessionManager, $notifier, $smarty) {
    list($loginSuccess, $notifier) = $loginManager->HandlePost($sessionManager, $notifier);
    
    if ($loginSuccess) {
        Redirect('/a');
    } else {
        DisplayTemplateOrNotFound($smarty, 'login.tpl', $notifier);
    }
});

SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/create/user', function() use ($userManager, $sessionManager, $notifier, $smarty) {
    if ($sessionManager->IsUserLoggedIn()) {
        Redirect('/a');
    }

    list($usercreationSuccess, $notifier) = $userManager->HandleCreate($sessionManager, $notifier);
    
    if ($usercreationSuccess) {
        Redirect('/a');
    } else {
        Redirect("/create/user");
    }
});

// subsite
SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/edit/s/{sid}', function($subsiteId) use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        Redirect('/s-id/' . $subsiteId);
    }
    list($editSuccess, $notifier) = $subsiteManager->HandleUpdate($subsiteId, $userId, $notifier);

    $smarty = $subsiteDataRetriever->AssignData($smarty, $subsiteId, false, $userId);
    Redirect("/edit/s/" . $subsiteId);
});

// subsite
SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/create/subsite', function() use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    list($creationSuccess, $notifier, $subsiteId) = $subsiteManager->HandleCreate($userId, $notifier);

    if ($creationSuccess) {
        Redirect('/edit/s/' . $subsiteId);
    } else {
        Redirect("/a");
    }
});

SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/edit/s/{sid}/update-f/{cfid}', function($subsiteId, $subsiteCfId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('/s-id/' . $subsiteId);
    }
    list($editSuccess, $notifier) = $fragmentManager->HandleUpdate($subsiteCfId, $subsiteId, $notifier);

    Redirect('/edit/s/' . $subsiteId);
});

SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/edit/s/{sid}/create-f', function($subsiteId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('/s-id/' . $subsiteId);
    }
    list($createSuccess, $notifier) = $fragmentManager->HandleCreate($subsiteId, $notifier);

    Redirect('/edit/s/' . $subsiteId);
});

// delete

SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/delete/s/{sid}', function($subsiteId) use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        Redirect('/s-id/' . $subsiteId);
    }
    list($deleteSuccess, $notifier) = $subsiteManager->HandleDelete($subsiteId, $notifier);

    Redirect('/a');
});

SimpleRouter::post(BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . '/edit/s/{sid}/delete-f/{cfid}', function($subsiteId, $subsiteCfId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if (!$sessionManager->IsUserLoggedIn()) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('/login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('/s-id/' . $subsiteId);
    }
    list($deleteSuccess, $nofitier) = $fragmentManager->HandleDelete($subsiteCfId, $notifier);

    Redirect('/edit/s/' . $subsiteId);
});


function DisplayTemplateOrNotFound($smarty, $template, $maybeNotifier) {
    if ($maybeNotifier != null) {
        $smarty->assign('notifier', $maybeNotifier);
    }
    if (!$smarty->getTemplateVars('NotFoundError') && !$smarty->getTemplateVars("Error") && $smarty->templateExists($template)) {
        $smarty->display($template);
    }
    elseif ($smarty->getTemplateVars('Error')) {
        if ($maybeNotifier != null) {
            $maybeNotifier->Post($smarty->getTemplateVars('Error'), "error");
        }
        Redirect('/');
    } else {
        if (!$smarty->getTemplateVars('NotFoundError')) {
            $smarty->assign('NotFoundError', "This page does not seem to exist.");
        }
        $smarty->assign('Error', $smarty->getTemplateVars('NotFoundError'));
        $smarty->assign("errorTitle", "404 Page Not Found");
        $smarty->display('error.tpl');
    }
}

function Redirect($url, $statuscode = 303) {
    header('Location: ' . BusinessConstants::$UNIVERSAL_ROUTE_PREFIX . $url, true, $statuscode);
    die();
}

SimpleRouter::error(function($request) use ($smarty) {
    $smarty->assign('Error', "An error occured.");
    $smarty->display('error.tpl');
});

SimpleRouter::start();

?>
