<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @author    Daniel Hoffmann <daniel.hoffmann@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

use jtl\Connector\Core\Utilities\Singleton;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\Type\DataType;
use jtl\Connector\Type\PropertyInfo;
use jtl\Connector\WooCommerce\Utility\Constants;
use jtl\Connector\WooCommerce\Utility\Date;

abstract class BaseMapper extends Singleton
{
    /**
     * @var DataType The mapped models type.
     */
    protected $type;
    /**
     * @var string The mapped models name.
     */
    protected $model;
    protected $push = [];
    protected $pull = [];
    protected $timeZone;

    /**
     * Test classes or plugins have to specify the entity as the controller and mapper are built with this as base.
     *
     * @param string $shortName The short name.
     */
    public function __construct($shortName = null)
    {
        if (is_null($shortName)) {
            $reflect = new \ReflectionClass($this);
            $shortName = $reflect->getShortName();
        }
        $typeClass = Constants::CORE_TYPE_NAMESPACE . $shortName;
        $this->model = Constants::CORE_MODEL_NAMESPACE . $shortName;
        if (class_exists($typeClass)) {
            $this->type = new $typeClass();
        }
        $this->timeZone = new \DateTimeZone('UTC');
    }

    /**
     * Method which maps the endpoint model to an host version.
     *
     * @param mixed $data The endpoint model which should be mapped.
     *
     * @return DataModel The result of mapping the WooCommerce model to a host model.
     */
    public function toHost($data)
    {
        $model = new $this->model();
        foreach ($this->pull as $host => $endpoint) {
            $setter = 'set' . ucfirst($host);
            $functionName = strtolower($host);
            $property = $this->type->getProperty($host);
            if (method_exists($this, $functionName) && is_null($endpoint)) {
                $value = $this->$functionName($data);
            } else {
                $value = $this->getValue($data, $endpoint);
                if ($property instanceof PropertyInfo && $property->isNavigation()) {
                    $subControllerName = Constants::CONTROLLER_NAMESPACE . $endpoint;
                    if (class_exists($subControllerName)) {
                        $subController = new $subControllerName();
                        if (method_exists($subController, 'pullData')) {
                            $value = $subController->pullData($data, $model);
                        }
                    }
                } elseif ($property->isIdentity()) {
                    $value = new Identity($value);
                } else {
                    $this->parseSimpleTypes($property, $value);
                }
            }
            if (!empty($value)) {
                $model->$setter($value);
            }
        }

        return $model;
    }

    protected function getValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key] : null;
    }

    /**
     * Method which maps the host model to an endpoint version.
     *
     * @param DataModel $data The host model which should be mapped.
     * @param null $customData Additional data which should be past to an extra defined method.
     *
     * @return array The result of mapping the host model to an WooCommerce model.
     */
    public function toEndpoint(DataModel $data, $customData = null)
    {
        $model = [];
        foreach ($this->push as $endpoint => $host) {
            $functionName = strtolower($endpoint);
            /** @var PropertyInfo $property */
            $property = $this->type->getProperty($host);
            if (method_exists($this, $functionName) && is_null($host)) {
                $model[$endpoint] = $this->$functionName($data, $customData);
            } else {
                $getter = 'get' . ucfirst($host);
                $value = $data->$getter();
                if ($property->isNavigation()) {
                    $subControllerName = Constants::CONTROLLER_NAMESPACE . $endpoint;
                    if (class_exists($subControllerName)) {
                        $subController = new $subControllerName();
                        if (method_exists($subController, 'pushData')) {
                            $subController->pushData($data, $model);
                        }
                    }
                } else {
                    if ($property->isIdentity() && $value instanceof Identity) {
                        $value = $value->getEndpoint();
                    } elseif ($property->getType() === 'DateTime') {
                        if (is_null($value)) {
                            $value = '0000-00-00 00:00:00';
                        } elseif ($value instanceof \DateTime) {
                            $value->setTimezone($this->timeZone);
                            $value = $value->format('Y-m-d H:i:s');
                        }
                    }
                    $model[$endpoint] = $value;
                }
            }
        }

        return $model;
    }

    private function parseSimpleTypes(PropertyInfo $property, &$value)
    {
        if ($property->getType() == 'string') {
            $value = "{$value}";
        } elseif ($property->getType() == 'boolean') {
            $value = (bool)$value;
        } elseif ($property->getType() == 'integer') {
            $value = (int)$value;
        } elseif ($property->getType() == 'double') {
            $value = (double)$value;
        } elseif ($property->getType() == 'DateTime') {
            $value = Date::isOpenDate($value) ? null : new \DateTime($value, $this->timeZone);
        }
    }
}
