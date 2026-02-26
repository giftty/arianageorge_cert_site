# SMTP Configuration Guide

## Securing Sensitive Credentials

The SMTP credentials are now removed from the source code and loaded from secure configuration sources.

### Option 1: Using config.php (Local File)

1. Copy the example configuration:
   ```bash
   cp backend/config.example.php backend/config.php
   ```

2. Edit `backend/config.php` and add your actual SMTP credentials:
   ```php
   return [
       'mail' => [
           'host' => 'mail.arianageorgegroups.com',
           'port' => 465,
           'username' => 'your-email@example.com',
           'password' => 'your-app-password-here',
           'encryption' => 'ssl',
           'from_name' => 'Ariana Groups'
       ]
   ];
   ```

3. The `config.php` file is automatically excluded from git via `.gitignore`

### Option 2: Using Environment Variables (Recommended for Production)

Set the following environment variables in your server or `.env` file:

```bash
MAIL_HOST=mail.arianageorgegroups.com
MAIL_PORT=465
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-app-password-here
MAIL_ENCRYPTION=ssl
```

Environment variables take priority over the config file.

### Option 3: Using .env File (Development)

1. Create a `.env` file in the project root:
   ```
   MAIL_HOST=mail.arianageorgegroups.com
   MAIL_PORT=465
   MAIL_USERNAME=your-email@example.com
   MAIL_PASSWORD=your-app-password-here
   MAIL_ENCRYPTION=ssl
   ```

2. Load the `.env` file in `backend/backend.php` or your bootstrap file:
   ```php
   if (file_exists(__DIR__ . '/../.env')) {
       $env = parse_ini_file(__DIR__ . '/../.env');
       foreach ($env as $key => $value) {
           putenv("$key=$value");
       }
   }
   ```

## Important Security Notes

- **Never commit `config.php` or `.env` files to git** - they are excluded via `.gitignore`
- **Never hardcode credentials in PHP files** - this exposes them in version control history
- **Use app-specific passwords** - Many email providers (Gmail, Microsoft, etc.) allow you to create app-specific passwords that are separate from your account password
- **Rotate credentials regularly** - If credentials are exposed, generate new ones immediately
- **Limit scope** - Use credentials that only have permission for SMTP, not other services

## If Credentials Were Already Exposed

Since these credentials were previously committed to git:

1. **Rotate the password immediately** - Change the SMTP account password
2. **Clean git history**:
   ```bash
   # Using BFG Repo-Cleaner (recommended)
   bfg --replace-text credentials.txt repo.git
   git reflog expire --expire=now --all && git gc --prune=now --aggressive
   git push --force-all
   ```
   OR use `git filter-branch` or `git filter-repo`

3. **Consider the credentials compromised** - Assume anyone with access to the git history could have the old password

4. **Review email account activity** - Check for unauthorized access or sent emails
