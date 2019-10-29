<?php
declare(strict_types=1);

namespace ExtendsFramework\Router\Controller;

use ExtendsFramework\Http\Request\RequestInterface;
use ExtendsFramework\Http\Response\ResponseInterface;
use ExtendsFramework\Router\Controller\Exception\ActionNotFound;
use ExtendsFramework\Router\Controller\Exception\ParameterNotFound;
use ExtendsFramework\Router\Route\RouteMatchInterface;
use ReflectionException;
use ReflectionMethod;

abstract class AbstractController implements ControllerInterface
{
    /**
     * String to append to the action.
     *
     * @var string
     */
    private $postfix = 'Action';

    /**
     * Request.
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Route match.
     *
     * @var RouteMatchInterface
     */
    private $routeMatch;

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function execute(RequestInterface $request, RouteMatchInterface $routeMatch): ResponseInterface
    {
        $this->request = $request;
        $this->routeMatch = $routeMatch;

        $method = $this->getMethod($routeMatch);
        $arguments = $this->getArguments($method, $routeMatch);

        return $method->invokeArgs($this, $arguments);
    }

    /**
     * Get callable method for $action.
     *
     * The object property $postfix will be append to $action.
     *
     * @param RouteMatchInterface $routeMatch
     * @return ReflectionMethod
     * @throws ControllerException
     * @throws ReflectionException
     */
    private function getMethod(RouteMatchInterface $routeMatch): ReflectionMethod
    {
        $action = $this->getAction($routeMatch);

        return new ReflectionMethod($this, $action . $this->getPostfix());
    }

    /**
     * Normalize action string.
     *
     * @param RouteMatchInterface $routeMatch
     * @return string
     * @throws ControllerException
     */
    private function getAction(RouteMatchInterface $routeMatch): string
    {
        $parameters = $routeMatch->getParameters();
        if (!array_key_exists('action', $parameters)) {
            throw new ActionNotFound();
        }

        return $this->normalizeAction($parameters['action']);
    }

    /**
     * Get $method argument values from $routeMatch.
     *
     * @param ReflectionMethod    $method
     * @param RouteMatchInterface $routeMatch
     * @return array
     * @throws ParameterNotFound
     * @throws ReflectionException
     */
    private function getArguments(ReflectionMethod $method, RouteMatchInterface $routeMatch): array
    {
        $parameters = $routeMatch->getParameters();

        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (!array_key_exists($name, $parameters)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $arguments[] = null;
                } else {
                    throw new ParameterNotFound($name);
                }
            } else {
                $arguments[] = $parameters[$name];
            }
        }

        return $arguments;
    }

    /**
     * Normalize action string.
     *
     * @param string $action
     * @return string
     */
    private function normalizeAction(string $action): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-', '.'], ' ', strtolower($action)))));
    }

    /**
     * Get request.
     *
     * @return RequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Get route match.
     *
     * @return RouteMatchInterface
     */
    protected function getRouteMatch(): RouteMatchInterface
    {
        return $this->routeMatch;
    }

    /**
     * Get Postfix.
     *
     * @return string
     */
    private function getPostfix(): string
    {
        return $this->postfix;
    }
}
