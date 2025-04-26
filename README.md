# Collibration_APP

Academic Resource Sharing Platform

## Overview

Collibration_APP is a web-based platform designed for academic resource sharing, discussions, study groups, and collaboration among students and educators. It features user authentication, resource management, discussion forums, reporting, and admin controls.

## Features

- User registration and login
- Resource sharing and management
- Discussion forums and comments
- Study group creation and participation
- Private messaging
- Reporting system for inappropriate content
- Admin dashboard for moderation

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL or compatible database
- Web server (Apache, Nginx, or PHP built-in server)

### Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/your-username/Collibration_APP.git
    cd Collibration_APP
    ```

2. Configure your database in `/includes/database.php`.

3. Start the PHP server:
    ```bash
    php -S localhost:8000
    ```

4. Open your browser and go to [http://localhost:8000](http://localhost:8000).

### Folder Structure

- `/includes` - Core PHP includes (auth, database, functions, API handlers)
- `/templates` - HTML/PHP templates for UI
- `/assets` - Static files (CSS, JS, images)

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

[MIT](LICENSE)
