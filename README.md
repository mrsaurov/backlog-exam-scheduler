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
- **Duplicate Prevention**: Client-side and server-side validation prevents selecting the same course multiple times
- **PDF Generation**: Download registration confirmation as PDF
- **Real-time Validation**: Dynamic form validation with instant feedback

### 👨‍💼 Admin Features
- **Student Management**: View, edit, and delete student registrations
- **Verification System**: Mark student registrations as verified/unverified
- **Exam Creation**: Create and manage available exams
- **Automated Scheduling**: Graph coloring algorithm for conflict-free exam scheduling
- **Enhanced UI/UX**: Modern, responsive interface with optimized animations

### 🔧 Technical Features
- **Graph Coloring Algorithm**: Intelligent scheduling to prevent exam time conflicts
- **Dynamic Course Selection**: Prevents duplicate course selection across all forms
- **Responsive Design**: Mobile-friendly interface built with Bootstrap 4
- **Flash Notifications**: User feedback with customizable animation durations
- **Database Migrations**: Version-controlled database schema changes

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
   - After successful registration, download PDF confirmation
   - PDF contains all registered course details

### For Administrators

1. **Login**:
   - Access admin panel via `/login`
   - Default credentials can be set up via seeders

2. **Manage Exams**:
   - Create new exam sessions
   - Set exam dates and details

3. **Student Management**:
   - View all registered students
   - Edit student registrations via modal interface
   - Delete registrations with confirmation
   - Mark students as verified/unverified

4. **Schedule Generation**:
   - Use graph coloring algorithm to generate conflict-free schedules
   - View and export exam timetables

## 🏗️ System Architecture

### Database Schema

#### Tables:
- **users**: Admin authentication
- **available_exams**: Exam session management
- **courses**: Course catalog
- **registered_students**: Student registrations (supports 5 courses)
- **course_exam_mappings**: Course-exam relationships

### Key Models:
- `User`: Admin authentication
- `AvailableExam`: Exam sessions
- `Course`: Course information
- `RegisteredStudent`: Student registrations
- `CourseExamMapping`: Course-exam relationships

### Controllers:
- `HomeController`: Student registration and PDF generation
- `AdminController`: Admin panel functionality
- `AuthController`: Authentication management

## 🎨 Graph Coloring Algorithm

The system uses a graph coloring algorithm to schedule exams without conflicts:

### How it Works:
1. **Graph Construction**: Create conflict graph where courses taken by the same student are connected
2. **Vertex Ordering**: Sort courses by conflict degree (highest first)
3. **Coloring**: Assign time slots ensuring no adjacent courses have the same color
4. **Scheduling**: Map colors to actual time slots

### Example:
```
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

### Time Slots:
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

### PHP Packages:
- `laravel/framework`: Core framework
- `barryvdh/laravel-dompdf`: PDF generation
- `php-flasher/flasher-laravel`: Flash notifications

### JavaScript Packages:
- `bootstrap`: UI framework
- `jquery`: DOM manipulation
- `fancyTable`: Enhanced table functionality

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

### Development Guidelines:
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
