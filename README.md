# CS3332 AllStars - Team Task & Project Management System

**Course**: CS3332 Software Engineering  
**Team**: AllStars (James Ward, Summer Hill, Juan Ledet, Alaric Higgins)  
**Duration**: 9-week intensive project

## Project Overview

A web-based team task and project management system designed to support collaborative software development. Built using agile principles and software engineering best practices learned in CS3332.

### Key Features
- **User Authentication**: Secure registration and login system
- **Project Management**: Create and manage multiple projects with team collaboration
- **Task Management**: Assign tasks, track progress, set due dates
- **Team Collaboration**: Comments, notifications, and activity tracking
- **Role-Based Access**: Member and Project Admin permissions
- **Learning-Focused**: Built-in tooltips and guidance for project management concepts

## Quick Start

### Prerequisites
- **Windows**: XAMPP (recommended) or WAMP
- **Mac/Linux**: PHP 7.4+, MySQL/MariaDB, or MAMP
- Modern web browser (Chrome, Firefox, Safari)

### Setup Instructions

**1. Clone the repository:**
```bash
git clone https://github.com/CS3332-AllStars/Team-Task-and-Project-Management-Tool.git
cd Team-Task-and-Project-Management-Tool
```

**2. Run the automated setup:**

**Windows (XAMPP):**
```bash
setup.bat
```

**Mac/Linux:**
```bash
chmod +x setup.sh
./setup.sh
```

**3. Start development:**
- **XAMPP**: Copy project to `C:\xampp\htdocs\` and visit `http://localhost/Team-Task-and-Project-Management-Tool/`
- **Built-in server**: `php -S localhost:8000` then visit `http://localhost:8000`

### Test Accounts
After running setup, you can login with:
- **Username**: `james_ward`, `summer_hill`, `juan_ledet`, `alaric_higgins`
- **Password**: `password123` (for all accounts)

## Project Structure

```
├── database/
│   ├── schema.sql          # MySQL database structure
│   └── sample_data.sql     # Test data for development
├── docs/
│   ├── Class Diagram.png   # UML class diagram
│   └── Use case diagram.png # UML use case diagram
├── src/
│   ├── config/
│   │   └── database.php    # Database connection (auto-generated)
│   ├── controllers/        # Application logic
│   ├── models/            # Data models (User, Project, Task, Comment)
│   └── views/             # HTML templates
├── test/
│   └── test_cases.md      # Manual testing procedures
├── .env.example           # Environment configuration template
├── setup.bat             # Windows setup script
└── setup.sh              # Unix setup script
```

## Development Workflow

### Requirements Implemented
- ✅ **29 Functional Requirements** (FR-1 through FR-29)
- ✅ **Use Case Diagrams** with 11 use cases
- ✅ **Class Diagrams** with 5 core classes
- ✅ **Database Schema** with proper relationships
- ✅ **Test Plan** with comprehensive scenarios

### Technologies Used
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (planned)
- **Development**: XAMPP/MAMP for local environment
- **Version Control**: Git with GitHub
- **Documentation**: Markdown, UML diagrams

### Team Responsibilities
- **James Ward** (Team Lead): Architecture, database design, testing coordination
- **Summer Hill**: Authentication, user interface design
- **Juan Ledet**: Task management features, backend logic
- **Alaric Higgins**: Integration testing, cross-browser compatibility

## Functional Requirements Coverage

### User Authentication (FR-1 to FR-7)
- User registration and login
- Profile management
- Role-based permissions

### Project Management (FR-8 to FR-13)
- Project creation and administration
- Team membership management
- Progress tracking

### Task Management (FR-14 to FR-20)
- Task creation and assignment
- Status tracking (To Do, In Progress, Done)
- Filtering and organization

### Team Collaboration (FR-21 to FR-25)
- Task comments and discussions
- Activity notifications
- Team activity visibility

### Learning Features (FR-26 to FR-29)
- Contextual help and tooltips
- Project management best practices
- Guided setup for new users

## Testing

### Manual Testing
- Run setup script to get test database
- Use provided test accounts
- Follow test cases in `test/test_cases.md`
- Test across Chrome, Firefox, and Safari

### Database Reset
```bash
# Rerun setup script to reset to clean state
setup.bat    # Windows
./setup.sh   # Mac/Linux
```

## Contributing

### Development Process
1. Create feature branch: `git checkout -b feature-name`
2. Make changes and test locally
3. Commit with descriptive messages
4. Push and create pull request
5. Team review before merging

### Code Standards
- Follow PSR-12 PHP coding standards
- Use meaningful variable and function names
- Comment complex logic
- Ensure security best practices (password hashing, SQL injection prevention)

## Course Context

This project demonstrates software engineering principles learned in CS3332:
- **Requirements Engineering**: Comprehensive FR documentation
- **Design Modeling**: UML diagrams and design patterns
- **Agile Development**: Iterative development with continuous integration
- **Testing**: Manual testing procedures and quality assurance
- **Team Collaboration**: Git workflow and communication protocols

## Support

### Common Issues
- **MySQL Connection Failed**: Ensure XAMPP/MAMP MySQL service is running
- **PHP Errors**: Check PHP version is 7.4+ with required extensions
- **Permission Denied**: On Mac/Linux, run `chmod +x setup.sh`

### Getting Help
- Check existing GitHub Issues
- Review test plan documentation
- Contact team members through Discord
- Reference CS3332 course materials

## License

Educational project for CS3332 Software Engineering course.
