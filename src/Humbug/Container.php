<?php
/**
 * Humbug
 *
 * @category   Humbug
 * @package    Humbug
 * @copyright  Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    https://github.com/padraic/humbug/blob/master/LICENSE New BSD License
 */

namespace Humbug;

use Humbug\Adapter;
use Humbug\Runkit;
use Humbug\Generator;
use Humbug\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Container
{

    protected $input;

    protected $output;

    protected $cache;

    protected $adapter;

    protected $adapterOptions = [];

    protected $runkit;

    protected $mutables = [];

    protected $generator;

    protected $bootstrap = '';

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->setAdapterOptionsFromString($this->input->getOption('options'));
    }

    /**
     * Retrieve any of the original input options
     *
     * @param string $option
     * @return string
     */
    public function get($option)
    {
        return $this->input->getOption($option);
    }

    /**
     * Set the cache directory of the project being mutated
     *
     * @param string $dir
     */
    public function setCacheDirectory($dir)
    {
        $dir = rtrim($dir, ' \\/');
        if (!is_dir($dir) || !is_readable($dir)) {
            throw new InvalidArgumentException('Invalid cache directory: "'.$dir.'"');
        }
        $this->cache = $dir;
        return $this;
    }

    /**
     * Get the cache directory of the project being mutated
     *
     * @return string
     */
    public function getCacheDirectory()
    {
        if (is_null($this->cache)) {
            return sys_get_temp_dir();
        }
        return $this->cache;
    }

    /**
     * Options to pass to adapter's underlying command
     *
     * @param string $optionString
     */
    public function setAdapterOptionsFromString($optionString)
    {
        $this->adapterOptions = array_merge(
            $this->adapterOptions,
            explode(' ', $optionString)
        );
        return $this;
    }

    /**
     * Set many options for adapter's underlying cli command
     * @param array|string $options Array or serialized array of options
     * @return self
     */
    public function setAdapterOptions($options)
    {
        if (!is_array($options)) {
            $options = unserialize($options);
        }
        foreach ($options as $value) {
            $this->setAdapterOption($value);
        }
        return $this;
    }

    /**
     * Get a space delimited string of testing tool options
     *
     * @return string
     */
    public function getAdapterOptions()
    {
        return $this->adapterOptions;
    }

    /**
     * Get a test framework adapter. Creates a new one based on the configured
     * adapter name passed on the CLI if not already set.
     *
     * @return \Humbug\Adapter\AdapterAbstract
     */
    public function getAdapter()
    {
        if (is_null($this->adapter)) {
            $name = ucfirst(strtolower($this->get('adapter')));
            $class = '\\Humbug\\Adapter\\' . $name;
            $this->adapter = new $class;
        }
        return $this->adapter;
    }

    /**
     * Set a test framework adapter.
     *
     * @param \Humbug\Adapter\AdapterAbstract $adapter
     */
    public function setAdapter(Adapter\AdapterAbstract $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Set a custom runkit instance.
     *
     * @param \Humbug\Utility\Runkit $runkit
     */
    public function setRunkit(Runkit $runkit)
    {
        $this->runkit = $runkit;
        return $this;
    }

    /**
     * Creates and returns a new instance of \Humbug\Runkit if not previously
     * loaded
     *
     * @return \Humbug\Runkit
     */
    public function getRunkit()
    {
        if (is_null($this->runkit)) {
            $this->runkit = new Runkit;
        }
        return $this->runkit;
    }

    /**
     * Generate Mutants!
     *
     * @return array
     */
    public function getMutables()
    {
        if (empty($this->mutables)) {
            $generator = $this->getGenerator();
            $generator->generate();
            $this->mutables = $generator->getMutables();
        }
        return $this->mutables;
    }

    /**
     * Set a specific Generator of mutations (stuck with a subclass).
     * TODO Add interface
     *
     * @param \Humbug\Generator
     */
    public function setGenerator(Generator $generator)
    {
        $this->generator = $generator;
        $this->generator->setSourceDirectory($this->get('srcdir'));
        return $this;
    }

    /**
     * Get a specific Generator of mutations.
     *
     * @return \Humbug\Generator
     */
    public function getGenerator()
    {
        if (!isset($this->_generator)) {
            $this->generator = new Generator;
            $this->generator->setSourceDirectory($this->get('srcdir'));
        }
        return $this->generator;
    }

    /**
     * Routed through Console Input class
     */
    
    public function getTimeout()
    {
        return $this->get('timeout');
    }

    public function getAdapterConstraints()
    {
        return $this->get('constraints');
    }

    public function getSourceDirectory()
    {
        return rtrim($this->get('srcdir'), ' \\/');
    }

    public function getTestDirectory()
    {
        return rtrim($this->get('testdir'), ' \\/');
    }

    public function getBaseDirectory()
    {
        return rtrim($this->get('basedir'), ' \\/');
    }

    public function getDetailCaptures()
    {
        return (boolean) $this->get('detail');
    }

    public function setBootstrap($bootstrap)
    {
        $this->bootstrap = realpath($bootstrap);
    }

    public function getBootstrap() {
        return $this->bootstrap;
    }

}