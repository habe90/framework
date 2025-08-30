<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use TrueFrame\Routing\Route;

class RouteListCommand extends Command
{
    protected string $signature = 'route:list';
    protected string $description = 'List all registered routes.';

    public function handle(): int
    {
        $this->line("Registered Routes:");
        $this->line("------------------");

        $routes = $this->router()->getRoutes()->all();

        if (empty($routes)) {
            $this->line("No routes defined.");
            return 0;
        }

        $this->line(sprintf("%-10s %-40s %s", "Method", "URI", "Action"));
        $this->line(sprintf("%-10s %-40s %s", "------", "---", "------"));

        foreach ($routes as $method => $routeList) {
            foreach ($routeList as $route) {
                $action = $route->getAction();
                $actionString = '';
                if ($action instanceof \Closure) {
                    $actionString = 'Closure';
                } elseif (is_array($action) && count($action) === 2) {
                    $actionString = (is_string($action[0]) ? $action[0] : get_class($action[0])) . '@' . $action[1];
                } else {
                    $actionString = (string) $action;
                }
                $this->line(sprintf("%-10s %-40s %s", $route->getMethod(), '/' . $route->getUri(), $actionString));
            }
        }

        return 0;
    }
}