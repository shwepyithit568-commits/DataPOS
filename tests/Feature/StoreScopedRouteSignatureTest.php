<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards against the Laravel 11/12 positional controller-resolution bug.
 *
 * Controller method params under /store/{store_slug}/... are resolved
 * positionally (array_splice by method index), and implicit model binding
 * places the substituted model at the ROUTE param's position. So any method
 * that consumes a later route param ({sale}, {order}, {line}, {shift}, …)
 * MUST declare `string $store_slug` BEFORE it — otherwise the store slug
 * string lands in the model param's slot and every request 500s with
 * "must be of type X, string given" (e.g. resume/void/post, fixed 2026-08-17).
 */
class StoreScopedRouteSignatureTest extends TestCase
{
    public function test_every_store_scoped_controller_consuming_a_route_param_declares_store_slug_first(): void
    {
        $violations = [];

        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if (! str_starts_with($route->uri(), 'store/{store_slug}')) {
                continue;
            }

            $action = $route->getAction('uses');
            if (! is_string($action) || ! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);
            if (! method_exists($class, $method)) {
                continue;
            }

            $paramNames = array_map(
                fn ($p) => $p->getName(),
                (new ReflectionMethod($class, $method))->getParameters()
            );

            // Which non-store_slug route params does this method consume by name?
            $consumed = array_values(array_filter(
                $route->parameterNames(),
                fn ($p) => $p !== 'store_slug'
                    && (in_array($p, $paramNames, true) || in_array(str_replace('_', '', $p), $paramNames, true))
            ));

            if ($consumed === []) {
                continue; // params read via $request->route() — no signature slot, safe
            }

            $slugPos = array_search('store_slug', $paramNames, true);
            $firstConsumedPos = min(array_map(
                fn ($p) => array_search($p, $paramNames, true) !== false
                    ? array_search($p, $paramNames, true)
                    : array_search(str_replace('_', '', $p), $paramNames, true),
                $consumed
            ));

            if ($slugPos === false || $slugPos > $firstConsumedPos) {
                $violations[] = sprintf(
                    '%s → %s@%s consumes [%s] but its signature is (%s) — add `string $store_slug` before the param',
                    $route->uri(),
                    class_basename($class),
                    $method,
                    implode(', ', $consumed),
                    implode(', ', $paramNames)
                );
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
