<?php

namespace App\Controller;

use Fastpress\Presentation\View;

class DefaultController {

    public function index(View $view) {

        $view->render('index.html');
    }


}
