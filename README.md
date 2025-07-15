# Africa Life WordPress Plugin v2.0

A comprehensive WordPress plugin for managing funeral cover applications with agent and admin interfaces.

## ✨ New Features in v2.0

### 🔄 Enhanced Agent Interface
- **Plan-First Selection**: Agents must select a plan before accessing the form
- **Dynamic Form Fields**: Form adapts based on selected plan coverage
- **Separate Script Shortcode**: Use `[africa_life_script]` on any page
- **Modern Step-by-Step Process**: Improved user experience with clear progression

### 🎨 Redesigned Admin Dashboard
- **Modern Dark UI**: Professional black background with white text
- **Improved Navigation**: Icon-based tabs with better visual hierarchy
- **Enhanced Modals**: Better form layouts and user feedback
- **Fixed Agent Creation**: Working agent management system
- **Separate Login Shortcode**: Use `[africa_life_admin_login]` for login pages

### 📋 Plan Management
- **Full CRUD Operations**: Create, edit, and delete plans
- **Category Management**: Add multiple categories per plan with rates and coverage
- **Visual Plan Cards**: Better plan display and selection

## 🚀 Quick Setup

### 1. Install Plugin
Upload and activate the plugin in WordPress.

### 2. Create Pages
Create the following pages with these shortcodes:

**Agent Login/Form Page:**
```
[africa_life_agent_form]
```

**Agent Script Page (optional):**
```
[africa_life_script]
```

**Admin Login Page:**
```
[africa_life_admin_login]
```

**Admin Dashboard Page:**
```
[africa_life_admin_dashboard]
```

### 3. Initial Setup
1. Log in to admin dashboard with your WordPress admin account
2. Go to "Plans" tab and create your first funeral cover plan
3. Go to "Agents" tab and create agent accounts
4. Share agent login credentials with your team

## 📦 Available Shortcodes

| Shortcode | Purpose | Access Required |
|-----------|---------|-----------------|
| `[africa_life_agent_form]` | Complete agent interface with plan selection and form | Agent login |
| `[africa_life_script]` | Agent script widget with minimize/maximize | Agent login |
| `[africa_life_admin_login]` | Admin login form | None |
| `[africa_life_agent_login]` | Agent login form | None |
| `[africa_life_admin_dashboard]` | Full admin dashboard | Admin login |

## 🎯 Agent Workflow

1. **Login**: Agent logs in to access the system
2. **Select Plan**: Choose from available funeral cover plans
3. **Read Script**: Use the script shortcode for customer interaction
4. **Fill Form**: Complete application with customer details
5. **Submit**: PDF is generated and emails are sent automatically
6. **Track**: View submission status in personal dashboard

## 👨‍💼 Admin Features

### Statistics Dashboard
- Total submissions counter
- Approved/declined statistics
- Interactive charts with Chart.js
- Monthly trends analysis

### Submissions Management
- View all submissions in a table
- Update status (Pending/Approved/Declined)
- Download generated PDFs
- Agent performance tracking

### Plans Management
- Create unlimited funeral cover plans
- Add categories (Principal Member, Spouse, Children, etc.)
- Set rates and coverage amounts
- Edit or delete existing plans

### Agent Management
- Create new agent accounts
- View agent statistics
- Delete agents when needed
- Track submission counts per agent

## 📄 PDF Generation

The plugin automatically generates professional PDFs that include:
- **Page 1**: Applicant information and signature
- **Page 2**: Payment mandate details
- **Plan Details**: Selected coverage with rates
- **Automatic Signature**: First initial + last name in italic (e.g., "J. Doe")

## 📧 Email System

Automated emails are sent to:
- **Customer**: Application confirmation with PDF attachment
- **Administrator**: New submission notification with PDF

## 🔒 Security Features

- WordPress nonce verification
- Input sanitization and validation
- Role-based access control
- Secure authentication system
- Agent-only and admin-only areas

## 🎨 Design

- **Modern Dark Theme**: Professional black background
- **Tailwind CSS**: Responsive and mobile-friendly
- **Yellow Accent Color**: Africa Life branding
- **Smooth Animations**: Enhanced user experience
- **Icon-Based Navigation**: Intuitive interface

## 📁 File Structure

```
africa-life/
├── africa-life.php          # Main plugin file
├── includes/                 # Core functionality
│   ├── class-database.php    # Database operations
│   ├── class-roles.php       # User role management
│   ├── class-shortcodes.php  # Shortcode handlers
│   ├── class-ajax-handler.php # AJAX request handling
│   ├── class-pdf-generator.php # PDF generation
│   └── class-email-handler.php # Email functionality
├── admin/                    # Admin dashboard
│   └── class-admin-dashboard.php
├── public/                   # Agent interface
│   └── class-agent-interface.php
├── assets/                   # CSS and JavaScript
│   ├── css/
│   └── js/
└── README.md
```

## 🗄️ Database

Custom tables created:
- `wp_africa_life_submissions` - Application submissions
- `wp_africa_life_plans` - Funeral cover plans with categories
- `wp_africa_life_templates` - Email and PDF templates

## ⚙️ Configuration

### Email Templates
Customizable with placeholders:
- `{customer_name}` - Customer's name
- `{plan_name}` - Selected plan name
- `{plan_details}` - Plan categories and rates
- `{agent_name}` - Agent's name
- `{submission_date}` - Application date

### Plan Categories
Common categories include:
- Principal Member (18-64 years)
- Spouse (18-64 years)
- Children by age groups
- Extended Family Members

## 🔧 Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## 📞 Support

All functionality has been tested and is working:
- ✅ Plan-first agent workflow
- ✅ PDF generation with signatures
- ✅ Email notifications
- ✅ Agent management
- ✅ Plan CRUD operations
- ✅ Modern admin UI
- ✅ Separate shortcodes

## 🆕 Version History

**v2.0.0** - Complete redesign with enhanced UX
**v1.0.0** - Initial release

---

*Developed for Africa Life - Professional funeral cover application management.*