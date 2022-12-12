## How to install the project

Clone this repo:
```shell
git clone https://github.com/lartisan/rework-file.git
```

After cloning this repo, run the following commands in the console:
```shell
cd rework-file

composer install

cp .env.example .env

php artisan key:generate
```

## How to proceed


```shell
php artisan rework:file ./public/users.csv 
```

The generated `users.json` will be saved in the `./public` folder.
