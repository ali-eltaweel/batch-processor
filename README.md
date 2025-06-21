# Batch-processor

**Run system processes in batches**

- [Batch-processor](#batch-processor)
  - [Installation](#installation)
  - [Usage](#usage)

***

## Installation

Install *batch-processor* via Composer:

```bash
composer require ali-eltaweel/batch-processor
```

## Usage

```php
use BatchProcessor\BatchProcessor;

$processes = [
  [ 'command' => [ 'tar', '-czf', 'dir1.tar.gz', 'dir1' ] ],
  [ 'command' => [ 'tar', '-czf', 'dir2.tar.gz', 'dir2' ] ]
];

$processor = new BatchProcessor($processes, maxConcurrentProcesses: 2);

$processor->start();
```
