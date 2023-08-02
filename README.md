# CHIP for Car Rental System

This code adds CHIP payment method option to [Codecanyon - Car Rental System](https://codecanyon.net/item/car-rental-system-native-wordpress-plugin/11758680).

## Installation

This is not a plugin since the [Car Rental System](https://codecanyon.net/item/car-rental-system-native-wordpress-plugin/11758680) did not provide hooks for extending it's functionality with plugin. Hence, the existing plugin needs to be modified as follows:

1. [Download plugin zip file.](https://github.com/CHIPAsia/chip-for-car-rental-system/archive/refs/heads/main.zip)
1. The file must be extracted as per file structure below:

### File Structure

```text

 /wp-content/plugins/FleetManagement/
  |- Libraries/ChipToFleetManagementTranspiler.php
  |- Models/Chip/ChipPaymentsTable.php

```

## Configuration

- Set Payment Method Name, Payment Method Class, Payment Method Description, Brand ID as Public Key, Secret Key as Private Key, Is Online Payment tick, Enabled tick.

<img src="./assets/001-screenshot.png" alt="drawing" width="900"/>

- You will see the payment method list after setting the payment method.

<img src="./assets/002-screenshot.png" alt="drawing" width="900"/>

## Demo

- Customer choose car to rent

<img src="./assets/003-screenshot.png" alt="drawing" width="900"/>

- Customer confirm the car

<img src="./assets/004-screenshot.png" alt="drawing" width="900"/>

- Customer choose payment method

<img src="./assets/005-screenshot.png" alt="drawing" width="900"/>

- Customer make payment

<img src="./assets/006-screenshot.png" alt="drawing" width="900"/>

- Reservation completed

<img src="./assets/007-screenshot.png" alt="drawing" width="900"/>

- Reservation reflected on admin side

<img src="./assets/008-screenshot.png" alt="drawing" width="900"/>

- Reservation completed

<img src="./assets/009-screenshot.png" alt="drawing" width="900"/>

## Other

Facebook: [Merchants & DEV Community](https://www.facebook.com/groups/3210496372558088)
