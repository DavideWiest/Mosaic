<?php
use Pecee\SimpleRouter\SimpleRouter;

// include 'src/db/dbConnection.php';

foreach (glob("src/*.php") as $filename)
{
    require $filename;
}
foreach (glob("src/**/*.php") as $filename)
{
    require $filename;
}


// use Smarty\Smarty\Smarty;
require 'vendor/autoload.php';

$smarty = new Smarty();
$smarty->setTemplateDir('src/templates/');

$servername = "localhost";
$username = "root";
$password = "";

$dbCon = new dbConnection($servername, $username, $password);
$sessionManager = new SessionManager();
$notifier = new Notifier();
$tables = new tableDefinitions($dbCon);

$frontDataRetriever = new FrontDataRetriever($tables);
$subsiteDataRetriever = new SubsiteDataRetriever($tables);
$accountDataRetriever = new AccountDataRetriever($tables);
$pricingDataRetriever = new PricingDataRetriever($tables);

$accountManager = new AccountManager($tables);
$loginManager = new LoginManager($tables);
$subsiteManager = new SubsiteManager($tables);
$fragmentManager = new FragmentManager($tables);

$smarty->assign('BusinessConstants', new BusinessConstants());

// ---------- GET ----------
// equivalent of select

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/', function() use ($frontDataRetriever, $smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        header('Location: /a');
    }
    $smarty = $frontDataRetriever->AssignData($smarty);
    DisplayTemplateOrNotFound($smarty, 'index.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/front', function() use ($frontDataRetriever, $smarty, $notifier) {
    $smarty = $frontDataRetriever->AssignData($smarty);
    DisplayTemplateOrNotFound($smarty, 'index.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/pricing', function() use ($frontDataRetriever, $smarty, $notifier) {
    $smarty = $frontDataRetriever->AssignData($smarty);
    DisplayTemplateOrNotFound($smarty, 'pricing.tpl', $notifier);
});

// account
SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/a', function() use ($accountDataRetriever, $smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        header('Location: /login');
    }
    $smarty = $accountDataRetriever->AssignData($smarty, $userId, true);
    DisplayTemplateOrNotFound($smarty, 'account.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/login', function() use ($smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        header('Location: /a');
    }
    DisplayTemplateOrNotFound($smarty, 'login.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/create/account', function() use ($smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        header('Location: /a');
    }
    DisplayTemplateOrNotFound($smarty, 'createAccount.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/create/subsite', function() use ($smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        header('Location: /a');
    }
    DisplayTemplateOrNotFound($smarty, 'createSubsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/logout', function() use ($sessionManager, $notifier) {
    $sessionManager->SetUserId(null);
    header('Location: /login');
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/u/{uname}', function($userName) use ($accountDataRetriever, $smarty, $notifier) {
    $smarty = $accountDataRetriever->AssignDataByUsername($smarty, $userName, false);
    DisplayTemplateOrNotFound($smarty, 'account.tpl', $notifier);
});

// product
SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/s/{sid}', function($subsiteId) use ($subsiteDataRetriever, $smarty, $notifier) {
    $smarty = $subsiteDataRetriever->AssignData($smarty, $subsiteId, false);
    DisplayTemplateOrNotFound($smarty, 'subsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/u/{uname}/{sroute}', function($userName, $subsiteRoute, $notifier) use ($subsiteDataRetriever, $smarty) {
    $smarty = $subsiteDataRetriever->AssignDataByRoute($smarty, $userName, $subsiteRoute, false);
    DisplayTemplateOrNotFound($smarty, 'subsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/edit/s/{sid}', function($subsiteId) use ($subsiteDataRetriever, $smarty, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        header('Location: /s/' + $subsiteId);
    }
    $smarty = $subsiteDataRetriever->AssignData($smarty, $subsiteId, true);
    DisplayTemplateOrNotFound($smarty, 'subsiteEdit.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/edit/s/{sid}/create-f', function($subsiteId) use ($smarty, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        header('Location: /s/' + $subsiteId);
    }

    DisplayTemplateOrNotFound($smarty, 'createFragment.tpl', $notifier);
});

// ---------- POST ----------

// account
SimpleRouter::post('/a', function() use ($accountManager, $sessionManager, $smarty, $accountDataRetriever, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    list($updateSuccess, $notifier) = $accountManager->HandleUpdate($userId, $notifier);

    $smarty = $accountDataRetriever->AssignData($smarty, $userId, true);
    DisplayTemplateOrNotFound($smarty, 'account.tpl', $notifier);
});

SimpleRouter::post('/login', function() use ($loginManager, $sessionManager, $notifier, $smarty) {
    list($loginSuccess, $notifier) = $loginManager->HandlePost($sessionManager, $notifier);
    
    if ($loginSuccess) {
        header('Location: /a');
    } else {
        DisplayTemplateOrNotFound($smarty, 'login.tpl', $notifier);
    }
});

SimpleRouter::post('/create/account', function() use ($accountManager, $sessionManager, $notifier, $smarty) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        header('Location: /a');
    }

    list($accountcreationSuccess, $notifier) = $accountManager->HandleCreate($sessionManager, $notifier);
    
    if ($accountcreationSuccess) {
        header('Location: /a');
    } else {
        DisplayTemplateOrNotFound($smarty, 'createAccount.tpl', $notifier);
    }
});

// subsite
SimpleRouter::post('/edit/s/{sid}', function($subsiteId) use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        header('Location: /s/' + $subsiteId);
    }
    list($editSuccess, $notifier) = $subsiteManager->HandleUpdate($subsiteId, $userId, $notifier);

    $smarty = $subsiteDataRetriever->AssignData($smarty, $subsiteId, true);
    DisplayTemplateOrNotFound($smarty, 'subsiteEdit.tpl', $notifier);
});

// subsite
SimpleRouter::post('/create/subsite', function() use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    list($creationSuccess, $subsiteId) = $subsiteManager->HandleCreate($userId, $notifier);

    if (!$creationSuccess) {
        $smarty->display('createSubsite.tpl');
    } else {
        header('Location: /edit/s/' + $subsiteId);
    }
});

// subsite
SimpleRouter::post('/edit/s/{sid}/update-f/{fid}', function($subsiteId, $fragmentId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        header('Location: /s/' + $subsiteId);
    }
    list($editSuccess, $notifier) = $fragmentManager->HandleUpdate($fragmentId, $subsiteId, $notifier);

    header('Location: /edit/s/' + $subsiteId);
});

SimpleRouter::post('/edit/s/{sid}/create-f', function($subsiteId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        header('Location: /s/' + $subsiteId);
    }
    list($createSuccess, $notifier) = $fragmentManager->HandleCreate($subsiteId, $notifier);

    header('Location: /edit/s/' + $subsiteId);
});

// delete

SimpleRouter::post('/delete/s/{sid}', function($subsiteId) use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        header('Location: /s/' + $subsiteId);
    }
    list($deleteSuccess, $notifier) = $subsiteManager->HandleDelete($subsiteId, $notifier);

    header('Location: /a');
});

SimpleRouter::post('/edit/s/{sid}/delete-f/{fid}', function($subsiteId, $fragmentId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        header('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        header('Location: /s/' + $subsiteId);
    }
    list($deleteSuccess, list($notifier, $notifier)) = $fragmentManager->HandleDelete($fragmentId, $notifier);

    header('Location: /edit/s/' + $subsiteId);
});


function DisplayTemplateOrNotFound($smarty, $template, $notifier) {
    if ($notifier != null) {
        $smarty->assign('notifier', $notifier);
    }
    if (!$smarty->getTemplateVars('NotFoundError') && $smarty->templateExists($template)) {
        $smarty->display($template);
    } else {
        $smarty->display("notFound.tpl");
        // header('HTTP/1.0 404 Not Found');
    }
}


SimpleRouter::start();

?>
