<?php

namespace App\Contracts\Transactions;

use Closure;

interface TransactionManagerInterface
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(Closure $callback, int $attempts = 1): mixed;
}
