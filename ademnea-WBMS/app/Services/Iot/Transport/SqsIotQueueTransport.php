<?php

namespace App\Services\Iot\Transport;

use App\Contracts\IotQueueTransportContract;
use App\Support\Iot\IotQueueMessage;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Log;

/**
 * Real AWS implementation — ready for when access is unblocked. Requires
 * `composer require aws/aws-sdk-php` and AWS credentials/region in .env.
 * No other file in this pipeline changes when switching to this class;
 * only the binding in AppServiceProvider (driven by IOT_QUEUE_DRIVER)
 * decides which transport is active.
 */
class SqsIotQueueTransport implements IotQueueTransportContract
{
    private SqsClient $client;

    public function __construct(private readonly string $queueUrl, string $region)
    {
        $this->client = new SqsClient(['region' => $region, 'version' => 'latest','http'    => [
        'verify' => false,
    ],]);
    }

    public function pop(int $waitSeconds): ?IotQueueMessage
    {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $this->queueUrl,
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds' => $waitSeconds, // long polling
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        $messages = $result->get('Messages');
        if (empty($messages)) {
            return null;
        }

        $raw = $messages[0];
        $envelope = json_decode($raw['Body'], true);
        $attempts = (int) ($raw['Attributes']['ApproximateReceiveCount'] ?? 1);

        return new IotQueueMessage($envelope, $raw['ReceiptHandle'], $attempts);
    }

    public function acknowledge(IotQueueMessage $message): void
    {
        // SQS does NOT auto-delete on receive — skipping this would cause
        // the message to reappear after the visibility timeout and be
        // reprocessed. Our idempotency constraints would absorb that
        // harmlessly, but this avoids the wasted work entirely.
        $this->client->deleteMessage([
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $message->receiptHandle,
        ]);
    }

    public function fail(IotQueueMessage $message, string $reason): void
    {
        // Deliberately does nothing beyond logging. Leaving the message
        // un-deleted lets SQS's visibility timeout expire and redeliver
        // it automatically; the queue's own redrive policy (configured in
        // the AWS Console, per the original infra guidance) moves it to
        // the dead-letter queue after maxReceiveCount attempts — no
        // application code is needed to replicate that here, unlike Redis.
        Log::warning('IoT SQS message processing failed; left for SQS redelivery.', [
            'reason' => $reason,
            'requestId' => $message->envelope['requestId'] ?? null,
        ]);
    }
}