<?php

namespace FirstRouteMatching;

use Aura\Router\RouterContainer;
use Inhere\Route\ORouter;
use Inhere\Route\SRouter;
use Nice\Benchmark\Benchmark;
use Nice\Benchmark\ResultPrinter\MarkdownPrinter;
use NoahBuscher\Macaw\Macaw;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\RouteCollector;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets up the First-route matching benchmark.
 * This benchmark tests how quickly each router can match the first route
 * @param int $numIterations 测试多少次
 * @param int $numRoutes 添加多少个路由
 * @param int $numArgs 参数节点长度
 * @return Benchmark
 */
function setupBenchmark($numIterations, $numRoutes, $numArgs)
{
    $benchmark = new Benchmark($numIterations, 'First route matching', new MarkdownPrinter());
    $benchmark->setDescription(sprintf(
        'This benchmark tests how quickly each router can match the first route. %s routes each with %s arguments.',
        number_format($numRoutes),
        $numArgs
    ));

    echo "## Collect Routes Time(ms):\n\n";

    // setupAura2($benchmark, $numRoutes, $numArgs);
    // setupAura3($benchmark, $numRoutes, $numArgs);
    setupFastRoute($benchmark, $numRoutes, $numArgs);
    setupFastRouteCached($benchmark, $numRoutes, $numArgs);

    // setupZqhongRoute($benchmark, $numRoutes, $numArgs);

    setupSRouter($benchmark, $numRoutes, $numArgs);
    setupORouter($benchmark, $numRoutes, $numArgs);

//    if (extension_loaded('r3')) {
//        setupR3($benchmark, $numRoutes, $numArgs);
//    } else {
//        echo "R3 extension is not loaded. Skipping initialization for \"First route matching\" test using R3.\n";
//    }

    setupSymfony($benchmark, $numRoutes, $numArgs);
    setupSymfonyOptimized($benchmark, $numRoutes, $numArgs);
    setupPux($benchmark, $numRoutes, $numArgs);

    setupAltoRouter($benchmark, $numRoutes, $numArgs);
    setupPhroute($benchmark, $numRoutes, $numArgs);
    setupNoahBuscherMacaw($benchmark, $numRoutes, $numArgs);
    setupNoodlehausDispatch($benchmark, $numRoutes, $numArgs);

    echo "\n";

    return $benchmark;
}

function getRandomParts()
{
    $rand = md5(uniqid(mt_rand(), true));

    return array(
        substr($rand, 0, 10),
        substr($rand, -10),
    );
}


/**
 * Sets up R3 tests
 */
//function setupR3(Benchmark $benchmark, $routes, $args)
//{
//    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
//    $str = $firstStr = '';
//    $router = r3_tree_create_persist("app", 10);
//    if (!r3_tree_is_compiled($router)) {
//        for ($i = 0; $i < $routes; $i++) {
//            list ($pre, $post) = getRandomParts();
//            $str = '/' . $pre . '/' . $argString . '/' . $post;
//            if (0 === $i) {
//                $firstStr = str_replace(array('{', '}'), '', $str);
//            }
//            $lastStr = str_replace(array('{', '}'), '', $str);
//            r3_tree_insert($router, $str, "handler" . $i);
//        }
//        r3_tree_compile($router);
//    }
//
//    $benchmark->register(sprintf('php-r3 - first route(%d)', $routes), function () use ($router, $firstStr) {
//            $data = r3_tree_match($router, $firstStr);
//        });
//}

/**
 * Sets up Inhere\Route\SRouter tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupSRouter(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $firstStr = '';
    $start = microtime(true);

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        SRouter::map('GET', $str, 'handler' . $i);
    }

    $buildTime = calc_time_consuming($start, microtime(true));
    echo "- inhere/sroute(SRouter): $buildTime ms\n";

    $benchmark->register(sprintf('SRouter - first route(%d)', $routes), function () use ($firstStr) {
        SRouter::match($firstStr);
    });
}

/**
 * Sets up Inhere\Route\ORouter tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupORouter(Benchmark $benchmark, $routes, $args)
{
    $router = new ORouter;
    $firstStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $start = microtime(true);

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $router->map('GET', $str, 'handler' . $i);
    }

    $buildTime = calc_time_consuming($start, microtime(true));
    echo "- inhere/sroute(ORouter): $buildTime ms\n";

    $benchmark->register(sprintf('ORouter - first route(%d)', $routes), function () use ($router, $firstStr) {
        $router->match($firstStr);
        //var_dump($firstStr, $route);die;
    });
}

/**
 * Sets up zqhong/route tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupZqhongRoute(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i:\w+}"; }, range(1, $args)));
    $str = $firstStr = '';
    $start = microtime(true);

    /** @var \zqhong\route\RouteDispatcher $routeDispatcher */
    $router = dispatcher(function ($router) use ($routes, $argString, &$lastStr, &$firstStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            if (0 === $i) {
                $firstStr = str_replace(array('{', '}'), '', $str);
            }

            $router->addRoute('GET', $str, 'handler' . $i);
        }
    });

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- zqhong/route: $buildTime ms\n";

    $benchmark->register(sprintf('zqhong/route - first route(%d)', $routes), function () use ($router, $firstStr) {
        $route = $router->dispatch('GET', $firstStr);
    });
}

/**
 * Sets up FastRoute tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupFastRoute(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $firstStr = '';
    $start = microtime(true);

    $router = \FastRoute\simpleDispatcher(function ($router) use ($routes, $argString, &$firstStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            if (0 === $i) {
                $firstStr = str_replace(array('{', '}'), '', $str);
            }

            $router->addRoute('GET', $str, 'handler' . $i);
        }
    });

    $buildTime = calc_time_consuming($start, microtime(true));
    echo "- nikic/fast-route: $buildTime ms\n";

    $benchmark->register(sprintf('FastRoute - first route(%d)', $routes), function () use ($router, $firstStr) {
        $router->dispatch('GET', $firstStr);
    });
}

/**
 * Sets up FastRoute(cached) tests
 */
function setupFastRouteCached(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $firstStr = '';
    $start = microtime(true);

    $router = \FastRoute\cachedDispatcher(function ($router) use ($routes, $argString, &$firstStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            if (0 === $i) {
                $firstStr = str_replace(array('{', '}'), '', $str);
            }

            $router->addRoute('GET', $str, 'handler' . $i);
        }
    }, [
        'cacheFile' => dirname(__DIR__) . '/files/first-route-fst.cache', /* required */
        'cacheDisabled' => false,     /* optional, enabled by default */
    ]);

    $buildTime = calc_time_consuming($start, microtime(true));
    echo "- nikic/fast-route(cached): $buildTime ms\n";

    $benchmark->register(sprintf('FastRoute(cached) - first route(%d)', $routes), function () use ($router, $firstStr) {
        $router->dispatch('GET', $firstStr);
    });
}

/**
 * Sets up Pux tests
 */
function setupPux(Benchmark $benchmark, $routes, $args)
{
    $name = extension_loaded('pux') ? 'Pux ext' : 'Pux PHP';
    $argString = implode('/', array_map(function ($i) {
        return ':arg' . $i;
    }, range(1, $args)));
    $firstStr = '';
    $router = new \Pux\Mux;
    $start = microtime(true);

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }

        $router->add($str, 'handler' . $i);
    }

    $buildTime = calc_time_consuming($start, microtime(true));
    echo "- corneltek/pux(php): $buildTime ms\n";

    $benchmark->register(sprintf('%s - first route(%d)', $name, $routes),
        function () use ($router, $firstStr) {
            $router->match($firstStr);
        });
}

/**
 * Sets up Symfony 2 tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupSymfony(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $firstStr = '';
    $sfRoutes = new RouteCollection();
    $router = new UrlMatcher($sfRoutes, new RequestContext());
    $start = microtime(true);

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $sfRoutes->add($str, new Route($str, array('controller' => 'handler' . $i)));
    }

    $buildTime = calc_time_consuming($start, microtime(true));
    echo "- Symfony/routing: $buildTime ms\n";

    $benchmark->register('Symfony - first route', function () use ($router, $firstStr) {
        $router->match($firstStr);
    });
}

/**
 * Sets up Symfony optimized tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupSymfonyOptimized(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $firstStr = '';
    $sfRoutes = new RouteCollection();
    $start = microtime(true);

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        // $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new Route($str, array('controller' => 'handler' . $i)));
    }

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- Symfony/routing(cached): $buildTime ms\n";

    $dumper = new PhpMatcherDumper($sfRoutes);
    file_put_contents(dirname(__DIR__) . '/files/first-route-sf.php', $dumper->dump(array(
        'class' => 'FirstRouteSf2UrlMatcher'
    )));
    require_once dirname(__DIR__) . '/files/first-route-sf.php';

    $router = new \FirstRouteSf2UrlMatcher(new RequestContext());

    $benchmark->register('Symfony Cached - first route', function () use ($router, $firstStr) {
        $router->match($firstStr);
    });
}

/**
 * Sets up Aura v3 tests(todo uncomplete ...)
 * https://github.com/auraphp/Aura.Router
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupAura3(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $firstStr = '';
    $routerContainer = new RouterContainer();
    $router = $routerContainer->getMap();
    $start = microtime(true);

    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        // $lastStr = str_replace(array('{', '}'), '', $str);

        $router->get($str, $str, array(
            'controller' => 'handler' . $i
        ));
    }

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- Aura.Router: $buildTime ms\n";

    // get the route matcher from the container ...
    $matcher = $routerContainer->getMatcher();

    $benchmark->register('Aura v3 - first route', function () use ($matcher, $firstStr) {
        $matcher->match($firstStr);
    });
}

/*
 * Sets up AltoRouter tests
 */
function setupAltoRouter(Benchmark $benchmark, $numbers, $argNum)
{
    $router = new \AltoRouter;
    $firstStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "[:arg$i]";
    }, range(1, $argNum)));
    $start = microtime(true);

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('[:', ']'), '', $str);
        }

        //$lastStr = str_replace(array('[:', ']'), '', $str);
        $router->map('GET', $str, 'null_handler');
    }

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- altorouter/altorouter: $buildTime ms\n";

    $benchmark->register(sprintf('AltoRouter - first route (%s routes)', $numbers),
        function () use ($router, $firstStr) {
            $router->match($firstStr, 'GET');
        });
}

/*
 * Sets up phroute/phroute tests
 */
function setupPhroute(Benchmark $benchmark, $numbers, $argNum)
{
    $collector = new RouteCollector();
    $firstStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $argNum)));
    $start = microtime(true);

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $collector->get($str, 'null_handler');
    }

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- phroute/phroute: $buildTime ms\n";

    $dispatcher = new Dispatcher($collector->getData());

    $benchmark->register(sprintf('phroute/phroute - first route (%s routes)', $numbers),
        function () use ($dispatcher, $firstStr) {
            $dispatcher->dispatch('GET', $firstStr);
        });
}

/*
 * Sets up NoahBuscher/Macaw tests
 */
function setupNoahBuscherMacaw(Benchmark $benchmark, $numbers, $argNum)
{
    $firstStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "(:any)";
    }, range(1, $argNum)));
    $start = microtime(true);

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('(:', ')'), '', $str);
        }

        Macaw::get($str, function () {
        });
    }

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- noahbuscher/macaw: $buildTime ms\n";

    Macaw::$error_callback = function () {
    };

    $benchmark->register(sprintf('Macaw - first route (%s routes)', $numbers), function () use ($firstStr) {
        $_SERVER['REQUEST_URI'] = $firstStr;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Macaw::dispatch();
    });
}

/*
 * Sets up noodlehaus/dispatch tests
 */
function setupNoodlehausDispatch(Benchmark $benchmark, $numbers, $argNum)
{
    $firstStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "(:any$i)";
    }, range(1, $argNum)));
    $start = microtime(true);

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('(:', ')'), '', $str);
        }

        route('GET', $str, function () {
            return response('');
        });
    }

    $end = microtime(true);
    $buildTime = number_format(($end - $start) * 1000, 3);
    echo "- noodlehaus/dispatch: $buildTime ms\n";

    $benchmark->register(sprintf('noodlehaus/dispatch - first route (%s routes)', $numbers),
        function () use ($firstStr) {
            $_SERVER['REQUEST_URI'] = $firstStr;
            $_SERVER['REQUEST_METHOD'] = 'GET';
            dispatch();
        });
}
