# Kiedy (When) - Group Availability App

A simple PHP application that allows users to create and share their availability for planning group events. The application does not require user logins; it uses browser cookies/local storage to remember users and features a MySQL database backend.

## 🚀 Getting Started

The easiest way to run the project locally for development is using Docker and Docker Compose. This runs both the PHP server and the MySQL database in isolated containers.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

### Running the App Locally

1. Clone the repository and navigate to the project directory:
   ```bash
   git clone <repository-url>
   cd kiedy
   ```

2. Copy `.env_template` to `.env` and fill in your values (you can also use the defaults).

3. Start the Docker containers:
   ```bash
   docker-compose up -d --build
   ```

4. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```
   
The database will be automatically initialized with the schema defined in `database.sql`.

### Stopping the App

To stop the containers, run:
```bash
docker-compose down
```
If you want to remove the database volume and start fresh on the next run, use:
```bash
docker-compose down -v
```

## 📂 Project Structure

- **`index.php`**: The main landing page featuring the calendar creator.
- **`calendar.php`**: The dedicated subpage for a specific calendar where users can select and share their availability for specific times.
- **`api/`**: Contains the PHP endpoints that handle backend processing (e.g., generating timelines, saving choices).
- **`config.php`**: PDO Database connection configuration parameters.
- **`helpers.php`**: Reusable shared PHP helper functions.
- **`database.sql`**: The initial database schema. This file runs automatically when the MySQL Docker container starts for the first time.
- **`css/` & `js/`**: Application styling and client-side logic.
- **`docker-compose.yml` & `Dockerfile`**: Configuration for running the app in a Docker-containerized environment.
- **`apache-config.conf`**: Apache VirtualHost configuration rules.

## 🤝 How to Contribute

We welcome contributions to make this project better! If you're interested in contributing, follow these steps:

1. **Fork and Branch:** Fork the repository and create a new branch for your feature or bug fix (`git checkout -b feature/your-feature-name`).
2. **Develop Locally:** Make your changes and test them locally using the Docker setup. Be sure to check logs (`docker-compose logs -f`) if you encounter any issues.
3. **Database Changes:** If your changes require modifying the database schema, update `database.sql`. Remember that changing `database.sql` on an existing database container won't auto-apply changes; you will need to restart and clear the volume (`docker-compose down -v && docker-compose up -d`) to apply the schema again.
4. **Code Guidelines:**
   - Keep the PHP logic structured and attempt to separate data processing from frontend presentation.
   - For backend REST API endpoints, ensure appropriate JSON formatting and HTTP status codes are returned upon success/failure.
5. **Submit a Pull Request:** Open a PR against the main branch with a clear description of your changes, how you tested them, and what they solve.

## 🐛 Bug Reports & Feature Requests

If you find a bug or have an idea for a new feature, please open a GitHub Issue with clear, detailed steps to reproduce the problem or a thoroughly defined use case for new ideas.
