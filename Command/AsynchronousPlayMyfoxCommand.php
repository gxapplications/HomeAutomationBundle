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

class AsynchronousPlayMyfoxCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
		->setName('myfox:play:asynchronous')
		->setDescription('Play a Myfox command that has been scheduled before; register the answer.')
		->addOption('home', null, InputOption::VALUE_REQUIRED, 'Home KEY')
		->addOption('command_id', null, InputOption::VALUE_REQUIRED, 'The ID of the command to play')
		->addOption('removeschedule', null, InputOption::VALUE_NONE, 'If set, the schedule will be deleted from scheduler after execution.')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* @var $service MyfoxService */
		$service = $this->getContainer()->get('gx_home_automation.myfox');
		
		$output->writeln( $service->playAsync($input->getOption('home'), $input->getOption('command_id')) );
		
		if ($input->hasOption('removeschedule')) {
			$em = $this->getContainer()->get('doctrine')->getManager();
			$taskName = 'Myfox H.'.$input->getOption('home').' C.'.$input->getOption('command_id');
			$query = $em->createQuery('DELETE FROM JMoseCommandSchedulerBundle:ScheduledCommand sc WHERE sc.name = ?1');
			$query->setParameter(1, $taskName);
			$query->execute();
		}
	}
}