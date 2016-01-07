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

class CancelScheduledMyfoxCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
		->setName('myfox:schedule:cancel')
		->setDescription('Cancel a registered Myfox command to be scheduled later, if it\'s not too late. And return state/cancel succeeded.')
		->addOption('command_id', null, InputOption::VALUE_REQUIRED, 'The ID of the command to cancel')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* @var $service MyfoxService */
		$service = $this->getContainer()->get('gx_home_automation.myfox');
		
		$output->writeln( $service->cancel($input->getOption('command_id')) );
	}
}