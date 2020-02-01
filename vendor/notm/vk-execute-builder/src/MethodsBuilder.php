<?php

namespace SMITExecute\Library;

class MethodsBuilder
{
    private $mainMethod;
    private $subMethod;
    private $params = [];

    /**
     * @return mixed
     */
    public function getMainMethod()
    {
        return $this->mainMethod;
    }

    /**
     * @param mixed $mainMethod
     * @return MethodsBuilder
     */
    public function setMainMethod($mainMethod)
    {
        $this->mainMethod = $mainMethod;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubMethod()
    {
        return $this->subMethod;
    }

    /**
     * @param mixed $subMethod
     * @return MethodsBuilder
     */
    public function setSubMethod($subMethod)
    {
        $this->subMethod = $subMethod;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     * @return MethodsBuilder
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return string $result
     */

    public function build()
    {
        $params = (!empty($this->params)) ?  json_encode($this->params, JSON_UNESCAPED_UNICODE) : "";
        return sprintf("API.%s.%s(%s)", $this->mainMethod, $this->subMethod, $params);
    }

}