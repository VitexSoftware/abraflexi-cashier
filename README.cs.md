# AbraFlexi Cashier

> Automatizované "vysávání" hotovosti z pokladny ve vašem AbraFlexi systému

![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)
![AbraFlexi](https://img.shields.io/badge/AbraFlexi-compatible-brightgreen)
![Dry Run](https://img.shields.io/badge/dry--run-supported-yellow)

## 📋 Popis

Tento nástroj postupně prochází všechny týdny od nejstaršího pohybu v pokladně až po současnost a vystavuje výdajové pokladní doklady dle aktuálního zůstatku.

- Iterace po týdnech (od nejstaršího záznamu)
- Možnost spustit v "dry-run" módu (bez zápisu)
- Integrace s [AbraFlexi REST API](https://doc.abraflexi.eu/)
- Snadné rozšíření a přizpůsobení

## 🛠️ Instalace

```bash
git clone https://github.com/VitexSoftware/abraflexi-cashier.git
cd abraflexi-cashier
composer install
