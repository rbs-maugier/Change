<?php
namespace Change\Application\Console;

use Change\Application\Console\ChangeCommand as Command;
use Change\Commands\Events\ConsoleCommandResponse;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Zend\Json\Json;

/**
 * @name \Change\Application\Console\ConsoleApplication
 */
class ConsoleApplication extends \Symfony\Component\Console\Application
{
	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \Change\Application
	 */
	protected $changeApplication;

	/**
	 * @return \Change\Application
	 */
	public function getChangeApplication()
	{
		return $this->changeApplication;
	}

	/**
	 * @return array
	 */
	public function getConfiguration()
	{
		if (!$this->configuration)
		{
			$globalConfig = array();
			$projectConfig = array();
			if (isset($_SERVER['HOME']) && file_exists($_SERVER['HOME'] . '/.console.json'))
			{
				$globalConfig = Json::decode(file_get_contents($_SERVER['HOME'] . '/.console.json'), Json::TYPE_ARRAY);
			}
			$projectConfigFile = $this->getChangeApplication()->getWorkspace()->appPath('Config', 'console.json');
			if (file_exists($projectConfigFile))
			{
				$projectConfig = Json::decode(file_get_contents($projectConfigFile), Json::TYPE_ARRAY);
			}
			$this->configuration = array_merge_recursive($globalConfig, $projectConfig);
		}
		return $this->configuration;
	}

	/**
	 * @param \Change\Application $changeApplication
	 */
	public function setChangeApplication(\Change\Application $changeApplication)
	{
		$this->changeApplication = $changeApplication;
	}

	/**
	 * Registers all the commands
	 */
	public function registerCommands()
	{
		$changeApplication = $this->getChangeApplication();
		$eventManagerFactory = new \Change\Events\EventManagerFactory($changeApplication);
		$eventManagerFactory->addSharedService('applicationServices', new \Change\Services\ApplicationServices($changeApplication, $eventManagerFactory));

		$eventManager = $eventManagerFactory->getNewEventManager('Commands');
		$classNames = $changeApplication->getConfiguration()->getEntry('Change/Events/Commands', array());
		$event = new \Change\Commands\Events\Event('registerServices', $changeApplication, array('eventManagerFactory' => $eventManagerFactory));
		$eventManager->trigger($event);

		$eventManagerFactory->registerListenerAggregateClassNames($eventManager, $classNames);
		$event = new \Change\Commands\Events\Event('config', $changeApplication, array('eventManagerFactory' => $eventManagerFactory));
		$results = $eventManager->trigger($event);
		foreach ($results as $result)
		{
			if (is_array($result))
			{
				$this->registerCommandsConfig($result, $eventManager, $changeApplication);
			}
		}

		$event = new \Change\Commands\Events\Event('command', $this, array());
		$eventManager->trigger($event);
	}

	/**
	 * @param array $commandsConfig
	 * @param \Change\Events\EventManager $eventManager
	 * @param \Change\Application $changeApplication
	 */
	protected function registerCommandsConfig($commandsConfig, $eventManager, $changeApplication)
	{
		if (!is_array($commandsConfig))
		{
			return;
		}

		$config = $this->getConfiguration();
		$aliases = isset($config['aliases']) ? $config['aliases'] : array();
		foreach ($commandsConfig as $commandName => $commandConfig)
		{
			$command = new Command($commandName);
			if (isset($aliases[$commandName]))
			{
				$currentAliases = $command->getAliases();
				if (is_array($aliases[$commandName]))
				{
					$currentAliases = array_merge($currentAliases, $aliases[$commandName]);
				}
				elseif (is_string($aliases[$commandName]))
				{
					$currentAliases[] = $aliases[$commandName];
				}
				$command->setAliases($currentAliases);
			}
			$command->setDescription($commandConfig['description']);
			if (isset($commandConfig['dev']) && $commandConfig['dev'] === true)
			{
				$command->setDevCommand(true);
			}

			if (isset($commandConfig['options']) && is_array($commandConfig['options']))
			{
				foreach ($commandConfig['options'] as $optionName => $optionData)
				{
					$shortcut = isset($optionData['shortcut']) ? $optionData['shortcut'] : null;
					if (array_key_exists('default', $optionData))
					{
						if (isset($optionData['default']))
						{
							$mode = InputOption::VALUE_OPTIONAL;
							$default = $optionData['default'];
						}
						else
						{
							$mode = InputOption::VALUE_REQUIRED;
							$default = null;
						}
					}
					else
					{
						$mode = InputOption::VALUE_NONE;
						$default = null;
					}
					$description = isset($optionData['description']) ? $optionData['description'] : '';
					$command->addOption($optionName, $shortcut, $mode, $description, $default);
				}
			}
			if (isset($commandConfig['arguments']) && is_array($commandConfig['arguments']))
			{
				foreach ($commandConfig['arguments'] as $argumentName => $argumentData)
				{
					$description = isset($argumentData['description']) ? $argumentData['description'] : '';
					$mode = isset($argumentData['required']) && $argumentData['required']
						? InputArgument::REQUIRED : InputArgument::OPTIONAL;
					$default = isset($argumentData['default']) ? $argumentData['default'] : null;
					$command->addArgument($argumentName, $mode, $description, $default);
				}
			}

			$command->setCode(function (InputInterface $input, OutputInterface $output) use (
				$command, $eventManager, $changeApplication
			)
			{
				$args = $eventManager->prepareArgs(array_merge($input->getOptions(), $input->getArguments()));
				$args['outputMessages'] = new \ArrayObject();

				$event = new \Change\Commands\Events\Event($command->getName(), $changeApplication, $args);

				$response = new \Change\Commands\Events\ConsoleCommandResponse();
				$response->setOutput($output);
				$event->setCommandResponse($response);

				$eventManager->trigger($event);
				$hasErrors = $response->hasError();

				return $hasErrors ? 1 : 0;
			});
			$command->setChangeApplication($changeApplication);
			$this->add($command);
		}
	}

	/**
	 * @return \Symfony\Component\Console\Input\InputDefinition
	 */
	protected function getDefaultInputDefinition()
	{
		$definition = parent::getDefaultInputDefinition();
		$definition->addOption(new InputOption('--dev', '-d', InputOption::VALUE_NONE, 'Force developer mode'));
		return $definition;
	}
}