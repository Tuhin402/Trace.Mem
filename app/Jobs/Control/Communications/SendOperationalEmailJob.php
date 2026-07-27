<?php

namespace App\Jobs\Control\Communications;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Events\Control\Communications\OperationalEmailFailed;
use App\Events\Control\Communications\OperationalEmailSent;
use App\Mail\OperationalEmailMail;
use App\Models\OperationalCommunicationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOperationalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int|array
     */
    public $backoff = [30, 60, 120];

    public function __construct(
        public readonly CommunicationContext $context,
        public readonly string $logId
    ) {
        $this->onQueue('communications');
    }

    public function handle(): void
    {
        $log = OperationalCommunicationLog::findOrFail($this->logId);

        $log->update([
            'status' => 'processing',
            'queue_job_id' => $this->job->getJobId(),
        ]);

        try {
            Mail::to($this->context->recipientEmail)
                ->send(new OperationalEmailMail($this->context, $this->logId));

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            OperationalEmailSent::dispatch($log);

        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        $log = OperationalCommunicationLog::find($this->logId);

        if ($log) {
            $log->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            OperationalEmailFailed::dispatch($log, $exception);
        }
    }
}
