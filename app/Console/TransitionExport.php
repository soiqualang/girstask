<?php

namespace Console;

use Core\Tool;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransitionExport extends Base
{
    protected function configure()
    {
        $this
            ->setName('export:transitions')
            ->setDescription('Task transitions CSV export')
            ->addArgument('project_id', InputArgument::REQUIRED, 'Project id')
            ->addArgument('start_date', InputArgument::REQUIRED, 'Start date (YYYY-MM-DD)')
            ->addArgument('end_date', InputArgument::REQUIRED, 'End date (YYYY-MM-DD)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->transition->export(
            $input->getArgument('project_id'),
            $input->getArgument('start_date'),
            $input->getArgument('end_date')
        );

        if (is_array($data)) {
            Tool::csv($data);
        }
    }
}
