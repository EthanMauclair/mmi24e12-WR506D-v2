<?php

declare(strict_types=1);

/*
 * Fournit le gestionnaire d'entités à PHPStan (extension phpstan-doctrine),
 * pour qu'il vérifie les requêtes DQL et les types des champs mappés.
 */

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
