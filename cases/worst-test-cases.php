<?php

namespace WorstCaseMatching;

use Aura\Router\RouterContainer;
use Inhere\Route\CachedRouter;
use Inhere\Route\ORouter;
use Inhere\Route\SRouter;
use Nice\Benchmark\Benchmark;
use Nice\Benchmark\ResultPrinter\MarkdownPrinter;
use NoahBuscher\Macaw\Macaw;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\RouteCollector;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets up the Worst-case matching benchmark.
 * This benchmark generates a randomly prefixed and suffixed route, in an attempt to thwart
 * any optimization.
 * @param $numIterations
 * @param int $numRoutes
 * @param int $numArgs
 * @return Benchmark
 */
function setupBenchmark($numIterations, $numRoutes, $numArgs)
{
    $benchmark = new Benchmark($numIterations, 'Worst-case matching', new MarkdownPrinter());
    $benchmark->setDescription(sprintf(
        'This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. %s routes each with %s arguments.',
        number_format($numRoutes),
        $numArgs
    ));

    // setupAura2($benchmark, $numRoutes, $numArgs);
    // setupAura3($benchmark, $numRoutes, $numArgs);
    setupFastRoute($benchmark, $numRoutes, $numArgs);
    setupFastRouteCached($benchmark, $numRoutes, $numArgs);

    // setupZqhongRoute($benchmark, $numRoutes, $numArgs);

    setupSRouter($benchmark, $numRoutes, $numArgs);
    setupORouter($benchmark, $numRoutes, $numArgs);
    setupCachedRouter($benchmark, $numRoutes, $numArgs);

//
//    if (extension_loaded('r3')) {
//        setupR3($benchmark, $numRoutes, $numArgs);
//    } else {
//        echo "R3 extension is not loaded. Skipping initialization for \"Worst-case matching\" test using R3.\n";
//    }

    setupSymfony($benchmark, $numRoutes, $numArgs);
    setupSymfonyOptimized($benchmark, $numRoutes, $numArgs);
    setupPux($benchmark, $numRoutes, $numArgs);

    setupAltoRouter($benchmark, $numRoutes, $numArgs);
    setupPhroute($benchmark, $numRoutes, $numArgs);
    setupNoahBuscherMacaw($benchmark, $numRoutes, $numArgs);
    setupNoodlehausDispatch($benchmark, $numRoutes, $numArgs);

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

//function setupR3(Benchmark $benchmark, $routes, $args)
//{
//    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
//    $str = $lastStr = '';
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
//    $benchmark->register(sprintf('r3 - last route (%s routes)', $routes), function () use ($router, $lastStr) {
//            $data = r3_tree_match($router, $lastStr);
//        });
//
//    $benchmark->register(sprintf('r3 - unknown route (%s routes)', $routes), function () use ($router) {
//            $data = r3_tree_match($router, "/not-even-real");
//        });
//}

/*
 * Sets up Inhere\Route\SRouter tests
 */
function setupSRouter(Benchmark $benchmark, $numbers, $argNum)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $argNum)));
    $str = '';

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        SRouter::map('GET', $str, 'handler' . $i);
    }

    $lastStr = str_replace(array('{', '}'), '', $str);
    $benchmark->register(sprintf('inhere/sroute(SRouter) - last route (%s routes)', $numbers),
        function () use ($lastStr) {
            SRouter::match($lastStr);
        });

    $benchmark->register(sprintf('inhere/sroute(SRouter) - unknown route (%s routes)', $numbers), function () {
        SRouter::match('/not-even-real');
    });
}

/*
 * Sets up Inhere\Route\ORouter tests
 */
function setupORouter(Benchmark $benchmark, $numbers, $argNum)
{
    $router = new ORouter;
    $str = '';
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $argNum)));

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('{', '}'), '', $str);
        // }

        $router->map('GET', $str, 'null_handler');
    }

    $lastStr = str_replace(array('{', '}'), '', $str);
    $benchmark->register(sprintf('inhere/sroute(ORouter) - last route (%s routes)', $numbers),
        function () use ($router, $lastStr) {
            $router->match($lastStr);
        });

    $benchmark->register(sprintf('inhere/sroute(ORouter) - unknown route (%s routes)', $numbers),
        function () use ($router) {
            $router->match('/not-even-real');
        });
}

/*
 * Sets up Inhere\Route\CachedRouter tests
 */
function setupCachedRouter(Benchmark $benchmark, $numbers, $argNum)
{
    $router = new CachedRouter([
        'cacheFile' => dirname(__DIR__) . '/files/worst-case-cachedRouter.php',
        'cacheEnable' => false,
    ]);
    $str = '';
    $argString = implode('/', array_map(function ($i) {
        return "{any$i}";
    }, range(1, $argNum)));

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        $router->map('GET', $str, 'null_handler');
    }

    $lastStr = str_replace(array('{', '}'), '', $str);
    $router->match($lastStr);

    // $benchmark->register(sprintf('ORouter(cached) - last route (%s routes)', $numbers), function () use ($router, $lastStr) {
    //     $route = $router->match($lastStr, 'GET');
    // });

    // $benchmark->register(sprintf('ORouter(cached) - unknown route (%s routes)', $numbers), function () use ($router) {
    //     $route = $router->match('/not-even-real', 'GET');
    // });
}

/*
 * Sets up zqhong/route tests
 */
function setupZqhongRoute(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i:\w+}"; }, range(1, $args)));
    $str = '';

    /** @var \zqhong\route\RouteDispatcher $routeDispatcher */
    $router = dispatcher(function ($router) use ($routes, $argString, &$lastStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            $lastStr = str_replace(array('{', '}'), '', $str);
            $router->addRoute('GET', $str, 'null_handler');
        }
    });

    $benchmark->register(sprintf('zqhong/route - last route (%s routes)', $routes),
        function () use ($router, $lastStr) {
            $route = $router->dispatch('GET', $lastStr);
        });

    $benchmark->register(sprintf('zqhong/route - unknown route (%s routes)', $routes),
        function () use ($router) {
            $route = $router->dispatch('GET', '/not-even-real');
        });
}

/*
 * Sets up FastRoute tests
 */
function setupFastRoute(Benchmark $benchmark, $routes, $args)
{
    $lastStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));

    $router = \FastRoute\simpleDispatcher(function ($router) use ($routes, $argString, &$lastStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            $lastStr = str_replace(array('{', '}'), '', $str);
            $router->addRoute('GET', $str, 'null_handler');
        }
    });

    $benchmark->register(sprintf('FastRoute - last route (%s routes)', $routes), function () use ($router, $lastStr) {
        $router->dispatch('GET', $lastStr);
    });

    $benchmark->register(sprintf('FastRoute - unknown route (%s routes)', $routes), function () use ($router) {
        $router->dispatch('GET', '/not-even-real');
    });
}


/*
 * Sets up FastRoute(use cache) tests
 */
function setupFastRouteCached(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));

    $lastStr = '';
    $router = \FastRoute\cachedDispatcher(function ($router) use ($routes, $argString, &$lastStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            // if (0 === $i) {
            //     $firstStr = str_replace(array('{', '}'), '', $str);
            // }

            $lastStr = str_replace(array('{', '}'), '', $str);
            $router->addRoute('GET', $str, 'null_handler');
        }
    }, [
        'cacheFile' => dirname(__DIR__) . '/files/worst-route-fst.cache', /* required */
        'cacheDisabled' => false,     /* optional, enabled by default */
    ]);

    $benchmark->register(
        sprintf('FastRoute(cached) - last route (%s routes)', $routes),
        function () use ($router, $lastStr) {
            $router->dispatch('GET', $lastStr);
        }
    );

    $benchmark->register(
        sprintf('FastRoute(cached) - unknown route (%s routes)', $routes),
        function () use ($router) {
            $router->dispatch('GET', '/not-even-real');
        }
    );
}

/*
 * Sets up Pux tests
 */
function setupPux(Benchmark $benchmark, $routes, $args)
{
    $name = extension_loaded('pux') ? 'Pux ext' : 'Pux PHP';

    $lastStr = '';
    $argString = implode('/', array_map(function ($i) {
        return ':arg' . $i;
    }, range(1, $args)));
    $router = new \Pux\Mux;
    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(':', '', $str);
        // }
        $lastStr = str_replace(':', '', $str);
        $router->add($str, 'null_handler');
    }

    $benchmark->register(sprintf('%s - last route (%s routes)', $name, $routes), function () use ($router, $lastStr) {
        $router->match($lastStr);
    });

    $benchmark->register(sprintf('%s - unknown route (%s routes)', $name, $routes), function () use ($router) {
        $router->match('/not-even-real');
    });
}

/*
 * Sets up Symfony 2 tests
 */
function setupSymfony(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $lastStr = '';
    $sfRoutes = new RouteCollection();
    $router = new UrlMatcher($sfRoutes, new RequestContext());
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('{', '}'), '', $str);
        // }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new \Symfony\Component\Routing\Route($str, array('controller' => 'handler' . $i)));
    }

    $benchmark->register(sprintf('Symfony - last route (%s routes)', $routes), function () use ($router, $lastStr) {
        $router->match($lastStr);
    });

    $benchmark->register(sprintf('Symfony - unknown route (%s routes)', $routes), function () use ($router) {
        try {
            $router->match('/not-even-real');
        } catch (ResourceNotFoundException $e) {
        }
    });
}

/*
 * Sets up Symfony optimized tests
 */
function setupSymfonyOptimized(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $lastStr = '';
    $sfRoutes = new RouteCollection();
    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('{', '}'), '', $str);
        // }
        $lastStr = str_replace(array('{', '}'), '', $str);
        $sfRoutes->add($str, new \Symfony\Component\Routing\Route($str, array('controller' => 'handler' . $i)));
    }
    $dumper = new PhpMatcherDumper($sfRoutes);
    file_put_contents(dirname(__DIR__) . '/files/worst-case-sf.php', $dumper->dump(array(
        'class' => 'WorstCaseSf2UrlMatcher'
    )));
    require_once dirname(__DIR__) . '/files/worst-case-sf.php';

    $router = new \WorstCaseSf2UrlMatcher(new RequestContext());

    $benchmark->register(
        sprintf('Symfony Cached - last route (%s routes)', $routes),
        function () use ($router, $lastStr) {
            $router->match($lastStr);
        });

    $benchmark->register(
        sprintf('Symfony Cached - unknown route (%s routes)', $routes), function () use ($router) {
        try {
            $router->match('/not-even-real');
        } catch (ResourceNotFoundException $e) {
        }
    });
}

/*
 * Sets up Aura v3 tests(todo uncomplete ...)
 *
 * https://github.com/auraphp/Aura.Router
 */
function setupAura3(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $args)));
    $lastStr = '';
    $routerContainer = new RouterContainer();
    $router = $routerContainer->getMap();

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('{', '}'), '', $str);
        // }
        $lastStr = str_replace(array('{', '}'), '', $str);
        $router->get($str, $str, array(
            'controller' => 'null_handler'
        ));
    }

    // get the route matcher from the container ...
    $matcher = $routerContainer->getMatcher();

    $benchmark->register(sprintf('Aura v2 - last route (%s routes)', $routes), function () use ($matcher, $lastStr) {
        $matcher->match($lastStr);
    });

    $benchmark->register(sprintf('Aura v2 - unknown route (%s routes)', $routes), function () use ($matcher) {
        $matcher->match('/not-even-real');
    });
}

/*
 * Sets up AltoRouter tests
 */
function setupAltoRouter(Benchmark $benchmark, $numbers, $argNum)
{
    $router = new \AltoRouter;
    $lastStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "[:arg$i]";
    }, range(1, $argNum)));

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('[:', ']'), '', $str);
        // }

        $lastStr = str_replace(array('[:', ']'), '', $str);
        $router->map('GET', $str, 'null_handler');
    }

    $benchmark->register(sprintf('AltoRouter - last route (%s routes)', $numbers), function () use ($router, $lastStr) {
        $router->match($lastStr, 'GET');
    });

    $benchmark->register(sprintf('AltoRouter - unknown route (%s routes)', $numbers), function () use ($router) {
        $router->match('/not-even-real', 'GET');
    });
}

/*
 * Sets up phroute/phroute tests
 */
function setupPhroute(Benchmark $benchmark, $numbers, $argNum)
{
    $collector = new RouteCollector();
    $lastStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "{arg$i}";
    }, range(1, $argNum)));

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('{', '}'), '', $str);
        // }

        $lastStr = str_replace(array('{', '}'), '', $str);
        $collector->get($str, 'null_handler');
    }

    $dispatcher = new Dispatcher($collector->getData());

    $benchmark->register(sprintf('phroute/phroute - last route (%s routes)', $numbers),
        function () use ($dispatcher, $lastStr) {
            $dispatcher->dispatch('GET', $lastStr);
        });

    $benchmark->register(sprintf('phroute/phroute - unknown route (%s routes)', $numbers),
        function () use ($dispatcher) {
            try {
                $dispatcher->dispatch('GET', '/not-even-real');
            } catch (HttpRouteNotFoundException $e) {
            }
        });
}

/*
 * Sets up NoahBuscher/Macaw tests
 */
function setupNoahBuscherMacaw(Benchmark $benchmark, $numbers, $argNum)
{
    $lastStr = '';
    $argString = implode('/', array_map(function ($i) {
        return '(:any)';
    }, range(1, $argNum)));

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('(:', ')'), '', $str);
        // }

        $lastStr = str_replace(array('(:', ')'), '', $str);
        Macaw::get($str, function () {
        });
    }

    Macaw::$error_callback = function () {
    };

    $benchmark->register(sprintf('Macaw - last route (%s routes)', $numbers), function () use ($lastStr) {
        $_SERVER['REQUEST_URI'] = $lastStr;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Macaw::dispatch();
    });

    $benchmark->register(sprintf('Macaw - unknown route (%s routes)', $numbers), function () {
        $_SERVER['REQUEST_URI'] = '/not-even-real';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        Macaw::dispatch();
    });
}

/*
 * Sets up noodlehaus/dispatch tests
 */
function setupNoodlehausDispatch(Benchmark $benchmark, $numbers, $argNum)
{
    $lastStr = '';
    $argString = implode('/', array_map(function ($i) {
        return "(:any$i)";
    }, range(1, $argNum)));

    for ($i = 0; $i < $numbers; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        // if (0 === $i) {
        //     $firstStr = str_replace(array('(:', ')'), '', $str);
        // }

        $lastStr = str_replace(array('(:', ')'), '', $str);
        route('GET', $str, function () {
            return response('');
        });
    }

    $benchmark->register(sprintf('noodlehaus/dispatch - last route (%s routes)', $numbers), function () use ($lastStr) {
        $_SERVER['REQUEST_URI'] = $lastStr;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        dispatch();
    });

    $benchmark->register(sprintf('noodlehaus/dispatch - unknown route (%s routes)', $numbers), function () {
        $_SERVER['REQUEST_URI'] = '/not-even-real';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        dispatch();
    });
}
