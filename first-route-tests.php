<?php

namespace FirstRouteMatching;

use Aura\Router\RouterContainer;
use Nice\Benchmark\Benchmark;
use Nice\Benchmark\ResultPrinter\MarkdownPrinter;
use inhere\sroute\SRouter;
use inhere\sroute\ORouter;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets up the First-route matching benchmark.
 *
 * This benchmark tests how quickly each router can match the first route
 *
 * @param int $numIterations 测试多少次
 * @param int $numRoutes 添加多少个路由
 * @param int $numArgs 参数节点长度
 *
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

    // setupAura2($benchmark, $numRoutes, $numArgs);
    // setupAura3($benchmark, $numRoutes, $numArgs);
    setupFastRoute($benchmark, $numRoutes, $numArgs);
    setupFastRouteCached($benchmark, $numRoutes, $numArgs);

    setupSRouter($benchmark, $numRoutes, $numArgs);
    setupORouter($benchmark, $numRoutes, $numArgs);

//    if (extension_loaded('r3')) {
//        setupR3($benchmark, $numRoutes, $numArgs);
//    } else {
//        echo "R3 extension is not loaded. Skipping initialization for \"First route matching\" test using R3.\n";
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


/**
 * Sets up R3 tests
 */
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
//    $benchmark->register(sprintf('php-r3 - first route(%d)', $routes), function () use ($router, $firstStr) {
//            $data = r3_tree_match($router, $firstStr);
//        });
//}

/**
 * Sets up inhere\sroute\SRouter tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupSRouter(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $firstStr = $lastStr = '';

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }

        $lastStr = str_replace(array('{', '}'), '', $str);

        SRouter::map('GET', $str, 'handler' . $i);
    }

    $benchmark->register(sprintf('SRouter - first route(%d)', $routes), function () use ($firstStr) {
        $route = SRouter::match($firstStr, 'GET');
    });
}

/**
 * Sets up inhere\sroute\ORouter tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
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

    $benchmark->register(sprintf('ORouter - first route(%d)', $routes), function () use ($router, $firstStr) {
        $route = $router->match($firstStr, 'GET');
        //var_dump($firstStr, $route);die;
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
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = \FastRoute\simpleDispatcher(function ($router) use ($routes, $argString, &$lastStr, &$firstStr) {
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

    $benchmark->register(sprintf('FastRoute - first route(%d)', $routes), function () use ($router, $firstStr) {
            $route = $router->dispatch('GET', $firstStr);
        });
}

/**
 * Sets up FastRoute(cached) tests
 */
function setupFastRouteCached(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = \FastRoute\cachedDispatcher(function ($router) use ($routes, $argString, &$lastStr, &$firstStr) {
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
        'cacheFile' => __DIR__ . '/files/first-route.cache', /* required */
        'cacheDisabled' => false,     /* optional, enabled by default */
    ]);

    $benchmark->register(sprintf('FastRoute(cached) - first route(%d)', $routes), function () use ($router, $firstStr) {
        $route = $router->dispatch('GET', $firstStr);
    });
}

/**
 * Sets up Pux tests
 */
function setupPux(Benchmark $benchmark, $routes, $args)
{
    $name = extension_loaded('pux') ? 'Pux ext' : 'Pux PHP';
    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $args)));
    $firstStr = $lastStr = '';
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

    $benchmark->register(sprintf('%s - first route(%d)', $name, $routes), function () use ($router, $firstStr) {
            $route = $router->match($firstStr);
        });
}

/**
 * Sets up Symfony 2 tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupSymfony2(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $sfRoutes = new RouteCollection();
    $router = new UrlMatcher($sfRoutes, new RequestContext());
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new Route($str, array('controller' => 'handler' . $i)));
    }

    $benchmark->register('Symfony2 - first route', function () use ($router, $firstStr) {
            $route = $router->match($firstStr);
        });
}

/**
 * Sets up Symfony2 optimized tests
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
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

        $sfRoutes->add($str, new Route($str, array('controller' => 'handler' . $i)));
    }
    $dumper = new PhpMatcherDumper($sfRoutes);
    file_put_contents(__DIR__ . '/files/first-route-sf2.php', $dumper->dump(array(
                'class' => 'FirstRouteSf2UrlMatcher'
            )));
    require_once __DIR__ . '/files/first-route-sf2.php';

    $router = new \FirstRouteSf2UrlMatcher(new RequestContext());

    $benchmark->register('Symfony2 Dumped - first route', function () use ($router, $firstStr) {
            $route = $router->match($firstStr);
        });
}

/**
 * Sets up Aura v3 tests(todo uncomplete ...)
 *
 * https://github.com/auraphp/Aura.Router
 * @param Benchmark $benchmark
 * @param $routes
 * @param $args
 */
function setupAura3(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $lastStr = $firstStr ='';
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

    $benchmark->register('Aura v2 - first route', function () use ($matcher, $firstStr) {
        $route = $matcher->match($firstStr);
    });
}
