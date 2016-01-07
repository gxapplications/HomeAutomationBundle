<?php

namespace GXApplications\HomeAutomationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GXApplications\HomeAutomationBundle\MyfoxService;
use GXApplications\HomeAutomationBundle\Entity\Home;
use Doctrine\ORM\EntityManager;
use GXApplications\HomeAutomationBundle\Entity\MyfoxCommand;

class AddScheduledMyfoxCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
		->setName('myfox:schedule:add')
		->setDescription('Register a Myfox command to be scheduled later or directly. And return the scheduled command ID')
		->addOption('home', null, InputOption::VALUE_REQUIRED, 'Home KEY')
		->addOption('command', null, InputOption::VALUE_REQUIRED, 'The Myfox command to play (see Myfox service)')
		->addOption('timestamp', 't', InputOption::VALUE_REQUIRED, 'The Unix timestamp for execution. Immediately by default.', -1)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{		
		/* @var $service MyfoxService */
		$service = $this->getContainer()->get('gx_home_automation.myfox');
		
		$output->writeln( $service->schedule(
							$input->getOption('home'),
							$input->getOption('command'),
							$input->getParameterOption('timestamp'))
						);
	}
}