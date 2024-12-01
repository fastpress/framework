<?php 

namespace App\Repository;

class DefaultRepository {
    public function getName() {
        // This would normally be a database call
        return "World";
    }
}