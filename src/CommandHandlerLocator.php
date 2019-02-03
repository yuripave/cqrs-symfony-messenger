<?php

/*
 * cqrs-symfony-messenger (https://github.com/phpgears/cqrs-symfony-messenger).
 * CQRS implementation with Symfony's Messenger.
 *
 * @license MIT
 * @link https://github.com/phpgears/cqrs-symfony-messenger
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

declare(strict_types=1);

namespace Gears\CQRS\Symfony\Messenger;

use Gears\CQRS\Command;
use Gears\CQRS\CommandHandler;
use Gears\CQRS\Exception\InvalidCommandException;
use Gears\CQRS\Symfony\Messenger\Exception\InvalidCommandHandlerException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

class CommandHandlerLocator implements HandlersLocatorInterface
{
    /**
     * Command handlers map.
     *
     * @var mixed[]
     */
    protected $handlersMap;

    /**
     * CommandHandlerLocator constructor.
     *
     * @param mixed[] $handlers
     */
    public function __construct(array $handlers)
    {
        $this->handlersMap = $handlers;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidCommandHandlerException
     */
    public function getHandlersMap(Envelope $envelope): iterable
    {
        $seen = [];

        foreach ($this->getCommandMap($envelope) as $type) {
            foreach ($this->handlersMap[$type] ?? [] as $alias => $handler) {
                if (!$handler instanceof CommandHandler) {
                    throw new InvalidCommandHandlerException(\sprintf(
                        'Command handler must implement %s interface, %s given',
                        CommandHandler::class,
                        \is_object($handler) ? \get_class($handler) : \gettype($handler)
                    ));
                }

                if (!\in_array($handler, $seen, true)) {
                    yield $alias => $seen[] = $handler;
                }
            }
        }
    }

    /**
     * Get command mapping.
     *
     * @param Envelope $envelope
     *
     * @throws InvalidCommandException
     *
     * @return mixed[]
     */
    final protected function getCommandMap(Envelope $envelope): array
    {
        $command = $envelope->getMessage();

        if (!$command instanceof Command) {
            throw new InvalidCommandException(\sprintf(
                'Command must implement %s interface, %s given',
                Command::class,
                \is_object($command) ? \get_class($command) : \gettype($command)
            ));
        }

        $class = \get_class($command);

        return [$class => $class]
            + ['*' => '*'];
    }
}