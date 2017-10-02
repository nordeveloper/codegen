<?php


namespace carono\codegen;


use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

/**
 * Class ClassGenerator
 *
 * @package carono\codegen
 */
abstract class ClassGenerator
{
    /**
     * External generator
     * @var mixed
     */
    public $generator;
    public $namespace;
    public $extends;
    public $params = [];
    public $className;
    /**
     * @var ClassType
     */
    protected $phpClass;
    /**
     * @var PhpNamespace
     */
    protected $phpNamespace;
    /**
     * @var PhpFile
     */
    protected $phpFile;
    protected $exceptRenderMethods = [
        'render',
        'renderToFile',
        '__construct'
    ];

    /**
     * @return array
     */
    protected function classUses()
    {
        return [];
    }

    /**
     * @param $className
     * @return ClassType
     */
    protected function getPhpClass($className)
    {
        if (!$this->phpClass) {
            $this->phpFile = new PhpFile();
            $namespace = $this->phpFile->addNamespace($this->namespace);
            $this->phpNamespace = $namespace;
            return $this->phpClass = $namespace->addClass($className);
        } else {
            return $this->phpClass;
        }
    }

    /**
     * @return void
     */
    protected function classAfterRender()
    {

    }

    /**
     * @return array
     */
    protected function classGeneratedBy()
    {
        return [
            'This class is generated using the package carono/codegen'
        ];
    }

    /**
     * @param $params
     * @return string
     * @throws \Exception
     */
    public function render($params)
    {
        $className = $this->className;
        if (!$className) {
            throw new \Exception('The class name was not set, update $className parameter');
        }
        $class = $this->getPhpClass($className);
        $this->phpClass->addExtend($this->extends);
        $this->params = $params;
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!in_array($method->name, $this->exceptRenderMethods)) {
                $result = call_user_func([$this, $method->name], $class->addMethod($method->name));
                if ($result === false) {
                    $methods = $class->getMethods();
                    unset($methods[$method->name]);
                    $class->setMethods($methods);
                }
            }
        }
        foreach (array_filter($this->classUses()) as $alias => $namespace) {
            $this->phpNamespace->addUse($namespace, is_numeric($alias) ? null : $alias);
        }
        foreach ($this->phpDocComments() as $comment) {
            $this->phpClass->addComment($comment);
        }
        foreach (array_filter($this->phpDocProperties()) as $property => $value) {
            $this->phpClass->addProperty($property, $value);
        }
        foreach (array_filter($this->classConstants()) as $constant => $value) {
            $this->phpClass->addConstant($constant, $value);
        }
        foreach (array_filter($this->classTraits()) as $trait => $resolutions) {
            $this->phpClass->addTrait(is_numeric($trait) ? $resolutions : $trait, is_numeric($trait) ? [] : $resolutions);
        }
        $this->classAfterRender();
        $generatedBy = $this->classGeneratedBy();
        $this->phpFile->addComment(is_array($generatedBy) ? join("\n", $generatedBy) : $generatedBy);
        return (string)$this->phpFile;
    }

    /**
     * @param $filePath
     * @param $params
     * @return bool|int
     */
    public function renderToFile($filePath, $params)
    {
        $content = $this->render($params);
        return file_put_contents($filePath, $content);
    }

    /**
     * @return array
     */
    protected function classTraits()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function phpDocComments()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function phpDocProperties()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function classConstants()
    {
        return [];
    }
}