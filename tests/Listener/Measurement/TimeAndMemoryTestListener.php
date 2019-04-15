<?php

namespace PhpunitMemoryAndTimeUsageListener\Listener\Measurement;

use Exception;
use Throwable;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\Warning;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\AssertionFailedError;
use PhpunitMemoryAndTimeUsageListener\Domain\Measurement\TestMeasurement;
use PhpunitMemoryAndTimeUsageListener\Domain\Measurement\TimeMeasurement;
use PhpunitMemoryAndTimeUsageListener\Domain\Measurement\MemoryMeasurement;

class TimeAndMemoryTestListener implements TestListener
{
    /** @var int  */
    protected $testSuitesRunning = 0;

    /** @var TestMeasurement[] */
    protected $testMeasurementCollection;

    /** @var bool  */
    protected $showOnlyIfEdgeIsExceeded = false;

    /**
     * Time in milliseconds we consider a test has a need to see if a refactor is needed
     * @var int
     */
    protected $executionTimeEdge = 100;

    /**
     * Memory bytes usage we consider a test has a need to see if a refactor is needed
     * @var int
     */
    protected $memoryUsageEdge = 1024;

    /**
     * Memory bytes usage we consider a test has a need to see if a refactor is needed
     * @var int
     */
    protected $memoryPeakDifferenceEdge = 1024;

    /**
     * @var TimeMeasurement
     */
    protected $executionTime;
    protected $memoryUsage;
    protected $memoryPeakIncrease;

    public function __construct($configurationOptions = array())
    {
        $this->setConfigurationOptions($configurationOptions);
    }

    /**
     * @param Test $test
     */
    public function startTest(Test $test) : void
    {
        $this->memoryUsage = memory_get_usage(true);
        $this->memoryPeakIncrease = memory_get_peak_usage(true);
    }

    /**
     * @param Test $test
     * @param $time
     */
    public function endTest(Test $test, $time) : void
    {
        $this->executionTime = new TimeMeasurement($time);
        $this->memoryUsage = memory_get_usage(true) - $this->memoryUsage;
        // $this->memoryUsage = memory_get_usage(true);
        $this->memoryPeakIncrease = memory_get_peak_usage(true) - $this->memoryPeakIncrease;
        // $this->memoryPeakIncrease = memory_get_peak_usage(true);

        if ($this->haveToSaveTestMeasurement($time)) {
            $this->testMeasurementCollection[] = new TestMeasurement(
                $test->getName(),
                get_class($test),
                $this->executionTime,
                (new MemoryMeasurement($this->memoryUsage)),
                (new MemoryMeasurement($this->memoryPeakIncrease))
            );
        }
    }

    /**
     * @param Test $test
     * @param Warning              $warning
     * @param float                   $time
     */
    public function addWarning(Test $test, Warning $warning, float $time) : void
    {
    }

    /**
     * @param Test $test
     * @param Throwable              $t
     * @param float                   $time
     */
    public function addError(Test $test, Throwable $t, float $time) : void
    {
    }

    /**
     * @param Test                 $test
     * @param AssertionFailedError $exception
     * @param float                                   $time
     */
    public function addFailure(Test $test, AssertionFailedError $exception, $time) : void
    {
    }

    /**
     * @param Test $test
     * @param Throwable              $t
     * @param float                   $time
     */
    public function addIncompleteTest(Test $test, Throwable $t, float $time) : void
    {
    }

    /**
     * @param Test $test
     * @param Throwable              $t
     * @param float                   $time
     */
    public function addRiskyTest(Test $test, Throwable $t, float $time) : void
    {
    }

    /**
     * @param Test $test
     * @param Throwable              $t
     * @param float                   $time
     */
    public function addSkippedTest(Test $test, Throwable $t, float $time) : void
    {
    }

    /**
     * @param TestSuite $suite
     */
    public function startTestSuite(TestSuite $suite) : void
    {
        $this->testSuitesRunning++;
    }

    /**
     * @param TestSuite $suite
     */
    public function endTestSuite(TestSuite $suite) : void
    {
        $this->testSuitesRunning--;

        if ((0 === $this->testSuitesRunning) && (0 < count($this->testMeasurementCollection))) {
            echo PHP_EOL . PHP_EOL . "Time & Memory measurement results: " . PHP_EOL;
            $i = 1;
            foreach ($this->testMeasurementCollection as $testMeasurement) {
                echo PHP_EOL . $i . " -> " . $testMeasurement->measuredInformationMessage();
                $i++;
            }
        }
    }

    /**
     * @return bool
     */
    protected function haveToSaveTestMeasurement()
    {
        return ((false === $this->showOnlyIfEdgeIsExceeded)
            || ((true === $this->showOnlyIfEdgeIsExceeded)
                && ($this->isAPotentialCriticalTimeUsage()
                || $this->isAPotentialCriticalMemoryUsage()
                || $this->isAPotentialCriticalMemoryPeakUsage()
                )
            )
        );
    }

    /**
     * Check if test execution time is critical so we need to check it out
     *
     * @return bool
     */
    protected function isAPotentialCriticalTimeUsage()
    {
        return $this->checkEdgeIsOverTaken($this->executionTime->timeInMilliseconds(), $this->executionTimeEdge);
    }

    /**
     * Check if test execution memory usage is critical so we need to check it out
     *
     * @return bool
     */
    protected function isAPotentialCriticalMemoryUsage()
    {
        return $this->checkEdgeIsOverTaken($this->memoryUsage, $this->memoryUsageEdge);
    }

    /**
     * Check if test execution memory peak usage is critical so we need to check it out
     *
     * @return bool
     */
    protected function isAPotentialCriticalMemoryPeakUsage()
    {
        return $this->checkEdgeIsOverTaken($this->memoryPeakIncrease, $this->memoryPeakDifferenceEdge);
    }

    /**
     * @param $value
     * @param $edgeValue
     * @return bool
     */
    protected function checkEdgeIsOverTaken($value, $edgeValue)
    {
        return ($value >= $edgeValue);
    }

    /**
     * @param $configurationOptions
     */
    protected function setConfigurationOptions($configurationOptions)
    {
        if (isset($configurationOptions['showOnlyIfEdgeIsExceeded'])) {
            $this->showOnlyIfEdgeIsExceeded = $configurationOptions['showOnlyIfEdgeIsExceeded'];
        }

        if (isset($configurationOptions['executionTimeEdge'])) {
            $this->executionTimeEdge = $configurationOptions['executionTimeEdge'];
        }

        if (isset($configurationOptions['memoryUsageEdge'])) {
            $this->memoryUsageEdge = $configurationOptions['memoryUsageEdge'];
        }

        if (isset($configurationOptions['memoryPeakDifferenceEdge'])) {
            $this->memoryPeakDifferenceEdge = $configurationOptions['memoryPeakDifferenceEdge'];
        }
    }
}
