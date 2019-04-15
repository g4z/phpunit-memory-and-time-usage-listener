<?php

namespace PhpunitMemoryAndTimeUsageListener\Domain\Measurement;

class TestMeasurement
{
    /** @var  string */
    private $testName;
    /** @var  string */
    private $testFile;
    /** @var TimeMeasurement  */
    private $timeUsage;
    /** @var MemoryMeasurement  */
    private $memoryUsage;
    /** @var MemoryMeasurement  */
    private $memoryPeakDifference;

    public function __construct(
        $name,
        $file,
        TimeMeasurement $timeUsage,
        MemoryMeasurement $memoryUsage,
        MemoryMeasurement $memoryPeakUsage
    ) {
        $this->testName = $name;
        $this->testFile = $file;
        $this->timeUsage = $timeUsage;
        $this->memoryUsage = $memoryUsage;
        $this->memoryPeakDifference = $memoryPeakUsage;
    }

    public function measuredInformationMessage()
    {
        // return $this->testName . " in file " . $this->testFile
        // . " measurements: " . $this->timeUsage->timeInMilliseconds() . " milliseconds, "
        // . $this->memoryUsage->memoryInKiloBytes() . "Kb memory usage, "
        // . $this->memoryPeakDifference->memoryInKiloBytes() . "Kb memory peak difference";

        return sprintf(
            "t_ms( %6.1f ) mem_mb( %6.1f ) peak_mb( %6.1f ) %s",
            $this->timeUsage->timeInMilliseconds(),
            $this->memoryUsage->memoryInMegaBytes(),
            $this->memoryPeakDifference->memoryInMegaBytes(),
            $this->testName
        );
    }
}