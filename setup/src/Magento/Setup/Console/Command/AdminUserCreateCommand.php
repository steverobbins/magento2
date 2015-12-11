<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Console\Command;

use Magento\Setup\Model\AdminAccount;
use Magento\Framework\Setup\ConsoleLogger;
use Magento\Setup\Model\InstallerFactory;
use Magento\User\Model\UserValidationRules;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AdminUserCreateCommand extends AbstractSetupCommand
{
    /**
     * @var InstallerFactory
     */
    private $installerFactory;

    /**
     * @var UserValidationRules
     */
    private $validationRules;

    /**
     * @param InstallerFactory $installerFactory
     * @param UserValidationRules $validationRules
     */
    public function __construct(InstallerFactory $installerFactory, UserValidationRules $validationRules)
    {
        $this->installerFactory = $installerFactory;
        $this->validationRules = $validationRules;
        parent::__construct();
    }

    /**
     * Initialization of the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('admin:user:create')
            ->setDescription('Creates an administrator')
            ->setDefinition($this->getOptionsList());
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->getUserData($input, $output);
        $errors = $this->validate($data);
        if ($errors) {
            $output->writeln('<error>' . implode('</error>' . PHP_EOL .  '<error>', $errors) . '</error>');
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $installer = $this->installerFactory->create(new ConsoleLogger($output));
        $installer->installAdminUser($data);
        $output->writeln(
            '<info>Created Magento administrator user named ' . $data[AdminAccount::KEY_USER] . '</info>'
        );
    }

    /**
     * Get list of arguments for the command
     *
     * @return InputOption[]
     */
    public function getOptionsList()
    {
        return [
            new InputOption(AdminAccount::KEY_USER, null, InputOption::VALUE_REQUIRED, 'Admin user'),
            new InputOption(AdminAccount::KEY_PASSWORD, null, InputOption::VALUE_REQUIRED, 'Admin password'),
            new InputOption(AdminAccount::KEY_EMAIL, null, InputOption::VALUE_REQUIRED, 'Admin email'),
            new InputOption(
                AdminAccount::KEY_FIRST_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Admin first name'
            ),
            new InputOption(
                AdminAccount::KEY_LAST_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Admin last name'
            ),
        ];
    }

    /**
     * Check if all admin options are provided
     *
     * @param string[] $input
     * @return string[]
     */
    public function validate($data)
    {
        $errors = [];
        $user = new \Magento\Framework\DataObject();
        $user->setFirstname($data[AdminAccount::KEY_FIRST_NAME])
            ->setLastname($data[AdminAccount::KEY_LAST_NAME])
            ->setUsername($data[AdminAccount::KEY_USER])
            ->setEmail($data[AdminAccount::KEY_EMAIL])
            ->setPassword(
                $data[AdminAccount::KEY_PASSWORD] === null
                ? '' : $data[AdminAccount::KEY_PASSWORD]
            );

        $validator = new \Magento\Framework\Validator\DataObject;
        $this->validationRules->addUserInfoRules($validator);
        $this->validationRules->addPasswordRules($validator);

        if (!$validator->isValid($user)) {
            $errors = array_merge($errors, $validator->getMessages());
        }

        return $errors;
    }

    /**
     * Get admin user data
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string[]
     */
    protected function getUserData(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');
        $data = [];
        $keys = [
            AdminAccount::KEY_FIRST_NAME,
            AdminAccount::KEY_LAST_NAME,
            AdminAccount::KEY_USER,
            AdminAccount::KEY_EMAIL,
            AdminAccount::KEY_PASSWORD,
        ];
        foreach ($keys as $key) {
            if ($value = $input->getOption($key)) {
                $data[$key] = $value;
            } else {
                $method = $key === AdminAccount::KEY_PASSWORD ? 'askHiddenResponse' : 'ask';
                $data[$key] = $dialog->$method(
                    $output,
                    '<info>' . ucwords(str_replace('-', ' ', $key)) . '</info>: '
                );
            }
        }
        return $data;
    }
}
