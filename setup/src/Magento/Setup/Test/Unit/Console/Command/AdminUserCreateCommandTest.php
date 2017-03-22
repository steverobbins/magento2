<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Test\Unit\Console\Command;

use Magento\Setup\Model\AdminAccount;
use Magento\Setup\Console\Command\AdminUserCreateCommand;
use Magento\Setup\Mvc\Bootstrap\InitParamListener;
use Magento\User\Model\UserValidationRules;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Tester\CommandTester;

class AdminUserCreateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Setup\Model\InstallerFactory
     */
    private $installerFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AdminUserCreateCommand
     */
    private $command;

    public function setUp()
    {
        $this->installerFactoryMock = $this->getMock(\Magento\Setup\Model\InstallerFactory::class, [], [], '', false);
        $this->command = new AdminUserCreateCommand($this->installerFactoryMock, new UserValidationRules());
        $this->command->setHelperSet(new HelperSet([new DialogHelper]));
    }

    public function testExecute()
    {
        $options = [
            '--' . AdminAccount::KEY_USER => 'user',
            '--' . AdminAccount::KEY_PASSWORD => '123123q',
            '--' . AdminAccount::KEY_EMAIL => 'test@test.com',
            '--' . AdminAccount::KEY_FIRST_NAME => 'John',
            '--' . AdminAccount::KEY_LAST_NAME => 'Doe',
        ];
        $data = [
            AdminAccount::KEY_USER => 'user',
            AdminAccount::KEY_PASSWORD => '123123q',
            AdminAccount::KEY_EMAIL => 'test@test.com',
            AdminAccount::KEY_FIRST_NAME => 'John',
            AdminAccount::KEY_LAST_NAME => 'Doe',
        ];
        $commandTester = new CommandTester($this->command);
        $installerMock = $this->getMock(\Magento\Setup\Model\Installer::class, [], [], '', false);
        $installerMock->expects($this->once())->method('installAdminUser')->with($data);
        $this->installerFactoryMock->expects($this->once())->method('create')->willReturn($installerMock);
        $commandTester->execute($options);
        $this->assertEquals('Created Magento administrator user named user' . PHP_EOL, $commandTester->getDisplay());
    }

    public function testExecuteWithPrompts()
    {
        $data = [
            AdminAccount::KEY_USER => 'user',
            AdminAccount::KEY_PASSWORD => '123123q',
            AdminAccount::KEY_EMAIL => 'test@test.com',
            AdminAccount::KEY_FIRST_NAME => 'John',
            AdminAccount::KEY_LAST_NAME => 'Doe',
        ];
        $commandTester = new CommandTester($this->command);
        $installerMock = $this->getMock('Magento\Setup\Model\Installer', [], [], '', false);
        $installerMock->expects($this->once())->method('installAdminUser')->with($data);
        $this->installerFactoryMock->expects($this->once())->method('create')->willReturn($installerMock);

        $helper = $this->command->getHelper('dialog');
        $helper->setInputStream($this->getInputStream("John\nDoe\nuser\ntest@test.com\n123123q\n"));

        $commandTester->execute([]);
        $this->assertContains('Created Magento administrator user named user', $commandTester->getDisplay());
    }

    public function testGetOptionsList()
    {
        /* @var $argsList \Symfony\Component\Console\Input\InputArgument[] */
        $argsList = $this->command->getOptionsList();
        $this->assertEquals(AdminAccount::KEY_EMAIL, $argsList[2]->getName());
    }

    /**
     * @dataProvider validateDataProvider
     * @param bool[] $options
     * @param string[] $errors
     */
    public function testValidate(array $options, array $errors)
    {
        $this->assertEquals($errors, $this->command->validate($options));
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                [
                    AdminAccount::KEY_FIRST_NAME => null,
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => 'test@test.com',
                    AdminAccount::KEY_PASSWORD   => '123123q',
                ],
                ['First Name is a required field.']
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => null,
                    AdminAccount::KEY_USER       => null,
                    AdminAccount::KEY_EMAIL      => 'test@test.com',
                    AdminAccount::KEY_PASSWORD   => '123123q',
                ],
                ['User Name is a required field.', 'Last Name is a required field.'],
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => null,
                    AdminAccount::KEY_PASSWORD   => '123123q',
                ],
                ['Please enter a valid email.']
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => 'test',
                    AdminAccount::KEY_PASSWORD   => '123123q',
                ],
                ["'test' is not a valid email address in the basic format local-part@hostname"]
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => 'test@test.com',
                    AdminAccount::KEY_PASSWORD   => '', 
            ],
                [
                    'Password is required field.',
                    'Your password must be at least 7 characters.',
                    'Your password must include both numeric and alphabetic characters.'
                ]
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => 'test@test.com',
                    AdminAccount::KEY_PASSWORD   => '123123',
                ],
                [
                    'Your password must be at least 7 characters.',
                    'Your password must include both numeric and alphabetic characters.'
                ]
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => 'test@test.com',
                    AdminAccount::KEY_PASSWORD   => '1231231',
                ],
                ['Your password must include both numeric and alphabetic characters.']
            ],
            [
                [
                    AdminAccount::KEY_FIRST_NAME => 'John',
                    AdminAccount::KEY_LAST_NAME  => 'Doe',
                    AdminAccount::KEY_USER       => 'admin',
                    AdminAccount::KEY_EMAIL      => 'test@test.com',
                    AdminAccount::KEY_PASSWORD   => '123123q',
                ],
                []
            ],
        ];
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
