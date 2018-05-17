<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\WooCommerce\Controller\Image as ImageCtrl;
use jtl\Connector\WooCommerce\Utility\Category as CategoryUtil;

final class SQL
{
    // <editor-fold defaultstate="collapsed" desc="Checksums">
    public static function checksumRead($endpointId, $type)
    {
        return "SELECT checksum
                FROM `jtl_connector_product_checksum`
                WHERE `product_id` = {$endpointId} AND `type` = {$type}";
    }

    public static function checksumWrite($endpointId, $type, $checksum)
    {
        return "INSERT IGNORE INTO `jtl_connector_product_checksum` (`product_id`, `type`, `checksum`)
                VALUES ({$endpointId}, {$type}, '{$checksum}')";
    }

    public static function checksumDelete($endpointId, $type)
    {
        return "DELETE FROM `jtl_connector_product_checksum`
                WHERE `product_id` = {$endpointId} AND `type` = {$type}";
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Category">
    public static function categoryTreeGet($where)
    {
        global $wpdb;

        list($table, $column) = CategoryUtil::getTermMetaData();

        return sprintf("
            SELECT tt.term_id, tt.parent, IF(tm.meta_key IS NULL, 0, tm.meta_value) as sort
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `{$wpdb->terms}` t ON tt.term_id = t.term_id
            LEFT JOIN `{$table}` tm ON tm.{$column} = tt.term_id AND tm.meta_key = 'order'
            WHERE tt.taxonomy = '%s' {$where}
            ORDER BY tt.parent ASC, sort ASC, t.name ASC",
            CategoryUtil::TERM_TAXONOMY
        );
    }

    public static function categoryTreeAddIgnore($categoryId, $level, $sort)
    {
        return sprintf(
            "INSERT IGNORE INTO `%s` VALUES ({$categoryId}, {$level}, {$sort})",
            CategoryUtil::LEVEL_TABLE
        );
    }

    public static function categoryTreeAdd($categoryId, $level, $sort)
    {
        return sprintf(
            "INSERT INTO `%s` VALUES ({$categoryId}, {$level}, {$sort})",
            CategoryUtil::LEVEL_TABLE
        );
    }

    public static function categoryTreeUpdate($categoryId, $level, $sort)
    {
        return sprintf(
            "UPDATE `%s` SET `level` = {$level}, `sort` = {$sort} WHERE `category_id` = {$categoryId}",
            CategoryUtil::LEVEL_TABLE
        );
    }

    public static function categoryTreePreOrderRoot()
    {
        global $wpdb;

        return sprintf("
            SELECT ccl.category_id, ccl.level
            FROM `%s` ccl
            LEFT JOIN {$wpdb->terms} t ON t.term_id = ccl.category_id
            WHERE ccl.level = 0
            ORDER BY ccl.sort, t.slug",
            CategoryUtil::LEVEL_TABLE
        );
    }

    public static function categoryTreePreOrder($categoryId, $level)
    {
        global $wpdb;

        return sprintf("
            SELECT ccl.category_id, ccl.level
            FROM `%s` ccl
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = ccl.category_id
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tt.parent = {$categoryId} AND ccl.level = {$level}
            ORDER BY ccl.sort, t.slug",
            CategoryUtil::LEVEL_TABLE
        );
    }

    public static function categoryPull($limit)
    {
        global $wpdb;

        return sprintf("
            SELECT tt.parent, tt.description, cl.*, t.name, t.slug, tt.count
            FROM `{$wpdb->terms}` t
            LEFT JOIN `{$wpdb->term_taxonomy}` tt ON t.term_id = tt.term_id
            LEFT JOIN `%s` cl ON tt.term_id = cl.category_id
            LEFT JOIN `jtl_connector_link_category` l ON t.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL
            ORDER BY cl.level ASC, tt.parent ASC, cl.sort ASC
            LIMIT {$limit}",
            CategoryUtil::LEVEL_TABLE,
            CategoryUtil::TERM_TAXONOMY
        );
    }

    public static function categoryStats()
    {
        global $wpdb;

        return sprintf("
            SELECT COUNT(tt.term_id)
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `jtl_connector_link_category` l ON tt.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL",
            CategoryUtil::TERM_TAXONOMY
        );
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Cross Selling">
    public static function crossSellingPull($limit = null)
    {
        global $wpdb;

        $limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;

        return "
            SELECT pm.post_id, pm.meta_value
            FROM `{$wpdb->posts}` p
            LEFT JOIN `{$wpdb->postmeta}` pm ON p.ID = pm.post_id
            LEFT JOIN `jtl_connector_link_crossselling` l ON p.ID = l.endpoint_id
            WHERE p.post_type = 'product' AND pm.meta_key = '_crosssell_ids' AND l.host_id IS NULL
            {$limitQuery}";
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Customer">
    public static function customerNotLinked($limit)
    {
        global $wpdb;

        if (is_null($limit)) {
            $select = 'COUNT(DISTINCT(pm.meta_value))';
            $limitQuery = '';
        } else {
            $select = 'DISTINCT(pm.meta_value)';
            $limitQuery = 'LIMIT ' . $limit;
        }

        $status = "'wc-pending', 'wc-processing', 'wc-on-hold'";

        if (\get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes') {
            $status .= ", 'wc-completed'";
        }

        return "
            SELECT {$select}
            FROM `{$wpdb->postmeta}` pm
            LEFT JOIN `{$wpdb->posts}` p ON p.ID = pm.post_id
            LEFT JOIN `jtl_connector_link_customer` l ON l.endpoint_id = pm.meta_value * 1 AND l.is_guest = 0
            WHERE l.host_id IS NULL AND p.post_status IN ({$status}) AND pm.meta_key = '_customer_user' AND pm.meta_value != 0
            {$limitQuery}";
    }

    public static function guestNotLinked($limit)
    {
        global $wpdb;

        $guestPrefix = Id::GUEST_PREFIX . Id::SEPARATOR;

        if (is_null($limit)) {
            $select = 'COUNT(p.ID)';
            $limitQuery = '';
        } else {
            $select = "DISTINCT(CONCAT('{$guestPrefix}', p.ID)) as id";
            $limitQuery = 'LIMIT ' . $limit;
        }

        $status = "'wc-pending', 'wc-processing', 'wc-on-hold'";

        if (\get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes') {
            $status .= ", 'wc-completed'";
        }

        return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN jtl_connector_link_customer l ON l.endpoint_id = CONCAT('{$guestPrefix}', p.ID) AND l.is_guest = 1
            WHERE l.host_id IS NULL AND p.post_status IN ({$status}) AND pm.meta_key = '_customer_user' AND pm.meta_value = 0
            {$limitQuery}";
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Customer Order">
    public static function customerOrderPull($limit)
    {
        global $wpdb;

        if (is_null($limit)) {
            $select = 'COUNT(DISTINCT(p.ID))';
            $limitQuery = '';
        } else {
            $select = 'DISTINCT(p.ID)';
            $limitQuery = 'LIMIT ' . $limit;
        }

        $status = "'wc-pending', 'wc-processing', 'wc-on-hold'";

        if (\get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes') {
            $status .= ", 'wc-completed'";
        }

        $since = \get_option(\JtlConnectorAdmin::OPTIONS_PULL_ORDERS_SINCE);
        $where = (!empty($since) && strtotime($since) !== false) ? "AND p.post_date > '{$since}'" : '';

        return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN jtl_connector_link_order l ON p.ID = l.endpoint_id
            WHERE p.post_type = 'shop_order' AND p.post_status IN ({$status}) AND l.host_id IS NULL {$where}
            ORDER BY p.post_date DESC
            {$limitQuery}";
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Global Data">
    public static function taxRatePull()
    {
        global $wpdb;

        return "SELECT tax_rate_id, tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates";
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Image">
    public static function imageCategoryPull($limit = null)
    {
        global $wpdb;

        $limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;
        list($table, $column) = CategoryUtil::getTermMetaData();

        return sprintf("
            SELECT CONCAT_WS('%s', '%s', p.ID) as id, p.ID as ID, tt.term_id as parent, p.guid
            FROM {$wpdb->term_taxonomy} tt
            RIGHT JOIN {$table} tm ON tt.term_id = tm.{$column}
            LEFT JOIN {$wpdb->posts} p ON p.ID = tm.meta_value
            LEFT JOIN jtl_connector_link_image l ON l.endpoint_id = p.ID AND type = %d
            WHERE l.host_id IS NULL AND tt.taxonomy = '%s' AND tm.meta_key = '%s' AND tm.meta_value != 0
            {$limitQuery}",
            Id::SEPARATOR, Id::CATEGORY_PREFIX, IdentityLinker::TYPE_CATEGORY,
            CategoryUtil::TERM_TAXONOMY, ImageCtrl::CATEGORY_THUMBNAIL
        );
    }

    public static function imageProductThumbnail()
    {
        global $wpdb;

        return sprintf("
            SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN jtl_connector_link_image l ON SUBSTRING_INDEX(l.endpoint_id, '%s', -1) = pm.post_id  AND l.type = %d
            WHERE p.post_type = 'product' AND p.post_status IN ('future', 'publish', 'inherit', 'private') AND 
            pm.meta_key = '%s' AND pm.meta_value != 0 AND l.host_id IS NULL
            GROUP BY p.ID, pm.meta_value",
            Id::SEPARATOR, IdentityLinker::TYPE_PRODUCT, ImageCtrl::PRODUCT_THUMBNAIL
        );
    }

    public static function imageProductGalleryStats()
    {
        global $wpdb;

        return sprintf("
            SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product' AND p.post_status IN ('future', 'publish', 'inherit', 'private') AND 
            pm.meta_key = '%s' AND pm.meta_value != 0",
            ImageCtrl::GALLERY_KEY
        );
    }

    public static function linkedProductImages()
    {
        return sprintf("SELECT endpoint_id FROM jtl_connector_link_image WHERE `type` = '%d'", IdentityLinker::TYPE_PRODUCT);
    }

    public static function imageVariationCombinationPull($limit = null)
    {
        global $wpdb;

        $limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;

        return sprintf("
            SELECT pm.post_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN jtl_connector_link_image l ON SUBSTRING_INDEX(l.endpoint_id, '%s', -1) = pm.post_id AND l.type = %d
            WHERE p.post_type = 'product_variation' AND p.post_status IN ('future', 'publish', 'inherit', 'private') AND 
            pm.meta_key = '%s' AND pm.meta_value != 0 AND l.host_id IS NULL AND p.post_parent IN (SELECT p2.ID FROM {$wpdb->posts} p2)
            {$limitQuery}",
            Id::SEPARATOR, IdentityLinker::TYPE_PRODUCT, ImageCtrl::PRODUCT_THUMBNAIL
        );
    }

    public static function imageCategoryDelete($id)
    {
        list($table, $column) = CategoryUtil::getTermMetaData();

        return sprintf("
            SELECT COUNT({$column})
            FROM {$table}
            WHERE meta_key = '%s' AND meta_value = {$id}",
            ImageCtrl::CATEGORY_THUMBNAIL
        );
    }

    public static function imageProductDelete($id)
    {
        global $wpdb;

        return sprintf("
            SELECT COUNT(meta_id)
            FROM {$wpdb->postmeta}
            WHERE (meta_key = '%s' AND meta_value = {$id}) OR (meta_key = '%s' AND FIND_IN_SET({$id}, meta_value) > 0)",
            ImageCtrl::PRODUCT_THUMBNAIL, ImageCtrl::GALLERY_KEY
        );
    }

    public static function imageDeleteLinks($productId)
    {
        return sprintf("
            DELETE FROM jtl_connector_link_image
            WHERE `type` = %d AND endpoint_id LIKE '%%%s{$productId}'",
            IdentityLinker::TYPE_PRODUCT, Id::SEPARATOR
        );
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Payment">
    public static function paymentCompletedPull($limit = null, $includeCompletedOrders)
    {
        global $wpdb;

        if (is_null($limit)) {
            $select = 'COUNT(DISTINCT(p.ID))';
            $limitQuery = '';
        } else {
            $select = 'DISTINCT(p.ID)';
            $limitQuery = 'LIMIT ' . $limit;
        }

        // Usually processing means paid but exception for Cash on delivery
        $status = "p.post_status = 'wc-processing' AND p.ID NOT IN (SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_value = 'cod')";

        if ($includeCompletedOrders) {
            $status = "(p.post_status = 'wc-completed' OR {$status})";
        }

        $since = \get_option(\JtlConnectorAdmin::OPTIONS_PULL_ORDERS_SINCE);
        $where = (!empty($since) && strtotime($since) !== false) ? "AND p.post_date > '{$since}'" : '';

        return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN jtl_connector_link_payment l ON l.endpoint_id = p.ID
            WHERE p.post_type = 'shop_order' AND l.host_id IS NULL AND {$status} {$where}
            {$limitQuery}";
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Primary Key Mapping">
    public static function primaryKeyMappingHostImage($endpointId, $type)
    {
        return "SELECT host_id
                FROM `jtl_connector_link_image`
                WHERE endpoint_id = '{$endpointId}' AND `type` = {$type}";
    }

    public static function primaryKeyMappingHostCustomer($endpointId, $isGuest)
    {
        return "SELECT `host_id`
                FROM `jtl_connector_link_customer`
                WHERE `endpoint_id` = '{$endpointId}' AND `is_guest` = {$isGuest}";
    }

    public static function primaryKeyMappingHostString($endpointId, $tableName)
    {
        return "SELECT host_id
                FROM `{$tableName}`
                WHERE endpoint_id = '{$endpointId}'";
    }

    public static function primaryKeyMappingHostInteger($endpointId, $tableName)
    {
        return "SELECT host_id
                FROM `{$tableName}`
                WHERE endpoint_id = {$endpointId}";
    }

    public static function primaryKeyMappingEndpoint($hostId, $tableName, $clause)
    {
        return "SELECT endpoint_id
                FROM `{$tableName}`
                WHERE host_id = {$hostId} {$clause}";
    }

    public static function primaryKeyMappingSaveImage($endpointId, $hostId, $type)
    {
        return "INSERT INTO `jtl_connector_link_image` (endpoint_id, host_id, `type`)
                VALUES ('{$endpointId}', {$hostId}, {$type})";
    }

    public static function primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest)
    {
        return "INSERT INTO `jtl_connector_link_customer` (endpoint_id, host_id, is_guest)
                VALUES ('{$endpointId}', {$hostId}, {$isGuest})";
    }

    public static function primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName)
    {
        return "INSERT INTO {$tableName} (endpoint_id, host_id)
                VALUES ({$endpointId}, {$hostId})";
    }

    public static function primaryKeyMappingDelete($where, $tableName)
    {
        return "DELETE FROM {$tableName} {$where}";
    }

    public static function primaryKeyMappingClear()
    {
        return [
            "DELETE FROM jtl_connector_link_category",
            "DELETE FROM jtl_connector_link_customer",
            "DELETE FROM jtl_connector_link_product",
            "DELETE FROM jtl_connector_link_image",
            "DELETE FROM jtl_connector_link_order",
            "DELETE FROM jtl_connector_link_payment",
            "DELETE FROM jtl_connector_link_crossselling",
        ];
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Product">
    public static function productPull($limit = null)
    {
        global $wpdb;

        $limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;

        return "
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN jtl_connector_link_product l ON p.ID = l.endpoint_id
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug IN ('simple', 'variable', 'variation')
            WHERE l.host_id IS NULL AND
            (
                p.post_type = 'product' OR
                (
                    p.post_type = 'product_variation' AND p.post_parent IN
                    (
                        SELECT p2.ID FROM {$wpdb->posts} p2 
                        WHERE p2.post_type = 'product' AND p2.post_status IN ('future', 'publish', 'inherit', 'private')
                    )
                )
            ) AND p.post_status IN ('future', 'publish', 'inherit', 'private') AND (tt.taxonomy IS NULL OR tt.taxonomy = 'product_type')
            ORDER BY p.post_type
            {$limitQuery}";
    }

    public static function productVariationObsoletes($id, $updatedAttributeKeys)
    {
        global $wpdb;

        return sprintf("
            SELECT meta_key
            FROM {$wpdb->postmeta}
            WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ('%s') AND post_id = {$id}",
            implode("','", $updatedAttributeKeys)
        );
    }
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Taxes">
    public static function taxClassByRate($rate)
    {
        global $wpdb;

        return sprintf("
            SELECT tax_rate_class
            FROM {$wpdb->prefix}woocommerce_tax_rates
            WHERE tax_rate = '%s'",
            number_format($rate, 4)
        );
    }
    
    public static function taxRateById($taxRateId)
    {
        global $wpdb;
        
        return "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = {$taxRateId}";
    }
    
    public static function getAllTaxRates()
    {
        global $wpdb;
        
        return "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates";
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="WordPress">
    public static function findTermTaxonomyRelation($productId, $termTaxonomyId)
    {
        global $wpdb;

        return "
            SELECT term_taxonomy_id
            FROM {$wpdb->term_relationships}
            WHERE object_id = {$productId} AND term_taxonomy_id = $termTaxonomyId";
    }

    public static function findTermsForProduct($productId, $taxonomy)
    {
        global $wpdb;

        return "
            SELECT tt.term_taxonomy_id, tt.term_id
            FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE  tr.object_id = {$productId} AND tt.taxonomy = '{$taxonomy}'";
    }

    public static function categoryProductsCount($offset, $limit)
    {
        global $wpdb;

        return "
            SELECT tt.term_taxonomy_id, tt.term_id, COUNT(tr.object_id) as count
            FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = 'product_cat'
            GROUP BY tt.term_taxonomy_id
            OFFSET {$offset}
            LIMIT {$limit}";
    }

    public static function termTaxonomyCountUpdate($termTaxonomyId, $count)
    {
        global $wpdb;

        return "UPDATE {$wpdb->term_taxonomy} SET count = {$count} WHERE term_taxonomy_id = {$termTaxonomyId}";
    }

    public static function categoryMetaCountUpdate($termId, $count)
    {
        list($table, $column) = CategoryUtil::getTermMetaData();

        return "
            UPDATE {$table}
            SET meta_value = {$count} WHERE {$column} = {$termId}
            AND meta_key = 'product_count_product_cat'";
    }

    public static function productTagsCount($offset, $limit)
    {
        global $wpdb;

        return "
            SELECT tt.term_taxonomy_id, COUNT(tr.object_id) as count
            FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = 'product_tag'
            GROUP BY tt.term_taxonomy_id
            OFFSET {$offset}
            LIMIT {$limit}";
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Germanized">
    public static function globalDataMeasurementUnitPull()
    {
        global $wpdb;

        return "
            SELECT tt.term_id as id, t.slug as code
            FROM {$wpdb->term_taxonomy} tt
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_unit'";
    }

    public static function deliveryStatusByText($status)
    {
        global $wpdb;

        return "
            SELECT tt.term_id
            FROM {$wpdb->terms} t
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'product_delivery_time' AND t.name = '{$status}'";
    }
    // </editor-fold>
}
