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

        $router->addRoute('shrnuti-registrace/<id>', 'Registration:Registration:summary');

        $router->addRoute('registrace-odeslana', 'Registration:Registration:sent');

        $router->addRoute('prihlaseni', 'Login:Login:default');

        $router->addRoute('zapomenute-heslo', 'Login:Login:forgotten');

        $router->addRoute('reset-hesla', 'Login:Login:reset');

        $router->addRoute('obnova-hesla-odeslana', 'Login:Login:sentReset');

        $router->addRoute('moje-udaje', 'Account:Account:overview');

        $router->addRoute('zmena-kontaktnich-udaju', 'Account:Account:contact');

        $router->addRoute('zmena-udaju-kliniky', 'Account:Account:clinic');

        $router->addRoute('zmena-hesla', 'Account:Account:password');

        // 3. Výchozí routa (homepage) směřující na 1. krok registrace
        $router->addRoute('<presenter>/<action>[/<id>]', [
            'module' => 'Home',
            'presenter' => 'Home',
            'action' => 'default',
        ]);
		return $router;
	}
}
