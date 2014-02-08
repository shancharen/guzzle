<?php

namespace Guzzle\Service;

use Guzzle\Common\HasEmitterTrait;
use Guzzle\Http\ClientInterface;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Description\DescriptionInterface;
use Guzzle\Service\Guzzle\CommandDescriptionFactory;

/**
 * Default Guzzle service description based client
 */
class ServiceClient implements ServiceClientInterface
{
    use HasEmitterTrait;

    private $client;
    private $description;
    private $config;
    private $commandFactory;

    public function __construct(
        ClientInterface $client,
        DescriptionInterface $description,
        array $config = []
    ) {
        $this->client = $client;
        $this->description = $description;
        $this->config = $config;
        $this->commandFactory = isset($config['command_factory'])
            ? $config['command_factory']
            : new CommandDescriptionFactory($this->description);
    }

    public function getHttpClient()
    {
        return $this->client;
    }

    public function getCommand($name, array $args = [])
    {
        if (!$this->description->hasOperation($name)) {
            throw new \InvalidArgumentException('No operation found matching ' . $name);
        }
    }

    public function execute(CommandInterface $command)
    {
        $this->getHttpClient()->send($command->getRequest());

        return $command->getResult();
    }

    public function getDescription()
    {
        return $this->description;
    }
}
