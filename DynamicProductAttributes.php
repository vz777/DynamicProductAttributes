<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace DynamicProductAttributes;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;

class DynamicProductAttributes extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'dynamicproductattributes';

    public function postActivation(ConnectionInterface $con = null): void
    {
        $database = new Database($con);

        $database->insertSql(null, [ __DIR__ . '/Config/thelia.sql' ]);
    }

    public function destroy(ConnectionInterface $con = null, $deleteModuleData = false): void
    {
        if ($deleteModuleData) {
            $database = new Database($con);

            $database->insertSql(null, [__DIR__ . '/Config/destroy.sql']);
        }
    }
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR.ucfirst(self::getModuleCode()).'/I18n/*'])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
