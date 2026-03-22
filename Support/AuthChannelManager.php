<?php

namespace Andmarruda\AuthModule\Support;

use Andmarruda\AuthModule\Contracts\AuthChannelInterface;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class AuthChannelManager
{
    /**
     * @param iterable<int, AuthChannelInterface> $channels
     */
    public function __construct(iterable $channels)
    {
        foreach ($channels as $channel) {
            $this->channels[$channel->name()] = $channel;
        }
    }

    /**
     * @var array<string, AuthChannelInterface>
     */
    private array $channels = [];

    public function resolve(?string $channel = null): AuthChannelInterface
    {
        $resolved = $channel ?: (string) config('authmodule.auth.default_channel', 'session');

        $driver = Arr::get($this->channels, $resolved);

        if ($driver instanceof AuthChannelInterface) {
            return $driver;
        }

        throw new InvalidArgumentException("Unsupported auth channel [{$resolved}].");
    }
}
