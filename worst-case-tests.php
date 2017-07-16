<?php

namespace WorstCaseMatching;

use Aura\Router\RouterContainer;
use Nice\Benchmark\Benchmark;
use Nice\Benchmark\ResultPrinter\MarkdownPrinter;
use inhere\sroute\SRouter;
use inhere\sroute\ORouter;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets up the Worst-case matching benchmark.
 *
 * This benchmark generates a randomly prefixed and suffixed route, in an attempt to thwart
 * any optimization.
 *
 * @param $numIterations
 * @param $numRoutes
 * @param $numArgs
 *
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

    setupSRouter($benchmark, $numRoutes, $numArgs);
    setupORouter($benchmark, $numRoutes, $numArgs);

    //setupORouterCached($benchmark, $numRoutes, $numArgs);
//
//    if (extension_loaded('r3')) {
//        setupR3($benchmark, $numRoutes, $numArgs);
//    } else {
//        echo "R3 extension is not loaded. Skipping initialization for \"Worst-case matching\" test using R3.\n";
//    }

    setupSymfony2($benchmark, $numRoutes, $numArgs);
    setupSymfony2Optimized($benchmark, $numRoutes, $numArgs);
    setupPux($benchmark, $numRoutes, $numArgs);

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
//    $str = $firstStr = $lastStr = '';
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
 * Sets up inhere\sroute\SRouter tests
 */
function setupSRouter(Benchmark $benchmark, $routes, $args)
{
    // $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $argString = implode('/', array_map(function ($i) { return "(:any)$i"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('(:', ')'), '', $str);
        }

        $lastStr = str_replace(array('(:', ')'), '', $str);

        SRouter::map('GET', $str, 'handler' . $i);
    }

    $benchmark->register(sprintf('SRouter - last route (%s routes)', $routes), function () use ($lastStr) {
        $route = SRouter::match($lastStr, 'GET');
    });

    $benchmark->register(sprintf('SRouter - unknown route (%s routes)', $routes), function () {
        $route = SRouter::match('/not-even-real', 'GET');
    });
}

/*
 * Sets up inhere\sroute\ORouter tests
 */
function setupORouter(Benchmark $benchmark, $routes, $args)
{
    $router = new ORouter;
    $str = $firstStr = $lastStr = '';
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $lastStr = str_replace(array('{', '}'), '', $str);

        $router->map('GET', $str, 'handler' . $i);
    }

    $benchmark->register(sprintf('ORouter - last route (%s routes)', $routes), function () use ($router, $lastStr) {
        $route = $router->match($lastStr, 'GET');
    });

    $benchmark->register(sprintf('ORouter - unknown route (%s routes)', $routes), function () use ($router) {
        $route = $router->match('/not-even-real', 'GET');
    });
}

/*
 * Sets up inhere\sroute\ORouter tests
 */
function setupORouterCached(Benchmark $benchmark, $routes, $args)
{
    $router = new ORouter([
        'cacheFile' => __DIR__ . '/files/ORouter-worst-case-cache.php',
        'cacheEnable' => true,
    ]);
    $str = $firstStr = $lastStr = '';
    $argString = implode('/', array_map(function ($i) { return "{any}$i"; }, range(1, $args)));

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $lastStr = str_replace(array('{', '}'), '', $str);

        $router->map('GET', $str, 'handler' . $i);
    }

    $benchmark->register(sprintf('ORouter(cached) - last route (%s routes)', $routes), function () use ($router, $lastStr) {
        $route = $router->match($lastStr, 'GET');
    });

    $benchmark->register(sprintf('ORouter(cached) - unknown route (%s routes)', $routes), function () use ($router) {
        $route = $router->match('/not-even-real', 'GET');
    });
}


/*
 * Sets up FastRoute tests
 */
function setupFastRoute(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = \FastRoute\simpleDispatcher(function ($router) use ($routes, $argString, &$lastStr) {
            for ($i = 0; $i < $routes; $i++) {
                list ($pre, $post) = getRandomParts();
                $str = '/' . $pre . '/' . $argString . '/' . $post;

                if (0 === $i) {
                    $firstStr = str_replace(array('{', '}'), '', $str);
                }
                $lastStr = str_replace(array('{', '}'), '', $str);

                $router->addRoute('GET', $str, 'handler' . $i);
            }
        });

    $benchmark->register(sprintf('FastRoute - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->dispatch('GET', $lastStr);
        });

    $benchmark->register(sprintf('FastRoute - unknown route (%s routes)', $routes), function () use ($router) {
            $route = $router->dispatch('GET', '/not-even-real');
        });
}


/*
 * Sets up FastRoute(use cache) tests
 */
function setupFastRouteCached(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = \FastRoute\cachedDispatcher(function ($router) use ($routes, $argString, &$lastStr) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;

            if (0 === $i) {
                $firstStr = str_replace(array('{', '}'), '', $str);
            }
            $lastStr = str_replace(array('{', '}'), '', $str);

            $router->addRoute('GET', $str, 'handler' . $i);
        }
    }, [
        'cacheFile' => __DIR__ . '/files/worst-route.cache', /* required */
        'cacheDisabled' => false,     /* optional, enabled by default */
    ]);

    $benchmark->register(sprintf('FastRoute(cached) - last route (%s routes)', $routes), function () use ($router, $lastStr) {
        $route = $router->dispatch('GET', $lastStr);
    });

    $benchmark->register(sprintf('FastRoute(cached) - unknown route (%s routes)', $routes), function () use ($router) {
        $route = $router->dispatch('GET', '/not-even-real');
    });
}

/*
 * Sets up Pux tests
 */
function setupPux(Benchmark $benchmark, $routes, $args)
{
    $name = extension_loaded('pux') ? 'Pux ext' : 'Pux PHP';

    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = new \Pux\Mux;
    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);

        $router->add($str, 'handler' . $i);
    }

    $benchmark->register(sprintf('%s - last route (%s routes)', $name, $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr);
        });

    $benchmark->register(sprintf('%s - unknown route (%s routes)', $name, $routes), function () use ($router) {
            $route = $router->match('/not-even-real');
        });
}

/*
 * Sets up Symfony 2 tests
 */
function setupSymfony2(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $firstStr = $lastStr = '';
    $sfRoutes = new RouteCollection();
    $router = new UrlMatcher($sfRoutes, new RequestContext());
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new \Symfony\Component\Routing\Route($str, array('controller' => 'handler' . $i)));
    }

    $benchmark->register(sprintf('Symfony2 - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr);
        });

    $benchmark->register(sprintf('Symfony2 - unknown route (%s routes)', $routes), function () use ($router) {
            try {
                $route = $router->match('/not-even-real');
            } catch (ResourceNotFoundException $e) { }
        });
}

/*
 * Sets up Symfony2 optimized tests
 */
function setupSymfony2Optimized(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $sfRoutes = new RouteCollection();
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new \Symfony\Component\Routing\Route($str, array('controller' => 'handler' . $i)));
    }
    $dumper = new PhpMatcherDumper($sfRoutes);
    file_put_contents(__DIR__ . '/files/worst-case-sf2.php', $dumper->dump(array(
                'class' => 'WorstCaseSf2UrlMatcher'
            )));
    require_once __DIR__ . '/files/worst-case-sf2.php';

    $router = new \WorstCaseSf2UrlMatcher(new RequestContext());

    $benchmark->register(sprintf('Symfony2 Dumped - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr);
        });

    $benchmark->register(sprintf('Symfony2 Dumped - unknown route (%s routes)', $routes), function () use ($router) {
            try {
                $route = $router->match('/not-even-real');
            } catch (ResourceNotFoundException $e) { }
        });
}

/*
 * Sets up Aura v3 tests(todo uncomplete ...)
 *
 * https://github.com/auraphp/Aura.Router
 */
function setupAura3(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $lastStr = '';
    $routerContainer = new RouterContainer();
    $router = $routerContainer->getMap();

    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $router->get($str, $str,array(
            'controller' => 'handler' . $i
        ));
    }

    // get the route matcher from the container ...
    $matcher = $routerContainer->getMatcher();

    $benchmark->register(sprintf('Aura v2 - last route (%s routes)', $routes), function () use ($matcher, $lastStr) {
            $route = $matcher->match($lastStr);
        });

    $benchmark->register(sprintf('Aura v2 - unknown route (%s routes)', $routes), function () use ($matcher) {
            $route = $matcher->match('/not-even-real');
        });
}
