<?php

namespace App\Controller;

use Fastpress\Presentation\View;
use Fastpress\Service\DefaultService;

class DefaultController {
    private $defaultService;

    public function __construct(DefaultService $defaultService) {
        $this->defaultService = $defaultService;
    }

    public function index(View $view) {
        $message = $this->defaultService->sayHello();
        $view->render('index.html', ['message' => $message]);
    }


}
