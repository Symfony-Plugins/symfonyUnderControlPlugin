<?php

require_once(dirname(__FILE__) . '/SymfonyUnderControlOutputInterface.php');

class SymfonyUnderControlTestOutput extends SymfonyUnderControlOutput implements SymfonyUnderControlOutputInterface
{
  private
    $failureCount = 0;

  /**
   * Write the XML to the file specified in the constructor
   *
   */
  public function writeToFile()
  {
    //if (! is_writable($this->path . '/undercontrol.xml'))
    //{
    //  throw new Exception('Path <' . $this->path . '/undercontrol.xml> not writable');
    //}
    $xml = $this->buildXML();
    file_put_contents($this->path . '/undercontrol.xml', $xml);

    return $xml;
  }

  /**
   * Get count Failure test
   *
   * @return int Count Failure test
   */
  public function getFailureCount()
  {
    return $this->failureCount;
  }

  /**
   * Build the XML from the test results
   *
   * @return string xUnit XML
   */
  public function buildXML()
  {
    $this->loadBaseXML();

    $alltests = $this->addTestSuite('All Tests');

    $failure_count = 0;
    $test_count = 0;
    $assertion_count = 0;
    $total_time = 0;

    foreach ( $this->tests as $type => $tests )
    {
      $current_suite = $this->addTestSuite($type, $alltests);

      $type_failure = 0;
      $type_test = 0;
      $type_assertion = 0;
      $type_time = 0;

      foreach ( $tests as $test )
      {
        $test_count ++;
        $type_test ++;

        $asserts = $test->getAsserts();

        $current_case = $this->addTestcase($current_suite, $test->getName());
        $current_case ['file'] = $test->getFilename();
        $current_case ['assertions'] = $test->getNumberOfAssertions();
        $current_case ['time'] = $test->getTimeSpent();

        $assertion_count = $assertion_count + $test->getNumberOfAssertions();
        $type_assertion = $type_assertion + $test->getNumberOfAssertions();
        $total_time = $total_time + $test->getTimeSpent();
        $type_time = $type_time + $test->getTimeSpent();

        foreach ( $asserts as $assert_number => $assert )
        {
          if (! empty($assert_number))
          {
            if (false === $assert ['status'])
            {
              $failure_count ++;
              $type_failure ++;
              $this->addFailure($current_case, $assert ['comment']);
            }
          }
        }
      }

      $current_suite ['tests'] = $type_test;
      $current_suite ['assertions'] = $type_assertion;
      $current_suite ['failures'] = $type_failure;
      $current_suite ['errors'] = 0;
      $current_suite ['time'] = $type_time;

    }

    $alltests ['tests'] = $test_count;
    $alltests ['assertions'] = $assertion_count;
    $alltests ['failures'] = $failure_count;
    $alltests ['errors'] = 0;
    $alltests ['time'] = $total_time;

    $this->failureCount = $failure_count;

    return $this->xml->asXml();
  }

  protected function loadBaseXML()
  {
    $this->xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <testsuites></testsuites>');
  }

  protected function addTestSuite($name, $suite = '')
  {
    if (empty($suite))
    {
      $returnsuite = $this->xml->addChild('testsuite');
    }
    else
    {
      $returnsuite = $suite->addChild('testsuite');
    }
    $returnsuite ['name'] = $name;
    return $returnsuite;
  }

  protected function getTestSuite($name)
  {
    return $this->xml->xpath("//testsuite[@name='" . $name . "']'");
  }

  protected function addTestcase($suite, $name, $value = null)
  {
    $returncase = $suite->addChild('testcase', $value);
    $returncase ['name'] = $name . 'class';
    return $returncase;
  }

  protected function getTestcase($name)
  {
    return $this->xml->xpath("//testcase[@name='" . $name . "']'");
  }

  protected function addFailure($testcase, $failure)
  {
    $failure = $testcase->addChild('failure', $failure);
    $failure ['type'] = 'UnderControlFailure';
    return $failure;
  }
}