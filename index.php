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

// ---------- CREATE CLASSES ----------
$dbCon = new dbConnection($servername, $username, $password);
$sessionManager = new SessionManager();
$notifier = new Notifier();
$tables = new tableDefinitions($dbCon);

$logger = new FileLogger("log.txt");

$frontDataRetriever = new FrontDataRetriever($tables);
$subsiteDataRetriever = new SubsiteDataRetriever($tables);
$userDataRetriever = new UserDataRetriever($tables);
$pricingDataRetriever = new PricingDataRetriever($tables);

$userManager = new UserManager($tables);
$loginManager = new LoginManager($tables);
$subsiteManager = new SubsiteManager($tables);
$fragmentManager = new FragmentManager($tables);

// ---------- ASSIGN BASE DATA ----------
$smarty->assign('BusinessConstants', new BusinessConstants());
$smarty->assign('GenericRender', new GenericRender());
$smarty->assign('sessionManager', $sessionManager);

if ($sessionManager->GetUserId() != null) {
    $smarty->assign('maybeUsername', $tables->user->SelectById($sessionManager->GetUserId()));
} else {
    $smarty->assign('maybeUsername', null);
}

// ---------- DEV EXTRA ----------
if (BusinessConstants::$HOSTMODE == "dev") {
    $notifier->Post("You are in dev mode. This is a test", "warning");
    $smarty->assign("users", $tables->user->SelectAll());
}

// ---------- GET ----------
// equivalent of select

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/', function() use ($frontDataRetriever, $smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        Redirect('Location: /a');
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

// user
SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/a', function() use ($userDataRetriever, $smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        Redirect('Location: /login');
    }
    $smarty = $userDataRetriever->AssignData($smarty, $userId, true);
    DisplayTemplateOrNotFound($smarty, 'user.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/login', function() use ($smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        Redirect('Location: /a');
    }
    DisplayTemplateOrNotFound($smarty, 'login.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/create/user', function() use ($smarty, $sessionManager, $notifier, $logger) {
    $logger->Log("create/user");
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        $logger->Log("create/user: redirecting to /a");
        Redirect('Location: /a');
    }
    DisplayTemplateOrNotFound($smarty, 'createUser.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/create/subsite', function() use ($smarty, $sessionManager, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        Redirect('Location: /a');
    }
    DisplayTemplateOrNotFound($smarty, 'createSubsite.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/logout', function() use ($sessionManager, $notifier) {
    $sessionManager->SetUserId(null);
    Redirect('Location: /login');
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/u/{uname}', function($userName) use ($userDataRetriever, $smarty, $notifier) {
    $smarty = $userDataRetriever->AssignDataByUsername($smarty, $userName, false);
    DisplayTemplateOrNotFound($smarty, 'user.tpl', $notifier);
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
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        Redirect('Location: /s/' . $subsiteId);
    }
    $smarty = $subsiteDataRetriever->AssignData($smarty, $subsiteId, true);
    DisplayTemplateOrNotFound($smarty, 'subsiteEdit.tpl', $notifier);
});

SimpleRouter::get(BusinessConstants::$UNIVERSAL_PAGE_ROUTE_PREFIX . '/edit/s/{sid}/create-f', function($subsiteId) use ($smarty, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('Location: /s/' . $subsiteId);
    }

    DisplayTemplateOrNotFound($smarty, 'createFragment.tpl', $notifier);
});

// ---------- POST ----------

// user
SimpleRouter::post('/a', function() use ($userManager, $sessionManager, $smarty, $userDataRetriever, $notifier) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    list($updateSuccess, $notifier) = $userManager->HandleUpdate($userId, $notifier);

    $smarty = $userDataRetriever->AssignData($smarty, $userId, true);
    DisplayTemplateOrNotFound($smarty, 'user.tpl', $notifier);
});

SimpleRouter::post('/login', function() use ($loginManager, $sessionManager, $notifier, $smarty) {
    list($loginSuccess, $notifier) = $loginManager->HandlePost($sessionManager, $notifier);
    
    if ($loginSuccess) {
        Redirect('Location: /a');
    } else {
        DisplayTemplateOrNotFound($smarty, 'login.tpl', $notifier);
    }
});

SimpleRouter::post('/create/user', function() use ($userManager, $sessionManager, $notifier, $smarty) {
    $userId = $sessionManager->GetUserId();
    if ($userId != null) {
        Redirect('Location: /a');
    }

    list($usercreationSuccess, $notifier) = $userManager->HandleCreate($sessionManager, $notifier);
    
    if ($usercreationSuccess) {
        Redirect('Location: /a');
    } else {
        DisplayTemplateOrNotFound($smarty, 'createUser.tpl', $notifier);
    }
});

// subsite
SimpleRouter::post('/edit/s/{sid}', function($subsiteId) use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        Redirect('Location: /s/' . $subsiteId);
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
        Redirect('Location: /login');
    }
    list($creationSuccess, $subsiteId) = $subsiteManager->HandleCreate($userId, $notifier);

    if (!$creationSuccess) {
        $smarty->display('createSubsite.tpl');
    } else {
        Redirect('Location: /edit/s/' . $subsiteId);
    }
});

// subsite
SimpleRouter::post('/edit/s/{sid}/update-f/{fid}', function($subsiteId, $fragmentId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('Location: /s/' . $subsiteId);
    }
    list($editSuccess, $notifier) = $fragmentManager->HandleUpdate($fragmentId, $subsiteId, $notifier);

    Redirect('Location: /edit/s/' . $subsiteId);
});

SimpleRouter::post('/edit/s/{sid}/create-f', function($subsiteId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('Location: /s/' . $subsiteId);
    }
    list($createSuccess, $notifier) = $fragmentManager->HandleCreate($subsiteId, $notifier);

    Redirect('Location: /edit/s/' . $subsiteId);
});

// delete

SimpleRouter::post('/delete/s/{sid}', function($subsiteId) use ($subsiteManager, $sessionManager, $notifier, $smarty, $subsiteDataRetriever, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this subsite", "error");
        Redirect('Location: /s/' . $subsiteId);
    }
    list($deleteSuccess, $notifier) = $subsiteManager->HandleDelete($subsiteId, $notifier);

    Redirect('Location: /a');
});

SimpleRouter::post('/edit/s/{sid}/delete-f/{fid}', function($subsiteId, $fragmentId) use ($fragmentManager, $sessionManager, $notifier, $tables) {
    $userId = $sessionManager->GetUserId();
    if ($userId == null) {
        $notifier->Post("Log in, then visit the url again", "error");
        Redirect('Location: /login');
    }
    if (!AuthorizationCheck::IsAuthorizedSubsite($subsiteId, $userId, $tables)) {
        $notifier->Post("You are not authorized to edit this fragment", "error");
        Redirect('Location: /s/' . $subsiteId);
    }
    list($deleteSuccess, list($notifier, $notifier)) = $fragmentManager->HandleDelete($fragmentId, $notifier);

    Redirect('Location: /edit/s/' . $subsiteId);
});


function DisplayTemplateOrNotFound($smarty, $template, $notifier) {
    if ($notifier != null) {
        $smarty->assign('notifier', $notifier);
    }
    if (!$smarty->getTemplateVars('NotFoundError') && $smarty->templateExists($template)) {
        $smarty->display($template);
    } else {
        $smarty->display("notFound.tpl");
        // Redirect('HTTP/1.0 404 Not Found');
    }
}

function Redirect($url, $statuscode = 303) {
    echo "Attempting redirect to: $url\n";
    Redirect('Location: ' . $url, true, $statuscode);
    die();
}

SimpleRouter::start();

?>
