<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

        // 1. Routa pro administraci na čisté URL /admin
        $router->addRoute('admin', 'Admin:Admin:default');

		$router->addRoute('registrace', 'Registration:Registration:default');

        // 2. Routa pro 2. krok registrace (např. /dokonceni-registrace?token=XYZ)
        // Zabráníme tomu, aby URL vypadala jako /registration/complete
        $router->addRoute('dokonceni-registrace', 'Registration:Registration:complete');

        // 3. Výchozí routa (homepage) směřující na 1. krok registrace
        $router->addRoute('<presenter>/<action>[/<id>]', [
            'module' => 'Home',
            'presenter' => 'Home',
            'action' => 'default',
        ]);
		return $router;
	}
}
