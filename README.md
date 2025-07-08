# Assessment Reports CLI Application

This is a Laravel-based CLI application that generates different types of assessment reports (Diagnostic, Progress, Feedback) for students based on JSON data files.

---

## Features

- Generate **Diagnostic** report.
- Generate **Progress** report.
- Generate **Feedback** report.
- Uses **Laravel Artisan Command** interface.
- Runs in a **Docker** container.
- Includes **automated tests**.
- Continuous Integration (CI) using **GitHub Actions** with Docker.

---

## Getting Started

### Prerequisites

- Docker and Docker Compose installed on your system.
- Git installed to clone this repository.

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/your-username/assessment-reports-cli.git
   cd assessment-reports-cli

2. Build and start the Docker containers:

    ```bash
    docker compose build
    docker compose up -d

3. Run the CLI command to generate reports:

    ```bash
    php artisan app:generate-assessment-report

4. To run automated tests:

    ```bash
    php artisan test
