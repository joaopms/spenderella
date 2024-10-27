# Spenderella

> "Spenderella" was inspired by the name "Cinderella", a well-known fairytale character. The idea behind the name "Spenderella" was to create a playful and catchy name that reflects the idea of managing expenses and spending money. The name is meant to convey a sense of fun and lightheartedness, while still being relevant to the core purpose of the application.


## What's Spenderella?

The project consists of an application to manage your money and expenses.
It will connect to your bank accounts so you quickly have access to your latest transactions.
You'll then be able to categorize them and attach transactions to each other, like when you split a bill with someone.


## How to run?

Docker needs to be installed on the host system.

### If using NixOS
Just run `nix-shell` and you'll have a shell set-up with PHP and Composer, along with a `sail` alias.

```sh
nix-shell
sail up -d
```


## Tech Info

The project uses Laravel and runs, in development, with Laravel Sail. Formatting is done with Laravel Pint.
