<?php

namespace App\Components;

/**
 * Class ShellScript
 * @package App\Commands
 */
class ShellScript
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @param string $shell
     * @return ShellScript
     */
    public function addScript(string $shell)
    {
        $this->parts[] = $shell;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return ShellScript
     */
    public function setParameter(string $name, string $value)
    {
        return $this->setParameters([$name => $value]);
    }

    /**
     * @param array $parameters
     * @return ShellScript
     */
    public function setParameters(array $parameters)
    {
        $this->parameters += $parameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return $this->resolveParameters(implode(' && ', $this->parts));
    }

    /**
     * @return bool|string
     */
    public function runScript()
    {
        return system($this->getScript());
    }

    /**
     * @param string $script
     * @return string
     */
    private function resolveParameters(string $script)
    {
        if (!empty($this->parameters)) {
            foreach ($this->parameters as $key => $value) {
                $script = str_replace(':' . $key, '"' . $value . '"', $script);
            }
        }

        return $script;
    }
}