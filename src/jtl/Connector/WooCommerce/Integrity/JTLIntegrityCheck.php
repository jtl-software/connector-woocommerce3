<?php

namespace jtl\Connector\WooCommerce\Integrity;

use Jtl\Connector\Integrity\Models\Test\Result;
use Jtl\Connector\Integrity\Models\Test\TestInterface;
use jtl\Connector\WooCommerce\Integrity\JTLIntegrityCheckResponse as Response;
use Jtl\Connector\Integrity\Models\Test\AbstractTest;
use Jtl\Connector\Integrity\Models\Test\TestCollection;
use Jtl\Connector\Integrity\Shops\Server\ServerTestLoader;


final class JTLIntegrityCheck
{
    /**
     * @var TestCollection
     */
    protected $tests;
    
    protected static $instance;
    
    protected function __construct()
    {
        $this->tests = new TestCollection();
    }
    
    private function __clone()
    {
    }
    
    /**
     * @return JTLIntegrityCheck
     */
    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * @param AbstractTest $test
     * @return JTLIntegrityCheck
     */
    public function registerTest(AbstractTest $test)
    {
        $this->tests->add($test);
        
        return $this;
    }
    
    /**
     * @param TestInterface[]
     * @return JTLIntegrityCheck
     * @throws \InvalidArgumentException
     */
    public function registerTests(array $tests)
    {
        $this->tests = new TestCollection();
        foreach ($tests as $test) {
            if (!($test instanceof AbstractTest)) {
                throw new \InvalidArgumentException(sprintf(
                    'Some element is not an instance of %s',
                    AbstractTest::class
                ));
            }
            
            $this->tests->add($test);
        }
        
        return $this;
    }
    
    public function run()
    {
        $this->tests = (new JTLIntegrityCheckTestLoader())->getTests();
        $this->tests->merge((new ServerTestLoader())->getTests());
        
        $response = new Response();
        
        /** @var AbstractTest $test */
        foreach ($this->tests as $test) {
            $test->run();
            $results = $test->getResults();
            $response->setResults($results);
        }
        
        $errors = [];
        /** @var AbstractTest $test */
        foreach ($this->tests->getItems() as $test) {
            $resultItems = $test->getResults()->getItems();
            /** @var Result $resultItem */
            foreach ($resultItems as $resultItem) {
                if ($resultItem->hasError()) {
                    $error = $resultItem->getError();
                    $errors[] = [
                        'name'     => $resultItem->getName(),
                        'message'  => $error->getMessage(),
                        'solution' => $error->getSolution(),
                    ];
                }
            }
        }
        
        return $errors;
    }
}
