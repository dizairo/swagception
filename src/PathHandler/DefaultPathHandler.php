<?php
namespace Swagception\PathHandler;

class DefaultPathHandler implements HandlesPath
{
    /**
     * @var \Swagception\Container\ContainsInstances
     */
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function convertPath($path)
    {
        if (empty($this->container->getSchema()) || !isset($this->container->getSchema()->paths->$path) || !isset($this->container->getSchema()->paths->$path->get) || !isset($this->container->getSchema()->paths->$path->get->parameters)) {
            //Could not find a get request at this path.
            return $path;
        }

        //Check enum and x-example
        $paramValues = array();
        foreach ($this->container->getSchema()->paths->$path->get->parameters as $param) {
            if ($param->in !== 'path') {
                //We're only parsing path parameters in this function.
                continue;
            }

            if (isset($paramValues[$param->name])) {
                throw new \Exception(sprintf('Parameter %1$s has been defined multiple times in path %2$s', $param->name, $path));
            }

            if (isset($param->{'x-example'})) {
                //Use the example, if present.
                $paramValues[$param->name] = $param->{'x-example'};
            } elseif (isset($param->enum)) {
                //Pick a random enum, if present.
                $paramValues[$param->name] = $param->enum[mt_rand(0, count($param->enum) - 1)];
            } else {
                throw new \Exception(sprintf('Parameter %1$s in path %2$s does not have an x-example or enum defined.', $param->name, $path));
            }
        }

        $parsedPath = $path;
        foreach ($paramValues as $param => $value) {
            $parsedPath = str_replace('{' . $param . '}', $value, $parsedPath);
        }

        return $parsedPath;
    }
}
