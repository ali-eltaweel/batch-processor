<?php

namespace BatchProcessor;

use Closure, Generator, InvalidArgumentException;

use Symfony\Component\Process\Process;

/**
 * BatchProcessor class to handle concurrent process execution.
 * 
 * @api
 * @final
 * @since 1.0.0
 * @version 1.0.0
 * @package BatchProcessor
 * @author Ali M. Kamel <ali.kamel.dev@gmail.com>
 */
final class BatchProcessor {

    /**
     * Holding the running processes.
     * 
     * @internal
     * @since 1.0.0
     * 
     * @var array<array{ id: string, process: Process }>
     */
    private array $runningProcesses = [];

    /**
     * Creates a new BatchProcessor instance.
     * 
     * @api
     * @final
     * @since 1.0.0
     * @version 1.0.0
     * 
     * @param array|Generator $processesData
     * @param Closure(mixed $id, Process $process): void $onProcessCompletion
     * @param Closure(array $data): Process $processCreator
     */
    public final function __construct(
        
        private array|Generator $processesData,
        private int             $maxConcurrentProcesses,
        private ?Closure        $onProcessCompletion = null,
        private int             $reliefSleepMicros   = 100000,
        private ?Closure        $processCreator      = null
    ) {}

    /**
     * Starts the batch processing of the provided processes data.
     * 
     * @api
     * @final
     * @since 1.0.0
     * @version 1.0.0
     * 
     * @throws InvalidArgumentException
     * @return void
     */
    public final function start(): void {

        foreach ($this->processesData as $processData) {

            $this->wait($this->maxConcurrentProcesses - 1);

            if (!is_array($processData)) {

                throw new InvalidArgumentException('Process data must be an array.');
            }

            $process = $this->createProcess($processData);

            $this->runningProcesses[] = [ 'id' => $id = $processData['id'] ?? null, 'process' => $process ];
            $process->start();

            $this->checkForCompletedProcesses();
        }

        $this->wait(0);
    }

    /**
     * Creates a Process instance from the provided process data.
     * 
     * @internal
     * @since 1.0.0
     * @version 1.0.0
     * 
     * @param array<string, mixed> $processData
     * @throws InvalidArgumentException
     * @return Process
     */
    private function createProcess(array $processData): Process {

        if (!is_null($this->processCreator)) {

            return ($this->processCreator)($processData);
        }
        
        if (is_null($command = $processData['command'] ?? null)) {

            throw new InvalidArgumentException('Process data must contain a "command" key.');
        }

        return new Process(
            
            command: $command,
            cwd:     $processData['cwd']     ?? null,
            env:     $processData['env']     ?? null,
            input:   $processData['input']   ?? null,
            timeout: $processData['timeout'] ?? 60
        );
    }

    /**
     * Waits until the number of running processes is less than or equal to the target count.
     * 
     * @internal
     * @since 1.0.0
     * @version 1.0.0
     * 
     * @param int $targetProcessesCount
     * @return void
     */
    private function wait(int $targetProcessesCount): void {

        while (count($this->runningProcesses) > $targetProcessesCount) {

            usleep($this->reliefSleepMicros);
            $this->checkForCompletedProcesses();
        }
    }

    /**
     * Checks for completed processes and invokes the completion callback if provided.
     * 
     * @internal
     * @since 1.0.0
     * @version 1.0.0
     * 
     * @return void
     */
    private function checkForCompletedProcesses(): void {

        foreach ($this->runningProcesses as $index => $process) {
            
            if (!$process['process']->isRunning()) {
                
                if (!is_null($onProcessCompletion = $this->onProcessCompletion)) {

                    $onProcessCompletion($process['id'], $process['process']);
                }
                
                unset($this->runningProcesses[$index]);
            }
        }
    }
}
