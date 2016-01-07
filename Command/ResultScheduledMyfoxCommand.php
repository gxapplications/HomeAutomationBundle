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

class ResultScheduledMyfoxCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
		->setName('myfox:schedule:result')
		->setDescription('Check for result of a registered Myfox command. Return its state or dump the result.')
		->addOption('command_id', null, InputOption::VALUE_REQUIRED, 'The ID of the command to check')
		->addOption('dump', 'd', InputOption::VALUE_NONE, 'If set, does not return state, but the dump of the result itself.')
		->addOption('clean', 'c', InputOption::VALUE_NONE, 'If set, and if command is finished, clean the command and its result from the register.')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* @var $service MyfoxService */
		$service = $this->getContainer()->get('gx_home_automation.myfox');
		
		if ($input->hasOption('dump')) {
			$output->writeln( $service->result($input->getOption('command_id'), $input->hasOption('clean')) );
		} else {
			$output->writeln( $service->state($input->getOption('command_id'), $input->hasOption('clean')) );
		}
	}
}