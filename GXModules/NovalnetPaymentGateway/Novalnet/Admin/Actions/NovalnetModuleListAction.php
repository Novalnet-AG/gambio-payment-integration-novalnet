<?php
declare(strict_types=1);

/**
 * Novalnet payment module
 *
 * This class extends the Gambio module list action to add Novalnet-specific
 * vendor and version information to the payment modules displayed in the
 * administration module center, and cleans up dummy requirement input fields
 * while retaining the requirement text cleanly in the module configuration.
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: NovalnetModuleListAction.php
 */

namespace GXModules\NovalnetPaymentGateway\Novalnet\Admin\Actions;

use Gambio\Admin\Modules\ModuleCenter\App\Actions\ModuleListAction;

class NovalnetModuleListAction extends ModuleListAction
{
    protected function buildModuleList(string $type): array 
    {
        $moduleList = parent::buildModuleList($type);

        if ($type === 'payment') {
            $versionInfo = $this->getGXModuleVersionInfo('NovalnetPaymentGateway', 'Novalnet');
            $version = $versionInfo['version'] ?? '12.4.2';

            foreach ($moduleList as &$module) {
                if (isset($module['code']) && substr($module['code'], 0, 8) === 'novalnet') {
                    $module['vendor']  = 'Novalnet';
                    $module['version'] = $version;

                    if ($module['code'] === 'novalnet_postfinance') {
                        $module['config'] = array_filter(
                            $module['config'],
                            static function (array $field): bool {
                                return substr($field['key'] ?? '', 0, 39) !== 'MODULE_PAYMENT_NOVALNET_POSTFINANCE_CARD_';
                            }
                        );
                    }

                    if(in_array($module['code'],['novalnet_instalment_invoice','novalnet_guarantee_invoice','novalnet_guarantee_sepa','novalnet_instalment_sepa'],true)){
                        foreach ($module['config'] as &$field) {
                            if (substr($field['key'] ?? '', -10) === '_BASIC_REQ') { 
                                $inputId = explode('/', $field['key'])[1] ?? '';
                                foreach ($field['description'] as $lang => &$desc) {
                                    // Hide the dummy input box via CSS while keeping title and requirements text
                                    $desc = '<style>#' . $inputId . ' { display: none !important; }</style>' . $desc;
                                }
                            }
                        } 
                    }
                }
            }
        } 

        return $moduleList;
    } 
}
