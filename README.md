# 🎓 Backlog Exam Scheduler

A comprehensive web application built with Laravel for managing backlog exam registrations and scheduling for educational institutions. The system allows students to register for up to 5 backlog courses and uses graph coloring algorithms to automatically schedule conflict-free exam timetables.

![Laravel](https://img.shields.io/badge/Laravel-9.x-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue?style=flat-square&logo=php)
![SQLite](https://img.shields.io/badge/Database-SQLite-lightgrey?style=flat-square&logo=sqlite)
![Bootstrap](https://img.shields.io/badge/UI-Bootstrap_4-purple?style=flat-square&logo=bootstrap)

## 📋 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [System Architecture](#-system-architecture)
- [Graph Coloring Algorithm](#-graph-coloring-algorithm)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 🎯 Student Features

- **Multi-Course Registration**: Register for up to 5 backlog courses per exam session
- **Duplicate Prevention**: Server-side checks prevent selecting the same course multiple times
- **Deadline-aware Forms**: Registration blocked automatically after exam deadline
- **PDF Application**: Download your submitted application as a PDF
- **Registration Lookup**: Retrieve your application by exam and roll
- **Exam Notices**: View active notices (with optional attachments) for each exam

### 👨‍💼 Admin Features

- **Exam Management**: Create, update, delete exams; assign courses per exam
- **Student Management**: View, edit, verify/unverify, and delete registrations
- **Scheduling**: Generate conflict-free exam days via graph coloring (uses only odd-numbered courses per policy)
- **CSV Exports**: Export courses with counts, course dependencies, day-wise schedule, and teacher assignments
- **Notice Management**: Create, update, delete notices; attach files; toggle active/inactive; public file preview
- **Teacher Management**: Add/edit/delete teachers; assign up to two teachers per course (per exam)
- **Bulk/Targeted Email**: Create templates and send general or course-specific emails to assigned teachers (with attachments)

### 🔧 Technical Features

- **Graph Coloring Algorithm**: Simple greedy coloring to avoid course conflicts among verified students (odd-numbered courses)
- **Data Export**: Multiple CSV endpoints for courses, dependencies, schedule, and teacher assignments
- **Flash Notifications**: User feedback via php-flasher
- **PDF Generation**: dompdf for application PDFs
- **Migrations & Seeders**: Versioned schema and optional seed data

## 🚀 Installation

### Prerequisites

- PHP 8.0 or higher
- Composer
- Node.js & NPM (for asset compilation)
- SQLite (default) or MySQL/PostgreSQL

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-username/backlog-exam-scheduler.git
cd backlog-exam-scheduler
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Database Setup

```bash
# Create SQLite database (default)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed
```

### Step 5: Asset Compilation

```bash
# Compile assets for development
npm run dev

# Or for production
npm run prod
```

### Step 6: Start the Application

```bash
# Start development server
php artisan serve

# Application will be available at http://localhost:8000
```

## ⚙️ Configuration

### Database Configuration

Edit `.env` file for database settings:

```env
# SQLite (Default)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# MySQL Alternative
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=backlog_scheduler
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 📖 Usage

### For Students

1. **Registration**:
   - Navigate to the home page
   - Fill in personal details (Name, Roll, Registration)
   - Select up to 5 courses (duplicate prevention enabled)
   - Submit registration

2. **Download Confirmation**:
   - After successful registration, download your application as PDF
   - Or use “Check Registration” with your roll to retrieve the PDF

3. **Exam Notices**:
   - Browse notices for a specific exam
   - Open attached files (PDF/images/docs) in the browser

### For Administrators

1. **Login**:
   - Access admin panel via `/login`
   - Default credentials can be set up via seeders

2. **Manage Exams**:
   - Create new exam sessions
   - Set name, department, series, and deadline
   - Map eligible courses to the exam

3. **Student Management**:
   - View all registered students (sortable by roll)
   - Edit, verify/unverify, and delete registrations
   - Prevent duplicate courses and duplicate rolls per exam

4. **Schedule & Exports**:
   - Generate conflict-free “day-wise” schedule via graph coloring
   - Export CSVs: course list with counts and rolls, dependencies, schedule

5. **Notices**:
   - Create, edit, delete notices; toggle active state
   - Upload a notice file; public inline preview route is available

6. **Teachers & Assignments**:
   - Manage teachers (name, email, phone, designation, department)
   - Assign up to two teachers per course (per exam)
   - Export teacher assignments to CSV

7. **Mailing**:
   - Create general/customized templates or use predefined ones
   - Send general mail to all assigned teachers or customized based on selected courses
   - Optional deadline placeholders and attachments

## 🏗️ System Architecture

### Database Schema

#### Tables

- **users**: Admin authentication
- **available_exams**: Exam session management
- **courses**: Course catalog
- **registered_students**: Student registrations (supports 5 courses)
- **course_exam_mappings**: Course-exam relationships

### Key Models

- `User`: Admin authentication
- `AvailableExam`: Exam sessions
- `Course`: Course information
- `RegisteredStudent`: Student registrations
- `CourseExamMapping`: Course-exam relationships

### Controllers

- `HomeController`: Home, registration, PDF download, exam CRUD, notices (public)
- `AdminController`: Students, courses, schedule/exports, notices (admin)
- `TeacherController`: Teachers, course-teacher assignments, exports
- `MailController`: Mail templates, previews, general/customized sends

## 🎨 Graph Coloring Algorithm

The system uses a graph coloring algorithm to schedule exams without conflicts:

### How it Works

1. **Graph Construction**: Create conflict graph where courses taken by the same student are connected
2. **Vertex Ordering**: Sort courses by conflict degree (highest first)
3. **Coloring**: Assign time slots ensuring no adjacent courses have the same color
4. **Scheduling**: Map colors to actual time slots

Note: By department policy, only odd-numbered courses (based on the numeric part of course code) are considered for scheduling and dependency graphs. Counts and student lists can include all courses where relevant.

### Example

```text
Student A: [Course1, Course2, Course3]
Student B: [Course1, Course4]
Student C: [Course2, Course4]

Conflict Graph:
Course1 ←→ Course2 ←→ Course3
   ↓           ↓
Course4 ←→ ←→ ←→

Result:
Course1: Time Slot 1
Course2: Time Slot 2  
Course3: Time Slot 1 (no conflict with Course1)
Course4: Time Slot 3
```

### Time Slots

- Slot 1: 9:00 AM - 12:00 PM (Day 1)
- Slot 2: 1:00 PM - 4:00 PM (Day 1)
- Slot 3: 9:00 AM - 12:00 PM (Day 2)
- And so on...


## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/StudentRegistrationTest.php

# Run with coverage
php artisan test --coverage
```

## 📦 Dependencies

### PHP Packages

- `laravel/framework`: Core framework
- `barryvdh/laravel-dompdf`: PDF generation
- `php-flasher/flasher-laravel`: Flash notifications

### JavaScript (build/dev)

- `vite`, `laravel-vite-plugin`: Asset build
- `axios`, `lodash`, `postcss`: Utilities and tooling

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for API changes
- Use meaningful commit messages

## 🐛 Known Issues

- Graph coloring algorithm may require optimization for very large datasets
- PDF generation may timeout for bulk operations
- Modal animations may vary across browsers

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Credits

- Built with [Laravel Framework](https://laravel.com)
- PDF generation by [dompdf](https://github.com/dompdf/dompdf)
- UI components by [Bootstrap](https://getbootstrap.com)

---

**For support or questions, please open an issue on GitHub.**
