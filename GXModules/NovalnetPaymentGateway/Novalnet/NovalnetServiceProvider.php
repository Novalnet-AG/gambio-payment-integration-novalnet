<?php

declare(strict_types=1);

/**
 * Novalnet payment module
 *
 * This class provides the service provider configuration for the Novalnet
 * payment module and overrides the Gambio module list action with the
 * Novalnet-specific module list implementation.
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: NovalnetServiceProvider.php
 */

namespace GXModules\NovalnetPaymentGateway\Novalnet;

use Doctrine\DBAL\Connection;
use Gambio\Admin\Modules\Configuration\App\Data\Repositories\Compatibility\ModuleCenterRepository;
use Gambio\Admin\Modules\ModuleCenter\App\Actions\ModuleListAction;
use Gambio\Core\Application\DependencyInjection\AbstractModuleBootableServiceProvider;
use Gambio\Core\Application\ValueObjects\Path;
use Gambio\Core\Configuration\Services\ConfigurationService;
use Gambio\Core\GXModules\Services\GXModulesComponentsService;
use Gambio\Core\Language\Services\LanguageService;
use Gambio\Core\TextManager\Services\TextManager;
use GXModules\NovalnetPaymentGateway\Novalnet\Admin\Actions\NovalnetModuleListAction;

class NovalnetServiceProvider extends AbstractModuleBootableServiceProvider
{
    public function provides(): array
    {
        return [
            ModuleListAction::class,
        ];
    }

    public function register(): void
    { 
    }

    public function boot(): void
    {
        $this->application->registerShared(ModuleListAction::class, NovalnetModuleListAction::class)
            ->addArgument(Path::class)
            ->addArgument(ConfigurationService::class)
            ->addArgument(TextManager::class)
            ->addArgument(GXModulesComponentsService::class)
            ->addArgument(LanguageService::class)
            ->addArgument(Connection::class)
            ->addArgument(ModuleCenterRepository::class);
    }
}
