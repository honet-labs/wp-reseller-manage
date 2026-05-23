<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Pages {
    use WRPM_Trait_Admin_Core;
    use WRPM_Trait_Admin_Dashboard;
    use WRPM_Trait_Admin_Product_Prices;
    use WRPM_Trait_Admin_Reseller_Products;
    use WRPM_Trait_Admin_Customers;
    use WRPM_Trait_Admin_Sellers;
    use WRPM_Trait_Admin_Active_Products;
    use WRPM_Trait_Admin_Reminders;
    use WRPM_Trait_Admin_Reports;
    use WRPM_Trait_Admin_Settings;
    use WRPM_Trait_Admin_Logs;
}
