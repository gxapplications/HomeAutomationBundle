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


class SynchronousPlayMyfoxCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
		->setName('myfox:play:synchronous')
		->setDescription('Play a Myfox command directly and return the server\'s response')
		->addOption('home', null, InputOption::VALUE_REQUIRED, 'Home KEY')
		->addOption('command', null, InputOption::VALUE_REQUIRED, 'The Myfox command to play (see Myfox service)')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* @var $service MyfoxService */
		$service = $this->getContainer()->get('gx_home_automation.myfox');
		
		$output->writeln( $service->playSync($input->getOption('home'), $input->getOption('command')) );
	}
}

// TODO !6 : ajouter une autre command pour authentifier le token (avec un login et password en arguments)