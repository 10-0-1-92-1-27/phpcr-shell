<?php

namespace PHPCR\Shell\Console\Command\Phpcr;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class NodeRemoveCommand extends Command
{
    protected function configure()
    {
        $this->setName('node:remove');
        $this->setDescription('Remove the node at path');
        $this->addArgument('path', InputArgument::REQUIRED, 'Path of node');
        $this->setHelp(<<<HERE
Remove the node at the given path.
HERE
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $session = $this->getHelper('phpcr')->getSession();
        $targetPath = $input->getArgument('path');
        $currentPath = $session->getCwd();

        // verify that node exists by trying to get it..
        $targetNode = $session->getNodeByPathOrIdentifier($targetPath);

        if ($targetNode->getPath() == '/') {
            throw new \InvalidArgumentException(
                'You cannot delete the root node!'
            );
        }

        $references = $targetNode->getReferences();

        if (count($references) > 0) {
            $paths = array();
            foreach ($references as $reference) {
                $paths[] = $reference->getPath();
            }

            throw new \InvalidArgumentException(sprintf(
                'The node "%s" is referenced by the following properties: "%s"',
                $targetNode->getPath(),
                implode('", "', $paths)
            ));
        }

        $targetNode->remove();

        // if we deleted the current path, switch back to the parent node
        if ($currentPath == $session->getAbsPath($targetPath)) {
            $session->chdir('..');
        }
    }
}
