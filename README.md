# Cashier for AbraFlexi

![Cashier](abraflexi-cashier.svg?raw=true)

**Cashier for AbraFlexi** is a PHP-based tool that helps manage your cash register by generating weekly expense receipts based on the current balance in your AbraFlexi account.

## Features

- Iterates through weeks from the earliest transaction to the most recent
- Calculates weekly cash income
- Automatically creates an expense receipt at the end of each week
- Supports dry-run mode for simulation without data changes
- Easily extendable and configurable

## Configuration

You can set the following environment variables to control behavior:

- `ABRAFLEXI_URL`
- `ABRAFLEXI_LOGIN`
- `ABRAFLEXI_PASSWORD`
- `ABRAFLEXI_CASH_BOX`
- `ABRAFLEXI_CASH_ROW`
- `ABRAFLEXI_CASH_TYPE`
- `ABRAFLEXI_CASH_ACCOUNTING`

## Contributing

Contributions are welcome! Feel free to open issues or submit pull requests.

## License

This project is licensed under the MIT License.

---

**Project Homepage:** [github.com/VitexSoftware/abraflexi-cashier](https://github.com/VitexSoftware/abraflexi-cashier)

MultiFlexi
----------

Cashier for AbraFlexi is ready for run as [MultiFlexi](https://multiflexi.eu) application.

See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)
