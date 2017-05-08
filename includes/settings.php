<div class="wrap woocommerce">
    <h3><?php _e('Information', TEXT_DOMAIN) ?></h3>
    <p><?php _e('Basic information and credentials of the installed connector. It is needed to configure the connector in the customer center and JTL-Wawi.',
            TEXT_DOMAIN) ?></p>
    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label>Connector URL</label>
            </th>
            <td>
                <p style="margin-top:0"><?php echo get_bloginfo('url') . '/index.php/jtlconnector/' ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php _e('Connector Password', TEXT_DOMAIN) ?></label>
            </th>
            <td>
                <p style="margin-top:0"><?php echo get_option(JtlConnectorAdmin::OPTIONS_TOKEN) ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label>Connector Version</label>
            </th>
            <td>
                <p style="margin-top:0"><?php echo CONNECTOR_VERSION ?></p>
            </td>
        </tr>
        </tbody>
    </table>
    <br>
    <?php woocommerce_admin_fields(JtlConnectorAdmin::get_settings()); ?>
</div>
