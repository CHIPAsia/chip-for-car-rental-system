# CHIP for Car Rental System

This code adds CHIP payment method option to [Car Rental System](https://codecanyon.net/item/car-rental-system-native-wordpress-plugin/11758680).

## Installation

This is not a plugin since the Car Rental System did not provide hooks for extending it's functionality with plugin. Hence, the existing plugin needs to be modified as follows:

1. [Download plugin zip file.](https://github.com/CHIPAsia/chip-for-car-rental-system/archive/refs/heads/main.zip)
1. The file must be extracted as follows:

```
 /wp-content/plugins/FleetManagement/
  |- Libraries/ChipToFleetManagementTranspiler.php
  |- Models/Chip/ChipPaymentsTable.php
```

## Configuration

Set the **Brand ID** and **Secret Key** in the plugins settings.

## Other

Facebook: [Merchants & DEV Community](https://www.facebook.com/groups/3210496372558088)