<?php

declare(strict_types=1);

namespace Keboola\MessengerBundle\ConnectionEvent\Serializer;

use JsonException;
use Keboola\MessengerBundle\ConnectionEvent\EventFactoryInterface;
use Keboola\MessengerBundle\ConnectionEvent\Exception\EventFactoryException;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AzureServiceBusSerializer implements SerializerInterface
{
    public function __construct(
        private readonly EventFactoryInterface $eventFactory,
    ) {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $messageBody = $encodedEnvelope['body'] ?? null;
        if ($messageBody === null) {
            throw new MessageDecodingFailedException('Message is missing body');
        }

        try {
            $messageBody = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MessageDecodingFailedException(
                sprintf('Message body is not a valid JSON: %s', $e->getMessage()),
                0,
                $e,
            );
        }

        if (!is_array($messageBody)) {
            throw new MessageDecodingFailedException('Message body must be an array');
        }

        // when passing message through EventGrid, it wraps the whole message
        // we expect the message to be passed from Connection -> EventGrid -> ServiceBus -> this consumer
        // https://docs.aws.amazon.com/sns/latest/dg/sns-sqs-as-subscriber.html
        if (!isset($messageBody['data'])) {
            throw new MessageDecodingFailedException(
                'Message is missing a "data" property. Was it passed through EventGrid?',
            );
        }

        $eventData = $messageBody['data'];

        if (!is_array($eventData)) {
            throw new MessageDecodingFailedException('Message body data must be an array');
        }

        try {
            $event = $this->eventFactory->createEventFromArray($eventData);
        } catch (EventFactoryException $e) {
            throw new MessageDecodingFailedException(
                sprintf('Failed to create an event object from the message: %s', $e->getMessage()),
                0,
                $e,
            );
        }

        return new Envelope($event, []);
    }

    public function encode(Envelope $envelope): array
    {
        throw new RuntimeException(sprintf('%s does not support encoding messages', static::class));
    }
}
