<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

class Image extends BaseMapper
{
    protected $pull = [
        'id'         => 'id',
        'name'       => 'post_name',
        'foreignKey' => 'parent',
        'remoteUrl'  => 'guid',
        'sort'       => 'sort',
        'filename'   => null,
    ];

    protected function filename(array $data)
    {
        return \wc_get_filename_from_url($data['guid']);
    }
}
