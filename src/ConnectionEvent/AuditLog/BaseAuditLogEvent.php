<?php

declare(strict_types=1);

namespace Keboola\MessengerBundle\ConnectionEvent\AuditLog;

use DateTimeImmutable;
use InvalidArgumentException;
use Keboola\MessengerBundle\ConnectionEvent\EventInterface;

abstract class BaseAuditLogEvent implements EventInterface
{
    protected string $id;
    protected DateTimeImmutable $createdAt;

    protected string $adminId;
    protected string $adminName;
    protected string $adminEmail;

    final private function __construct()
    {
    }

    public static function fromArray(array $data): static
    {
        $eventName = static::NAME; // @phpstan-ignore-line

        if ($data['operation'] !== $eventName) {
            throw new InvalidArgumentException(sprintf(
                '%s expects event name "%s" but operation in data is "%s"',
                static::class,
                $eventName,
                $data['operation'],
            ));
        }

        $event = new static();

        $event->id = (string) $data['id'];
        $event->createdAt = new DateTimeImmutable($data['auditLogEventCreatedAt']);

        $event->adminId = (string) $data['admin']['id'];
        $event->adminName = $data['admin']['name'];
        $event->adminEmail = $data['admin']['email'];

        return $event;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'operation' => static::getEventName(),
            'auditLogEventCreatedAt' => $this->createdAt->format(DateTimeImmutable::ISO8601),
            'admin' => [
                'id' => $this->adminId,
                'name' => $this->adminName,
                'email' => $this->adminEmail,
            ],
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return static::NAME; // @phpstan-ignore-line
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAdminId(): string
    {
        return $this->adminId;
    }

    public function getAdminName(): string
    {
        return $this->adminName;
    }

    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }
}
