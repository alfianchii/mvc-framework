<?php
// The namespace
namespace app\core;

use app\core\db\Database;

class Application
{
    // Properties
    public static string $ROOT_DIR;
    public static Application $app;
    public string $userClass;
    public string $layout = "main";
    public Router $router;
    public Request $request;
    public Response $response;
    public ?Controller $controller = null;
    public Database $db;
    public Session $session;
    public ?DbModel $user;
    public View $view;

    public function __construct($rootPath, array $config)
    {
        // Fill out the properties
        self::$ROOT_DIR = $rootPath;
        self::$app = $this;
        $this->userClass = $config["userClass"];
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database($config["db"]);
        $this->session = new Session();
        $this->view = new View();

        /*
            Noting, we should never use any class inside the core which is outside the core.
        */
        // Get the user's session if exists
        $primaryValue = $this->session->get("user");
        if ($primaryValue) {
            $primaryKey = $this->userClass::primaryKey();
            $this->user = $this->userClass::findOne([$primaryKey => $primaryValue]);
        } else {
            $this->user = null;
        }
    }

    /*
        Methods
    */
    // Running the application
    public function run()
    {
        try {
            echo $this->router->resolve();
        } catch (\Exception $e) {
            // Set the status code
            $this->response->setStatusCode($e->getCode());
            // Render view of error
            echo $this->view->renderView("_error", [
                "exception" => $e
            ]);
        }
    }

    // Getter and setter
    public function getController(): \app\core\Controller
    {
        return $this->controller;
    }

    public function setController(\app\core\Controller $controller): void
    {
        $this->controller = $controller;
    }

    // Save user's login into session (based on id)
    public function login(UserModel $user)
    {
        // Set user
        $this->user = $user;
        // Take the primary key
        $primaryKey = $user->primaryKey();
        // Take the primary's value from user
        $primaryValue = $user->{$primaryKey};
        // Set the session
        $this->session->set("user", $primaryValue);

        return true;
    }

    // User's logout
    public function logout()
    {
        $this->user = null;
        $this->session->remove("user");
    }

    // If the user was guest
    public static function isGuest()
    {
        return !self::$app->user;
    }
}