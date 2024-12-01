<?php 

namespace App\Service;
use App\Repository\DefaultRepository;

class DefaultService {
    private $defaultRepository;

    public function __construct(DefaultRepository $defaultRepository) {
        $this->defaultRepository = $defaultRepository;
    }

    public function sayHello() {
        $name = $this->defaultRepository->getName();

        return "Hello, $name!";
    }
}