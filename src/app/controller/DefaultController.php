<?php

namespace App\Controller;

use Fastpress\View\View;

class DefaultController {

    public function index(View $view) {

        $view->render('index.html');
    }


}
