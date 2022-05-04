<?php

namespace App\Jobs;

use App\Events\FailedJobEvent;
use App\Events\SuccessJobEvent;
use App\Events\TryJobEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $args = [
        'backoff' => 0,
        'tries' => 100,
        'guessNumber' => 50,
    ];
    protected string $transaction;
    protected int $randNumber;
    public $tries = 100;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($args = [])
    {
        if(!empty($args)) {
            $this->args = array_merge($this->args, $args);
            $this->tries = $this->args['tries'];
        }
        $this->transaction = time();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->randNumber = mt_rand(1, 100);
        event(new TryJobEvent($this->randNumber, $this->args['guessNumber'], $this->transaction));
        if ($this->randNumber != $this->args['guessNumber']) {
            throw new \Exception('Trying failed. Number ' . $this->randNumber . ' is not ' . $this->args['guessNumber']);
        } else {
            event(new SuccessJobEvent($this->randNumber, $this->args['guessNumber'], $this->transaction));
        }
    }

    public function failed(Throwable $throwable)
    {
        event(new FailedJobEvent($this->randNumber, $this->args['guessNumber'], $this->transaction, $throwable->getMessage()));
    }

    public function backoff()
    {
        return $this->args['backoff'];
    }
}