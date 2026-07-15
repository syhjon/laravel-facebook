<?php

namespace App\Transactions;

use App\Contracts\Transactions\TransactionManagerInterface;
use Closure;
use Illuminate\Database\DatabaseManager;

class DatabaseTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {}

    public function run(Closure $callback, int $attempts = 1): mixed
    {
        return $this->databaseManager
            ->connection()
            ->transaction(fn (): mixed => $callback(), $attempts);
    }
}
