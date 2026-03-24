# Linked Accounts for Flarum

A [Flarum](https://flarum.org) extension that allows users to link multiple accounts and switch between them seamlessly.

## Features

- **Create Sub-Accounts** — Create new accounts linked to your main account
- **Link Existing Accounts** — Link an existing account by verifying its password
- **Instant Switching** — Switch between linked accounts without re-authenticating
- **Post As** — Create discussions and replies as any linked account from the composer
- **Admin Controls** — Configure max accounts per user, log retention, and permissions
- **Activity Log** — Full audit trail of all linking, switching, and unlinking actions
- **Permission System** — Granular control over who can use and create linked accounts

## Installation

Install with Composer:

```bash
composer require ralkage/flarum-ext-linked-accounts:"*"
```

## Updating

```bash
composer update ralkage/flarum-ext-linked-accounts
php flarum migrate
php flarum cache:clear
```

## Configuration

After enabling the extension in the admin panel:

1. **Settings** — Set maximum linked accounts per user and log retention days
2. **Permissions** — Configure which groups can use linked accounts, create new ones, or view others' linked accounts

## How It Works

### For Users
- Navigate to **Linked Accounts** from the user menu
- Create a new linked account or link an existing one (password required for verification)
- Switch between accounts using the **Switch** button on each linked account card
- Use the **Post as** dropdown in the composer to post as any linked account

### For Admins
- View the activity log in the admin panel under the extension settings
- Filter logs by parent user
- Clear logs as needed

## Links

- [Packagist](https://packagist.org/packages/ralkage/flarum-ext-linked-accounts)
- [GitHub](https://github.com/Ralkage/flarum-ext-linked-accounts)
- [Discuss](https://discuss.flarum.org)

## License

MIT
