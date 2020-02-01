<?php


namespace SMITExecute\Library;

class ExecuteRequest
{

    /**
     * @var MethodsBuilder[]
     */

    private $queries;


    /**
     * @var array $jsExecute
     */

    private $jsExecute = ["var result = {};"];


    /**
     * @param MethodsBuilder $query
     * @return $this
     */

    public function add(MethodsBuilder $query)
    {
        $this->queries[] = $query;
        return $this;
    }

    /**
     * @return MethodsBuilder
     */

    public function create()
    {
        return new MethodsBuilder();
    }


    /**
     * @return array
     */

    public function convertToJS()
    {
        $defined_keys = [];

        foreach ($this->queries as $query) {
            $key = sprintf("%s_%s", $query->getMainMethod(), $query->getSubMethod());

            if (!in_array($key, $defined_keys)) {
                $this->jsExecute[] = sprintf("result.%s = [];", $key);
                $defined_keys[] = $key;
            }

            $this->jsExecute[] = sprintf("result.%s.push(%s);", $key, $query->build());
        }

        $this->jsExecute[] = "return result;";

        return $this->jsExecute;
    }

}