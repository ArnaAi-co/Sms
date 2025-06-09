# README

## Project Overview
This project is a web application built with PHP, utilizing a Laravel framework. The structure includes controllers, models, views, and routes, with a focus on authentication, user registration, and OTP-based login/register functionality.

## Key Files and Modifications
### Controllers
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (Lines 111-112): Updated to handle authenticated session logic.
- `app/Http/Controllers/Auth/RegisteredUserController.php` (Lines 242-243): Modified to manage registered user sessions.
- `app/Http/Controllers/OtpController.php`: Added for handling OTP-based login and registration processes.

### Helpers
- `app/Helpers/Ippanel.php`: Integrated for SMS functionality. The following code has been added to send a pattern SMS after registration:
  ```php
  $input_data = [
      "name" => $user->name,
  ];

  $ippanel = new \App\Helpers\Ippanel();
  $sms = $ippanel->sendPattern(env('SMS_AFTER_REGISTER_TEXT'), $user->phone_number, $input_data);
  ```

### Views
- `resources/views/auth/login-mobile.blade.php`: Updated to support mobile login interface.

### Commands
- `app/Console/Commands/PackageExpireAlert.php`: Added to send package expiration alerts. Configure this command to run daily using the Laravel scheduler.

## Setup and Usage
1. Ensure all dependencies are installed via Composer.
2. Configure the `.env` file with necessary credentials, including `SMS_AFTER_REGISTER_TEXT`.
3. Schedule the `PackageExpireAlert` command to run daily in the Laravel scheduler (`app/Console/Kernel.php`).
4. Run the application using `php artisan serve`.

## Notes
- The project leverages the Ippanel helper for SMS notifications.
- Regular maintenance of the scheduled command is recommended to ensure timely alerts.

For further details, refer to the Laravel documentation or contact the project maintainer.


- 👤 Author
- Amir Shahamiri
- 🧑‍💻 GitHub: github.com/amirshahamiri
- 💼 LinkedIn: linkedin.com/in/amirshahamiri
