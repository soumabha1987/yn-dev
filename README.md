<p align="center">
    <a
        href="https://creditor.younegotiate.com"
        target="_blank"
    >
        <picture>
            <img
                width="320px"
                alt="YouNegotiate logo"
                src="./public/images/logo.svg"
            >
        </picture>
    </a>
</p>

## You Negotiate
- PHP 8.2
- Other [Laravel requirements](https://laravel.com/docs/10.x/deployment#server-requirements)

### [Installation](./INSTALLATION.md)
- Clone the repo: git clone [REPO_URL] [DIRECTORY_NAME]
- Create `.env` file from the example file: `php -r "file_exists('.env') || copy('.env.example', '.env');"`
- Install the dependencies: `composer install`
- Generate Key: `php artisan key:generate`
- DB migrate: `php artisan migrate`
- Public images: `php artisan storage:link`

#### To enable IDE Helper support, you can run the following command:
```
php artisan ide-helper:models -M
```
