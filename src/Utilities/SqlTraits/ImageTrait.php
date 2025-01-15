<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use Jtl\Connector\Core\Definition\IdentityType;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Controllers\ImageController as ImageCtrl;
use JtlWooCommerceConnector\Utilities\Id;

trait ImageTrait
{
    /**
     * @param int|null $limit
     * @return string
     */
    public static function imageCategoryPull(?int $limit = null): string
    {
        global $wpdb;

        $limitQuery           = \is_null($limit) ? '' : 'LIMIT ' . $limit;
        list($table, $column) = CategoryUtil::getTermMetaData();
        $jcli                 = $wpdb->prefix . 'jtl_connector_link_image';

        return \sprintf(
            "
            SELECT CONCAT_WS('%s', '%s', p.ID) as id, p.ID as ID, tt.term_id as parent, p.guid
            FROM {$wpdb->term_taxonomy} tt
            RIGHT JOIN {$table} tm
            ON tt.term_id = tm.{$column}
            LEFT JOIN {$wpdb->posts} p
            ON p.ID = tm.meta_value
            LEFT JOIN {$jcli} l
            ON l.endpoint_id = p.ID
            AND type = %d
            WHERE l.host_id IS NULL
            AND tt.taxonomy = '%s'
            AND tm.meta_key = '%s'
            AND tm.meta_value != 0
            AND p.guid IS NOT NULL
            AND ID IS NOT NULL
            {$limitQuery}",
            Id::SEPARATOR,
            Id::CATEGORY_PREFIX,
            IdentityType::CATEGORY_IMAGE,
            CategoryUtil::TERM_TAXONOMY,
            ImageCtrl::CATEGORY_THUMBNAIL
        );
    }

    /**
     * @param int|null $limit
     * @return string
     */
    public static function imageManufacturerPull(?int $limit = null): string
    {
        global $wpdb;

        $limitQuery = \is_null($limit) ? '' : 'LIMIT ' . $limit;
        $jcli       = $wpdb->prefix . 'jtl_connector_link_image';
        $sql        = \sprintf(
            "
            SELECT CONCAT_WS('%s', '%s', p.ID) as id, p.ID as ID, tt.term_id as parent, p.guid
            FROM {$wpdb->term_taxonomy} tt
            RIGHT JOIN {$wpdb->termmeta} tm
            ON tt.term_id = tm.term_id
            LEFT JOIN {$wpdb->posts} p
            ON p.ID = tm.meta_value
            LEFT JOIN {$jcli} l
            ON l.endpoint_id = p.ID
            AND type = %d
            WHERE l.host_id IS NULL
            AND tt.taxonomy = '%s'
            AND tm.meta_key = '%s'
            AND tm.meta_value != 0
            {$limitQuery}",
            Id::SEPARATOR,
            Id::MANUFACTURER_PREFIX,
            IdentityType::MANUFACTURER_IMAGE,
            'pwb-brand',
            ImageCtrl::MANUFACTURER_KEY
        );

        return $sql;
    }

    /**
     * @return string
     */
    public static function imageProductThumbnail(): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return \sprintf(
            "
            SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
            ON p.ID = pm.post_id
            LEFT JOIN {$jcli} l
            ON SUBSTRING_INDEX(l.endpoint_id, '%s', -1) = pm.post_id  AND l.type = %d
            WHERE p.post_type = 'product'
            AND p.post_status IN ('draft', 'future', 'publish', 'inherit', 'private')
            AND pm.meta_key = '%s'
            AND pm.meta_value != 0
            AND l.host_id IS NULL
            GROUP BY p.ID, pm.meta_value",
            Id::SEPARATOR,
            IdentityType::PRODUCT_IMAGE,
            ImageCtrl::PRODUCT_THUMBNAIL
        );
    }

    /**
     * @return string
     */
    public static function imageProductGalleryStats(): string
    {
        global $wpdb;

        return \sprintf(
            "
            SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
            ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND p.post_status IN ('future', 'draft', 'publish', 'inherit', 'private')
            AND pm.meta_key = '%s' AND pm.meta_value != 0",
            ImageCtrl::GALLERY_KEY
        );
    }

    /**
     * @return string
     */
    public static function linkedProductImages(): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return \sprintf(
            "
			SELECT endpoint_id
			FROM {$jcli}
			WHERE `type` = '%d'",
            IdentityType::PRODUCT_IMAGE
        );
    }

    /**
     * @param int|null $limit
     * @return string
     */
    public static function imageVariationCombinationPull(?int $limit = null): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        $limitQuery = \is_null($limit) ? '' : 'LIMIT ' . $limit;

        return \sprintf(
            "
            SELECT pm.post_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
            ON p.ID = pm.post_id
            LEFT JOIN {$jcli} l
            ON SUBSTRING_INDEX(l.endpoint_id, '%s', -1) = pm.post_id
            AND l.type = %d
            WHERE p.post_type = 'product_variation'
            AND p.post_status IN ('draft', 'future', 'publish', 'inherit', 'private')
            AND pm.meta_key = '%s'
            AND pm.meta_value != 0
            AND l.host_id IS NULL
            AND p.post_parent IN (SELECT p2.ID
            FROM {$wpdb->posts} p2)
            {$limitQuery}",
            Id::SEPARATOR,
            IdentityType::PRODUCT_IMAGE,
            ImageCtrl::PRODUCT_THUMBNAIL
        );
    }

    /**
     * @param int    $attachementId
     * @param string $metaKey
     * @return string
     */
    public static function countTermMetaImages(int $attachementId, string $metaKey): string
    {
        global $wpdb;
        return \sprintf(
            "SELECT COUNT(term_id) FROM %s WHERE meta_key = '%s' AND meta_value = %s",
            $wpdb->termmeta,
            $metaKey,
            $attachementId
        );
    }

    /**
     * @param int $attachementId
     * @return string
     */
    public static function countRelatedProducts(int $attachementId): string
    {
        global $wpdb;

        return \sprintf(
            "
            SELECT COUNT(meta_id)
            FROM {$wpdb->postmeta}
            WHERE (meta_key = '%s'
            AND meta_value = {$attachementId})
            OR (meta_key = '%s'
            AND FIND_IN_SET({$attachementId}, meta_value) > 0)",
            ImageCtrl::PRODUCT_THUMBNAIL,
            ImageCtrl::GALLERY_KEY
        );
    }

    /**
     * @param int $productId
     * @return string
     */
    public static function imageDeleteLinks(int $productId): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return \sprintf(
            "
            DELETE FROM {$jcli}
            WHERE (`type` = %d
            OR `type` = %d)
            AND endpoint_id
            LIKE '%%%s{$productId}'",
            IdentityType::PRODUCT_IMAGE,
            IdentityType::PRODUCT,
            Id::SEPARATOR
        );
    }

    /**
     * @param int $attachmentId
     * @param int $productId
     * @return string
     */
    public static function imageDeleteLink(int $attachmentId, int $productId): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return \sprintf(
            "
            DELETE FROM {$jcli}
            WHERE (`type` = %d
            OR `type` = %d)
            AND endpoint_id
            LIKE '{$attachmentId}%s{$productId}'",
            IdentityType::PRODUCT_IMAGE,
            IdentityType::PRODUCT,
            Id::SEPARATOR
        );
    }
}
